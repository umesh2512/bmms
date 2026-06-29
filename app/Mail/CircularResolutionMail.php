<?php

namespace App\Mail;

use App\Models\Resolution;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CircularResolutionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Resolution $resolution,
        public readonly User       $recipient,
        public readonly string     $voteUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Circular Resolution — Your Vote Required: ' . $this->resolution->title);
    }

    public function content(): Content
    {
        return new Content(view: 'circular-resolution.mail');
    }
}
