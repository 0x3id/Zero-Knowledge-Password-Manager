<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class VerifyEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $userLocale = 'en',
        public string $verificationUrl = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Verify Your Email Address'),
        );
    }

    public function content(): Content
    {
        App::setLocale($this->userLocale);

        return new Content(
            view: 'emails.auth.verify-email',
            text: 'emails.auth.verify-email-text',
            with: [
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
                'appName' => config('app.name', 'ZeroKnowledgePM'),
            ],
        );
    }
}