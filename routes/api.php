<?php

use App\Http\Controllers\Api\AssignProjectController;
use App\Http\Controllers\Api\ConfirmWorkEntryController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class);
Route::post('/entries/{workEntry}/confirm', ConfirmWorkEntryController::class);
Route::post('/assign-project', AssignProjectController::class);
