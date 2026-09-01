@extends('emails.layouts.verify')

@section('subject', 'Account Recovery Code')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:14px;font-family:'Space Grotesk',Helvetica,Arial,sans-serif;font-size:20px;font-weight:800;color:#16212c;">
                {{ __('Account Recovery Code') }}
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:22px;font-family:'Inter',Helvetica,Arial,sans-serif;font-size:15px;color:#5a6b7b;line-height:1.7;">
                {{ __('Use the one-time code below to prove account ownership before unlocking your encrypted recovery material.') }}
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:12px 0 22px;">
                <span style="display:inline-block;letter-spacing:6px;font-family:'SF Mono',Menlo,Monaco,Consolas,monospace;font-size:30px;font-weight:800;color:#1F9D6B;background-color:#e9f7f1;border:1px dashed rgba(31,157,107,0.5);border-radius:12px;padding:14px 28px;">{{ $code }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:16px;background-color:#f6f8fa;border:1px solid #dce3ea;border-radius:8px;font-family:'Inter',Helvetica,Arial,sans-serif;font-size:13px;color:#5a6b7b;line-height:1.7;">
                <strong style="color:#1F9D6B;">{{ __('Security notice') }}</strong><br>
                {{ __('This code expires in 10 minutes and can be used only once. Never share it with anyone. If you did not request this, ignore this email and consider reviewing your account security.') }}
            </td>
        </tr>
    </table>
@endsection