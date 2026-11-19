<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CarAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Alert $alert) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->alert->reasonEmoji().' '.$this->alert->vehicle->name.': '.$this->alert->reasonLabel(),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.car-alert');
    }
}
