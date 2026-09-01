<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class RecoveryCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * A one-time recovery OTP is delivered inside the cyber-themed master
     * layout so it renders consistently and securely across every client.
     * Localization is forced to the recipient's chosen UI locale.
     *
     * @param  string  $code  The 6-digit one-time recovery code.
     * @param  string  $userLocale  The locale to render the email in (en | ar).
     */
    public function __construct(
        public readonly string $code,
        public readonly string $userLocale = 'en',
    ) {}

    /**
     * Get the message envelope.
     *
     * @return Envelope The envelope definition.
     */
    public function envelope(): Envelope
    {
        App::setLocale($this->userLocale);

        return new Envelope(
            subject: __('Account Recovery Code'),
        );
    }

    /**
     * Get the message content definition.
     *
     * Renders both a themed HTML view and a plain-text fallback for
     * clients that disable HTML.
     *
     * @return Content The message content definitions.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.recovery-code',
            text: 'emails.auth.recovery-code-text',
            with: [
                'code' => $this->code,
                'appName' => config('app.name', 'ZeroKnowledgePM'),
            ],
        );
    }
}
