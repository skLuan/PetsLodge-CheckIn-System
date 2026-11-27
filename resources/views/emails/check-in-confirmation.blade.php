@extends('emails.layouts.base')

@section('title', 'Your booking is confirmed')

@section('preheader')
    We've confirmed the booking for {{ $checkIns->map(fn ($c) => $c->pet->name ?? 'your pet')->implode(', ') }}.
@endsection

@section('content')

    <h1 style="margin:0 0 16px 0; font-size:24px; font-weight:700; color:#4D723C;">
        Your booking is confirmed
    </h1>

    <p style="margin:0 0 20px 0;">
        Hi {{ $owner->name ?? 'there' }}, thank you for choosing {{ config('lodge.name') }}.
        We've received {{ $checkIns->count() === 1 ? 'the check-in below' : 'the check-ins below' }}
        and we're looking forward to the visit.
    </p>

    {{-- One card per pet in this submission. --}}
    @foreach ($checkIns as $checkIn)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
               style="margin:0 0 16px 0; background-color:#FFFBEB; border-left:4px solid #7EAF67; border-radius:6px;">
            <tr>
                <td style="padding:16px 20px;">
                    <p style="margin:0 0 6px 0; font-size:18px; font-weight:700; color:#644A35;">
                        {{ $checkIn->pet->name ?? 'Your pet' }}
                        @if ($checkIn->pet?->kindOfPet?->name)
                            <span style="font-size:14px; font-weight:400; color:#54595F;">
                                ({{ $checkIn->pet->kindOfPet->name }})
                            </span>
                        @endif
                    </p>

                    <p style="margin:0 0 4px 0; font-size:14px; color:#54595F;">
                        <strong style="color:#4D723C;">Check-in ID:</strong>
                        #{{ str_pad($checkIn->id, 4, '0', STR_PAD_LEFT) }}
                    </p>

                    @if ($checkIn->check_in)
                        <p style="margin:0 0 4px 0; font-size:14px; color:#54595F;">
                            <strong style="color:#4D723C;">Checked in:</strong>
                            {{ $checkIn->check_in->format('M j, Y \a\t g:i A') }}
                        </p>
                    @endif

                    @if ($checkIn->extraServices->isNotEmpty())
                        <p style="margin:8px 0 0 0; font-size:14px; color:#54595F;">
                            <strong style="color:#4D723C;">Services booked:</strong>
                        </p>
                        <ul style="margin:4px 0 0 0; padding-left:20px; font-size:14px; color:#54595F;">
                            @foreach ($checkIn->extraServices as $service)
                                <li style="margin:0 0 2px 0;">
                                    {{ ucfirst($service->name) }}
                                    @if ($service->pivot->grooming_appointment_day)
                                        — {{ $service->pivot->grooming_appointment_day }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </td>
            </tr>
        </table>
    @endforeach

    <p style="margin:24px 0 0 0; font-size:14px; color:#54595F;">
        By completing this check-in you accepted our Terms &amp; Conditions, including the
        health and vaccination requirements.
    </p>

    <p style="margin:16px 0 0 0;">
        If anything above looks wrong, please get in touch and we'll fix it right away.
    </p>

@endsection
