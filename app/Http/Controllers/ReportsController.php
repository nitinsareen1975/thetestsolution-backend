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

class ReportsController extends Controller
{
    protected string $tablePatients = 'patients';
    protected string $tableTestTypes = "test_types";
    protected string $tableLabs = "labs";
    protected string $tableLabPricing = "lab_pricing";
    protected string $tablePaymentMethods = "payment_methods";
    protected string $tablePatientStatusList = "patient_status_list";
    protected string $tableResults = "results";
    protected string $tableTestTypeMethods = "test_type_methods";

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getAll(Request $request)
    {
        $query = "SELECT p.*, (SELECT name FROM {$this->tableLabs} WHERE id IN (p.lab_assigned)) as lab_assigned, r.result, r.result_value, r.created_at as completed_date FROM {$this->tablePatients} p inner join {$this->tableLabPricing} lp on lp.id = p.pricing_id inner join {$this->tableResults} r on r.patient_id = p.id WHERE r.lab_id = p.lab_assigned ";
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
                    if (in_array($column, ["lab_assigned", "progress_status"])) {
                        if (is_array($value)) {
                            $value = "'" . implode("','", $value) . "'";
                        }
                        $query .= "AND p.{$column} IN ({$value}) ";
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

    public function export(Request $request)
    {
        $query = "SELECT p.id, p.firstname, p.lastname, p.email, p.phone, p.gender, p.dob, p.scheduled_date, p.specimen_collection_date, (SELECT l.name FROM {$this->tableLabs} l WHERE l.id IN (p.lab_assigned)) as lab_assigned, r.result, r.result_value, r.created_at as completed_date FROM {$this->tablePatients} p inner join {$this->tableLabPricing} lp on lp.id = p.pricing_id inner join {$this->tableResults} r on r.patient_id = p.id WHERE r.lab_id = p.lab_assigned ";
        
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $token = JWTAuth::getToken();
        $tokenData = JWTAuth::getPayload($token)->toArray();
        $role = Roles::find($tokenData['roles'])->toArray();
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    if (in_array($column, ["lab_assigned", "progress_status"])) {
                        if (is_array($value)) {
                            $value = "'" . implode("','", $value) . "'";
                        }
                        $query .= "AND p.{$column} IN ({$value}) ";
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
                        $query .= "AND p.{$column} LIKE '%{$value}%' ";
                    }
                }
            }
        }
        if (stripos($role['name'], env("ADMINISTRATOR_ROLES")) === -1) {
            $query .= "AND p.lab_assigned IN ({$tokenData['lab_assigned']}) ";
        }
        $query .= "ORDER BY {$sort} {$order} ";
        /* filters, pagination and sorter */
        $data = DB::select($query);
        if (count($data) > 0) {
            $f = fopen('php://memory', 'r+');
            $fileHeaders = ['Lab Assigned', 'Patient Name', 'Email', 'Phone #', 'Gender', 'Date of Birth', 'Scheduled Date', 'Specimen Collection Date', 'Completed Date', 'Result', 'Result Value'];
            fputcsv($f, $fileHeaders);
            foreach ($data as $item) {
                $rowData = [
                    'lab_assigned' => $item->lab_assigned, 
                    'firstname' => $item->firstname.' '.$item->lastname,
                    'email' => $item->email, 
                    'phone' => $item->phone, 
                    'gender' => $item->gender, 
                    'dob' => date("Y-m-d", strtotime($item->dob)), 
                    'scheduled_date' => date("Y-m-d", strtotime($item->scheduled_date)), 
                    'specimen_collection_date' => date("Y-m-d", strtotime($item->specimen_collection_date)), 
                    'completed_date' => date("Y-m-d", strtotime($item->completed_date)), 
                    'result' => $item->result, 
                    'result_value' => $item->result_value
                ];
                fputcsv($f, $rowData);
            }
            rewind($f);
            return response()->json([
                'status' => true,
                'message' => 'Success',
                'data' => stream_get_contents($f)
            ], 200);
        } else {
            return response()->json(['status' => true, 'message' => 'No data found.'], 409);
        }
    }
}
