<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class SendInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param string $recipientEmail
     * @param string|null $studentName
     * @param string $instructorName
     * @param string $token
     * @param string $tenantName
     */
    public function __construct(
        public readonly string $recipientEmail,
        public readonly ?string $studentName,
        public readonly string $instructorName,
        public readonly string $token,
        public readonly string $tenantName,
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Convite para vincular com o instrutor {$this->instructorName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitation',
            with: [
                'recipientEmail' => $this->recipientEmail,
                'studentName' => $this->studentName,
                'instructorName' => $this->instructorName,
                'token' => $this->token,
                'tenantName' => $this->tenantName,
                'acceptLink' => route('api.invitations.accept', ['token' => $this->token]),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
