Hello {{ $user->username }},

One last step to secure your account. Confirm that {{ $user->email }} belongs
to you to activate your zero-knowledge vault.

Verify Email Address:
{{ $verificationUrl }}

This link expires in 60 minutes and can be used only once. If you did not
register this account, no further action is required and this message can
be safely ignored.

This is an automated security message from {{ config('app.name', 'ZeroKnowledgePM') }}.