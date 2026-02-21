<?php
/**
 * API للتحقق من صحة تسجيل الدخول - مع دعم الحسابات التجريبية
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// تضمين ملف الإعدادات
require_once 'db_config.php';

// استقبال البيانات
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// التحقق من البيانات
if (empty($input['username']) || empty($input['password']) || empty($input['activation_code'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please provide all required data'
    ]);
    exit();
}

$username = trim($input['username']);
$password = trim($input['password']);
$code = trim($input['activation_code']);

// التحقق من صحة بيانات الدخول
$sql = "SELECT s.*, i.m3u_url, i.server_url, i.stream_port 
        FROM subscribers s 
        LEFT JOIN iptv_servers i ON s.server_id = i.id 
        WHERE s.username = ? AND s.activation_code = ? 
        AND s.status = 'active' 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // التحقق من كلمة المرور
    if (password_verify($password, $user['password'])) {
        
        // التحقق من صلاحية الاشتراك
        $expiry_date = new DateTime($user['expiry_date']);
        $current_date = new DateTime();
        
        if ($current_date > $expiry_date) {
            // تحديث حالة المشترك إلى منتهي
            $update = $conn->prepare("UPDATE subscribers SET status = 'expired' WHERE id = ?");
            $update->bind_param("i", $user['id']);
            $update->execute();
            $update->close();
            
            echo json_encode([
                'status' => 'error',
                'message' => 'Subscription has expired'
            ]);
            exit();
        }
        
        // إذا كان حساباً تجريبياً، تحديث حالة الاستخدام
        if ($user['is_trial'] && !$user['trial_used']) {
            $update_trial = $conn->prepare("UPDATE subscribers SET trial_used = TRUE WHERE id = ?");
            $update_trial->bind_param("i", $user['id']);
            $update_trial->execute();
            $update_trial->close();
        }
        
        // حساب الأيام/الساعات المتبقية
        $interval = $current_date->diff($expiry_date);
        if ($user['is_trial']) {
            $remaining = $interval->h + ($interval->days * 24);
            $remaining_unit = 'hours';
        } else {
            $remaining = $interval->days;
            $remaining_unit = 'days';
        }
        
        // تحديث آخر دخول
        $update = $conn->prepare("
            UPDATE subscribers 
            SET last_login = NOW(), 
                total_logins = total_logins + 1 
            WHERE id = ?
        ");
        $update->bind_param("i", $user['id']);
        $update->execute();
        $update->close();
        
        // إعداد رابط M3U
        $m3u_url = $user['m3u_url'];
        if (strpos($m3u_url, '{username}') !== false) {
            $m3u_url = str_replace('{username}', $username, $m3u_url);
            $m3u_url = str_replace('{password}', $password, $m3u_url);
        }
        
        // إرجاع النتيجة
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'username' => $user['username'],
                    'activation_code' => $user['activation_code'],
                    'expiry_date' => $user['expiry_date'],
                    'remaining' => $remaining,
                    'remaining_unit' => $remaining_unit,
                    'is_trial' => $user['is_trial']
                ],
                'server' => [
                    'url' => $user['server_url'],
                    'port' => $user['stream_port'],
                    'm3u_url' => $m3u_url
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Incorrect password'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid login credentials'
    ]);
}

$stmt->close();
$conn->close();
?>