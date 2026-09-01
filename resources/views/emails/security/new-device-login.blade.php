@extends('emails.layouts.verify')

@section('subject', 'New Device Sign-in Detected')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:14px;font-family:'Space Grotesk',Helvetica,Arial,sans-serif;font-size:20px;font-weight:800;color:#16212c;">
                {{ __('New Device Sign-in Detected') }}
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:20px;font-family:'Inter',Helvetica,Arial,sans-serif;font-size:15px;color:#5a6b7b;line-height:1.7;">
                {{ __('Your account was signed in from a device or location you have not used before.') }}
            </td>
        </tr>
        <tr>
            <td style="padding:18px;background-color:#f6f8fa;border:1px solid #dce3ea;border-radius:8px;margin-bottom:20px;font-family:'Inter',Helvetica,Arial,sans-serif;font-size:14px;color:#16212c;line-height:1.9;">
                <strong style="color:#1F9D6B;display:inline-block;min-width:110px;">{{ __('Device') }}:</strong> {{ $deviceName }}<br>
                <strong style="color:#1F9D6B;display:inline-block;min-width:110px;">{{ __('IP Address') }}:</strong> {{ $ip }}<br>
                <strong style="color:#1F9D6B;display:inline-block;min-width:110px;">{{ __('Time') }}:</strong> {{ $time }}
            </td>
        </tr>
        <tr>
            <td style="padding:6px 0 4px;font-family:'Inter',Helvetica,Arial,sans-serif;font-size:14px;color:#5a6b7b;line-height:1.7;">
                {{ __('If this was you, no action is needed. If it was not you:') }}
            </td>
        </tr>
        <tr>
            <td align="{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" style="padding:12px 0 20px;">
                <a class="btn-verify" href="{{ url('/sessions') }}" style="display:inline-block;background-color:#1F9D6B;color:#ffffff;font-weight:700;font-size:15px;padding:14px 30px;border-radius:8px;text-decoration:none;">
                    <span style="color:#ffffff;">{{ __('Review Active Sessions') }}</span>
                </a>
            </td>
        </tr>
        <tr>
            <td style="padding:16px;background-color:#fdf1f0;border:1px solid #f3c1bd;border-radius:8px;font-family:'Inter',Helvetica,Arial,sans-serif;font-size:13px;color:#7a2e28;line-height:1.7;">
                <strong style="color:#b3362d;">{{ __('Action required if this was not you') }}</strong><br>
                {{ __('Revoke the session, change your master password, and review your security settings immediately.') }}
            </td>
        </tr>
    </table>
@endsection