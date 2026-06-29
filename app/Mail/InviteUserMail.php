<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InviteUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $acceptUrl;

    public function __construct(
        public readonly User $user,
        string $token,
    ) {
        $this->acceptUrl = route('invite.accept', ['token' => $token]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been invited to BMMS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invite-user',
        );
    }
}
