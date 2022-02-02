<?php 

namespace App\Http\Controllers;
use App\Models\Users;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class GlobalController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth');
    }

    public function getCountries(Request $request)
    {
        $query = DB::table('countries'); 
        $countries = $query->get();
        
        return response()->json([
            'status' => true, 
            'message' => 'Success', 
            'data' =>  $countries
        ], 200);
    }
}