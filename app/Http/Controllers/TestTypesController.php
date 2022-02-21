<?php 

namespace App\Http\Controllers;
use App\Models\TestTypes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class TestTypesController extends Controller
{
    protected string $tableTestTypes = 'test_types';

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
            $testType->fi_test_name = $request->input('fi_test_name');
            $testType->fi_test_type = $request->input('fi_test_type');
            $testType->fi_model = $request->input('fi_model');
            $testType->estimated_hours = empty($request->input('estimated_hours')) ? '00' : sprintf('%02d', $request->input('estimated_hours'));
            $testType->estimated_minutes = empty($request->input('estimated_minutes')) ? '00' : sprintf('%02d', $request->input('estimated_minutes'));
            $testType->estimated_seconds = empty($request->input('estimated_seconds')) ? '00' : sprintf('%02d', $request->input('estimated_seconds'));
            $testType->status = empty($request->input('status')) ? 0 : (boolean)$request->input('status');
            $testType->save();
            return response()->json(['status' => true, 'data' => $testType, 'message' => 'Test Type created successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Test Type Creation Failed.'], 409);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $keys = $request->keys();
            if(!empty($keys)){
                $data = [];
                foreach($keys as $key){
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
            return response()->json(['status' => false, 'message' => 'Update Failed.'], 409);
        }
    }
    
    public function get($id)
    {
        return response()->json(['status' => true, 'message' => 'Success', 'data' =>  TestTypes::findOrFail($id)], 200);
    }

    public function getAll(Request $request)
    {
        $query = DB::table($this->tableTestTypes); 
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters') ) {
            $filters = json_decode($request->get("filters"), true);
            if(count($filters) > 0){
                foreach($filters as $column => $value){
                    $query->where($column, 'like', "%{$value}%");
                }
            }
        }
        if ($request->has('sorter')) {
            $sorter = json_decode($request->get("sorter"), true);
            if(isset($sorter['column'])){
                $sort = $sorter['column'];
            }
            if(isset($sorter['order'])){
                $order = $sorter['order'];
            }
        }
        $query->orderBy($sort, $order);
        $totalRecords = count($query->get());
        if ($request->has('pagination') ) {
            $pagination = json_decode($request->get("pagination"), true);
            if(isset($pagination['page'])){
                $page = max(1, $pagination['page']);
            }
            if(isset($pagination['pageSize'])){
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
}