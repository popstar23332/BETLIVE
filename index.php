<?php
header('Content-type: text/plain');

$text = $_POST['text'] ?? '';
$phone = $_POST['phoneNumber'] ?? '0727430534';

$steps = explode("*", $text);

if ($text == "") {
    echo "CON Welcome to Debug Test\n1. Log In";
} elseif ($steps[0] == "1" && count($steps) == 1) {
    echo "CON Enter your National ID:";
} elseif ($steps[0] == "1" && count($steps) == 2) {
    echo "CON Main Menu:\n1. Deposit\n2. Bet\n3. Withdraw";
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
