<?php 

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Crypt;
use App\Models\Users;

class UsersController extends Controller
{
    /**
     * Retrieve the user for the given ID.
     *
     * @param  int  $id
     * @return Response
     */
    public function getById($id)
    {
        return Users::findOrFail($id);
    }
}