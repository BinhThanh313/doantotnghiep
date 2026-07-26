<?php
$bot1 = 'Mình tìm thấy 2 sản phẩm phù hợp: - Samsung Galaxy Z Fold5 — 45.990.000đ (Điện thoại) - Samsung Galaxy S24 Ultra — 25.192.000đ (⚡Flash Sale -16%, giá gốc 29.990.000đ) (Điện thoại)';
$bot2 = 'Cả hai sản phẩm Samsung Galaxy Z Fold5 và Samsung Galaxy S24 Ultra đều có cấu hình camera sau tương tự...';
$bot3 = 'Cái đầu tiên bạn nhắc tới là Samsung Galaxy Z Fold5, với pin 5000 mAh và hỗ trợ sạc tối đa 45 W.';
$user4 = 'Thế cái thứ hai pin có trâu không?';

$haystack = trim($bot1 . ' ' . $bot2 . ' ' . $bot3 . ' ' . $user4);
$haystackLower = mb_strtolower($haystack);

$name = 'Samsung Galaxy S24 Ultra';
var_dump(str_contains($haystackLower, mb_strtolower($name)));
