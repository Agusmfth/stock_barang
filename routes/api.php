<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', fn ($request) => $request->user());
