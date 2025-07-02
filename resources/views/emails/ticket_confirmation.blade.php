<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket Confirmation</title>
</head>
<body>
    <h2>🎫 Ticket Confirmation</h2>

    <p>Hello {{ $tickets->first()->user->name }},</p>

    <p>You’ve successfully purchased {{ $tickets->count() }} ticket(s) for the event:</p>

    <ul>
        <li><strong>Event:</strong> {{ $tickets->first()->event->title }}</li>
        <li><strong>Venue:</strong> {{ $tickets->first()->event->venue }}</li>
        <li><strong>Date:</strong> {{ \Carbon\Carbon::parse($tickets->first()->event->dateTime)->format('F d, Y') }}</li>
        <li><strong>Status:</strong> Confirmed</li>
    </ul>

    <p>Your tickets are available in your Evently app under “My Tickets.”</p>

    <p>Thank you and enjoy the event!</p>
    <br>
    <p>Evently Team</p>
</body>
</html>
