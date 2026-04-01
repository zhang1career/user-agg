<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\UserAggregationController;
use Illuminate\Support\Facades\Route;


Route::prefix('')->middleware([])->group(function () {
    Route::post('files', [FileController::class, 'upload']);
    Route::get('files/{id}', [FileController::class, 'show']);
    Route::get('files/{id}/download', [FileController::class, 'download']);
    Route::get('user/me', [UserAggregationController::class, 'me']);
});

