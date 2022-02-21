<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Illuminate\Support\Facades\DB;

class PaymentsController extends Controller
{
    protected $tableLabPricing = "lab_pricing";
    protected $tablePayments = "payments";
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        //$this->middleware('auth');
    }

    public function createPaymentIntent(Request $request)
    {
        try {
            $pricingId = $request->input('pricing_id');
            $pricing = DB::select("SELECT * FROM {$this->tableLabPricing} WHERE id = {$pricingId}");
            $pricing = $pricing[0];

            $paymentData = [
                "amount" => $pricing->price * 100,
                "currency" => strtolower($pricing->currency),
                "payment_method_types" => ['card'],
                "description" => "Payment for scheduled screening"
            ];
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $paymentObj = $stripe->paymentIntents->create($paymentData);
            return response()->json(['status' => true, 'data' => $paymentObj->client_secret], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Request Failed.' . (env("APP_ENV") !== "production") ? $e->getMessage() : ""], 409);
        }
    }

    public function makeStripePayment($request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $customer = $stripe->customers->create([
            'name' => $request['customer_name'],
            'email' => $request['customer_email'],
            'phone' => $request['customer_phone'],
            'payment_method' => $request['source'],
            'address' => [
                'line1' => $request['customer_street'],
                'postal_code' => $request['customer_postal_code'],
                'city' => $request['customer_city'],
                'state' => $request['customer_state'],
                'country' => $request['customer_country'],
            ]
        ]);

        $paymentArgs = [];
        $paymentArgs['amount'] = $request['amount'];
        $paymentArgs['currency'] = $request['currency'];
        $paymentArgs['source'] = $request['source'];
        $paymentArgs['description'] = $request['description'];
        $paymentArgs['payment_method_types'] = 'card';
        /* if($customer->id){
            $paymentArgs['customer'] = $customer->id;
        } */
        $charge = new \Stripe\Charge();
        try {
            $chargeObj = $charge->create($paymentArgs);
            $response = $charge->capture($chargeObj->id, []);
            return response()->json(['status' => true, 'data' => $response->toArray()], 200);
        } catch (\Stripe\Exception\CardException $e) {
            return response()->json(['status' => false, 'message' => $e->getError()->message], 409);
        } catch (\Stripe\Exception\RateLimitException $e) {
            return response()->json(['status' => false, 'message' => $e->getError()->message], 409);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            return response()->json(['status' => false, 'message' => $e->getError()->message], 409);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            return response()->json(['status' => false, 'message' => $e->getError()->message], 409);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            return response()->json(['status' => false, 'message' => $e->getError()->message], 409);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json(['status' => false, 'message' => $e->getError()->message], 409);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 409);
        }
    }

    public function getById(Request $request)
    {
        try {
            if (empty($request->input("transaction_id")) && empty($request->input("id"))) {
                return response()->json(['status' => false, 'message' => 'Transaction ID not found'], 409);
            }
            $transactionId = $request->input("transaction_id");
            $paymentId = $request->input("id");
            $query = "SELECT * FROM {$this->tablePayments} WHERE transaction_id='{$transactionId}'";
            if (!empty($paymentId)) {
                $query = "SELECT * FROM {$this->tablePayments} WHERE id='{$paymentId}'";
            }
            $data = DB::select($query);
            return response()->json([
                'status' => true,
                'message' => 'Success',
                'data' =>  $data[0]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'No record found.'], 409);
        }
    }

    public function refundTransaction(Request $request)
    {
        try {
            if (empty($request->input("transaction_id"))) {
                return response()->json(['status' => false, 'message' => 'Transaction ID not found'], 409);
            }
            $transactionId = $request->input("transaction_id");
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $refundArgs = [
                'payment_intent' => $transactionId
            ];
            $refundObj = $stripe->refunds->create($refundArgs);
            if ($refundObj->id) {
                DB::update("UPDATE {$this->tablePayments} SET payment_status='refunded' WHERE transaction_id='{$transactionId}'");
                return response()->json(['status' => true, 'message' => 'Transaction refunded successfully.'], 200);
            } else {
                return response()->json(['status' => false, 'message' => "Refund request failed."], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Request failed.'], 409);
        }
    }
}
