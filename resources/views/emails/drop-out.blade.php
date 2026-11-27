@extends('emails.layouts.base')

@section('title', 'Thank you for visiting')

@section('preheader')
    {{ $pet->name ?? 'Your pet' }} has been picked up — thank you for visiting.
@endsection

@section('content')

    <h1 style="margin:0 0 16px 0; font-size:24px; font-weight:700; color:#4D723C;">
        {{ $pet->name ?? 'Your pet' }} has been picked up
    </h1>

    <p style="margin:0 0 20px 0;">
        Hi {{ $owner->name ?? 'there' }}, this is just to confirm that
        <strong style="color:#644A35;">{{ $pet->name ?? 'your pet' }}</strong>
        has been checked out of {{ config('lodge.name') }}.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="margin:0 0 20px 0; background-color:#FFFBEB; border-left:4px solid #7EAF67; border-radius:6px;">
        <tr>
            <td style="padding:16px 20px; font-size:14px; color:#54595F;">
                <p style="margin:0 0 4px 0;">
                    <strong style="color:#4D723C;">Check-in ID:</strong>
                    #{{ str_pad($checkIn->id, 4, '0', STR_PAD_LEFT) }}
                </p>
                <p style="margin:0;">
                    <strong style="color:#4D723C;">Picked up:</strong>
                    {{ ($checkIn->check_out ?? now())->format('M j, Y \a\t g:i A') }}
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px 0;">
        Thank you for trusting us with {{ $pet->name ?? 'your pet' }} — it was a pleasure
        having them stay with us.
    </p>

    <p style="margin:0;">
        We hope to see you both again soon!
    </p>

@endsection
