<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $tickets;

    public function __construct($tickets)
    {
        $this->tickets = is_array($tickets) ? collect($tickets) : collect([$tickets]);
    }

        public function build()
    {
        $firstTicket = $this->tickets->first();

        return $this->subject('Your Ticket Confirmation for ' . $firstTicket->event->title)
                    ->view('emails.ticket_confirmation');
    }

    
}
