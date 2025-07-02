<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Event;
use Illuminate\Support\Str;

class MpesaController extends Controller
{
    public function stkPush(Request $request)
    {
        try {
            $user = Auth::user();
            $phone = $request->input('phone');
            $requestAmount = (int) $request->input('amount');
            $eventId = $request->input('event_id');
            $quantity =(int) $request->input('ticket_quantity');
            $event = Event::findOrFail($eventId);
            $ticketPrice = (int) $event->price;
            $correctAmount = $ticketPrice * $quantity;


           if ($requestAmount !== $correctAmount) {
           return response()->json(['message' => 'Amount mismatch'], 400);
            }

         $amount = $correctAmount;

         Log::info('Incoming STK Push request:', $request->all());

            // Validate inputs
            $validated = $request->validate([
                'phone' => 'required|regex:/^254[0-9]{9}$/',
                'amount' => 'required|integer|min:1|max:150000',
                'event_id' => 'required|exists:events,id',
                'ticket_quantity' => 'required|integer|min:1|max:10'
            ]);
            Log::info('Looking for Event ID:', ['event_id' => $eventId]);
            
            if (!is_numeric($event->price) || (int)$event->price === 0) {
              return response()->json([
                'message' => 'This event is free. No payment required.',
                'status' => 'free'
            ], 200);
}

            
            $totalCost = $event->price * $quantity;

           if ((int)$amount !== $totalCost) {
              return response()->json([
             'message' => 'Amount mismatch with event ticket price',
             'expected_amount' => $totalCost,
             'received_amount' => $amount,
           ], 400);
}

            $consumerKey = env('MPESA_CONSUMER_KEY');
            $consumerSecret = env('MPESA_CONSUMER_SECRET');

            // Generate access token
            $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
            $accessTokenResponse = Http::withOptions([
                'verify' => 'C:/xampp/php/extras/ssl/cacert.pem',
            ])->withHeaders([
                'Authorization' => 'Basic ' . $credentials,
            ])->get('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

            if (!$accessTokenResponse->successful()) {
                Log::error('Failed to get access token', ['response' => $accessTokenResponse->json()]);
                return response()->json([
                    'message' => 'Failed to authenticate with M-Pesa',
                    'error' => $accessTokenResponse->json(),
                ], 500);
            }

            $accessToken = $accessTokenResponse['access_token'];

            // Build STK Push request
            $timestamp = now()->format('YmdHis');
            $passkey = env('MPESA_PASSKEY');
            $shortcode = env('MPESA_SHORTCODE', '174379');
            $callbackUrl = env('MPESA_CALLBACK_URL');

            $password = base64_encode($shortcode . $passkey . $timestamp);

            // Log important request info
            Log::info('Initiating STK Push', [
                'callback_url' => $callbackUrl,
                'timestamp' => $timestamp,
                'shortcode' => $shortcode,
                'phone' => $phone,
                'amount' => $amount,
                'user_id' => $user->id,
            ]);



            // Send STK Push
            $stkResponse = Http::withOptions([
                'verify' => 'C:/xampp/php/extras/ssl/cacert.pem',
            ])->withToken($accessToken)->post('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest', [
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int) $amount,
                'PartyA' => $phone,
                'PartyB' => $shortcode,
                'PhoneNumber' => $phone,
                'CallBackURL' => $callbackUrl,
                'AccountReference' => 'Evently',
                'TransactionDesc' => 'Ticket purchase'
            ]);

            $data = $stkResponse->json();

            Log::info('STK Push API Response:', $data);

            if (isset($data['ResponseCode']) && $data['ResponseCode'] == '0') {
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'event_id' => $eventId,
                    'amount' => $amount,
                    'phone' => $phone,
                    'status' => 'Pending',
                    'transaction_reference' => $data['CheckoutRequestID'],
                    'ticket_quantity' => $quantity,
                    
                ]);

                Log::info('Payment record created', [
                    'CheckoutRequestID' => $data['CheckoutRequestID'],
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'phone' => $phone
                ]);

                return response()->json([
                    'message' => 'STK Push initiated successfully',
                    'details' => $data
                ], 200);
            } else {
                Log::error('STK Push failed', ['response' => $data]);
                return response()->json([
                    'message' => 'Safaricom STK push failed',
                    'error' => $data
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('STK Push error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function mpesaCallback(Request $request)
    {
        Log::info("M-PESA CALLBACK RECEIVED", $request->all());

        $data = $request->all();
        $callback = $data['Body']['stkCallback'] ?? null;

        if (!$callback) {
            Log::error("Invalid callback format: No stkCallback found", ['data' => $data]);
            return response()->json(['result' => 'Invalid callback format'], 400);
        }

        $checkoutRequestID = $callback['CheckoutRequestID'] ?? null;
        $resultCode = $callback['ResultCode'] ?? null;
        $resultDesc = $callback['ResultDesc'] ?? 'No description provided';

        if (!$checkoutRequestID) {
            Log::error("Missing CheckoutRequestID in callback", ['callback' => $callback]);
            return response()->json(['result' => 'Missing CheckoutRequestID'], 400);
        }

        $payment = Payment::where('transaction_reference', $checkoutRequestID)->first();

        if (!$payment) {
            Log::error("No payment found for CheckoutRequestID: {$checkoutRequestID}");
            return response()->json(['result' => 'Payment not found'], 404);
        }

        if ($resultCode == 0) {
            $amount = $callback['CallbackMetadata']['Item'][0]['Value'] ?? 0;
            $mpesaReceipt = $callback['CallbackMetadata']['Item'][1]['Value'] ?? null;

            $payment->update([
                'status' => 'Success',
                'mpesa_receipt_number' => $mpesaReceipt,
                'amount' => $amount,
            ]);

            Log::info("Payment updated to Success", [
                'CheckoutRequestID' => $checkoutRequestID,
                'mpesa_receipt' => $mpesaReceipt,
                'amount' => $amount,
            ]);

            // Create tickets for the user
            if($payment->tickets()->count() === 0){
                 $eventId = $payment->event_id;
                 $quantity = $payment->ticket_quantity;

                for ($i = 0; $i < $quantity; $i++) {
                   Ticket::create([
                     'user_id' => $payment->user_id,
                     'event_id' => $eventId,
                     'payment_id' => $payment->id,
                     'qr_code' => Str::uuid(),
             ]);
        }
         Log::info("Tickets generated automatically after payment", [
                    'payment_id' => $payment->id,
                    'event_id' => $eventId,
                    'quantity' => $quantity,
              ]);
            }

        } else {
            $payment->update([
                'status' => "failed",
                'error_message' => $resultDesc,
            ]);

            Log::info("Payment failed", [
                'CheckoutRequestID' => $checkoutRequestID,
                'ResultCode' => $resultCode,
                'ResultDesc' => $resultDesc,
            ]);
        }

        return response()->json(['result' => 'Callback processed successfully'], 200);
    }
}
