<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\LibrarianController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// User related api
Route::get('/getbooklist', [UsersController::class, 'getallbookslist']);
Route::get('/getduedlist', [UsersController::class, 'getduedlist']);
Route::get('/getloanlist', [UsersController::class, 'getloanlist']);
Route::get('/searchbooks', [UsersController::class, 'searchbooks']);
Route::get('/searchbooks', [UsersController::class, 'searchbooks']);
Route::post('/loanbook', [UsersController::class, 'loanbook']);
Route::post('/paydues', [UsersController::class, 'paydues']);

// Librarian related api
Route::get('/organizeloans', [LibrarianController::class, 'organizeloans']);
Route::post('/updateloan', [LibrarianController::class, 'updateloan']);
Route::post('/updatereturn', [LibrarianController::class, 'updatereturn']);