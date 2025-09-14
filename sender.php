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
$FOOTER = $config['footer_text'] ?? '';

$STATE_FILE = __DIR__ . '/storage/state.json';
if (!file_exists(dirname($STATE_FILE))) mkdir(dirname($STATE_FILE), 0777, true);
$state = file_exists($STATE_FILE) ? json_decode(file_get_contents($STATE_FILE), true) : [];

// تابع ارسال پیام
function sendMessage($bot, $chat, $text) {
    $url = "https://api.telegram.org/bot$bot/sendMessage";
    file_get_contents($url."?chat_id=$chat&text=".urlencode($text)."&parse_mode=HTML");
}

// بررسی کانال‌ها و ارسال پیام‌ها
foreach ($SOURCES as $src) {
    $src = ltrim($src, '@');
    $lastSeen = $state[$src] ?? 0;

    $html = @file_get_contents("https://t.me/s/$src");
    if (!$html) continue;

    preg_match_all('/data-post=".*?\/(\d+)"/', $html, $matches);
    foreach ($matches[1] as $id) {
        if ($id <= $lastSeen) continue;

        // استخراج متن پیام
        preg_match("/$id.*?tgme_widget_message_text.*?>(.*?)<\/div/s", $html, $m);
        $text = isset($m[1]) ? strip_tags($m[1]) : '';

        // حذف لینک‌ها، @username و هر چیزی که به کانال یا وب اشاره دارد
        $text = preg_replace([
            '/https?:\/\/\S+/i',  // حذف لینک‌ها
            '/www\.\S+/i',         // حذف www
            '/@\S+/i'              // حذف نام کاربری
        ], '', $text);

        // trim کردن اضافات
        $text = trim($text);

        // اضافه کردن footer خودت
        $final = $text . "\n\n" . $FOOTER;

        foreach ($TARGETS as $t) sendMessage($BOT_TOKEN, $t, $final);

        $lastSeen = $id;
    }

    $state[$src] = $lastSeen;
}

// ذخیره وضعیت
file_put_contents($STATE_FILE, json_encode($state));
echo "OK";
