<!DOCTYPE html>
<html>
<head>
    <title>Event Approved</title>
</head>
<body>
    <h2>Hello {{ $event->user->name }},</h2>
    <p>We are pleased to inform you that your event titled <strong>"{{ $event->title }}"</strong> has been <strong>approved</strong> and is now live on our platform.</p>
    
    <p><strong>Event Details:</strong></p>
    <ul>
        <li>Venue: {{ $event->venue }}</li>
        <li>City: {{ $event->city }}</li>
        <li>Date & Time: {{ \Carbon\Carbon::parse($event->dateTime)->toDayDateTimeString() }}</li>
      
    </ul>

    <p>Thank you for using Evently!</p>
</body>
</html>
