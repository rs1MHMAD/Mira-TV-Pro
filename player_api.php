<?php
/**
 * ملف الربط بين ميرا تي في برو ولوحة الإدارة وسيرفر البث
 * API Bridge (محدث لحل مشاكل الاتصال والحماية)
 */

// إخفاء الأخطاء لضمان نظافة المخرجات
ini_set('display_errors', 0);

// 💡 هذا هو الحل السحري لمشكلة الحظر (CORS) - السماح للبرنامج بالاتصال بالسيرفر
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// معالجة طلبات الـ OPTIONS التي يرسلها المتصفح للتحقق من الحماية
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// 1. الاتصال بقاعدة البيانات
$config_file = 'db_config.php';
if (!file_exists($config_file)) {
    die(json_encode(['user_info' => ['auth' => 0, 'error' => 'db_file_missing']]));
}
$conn = include $config_file;

// استقبال البيانات من التطبيق
$username = $_GET['username'] ?? '';
$password = $_GET['password'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($username) || empty($password)) {
    die(json_encode(['user_info' => ['auth' => 0, 'error' => 'empty_credentials']]));
}

// 2. التحقق من المشترك في قاعدة بيانات اللوحة
$stmt = $conn->prepare("
    SELECT s.*, srv.server_url, srv.stream_port 
    FROM subscribers s 
    LEFT JOIN iptv_servers srv ON s.server_id = srv.id 
    WHERE s.username = ?
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// التحقق من صحة المستخدم وكلمة المرور
if (!$user || !password_verify($password, $user['password'])) {
    die(json_encode(['user_info' => ['auth' => 0, 'error' => 'wrong_password']]));
}

// التحقق من تاريخ الانتهاء فقط (تم إزالة شرط الحالة لتجنب الأخطاء)
if (strtotime($user['expiry_date']) < time()) {
    die(json_encode(['user_info' => ['auth' => 0, 'status' => 'Expired']]));
}

// تجهيز رابط السيرفر الحقيقي (الذي سيتم جلب القنوات منه)
$server_url = rtrim($user['server_url'] ?? '', '/');
$stream_port = $user['stream_port'] ?? '8080';
$real_server_base = $server_url . ':' . $stream_port;

// 3. إذا كان الطلب هو تسجيل الدخول فقط (فتح البرنامج)
if (empty($action)) {
    echo json_encode([
        "user_info" => [
            "username" => $user['username'],
            "auth" => 1,
            "status" => "Active",
            "exp_date" => (string)strtotime($user['expiry_date']),
            "is_trial" => $user['is_trial'],
            "active_cons" => 0,
            "max_connections" => 1,
            "real_server" => $real_server_base 
        ]
    ]);
    exit;
}

// 4. جلب القنوات (Proxy) من السيرفر الحقيقي
$real_api_url = $real_server_base . '/player_api.php';

// بناء الرابط مع تمرير نفس البيانات
$query_string = http_build_query($_GET);
$final_url = $real_api_url . '?' . $query_string;

// جلب البيانات باستخدام cURL لسرعة الأداء
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $final_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
curl_close($ch);

// إرسال النتيجة للبرنامج
if ($response) {
    echo $response;
} else {
    echo json_encode([]);
}
?>