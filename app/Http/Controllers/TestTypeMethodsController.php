<?php 

namespace App\Http\Controllers;
use App\Models\TestTypeMethods;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class TestTypeMethodsController extends Controller
{
    protected string $tableTestTypeMethods = 'test_type_methods';
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
            $row = new TestTypeMethods;
            $row->name = $request->input('name');
            $row->code = $request->input('code');
            $row->status = empty($request->input('status')) ? 0 : (boolean)$request->input('status');
            $row->save();
            return response()->json(['status' => true, 'data' => $row, 'message' => 'Row created successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Row Creation Failed.', 'exception' => $e->getMessage()], 409);
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
                DB::table($this->tableTestTypeMethods)->where('id', $id)->update($data);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.', 'exception' => $e->getMessage()], 409);
        }
    }
    
    public function get($id)
    {
        return response()->json(['status' => true, 'message' => 'Success', 'data' => TestTypeMethods::findOrFail($id)], 200);
    }

    public function getAll(Request $request)
    {
        $query = DB::table($this->tableTestTypeMethods); 
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

    public function getTestTypeMethodsForPatient($patientId, Request $request)
    {
        //$query = DB::table($this->tableTestTypeMethods); 
        $query = "SELECT tt.id, tt.observation_methods from {$this->tableTestTypes} tt inner join pricing lp on lp.test_type = tt.id inner join patients p on p.pricing_id = lp.id where p.id = {$patientId}";
        $testType = DB::select($query);
        if(count($testType) > 0){
            if(isset($testType[0]->observation_methods) && !empty($testType[0]->observation_methods)){
                $observation_methods = $testType[0]->observation_methods;
                $query = "SELECT * from {$this->tableTestTypeMethods} where id in ({$observation_methods}) and status = 1";
                $testMethods = DB::select($query);
                if(count($testMethods) > 0){
                    return response()->json([ 'status' => true, 'message' => 'Success', 'data' =>  $testMethods ], 200);
                } else {
                    return response()->json([ 'status' => false, 'message' => 'No data found' ], 409);
                }
            } else {
                return response()->json([ 'status' => false, 'message' => 'No data found' ], 409);
            }
        } else {
            return response()->json([ 'status' => false, 'message' => 'No data found' ], 409);
        }
    }

}