<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;
use Rawilk\Printing\Facades\Printing;

use function Laravel\Prompts\error;

class PrinterController extends Controller
{

    public function getPrinters(){

        // $printers = Printing::printers();

        // $printerNames =[];
        // foreach ($printers as $printer) {
        //  $printerNames [] = $printer->name();
        // }

        // return $printerNames;

        $os = php_uname('s');



        switch ($os) {
            case 'windows':
            case 'Windows NT':
                return $this->getWindowsPrinters();
            case 'Linux':
                return $this->getLinuxPrinters();
            case 'darwin':
                // get same network conneted printers
            case 'android':
                // get same network conneted printers
            default:
                return response()->json(['response' => '', 'error' => 'Unsupported operating system']);
            }
    }
        private function getWindowsPrinters() {
            exec('powershell -Command "Get-WmiObject -Query \'SELECT * FROM Win32_Printer\' | Select-Object Name"', $output, $returnCode);

            if ($returnCode!== 0) {
                return response()->json(['response' => '', 'error' => 'Error executing PowerShell command']);
            }

            $printerNames = [];
            foreach ($output as $line) {
                if (trim($line)!== '' && strpos($line, '----') === false) {
                    $printerNames[] = trim($line);
                }
            }

            if (empty($printerNames)) {
                return response()->json(['response' => '', 'error' => 'No printer found...']);
            } else {
                return response()->json(['response' => implode('|', $printerNames), 'error' => '']);
            }
        }

        private function getLinuxPrinters() {
            exec('lpstat -p', $output, $returnCode);

            if ($returnCode !== 0) {
                return response()->json(['response' => '', 'error' => 'Error executing lpstat command']);
            }
            $printerNames = [];
            foreach ($output as $line) {
                if (preg_match('/printer\s+(.+)/', $line, $matches)) {
                    $printerNames[] = $matches[1];
                }
            }

            if (empty($printerNames)) {
                return response()->json(['response' => '', 'error' => 'No printers found']);
            }
            return response()->json(['response' => implode('|', $printerNames), 'error' => '']);
        }


    public function setPrinter($name) {
        Session::put('selected_printer', $name);
        return response()->json(['response' => 'Printer set to ' . $name]);
    }

    public function getPrinter()
    {
        $selectedPrinter = Session::get('selected_printer', 'N/A');
        return $selectedPrinter;
    }

    public function printRaw(Request $request)
    {

        if (!$request->session()->isStarted()) {
            $request->session()->start();
        }

        $printerName = $this->getPrinter();

        // dd($printerName);

        if ($printerName === 'N/A') {
            return response()->json(['response' => '', 'error' => 'No printer selected']);
        }

        $request->validate([
            'raw_data' => 'required|string',
        ]);

        $rawData = $request->input('raw_data');

        // check os and run print

        if (true) {
            return response()->json(['response' => 'OK', 'error' => '']);
        } else {
            return response()->json(['response' => '', 'error' => 'Failed to print']);
        }
    }

    public function printFileByUrl(Request $request)
    {

        if (!$request->session()->isStarted()) {
            $request->session()->start();
        }

        $printerName = $this->getPrinter();

        $request->validate([
            'url' => 'required|url',
        ]);

        $url = $request->input('url');

        // check os and run print

        if (true) {
            return response()->json(['response' => 'OK', 'error' => '']);
        } else {
            return response()->json(['response' => '', 'error' => 'Failed to print']);
        }
    }

}


