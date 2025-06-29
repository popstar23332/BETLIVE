<?php
function stkPush($phone, $amount, $type = 'deposit') {
    $accessToken = getMpesaAccessToken();

    $shortcode = "174379"; // Sandbox shortcode
    $passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2c2c46b7e4d07c3b5b4f91f79e8e2c70"; // Replace with your passkey
    $timestamp = date("YmdHis");
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $payload = [
        "BusinessShortCode" => $shortcode,
        "Password" => $password,
        "Timestamp" => $timestamp,
        "TransactionType" => "CustomerPayBillOnline",
        "Amount" => $amount,
        "PartyA" => formatPhoneNumber($phone),
        "PartyB" => $shortcode,
        "PhoneNumber" => formatPhoneNumber($phone),
        "CallBackURL" => "https://yourdomain.com/mpesa/callback", // Replace with your live callback
        "AccountReference" => "PopStarBet",
        "TransactionDesc" => "$type payment"
    ];

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $accessToken"
    ];

    $ch = curl_init("https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    curl_close($ch);

    logMpesaRequest($phone, $amount, "STK_PUSH");

    return json_decode($response, true);
}

function sendProfitToCompany($amount, $gameId) {
    $accessToken = getMpesaAccessToken();

    $url = "https://sandbox.safaricom.co.ke/mpesa/b2b/v1/paymentrequest";

    $payload = [
        "InitiatorName" => "testapi", // Replace with your initiator username
        "SecurityCredential" => "ENCRYPTED_PASSWORD", // Replace with encrypted credential
        "CommandID" => "BusinessPayBill",
        "SenderIdentifierType" => "4",
        "ReceiverIdentifierType" => "4",
        "Amount" => $amount,
        "PartyA" => "600XXX",
        "PartyB" => "600YYY",
        "Remarks" => "Profit for Game ID $gameId",
        "AccountReference" => "Game_$gameId",
        "QueueTimeOutURL" => "https://yourdomain.com/mpesa/timeout",
        "ResultURL" => "https://yourdomain.com/mpesa/result"
    ];

    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer $accessToken"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    curl_close($ch);

    logMpesaRequest("COMPANY_PAYBILL", $amount, "profit_transfer_game_$gameId");
    return $response;
}

function getMpesaAccessToken() {
    $consumerKey = "YOUR_CONSUMER_KEY"; // Replace with your sandbox/live key
    $consumerSecret = "YOUR_CONSUMER_SECRET"; // Replace with your key
    $credentials = base64_encode("$consumerKey:$consumerSecret");

    $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
    $headers = ["Authorization: Basic $credentials"];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response);
    return $result->access_token ?? null;
}

function formatPhoneNumber($phone) {
    // Ensure phone starts with 2547...
    $phone = preg_replace("/[^0-9]/", "", $phone);
    if (substr($phone, 0, 1) === "0") {
        $phone = "254" . substr($phone, 1);
    }
    return $phone;
}

function logMpesaRequest($phone, $amount, $type) {
    $file = fopen("mpesa_log.txt", "a");
    fwrite($file, date("Y-m-d H:i:s") . ": $type of $amount for $phone\n");
    fclose($file);
}
?>
