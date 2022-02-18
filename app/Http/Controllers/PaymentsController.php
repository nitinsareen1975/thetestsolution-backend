<?php

namespace App\Http\Controllers;

use Stripe\Stripe;

class PaymentsController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth');
    }

    public function makeStripePayment($request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $request['payment_method_types'] = ['card'];
        $payment = $stripe->paymentIntents->create($request);

        //$charge = new \Stripe\Charge();
        try {
            $response = $stripe->paymentIntents->capture($payment->id, []);
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
}
