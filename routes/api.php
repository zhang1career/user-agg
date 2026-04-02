<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\UserAggregationController;
use App\Http\Controllers\UserAuthProxyController;
use Illuminate\Support\Facades\Route;


Route::prefix('')->middleware([])->group(function () {
    Route::post('files', [FileController::class, 'upload']);
    Route::get('files/{id}', [FileController::class, 'show']);
    Route::get('files/{id}/download', [FileController::class, 'download']);
    Route::post('user/login', [UserAuthProxyController::class, 'login']);
    Route::put('user/login', [UserAuthProxyController::class, 'refresh']);
    Route::get('user/me', [UserAggregationController::class, 'me']);
});

