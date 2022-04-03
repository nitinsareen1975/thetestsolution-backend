<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Illuminate\Support\Facades\DB;

class PaymentsController extends Controller
{
    protected $tablePricing = "pricing";
    protected $tablePayments = "payments";
    protected $tablePaymentMethods = "payment_methods";
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        //$this->middleware('auth');
    }

    public function createPaymentIntent(Request $request)
    {
        try {
            $pricingId = $request->input('pricing_id');
            $pricing = DB::select("SELECT * FROM {$this->tablePricing} WHERE id = {$pricingId}");
            $pricing = $pricing[0];

            $paymentData = [
                "amount" => $pricing->retail_price * 100,
                "currency" => strtolower($pricing->currency),
                "payment_method_types" => ['card'],
                "description" => "Payment for scheduled screening (".$request->input('customer_email').")"
            ];
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $paymentObj = $stripe->paymentIntents->create($paymentData);
            return response()->json(['status' => true, 'data' => $paymentObj->client_secret], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Request Failed.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function createEventPaymentIntent(Request $request)
    {
        try {
            $amount = $request->input('amount');
            $paymentData = [
                "amount" => $amount * 100,
                "currency" => 'usd',
                "payment_method_types" => ['card'],
                "description" => "Payment for scheduled screening (".$request->input('customer_email').")"
            ];
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $paymentObj = $stripe->paymentIntents->create($paymentData);
            return response()->json(['status' => true, 'data' => $paymentObj->client_secret], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Request Failed.', 'exception' => $e->getMessage()], 409);
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
            return response()->json(['status' => false, 'message' => 'No record found.', 'exception' => $e->getMessage()], 409);
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
            return response()->json(['status' => false, 'message' => 'Request failed.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function getPaymentMethods(Request $request){
        $query = "SELECT * FROM {$this->tablePaymentMethods} WHERE 1=1 ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);

        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    $query .= "AND {$column} LIKE '%{$value}%' ";
                }
            }
        }
        if ($request->has('sorter')) {
            $sorter = json_decode($request->get("sorter"), true);
            if (isset($sorter['column'])) {
                $sort = $sorter['column'];
            }
            if (isset($sorter['order'])) {
                $order = $sorter['order'];
            }
        }

        $query .= "ORDER BY {$sort} {$order} ";
        $totalRecords = count(DB::select($query));
        if ($request->has('pagination')) {
            $pagination = json_decode($request->get("pagination"), true);
            if (isset($pagination['page'])) {
                $page = max(1, $pagination['page']);
            }
            if (isset($pagination['pageSize'])) {
                $limit = max(env("RESULTS_PER_PAGE"), $pagination['pageSize']);
            }
            $offset = ($page - 1) * $limit;
            $query .= "LIMIT {$offset}, {$limit} ";
        }
        /* filters, pagination and sorter */
        $data = DB::select($query);

        $paginationArr = [
            'totalRecords' => $totalRecords,
            'current' => $page,
            'pageSize' => $limit
        ];
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $data,
            'pagination' => $paginationArr
        ], 200);
    }
}
