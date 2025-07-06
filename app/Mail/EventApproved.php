<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventApproved extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The event instance.
     *
     * @var mixed
     */
    public $event;

    /**
     * Create a new message instance.
     */
    public function __construct($event)
    {
         $this->event = $event;
    }

    public function build()
{
    return $this->subject('Your Event Has Been Approved!')
                ->view('emails.event_approved');
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
