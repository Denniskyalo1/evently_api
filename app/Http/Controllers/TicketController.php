<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketConfirmation;

class TicketController extends Controller
{
        public function store(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'quantity' => 'required|integer|min:1',
            'payment_id' => 'nullable|string',
        ]);

        $user = Auth::user();
        $event = Event::findOrFail($request->event_id);
        $quantity = $request->quantity;
        $paymentId = $request->payment_id;

        $existing = Ticket::where('user_id', $user->id)
        ->where('event_id', $event->id)
        ->where('expires_at', '>', now())
        ->exists();

       if ($existing) {
        return response()->json([
            'message' => 'You already have a valid ticket for this event.'
        ], 409);
       }


        $tickets = [];

        for ($i = 0; $i < $quantity; $i++) {
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'qr_code' => (string) Str::uuid(),
                'status' => 'confirmed',
                'payment_id' => $paymentId,
            ]);

            $tickets[] = $ticket;
        }

        Mail::to($user->email)->send(new TicketConfirmation($tickets));

        return response()->json([
            'message' => 'Tickets purchased successfully.',
            'tickets' => $tickets,
        ], 201);
    }
}
