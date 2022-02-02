<?php 
use Illuminate\Support\Facades\Hash;
$router->get('api/generate-password/{password}', function ($password) {
    return Hash::make($password);
});
$router->group(['prefix' => 'auth'], function () use ($router) {
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    $router->post('forgot-password', 'AuthController@forgotPassword');
    $router->post('reset-password', 'AuthController@resetPassword');
    $router->post('logout', 'AuthController@logout');
    $router->post('refresh-token', 'AuthController@refreshToken');
});
