<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Patients;
use App\Models\Payments;
use App\Models\Labs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PatientController extends Controller
{
    protected string $tablePatients = 'patients';
    protected string $tableTestTypes = "test_types";
    protected string $tableLabs = "labs";
    protected string $tableLabPricing = "lab_pricing";
    protected string $tablePaymentMethods = "payment_methods";
    protected string $tablePatientStatusList = "patient_status_list";

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string',
            'email' => 'required|email'
        ]);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
        }
        try {
            if (!empty($request->input('transaction_id'))) {
                $patients = new Patients;
                $patients->city = $request->input('city');
                $patients->country = $request->input('country');
                $patients->dob = $request->input('dob');
                $patients->email = $request->input('email');
                $patients->ethnicity = $request->input('ethnicity');
                $patients->firstname = $request->input('firstname');
                $patients->gender = $request->input('gender');
                $patients->have_any_symptom = $request->input('have_any_symptom');
                $patients->have_breath_shortness = $request->input('have_breath_shortness');
                $patients->have_cough = $request->input('have_cough');
                $patients->have_decreased_taste = $request->input('have_decreased_taste');
                $patients->have_fever = $request->input('have_fever');
                $patients->have_muscle_pain = $request->input('have_muscle_pain');
                $patients->have_sore_throat = $request->input('have_sore_throat');
                $patients->have_vaccinated = $request->input('have_vaccinated');
                $patients->identifier = $request->input('identifier');
                $patients->identifier_country = $request->input('identifier_country');
                $patients->identifier_state = $request->input('identifier_state');
                $patients->identifier_doc = $request->input('identifier_doc');
                $patients->identifier_type = $request->input('identifier_type');
                $patients->lab_assigned = $request->input('lab_assigned');
                $patients->lastname = $request->input('lastname');
                $patients->middlename = $request->input('middlename');
                $patients->phone = $request->input('phone');
                $patients->race = $request->input('race');
                $patients->scheduled_date = $request->input('scheduled_date');
                $patients->scheduled_time = $request->input('scheduled_time');
                $patients->state = $request->input('state');
                $patients->street = $request->input('street');
                $patients->pricing_id = $request->input('pricing_id');
                $patients->zip = $request->input('zip');
                $patients->transaction_id = $request->input('transaction_id');
                $patients->confirmation_code = $request->input('confirmation_code');
                $patients->progress_status = empty($request->input('progress_status')) ? 1 : $request->input('progress_status');
                $patients->payment_provider = empty($request->input('payment_provider')) ? 'Stripe' : $request->input('payment_provider');
                $patients->status = empty($request->input('status')) ? 1 : (bool)$request->input('status');
                $patients->save();

                //save payment
                $pricingId = $request->input('pricing_id');
                $pricing = DB::select("SELECT * FROM {$this->tableLabPricing} WHERE id = {$pricingId}");
                $pricing = $pricing[0];
                $payments = new Payments;
                $payments->patient_id = $patients->id;
                $payments->transaction_id = $patients->transaction_id;
                $payments->amount = $pricing->price;
                $payments->payment_status = "completed";
                $payments->currency = $pricing->currency;
                $payments->save();

                //send email
                $labAssigned = Labs::findOrFail($patients->lab_assigned);
                $qrCodeFile = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qrcodes' . DIRECTORY_SEPARATOR . $patients->confirmation_code . '.png';
                $qrCodeUrl = url('/') . '/public/uploads/qrcodes/' . $patients->confirmation_code . '.png';
                QrCode::format('png')->color(21, 106, 165)->size(100)->generate(env("APP_FRONTEND_URL") . '/patient-report/' . $patients->confirmation_code, $qrCodeFile);

                $data = array(
                    'name' => $patients->firstname,
                    'confirmationCode' => $patients->confirmation_code,
                    'scheduleDate' => $patients->scheduled_date,
                    'scheduleTime' => $patients->scheduled_time,
                    'labName' => $labAssigned->name,
                    'qrCode' => '<img src="' . $qrCodeUrl . '">',
                    'labAddress' => $labAssigned->street . ', ' . $labAssigned->city . ', ' . $labAssigned->state,
                    'mapsLink' => "https://maps.google.com/?q=" . $labAssigned->geo_lat . ',' . $labAssigned->geo_long
                );

                Mail::send('schedule-confirmation', $data, function ($message) use ($patients) {
                    $message->to($patients->email, $patients->firstname . ' ' . $patients->lastname)->subject('Schedule Confirmation - The Test Solution');
                    $message->from(env("MAIL_FROM_ADDRESS"), env("MAIL_FROM_NAME"));
                });
                return response()->json(['status' => true, 'data' => $patients, 'message' => 'Registration successful.'], 201);
            } else {
                return response()->json(['status' => false, 'message' => 'Payment Failed.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Registration Failed.' . (env("APP_ENV") !== "production") ? $e->getMessage() : ""], 409);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $keys = $request->keys();
            if (!empty($keys)) {
                $data = [];
                foreach ($keys as $key) {
                    if (in_array($key, ['status'])) {
                        $data[$key] = (bool)$request->get($key);
                    } else {
                        $data[$key] = $request->get($key);
                    }
                }
                DB::table($this->tablePatients)->where('id', $id)->update($data);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Patient updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.'], 409);
        }
    }

    public function get($id)
    {
        return response()->json(['status' => true, 'message' => 'Success', 'data' =>  Patients::findOrFail($id)], 200);
    }

    public function getAll(Request $request)
    {
        $query = "SELECT p.*, (SELECT name FROM {$this->tableLabs} WHERE id IN (p.lab_assigned)) as lab_assigned, (SELECT tt.name from {$this->tableTestTypes} tt inner join {$this->tableLabPricing} lp on lp.test_type = tt.id where lp.id = p.pricing_id) as test_type_name FROM {$this->tablePatients} p WHERE 1=1 ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    if (in_array($column, ["lab_assigned", "progress_status"])) {
                        if(is_array($value)){
                            $value = "'".implode("','",$value)."'";
                        }
                        $query .= "AND p.{$column} IN ({$value}) ";
                    } else {
                        $query .= "AND p.{$column} LIKE '%{$value}%' ";
                    }
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
        $labs = DB::select($query);

        $paginationArr = [
            'totalRecords' => $totalRecords,
            'current' => $page,
            'pageSize' => $limit
        ];
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $labs,
            'pagination' => $paginationArr
        ], 200);
    }
}
