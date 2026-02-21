<?php
/**
 * ملف إعدادات قاعدة البيانات
 * لا يمكن الوصول إليه مباشرة
 */

// منع الوصول المباشر
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    http_response_code(403);
    exit('<h1 style="color:red;text-align:center;">⛔ الوصول المباشر ممنوع!</h1>');
}

// تعريف ثوابت الاتصال
define('DB_HOST', 'localhost');
define('DB_USER', 'tvus');
define('DB_PASS', 'g}G@b)?L,{63');
define('DB_NAME', 'tv');
define('DB_CHARSET', 'utf8mb4');
define('DB_TIMEZONE', '+03:00');

/**
 * إنشاء اتصال قاعدة البيانات
 * @return mysqli
 */
function getDatabaseConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            // إنشاء اتصال جديد
            $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // التحقق من الأخطاء
            if ($connection->connect_errno) {
                throw new Exception(
                    "فشل الاتصال بقاعدة البيانات: " . 
                    htmlspecialchars($connection->connect_error)
                );
            }
            
            // تعيين ترميز UTF-8
            if (!$connection->set_charset(DB_CHARSET)) {
                throw new Exception("فشل تعيين الترميز: " . $connection->error);
            }
            
            // تعيين المنطقة الزمنية
            $connection->query("SET time_zone = '" . DB_TIMEZONE . "'");
            
            // إعدادات إضافية
            $connection->query("SET sql_mode = ''");
            $connection->query("SET NAMES " . DB_CHARSET);
            
        } catch (Exception $e) {
            // تسجيل الخطأ
            error_log("[DB ERROR] " . date('Y-m-d H:i:s') . " - " . $e->getMessage());
            
            // عرض رسالة مناسبة
            if (isset($_SERVER['HTTP_HOST']) && 
                (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                 strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
                // في التطوير المحلي
                die('<div style="font-family: Arial, sans-serif; padding: 20px; background: #ffebee; border: 2px solid #c62828; border-radius: 10px; max-width: 800px; margin: 50px auto;">
                    <h2 style="color: #c62828;">🚨 خطأ في قاعدة البيانات</h2>
                    <p><strong>التفاصيل:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                    <hr>
                    <h3>🔧 خطوات استكشاف الأخطاء وإصلاحها:</h3>
                    <ol>
                        <li>تأكد من تشغيل خادم MySQL</li>
                        <li>تحقق من صحة بيانات الاتصال في db_config.php</li>
                        <li>تأكد من وجود قاعدة البيانات: <code>tv</code></li>
                        <li>تحقق من صلاحيات المستخدم <code>tvus</code></li>
                        <li>جرب تنفيذ استعلام SQL مباشرة: <code>SELECT 1</code></li>
                    </ol>
                    <p><strong>بيانات الاتصال الحالية:</strong></p>
                    <ul>
                        <li>Host: ' . DB_HOST . '</li>
                        <li>User: ' . DB_USER . '</li>
                        <li>Database: ' . DB_NAME . '</li>
                    </ul>
                </div>');
            } else {
                // في الإنتاج
                die(json_encode([
                    'status' => 'error',
                    'message' => 'عذراً، حدث خطأ في النظام. يرجى المحاولة لاحقاً.'
                ], JSON_UNESCAPED_UNICODE));
            }
        }
    }
    
    return $connection;
}

// إنشاء اتصال تلقائي
$conn = getDatabaseConnection();

// دالة للتحقق من صحة الاتصال
function isDatabaseConnected($connection = null) {
    if ($connection === null) {
        global $conn;
        $connection = $conn;
    }
    
    return ($connection && 
            $connection instanceof mysqli && 
            !$connection->connect_errno && 
            $connection->ping());
}

// دالة لتنفيذ الاستعلامات بأمان
function dbQuery($sql, $params = [], $types = '') {
    global $conn;
    
    if (!isDatabaseConnected($conn)) {
        throw new Exception("فقد الاتصال بقاعدة البيانات");
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("فشل تحضير الاستعلام: " . $conn->error);
    }
    
    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("فشل تنفيذ الاستعلام: " . $stmt->error);
    }
    
    return $stmt;
}

// دالة للحصول على صف واحد
function dbFetchOne($sql, $params = [], $types = '') {
    $stmt = dbQuery($sql, $params, $types);
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

// دالة للحصول على جميع الصفوف
function dbFetchAll($sql, $params = [], $types = '') {
    $stmt = dbQuery($sql, $params, $types);
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

// دالة للإدراج
function dbInsert($table, $data) {
    global $conn;
    
    $keys = array_keys($data);
    $values = array_values($data);
    $placeholders = str_repeat('?,', count($values) - 1) . '?';
    $types = str_repeat('s', count($values));
    
    $sql = "INSERT INTO `$table` (`" . implode('`, `', $keys) . "`) 
            VALUES ($placeholders)";
    
    $stmt = dbQuery($sql, $values, $types);
    $insertId = $conn->insert_id;
    $stmt->close();
    
    return $insertId;
}

// دالة للتحديث
function dbUpdate($table, $data, $where, $whereParams = [], $whereTypes = '') {
    $setParts = [];
    $setValues = [];
    $setTypes = '';
    
    foreach ($data as $key => $value) {
        $setParts[] = "`$key` = ?";
        $setValues[] = $value;
        $setTypes .= 's';
    }
    
    $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE $where";
    $params = array_merge($setValues, $whereParams);
    $types = $setTypes . $whereTypes;
    
    $stmt = dbQuery($sql, $params, $types);
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    return $affectedRows;
}

// ======== إعدادات متعددة اللغات ========

// تحديد اللغة الافتراضية
define('DEFAULT_LANG', 'ar');

// الحصول على اللغة الحالية
function getCurrentLanguage() {
    if (isset($_SESSION['admin_language'])) {
        return $_SESSION['admin_language'];
    }
    return DEFAULT_LANG;
}

// تحميل ملفات الترجمة
function loadLanguageFile($language) {
    $langFile = "languages/{$language}.php";
    if (file_exists($langFile)) {
        return include $langFile;
    }
    return include "languages/ar.php";
}

// دالة الترجمة
function trans($key, $params = []) {
    global $lang;
    
    if (isset($lang[$key])) {
        $text = $lang[$key];
        foreach ($params as $param => $value) {
            $text = str_replace(":{$param}", $value, $text);
        }
        return $text;
    }
    
    return $key;
}

// تحديد اتجاه النص
function getTextDirection($language) {
    $directions = [
        'ar' => 'rtl',
        'tr' => 'ltr',
        'en' => 'ltr'
    ];
    return $directions[$language] ?? 'rtl';
}


// إرجاع الاتصال
return $conn;
?>