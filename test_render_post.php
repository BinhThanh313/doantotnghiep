<?php
$data = json_encode(["email" => "admin@electro.vn", "password" => "password"]);
$options = [
    "http" => [
        "method"  => "POST",
        "header"  => "Content-Type: application/json\r\nAccept: application/json\r\n",
        "content" => $data
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents("https://doantotnghiep-u4gt.onrender.com/api/admin/login", false, $context);
if ($response === false) { die("Login failed: " . error_get_last()["message"]); }
$responseData = json_decode($response, true);
$token = $responseData["token"] ?? "";

$postData = json_encode(["ids" => [1], "action" => "update_status", "status" => "processing"]);
$postOptions = [
    "http" => [
        "method"  => "POST",
        "header"  => "Content-Type: application/json\r\nAccept: application/json\r\nAuthorization: Bearer " . $token . "\r\n",
        "content" => $postData,
        "ignore_errors" => true
    ]
];
$postContext = stream_context_create($postOptions);
$postResponse = file_get_contents("https://doantotnghiep-u4gt.onrender.com/api/admin/orders/bulk", false, $postContext);
echo "POST Result:\n" . $postResponse;

