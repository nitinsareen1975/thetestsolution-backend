<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    protected function respondWithToken($token, $user = [])
    {
        return response()->json([
            'status' => true,
            'data' => [
                'token' => [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => Auth::factory()->getTTL() * 60
                ],
                'user' => $user
            ]
        ], 200);
    }
}
