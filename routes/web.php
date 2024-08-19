<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    app('session')->put('disable_notifications', true);
    app('session')->get('disable_notifications');
    session(['disable_notifications' => true]);
    session('disable_notifications');

    return view('welcome');
});