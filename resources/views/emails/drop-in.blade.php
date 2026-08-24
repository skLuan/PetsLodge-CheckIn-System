@extends('emails.layouts.base')

@section('title', 'Your pet has arrived')

@section('preheader')
    {{ $pet->name ?? 'Your pet' }} is settling in with us.
@endsection

@section('content')

    <h1 style="margin:0 0 16px 0; font-size:24px; font-weight:700; color:#4D723C;">
        {{ $pet->name ?? 'Your pet' }} has arrived safely
    </h1>

    <p style="margin:0 0 20px 0;">
        Hi {{ $owner->name ?? 'there' }}, we've got
        <strong style="color:#644A35;">{{ $pet->name ?? 'your pet' }}</strong>
        settled in at {{ config('lodge.name') }}. They're in good hands.
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
                    <strong style="color:#4D723C;">Dropped in:</strong>
                    {{ now()->format('M j, Y \a\t g:i A') }}
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0;">
        We'll let you know as soon as {{ $pet->name ?? 'your pet' }} is picked up.
        If you need anything in the meantime, just reach out using the details below.
    </p>

@endsection
