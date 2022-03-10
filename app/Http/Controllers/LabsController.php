<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Labs;
use App\Helpers\GlobalHelper;
use Illuminate\Http\Request;

class LabsController extends Controller
{
    protected string $tableLabs = 'labs';
    protected string $tableLabPricing = 'lab_pricing';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:labs'
        ]);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
        }
        try {
            $lab = new Labs;
            $lab->name = $request->input('name');
            $lab->email = $request->input('email');
            $lab->phone = $request->input('phone');
            $lab->concerned_person_name = $request->input('concerned_person_name');
            $lab->licence_number = $request->input('licence_number');
            $lab->logo = $request->input('logo');
            $lab->price_per_test = $request->input('price_per_test');
            $lab->tests_available = $request->input('tests_available');
            $lab->test_codes = $request->input('test_codes');
            $lab->date_incorporated = $request->input('date_incorporated');
            $lab->payment_days = $request->input('payment_days');
            $lab->payment_mode = $request->input('payment_mode');
            $lab->has_tax = empty($request->input('has_tax')) ? 0 : (bool)$request->input('has_tax');
            $lab->has_compliance = empty($request->input('has_compliance')) ? 0 : (bool)$request->input('has_compliance');
            $lab->location_code = $request->input('location_code');
            $lab->street = $request->input('street');
            $lab->city = $request->input('city');
            $lab->state = $request->input('state');
            $lab->county = $request->input('county');
            $lab->country = $request->input('country');
            $lab->zip = $request->input('zip');
            $lab->geo_lat = $request->input('geo_lat');
            $lab->geo_long = $request->input('geo_long');
            $lab->ftp_host = $request->input('ftp_host');
            $lab->ftp_port = empty($request->input('ftp_port')) ? 80 : $request->input('ftp_port');
            $lab->ftp_username = $request->input('ftp_username');
            $lab->ftp_password = $request->input('ftp_password');
            $lab->status = empty($request->input('status')) ? 1 : (bool)$request->input('status');
            $lab->save();
            return response()->json(['status' => true, 'data' => $lab, 'message' => 'Lab created successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Lab Creation Failed.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $keys = $request->keys();
            if (!empty($keys)) {
                $data = [];
                foreach ($keys as $key) {
                    if ($key == "lab_pricing") continue;
                    if (in_array($key, ['has_tax', 'has_compliance', 'status'])) {
                        $data[$key] = (bool)$request->get($key);
                    } else {
                        $data[$key] = $request->get($key);
                    }
                }
                DB::table($this->tableLabs)->where('id', $id)->update($data);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Lab updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function get($id)
    {
        return response()->json(['status' => true, 'message' => 'Success', 'data' =>  Labs::findOrFail($id)], 200);
    }

    public function getAll(Request $request)
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

    /* lab-pricing */
    public function getPricing($id)
    {
        $query = "SELECT * FROM {$this->tableLabPricing} WHERE lab_id = {$id}";
        $pricing = DB::select($query);
        if (count($pricing) > 0) {
            return response()->json(['status' => true, 'message' => 'Success', 'data' =>  $pricing], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Pricing not found.'], 200);
        }
    }

    public function getAllPricing(Request $request)
    {
        $query = "SELECT l.* FROM {$this->tableLabPricing} l WHERE 1=1 ";
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
        $pricing = DB::select($query);

        $paginationArr = [
            'totalRecords' => $totalRecords,
            'current' => $page,
            'pageSize' => $limit
        ];
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $pricing,
            'pagination' => $paginationArr
        ], 200);
    }

    public function addUpdatePricing($id, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'pricing' => 'required'
            ]);
            if ($validator->fails()) {
                $messages = $validator->errors();
                return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
            }

            $pricing = $request->input("pricing");
            if (count($pricing) > 0) {
                $existingPricing = DB::select("SELECT * FROM {$this->tableLabPricing} WHERE lab_id = {$id}");

                $postedExistingCount = 0;
                $existingCount = count($existingPricing);
                $postedExistingIds = [];
                foreach($pricing as $price){
                    if(isset($price["id"])){
                        $postedExistingCount++;
                        $postedExistingIds[] = $price["id"];
                        DB::table($this->tableLabPricing)->where('id', $price["id"])->update($price);
                    } else {
                        $data = [
                            'lab_id' => $id,
                            'price' => $price['price'],
                            'test_type' => $price['test_type'],
                            'test_label' => $price['test_label'],
                            'test_duration' => $price['test_duration'],
                            'test_codes' => $price['test_codes']
                        ];
                        $rowId = DB::table($this->tableLabPricing)->insertGetId($data);
                        $postedExistingIds[] = $rowId;
                    }
                }
                if($postedExistingCount < $existingCount){
                    $rowsNotToDelete = implode(",",$postedExistingIds);
                    DB::statement("DELETE FROM {$this->tableLabPricing} WHERE lab_id = {$id} AND id NOT IN ($rowsNotToDelete)");
                }
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Pricing updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Pricing not updated.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function updateLabSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string',
            'licence_number' => 'required|string'
        ]);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
        }
        try {
            $lab_id = $request->input('lab_id');
            if ($lab_id > 0) {
                $keys = $request->keys();
                if (!empty($keys)) {
                    $data = [];
                    foreach ($keys as $key) {
                        if ($key == "lab_pricing" || $key == "lab_id") continue;
                        if (in_array($key, ['has_tax', 'has_compliance', 'status'])) {
                            $data[$key] = (bool)$request->get($key);
                        } else {
                            $data[$key] = $request->get($key);
                        }
                    }
                    DB::table($this->tableLabs)->where('id', $lab_id)->update($data);
                }
                return response()->json(['status' => true, 'data' => [], 'message' => 'Settings updated successfully.'], 201);
            } else {
                return response()->json(['status' => false, 'message' => 'Error: Lab not found.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error: Update failed.', 'exception' => $e->getMessage()], 409);
        }
    }
}
