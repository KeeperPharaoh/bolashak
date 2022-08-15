<?php

use App\Http\Controllers\NewTestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\AdminTestController;

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

Route::get('index', [MainController::class, 'index']);
Route::post('feedback', [MainController::class, 'feedback']);

Route::get('test/categories', [TestController::class, 'categories']);
Route::get('test/description', [TestController::class, 'description']);
Route::get('test/instruction', [TestController::class, 'instruction']);

Route::get('test', [TestController::class, 'tests']);
Route::post('test', [TestController::class, 'results']);

Route::get('admin/test/{id}', [AdminTestController::class, 'index']);
Route::post('admin/test', [AdminTestController::class, 'create']);
Route::post('admin/test/{id}', [AdminTestController::class, 'update']);

Route::get('test/results', [TestController::class, 'userResults']);

Route::delete('admin/test', [AdminTestController::class, 'delete']);

Route::get('start-creating', [NewTestController::class, 'startCreating']);
Route::post('main/{id}', [NewTestController::class, 'main']);
Route::post('question/{id}', [NewTestController::class, 'questionCreate']);
Route::post('question/{id}/update', [NewTestController::class, 'questionUpdate']);
Route::delete('question/{id}', [NewTestController::class, 'questionDelete']);

Route::post('answer/{id}', [NewTestController::class, 'answerCreate']);
Route::post('answer/{id}/update', [NewTestController::class, 'answerUpdate']);
Route::delete('answer/{id}', [NewTestController::class, 'answerDelete']);


Route::post('user', [TestController::class, 'user']);
Route::middleware('auth:sanctum')
     ->get('/user', function (Request $request) {
         return $request->user();
     })
;
