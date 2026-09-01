@php
    $emailDir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $brandName = config('app.name', 'ZeroKnowledgePM');
    $heroUrl = url('/');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $emailDir }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="x-apple-disable-message-reformatting">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="color-scheme" content="light">
        <meta name="supported-color-schemes" content="light">
        <meta name="format-detection" content="telephone=no">
        <title>@yield('subject', $brandName)</title>
        <!--[if mso]>
        <style type="text/css">
            table { border-collapse: collapse; }
            .container { width: 600px !important; }
            .row-pad { padding: 24px !important; }
        </style>
        <![endif]-->
        <style type="text/css">
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { margin: 0; padding: 0; width: 100%; background-color: #f6f8fa; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
            a { text-decoration: none; }
            .hk { display: block; max-width: 600px; margin: 0 auto; padding: 32px 24px; }
            .shell { width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; }
            .card { background-color: #ffffff; border: 1px solid #dce3ea; border-radius: 12px; }
            .btn-verify { display: inline-block; background-color: #1F9D6B; color: #ffffff; font-weight: 700; font-size: 15px; padding: 14px 30px; border-radius: 8px; text-decoration: none; mso-padding-alt: 0; }
            .btn-verify span { color: #ffffff; text-decoration: none; }
            .footer-text { color: #8b95a5; }
            @media screen and (max-width: 480px) {
                .hk { padding: 16px 12px; }
                .container-pad { padding: 20px 16px !important; }
                .btn-verify { width: 100% !important; display: block !important; text-align: center !important; }
                .stack { display: block !important; width: 100% !important; }
            }
        </style>
    </head>
    <body style="margin:0;padding:0;width:100%;background-color:#f6f8fa;-webkit-text-size-adjust:100%;">
        <center role="article" aria-roledescription="email" lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="width:100%;background-color:#f6f8fa;">
            <!--[if mso]>
            <table role="presentation" width="600" align="center" cellpadding="0" cellspacing="0"><tr><td>
            <![endif]-->

            <div class="hk">
                <div class="shell">

                    {{-- Brand header --}}
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="card">
                        <tr>
                            <td class="container-pad" style="padding:24px 32px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td align="center" style="padding-bottom:12px;">
                                            <a href="{{ $heroUrl }}" style="text-decoration:none;">
                                                <img src="{{ url('icons/icon-192.png') }}" alt="{{ $brandName }}" width="64" height="64" style="display:block;width:64px;height:64px;border:0;outline:none;text-decoration:none;border-radius:14px;">
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding-bottom:2px;">
                                            <a href="{{ $heroUrl }}" style="text-decoration:none;">
                                                <span style="font-family:'Space Grotesk','Inter',Helvetica,Arial,sans-serif;font-size:20px;font-weight:800;color:#16212c;">
                                                    {{ $brandName }}
                                                </span>
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    {{-- Body slot --}}
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="card" style="margin-top:16px;">
                        <tr>
                            <td class="container-pad" style="padding:32px;color:#16212c;font-family:'Space Grotesk',Helvetica,Arial,sans-serif;font-size:15px;line-height:1.65;">
                                @yield('content')
                            </td>
                        </tr>
                    </table>

                    {{-- Footer --}}
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td align="center" class="container-pad" style="padding:26px 16px 4px;">
                                <table role="presentation" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td align="center" style="padding-bottom:10px;">
                                            <span class="footer-text" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;">
                                                @yield('footer_security', __('This is an automated security message from :app.', ['app' => $brandName]))
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding-bottom:6px;">
                                            <span class="footer-text" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;">
                                                {{ __('If you didn\'t create this account, you can safely ignore this email.') }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding-bottom:8px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.7;">
                                            <span class="footer-text">
                                                {{ $brandName }} &copy; {{ now()->year }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                </div>
            </div>

            <!--[if mso]>
            </td></tr></table>
            <![endif]-->
        </center>
    </body>
</html>