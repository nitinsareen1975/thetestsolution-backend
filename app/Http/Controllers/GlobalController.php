<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Helpers\GlobalHelper;
use Illuminate\Support\Facades\Validator;
use App\Models\Payments;
use App\Models\Patients;
use App\Models\Labs;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GlobalController extends Controller
{
    protected $tableTestTypes = "test_types";
    protected $tableLabs = "labs";
    protected $tableLabPricing = "lab_pricing";
    protected $tablePaymentMethods = "payment_methods";
    protected $tablePatientStatusList = "patient_status_list";

    public function __construct()
    {
        //$this->middleware('auth');
    }

    public function getCountries(Request $request)
    {
        $query = DB::table('countries');
        $countries = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $countries
        ], 200);
    }

    public function getAllTestTypes(Request $request)
    {
        $query = DB::table($this->tableTestTypes);
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    $query->where($column, 'like', "%{$value}%");
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
        $query->orderBy($sort, $order);
        $totalRecords = count($query->get());
        if ($request->has('pagination')) {
            $pagination = json_decode($request->get("pagination"), true);
            if (isset($pagination['page'])) {
                $page = max(1, $pagination['page']);
            }
            if (isset($pagination['pageSize'])) {
                $limit = max(env("RESULTS_PER_PAGE"), $pagination['pageSize']);
            }
            $offset = ($page - 1) * $limit;
            $query->skip($offset)->take($limit);
        }
        /* filters, pagination and sorter */
        $testTypes = $query->get();

        $paginationArr = [
            'totalRecords' => $totalRecords,
            'current' => $page,
            'pageSize' => $limit
        ];
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $testTypes,
            'pagination' => $paginationArr
        ], 200);
    }

    public function findLab($id, Request $request)
    {
        return response()->json(['status' => true, 'message' => 'Success', 'data' =>  Labs::findOrFail($id)], 200);
    }

    public function findLabs(Request $request)
    {
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (isset($filters['lat'])) {
                return $this->getNearByLabs($filters, $request);
            } else {
                return $this->getAllLabs($request);
            }
        } else {
            return $this->getAllLabs($request);
        }
    }

    public function getNearByLabs($filters, $request)
    {
        $labs = GlobalHelper::getNearByLabsByRadius($filters["lat"], $filters["lng"], env("NEARBY_LAB_RADIUS", 50));
        if (count($labs) > 0) {
            return response()->json([
                'status' => true,
                'type' => 'nearby',
                'message' => 'Success',
                'data' =>  $labs
            ], 200);
        } else {
            return $this->getAllLabs($request);
        }
    }

    public function getAllLabs(Request $request)
    {
        $query = "SELECT l.* FROM {$this->tableLabs} l WHERE 1=1 ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    if (!in_array($column, ['lat', 'lng'])) {
                        $query .= "AND l.{$column} LIKE '%{$value}%' ";
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

    public function upload($key, Request $request)
    {
        try {
            switch ($key) {
                case "patient-identifier-doc":
                    try {
                        if ($request->hasFile('identifier_doc')) {
                            $picName = GlobalHelper::slugify(pathinfo($request->file('identifier_doc')->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $request->file('identifier_doc')->getClientOriginalExtension();
                            $picName = uniqid() . '_' . $picName;
                            $destinationPath = DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'patients' . DIRECTORY_SEPARATOR;
                            $request->file('identifier_doc')->move(base_path() . $destinationPath, $picName);
                            $fileUrl = $destinationPath . $picName;
                            return response()->json(['status' => true, 'data' => $fileUrl, 'message' => 'Document uploaded successfully.'], 201);
                        } else {
                            return response()->json(['status' => false, 'message' => 'File not found.'], 409);
                        }
                    } catch (\Exception $e) {
                        return response()->json(['status' => false, 'message' => 'Update Failed.'], 409);
                    }
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Upload Failed.'], 409);
        }
    }

    public function registerPatient(Request $request)
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

    public function getLabPricing($id, Request $request)
    {
        $query = "SELECT l.*, t.name as test_name, t.estimated_hours, t.estimated_minutes, t.estimated_seconds FROM {$this->tableLabPricing} l INNER JOIN {$this->tableTestTypes} t ON t.id = l.test_type WHERE l.lab_id={$id} ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    $query .= "AND l.{$column} LIKE '%{$value}%' ";
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
        $results = DB::select($query);

        $paginationArr = [
            'totalRecords' => $totalRecords,
            'current' => $page,
            'pageSize' => $limit
        ];
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $results,
            'pagination' => $paginationArr
        ], 200);
    }

    public function resendConfirmationEmail($code, Request $request)
    {
        $patient = DB::selectOne("SELECT * FROM patients WHERE confirmation_code = '{$code}'");
        $labAssigned = Labs::findOrFail($patient->lab_assigned);

        $qrCodeFile = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qrcodes' . DIRECTORY_SEPARATOR . $patient->confirmation_code . '.png';
        $qrCodeUrl = url('/') . '/public/uploads/qrcodes/' . $patient->confirmation_code . '.png';
        QrCode::format('png')->color(21, 106, 165)->size(100)->generate(env("APP_FRONTEND_URL") . '/patient-report/' . $patient->confirmation_code, $qrCodeFile);

        $data = array(
            'name' => $patient->firstname,
            'confirmationCode' => $patient->confirmation_code,
            'scheduleDate' => $patient->scheduled_date,
            'scheduleTime' => $patient->scheduled_time,
            'labName' => $labAssigned->name,
            'qrCode' => '<img src="' . $qrCodeUrl . '">',
            'labAddress' => $labAssigned->street . ', ' . $labAssigned->city . ', ' . $labAssigned->state,
            'mapsLink' => "https://maps.google.com/?q=" . $labAssigned->geo_lat . ',' . $labAssigned->geo_long
        );
        Mail::send('schedule-confirmation', $data, function ($message) use ($patient) {
            $message->to($patient->email, $patient->firstname . ' ' . $patient->lastname)->subject('Schedule Confirmation - The Test Solution');
            $message->from(env("MAIL_FROM_ADDRESS"), env("MAIL_FROM_NAME"));
        });
    }

    public function getPaymentMethods(Request $request)
    {
        $query = "SELECT * FROM {$this->tablePaymentMethods}";
        $data = DB::select($query);
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $data,
            'pagination' => []
        ], 200);
    }

    public function getPatientStatusList(Request $request)
    {
        $query = "SELECT * FROM {$this->tablePatientStatusList}";
        $data = DB::select($query);
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $data,
            'pagination' => []
        ], 200);
    }
}
