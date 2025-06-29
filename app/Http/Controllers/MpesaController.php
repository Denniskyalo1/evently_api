<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;

class MpesaController extends Controller
{
    public function stkPush(Request $request)
{
    try {
        $user = Auth::user();
        $phone = $request->input('phone');
        $amount = $request->input('amount');

        // Validate inputs
        $validated = $request->validate([
            'phone' => 'required|regex:/^254[0-9]{9}$/', 
            'amount' => 'required|integer|min:1|max:150000', 
        ]);

        $consumerKey = 'D8TDfSjpVb07iCy2gV7a5JT9tmvA2s7AUBAGtRztjTh4qOwF';
        $consumerSecret = 'qNtOl6jWCHfvAGBtjTREOQYZlIlrjf7zvHwHffG9zG3X0gS8SGGzALA9MR69yhIq';

        //Generate access token
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

        //Build STK Push request
        $timestamp = now()->format('YmdHis');
        $passkey = env('MPESA_PASSKEY');
        $shortcode = env('MPESA_SHORTCODE', '174379');
        $callbackUrl = env('MPESA_CALLBACK_URL');

        $password = base64_encode($shortcode . $passkey . $timestamp);

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

        if (isset($data['ResponseCode']) && $data['ResponseCode'] == '0') {
            $payment = Payment::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'phone' => $phone,
                'status' => 'Pending',
                'transaction_reference' => $data['CheckoutRequestID'],
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

    // Handle callback
    public function mpesaCallback(Request $request)
{
    Log::info("M-PESA CALLBACK:", $request->all());
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
        Log::info("Payment updated to Success", ['CheckoutRequestID' => $checkoutRequestID]);
    } else {
        $status = ($resultCode == 1032) ? 'Canceled' : 'Failed';
        $payment->update([
            'status' => $status,
            'error_message' => $resultDesc,
        ]);
        Log::info("Payment updated to {$status}", ['CheckoutRequestID' => $checkoutRequestID, 'ResultCode' => $resultCode, 'ResultDesc' => $resultDesc]);
    }

    return response()->json(['result' => 'Callback processed successfully'], 200);
}
}
