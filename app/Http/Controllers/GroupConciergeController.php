<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Labs;
use App\Helpers\GlobalHelper;
use Illuminate\Http\Request;

class GroupConciergeController extends Controller
{
    protected string $tableGroupEvents = 'group_events';
    protected string $tableGroupPatients = 'group_patients';
    protected string $tableGroupResults = 'group_results';
    protected string $tableGroupPayments = 'group_payments';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function addGroupEvent(Request $request)
    {
        try {
            $data = [];
            $data['name'] = $request->input('name');
            $data['address'] = $request->input('address');
            $data['number_of_persons'] = $request->input('number_of_persons');
            $data['test_type'] = $request->input('test_type');
            $data['rate_per_test'] = $request->input('rate_per_test');
            $data['lab_location'] = $request->input('lab_location');
            $data['event_date'] = $request->input('event_date');
            $data['event_time'] = $request->input('event_time');
            $data['contact_person_name'] = $request->input('contact_person_name');
            $data['contact_person_email'] = $request->input('contact_person_email');
            $data['contact_person_phone'] = $request->input('contact_person_phone');
            $data['payment_method'] = $request->input('payment_method');
            $data['cheque_number'] = $request->input('cheque_number');
            $data['payment_amount'] = $request->input('payment_amount');
            $data['transaction_id'] = $request->input('transaction_id');
            $data['event_agreement'] = $request->input('event_agreement');
            $data['created_at'] = date("Y-m-d H:i:s");
            $data['updated_at'] = date("Y-m-d H:i:s");
            $data['status'] = empty($request->input('status')) ? 1 : (bool)$request->input('status');
            $eventId = DB::table($this->tableGroupEvents)->insertGetId($data);

            //save payment
            if($eventId > 0){
                $paymentData = [];
                $paymentData['group_id'] = $eventId;
                $paymentData['transaction_id'] = $request->input('transaction_id');
                $paymentData['amount'] = $request->input('payment_amount');
                $paymentData['payment_status'] = "completed";
                $paymentData['currency'] = 'USD';
                $paymentData['created_at'] = date('Y-m-d H:i:s');
                $paymentData['updated_at'] = date('Y-m-d H:i:s');
                DB::table($this->tableGroupPayments)->insert($paymentData);
            }
            return response()->json(['status' => true, 'data' => $data, 'message' => 'Event created successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Event Creation Failed.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function updateGroupEvent($id, Request $request)
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
                DB::table($this->tableGroupEvents)->where('id', $id)->update($data);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Event updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.', 'exception' => $e->getMessage()], 409);
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

    public function getGroupEvents(Request $request)
    {
        $query = "SELECT e.* FROM {$this->tableGroupEvents} e WHERE 1=1 ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    $query .= "AND e.{$column} LIKE '%{$value}%' ";
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
            $data['specimen_collection_date'] = $request->input('specimen_collection_date');
            $data['progress_status'] = $request->input('progress_status');
            $data['street2'] = $request->input('street2');
            $data['ssn'] = $request->input('ssn');
            $data['FirstTestForCondition'] = $request->input('FirstTestForCondition');
            $data['EmployedInHealthCare'] = $request->input('EmployedInHealthCare');
            $data['Symptomatic'] = $request->input('Symptomatic');
            $data['DateOfSymptomOnset'] = $request->input('DateOfSymptomOnset');
            $data['AccessionNumber'] = $request->input('AccessionNumber');
            $data['pregnent'] = $request->input('pregnent');
            $data['created_at'] = date("Y-m-d H:i:s");
            $data['updated_at'] = date("Y-m-d H:i:s");
            $data['status'] = empty($request->input('status')) ? 1 : (bool)$request->input('status');
            DB::table($this->tableGroupPatients)->insert($data);
            return response()->json(['status' => true, 'data' => $data, 'message' => 'Patient created successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Patient Creation Failed.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function updateGroupPatient($id, Request $request)
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
                DB::table($this->tableGroupPatients)->where('id', $id)->update($data);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Patient updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function getGroupPatient($id)
    {
        $data = DB::select("SELECT * FROM {$this->tableGroupPatients} WHERE id={$id}");
        if(count($data) > 0){
            return response()->json(['status' => true, 'message' => 'Success', 'data' =>  $data[0]], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Record not found.'], 409);
        }
    }

    public function getGroupPatients(Request $request)
    {
        $query = "SELECT e.*, (SELECT ev.name from {$this->tableGroupEvents} ev WHERE ev.id = e.group_id) as event_name FROM {$this->tableGroupPatients} e WHERE 1=1 ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    if (in_array($column, ["group_id", "lab_assigned", "progress_status"])) {
                        if (is_array($value)) {
                            $value = "'" . implode("','", $value) . "'";
                        }
                        $query .= "AND e.{$column} IN ({$value}) ";
                    } else {
                        $query .= "AND e.{$column} LIKE '%{$value}%' ";
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

    public function groupConciergeResults(Request $request)
    {
        $query = "SELECT r.group_id, r.lab_id, r.created_at as completed_date, r.result, r.result_value, p.id, p.firstname, p.lastname, p.email, p.phone, p.scheduled_date, e.name as event_name, p.confirmation_code FROM {$this->tableGroupResults} r INNER JOIN {$this->tableGroupEvents} e on e.id = r.group_id INNER JOIN {$this->tableGroupPatients} p on p.group_id = e.id WHERE p.progress_status=4 ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    if (in_array($column, ["group_id", "lab_assigned"])) {
                        if (is_array($value)) {
                            $value = "'" . implode("','", $value) . "'";
                        }
                        if($column == "group_id"){
                            $query .= "AND p.{$column} IN ({$value}) ";
                        } elseif($column == "lab_assigned"){
                            $query .= "AND e.lab_location IN ({$value}) ";
                        }
                    } elseif (in_array($column, ["scheduled_date_range", "completed_date_range"])) {
                        if (!is_null($value)) {
                            $startDate = $value["start_date"];
                            $endDate = $value["end_date"];
                            if ($column == 'scheduled_date_range') {
                                $query .= "AND p.scheduled_date BETWEEN '{$startDate}' AND '{$endDate}' ";
                            }
                            if ($column == 'completed_date_range') {
                                $query .= "AND r.created_at BETWEEN '{$startDate}' AND '{$endDate}' ";
                            }
                        }
                    } else {
                        $query .= "AND r.{$column} LIKE '%{$value}%' ";
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

    public function uploadGroupEventAgreement(Request $request)
    {
        try {
            if($request->hasFile('event_agreement')){
                $picName = GlobalHelper::slugify(pathinfo($request->file('event_agreement')->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$request->file('event_agreement')->getClientOriginalExtension();
                $picName = uniqid().'_'.$picName;
                $destinationPath = DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'event-agreements'.DIRECTORY_SEPARATOR;
                $request->file('event_agreement')->move(base_path().$destinationPath, $picName);
                $fileUrl = $destinationPath.$picName;
                return response()->json(['status' => true, 'data' => $fileUrl, 'message' => 'File uploaded successfully.'], 201);
            } else {
                return response()->json(['status' => false, 'message' => 'File not found.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.', 'exception' => $e->getMessage()], 409);
        }
    }
    
    public function removeGroupEventAgreement(Request $request)
    {
        try {
            $id = $request->input("event_id");
            $oldFile = DB::table($this->tableGroupEvents)->where('id','=',$id)->first();
            $oldFile = base_path().$oldFile->event_agreement;                
            if(file_exists($oldFile)){
                unlink($oldFile);
            } 
            $data = [];
            $data['event_agreement'] = "";
            DB::table($this->tableGroupEvents)->where('id', $id)->update($data);
            return response()->json(['status' => true, 'message' => 'File removed.'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Request Failed.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function completeRegistration($id, $group_id, Request $request){
        try {
            $lab_id = 0;
            $confirmation_code = "";
            $lab = DB::select("SELECT e.lab_location, p.confirmation_code from {$this->tableGroupEvents} e INNER JOIN {$this->tableGroupPatients} p on p.group_id = e.id WHERE p.id = '{$id}'");
            if(is_array($lab) && count($lab) > 0){
                $lab_id = $lab[0]->lab_location;
            }
            $args = [
                'group_id' => $group_id,
                'patient_id' => $id,
                'lab_id' => $lab_id,
                'result' => "",
                'result_value' => "",
                'test_type_method_id' => "0",
                'sent_to_govt' => 0,
                'qr_code' => $confirmation_code,
                'created_at' => date("Y-m-d H:i:s")
            ];
            $exists = DB::select("select * from {$this->tableGroupResults} where patient_id = {$id} and lab_id = {$lab_id}");
            if (count($exists) > 0) {
                DB::table($this->tableGroupResults)->where('id', $exists[0]->id)->update($args);
            } else {
                DB::table($this->tableGroupResults)->insert($args);
            }
            DB::table($this->tableGroupPatients)->where('id', $id)->update(["progress_status" => 4]);
            return response()->json(['status' => true, 'data' => [], 'message' => 'Patient updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function saveAndCompleteRegistration($id, $group_id, Request $request){
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
                DB::table($this->tableGroupPatients)->where('id', $id)->update($data);
            }

            $lab_id = 0;
            $confirmation_code = "";
            $lab = DB::select("SELECT e.lab_location, p.confirmation_code from {$this->tableGroupEvents} e INNER JOIN {$this->tableGroupPatients} p on p.group_id = e.id WHERE p.id = '{$id}'");
            if(is_array($lab) && count($lab) > 0){
                $lab_id = $lab[0]->lab_location;
            }
            $args = [
                'group_id' => $group_id,
                'patient_id' => $id,
                'lab_id' => $lab_id,
                'result' => "",
                'result_value' => "",
                'test_type_method_id' => "0",
                'sent_to_govt' => 0,
                'qr_code' => $confirmation_code,
                'created_at' => date("Y-m-d H:i:s")
            ];
            $exists = DB::select("select * from {$this->tableGroupResults} where patient_id = {$id} and lab_id = {$lab_id}");
            if (count($exists) > 0) {
                DB::table($this->tableGroupResults)->where('id', $exists[0]->id)->update($args);
            } else {
                DB::table($this->tableGroupResults)->insert($args);
            }
            DB::table($this->tableGroupPatients)->where('id', $id)->update(["progress_status" => 4]);
            return response()->json(['status' => true, 'data' => [], 'message' => 'Patient updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.', 'exception' => $e->getMessage()], 409);
        }
    }

}
