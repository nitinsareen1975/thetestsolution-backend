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
    $router->get('tests', 'GlobalController@getAllTestTypes');
    $router->get('labs', 'GlobalController@findLabs');
    $router->get('labs/{id}', 'GlobalController@findLab');
    $router->post('upload/{key}', 'GlobalController@upload');
    $router->post('register-patient', 'GlobalController@registerPatient');
    $router->get('lab-pricing/{id}', 'GlobalController@getLabPricing');
    $router->get('resend-confirmation-email/{code}', 'GlobalController@resendConfirmationEmail');
    $router->get('payment-methods', 'GlobalController@getPaymentMethods');
    $router->get('patient-status-list', 'GlobalController@getPatientStatusList');
    $router->post('create-payment-intent', 'PaymentsController@createPaymentIntent');
    $router->post('create-event-payment-intent', 'PaymentsController@createEventPaymentIntent');
    $router->post('validate-patient-dob', 'GlobalController@validateDOB');
    $router->post('get-patient-report', 'GlobalController@getPatientReport');
    $router->post('get-patient-report-pdf', 'GlobalController@getPatientReportPDF');
    $router->post('get-group-patient-report-pdf', 'GlobalController@getGroupPatientReportPDF');
    $router->get('print-template', 'GlobalController@printEmailTemplate');
    $router->get('currency-codes', 'GlobalController@getCurrencyCodes');
    $router->get('pricing', 'GlobalController@getPricing');
    $router->get('is-walkin-patient/{patientId}', 'GlobalController@isWalkinPatient');
    $router->post('pre-registration-qrcode-pdf', 'GlobalController@preRegistrationQRCodePDF');
    $router->get('group-events/{id}', 'GlobalController@getGroupEvent');
    $router->post('group-patients', 'GlobalController@addGroupPatient');
});
$router->get('global/get-dashboard-stats', [
    'middleware' => 'auth',
    'uses' => 'GlobalController@getDashboardStats'
]);
$router->group(['prefix' => 'crons'], function () use ($router) {
    $router->get('send-results-to-govt', 'CronsController@sendResultsToGovt');
});