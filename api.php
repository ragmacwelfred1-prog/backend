<?php

use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/students/{id}', [StudentController::class, 'getStudentById']);

Route::get('/students', [StudentController::class, 'getAllStudent']);

Route::post('/students/create', [StudentController::class, 'addStudent']);

Route::post('/students/edit/{id}', [StudentController::class, 'updateStudent']);

Route::post('/students/destroy/{id}', [StudentController::class, 'deleteStudent']);
