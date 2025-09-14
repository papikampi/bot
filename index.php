<?php
$configFile = __DIR__ . '/config.json';
$data = [
    'source_channels' => [''],
    'target_channels' => [''],
    'footer_text' => '',
    'bot_token' => ''
];
if (file_exists($configFile)) {
    $json = file_get_contents($configFile);
    $data = json_decode($json, true) ?: $data;
}
$error = null; $success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sources = array_filter(array_map('trim', explode("\n", $_POST['source_channels'] ?? '')));
    $targets = array_filter(array_map('trim', explode("\n", $_POST['target_channels'] ?? '')));
    $footer = trim($_POST['footer_text'] ?? '');
    $token = trim($_POST['bot_token'] ?? '');
    $password = trim($_POST['admin_password'] ?? '');
    if ($password !== (getenv('ADMIN_PASSWORD') ?: 'admin123')) {
        $error = "رمز مدیریت اشتباه است.";
    } elseif (empty($sources) || empty($targets)) {
        $error = "حداقل یک کانال مبدا و یک کانال مقصد لازم است.";
    } elseif (empty($token)) {
        $error = "توکن ربات الزامی است.";
    } else {
        $data = [
            'source_channels' => array_values($sources),
            'target_channels' => array_values($targets),
            'footer_text' => $footer,
            'bot_token' => $token,
        ];
        if (file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)))
            $success = "ذخیره شد.";
        else $error = "خطا در ذخیره.";
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="UTF-8"><title>مدیریت ربات</title></head>
<body>
<h2>مدیریت ربات</h2>
<?php if ($error) echo "<p style='color:red'>$error</p>"; ?>
<?php if ($success) echo "<p style='color:green'>$success</p>"; ?>
<form method="post">
    <label>کانال‌های مبدا:</label><br>
    <textarea name="source_channels" rows="3"><?php echo htmlspecialchars(implode("\n", $data['source_channels'])); ?></textarea><br>
    <label>کانال‌های مقصد:</label><br>
    <textarea name="target_channels" rows="3"><?php echo htmlspecialchars(implode("\n", $data['target_channels'])); ?></textarea><br>
    <label>متن پایانی:</label><br>
    <input type="text" name="footer_text" value="<?php echo htmlspecialchars($data['footer_text']); ?>"><br>
    <label>توکن ربات:</label><br>
    <input type="password" name="bot_token" value="<?php echo htmlspecialchars($data['bot_token']); ?>"><br>
    <label>رمز مدیریت:</label><br>
    <input type="password" name="admin_password"><br><br>
    <button type="submit">ذخیره</button>
</form>
</body>
</html>
