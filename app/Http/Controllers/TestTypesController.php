<?php 

namespace App\Http\Controllers;
use App\Models\TestTypes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class TestTypesController extends Controller
{
    protected string $tableTestTypes = 'test_types';
    protected string $tableTestTypeMethods = 'test_type_methods';

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string'
        ]);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return response()->json(['status' => false, 'message' => implode(", ",$messages->all())], 409);
        }
        try {
            $testType = new TestTypes;
            $testType->name = $request->input('name');
            $testType->specimen_site = $request->input('specimen_site');
            $testType->specimen_site_snomed = $request->input('specimen_site_snomed');
            $testType->test_procedure = $request->input('test_procedure');
            $testType->test_procedure_snomed = $request->input('test_procedure_snomed');
            $testType->testing_platform = $request->input('testing_platform');
            $testType->cost = $request->input('cost');
            $testType->price = $request->input('price');
            $testType->loinc = $request->input('loinc');
            $testType->test_type = $request->input('test_type');
            $testType->gender = $request->input('gender');
            $testType->panic_max = $request->input('panic_max');
            $testType->panic_min = $request->input('panic_min');
            $testType->range_max = $request->input('range_max');
            $testType->range_min = $request->input('range_min');
            $testType->range_type = $request->input('range_type');
            $testType->ref_max = $request->input('ref_max');
            $testType->ref_min = $request->input('ref_min');
            $testType->units = $request->input('units');
            $testType->fi_test_name = $request->input('fi_test_name');
            $testType->fi_test_type = $request->input('fi_test_type');
            $testType->fi_model = $request->input('fi_model');
            $testType->observation_methods = !empty($request->input('observation_methods')) ? implode(",", $request->input("observation_methods")) : "";
            $testType->is_rapid_test = empty($request->input('is_rapid_test')) ? 0 : (boolean)$request->input('is_rapid_test');
            $testType->status = empty($request->input('status')) ? 0 : (boolean)$request->input('status');
            $testType->save();
            return response()->json(['status' => true, 'data' => $testType, 'message' => 'Test Type created successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Test Type Creation Failed.', 'exception' => $e->getMessage()], 409);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $keys = $request->keys();
            if(!empty($keys)){
                $data = [];
                foreach($keys as $key){
                    if ($key == "test_type_methods") continue;
                    if(in_array($key, ['status'])){
                        $data[$key] = (boolean)$request->get($key);
                    } else {
                        $data[$key] = $request->get($key);
                    }
                }
                DB::table($this->tableTestTypes)->where('id', $id)->update($data);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Test Type updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.', 'exception' => $e->getMessage()], 409);
        }
    }
    
    public function get($id)
    {
        return response()->json(['status' => true, 'message' => 'Success', 'data' =>  TestTypes::findOrFail($id)], 200);
    }

    public function getAll(Request $request)
    {
        $query = "SELECT t.*, (select name from test_type_names where id = t.test_type) as test_type FROM {$this->tableTestTypes} t WHERE 1=1 ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    $query .= "AND t.{$column} LIKE '%{$value}%' ";
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

    public function getTestMethods($id, Request $request)
    {
        $query = DB::table($this->tableTestTypeMethods)->where('test_type_id', '=', $id);
        $data = $query->get();
        $paginationArr = [];
        return response()->json([
            'status' => true, 
            'message' => 'Success', 
            'data' =>  $data,
            'pagination' => $paginationArr
        ], 200);
    }
    
    public function addUpdateTestMethods($id, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'methods' => 'required'
            ]);
            if ($validator->fails()) {
                $messages = $validator->errors();
                return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
            }

            $methods = $request->input("methods");
            if (count($methods) > 0) {
                DB::table($this->tableTestTypeMethods)->where('test_type_id', $id)->delete();
                $data = [];
                foreach ($methods as $item) {
                    $data[] = [
                        'test_type_id' => $id,
                        'name' => $item['name'],
                        'code' => $item['code']
                    ];
                }
                DB::table($this->tableTestTypeMethods)->insert($data);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Observation definitions updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Oberservation definitions not updated.', 'exception' => $e->getMessage()], 409);
        }
    }
}