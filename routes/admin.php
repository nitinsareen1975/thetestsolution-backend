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
    
    /* test-type-methods */
    $router->get('test-type-methods', 'TestTypeMethodsController@getAll');
    $router->get('test-type-methods/{id}', 'TestTypeMethodsController@get');
    $router->post('test-type-methods', 'TestTypeMethodsController@add');
    $router->put('test-type-methods/{id}', 'TestTypeMethodsController@update');
    $router->get('test-type-methods-for-patient/{patientId}', 'TestTypeMethodsController@getTestTypeMethodsForPatient');
    /* test-type-methods */

    /* test-result-types */
    $router->get('test-result-types', 'TestResultTypesController@getAll');
    $router->get('test-result-types/{id}', 'TestResultTypesController@get');
    $router->post('test-result-types', 'TestResultTypesController@add');
    $router->put('test-result-types/{id}', 'TestResultTypesController@update');
    /* test-result-types */
    
    /* test-type-names */
    $router->get('test-type-names', 'TestTypeNamesController@getAll');
    $router->get('test-type-names/{id}', 'TestTypeNamesController@get');
    $router->post('test-type-names', 'TestTypeNamesController@add');
    $router->put('test-type-names/{id}', 'TestTypeNamesController@update');
    /* test-type-names */
    
    /* files */
    $router->post('upload/{key}/{id}', 'FilesController@upload');
    $router->delete('upload/{key}/{id}', 'FilesController@remove');
    /* files */

    /* patients */
    $router->get('patients/completed', 'PatientController@getCompletedPatients');
    $router->get('patients', 'PatientController@getAll');
    $router->get('patients/{id}', 'PatientController@get');
    $router->post('patients', 'PatientController@add');
    $router->put('patients/{id}', 'PatientController@update');
    $router->get('patients/pricing/{patientId}/{pricingId}', 'PatientController@getPatientPricing');
    $router->post('patients/save-results/{patientId}', 'PatientController@saveResults');
    $router->post('patients/save-results-test/{patientId}', 'PatientController@generateSampleTestReport');
    /* patients */

    /* payments */
    $router->get('payments', 'PaymentsController@getById');
    $router->post('payments/refund', 'PaymentsController@refundTransaction');
    $router->get('payment-methods', 'PaymentsController@getPaymentMethods');
    /* payments */

    /* my-account */
    $router->put('update-profile', 'UsersController@updateProfile');
    $router->put('update-password', 'UsersController@updatePassword');
    $router->put('update-lab', 'LabsController@updateLabSettings');
    /* my-account */

    /* reports */
    $router->get('reports', 'ReportsController@getAll');
    $router->get('reports/download/{patientId}', 'ReportsController@download');
    $router->get('reports/export/{format}', 'ReportsController@export');
    /* reports */

    
    /* pricing */
    $router->get('pricing', 'PricingController@getAll');
    $router->get('pricing/{id}', 'PricingController@get');
    $router->post('pricing', 'PricingController@add');
    $router->put('pricing/{id}', 'PricingController@update');
    /* pricing */
});