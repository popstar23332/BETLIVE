<?php
echo "<pre>";
if (file_exists("debug_register.txt")) {
    echo file_get_contents("debug_register.txt");
} else {
    echo "No log found.";
}
echo "</pre>";
