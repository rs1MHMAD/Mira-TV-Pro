<?php
header('Content-Type: application/json; charset=utf-8');
echo '<h2>🔍 اختبار API نظام MIRA TV PRO</h2>';

// اختبار 1: التحقق من كود التفعيل
echo "<h3>1. اختبار api.php</h3>";
$code = "ABC123XYZ"; // استبدل بكود حقيقي
$url1 = "https://tv.gsmcharm.com/api.php?code=" . urlencode($code);

$test1 = file_get_contents($url1);
echo "<pre>";
echo "URL: " . $url1 . "\n";
echo "Response:\n";
print_r(json_decode($test1, true));
echo "</pre>";

// اختبار 2: تسجيل الدخول
echo "<h3>2. اختبار check_auth.php</h3>";
$url2 = "https://tv.gsmcharm.com/check_auth.php";
$data2 = json_encode([
    'username' => 'testuser',
    'password' => 'testpass',
    'activation_code' => 'ABC123XYZ'
]);

$options2 = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data2,
    ],
];

$context2 = stream_context_create($options2);
$test2 = file_get_contents($url2, false, $context2);

echo "<pre>";
echo "URL: " . $url2 . "\n";
echo "Response:\n";
print_r(json_decode($test2, true));
echo "</pre>";

// اختبار 3: اختبار الاتصال
echo "<h3>3. اختبار الاتصال العام</h3>";
$ping_url = "https://tv.gsmcharm.com/";
if (@file_get_contents($ping_url)) {
    echo "<p style='color:green;'>✅ الموقع يعمل بشكل صحيح</p>";
} else {
    echo "<p style='color:red;'>❌ لا يمكن الوصول للموقع</p>";
}
?>