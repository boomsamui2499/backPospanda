<?php

namespace App\Http\Controllers;
// require_once __DIR__ . '/vendor/autoload.php'; 
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use item;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
use App\Models\Product;
use App\Models\MetaData;
use App\Http\Resources\ProductResource;
use Carbon\Carbon;
use \Milon\Barcode\DNS1D;
use \Milon\Barcode\DNS2D;

use Exception;
use League\Flysystem\RootViolationException;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

class PrintController extends Controller
{
    public function test(Request $request, $barcode)
    {

        $data = "public/barcode/" . $barcode . ".png";
        DNS1D::getBarcodePNGPath($barcode, 'C39', 1, 50, array(0, 0, 0));
        return response()->json([
            "success" => true,
            "url" => url('/') . "/storage" . str_replace("public", "", $data)
        ]);
    }
    public function generate(Request $request)
    {
        $current_timestamp = Carbon::now()->timestamp;
        $OS = PHP_OS;
        $DataName = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'printer')->where("meta_key", 'printer_name')->first();
        $Datawidth = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'printer')->where("meta_key", 'printer_width')->first();

        $html_data = $request->input('html_data');
        $time = "image";
        $time .= $current_timestamp;
        $html_file_name = $time . ".html";
        $jpg_file_name = $time . ".jpg";
        file_put_contents('../storage/app/public/html/' . $html_file_name, $html_data);
        ($Datawidth->meta_value == 1 ? $width = 380 : $width = 560);

        $source = '../storage/app/public/html/' . $html_file_name;
        $dest = '../storage/app/public/jpg/' . $jpg_file_name;
        $command = sprintf(
            "wkhtmltoimage -n -q --width %s %s %s",
            escapeshellarg($width),
            escapeshellarg($source),
            escapeshellarg($dest)
        );
        $descriptors = array(
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );
        $process = proc_open($command, $descriptors, $fd);
        if (is_resource($process)) {
            /* Read stdout */
            $outputStr = stream_get_contents($fd[1]);
            fclose($fd[1]);
            /* Read stderr */
            $errorStr = stream_get_contents($fd[2]);
            fclose($fd[2]);
            /* Finish up */
            $retval = proc_close($process);
            if ($retval != 0) {
                throw new Exception("Command failed: $outputStr $errorStr");
            }
        } else {
            throw new Exception("Command  failed to start.");
        }
        if ($OS == "WINNT") {
            $connector = new WindowsPrintConnector($DataName->meta_value); // Add connector for your printer here. for windows 
            $printer = new Printer($connector);
        } elseif ($OS == "LINUX") {

            $connector = new CupsPrintConnector($DataName->meta_value); // Add connector for your printer here. for linux
            $printer = new Printer($connector);
        }


        $img_path = $dest;
        $max_height = 1500;
        list($width, $height) = getimagesize($img_path);
        //echo "HEIGHT : ". $height ;
        if ($height > 1500) {
            # code...
            $n2 = $height / $max_height;
            $n = (int) ceil($n2);
            //echo "N : ". $n;
            $im = imagecreatefromjpeg($img_path);


            for ($i = 0; $i < $n; $i++) {
                //echo "<br /> Loop : ". $i;
                if ($i < $n - 1) {
                    $im2 = imagecrop($im, ['x' => 0, 'y' => $i * $max_height, 'width' => $width, 'height' => $max_height]);
                } else {
                    $im2 = imagecrop($im, ['x' => 0, 'y' => $i * $max_height, 'width' => $width, 'height' => $height - ($i * $max_height)]);
                }
                if ($im2 !== FALSE) {
                    $name_slice = $img_path . '-' . $i . '.jpg';
                    imagejpeg($im2, $name_slice);
                    imagedestroy($im2);
                }
            }

            imagedestroy($im);
            //เปลี่ยนฟังชั่นในไฟลC:\xampp\htdocs\qp2backend\vendor\mike42\escpos-php\src\Mike42\Escpos\GdEscposImage.php ให้เป็นใช้่เฉพาะ php8.0
            //$connector = new FilePrintConnector("/dev/usb/lp0");
            // echo(PHP_OS);

            // $connector = new NetworkPrintConnector('192.168.1.105', 'USB002'); // Add connector for your printer here.
            // $printer = new Printer($connector);
            try {

                for ($i = 0; $i < $n; $i++) {
                    sleep(1);
                    $name_slice = $img_path . '-' . $i . '.jpg';

                    try {
                        //$tux = new EscposImage($dest);
                        $tux = EscposImage::load($name_slice);
                    } catch (Exception $ex) {
                        //unlink($dest);
                        throw $ex;
                    }
                    $printer->bitImage($tux);
                    $printer->pulse();
                }


                $printer->feed(10);
                $printer->cut();
            } catch (Exception $e) {
                echo $e->getMessage();
            } finally {
                $printer->close();
            }
        } else {
            try {
                $bill = EscposImage::load($img_path, false);
                $printer->pulse();
                $printer->bitImage($bill);
                $printer->feed(10);
                $printer->cut();
                $printer->close();
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }

        return response()->json([
            "success" => true,
            "message" => "Printing bill"
        ]);
    }



    public function testbarcode(Request $request)
    {
        $connector = new WindowsPrintConnector("XP-58");
        $printer = new Printer($connector);
        
        /* Height and width */
        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
        $printer->text("Height and bar width\n");
        $printer->selectPrintMode();
            $printer->setBarcodeHeight(80);
            $printer->setBarcodeWidth(5);

        $printer -> text("Default look\n");
        $printer->barcode("ABC", Printer::BARCODE_CODE39);
        $printer->close();

            // // $printer->setBarcodeTextPosition(Printer::BARCODE_TEXT_BELOW);
            // $printer->barcode("9876",Printer::BARCODE_CODE39);
            // $printer->feed(5);
            // $printer->cut();
       

        return response()->json([
            "success" => true,
            "message" => "Printing barcode"
        ]);
    }
}
