<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    // Own account: contact fields are hidden on User by default
    return $request->user()->makeVisible(['phone', 'email']);
})->middleware('auth:sanctum');
