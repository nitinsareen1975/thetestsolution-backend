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
        $rows = DB::select("select * from {$this->tableResults} where sent_to_govt = 0");
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                try{
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
                } catch(Exception $e){
                    
                }
            }
        }
    }
}
