<?php

namespace App\Console\Commands;

use App\Mail\CheckInConfirmationMail;
use App\Mail\DropInMail;
use App\Mail\DropOutMail;
use App\Models\CheckIn;
use Illuminate\Console\Command;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * One-command verification of the outgoing mail pipeline.
 *
 * Exists because "did the email actually go out?" has three separate answers —
 * is the SMTP config right, does the transport connect, and is a worker draining
 * the queue — and the app itself swallows mail errors on purpose so a dead SMTP
 * server can never 500 a check-in. This command deliberately does NOT swallow
 * them: it sends synchronously so failures surface here instead of in a log.
 *
 * Usage:
 *   php artisan mail:test                        # show effective config only
 *   php artisan mail:test you@example.com        # + send a plain probe
 *   php artisan mail:test you@example.com --template=confirmation
 */
class MailTest extends Command
{
    protected $signature = 'mail:test
        {recipient? : Address to send the probe to. Omit to only print config.}
        {--template= : Send a real branded template instead of a plain probe: confirmation|drop-in|drop-out}
        {--checkin= : CheckIn id to render the template from (defaults to the most recent).}';

    protected $description = 'Show the effective mail configuration and optionally send a real test email.';

    public function handle(): int
    {
        $this->showConfiguration();

        $recipient = $this->argument('recipient');

        if (! $recipient) {
            $this->newLine();
            $this->comment('No recipient given — configuration shown, nothing sent.');
            $this->comment('Send a probe with:  php artisan mail:test you@example.com');

            return self::SUCCESS;
        }

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('"'.$recipient.'" is not a valid email address.');

            return self::FAILURE;
        }

