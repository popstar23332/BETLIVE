<?php
session_start();
header('Content-type: text/plain');

$text = $_POST['text'] ?? '';
$phone = $_POST['phoneNumber'] ?? '0727430534';
$steps = explode("*", $text);

require_once 'db.php';
require_once 'sms.php';
require_once 'mpesa.php';
require_once 'football_api.php';

if ($text == "") {
    echo "CON Welcome to Popstars Bet\n1. Log In";
} elseif ($steps[0] == "1" && count($steps) == 1) {
    echo "CON Enter your National ID:";
} elseif ($steps[0] == "1" && count($steps) == 2) {
    $id = $steps[1];
    $success = registerUser($pdo, $phone, $id);
    if ($success) {
        echo "CON Main Menu:\n1. Deposit\n2. Bet\n3. Withdraw";
    } else {
        echo "END Registration failed. Please try again later.";
    }
} elseif ($steps[0] == "1" && count($steps) == 3) {
    $option = $steps[2];
    if ($option == "1") {
        echo "END You selected Deposit";
    } elseif ($option == "2") {
        echo "END You selected Bet";
    } elseif ($option == "3") {
        echo "END You selected Withdraw";
    } else {
        echo "END Invalid selection from menu.";
    }
} else {
    echo "END Invalid input.";
}
