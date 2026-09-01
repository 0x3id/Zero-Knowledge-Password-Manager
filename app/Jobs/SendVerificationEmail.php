<?php

namespace App\Jobs;

use App\Models\User;
use App\Mail\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class SendVerificationEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $userLocale = 'en',
    ) {}

    public function handle(): void
    {
        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);

        DB::table('email_verification_tokens')->insert([
            'id' => Str::uuid(),
            'user_id' => $this->user->id,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addMinutes(60),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $this->user->getKey(), 'hash' => $tokenHash],
        );

        \Illuminate\Support\Facades\Mail::to($this->user->email)->send(
            new VerifyEmail($this->user, $this->userLocale, $verificationUrl)
        );
    }
}