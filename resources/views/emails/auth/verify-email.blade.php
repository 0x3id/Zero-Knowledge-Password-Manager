@extends('emails.layouts.verify')

@section('subject', 'Verify Your Email Address')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:14px;font-family:'Space Grotesk',Helvetica,Arial,sans-serif;font-size:20px;font-weight:800;color:#16212c;">
                {{ __('Welcome, :name!', ['name' => $user->username]) }}
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:20px;font-family:'Inter',Helvetica,Arial,sans-serif;font-size:15px;color:#5a6b7b;line-height:1.7;">
                {{ __('One last step to secure your account.') }}<br>
                {{ __('Confirm that :email belongs to you to activate your zero-knowledge vault.', ['email' => $user->email]) }}
            </td>
        </tr>
        <tr>
            <td align="{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" style="padding:6px 0 22px;">
                <a class="btn-verify" href="{{ $verificationUrl }}" style="display:inline-block;background-color:#1F9D6B;color:#ffffff;font-weight:700;font-size:15px;padding:14px 30px;border-radius:8px;text-decoration:none;">
                    <span>{{ __('Verify Email Address') }}</span>
                </a>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:18px;font-family:'Inter',Helvetica,Arial,sans-serif;font-size:13px;color:#8b95a5;line-height:1.6;">
                {{ __('If the button above does not work, copy and paste this link into your browser:') }}<br>
                <span style="color:#5a6b7b;word-break:break-all;">{{ $verificationUrl }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:16px;background-color:#f6f8fa;border:1px solid #dce3ea;border-radius:8px;font-family:'Inter',Helvetica,Arial,sans-serif;font-size:13px;color:#5a6b7b;line-height:1.7;">
                <strong style="color:#1F9D6B;">{{ __('Security notice') }}</strong><br>
                {{ __('This link expires in 60 minutes and can be used only once. If you did not register this account, no further action is required and this message can be safely ignored.') }}
            </td>
        </tr>
    </table>
@endsection