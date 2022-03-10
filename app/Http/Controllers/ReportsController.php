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

    public function export($format, Request $request)
    {
        //$query = "SELECT p.id, p.lab_assigned as lab_id, p.firstname, p.lastname, p.email, p.phone, p.gender, p.dob, p.scheduled_date, p.specimen_collection_date, (SELECT l.name FROM {$this->tableLabs} l WHERE l.id IN (p.lab_assigned)) as lab_assigned, r.result, r.result_value, r.created_at as completed_date, p.confirmation_code, p.street, p.city, p.state FROM {$this->tablePatients} p inner join {$this->tableLabPricing} lp on lp.id = p.pricing_id inner join {$this->tableResults} r on r.patient_id = p.id WHERE r.lab_id = p.lab_assigned ";

        $query = "SELECT p.id, l.licence_number, l.facility_id, (SELECT l.name FROM {$this->tableLabs} l WHERE l.id IN (p.lab_assigned)) as lab_assigned, p.lab_assigned as lab_id, p.firstname, p.lastname, p.email, p.phone, p.gender, p.dob, p.scheduled_date, p.specimen_collection_date, r.result, r.created_at as completed_date, p.confirmation_code, p.street, p.city, p.state, p.county, p.zip, tt.test_type, tt.specimen_site_snomed as snomed, tt.name as test_name, tt.loinc, tt.fi_model, ttm.code as specimen_snomed, tt.specimen_site as specimen_collection_site, p.race, p.ethnicity FROM {$this->tablePatients} p 
        inner join {$this->tableLabPricing} lp on lp.id = p.pricing_id 
        inner join {$this->tableLabs} l on l.id = p.lab_assigned  
        inner join {$this->tableTestTypes} tt on tt.id = lp.test_type 
        inner join {$this->tableTestTypeMethods} ttm on ttm.test_type_id = tt.id 
        inner join {$this->tableResults} r on r.patient_id = p.id 
        WHERE r.lab_id = p.lab_assigned and r.test_type_method_id = ttm.id ";
        
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
            $facilityName = "";
            $f = fopen('php://memory', 'r+');
            $fileHeaders = ['RecordID|FacilityID|CLIAID|AccessionNumber|ClientID|LastName|FirstName|MiddleName|DOB|SSN|StreetAddress|City|State|Zip|County|Gender|PhoneNumber|Ethnicity|RaceWhite|RaceBlack|RaceAmericanIndianAlaskanNative|RaceAsian|RaceNativeHawaiianOrOtherPacificIslander|RaceOther|RaceUnknown|RaceNoResponse|ProviderName|NPI|Pregnant|SchoolAssociation|SchoolName|SpecimenCollectionSite|SpecimenSNOMED|SpecimenCollectedDate|SpecimenReportedDate|RapidTest|Type|ModelOrComponent|LOINC|TestName|SNOMED|Result'];
            fputcsv($f, $fileHeaders);
            foreach ($data as $item) {
                $facilityName = str_replace(" ", "", $item->lab_assigned);
                $rowData = [
                    $item->id,
                    $item->facility_id,
                    $item->licence_number,
                    '',
                    $item->id,
                    $item->lastname,
                    $item->firstname,
                    '',
                    date("m/d/Y", strtotime($item->dob)),
                    '',
                    $item->street,
                    $item->city,
                    $item->state,
                    $item->zip,
                    $item->county,
                    $item->gender,
                    $item->phone,
                    $item->ethnicity,
                    ($item->race == "White") ? 1 : 0,
                    ($item->race == "Black") ? 1 : 0,
                    ($item->race == "American Indian or Alaska Native") ? 1 : 0,
                    ($item->race == "Asian") ? 1 : 0,
                    ($item->race == "Native Hawaiian or Other Pacific Islander") ? 1 : 0,
                    ($item->race == "Other") ? 1 : 0,
                    ($item->race == "Unknown") ? 1 : 0,
                    0,
                    '',
                    '',
                    '',
                    '',
                    '',
                    $item->specimen_collection_site,
                    $item->specimen_snomed,
                    $item->specimen_collection_date,
                    '',
                    '',
                    $item->test_type,
                    $item->fi_model,
                    $item->loinc,
                    $item->test_name,
                    $item->snomed,
                    $item->result
                ];
                $fields = [implode("|", $rowData)];
                fputcsv($f, $fields);
            }
            rewind($f);
            return response()->json([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'file_content' => stream_get_contents($f),
                    'file_name' => $facilityName.'_'.date("mdY").'_'.time().'.csv'
                ]
            ], 200);
        } else {
            return response()->json(['status' => true, 'message' => 'No data found.'], 409);
        }
    }
}
