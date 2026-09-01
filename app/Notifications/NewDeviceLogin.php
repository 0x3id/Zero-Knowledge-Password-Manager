<?php

namespace App\Notifications;

use App\Mail\NewDeviceLoginMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewDeviceLogin extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new device-alert notification instance.
     *
     * Raised by SessionManager only when a sign-in originates from a
     * device/IP combination never seen for the account, to avoid alert
     * fatigue. Delivery is delegated to a cyber-themed Mailable so the
     * message renders through the application's master email layout.
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
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable  The receiving entity.
     * @return array<int, string> The mail channel.
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the themed mail representation for the notification.
     *
     * @param  mixed  $notifiable  The receiving entity (User).
     * @return NewDeviceLoginMail The themed mail message.
     */
    public function toMail(mixed $notifiable): NewDeviceLoginMail
    {
        return (new NewDeviceLoginMail($this->deviceName, $this->ip, $this->userLocale))
            ->to($notifiable->getEmailForVerification() ?? $notifiable->email);
    }
}
