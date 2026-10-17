<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordResetMail extends Mailable
{
    public string $resetUrl;

    public function __construct(
        public User $user,
        public string $token,
    ) {
        $frontendUrl   = config('app.frontend_url', env('FRONTEND_URL', 'https://one-hotel-lemon.vercel.app'));
        $this->resetUrl = "{$frontendUrl}/reset-password?token={$token}&email={$user->email}";
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password - ' . config('app.name', 'HMS'),
            replyTo: [
                new Address(
                    config('mail.from.address', 'ashenafisileshi7@gmail.com'),
                    config('mail.from.name', 'HMS')
                ),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'user'        => $this->user,
                'firstName'   => $this->user->first_name,
                'lastName'    => $this->user->last_name,
                'email'       => $this->user->email,
                'resetUrl'    => $this->resetUrl,
                'expiresIn'   => '60 minutes',
                'hotelName'   => env('HOTEL_NAME', config('app.name')),
                'hotelPhone'  => env('HOTEL_PHONE', '+251-800-000-0000'),
                'hotelEmail'  => env('HOTEL_EMAIL', 'ashenafisileshi7@gmail.com'),
                'hotelWebsite'=> env('APP_URL', 'https://executivehorizon.com'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
