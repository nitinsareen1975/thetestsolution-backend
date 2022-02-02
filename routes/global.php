<?php 

$router->get('/',[ function () {
    return 'Restricted Access!';
}]);
$router->get('/api',[ function () {
    return 'Restricted Access!';
}]);
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('get-countries', 'GlobalController@getCountries');
});