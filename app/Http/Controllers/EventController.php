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
}
