<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PrinterController extends Controller
{
    // check os
    private function getOperatingSystem()
    {
        $os = strtolower(php_uname('s'));

        switch ($os) {
            case 'windows':
            case 'windows nt':
                return 'windows';
            case 'linux':
                return 'linux';
            default:
                return 'unsupported';
        }
    }

    // check connected printers
    public function getPrinters(){

        $os = $this->getOperatingSystem();
        switch ($os) {
            case 'windows':
                return $this->getWindowsPrinters();
            case 'linux':
                return $this->getLinuxPrinters();
            default:
                return response()->json(['response' => '', 'error' => 'Unsupported operating system']);
            }
    }

    // get windows connected printers
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

     // get linux connected printers
        private function getLinuxPrinters() {
            exec('lpstat -p', $output, $returnCode);

            if ($returnCode !== 0) {
                return response()->json(['response' => '', 'error' => 'Error executing lpstat command']);
            }
            $printerNames = [];
            foreach ($output as $line) {
                if (preg_match('/printer\s+(.+)\s+is/', $line, $matches)) {
                    $printerNames[] = $matches[1];
                }
            }

            if (empty($printerNames)) {
                return response()->json(['response' => '', 'error' => 'No printers found']);
            }
            return response()->json(['response' => implode('|', $printerNames), 'error' => '']);
        }

    // select printer to session data
    public function setPrinter($name) {
        Session::put('selected_printer', $name);
        return response()->json(['response' => 'Printer set to ' . $name]);
    }

    // get selected printer from session data
    public function getPrinter()
    {
        $selectedPrinter = Session::get('selected_printer', 'N/A');
        return $selectedPrinter;
    }

    // print raw
    public function printRaw(Request $request)
    {

        if (!$request->session()->isStarted()) {
            $request->session()->start();
        }

        $printerName = $this->getPrinter();

        if ($printerName === 'N/A') {
            return response()->json(['response' => '', 'error' => 'No printer selected']);
        }

        $request->validate([
            'raw_data' => 'required|string',
        ]);

        $rawData = $request->input('raw_data');
        // $rawData = 'hello, this is printing test on windows';
        // $filePath = public_path('test.txt');
        // check os and run print cmd
        $os = $this->getOperatingSystem();

        switch ($os) {
            case 'windows':
                $command = "powershell -Command \"Out-Printer -Name '$printerName' -InputObject '$rawData'\"";
                // $command = "powershell -Command \"Out-Printer -Name '$printerName' -InputObject (Get-Content '$filePath')\"";
                $return_var = 0;
                $return_var = 0;
                exec($command, $output, $return_var);
                if ($return_var !== 0) {
                    return response()->json(['response' => '', 'error' => 'Failed to print']);
                }
            break;

            case 'linux':
                $command = "echo '$rawData' | lp -d '$printerName' -o raw -";
                // $command = "lp -d $printerName '$filePath'";
                exec($command, $output, $return_var);
                if ($return_var !== 0) {
                    return response()->json(['response' => '', 'error' => 'Failed to print']);
                }
            break;

            default:
            return response()->json(['response' => '', 'error' => 'Unsupported operating system']);
        }

        return response()->json(['response' => 'OK', 'error' => '']);

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


