<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class EventController extends Controller
{
    
public function store(StoreEventRequest $request)
{
    /** @var User $user */
    $user = Auth::user();

    if (!$user->hasRole(['admin', 'eventmanager'])) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    $event = Event::create(array_merge(
        $request->validated(),
        ['user_id' => $user->id]
    ));

    return response()->json([
        'message' => 'Event created successfully',
        'event' => $event
    ]);
}

public function index()
{
    $events = Event::with('category', 'user')->get();
    $formattedEvents = $events->map(function ($event) {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'city' => $event->city,
            'venue' => $event->venue,
            'dateTime' => $event->dateTime,
            'imageUrl' => $event->imageUrl,
            'price' => $event->price,
            'category' => $event->category ? $event->category->name : null,
        ];
    });

    return response()->json($formattedEvents);
}
}
