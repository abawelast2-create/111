<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $reportTitle,
        public string $reportType,
        public array $reportData,
        public string $period,
        public ?string $csvPath = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "تقرير الحضور - {$this->reportTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance-report',
        );
    }

    public function attachments(): array
    {
        if ($this->csvPath && file_exists($this->csvPath)) {
            return [
                \Illuminate\Mail\Mailables\Attachment::fromPath($this->csvPath)
                    ->as("{$this->reportType}_report.csv")
                    ->withMime('text/csv'),
            ];
        }

        return [];
    }
}
