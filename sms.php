<?php
function sendSMS($phone, $message) {
    $log = date("Y-m-d H:i:s") . " | SMS to $phone: $message\n";
    file_put_contents("sms_log.txt", $log, FILE_APPEND);
    return true;
}
