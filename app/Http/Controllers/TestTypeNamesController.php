<?php 

namespace App\Http\Controllers;
use App\Models\TestTypeNames;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class TestTypeNamesController extends Controller
{
    protected string $tableTestTypeNames = 'test_type_names';

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
            $testType = new TestTypeNames;
            $testType->name = $request->input('name');
            $testType->status = empty($request->input('status')) ? 0 : (boolean)$request->input('status');
            $testType->save();
            return response()->json(['status' => true, 'data' => $testType, 'message' => 'Test type name created successfully.'], 201);
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
                DB::table($this->tableTestTypeNames)->where('id', $id)->update($data);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.', 'exception' => $e->getMessage()], 409);
        }
    }
    
    public function get($id)
    {
        return response()->json(['status' => true, 'message' => 'Success', 'data' =>  TestTypeNames::findOrFail($id)], 200);
    }

    public function getAll(Request $request)
    {
        $query = DB::table($this->tableTestTypeNames); 
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