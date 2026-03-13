<?php

namespace HeritageApps\Help\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AiConversationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $pdfPath,
        public int $messageCount
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your AI Assistant Conversation',
        );
    }

    public function content(): Content
    {
        $appName   = config('help.app_name', 'the system');
        $emailBody = '<p>Hi,</p>'
            . "<p>Your AI Assistant conversation with {$this->messageCount} message(s) is attached as a PDF.</p>"
            . "<p>Thank you for using the AI Assistant!</p>";

        return new Content(
            htmlString: $emailBody,
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('AI-Conversation-' . now()->format('Y-m-d') . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