        return $this->option('template')
            ? $this->sendTemplate($recipient)
            : $this->sendProbe($recipient);
    }

    /**
     * Print what the framework will *actually* use, not what a .env file says.
     * Cached config is the usual reason those two disagree in production.
     */
    private function showConfiguration(): void
    {
        $mailer = config('mail.default');
        $smtp = config('mail.mailers.'.$mailer, []);

        $this->info('Effective mail configuration');
        $this->table(['Setting', 'Value'], [
            ['APP_ENV', config('app.env')],
            ['APP_URL (email logo/links)', config('app.url')],
            ['mail.default', $mailer],
            ['host', $smtp['host'] ?? '—'],
            ['port', $smtp['port'] ?? '—'],
            ['encryption', $smtp['encryption'] ?? '(none)'],
            ['username', $smtp['username'] ?: '(none)'],
            ['password', ($smtp['password'] ?? null) ? '(set, '.strlen($smtp['password']).' chars)' : '(EMPTY)'],
            ['from', config('mail.from.address').' — "'.config('mail.from.name').'"'],
            ['queue connection', config('queue.default')],
        ]);

        $this->warnAboutMisconfiguration($mailer, $smtp);
    }

    /**
     * The mistakes that actually cost delivery, called out before a send is tried.
     */
    private function warnAboutMisconfiguration(string $mailer, array $smtp): void
    {
        $warnings = [];

        if ($mailer === 'log') {
            $warnings[] = 'MAIL_MAILER=log — mail is written to storage/logs, NOT delivered to anyone.';
        }

        $host = $smtp['host'] ?? '';

        if (str_contains($host, 'hostinger')) {
            $from = config('mail.from.address');
            $user = $smtp['username'] ?? '';

            if ($user && $from && strcasecmp($user, $from) !== 0) {
                $warnings[] = "MAIL_FROM_ADDRESS ({$from}) differs from MAIL_USERNAME ({$user}) — Hostinger rejects mail whose From is not the authenticated mailbox.";
            }

            $port = (int) ($smtp['port'] ?? 0);
            $encryption = strtolower((string) ($smtp['encryption'] ?? ''));

            if ($port === 465 && $encryption !== 'ssl') {
                $warnings[] = "Port 465 needs MAIL_ENCRYPTION=ssl (currently '{$encryption}').";
            }

            if ($port === 587 && $encryption !== 'tls') {
                $warnings[] = "Port 587 needs MAIL_ENCRYPTION=tls (currently '{$encryption}').";
            }

            if (empty($smtp['password'])) {
                $warnings[] = 'MAIL_PASSWORD is empty — Hostinger SMTP requires authentication.';
            }
        }

        if (str_contains((string) config('app.url'), 'localhost')) {
            $warnings[] = 'APP_URL points at localhost — the header logo and links break in every mail client.';
        }

        foreach ($warnings as $warning) {
            $this->newLine();
            $this->warn('⚠  '.$warning);
        }
    }

    private function sendProbe(string $recipient): int
    {
        $this->newLine();
        $this->info("Sending plain probe to {$recipient} ...");

        $body = sprintf(
            "PetsLodge SMTP probe.\n\nSent: %s\nEnvironment: %s\nHost: %s\n\nIf you are reading this, outgoing mail works.",
            now()->toDayDateTimeString(),
            config('app.env'),
            config('mail.mailers.'.config('mail.default').'.host'),
        );

        return $this->attemptSend(function () use ($recipient, $body) {
            Mail::raw($body, function ($message) use ($recipient) {
                $message->to($recipient)->subject('PetsLodge SMTP test — '.now()->format('H:i:s'));
            });
        }, $recipient);
    }

    private function sendTemplate(string $recipient): int
    {
        $template = $this->option('template');
        $checkIn = $this->resolveCheckIn();

        if (! $checkIn) {
            $this->error('No check-in found to render. Create one, or pass --checkin=<id>.');

            return self::FAILURE;
        }

        $checkIn->loadMissing(['pet', 'user', 'extraServices']);

        $mailable = match ($template) {
            'confirmation' => new CheckInConfirmationMail(collect([$checkIn])),
            'drop-in' => new DropInMail($checkIn),
            'drop-out' => new DropOutMail($checkIn),
            default => null,
        };

        if (! $mailable) {
            $this->error("Unknown template '{$template}'. Use: confirmation, drop-in or drop-out.");

            return self::FAILURE;
        }

        $petName = $checkIn->pet->name ?? 'n/a';
        $this->newLine();
        $this->info("Sending '{$template}' (check-in #{$checkIn->id}, pet: {$petName}) to {$recipient} ...");

        return $this->attemptSend(
            // Mailable::send() is the immediate path the queue job itself calls.
            // Going through Mail::to(...)->send() would instead enqueue this,
            // because every one of these mailables implements ShouldQueue — and
            // the command would report success without any SMTP having happened.
            function () use ($mailable, $recipient) {
                $mailable->to($recipient);
                $mailable->send(app(Mailer::class));
            },
            $recipient,
        );
    }

    private function resolveCheckIn(): ?CheckIn
    {
        return $this->option('checkin')
            ? CheckIn::find($this->option('checkin'))
            : CheckIn::latest('id')->first();
    }

    private function attemptSend(callable $send, string $recipient): int
    {
        try {
            $send();
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('✗ Send FAILED: '.$e->getMessage());
            $this->newLine();
            $this->line('Common causes:');
            $this->line('  • getaddrinfo failed         → wrong MAIL_HOST, or container cannot reach it');
            $this->line('  • Connection refused/timeout → port blocked by the host firewall (try 587 instead of 465)');
            $this->line('  • Authentication failed      → wrong mailbox password, or username is not the full address');
            $this->line('  • Config looks stale         → run `php artisan config:clear`');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("✓ Accepted by the mail server for {$recipient}.");

        if (str_contains((string) config('mail.mailers.'.config('mail.default').'.host'), 'mailpit')) {
            $this->comment('  Open http://localhost:8025 to read it.');
        } else {
            $this->comment('  Check the inbox AND the spam folder. Spam-filing usually means SPF/DKIM are missing.');
        }

        return self::SUCCESS;
    }
}
