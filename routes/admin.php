<?php
$router->group(['prefix' => 'api'], function () use ($router) {
    /* users */
    $router->get('users', 'UsersController@getAll');
    $router->get('users/{id}', 'UsersController@get');
    $router->post('users', 'UsersController@add');
    $router->put('users/{id}', 'UsersController@update');
    /* users */

    /* roles */
    $router->get('roles', 'RolesController@getAll');
    $router->get('roles/{id}', 'RolesController@get');
    $router->post('roles', 'RolesController@add');
    $router->put('roles/{id}', 'RolesController@update');
    /* roles */
    
    /* labs */
    $router->get('labs', 'LabsController@getAll');
    $router->get('labs/{id}', 'LabsController@get');
    $router->post('labs', 'LabsController@add');
    $router->put('labs/{id}', 'LabsController@update');
    /* labs */
    
    /* labs */
    $router->get('lab-pricing', 'LabsController@getAllPricing');
    $router->get('lab-pricing/{id}', 'LabsController@getPricing');
    $router->post('lab-pricing/{id}', 'LabsController@addUpdatePricing');
    $router->put('lab-pricing/{id}', 'LabsController@addUpdatePricing');
    /* labs */

    /* test-types */
    $router->get('test-types', 'TestTypesController@getAll');
    $router->get('test-types/{id}', 'TestTypesController@get');
    $router->post('test-types', 'TestTypesController@add');
    $router->put('test-types/{id}', 'TestTypesController@update');
    /* test-types */
    
    /* tests */
    $router->get('tests', 'TestsController@getAll');
    $router->get('tests/{id}', 'TestsController@get');
    $router->post('tests', 'TestsController@add');
    $router->put('tests/{id}', 'TestsController@update');
    /* tests */
    
    /* files */
    $router->post('upload/{key}/{id}', 'FilesController@upload');
    $router->delete('upload/{key}/{id}', 'FilesController@remove');
    /* files */

    /* patients */
    $router->get('patients', 'PatientController@getAll');
    $router->get('patients/{id}', 'PatientController@get');
    $router->post('patients', 'PatientController@add');
    $router->put('patients/{id}', 'PatientController@update');
    /* patients */
});