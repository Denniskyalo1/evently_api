<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubmittedEvent;
use \Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Event;
use Illuminate\Support\Facades\Mail;
use App\Mail\EventApproved;
use App\Mail\EventRejected;

class EventSubmissionController extends Controller
{
    public function submit(Request $request)
{
      Log::info('Incoming event submission', [
        'user_id' => Auth::id(),
        'request_data' => $request->all(),
    ]);

    $validated = $request->validate([
        'title' => 'required|string',
        'description' => 'required|string',
        'venue' => 'required|string',
        'city' => 'required|string',
        'dateTime' => 'required|date',
        'price' => 'required|numeric',
        'category_id' => 'required|exists:categories,id',
        'imageUrl' => 'nullable|string',
    ]);

    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $submission = SubmittedEvent::create([
        'user_id' => $user->id,
        ...$validated,
    ]);

    return response()->json([
        'message' => 'Event submitted for approval.',
        'submission' => $submission
    ]);
}

public function approve($id)
{
    $submission = SubmittedEvent::findOrFail($id);
    $submission->status = 'approved';
    $submission->save();

    // Copy to events table
    Event::create([
        'user_id' => $submission->user_id,
        'title' => $submission->title,
        'description' => $submission->description,
        'venue' => $submission->venue,
        'city' => $submission->city,
        'category_id' => $submission->category_id,
        'price' => $submission->price,
        'dateTime' => $submission->dateTime,
        'imageUrl' => $submission->imageUrl,
    ]);

    // Send email
    Mail::to($submission->user->email)->send(new EventApproved($submission));

    return response()->json(['message' => 'Event approved and published.']);
}

public function reject($id)
{
    $submission = SubmittedEvent::findOrFail($id);
    $submission->status = 'rejected';
    $submission->save();

    // Send rejection email
    Mail::to($submission->user->email)->send(new EventRejected($submission));

    return response()->json(['message' => 'Event rejected.']);
}

public function list()
{
    $submissions = SubmittedEvent::with('user') 
        ->where('status', 'pending')
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($submissions);
}
}
