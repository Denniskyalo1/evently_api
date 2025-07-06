<!DOCTYPE html>
<html>
<head>
    <title>Event Rejected</title>
</head>
<body>
    <h2>Hello {{ $event->user->name }},</h2>
    <p>We regret to inform you that your event titled <strong>"{{ $event->title }}"</strong> has been <strong>rejected</strong>.</p>

    <p>If you believe this was a mistake or would like more information, feel free to contact our support team.</p>

    <p>Thank you for using Evently.</p>
</body>
</html>
