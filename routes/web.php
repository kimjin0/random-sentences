<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/ver4', function () {
    return view('ver4');
});
Route::get('/listen', function () {
    return view('listen');
});

Route::get('/sendmail', function () {
    $name = "kimjin0";
    Mail::to('devkimjin0@gmail.com')->send(new \App\Mail\MyTestEmail($name));
});
