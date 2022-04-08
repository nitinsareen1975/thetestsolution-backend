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
use Barryvdh\DomPDF\Facade\Pdf;
use PharIo\Manifest\Url;

class GlobalController extends Controller
{
    protected $tableTestTypes = "test_types";
    protected $tableLabs = "labs";
    protected $tablePaymentMethods = "payment_methods";
    protected $tablePatientStatusList = "patient_status_list";
    protected $tablePatients = "patients";
    protected $tableTestTypeMethods = "test_type_methods";
    protected $tableResults = "results";
    protected $tableCountries = "countries";
    protected $tablePricing = "pricing";
    protected string $tableGroupEvents = 'group_events';
    protected string $tableGroupPatients = 'group_patients';
    protected string $tableGroupResults = 'group_results';
    protected string $tableGroupPayments = 'group_payments';

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
                        return response()->json(['status' => false, 'message' => 'Update Failed.', 'exception' => $e->getMessage()], 409);
                    }
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Upload Failed.', 'exception' => $e->getMessage()], 409);
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
                $patients->street2 = $request->input('street2');
                $patients->ssn = $request->input('ssn');
                $patients->AbnormalFlag = $request->input('AbnormalFlag');
                $patients->FirstTestForCondition = $request->input('FirstTestForCondition');
                $patients->EmployedInHealthCare = $request->input('EmployedInHealthCare');
                $patients->Symptomatic = $request->input('Symptomatic');
                $patients->DateOfSymptomOnset = $request->input('DateOfSymptomOnset');
                $patients->AccessionNumber = $request->input('AccessionNumber');
                $patients->SpecimenSourceCode = $request->input('SpecimenSourceCode');
                $patients->pregnent = $request->input('pregnent');
                $patients->save();

                //save payment
                $pricingId = $request->input('pricing_id');
                $pricing = DB::select("SELECT * FROM {$this->tablePricing} WHERE id = {$pricingId}");
                $pricing = $pricing[0];
                $payments = new Payments;
                $payments->patient_id = $patients->id;
                $payments->transaction_id = $patients->transaction_id;
                $payments->amount = $pricing->retail_price;
                $payments->payment_status = "completed";
                $payments->currency = $pricing->currency;
                $payments->save();

                //send email
                $labAssigned = Labs::findOrFail($patients->lab_assigned);
                $qrCodeFile = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qrcodes' . DIRECTORY_SEPARATOR . $patients->confirmation_code . '.png';
                $qrCodeUrl = url('/') . '/public/uploads/qrcodes/' . $patients->confirmation_code . '.png';
                QrCode::format('png')->color(21, 106, 165)->size(100)->generate(env("APP_FRONTEND_URL") . '/patient-report/' . base64_encode($patients->id) . '/' . $patients->confirmation_code, $qrCodeFile);

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

                try {
                    Mail::send('schedule-confirmation', $data, function ($message) use ($patients) {
                        $message->to($patients->email, $patients->firstname . ' ' . $patients->lastname)->subject('Schedule Confirmation - Telestar Health');
                        $message->from(env("MAIL_FROM_ADDRESS"), env("MAIL_FROM_NAME"));
                    });
                } catch (\Exception $e) {
                    return response()->json(['status' => true, 'message' => 'Registration successful but email was not sent. Please contact support.', 'exception' => $e->getMessage()]);
                }
                return response()->json(['status' => true, 'data' => $patients, 'message' => 'Registration successful.'], 201);
            } else {
                return response()->json(['status' => false, 'message' => 'Payment Failed.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Registration Failed.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function getLabPricing($id, Request $request)
    {
        $query = "SELECT l.*, t.name as test_name, t.estimated_hours, t.estimated_minutes, t.estimated_seconds FROM {$this->tablePricing} l INNER JOIN {$this->tableTestTypes} t ON t.id = l.test_type WHERE l.id={$id} ";
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
        QrCode::format('png')->color(21, 106, 165)->size(100)->generate(env("APP_FRONTEND_URL") . '/patient-report/' . base64_encode($patient->id) . '/' . $patient->confirmation_code, $qrCodeFile);

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
        try {
            Mail::send('schedule-confirmation', $data, function ($message) use ($patient) {
                $message->to($patient->email, $patient->firstname . ' ' . $patient->lastname)->subject('Schedule Confirmation - Telestar Health');
                $message->from(env("MAIL_FROM_ADDRESS"), env("MAIL_FROM_NAME"));
            });
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Email was not sent. Please try again later.', 'exception' => $e->getMessage()]);
        }
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

    public function validateDOB(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'dob' => 'required',
                'patientId' => 'required'
            ]);
            if ($validator->fails()) {
                $messages = $validator->errors();
                return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
            }
            $dob = $request->input("dob");
            $patient_id = base64_decode($request->input("patientId"));
            $data = DB::select("select * from {$this->tablePatients} where dob='{$dob}' and id={$patient_id}");
            if (count($data) > 0) {
                return response()->json(['status' => true, 'confirmationCode' => $data[0]->confirmation_code, 'message' => 'DOB validated.'], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'No matching records were found.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'No records found.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function getPatientReport(Request $request) //not in use
    {
        try {
            $validator = Validator::make($request->all(), [
                'patientId' => 'required'
            ]);
            if ($validator->fails()) {
                $messages = $validator->errors();
                return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
            }
            $patient_id = base64_decode($request->input("patientId"));

            $sql = "SELECT p.firstname, p.lastname, p.dob, p.gender, p.street, p.city, p.state, p.zip, p.phone, p.ethnicity, p.pregnent, p.specimen_collection_date, p.specimen_type, p.confirmation_code, tt.specimen_site, l.phone as lab_phone, l.licence_number, l.name as lab_name, l.date_incorporated, tt.loinc, tt.name as test_type_name, r.result, r.result_value, r.created_at as result_date, l.street as lab_street, l.city as lab_city, l.state as lab_state, l.zip as lab_zip, ttm.name as test_type_method FROM {$this->tablePatients} p 
            inner join {$this->tablePricing} lp on lp.id = p.pricing_id 
            inner join {$this->tableTestTypes} tt on tt.id = lp.test_type 
            inner join {$this->tableTestTypeMethods} ttm on ttm.test_type_id = tt.id 
            inner join {$this->tableLabs} l on l.id = p.lab_assigned  
            inner join {$this->tableResults} r on r.patient_id = p.id and r.lab_id = l.id 
            WHERE p.id = {$patient_id} and ttm.id = r.test_type_method_id";
            $data = DB::select($sql);
            if (count($data) > 0) {
                return response()->json(['status' => true, 'data' => $data[0], 'message' => 'Success.'], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'No matching records were found.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'No records found.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function getGroupPatientReportPDF(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'patientId' => 'required'
            ]);
            if ($validator->fails()) {
                $messages = $validator->errors();
                return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
            }
            $patient_id = base64_decode($request->input("patientId"));
            $patient = DB::select("select * from {$this->tableGroupPatients} where id = '$patient_id'");
            $patient = $patient[0];
            $filename = "patient_" . $patient->confirmation_code . '.pdf';
            $destinationPath = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR;

            if (!file_exists($destinationPath . $filename)) {
                $patientController = new PatientController();
                $patientController->generateGroupPatientReport($patient, $destinationPath . $filename);
            }

            if (file_exists($destinationPath . $filename)) {
                return response()->json(['status' => true, 'data' => ["url" => Url(str_replace(base_path(), "", $destinationPath . $filename))], 'message' => 'Success.'], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'Patient report is not generated yet.'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'No records found.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function getPatientReportPDF(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'patientId' => 'required'
            ]);
            if ($validator->fails()) {
                $messages = $validator->errors();
                return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
            }
            $patient_id = base64_decode($request->input("patientId"));
            $patient = Patients::findOrFail($patient_id);
            $filename = "patient_" . $patient->confirmation_code . '.pdf';
            $destinationPath = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR;

            if (!file_exists($destinationPath . $filename)) {
                $patientController = new PatientController();
                $patientController->generatePatientReport($patient, $destinationPath . $filename);
            }

            if (file_exists($destinationPath . $filename)) {
                return response()->json(['status' => true, 'data' => ["url" => Url(str_replace(base_path(), "", $destinationPath . $filename))], 'message' => 'Success.'], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'Patient report is not generated yet.'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'No records found.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function getDashboardStats(Request $request)
    {
        try {
            $lab_id = !empty($request->input("lab_id")) ? $request->input("lab_id") : 0;
            $sqlTotalResults = "select 
            (SELECT COUNT(p.id) from {$this->tablePatients} p) as total_patients,
            (select count(l.id) from {$this->tableLabs} l) as total_labs,
            (select count(p1.id) from {$this->tablePatients} p1 where p1.progress_status = 1) as total_scheduled_patients, 
            (select count(p2.id) from {$this->tablePatients} p2 where p2.progress_status = 3) as total_pending_results, 
            (select count(p3.id) from {$this->tablePatients} p3 where p3.progress_status = 4) as total_completed_results";

            $sqlTotalTestsByTop5Labs = "select l.name as lab_name, (select count(*) from {$this->tablePatients} p where p.lab_assigned = l.id) as total_tests from {$this->tableLabs} l order by total_tests desc limit 0,5";

            $sqlPatientsByLabsPastWeek = "select p.created_at, p.id from {$this->tablePatients} p where p.created_at > DATE(NOW()) - INTERVAL 14 DAY";

            $sqlLatestAppointments = "select p.id, p.firstname, p.lastname, p.scheduled_date, p.scheduled_time, p.email, p.phone from {$this->tablePatients} p where p.progress_status = 1 and CAST(p.scheduled_date as DATE) >= CURDATE() order by p.scheduled_date asc limit 0, 5";

            $sqlSalesByTestTypes = "SELECT pr.name as test_type, 
            ifnull((SELECT sum(pr1.retail_price) from {$this->tablePricing} pr1 inner join {$this->tablePatients} p1 on p1.pricing_id = pr1.id where pr1.id = pr.id and CAST(p1.created_at as DATE) BETWEEN (curdate() - INTERVAL((WEEKDAY(curdate()))) DAY) AND (curdate() - INTERVAL((WEEKDAY(curdate()))-7) DAY)), 0) as sales_this_week,
            ifnull((SELECT sum(pr1.retail_price) from {$this->tablePricing} pr1 inner join {$this->tablePatients} p1 on p1.pricing_id = pr1.id where pr1.id = pr.id and CAST(p1.created_at as DATE) BETWEEN (last_day(curdate() - interval 1 month) + interval 1 day) AND (last_day(curdate()))), 0) as sales_this_month,
            ifnull((SELECT sum(pr1.retail_price) from {$this->tablePricing} pr1 inner join {$this->tablePatients} p1 on p1.pricing_id = pr1.id where pr1.id = pr.id and CAST(p1.created_at as DATE) BETWEEN DATE_FORMAT(NOW() ,'%Y') AND NOW()), 0) as sales_this_year 
            from {$this->tablePricing} pr where pr.is_walkin_price=0 order by test_type";

            $sqlTotalSalesData = "select 'All Time' as 'duration', count(p.id) as 'total_orders', ifnull(sum(pr.retail_price),0) as 'total_sales' from {$this->tablePatients} p 
            inner join {$this->tablePricing} pr on pr.id = p.pricing_id
            union 
            select 'Last Month' as 'duration', count(p.id) as 'total_orders', ifnull(sum(pr.retail_price),0) as 'total_sales' from {$this->tablePatients} p 
            inner join {$this->tablePricing} pr on pr.id = p.pricing_id
            where p.created_at BETWEEN (last_day(curdate() - interval 2 month) + interval 1 day) AND (last_day(curdate() - interval 1 month))
            union
            select 'Current Month' as 'duration', count(p.id) as 'total_orders', ifnull(sum(pr.retail_price),0) as 'total_sales' from {$this->tablePatients} p 
            inner join {$this->tablePricing} pr on pr.id = p.pricing_id
            where p.created_at BETWEEN (last_day(curdate() - interval 1 month) + interval 1 day) AND (last_day(curdate()))";

            //for lab admins
            if ($lab_id > 0) {
                $sqlTotalResults = "select 
                (SELECT COUNT(p.id) from {$this->tablePatients} p where p.lab_assigned='{$lab_id}') as total_patients,
                (select count(l.id) from {$this->tableLabs} l) as total_labs,
                (select count(p1.id) from {$this->tablePatients} p1 where p1.progress_status = 1 and p1.lab_assigned='{$lab_id}') as total_scheduled_patients, 
                (select count(p2.id) from {$this->tablePatients} p2 where p2.progress_status = 3 and p2.lab_assigned='{$lab_id}') as total_pending_results, 
                (select count(p3.id) from {$this->tablePatients} p3 where p3.progress_status = 4 and p3.lab_assigned='{$lab_id}') as total_completed_results";

                $sqlTotalTestsByTop5Labs = "select l.name as lab_name, (select count(*) from {$this->tablePatients} p where p.lab_assigned = l.id) as total_tests from {$this->tableLabs} l where l.id='{$lab_id}' order by total_tests desc limit 0,5";

                $sqlPatientsByLabsPastWeek = "select p.created_at, p.id from {$this->tablePatients} p where p.created_at > DATE(NOW()) - INTERVAL 14 DAY and p.lab_assigned='{$lab_id}'";

                $sqlLatestAppointments = "select p.id, p.firstname, p.lastname, p.scheduled_date, p.scheduled_time, p.email, p.phone from {$this->tablePatients} p where p.lab_assigned='{$lab_id}' and p.progress_status = 1 and CAST(p.scheduled_date as DATE) >= CURDATE() order by p.scheduled_date asc limit 0, 5";

                $sqlSalesByTestTypes = "SELECT pr.name as test_type, 
                ifnull((SELECT sum(pr1.retail_price) from {$this->tablePricing} pr1 inner join {$this->tablePatients} p1 on p1.pricing_id = pr1.id where pr1.id = pr.id and p1.lab_assigned='{$lab_id}' and CAST(p1.created_at as DATE) BETWEEN (curdate() - INTERVAL((WEEKDAY(curdate()))) DAY) AND (curdate() - INTERVAL((WEEKDAY(curdate()))-7) DAY)), 0) as sales_this_week,
                ifnull((SELECT sum(pr1.retail_price) from {$this->tablePricing} pr1 inner join {$this->tablePatients} p1 on p1.pricing_id = pr1.id where pr1.id = pr.id and p1.lab_assigned='{$lab_id}' and CAST(p1.created_at as DATE) BETWEEN (last_day(curdate() - interval 1 month) + interval 1 day) AND (last_day(curdate()))), 0) as sales_this_month,
                ifnull((SELECT sum(pr1.retail_price) from {$this->tablePricing} pr1 inner join {$this->tablePatients} p1 on p1.pricing_id = pr1.id where pr1.id = pr.id and p1.lab_assigned='{$lab_id}' and CAST(p1.created_at as DATE) BETWEEN DATE_FORMAT(NOW() ,'%Y') AND NOW()), 0) as sales_this_year 
                from {$this->tablePricing} pr where pr.is_walkin_price=0 order by test_type";

                $sqlTotalSalesData = "select 'All Time' as 'duration', count(p.id) as 'total_orders', ifnull(sum(pr.retail_price),0) as 'total_sales' from {$this->tablePatients} p 
                inner join {$this->tablePricing} pr on pr.id = p.pricing_id where p.lab_assigned='{$lab_id}'
                union 
                select 'Last Month' as 'duration', count(p.id) as 'total_orders', ifnull(sum(pr.retail_price),0) as 'total_sales' from {$this->tablePatients} p 
                inner join {$this->tablePricing} pr on pr.id = p.pricing_id
                where p.created_at BETWEEN (last_day(curdate() - interval 2 month) + interval 1 day) AND (last_day(curdate() - interval 1 month)) and p.lab_assigned='{$lab_id}'
                union
                select 'Current Month' as 'duration', count(p.id) as 'total_orders', ifnull(sum(pr.retail_price),0) as 'total_sales' from {$this->tablePatients} p 
                inner join {$this->tablePricing} pr on pr.id = p.pricing_id
                where p.created_at BETWEEN (last_day(curdate() - interval 1 month) + interval 1 day) AND (last_day(curdate())) and p.lab_assigned='{$lab_id}'";
            }

            $sqlTotalResults = DB::select($sqlTotalResults);
            $sqlTotalTestsByTop5Labs = DB::select($sqlTotalTestsByTop5Labs);
            $sqlPatientsByLabsPastWeek = DB::select($sqlPatientsByLabsPastWeek);
            $sqlLatestAppointments = DB::select($sqlLatestAppointments);
            $sqlSalesByTestTypes = DB::select($sqlSalesByTestTypes);
            $sqlTotalSalesData = DB::select($sqlTotalSalesData);

            $data = [
                'total_patients' => isset($sqlTotalResults[0]) ? $sqlTotalResults[0]->total_patients : 'N/A',
                'total_labs' => isset($sqlTotalResults[0]) ? $sqlTotalResults[0]->total_labs : 'N/A',
                'total_scheduled_patients' => isset($sqlTotalResults[0]) ? $sqlTotalResults[0]->total_scheduled_patients : 'N/A',
                'total_pending_results' => isset($sqlTotalResults[0]) ? $sqlTotalResults[0]->total_pending_results : 'N/A',
                'total_completed_results' => isset($sqlTotalResults[0]) ? $sqlTotalResults[0]->total_completed_results : 'N/A',
                'total_tests_by_top5_labs' => isset($sqlTotalTestsByTop5Labs[0]) ? $sqlTotalTestsByTop5Labs : [],
                'total_appointments_past_week' => isset($sqlPatientsByLabsPastWeek[0]) ? $sqlPatientsByLabsPastWeek : [],
                'latest_appointments' => isset($sqlLatestAppointments[0]) ? $sqlLatestAppointments : [],
                'sales_by_test_types' => isset($sqlSalesByTestTypes[0]) ? $sqlSalesByTestTypes : [],
                'total_sales_data' => isset($sqlTotalSalesData[0]) ? $sqlTotalSalesData : []
            ];
            return response()->json(['status' => true, 'data' => $data, 'message' => 'Success.'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'No data found.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function printEmailTemplate()
    {
        $viewData = [
            'name' => '',
            'username' => '',
            'password' => '',
            'appUrl' => '',
            'scheduleTime' => '',
            'labName' => '',
            'labAddress' => '',
            'mapsLink' => '',
        ];
        echo view('registration-confirmation', $viewData)->render();
        die;
    }

    public function getCurrencyCodes()
    {
        $codes = DB::select("SELECT distinct currency_code from {$this->tableCountries}");
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $codes
        ], 200);
    }

    public function getPricing(Request $request)
    {
        $query = "SELECT p.id, p.currency, p.retail_price, p.test_duration, p.name, p.status FROM {$this->tablePricing} p WHERE p.status=1 ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    $query .= "AND p.{$column} LIKE '%{$value}%' ";
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

    public function isWalkinPatient($patientId)
    {
        $sql = "SELECT pr.is_walkin_price from {$this->tablePatients} p INNER JOIN {$this->tablePricing} pr on pr.id = p.pricing_id WHERE p.id = {$patientId}";
        $data = DB::select($sql);
        return response()->json(['status' => true, 'message' => 'Success', 'data' => ['is_walkin_patient' => ($data[0]->is_walkin_price == 1) ? true : false]], 200);
    }

    public function preRegistrationQRCodePDF(Request $request)
    {
        $eventId = $request->input("eventId");
        $event = DB::select("select * from {$this->tableGroupEvents} where id = '{$eventId}'");
        $event = $event[0];
        $filename = "event_" . $event->id . '.pdf';
        $destinationPath = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'event-pre-registration-pdfs' . DIRECTORY_SEPARATOR;
        if (!file_exists($destinationPath . $filename)) {
            $qrCodeFile = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qrcodes' . DIRECTORY_SEPARATOR . 'event_pre_reg_'.$event->id . '.png';
            $qrCodeUrl = url('/') . '/public/uploads/qrcodes/' . 'event_pre_reg_'.$event->id . '.png';
            QrCode::format('png')->size(280)->generate(env("APP_FRONTEND_URL") . '/event-pre-register/' . base64_encode($event->id), $qrCodeFile);
            $viewData = [
                'qr_code' => $qrCodeUrl, 
                'logo_url' => Url("/public/images/logo.jpg"),
                'support_number' => env('CUSTOMER_SERVICE_NUMBER', '786-301-7481')
            ];
            $pdf = PDF::loadView('event-pre-registration-qrcode', $viewData)->setPaper('a4', 'portrait');
            $pdf->save($destinationPath . $filename);
        }
        return response()->json(['status' => true, 'data' => ["url" => Url(str_replace(base_path(), "", $destinationPath . $filename))], 'message' => 'Success'], 200);
    }

    public function addGroupPatient(Request $request)
    {
        try {
            $data = [];
            $data['group_id'] = $request->input('group_id');
            $data['initial'] = $request->input('initial');
            $data['firstname'] = $request->input('firstname');
            $data['middlename'] = $request->input('middlename');
            $data['lastname'] = $request->input('lastname');
            $data['email'] = $request->input('email');
            $data['phone'] = $request->input('phone');
            $data['gender'] = $request->input('gender');
            $data['dob'] = $request->input('dob');
            $data['street'] = $request->input('street');
            $data['city'] = $request->input('city');
            $data['state'] = $request->input('state');
            $data['county'] = $request->input('county');
            $data['country'] = $request->input('country');
            $data['zip'] = $request->input('zip');
            $data['identifier'] = $request->input('identifier');
            $data['identifier_state'] = $request->input('identifier_state');
            $data['identifier_country'] = $request->input('identifier_country');
            $data['identifier_type'] = $request->input('identifier_type');
            $data['identifier_doc'] = $request->input('identifier_doc');
            $data['ethnicity'] = $request->input('ethnicity');
            $data['pregnent'] = $request->input('pregnent');
            $data['race'] = $request->input('race');
            $data['scheduled_date'] = $request->input('scheduled_date');
            $data['scheduled_time'] = $request->input('scheduled_time');
            $data['lab_assigned'] = $request->input('lab_assigned');
            $data['have_fever'] = $request->input('have_fever');
            $data['have_breath_shortness'] = $request->input('have_breath_shortness');
            $data['have_sore_throat'] = $request->input('have_sore_throat');
            $data['have_muscle_pain'] = $request->input('have_muscle_pain');
            $data['have_cough'] = $request->input('have_cough');
            $data['have_decreased_taste'] = $request->input('have_decreased_taste');
            $data['have_any_symptom'] = $request->input('have_any_symptom');
            $data['have_vaccinated'] = $request->input('have_vaccinated');
            $data['pricing_id'] = $request->input('pricing_id');
            $data['is_lab_collected'] = $request->input('is_lab_collected');
            $data['transaction_id'] = $request->input('transaction_id');
            $data['payment_provider'] = $request->input('payment_provider');
            $data['confirmation_code'] = $request->input('confirmation_code');
            $data['specimen_collection_method'] = $request->input('specimen_collection_method');
            $data['specimen_type'] = $request->input('specimen_type');
            $data['specimen_collection_date'] = $request->input('specimen_collection_date');
            $data['progress_status'] = $request->input('progress_status');
            $data['created_at'] = date("Y-m-d H:i:s");
            $data['updated_at'] = date("Y-m-d H:i:s");
            $data['status'] = empty($request->input('status')) ? 1 : (bool)$request->input('status');
            DB::table($this->tableGroupPatients)->insert($data);
            return response()->json(['status' => true, 'data' => $data, 'message' => 'Registration successful.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Registration failed. Please try again.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function getGroupEvent($id)
    {
        $data = DB::select("SELECT * FROM {$this->tableGroupEvents} WHERE id={$id}");
        if(count($data) > 0){
            return response()->json(['status' => true, 'message' => 'Success', 'data' =>  $data[0]], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Record not found.'], 409);
        }
    }

}
