<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Patients;
use App\Models\Payments;
use App\Models\Labs;
use App\Models\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Tymon\JWTAuth\Facades\JWTAuth;
use Barryvdh\DomPDF\Facade\Pdf;

class PatientController extends Controller
{
    protected string $tablePatients = 'patients';
    protected string $tableTestTypes = "test_types";
    protected string $tableLabs = "labs";
    protected string $tablePricing = "pricing";
    protected string $tablePaymentMethods = "payment_methods";
    protected string $tablePatientStatusList = "patient_status_list";
    protected string $tableResults = "results";
    protected string $tableTestTypeMethods = "test_type_methods";

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
                    return response()->json(['status' => false, 'message' => 'Email was not sent. Please try again later.', 'exception' => $e->getMessage()]);
                }
                return response()->json(['status' => true, 'data' => $patients, 'message' => 'Registration successful.'], 201);
            } else {
                return response()->json(['status' => false, 'message' => 'Payment Failed.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Registration Failed.', 'exception' => $e->getMessage()], 409);
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
        $query = "SELECT p.*, p.lab_assigned as lab_assigned_id, (SELECT name FROM {$this->tableLabs} WHERE id IN (p.lab_assigned)) as lab_assigned, (SELECT tt.name from {$this->tableTestTypes} tt inner join {$this->tablePricing} lp on lp.test_type = tt.id where lp.id = p.pricing_id) as test_type_name FROM {$this->tablePatients} p WHERE 1=1 ";
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
                        if (is_array($value)) {
                            $value = "'" . implode("','", $value) . "'";
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

    public function getPatientPricing($patientId, $pricingId, Request $request)
    {
        /* $query = "SELECT p.firstname, p.lastname, tt.name as test_type, tt.id as test_type_id FROM {$this->tablePricing} lp 
        inner join {$this->tableLabs} l on l.id = p.lab_assigned  
        inner join {$this->tablePatients} p on p.lab_assigned = l.id 
        inner join {$this->tableTestTypes} tt on tt.id = lp.test_type 
        where p.id = {$patientId} and lp.id = {$pricingId}"; */
        $query = "SELECT p.firstname, p.lastname, tt.name as test_type, (select ttm.name from {$this->tableTestTypeMethods} ttm where ttm.id=p.specimen_collection_method) as specimen_collection_method, (select ttm.id from {$this->tableTestTypeMethods} ttm where ttm.id=p.specimen_collection_method) as specimen_collection_method_id, tt.id as test_type_id FROM {$this->tablePatients} p 
        inner join {$this->tablePricing} lp on lp.id = p.pricing_id 
        inner join {$this->tableTestTypes} tt on tt.id = lp.test_type 
        where p.id = {$patientId} and lp.id = {$pricingId}";
        $data = DB::select($query);
        if (count($data) == 0 || !isset($data[0]->test_type_id)) {
            return response()->json(['status' => false, 'message' => 'Failed', 'data' =>  $data], 409);
        }
        $paginationArr = [];
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $data[0],
            'pagination' => $paginationArr
        ], 200);
    }

    public function saveResults($patientId, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sample_collection_method' => 'required',
                'result' => 'required'
            ]);
            if ($validator->fails()) {
                $messages = $validator->errors();
                return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
            }
            $sample_collection_method = $request->input("sample_collection_method");
            $result = $request->input("result");
            $result_text = $request->input("result_text");
            $progress_status = $request->input("progress_status");
            $lab_id = $request->input("lab_id");
            $send_to_govt = $request->input("send_to_govt");
            $confirmation_code = $request->input("confirmation_code");
            $data = [
                'patient_id' => $patientId,
                'lab_id' => $lab_id,
                'result' => $result,
                'result_value' => $result_text,
                'test_type_method_id' => $sample_collection_method,
                'sent_to_govt' => $send_to_govt,
                'qr_code' => $confirmation_code,
                'created_at' => date("Y-m-d H:i:s")
            ];
            $exists = DB::select("select * from {$this->tableResults} where patient_id = {$patientId} and lab_id = {$lab_id}");
            if (count($exists) > 0) {
                DB::table($this->tableResults)->where('id', $exists[0]->id)->update($data);
            } else {
                DB::table($this->tableResults)->insert($data);
            }
            DB::table($this->tablePatients)->where('id', $patientId)->update(['progress_status' => $progress_status]);
            $patient = Patients::findOrFail($patientId);

            $filename = "patient_" . $patient->confirmation_code . '.pdf';
            $destinationPath = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR;
            $this->generatePatientReport($patient, $destinationPath . $filename);
            if ($send_to_govt == 1) {
                $this->sendResultsToGovt($patientId, $lab_id);
            }
            $data = array(
                'name' => $patient->firstname,
                'resultsLink' => env("APP_FRONTEND_URL") . '/patient-report/' . base64_encode($patient->id)
            );
            try {
                Mail::send('test-results-confirmation', $data, function ($message) use ($patient) {
                    $message->to($patient->email, $patient->firstname . ' ' . $patient->lastname)->subject('Test results available - Telestar Health');
                    $message->from(env("MAIL_FROM_ADDRESS"), env("MAIL_FROM_NAME"));
                });
            } catch (\Exception $e) {
                return response()->json(['status' => false, 'message' => 'Email was not sent. Please try again later.', 'exception' => $e->getMessage()]);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Patient result saved successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Result not updated.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function sendResultsToGovt($patientId, $labId)
    {
        $patient = Patients::findOrFail($patientId);
        if ($patient->id) {
            $filename = "patient_" . $patient->confirmation_code . '.pdf';
            $destinationPath = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR;
            if (!file_exists($destinationPath . $filename)) {
                $this->generatePatientReport($patient, $destinationPath . $filename);
            }

            $labdata = Labs::findOrFail($labId);
            if (!empty($labdata->ftp_host) && !empty($labdata->ftp_host) && !empty($labdata->ftp_host) && !empty($labdata->ftp_password) && !empty($labdata->ftp_folder_path)) {
                $strServer = $labdata->ftp_host;
                $strServerPort = $labdata->ftp_port;
                $strServerUsername = $labdata->ftp_username;
                $strServerPassword = $labdata->ftp_password;

                $resConnection = ssh2_connect($strServer, $strServerPort);
                if (ssh2_auth_password($resConnection, $strServerUsername, $strServerPassword)) {
                    $resSFTP = ssh2_sftp($resConnection);
                    $resFile = fopen("ssh2.sftp://{$resSFTP}/" . $labdata->ftp_folder_path . '/' . $filename, 'w');
                    $srcFile = fopen($destinationPath . $filename, 'r');
                    $writtenBytes = stream_copy_to_stream($srcFile, $resFile);
                    fclose($resFile);
                    fclose($srcFile);
                }
            }
        }
    }

    public function sendResultsToGovtTest($patientId, $labId)
    {
        $patient = Patients::findOrFail($patientId);
        if (!$patient->id) {
            return response()->json(['status' => false, 'message' => 'Patient does not exist.'], 409);
        }

        $filename = "patient_" . $patient->confirmation_code . '.pdf';
        $destinationPath = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR;
        //if (!file_exists($destinationPath . $filename)) {
        $pdf = new Pdf();
        $data = $this->getPatientReport($patient->id);
        $viewData = [];
        foreach ($data as $k => $v) {
            if ($k == "logo" && empty($k)) {
                $v = url("/public/images/logo.jpg");
            }
            if ($k == "logo") {
                if ((stripos($v, '.jpg') === -1) && (stripos($v, '.jpeg') === -1)) {
                    $v = url("/public/images/logo.jpg");
                } else {
                    $v = url("/") . str_replace("\\", "/", $v);
                }
            }
            $viewData["report_{$k}"] = $v;
        }
        $qrCodeFile = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qrcodes' . DIRECTORY_SEPARATOR . $patient->confirmation_code . '.png';
        $qrCodeUrl = url('/') . '/public/uploads/qrcodes/' . $patient->confirmation_code . '.png';
        QrCode::format('png')->size(180)->generate(env("APP_FRONTEND_URL") . '/patient-report/' . base64_encode($patient->id) . '/' . $patient->confirmation_code, $qrCodeFile);
        $viewData["report_qrcode"] = $qrCodeUrl;
        $pdf = PDF::loadView('patient-report', $viewData)->setPaper('a4', 'portrait');
        $pdf->save($destinationPath . $filename);
        //}

        return response()->json(['status' => true, 'message' => 'Result uploaded.'], 200);

        /* $labdata = Labs::findOrFail($labId);
        if (!empty($labdata->ftp_host) && !empty($labdata->ftp_host) && !empty($labdata->ftp_host) && !empty($labdata->ftp_password) && !empty($labdata->ftp_folder_path)) {
            $strServer = $labdata->ftp_host;
            $strServerPort = $labdata->ftp_port;
            $strServerUsername = $labdata->ftp_username;
            $strServerPassword = $labdata->ftp_password;

            $resConnection = ssh2_connect($strServer, $strServerPort);
            if (ssh2_auth_password($resConnection, $strServerUsername, $strServerPassword)) {
                $resSFTP = ssh2_sftp($resConnection);
                $resFile = fopen("ssh2.sftp://{$resSFTP}/" . $labdata->ftp_folder_path . '/' . $filename, 'w');
                $srcFile = fopen($destinationPath . $filename, 'r');
                $writtenBytes = stream_copy_to_stream($srcFile, $resFile);
                fclose($resFile);
                fclose($srcFile);
                return response()->json(['status' => true, 'message' => 'Result uploaded successfully.'], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'Unable to connect to server.'], 409);
            }
        } */
    }

    public function getPatientReport($patient_id)
    {
        $sql = "SELECT p.firstname, p.lastname, p.dob, p.gender, p.street, p.city, p.state, p.zip, p.phone, p.ethnicity, p.pregnent, p.specimen_collection_date, p.specimen_type, p.confirmation_code, tt.specimen_site, l.phone as lab_phone, l.licence_number, l.name as lab_name, l.logo, l.date_incorporated, l.facility_id, tt.loinc, tt.name as test_type_name, r.result, r.result_value, r.created_at as result_date, l.street as lab_street, l.city as lab_city, l.state as lab_state, l.zip as lab_zip, (select name from {$this->tableTestTypeMethods} where id = r.test_type_method_id) as test_type_method FROM {$this->tablePatients} p 
            inner join {$this->tablePricing} lp on lp.id = p.pricing_id 
            inner join {$this->tableTestTypes} tt on tt.id = lp.test_type 
            inner join {$this->tableLabs} l on l.id = p.lab_assigned  
            inner join {$this->tableResults} r on r.patient_id = p.id and r.lab_id = l.id 
            WHERE p.id = {$patient_id}";
        $data = DB::select($sql);
        return $data[0];
    }

    public function getCompletedPatients(Request $request)
    {
        $query = "SELECT p.*, (SELECT name FROM {$this->tableLabs} WHERE id IN (p.lab_assigned)) as lab_assigned, r.result, r.result_value FROM {$this->tablePatients} p inner join {$this->tablePricing} lp on lp.id = p.pricing_id inner join {$this->tableResults} r on r.patient_id = p.id WHERE r.lab_id = p.lab_assigned ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);

        $token = JWTAuth::getToken();
        $tokenData = JWTAuth::getPayload($token)->toArray();
        $role = Roles::find($tokenData['roles'])->toArray();
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    if ($column == "lab_assigned" && stripos($role['name'], env("ADMINISTRATOR_ROLES")) > -1) {
                        continue;
                    }
                    if (in_array($column, ["lab_assigned", "progress_status"])) {
                        if (is_array($value)) {
                            $value = "'" . implode("','", $value) . "'";
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

        if (stripos($role['name'], env("ADMINISTRATOR_ROLES")) === -1) {
            $query .= "AND p.lab_assigned IN ({$tokenData['lab_assigned']}) ";
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

    public function generatePatientReport($patient, $filePath)
    {
        $pdf = new Pdf();
        $data = $this->getPatientReport($patient->id);
        $viewData = [];
        foreach ($data as $k => $v) {
            if ($k == "logo" && empty($k)) {
                $v = url("/public/images/logo.jpg");
            }
            if ($k == "logo") {
                if ((stripos($v, '.jpg') === -1) && (stripos($v, '.jpeg') === -1)) {
                    $v = url("/public/images/logo.jpg");
                } else {
                    $v = url("/") . str_replace("\\", "/", $v);
                }
            }
            $viewData["report_{$k}"] = $v;
        }
        $qrCodeFile = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qrcodes' . DIRECTORY_SEPARATOR . $patient->confirmation_code . '.png';
        $qrCodeUrl = url('/') . '/public/uploads/qrcodes/' . $patient->confirmation_code . '.png';
        QrCode::format('png')->size(180)->generate(env("APP_FRONTEND_URL") . '/patient-report/' . base64_encode($patient->id) . '/' . $patient->confirmation_code, $qrCodeFile);
        $viewData["report_qrcode"] = $qrCodeUrl;
        $pdf = PDF::loadView('patient-report', $viewData)->setPaper('a4', 'portrait');
        $pdf->save($filePath);
    }
}
