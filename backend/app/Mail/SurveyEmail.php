<?php

namespace App\Mail;

use App\Models\Recipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveyEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $recipient;
    public $campaign;
    public $renderedBody;

    /**
     * Create a new message instance.
     */
    public function __construct(Recipient $recipient)
    {
        $this->recipient = $recipient;
        $this->campaign = $recipient->campaign;

        // Render template with placeholders
        $this->renderedBody = $this->renderTemplate();
    }

    /**
     * Render the email body template with placeholders
     */
    private function renderTemplate(): string
    {
        $template = $this->campaign->message_template['body'] ?? '';
        $responseLink = $this->recipient->getResponseLink();

        // Replace placeholders
        $replacements = [
            '{{name}}' => $this->recipient->name,
            '{{email}}' => $this->recipient->email,
            '{{link}}' => $responseLink,
            '{{campaign_name}}' => $this->campaign->name,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->campaign->message_template['subject'] ?? 'Pesquisa de Satisfação';

        // Replace placeholders in subject
        $subject = str_replace('{{name}}', $this->recipient->name, $subject);

        return new Envelope(
            from: new Address(
                $this->campaign->sender_email ?? config('mail.from.address'),
                $this->campaign->sender_name ?? config('mail.from.name')
            ),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            text: 'emails.survey-text',
            with: [
                'body' => $this->renderedBody,
                'recipientName' => $this->recipient->name,
                'campaignName' => $this->campaign->name,
                'responseLink' => $this->recipient->getResponseLink(),
            ]
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
