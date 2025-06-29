<?php
header('Content-type: text/plain');

$text = $_POST['text'] ?? '';
$phone = $_POST['phoneNumber'] ?? '0727430534';

$steps = explode("*", $text);

if ($text == "") {
    echo "CON Welcome to Debug Test\n1. Continue";
} elseif ($steps[0] == "1" && count($steps) == 1) {
    echo "CON Enter your National ID:";
} elseif ($steps[0] == "1" && count($steps) == 2) {
    echo "CON Thank you. Here is the main menu:\n1. Deposit\n2. Bet\n3. Withdraw";
} else {
    echo "END Invalid input.";
}
