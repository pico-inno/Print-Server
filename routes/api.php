<?php

use App\Http\Controllers\PrinterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/printers', [PrinterController::class, 'getPrinters']);
Route::post('/set-printer/{printer}', [PrinterController::class, 'setPrinter']);
Route::get('/printer', [PrinterController::class, 'getPrinter']);
Route::get('/print-raw', [PrinterController::class, 'printRaw']);
Route::post('/print-url', [PrinterController::class, 'printFileByUrl']);


