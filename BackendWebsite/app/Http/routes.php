<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/create/comment', function() {
	return view('comment');
});

Route::get('/screenshots/{id}', 'BackendController@viewScreenshot');

Route::get('/add/comment', 'BackendController@addComment');

Route::post('/upload/screenshot', 'BackendController@uploadScreenshot');