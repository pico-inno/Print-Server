<?php

namespace App\Http\Controllers;

use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class PrinterController extends Controller
{
    public $adobeAppName="Acrobat.exe";
    public function getPrintCmdForWindow($tempFile,$printerName){
        $adobeAppName=$this->adobeAppName;
        return  "start  $adobeAppName  /r  /t \"$tempFile\" \"$printerName\"";
    }
    //check connection
    public function checkConnection(){
        return response()->json([
            'message'=>'success'
        ], 200);
    }
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


    public function getPrintersNameForWindows($result){
        $text = str_replace("Name|", "", $result);
        return explode("|", $text);
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
            if (trim($line)!== '' && strpos($line, '----') === false && $line!='Name') {
                $printerNames[] = trim($line);
            }
        }

        if (empty($printerNames)) {
            return response()->json(['response' => '', 'error' => 'No printer found...']);
        } else {
            return response()->json(['response' =>$printerNames]);
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
    public function setPrinter($printer) {
        Storage::put('printer.txt', $printer);
        return response()->json(['message' => 'Printer set to ' . $printer]);
    }

    // get selected printer from session data
    public function getPrinter()
    {
        if (Storage::exists('printer.txt')) {
            return Storage::get('printer.txt');
        } else {
            return 'N/A';
        }
    }

    // print raw
    public function printRaw(Request $request)
    {
        $printerName = $this->getPrinter();
        if ($printerName === 'N/A') {
            return response()->json(['response' => '', 'error' => 'No printer selected']);
        }
        $request->validate([
            'raw_data' => 'required|string',
        ]);

        $rawData = strip_tags($request->input('raw_data'));
        // check os and run print cmd
        $os = $this->getOperatingSystem();

        switch ($os) {
            case 'windows':
                $command = "powershell -Command \"Out-Printer -Name '$printerName' -InputObject '$rawData'\"";
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
        $printerName = $this->getPrinter();
        if ($printerName === 'N/A') {
            return response()->json(['response' => '', 'error' => 'No printer selected']);
        }

        $printerName='EPSON L3110 Series';
        $os = $this->getOperatingSystem();
        $validatedData = $request->validate([
            'url' => 'required|url',
        ]);
        // $validatedUrl = "https://www.example.com";
        $validatedUrl = $validatedData['url'];
        $response = Http::get($validatedUrl);

        if (!$response->successful()) {
            return response()->json(['response' => '', 'error' => "Failed to fetch URL: $validatedUrl"]);
        }

        $contentType = $response->header('Content-Type');

        switch ($os) {
            case 'windows':
                if (strpos($contentType, 'application/pdf') !== false) {
                    $pdfContent = $response->body();
                    $tempFile = tempnam(sys_get_temp_dir(), 'pdf_content_');
                    file_put_contents($tempFile, $pdfContent);
                    logger([$tempFile,'tempfile']);
                    // $command=start AcroRd32.exe

                    $command = "start Acrobat.exe /r /t \"$tempFile\" \"$printerName\"";
                    // $command = "powershell -Command \"Start-Process -FilePath 'C:\\Program Files\\Adobe\\Acrobat DC\\Acrobat\\Acrobat.exe' -ArgumentList '/t', '$tempFile' -NoNewWindow -Wait\"";
                } elseif (strpos($contentType, 'text/html') === 0) {
                    $htmlContent = $response->body();
                    $dompdf = new Dompdf();
                    $dompdf->loadHtml($htmlContent);
                    $dompdf->render();

                    $tempFile = tempnam(sys_get_temp_dir(), 'pdf_content_');
                    file_put_contents($tempFile,  $dompdf->output());


                    $command = "start Acrobat.exe /t \"$tempFile\" \"$printerName\"";
                    // $command = "powershell -Command \"Start-Process -FilePath 'C:\\Program Files\\Adobe\\Acrobat DC\\Acrobat\\Acrobat.exe' -ArgumentList '/t', '$tempFile' -NoNewWindow -Wait\"";
                } else {
                    return response()->json(['response' => '', 'error' => "Unsupported content type: $contentType"]);
                }
                break;

            case 'linux':
                if (strpos($contentType, 'application/pdf') !== false) {
                    $pdfContent = $response->body();

                    $tempPdfFile = tempnam(sys_get_temp_dir(), 'pdf_content_');
                    file_put_contents($tempPdfFile, $pdfContent);

                    $command = "lp -d $printerName '$tempPdfFile'";
                } elseif (strpos($contentType, 'text/html') === 0) {
                    $htmlContent = $response->body();
                    $dompdf = new Dompdf();
                    $dompdf->loadHtml($htmlContent);
                    $dompdf->render();

                    $tempPdfFile = tempnam(sys_get_temp_dir(), 'pdf_content_');
                    file_put_contents($tempPdfFile,  $dompdf->output());

                    $command = "lp -d $printerName '$tempPdfFile'";
                } else {
                    return response()->json(['response' => '', 'error' => "Unsupported content type: $contentType"]);
                }
                break;

            default:
                return response()->json(['response' => '', 'error' => 'Unsupported operating system']);
        }

        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            return response()->json(['response' => 'Print command executed successfully!']);
        } else {
            return response()->json(['response' => '', 'error' => 'Failed to execute print command.']);
        }

        if (isset($tempFile)) {
            unlink($tempFile);
        }

        if (isset($tempPdfFile)) {
            unlink($tempPdfFile);
        }
    }


    public function printRawToPdf(Request $request){
        $html = $request->input('raw_data');
        $pdf= Pdf::html($html)
        ->format('a4')
        ->save('../test.pdf');

        $tempFile=realpath('../test.pdf');
        $printerName='EPSON L3110 Series';

        // $command = "print $printerName $tempFile";

        $command = "start Acrobat.exe /r /t \"$tempFile\" \"$printerName\"";
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);


        // unlink('../test.pdf');
        // return $pdf;
        // logger($html);
        // // Create Dompdf instance
        // $dompdf = new Dompdf();

        // // Load HTML content
        // $dompdf->loadHtml($html);

        // // Set paper size and orientation (optional)
        // $dompdf->setPaper('A4', 'portrait');

        // // Render PDF (optional)
        //  $dompdf->render();


        // // Get the generated PDF content
        // // $pdfContent = $dompdf->output();
        // // Stream PDF to the browser
        // return $dompdf->stream('document.pdf');
    }

    public function printFile2(Request $request)
    {
        // Extract the pdfContent from the request
        logger($request->toArray());
        $pdfContent = base64_decode($request->input('file'));
        logger($pdfContent);
        // Generate a unique file name
        $fileName = 'pdf_content_def' . uniqid() . '.pdf';

        // Save the file using Storage facade
        Storage::put($fileName, $pdfContent);

        // // Get the path to the saved file
        // $filePath = Storage::path($fileName);
        // logger([$filePath,'tempfile']);


        $printerName = 'EPSON L3110 Series';

        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_content_');
        file_put_contents($tempFile, $pdfContent);
        logger([$tempFile,'tempfile']);
        // $command=start AcroRd32.exe

        // $command = "start Acrobat.exe  /r  /t \"$tempFile\" \"$printerName\"";
        $command =$this-> getPrintCmdForWindow($tempFile,$printerName);
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);





        // logger($pdfFilePath);
        // file_put_contents($pdfFilePath,  $pdfContent);

        // // Printer name (adjust this to match your printer)


        // $command = "start Acrobat.exe /t \"$tempFile\" \"$printerName\"";
        // // Execute the command
        $output = [];
        $return_var = 0;
        // logger([
        //     $command, $output, $return_var
        // ]);
        // exec($command, $output, $return_var);

        // Optionally, you can check $return_var or $output for error handling

        // Delete the temporary PDF file after printing
        // unlink($tempFile);

        // Return a response indicating success
        return response()->json(['success' => true, 'message' => 'File sent to printer']);
    }

    public function printFile(Request $request)
{
    logger('hello');
    logger($request->toArray());
    $pdfFile = $request->file('pdf_file');
    logger($pdfFile);
    // Handle the uploaded file, such as saving it to storage or processing it further

    return response()->json(['message' => 'PDF uploaded successfully']);
}

}



