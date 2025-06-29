function sendSMS($phone, $message) {
    $username = "your_username"; // Replace with your AT username
    $apiKey   = "your_api_key";  // Replace with your AT API key
    $from     = "PataBet";       // Optional sender ID (must be approved)

    $url = "https://api.africastalking.com/version1/messaging";
    
    $postData = http_build_query([
        'username' => $username,
        'to'       => $phone,
        'message'  => $message,
        'from'     => $from
    ]);

    $headers = [
        "apiKey: $apiKey",
        "Content-Type: application/x-www-form-urlencoded"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    curl_close($ch);

    // Optional: Log or decode response
    file_put_contents("sms_log.txt", date("Y-m-d H:i:s") . " | SMS to $phone: $message\nResponse: $response\n", FILE_APPEND);

    return $response;
}
