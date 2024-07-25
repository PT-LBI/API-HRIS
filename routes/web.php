<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
}); 

Route::get('/posts', function () {
    dd('hello world wkwk');
});
Route::get('/user', [UserController::class, 'index']);
