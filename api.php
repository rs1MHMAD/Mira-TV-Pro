<?php
/**
 * API للتحقق من كود التفعيل
 */

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

// تضمين ملف الإعدادات
require_once 'db_config.php';

// الحصول على الكود
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

if (empty($code)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'الرجاء إدخال كود التفعيل'
    ]);
    exit();
}

// الاستعلام عن بيانات المشترك
$sql = "SELECT s.*, i.m3u_url, i.server_name, i.server_url, i.stream_port 
        FROM subscribers s 
        LEFT JOIN iptv_servers i ON s.server_id = i.id 
        WHERE s.activation_code = ? 
        AND s.status = 'active'
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطأ في النظام'
    ]);
    exit();
}

$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    
    // حساب الأيام المتبقية
    $expiry_date = new DateTime($data['expiry_date']);
    $current_date = new DateTime();
    
    $interval = $current_date->diff($expiry_date);
    $remaining_days = $interval->days;
    
    // إذا انتهت الصلاحية
    if ($current_date > $expiry_date) {
        $remaining_days = 0;
        
        echo json_encode([
            'status' => 'error',
            'message' => 'انتهت صلاحية الاشتراك'
        ]);
        exit();
    }
    
    // إعداد رابط M3U
    $m3u_url = $data['m3u_url'];
    if (strpos($m3u_url, '{username}') !== false && isset($data['username'])) {
        $m3u_url = str_replace('{username}', $data['username'], $m3u_url);
        $m3u_url = str_replace('{password}', $data['password'], $m3u_url);
    }
    
    // إرجاع البيانات
    echo json_encode([
        'status' => 'success',
        'message' => 'تم العثور على الاشتراك',
        'data' => [
            'full_name' => $data['full_name'],
            'username' => $data['username'],
            'activation_code' => $data['activation_code'],
            'expiry_date' => $data['expiry_date'],
            'remaining_days' => $remaining_days,
            'server_name' => $data['server_name'],
            'server_url' => $data['server_url'],
            'stream_port' => $data['stream_port'],
            'm3u_url' => $m3u_url,
            'created_at' => $data['created_at'],
            'last_login' => $data['last_login'],
            'max_devices' => $data['max_devices'],
            'device_count' => $data['device_count']
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'كود التفعيل غير صالح أو منتهي الصلاحية'
    ]);
}

$stmt->close();
$conn->close();
?>