<?php

use App\Http\Controllers\CursoController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/cursos', [CursoController::class, 'store'])->name('cursos.store');
