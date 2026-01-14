<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\EventSubmissionController;
use App\Http\Controllers\EventImageController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Hash;

// Get current authenticated user
Illuminate\Support\Facades\Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/events', [EventController::class, 'index']);
Route::post('/mpesa/callback', [MpesaController::class, 'mpesaCallback']);
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = \App\Models\User::findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        return response()->json(['message' => 'Invalid or expired verification link'], 403);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified']);
    }

    $user->markEmailAsVerified();

    return response()->json(['message' => 'Email verified successfully']);
})->middleware(['signed'])->name('verification.verify');


// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/events', [EventController::class, 'store']);
    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification email resent']);
    })->middleware('throttle:6,1')->name('verification.send');
     Route::post('/mpesa/stkpush', [MpesaController::class, 'stkPush']);
    Route::post('/ticket', [EventController::class, 'store']);
    Route::get('/myTickets', [TicketController::class, 'myTickets']);
    Route::post('/submit-event', [EventSubmissionController::class, 'submit']);
    Route::post('/upload-event-image', [EventImageController::class, 'upload']);

});

//Admin only routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/submitted-events', [EventSubmissionController::class, 'list']);
    Route::post('/approve-event/{id}', [EventSubmissionController::class, 'approve']);
    Route::post('/reject-event/{id}', [EventSubmissionController::class, 'reject']);
});






