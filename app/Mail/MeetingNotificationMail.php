<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Meeting $meeting,
        public readonly User    $recipient,
        public readonly string  $transitionType,
        public readonly string  $notificationTitle,
        public readonly string  $notificationBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->notificationTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.meeting-notification');
    }
}
