<?php 

$router->get('/',[ function () {
    return 'Restricted Access!';
}]);
$router->get('/api',[ function () {
    return 'Restricted Access!';
}]);
$router->get('/key', function() {
    return \Illuminate\Support\Str::random(32);
});
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('get-countries', 'GlobalController@getCountries');
});
$router->group(['prefix' => 'global'], function () use ($router) {
    $router->get('get-countries', 'GlobalController@getCountries');
    $router->get('test-types', 'GlobalController@getAllTestTypes');
    $router->get('labs', 'GlobalController@findLabs');
    $router->get('labs/{id}', 'GlobalController@findLab');
    $router->post('upload/{key}', 'GlobalController@upload');
    $router->post('register-patient', 'GlobalController@registerPatient');
    $router->get('lab-pricing/{id}', 'GlobalController@getLabPricing');
    $router->get('resend-confirmation-email/{code}', 'GlobalController@resendConfirmationEmail');
    $router->get('payment-methods', 'GlobalController@getPaymentMethods');
    $router->get('patient-status-list', 'GlobalController@getPatientStatusList');
    $router->post('create-payment-intent', 'PaymentsController@createPaymentIntent');
});