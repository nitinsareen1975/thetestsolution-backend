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
use Exception;

class CronsController extends Controller
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
        //$this->middleware('auth');
    }

    public function sendResultsToGovt()
    {
        exit();
        $query = "SELECT p.id, (SELECT l.name FROM {$this->tableLabs} l WHERE l.id IN (p.lab_assigned)) as lab_assigned, p.lab_assigned as lab_id, p.firstname, p.lastname, p.email, p.phone, p.gender, p.dob, p.scheduled_date, p.specimen_collection_date, r.result, r.created_at as completed_date, p.confirmation_code, p.street, p.city, p.state, p.county, p.zip, tt.test_type, tt.specimen_site_snomed as snomed, tt.name as test_name, tt.loinc, tt.fi_model, ttm.code as specimen_snomed, tt.specimen_site as specimen_collection_site, p.race, p.ethnicity FROM {$this->tablePatients} p 
        inner join {$this->tableLabPricing} lp on lp.id = p.pricing_id 
        inner join {$this->tableTestTypes} tt on tt.id = lp.test_type 
        inner join {$this->tableTestTypeMethods} ttm on ttm.test_type_id = tt.id 
        inner join {$this->tableResults} r on r.patient_id = p.id 
        WHERE r.lab_id = p.lab_assigned and r.test_type_method_id = ttm.id and r.sent_to_govt = 0 ";
        $rows = DB::select($query);
        if (count($rows) > 0) {
            try {
                $facilityName = "";
                $labId = "";
                $f = fopen('php://memory', 'r+');
                $fileHeaders = ['RecordID|FacilityID|CLIAID|AccessionNumber|ClientID|LastName|FirstName|MiddleName|DOB|SSN|StreetAddress|City|State|Zip|County|Gender|PhoneNumber|Ethnicity|RaceWhite|RaceBlack|RaceAmericanIndianAlaskanNative|RaceAsian|RaceNativeHawaiianOrOtherPacificIslander|RaceOther|RaceUnknown|RaceNoResponse|ProviderName|NPI|Pregnant|SchoolAssociation|SchoolName|SpecimenCollectionSite|SpecimenSNOMED|SpecimenCollectedDate|SpecimenReportedDate|RapidTest|Type|ModelOrComponent|LOINC|TestName|SNOMED|Result'];
                fputcsv($f, $fileHeaders);
                foreach ($rows as $item) {
                    $facilityName = str_replace(" ", "", $item->lab_assigned);
                    $labId = $item->lab_id;
                    $rowData = [
                        $item->id,
                        $item->lab_id,
                        $item->confirmation_code,
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

                $csvData = stream_get_contents($f);
                $filename = $facilityName . '_' . date("mdY") . '_' . time() . '.csv';

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
                        DB::table($this->tableResults)->where('id', $row->id)->update(["sent_to_govt" => 1]);
                    }
                } */
            } catch (Exception $e) {
            }
        }
    }
    /* public function sendResultsToGovtOld()
    {
        $rows = DB::select("select * from {$this->tableResults} where sent_to_govt = 0");
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                try {
                    $patient = Patients::findOrFail($row->patient_id);
                    if ($patient->id) {
                        $filename = "patient_" . $patient->confirmation_code . '.pdf';
                        $destinationPath = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR;
                        $patientObj = new PatientController();
                        if (!file_exists($destinationPath . $filename)) {
                            $patientObj->generatePatientReport($patient, $destinationPath . $filename);
                        }

                        $labdata = Labs::findOrFail($patient->lab_assigned);
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
                                DB::table($this->tableResults)->where('id', $row->id)->update(["sent_to_govt" => 1]);
                            }
                        }
                    }
                } catch (Exception $e) {
                }
            }
        }
    } */
}
