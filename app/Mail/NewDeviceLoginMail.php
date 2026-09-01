<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class NewDeviceLoginMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new device-alert message instance.
     *
     * Delivers a security alert when a login originates from a new
     * device/IP combination, rendered through the cyber-themed master
     * email layout under the recipient's chosen UI locale.
     *
     * @param  string  $deviceName  The parsed device label.
     * @param  string  $ip  The source IP address.
     * @param  string  $userLocale  The locale to render the email in (en | ar).
     */
    public function __construct(
        public string $deviceName,
        public string $ip,
        public string $userLocale = 'en',
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
            subject: __('New Device Sign-in Detected'),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return Content The themed HTML and plain-text content.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.security.new-device-login',
            text: 'emails.security.new-device-login-text',
            with: [
                'deviceName' => $this->deviceName,
                'ip' => $this->ip,
                'time' => now()->toDateTimeString(),
                'appName' => config('app.name', 'ZeroKnowledgePM'),
            ],
        );
    }
}
