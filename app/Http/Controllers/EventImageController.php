<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventImageController extends Controller
{
    public function upload(Request $request)
{
    if (!$request->hasFile('image')) {
        Log::error('No image found in request.');
        return response()->json([
            'status' => 'error',
            'message' => 'No image uploaded'
        ], 400);
    }

    $image = $request->file('image');

    $filename = time() . '_' . $image->getClientOriginalName();
    $path = $image->storePubliclyAs('events', $filename, 'public');

    Log::info('Image uploaded:', [
        'original' => $image->getClientOriginalName(),
        'filename' => $filename,
        'stored_at' => $path,
        'exists_after_upload' => file_exists(storage_path("app/public/events/{$filename}"))
    ]);

    return response()->json([
        'status' => 'success',
        'path' => str_replace('public/', '', $path)  
    ]);
}

}
