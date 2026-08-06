{{--
    Shared layout for every transactional email.

    Email clients strip <style> blocks and ignore external CSS, so everything
    here is inline and table-based. Colours mirror tailwind.config.js:
    green #7EAF67 / #4D723C, brown #644A35, sand #FFFBEB, grey #54595F.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', config('lodge.name'))</title>
</head>
<body style="margin:0; padding:0; background-color:#ECEDEE; -webkit-font-smoothing:antialiased;">

    {{-- Preheader: the grey preview line in the inbox list. --}}
    <div style="display:none; font-size:1px; color:#ECEDEE; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        @yield('preheader')
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background-color:#ECEDEE; padding:24px 12px;">
        <tr>
            <td align="center">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                       style="width:100%; max-width:600px; background-color:#FCFCFC; border-radius:12px; overflow:hidden; font-family:'Nunito', Helvetica, Arial, sans-serif;">

                    {{-- Header --}}
                    <tr>
                        <td align="center" style="background-color:#7EAF67; padding:28px 24px;">
                            <img src="{{ asset('images/logo-pets-lodge.png') }}"
                                 alt="{{ config('lodge.name') }}"
                                 width="120"
                                 style="display:block; width:120px; max-width:120px; height:auto; border:0; margin:0 auto 12px auto;">
                            <div style="font-size:20px; font-weight:700; color:#FFFBEB; letter-spacing:0.3px;">
                                {{ config('lodge.name') }}
                            </div>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 32px 24px 32px; color:#54595F; font-size:16px; line-height:1.6;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#FFFBEB; padding:24px 32px; border-top:2px solid #D7E2D1;">
                            <p style="margin:0 0 8px 0; font-size:14px; font-weight:700; color:#644A35;">
                                {{ config('lodge.name') }}
                            </p>

                            @if (config('lodge.address'))
                                <p style="margin:0 0 4px 0; font-size:13px; color:#54595F;">
                                    {{ config('lodge.address') }}
                                </p>
                            @endif

                            @if (config('lodge.phone'))
                                <p style="margin:0 0 4px 0; font-size:13px; color:#54595F;">
                                    Phone: <a href="tel:{{ preg_replace('/[^0-9+]/', '', config('lodge.phone')) }}"
                                              style="color:#4D723C; text-decoration:none;">{{ config('lodge.phone') }}</a>
                                </p>
                            @endif

                            @if (config('lodge.email'))
                                <p style="margin:0 0 4px 0; font-size:13px; color:#54595F;">
                                    Email: <a href="mailto:{{ config('lodge.email') }}"
                                              style="color:#4D723C; text-decoration:none;">{{ config('lodge.email') }}</a>
                                </p>
                            @endif

                            @if (config('lodge.website'))
                                <p style="margin:0 0 4px 0; font-size:13px; color:#54595F;">
                                    <a href="{{ config('lodge.website') }}"
                                       style="color:#4D723C; text-decoration:none;">{{ config('lodge.website') }}</a>
                                </p>
                            @endif

                            <p style="margin:12px 0 0 0; font-size:12px; color:#A0A5AB;">
                                This is an automated message — please don't reply to this address.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
