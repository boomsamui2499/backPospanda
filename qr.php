<?Php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: *");
require_once("lib/PromptPayQR.php");
$id = $_GET['id'];
$amount = $_GET['amount'];
// var_dump($name);
$PromptPayQR = new PromptPayQR(); // new object
$PromptPayQR->size = 8; // Set QR code size to 8
$PromptPayQR->id = $id; // PromptPay ID
$PromptPayQR->amount = $amount; // Set amount (not necessary)
// $PromptPayQR->generate();
echo '<h1><center>Qr Code</h1>';
echo '<h1><center>ยอดที่ต้องชำระ'.$amount.'฿</h1>';
echo '<center><img src="' . $PromptPayQR->generate() . '" />';
// unlink('public\storage\barcode\qrcode.png');

// return response()->json([
//     "success" => true,
//     "url" => url('/') . "/storage" . str_replace("public", "", $data)
// ]);  