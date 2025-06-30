<?php
date_default_timezone_set('Africa/Nairobi');
header("Content-Type: application/json");

// Input
$inputData = json_decode(file_get_contents("php://input"), true);
if (!$inputData || empty($inputData['phone']) || empty($inputData['amount'])) {
    echo json_encode(["error" => "Phone and amount are required"]);
    exit;
}

$phone = $inputData['phone']; // e.g. '254712345678'
$amount = $inputData['amount']; // e.g. 100

// M-Pesa credentials
$consumerKey = "1n1TXMzll0HUSEEV1ItqgZvAQph1DGJMIyrSHrxSVI3Njml6";
$consumerSecret = "F1qt3ip2O8lQU5c7Sh4IvJrJoihoxkPd31WDnnpzl4JG8UKHfPUIqZQIRW0iuQ0zta";
$BusinessShortCode = "174379";
$Passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";
$callbackurl = "https://6ijmjiomxl.sharedwithexpose.com/api/mpesa/callback.php";

// Get access token
$credentials = base64_encode($consumerKey . ':' . $consumerSecret);
$ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$token_response = curl_exec($ch);
$token_data = json_decode($token_response);
curl_close($ch);

if (!isset($token_data->access_token)) {
    echo json_encode(["error" => "Access token failed", "details" => $token_response]);
    exit;
}
$access_token = $token_data->access_token;

// STK Push Request
$Timestamp = date('YmdHis');
$Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);

$stkHeader = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
];

$stkPayload = [
    'BusinessShortCode' => $BusinessShortCode,
    'Password' => $Password,
    'Timestamp' => $Timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => $BusinessShortCode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callbackurl,
    'AccountReference' => 'KAYPAY',
    'TransactionDesc' => 'POS Payment'
];

$ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
curl_setopt($ch, CURLOPT_HTTPHEADER, $stkHeader);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
