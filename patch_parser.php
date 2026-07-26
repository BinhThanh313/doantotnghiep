<?php
$content = file_get_contents('app/Services/Chatbot/ProductQueryParser.php');

$content = str_replace(
    '$this->detectStorage($text, $ramValue)',
    '$this->detectStorage($text, $ramValue), $this->detectScreenSize($text)',
    $content
);

$newMethod = <<<PHP
    private const PRICE_UNIT_PATTERN = '(?:tr|triệu|k|nghìn|tỷ)';

    /** Nhận diện kích thước màn hình (VD: "65 inch", "15.6 in", "55\"") */
    private function detectScreenSize(string \$text): array
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:inch|in|"|\'\')/i', \$text, \$m)) {
            return [['label' => 'Màn hình', 'operator' => 'contains', 'value' => \$m[1]]];
        }
        return [];
    }
PHP;

$content = str_replace("    private const PRICE_UNIT_PATTERN = '(?:tr|triệu|k|nghìn|tỷ)';", $newMethod, $content);

file_put_contents('app/Services/Chatbot/ProductQueryParser.php', $content);
echo "Patched.";
