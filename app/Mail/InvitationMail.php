<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $acceptUrl;

    public function __construct(public Invitation $invitation)
    {
        $this->acceptUrl = route('invitation.accept', $invitation->token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación al proyecto ' . $this->invitation->project->name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invitation');
    }
}
