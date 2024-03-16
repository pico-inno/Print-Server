<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FrontendPrinterController extends Controller
{
    public function index()
    {
        $response = Http::timeout(60)->get('http://127.0.0.1:8000/api/getPrinter');


        $printers = $response->json();
        dd($printers);
        return view('Printer.index', ['printers' => $printers]);

    }
}
