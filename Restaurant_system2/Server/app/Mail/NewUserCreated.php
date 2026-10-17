<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewUserCreated extends Mailable
{
    public string $loginUrl;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
    ) {
        $this->loginUrl = (config('app.frontend_url') ?: 'https://one-hotel-lemon.vercel.app') . '/login';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Account Has Been Created - ' . config('app.name', 'HMS'),
            replyTo: [
                new \Illuminate\Mail\Mailables\Address(
                    config('mail.from.address', 'ashenafisileshi7@gmail.com'),
                    config('mail.from.name', 'HMS')
                ),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-user-created',
            with: [
                'userName'          => trim($this->user->first_name . ' ' . $this->user->last_name),
                'email'             => $this->user->email,
                'temporaryPassword' => $this->temporaryPassword,
                'role'              => ucfirst($this->user->role ?? 'Staff'),
                'loginUrl'          => $this->loginUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
