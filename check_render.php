<?php
$response = @file_get_contents("https://doantotnghiep-u4gt.onrender.com/trigger-recommendation");
if ($response === false) {
    echo "FAILED: " . error_get_last()["message"];
} else {
    echo "SUCCESS: " . $response;
}

