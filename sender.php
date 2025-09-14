<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// بررسی توکن دسترسی
$SECRET = getenv('WEBHOOK_SECRET') ?: 'MY_SECRET';
if (!isset($_GET['token']) || $_GET['token'] !== $SECRET) {
    http_response_code(403);
    exit("Access denied");
}

// بارگذاری config
$configFile = __DIR__ . '/config.json';
if (!file_exists($configFile)) exit("config.json not found");
$config = json_decode(file_get_contents($configFile), true);

$BOT_TOKEN = $config['bot_token'];
$TARGETS = $config['target_channels'] ?? [];
$SOURCES = $config['source_channels'] ?? [];

$STATE_FILE = __DIR__ . '/storage/state.json';
if (!file_exists(dirname($STATE_FILE))) mkdir(dirname($STATE_FILE), 0777, true);
$state = file_exists($STATE_FILE) ? json_decode(file_get_contents($STATE_FILE), true) : [];

// تابع فوروارد پیام
function forwardMessage($bot, $chat, $from_chat_id, $message_id) {
    $url = "https://api.telegram.org/bot$bot/forwardMessage";
    file_get_contents($url."?chat_id=$chat&from_chat_id=$from_chat_id&message_id=$message_id");
}

// بررسی کانال‌ها و فوروارد پیام‌ها
foreach ($SOURCES as $src) {
    $src = ltrim($src, '@');
    $lastSeen = $state[$src] ?? 0;

    $html = @file_get_contents("https://t.me/s/$src");
    if (!$html) continue;

    preg_match_all('/data-post=".*?\/(\d+)"/', $html, $matches);
    foreach ($matches[1] as $id) {
        if ($id <= $lastSeen) continue;

        // فوروارد کردن پیام به همه کانال‌های هدف
        foreach ($TARGETS as $t) forwardMessage($BOT_TOKEN, $t, $src, $id);

        $lastSeen = $id;
    }

    $state[$src] = $lastSeen;
}

// ذخیره آخرین وضعیت
file_put_contents($STATE_FILE, json_encode($state));
echo "OK";
