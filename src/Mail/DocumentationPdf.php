<?php

declare(strict_types=1);

namespace HeritageApps\Help\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentationPdf extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $articleTitle,
        public readonly string $pdfContent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Help Documentation: ' . $this->articleTitle,
        );
    }

    public function content(): Content
    {
        $body = '<p>Hi,</p>'
            . '<p>Please find attached the help documentation article <strong>' . e($this->articleTitle) . '</strong>.</p>';

        return new Content(htmlString: $body);
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $filename = 'Help-' . preg_replace('/[^a-zA-Z0-9\-]/', '-', $this->articleTitle) . '.pdf';

        return [
            Attachment::fromData(fn () => $this->pdfContent, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
