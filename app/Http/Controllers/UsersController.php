<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Roles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    protected string $tableUsers = 'users';
    protected string $tableRoles = 'roles';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string',
            'email' => 'required|email|unique:users',
            'phone' => 'required'
        ]);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
        }
        try {
            $user = new Users;
            $plainPassword = env("DEFAULT_USER_PASSWORD");
            $user->password = app('hash')->make($plainPassword);
            $user->firstname = $request->input('firstname');
            $user->lastname = $request->input('lastname');
            $user->email = $request->input('email');
            $user->phone = $request->input('phone');
            $user->street = $request->input('street');
            $user->city = $request->input('city');
            $user->state = $request->input('state');
            $user->country = $request->input('country');
            $user->zip = $request->input('zip');
            $user->roles = $request->input('roles');
            $user->lab_assigned = $request->input('lab_assigned');
            $user->can_read_reports = $request->input('can_read_reports');
            $user->status = $request->input('status');
            $user->save();
            return response()->json(['status' => true, 'data' => $user, 'message' => 'User created successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'User Creation Failed.'], 409);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $keys = $request->keys();
            if (!empty($keys)) {
                $data = [];
                foreach ($keys as $key) {
                    $data[$key] = $request->get($key);
                }
                DB::table($this->tableUsers)->where('id', $id)->update($data);
            }
            return response()->json(['status' => true, 'data' => [], 'message' => 'User updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.'], 409);
        }
    }

    public function get($id)
    {
        return response()->json(['status' => true, 'message' => 'Success', 'data' =>  Users::findOrFail($id)], 200);
    }

    public function getAll(Request $request)
    {
        $query = "SELECT u.*, (SELECT name FROM {$this->tableRoles} WHERE id IN (u.roles)) as roles FROM {$this->tableUsers} u WHERE 1=1 ";
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    $query .= "AND u.{$column} LIKE '%{$value}%' ";
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
        $token = JWTAuth::getToken();
        $tokenData = JWTAuth::getPayload($token)->toArray();
        $role = Roles::find($tokenData['roles'])->toArray();
        if (stripos($role['name'], env("ADMINISTRATOR_ROLES")) === -1) {
            $query .= "AND u.lab_assigned IN ({$tokenData['lab_assigned']}) ";
        }
        if (isset($tokenData['uid']) && $tokenData['uid'] > 0) {
            $query .= "AND u.id NOT IN ({$tokenData['uid']}) ";
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
        $users = DB::select($query);

        $paginationArr = [
            'totalRecords' => $totalRecords,
            'current' => $page,
            'pageSize' => $limit
        ];
        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $users,
            'pagination' => $paginationArr
        ], 200);
    }

    public function getAllRoles(Request $request)
    {
        $query = DB::table($this->tableRoles);
        /* filters, pagination and sorter */
        $page = 1;
        $sort = env("RESULTS_SORT", "id");
        $order = env("RESULTS_ORDER", "desc");
        $limit = env("RESULTS_PER_PAGE", 10);
        if ($request->has('filters')) {
            $filters = json_decode($request->get("filters"), true);
            if (count($filters) > 0) {
                foreach ($filters as $column => $value) {
                    $query->where($column, 'like', "%{$value}%");
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
        $query->orderBy($sort, $order);
        if ($request->has('pagination')) {
            $pagination = json_decode($request->get("pagination"), true);
            if (isset($pagination['page'])) {
                $page = max(1, $pagination['page']);
            }
            if (isset($pagination['pageSize'])) {
                $limit = max(env("RESULTS_PER_PAGE"), $pagination['pageSize']);
            }
            $offset = ($page - 1) * $limit;
            $query->skip($offset)->take($limit);
        }
        /* filters, pagination and sorter */
        $roles = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' =>  $roles
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string'
        ]);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
        }
        try {
            $token = JWTAuth::getToken();
            $tokenData = JWTAuth::getPayload($token)->toArray();
            if (isset($tokenData['uid']) && $tokenData['uid'] > 0) {
                $args = [];
                $args['firstname'] = $request->input('firstname');
                $args['lastname'] = $request->input('lastname');
                $args['phone'] = $request->input('phone');
                $args['street'] = $request->input('street');
                $args['city'] = $request->input('city');
                $args['state'] = $request->input('state');
                $args['country'] = $request->input('country');
                $args['zip'] = $request->input('zip');
                $args['roles'] = $request->input('roles');
                DB::table($this->tableUsers)->where('id', $tokenData['uid'])->update($args);
                return response()->json(['status' => true, 'data' => [], 'message' => 'Profile updated successfully.'], 201);
            } else {
                return response()->json(['status' => false, 'message' => 'Error: User not found.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error: Update failed.'], 409);
        }
    }
    
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldPassword' => 'required|string',
            'password' => 'required|string'
        ]);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return response()->json(['status' => false, 'message' => implode(", ", $messages->all())], 409);
        }
        try {
            $token = JWTAuth::getToken();
            $tokenData = JWTAuth::getPayload($token)->toArray();
            if (isset($tokenData['uid']) && $tokenData['uid'] > 0) {
                $user = Users::findOrFail($tokenData['uid']);
                if(isset($user->id)){
                    $oldPassword = $request->input('oldPassword');
                    $password = $request->input('password');
                    $isOldPasswordValid = Hash::check($oldPassword, $user->password);
                    if($isOldPasswordValid){
                        $args = [
                            'password' => app('hash')->make($password)
                        ];
                        DB::table($this->tableUsers)->where('id', $tokenData['uid'])->update($args);
                        return response()->json(['status' => true, 'data' => [], 'message' => 'Password updated successfully.'], 201);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Old password not matched.'], 409);
                    }
                } else {
                    return response()->json(['status' => false, 'message' => 'User not found.'], 409);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'User not found.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error: Update failed.'], 409);
        }
    }
}
