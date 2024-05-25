<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\FrontendPrinterController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::post('/', [PrinterController::class, 'printRaw']);
Route::get('/', function () {
    return view('welcome');
});

Route::get('/getPrinters', [FrontendPrinterController::class, 'index']);
