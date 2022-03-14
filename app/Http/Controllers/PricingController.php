<?php 

namespace App\Http\Controllers;
use App\Models\Pricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    protected string $tablePricing = 'pricing';
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
            $el = new Pricing;
            $el->name = $request->input('name');
            $el->description = $request->input('description');
            $el->test_type = $request->input('test_type');
            $el->raw_price = $request->input('raw_price');
            $el->retail_price = $request->input('retail_price');
            $el->test_duration = $request->input('test_duration');
            $el->created_at = date("Y-m-d H:i:s");
            $el->updated_at = date("Y-m-d H:i:s");
            $el->status = empty($request->input('status')) ? 0 : (boolean)$request->input('status');
            $el->save();
            return response()->json(['status' => true, 'data' => $el, 'message' => 'Pricing created successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Request Failed.', 'exception' => $e->getMessage()], 409);
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
                DB::table($this->tablePricing)->where('id', $id)->update($data);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'Pricing updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.', 'exception' => $e->getMessage()], 409);
        }
    }
    
    public function get($id)
    {
        return response()->json(['status' => true, 'message' => 'Success', 'data' =>  Pricing::findOrFail($id)], 200);
    }

    public function getAll(Request $request)
    {
        $query = "SELECT p.*, (SELECT name from {$this->tableTestTypes} where id = p.test_type) as test_type_name FROM {$this->tablePricing} p WHERE 1=1 ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    $query .= "AND p.{$column} LIKE '%{$value}%' ";
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

}