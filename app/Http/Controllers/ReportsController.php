<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Roles;
use Illuminate\Http\Request;
use App\Helpers;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class ReportsController extends Controller
{
    protected string $tablePatients = 'patients';
    protected string $tableTestTypes = "test_types";
    protected string $tableLabs = "labs";
    protected string $tablePricing = "pricing";
    protected string $tablePaymentMethods = "payment_methods";
    protected string $tablePatientStatusList = "patient_status_list";
    protected string $tableResults = "results";
    protected string $tableTestTypeMethods = "test_type_methods";
    protected string $tableTestTypeNames = "test_type_names";
    protected string $tableResultTypes = "result_types";
    protected string $tableGroupEvents = "group_events";
    protected string $tableGroupPatients = "group_patients";
    protected string $tableGroupResults = "group_results";

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getAll(Request $request)
    {
        $query = "SELECT p.*, (SELECT name FROM {$this->tableLabs} WHERE id IN (p.lab_assigned)) as lab_assigned, r.result, r.result_value, r.created_at as completed_date FROM {$this->tablePatients} p inner join {$this->tablePricing} lp on lp.id = p.pricing_id inner join {$this->tableResults} r on r.patient_id = p.id WHERE r.lab_id = p.lab_assigned ";
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
        $query = "SELECT p.id, l.licence_number, l.concerned_person_name, l.npi, l.facility_id, l.street as lab_address1, l.street2 as lab_address2, l.city as lab_city, l.state as lab_state, l.zip as lab_zip, l.phone as lab_phone, l.npi, l.provider_firstname, l.provider_lastname, l.provider_phone, l.provider_address1, l.provider_address2, l.provider_city, l.provider_state, l.provider_zip, (SELECT l.name FROM {$this->tableLabs} l WHERE l.id IN (p.lab_assigned)) as lab_assigned, p.lab_assigned as lab_id, p.firstname, p.middlename, p.lastname, p.email, p.phone, p.gender, p.dob, p.scheduled_date, p.specimen_collection_date, p.SpecimenSourceCode, r.created_at as completed_date, p.confirmation_code, p.street, p.street2, p.city, p.state, p.county, p.zip, p.ssn, p.pregnent, p.AccessionNumber, p.AbnormalFlag, p.FirstTestForCondition, p.EmployedInHealthCare, p.Symptomatic, p.DateOfSymptomOnset, p.HospitalizedDueToCOVID, (select name from {$this->tableTestTypeNames} where id = tt.test_type) as test_type, (select name from {$this->tableResultTypes} where id = r.result) as result, (select snomed from {$this->tableResultTypes} where id = r.result) as snomed, (select result_value from {$this->tableResults} where id = r.id) as result_value, tt.name as test_name, tt.loinc, tt.loinc_desc, tt.fi_model, tt.fi_test_name, tt.is_rapid_test, (select code from {$this->tableTestTypeMethods} where id = r.test_type_method_id) as specimen_snomed, (select name from {$this->tableTestTypeMethods} where id = r.test_type_method_id) as specimen_collection_site, p.race, p.ethnicity FROM {$this->tablePatients} p 
        inner join {$this->tablePricing} lp on lp.id = p.pricing_id 
        inner join {$this->tableLabs} l on l.id = p.lab_assigned  
        inner join {$this->tableTestTypes} tt on tt.id = lp.test_type 
        inner join {$this->tableResults} r on r.patient_id = p.id 
        WHERE r.lab_id = p.lab_assigned ";

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
            switch($format){
                case 'csv':
                    return $this->exportAsCSV($data);
                    break;
                case 'xls':
                    return $this->exportAsXls($data);
                    break;
                default:
                return response()->json(['status' => false, 'message' => 'Format not supported.'], 409);
                    break;
            }
        } else {
            return response()->json(['status' => false, 'message' => 'No data found.'], 409);
        }
    }

    public function exportGroup($format, Request $request)
    {
        /* $query = "SELECT p.id, l.licence_number, l.concerned_person_name, l.npi, l.facility_id, (SELECT l.name FROM {$this->tableLabs} l WHERE l.id IN (p.lab_assigned)) as lab_assigned, p.lab_assigned as lab_id, p.firstname, p.lastname, p.email, p.phone, p.gender, p.dob, p.scheduled_date, p.specimen_collection_date, r.created_at as completed_date, p.confirmation_code, p.street, p.city, p.state, p.county, p.zip, (select name from {$this->tableTestTypeNames} where id = tt.test_type) as test_type, (select name from {$this->tableResultTypes} where id = r.result) as result, (select snomed from {$this->tableResultTypes} where id = r.result) as snomed, tt.name as test_name, tt.loinc, tt.fi_model, tt.fi_test_name, tt.is_rapid_test, (select code from {$this->tableTestTypeMethods} where id = r.test_type_method_id) as specimen_snomed, (select name from {$this->tableTestTypeMethods} where id = r.test_type_method_id) as specimen_collection_site, p.race, p.ethnicity FROM {$this->tableGroupPatients} p 
        inner join {$this->tableGroupEvents} e on e.id = p.group_id 
        inner join {$this->tableLabs} l on l.id = p.lab_assigned  
        inner join {$this->tableTestTypes} tt on tt.id = e.test_type 
        inner join {$this->tableGroupResults} r on r.patient_id = p.id 
        WHERE r.lab_id = p.lab_assigned "; */


        $query = "SELECT p.id, l.licence_number, l.concerned_person_name, l.npi, l.facility_id, l.street as lab_address1, l.street2 as lab_address2, l.city as lab_city, l.state as lab_state, l.zip as lab_zip, l.phone as lab_phone, l.npi, l.provider_firstname, l.provider_lastname, l.provider_phone, l.provider_address1, l.provider_address2, l.provider_city, l.provider_state, l.provider_zip, (SELECT l.name FROM {$this->tableLabs} l WHERE l.id IN (p.lab_assigned)) as lab_assigned, p.lab_assigned as lab_id, p.firstname, p.middlename, p.lastname, p.email, p.phone, p.gender, p.dob, p.scheduled_date, p.specimen_collection_date, p.SpecimenSourceCode, r.created_at as completed_date, p.confirmation_code, p.street, p.street2, p.city, p.state, p.county, p.zip, p.ssn, p.pregnent, p.AccessionNumber, p.AbnormalFlag, p.FirstTestForCondition, p.EmployedInHealthCare, p.Symptomatic, p.DateOfSymptomOnset, p.HospitalizedDueToCOVID, (select name from {$this->tableTestTypeNames} where id = tt.test_type) as test_type, (select name from {$this->tableResultTypes} where id = r.result) as result, (select snomed from {$this->tableResultTypes} where id = r.result) as snomed, (select result_value from {$this->tableGroupResults} where id = r.id) as result_value, tt.name as test_name, tt.loinc, tt.loinc_desc, tt.fi_model, tt.fi_test_name, tt.is_rapid_test, (select code from {$this->tableTestTypeMethods} where id = r.test_type_method_id) as specimen_snomed, (select name from {$this->tableTestTypeMethods} where id = r.test_type_method_id) as specimen_collection_site, p.race, p.ethnicity FROM {$this->tableGroupPatients} p 
        inner join {$this->tableGroupEvents} e on e.id = p.group_id  
        inner join {$this->tableLabs} l on l.id = p.lab_assigned  
        inner join {$this->tableTestTypes} tt on tt.id = e.test_type 
        inner join {$this->tableGroupResults} r on r.patient_id = p.id 
        WHERE r.lab_id = p.lab_assigned ";

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
            switch($format){
                case 'csv':
                    return $this->exportAsCSV($data);
                    break;
                case 'xls':
                    return $this->exportAsXls($data);
                    break;
                default:
                return response()->json(['status' => false, 'message' => 'Format not supported.'], 409);
                    break;
            }
        } else {
            return response()->json(['status' => true, 'message' => 'No data found.'], 409);
        }
    }

    public function exportAsCSV($data)
    {
        try{
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
                    $item->concerned_person_name,
                    $item->npi,
                    '',
                    '',
                    '',
                    $item->specimen_collection_site,
                    $item->specimen_snomed,
                    date("m/d/Y", strtotime($item->specimen_collection_date)),
                    date("m/d/Y", strtotime($item->specimen_collection_date)),
                    max(0, $item->is_rapid_test),
                    $item->fi_test_name,
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
                    'file_name' => $facilityName . '_' . date("mdY") . '_' . time() . '.csv'
                ]
            ], 200);
        } catch(Exception $e){
            return response()->json(['status' => false, 'message' => 'No data found.', 'exception' => $e->getMessage()], 409);
        }
    }
    
    public function exportAsXls($data)
    {
        require_once base_path(). DIRECTORY_SEPARATOR .'third-party'.DIRECTORY_SEPARATOR.'PHPExcel'.DIRECTORY_SEPARATOR.'PHPExcel.php';
        $_data = [];
        $_data[] = [
            'SendingFacilityName',
            'SendingFacilityCLIA',
            'MedicalRecordNumber',
            'PatientLastname',
            'PatientFirstname',
            'PatientMiddlename',
            'DOB',
            'Gender',
            'PatientRace',
            'PatientAddress1',
            'PatientAddress2',
            'PatientCity',
            'PatientState',
            'PatientZip',
            'PatientPhoneNumber',
            'SSNumber',
            'PatientEthnicity',
            'OrderingFacilityName',
            'OrderingFacilityAddress1',
            'OrderingFacilityAddress2',
            'OrderingFacilityCity',
            'OrderingFacilityState',
            'OrderingFacilityZip',
            'OrderingFacilityPhoneNumber',
            'OrderingProviderNPI',
            'OrderingProviderLastName',
            'OrderingProviderFirstName',
            'OrderingProviderPhoneNumber',
            'OrderingProviderAddress1',
            'OrderingProviderAddress2',
            'OrderingProviderCity',
            'OrderingProviderState',
            'OrderingProviderZip',
            'AccessionNumber',
            'SpecimenCollectedDate',
            'SpecimenSourceCode',
            'SpecimenReceivedDate',
            'LOINC_Code',
            'LOINC_Description',
            'LocalCode',
            'LocalCodeDescription',
            'SNOMEDResultCode',
            'TestResultDescription',
            'ObservationUnits',
            'ReferenceRange',
            'AbnormalFlag',
            'FinalizedDate',
            'KIT^DEVICE^IDTYPE',
            'PerformingLabName',
            'PerformingLabCLIA',
            'LOINC_Code_CT',
            'LOINC_Description_CT',
            'CT_Value',
            'CT_Reference_Range',
            'Variant_LOINC',
            'Variant_Result',
            'Age(30525-0)',
            'PregnancyStatus(82810-3)',
            'FirstTestForCondition(95417-2)',
            'EmployedInHealthCare(95418-0)',
            'Occupation(85658-3)',
            'Symptomatic(95419-8)',
            'Symptom(75325-1)',
            'DateOfSymptomOnset(65222-2)',
            'HospitalizedDueToCOVID(77974-4)',
            'InICU(95420-6)',
            'ResidesinCongregateCare(95421-4)',
            'SpecifyCongregateSetting(75617-1)',
            'StudentTeacherOtherFaculty(63511-0)',
            'NameOfSchool(66280-9)'
        ];
        foreach($data as $row){
            $_data[] = [
                $row->lab_assigned,
                $row->licence_number,
                $row->id,
                $row->lastname,
                $row->firstname,
                $row->middlename,
                $row->dob,
                $row->gender,
                $row->race,
                $row->street,
                $row->street2,
                $row->city,
                $row->state,
                $row->zip,
                $row->phone,
                $row->ssn,
                $row->ethnicity,
                $row->lab_assigned,
                $row->lab_address1,
                $row->lab_address2,
                $row->lab_city,
                $row->lab_state,
                $row->lab_zip,
                $row->lab_phone,
                $row->npi,
                $row->provider_lastname,
                $row->provider_firstname,
                $row->provider_phone,
                $row->provider_address1,
                $row->provider_address2,
                $row->provider_city,
                $row->provider_state,
                $row->provider_zip,
                $row->AccessionNumber,
                $row->specimen_collection_date,
                $row->SpecimenSourceCode,
                $row->specimen_collection_date,
                $row->loinc,
                $row->loinc_desc,
                '',//LocalCode
                '',//LocalCodeDescription
                $row->snomed,
                $row->result_value,
                '',//ObservationUnits
                '',//ReferenceRange
                ($row->AbnormalFlag == 'Yes') ? 'A' : 'N',
                $row->completed_date,
                '',//KIT^DEVICE^IDTYPE
                $row->lab_assigned,
                $row->licence_number,
                $row->loinc,
                $row->loinc_desc,
                '',//CT_Value
                '',//CT_Reference_Range
                '',//Variant_LOINC
                '',//Variant_Result
                (date('Y') - date('Y',strtotime($row->dob))).' years old',
                ($row->gender == 'Male') ? '' : (($row->pregnent == 'Yes') ? 'Y' : 'N'),
                ($row->FirstTestForCondition == 'Yes') ? 'Y' : 'N',
                ($row->EmployedInHealthCare == 'Yes') ? 'Y' : 'N',
                '',//Occupation
                ($row->Symptomatic == 'Yes') ? 'Y' : 'N',
                '',//Symptom
                ($row->DateOfSymptomOnset == 'Yes') ? 'Y' : 'N',
                ($row->HospitalizedDueToCOVID == 'Yes') ? 'Y' : 'N',
                '',//InICU,
                '',//ResidesinCongregateCare,
                '',//SpecifyCongregateSetting
                '',//StudentTeacherOtherFaculty
                '' //NameOfSchool
            ];
        }
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        //$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);

        $objPHPExcel->getActiveSheet()->fromArray($_data);
        $filename = 'Abc';
        
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'-formatted.xlsx"');
        header('Cache-Control: max-age=0');
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        ob_start();
        $objWriter->save('php://output');
        $xlsData = ob_get_contents();
        ob_end_clean();


        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => [
                'file_content' => "data:application/vnd.ms-excel;base64,".base64_encode($xlsData),
                'file_name' => 'PatientsExport_' . date("mdY") . '_' . time() . '.xlsx'
            ]
        ], 200);
        
        try{
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
                    $item->concerned_person_name,
                    $item->npi,
                    '',
                    '',
                    '',
                    $item->specimen_collection_site,
                    $item->specimen_snomed,
                    date("m/d/Y", strtotime($item->specimen_collection_date)),
                    date("m/d/Y", strtotime($item->specimen_collection_date)),
                    max(0, $item->is_rapid_test),
                    $item->fi_test_name,
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
                    'file_name' => $facilityName . '_' . date("mdY") . '_' . time() . '.csv'
                ]
            ], 200);
        } catch(Exception $e){
            return response()->json(['status' => false, 'message' => 'No data found.', 'exception' => $e->getMessage()], 409);
        }
    }
}
