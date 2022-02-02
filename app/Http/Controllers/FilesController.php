<?php 
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Helpers\GlobalHelper;
use Illuminate\Http\Request;

class FilesController extends Controller
{
    protected string $tableLabs = 'labs';

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function get($key, $id){
        switch($key){
            case "lab-logo":
                $logo = DB::table($this->tableLabs)->where('id', '=', $id)->first(['logo']);
                return response()->json(['status' => true, 'data' => $logo->logo, 'message' => 'Success'], 201);
                break;
            default:
                break;
        }
    }

    public function upload($key, $id, Request $request)
    {
        try {
            switch($key){
                case "lab-logo":
                    return $this->uploadLabLogo($id, $request);
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Upload Failed.'.$e->getMessage()], 409);
        }
    }

    public function remove($key, $id)
    {
        try {
            switch($key){
                case "lab-logo":
                    return $this->removeLabLogo($id);
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Upload Failed.'.$e->getMessage()], 409);
        }
    }

    public function uploadLabLogo($id, $request)
    {
        try {
            if($request->hasFile('logo')){
                $picName = GlobalHelper::slugify(pathinfo($request->file('logo')->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$request->file('logo')->getClientOriginalExtension();
                $picName = uniqid().'_'.$picName;
                $destinationPath = DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'labs'.DIRECTORY_SEPARATOR;
                $request->file('logo')->move(base_path().$destinationPath, $picName);
                $logoUrl = $destinationPath.$picName;
                return response()->json(['status' => true, 'data' => $logoUrl, 'message' => 'Logo uploaded successfully.'], 201);
            } else {
                return response()->json(['status' => false, 'message' => 'File not found.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update Failed.'], 409);
        }
    }
    
    public function removeLabLogo($id)
    {
        try {
            $oldLogo = DB::table($this->tableLabs)->where('id','=',$id)->first();
            $oldLogo = base_path().$oldLogo->logo;                
            if(file_exists($oldLogo)){
                unset($oldLogo);
                return response()->json(['status' => true, 'message' => 'File removed.'], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'File not found.'], 409);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Request Failed.'], 409);
        }
    }
    
    
}