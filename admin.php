<?php
/**
 * لوحة تحكم MIRA TV PRO - الإصدار النهائي
 * مع دعم متعدد اللغات وقيود الموزعين
 */

// ============================================
// 1. الإعدادات الأولية
// ============================================

// تضمين ملف الإعدادات
$config_file = 'db_config.php';
if (!file_exists($config_file)) {
    die('Configuration file not found.');
}

// تضمين ملف الإعدادات
$conn = include $config_file;

// التحقق من الاتصال
if (!($conn instanceof mysqli)) {
    die('Database connection failed.');
}

// بدء الجلسة
session_start();
date_default_timezone_set('Asia/Riyadh');

// ============================================
// 2. إعدادات اللغة
// ============================================

// تحديد اللغة الافتراضية
define('DEFAULT_LANGUAGE', 'ar');
$supported_languages = ['ar', 'tr', 'en'];

// الحصول على اللغة الحالية
if (isset($_GET['lang']) && in_array($_GET['lang'], $supported_languages)) {
    $_SESSION['admin_language'] = $_GET['lang'];
    header('Location: admin.php');
    exit;
}

$current_language = $_SESSION['admin_language'] ?? DEFAULT_LANGUAGE;

// تحميل ملف اللغة
$lang_file = "languages/{$current_language}.php";
if (!file_exists($lang_file)) {
    $lang_file = "languages/ar.php";
}
$lang = include $lang_file;

// دالة الترجمة
function t($key, $params = []) {
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
$text_direction = ($current_language == 'ar') ? 'rtl' : 'ltr';

// ============================================
// 3. نظام تسجيل الدخول
// ============================================

if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        $stmt = $conn->prepare("
            SELECT id, username, password, role, credits, full_name, status, language 
            FROM admin_users 
            WHERE username = ? AND status = 'active'
            LIMIT 1
        ");
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_username'] = $row['username'];
                $_SESSION['admin_role'] = $row['role'];
                $_SESSION['admin_credits'] = $row['credits'];
                $_SESSION['admin_name'] = $row['full_name'];
                $_SESSION['admin_language'] = $row['language'] ?? DEFAULT_LANGUAGE;
                
                // تحديث آخر دخول
                $update = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $row['id']);
                $update->execute();
                $update->close();
                
                header("Location: admin.php");
                exit;
            }
        }
        $login_error = t('invalid_credentials');
    }
    
    // عرض صفحة الدخول
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo $current_language; ?>" dir="<?php echo $text_direction; ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo t('app_name'); ?> - <?php echo t('login'); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root {
                --primary: #00e5ff;
                --primary-dark: #006aff;
                --dark: #060a12;
            }
            
            body {
                background: linear-gradient(135deg, var(--dark) 0%, #1a1a2e 100%);
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            
            .login-box {
                background: rgba(21, 25, 33, 0.95);
                padding: 40px;
                border-radius: 20px;
                border: 1px solid var(--primary);
                box-shadow: 0 20px 50px rgba(0, 229, 255, 0.2);
                width: 100%;
                max-width: 400px;
                backdrop-filter: blur(10px);
            }
            
            .logo {
                text-align: center;
                margin-bottom: 30px;
            }
            
            .logo-icon {
                font-size: 70px;
                color: var(--primary);
                margin-bottom: 20px;
            }
            
            .form-control {
                background: rgba(11, 14, 20, 0.9);
                border: 1px solid #333;
                color: white;
                padding: 12px 20px;
                border-radius: 12px;
                margin-bottom: 20px;
                transition: all 0.3s;
            }
            
            .form-control:focus {
                background: rgba(11, 14, 20, 1);
                border-color: var(--primary);
                color: white;
                box-shadow: 0 0 0 0.3rem rgba(0, 229, 255, 0.25);
            }
            
            .btn-login {
                background: linear-gradient(90deg, var(--primary), var(--primary-dark));
                border: none;
                color: black;
                font-weight: 800;
                padding: 15px;
                width: 100%;
                border-radius: 12px;
                font-size: 18px;
                transition: all 0.3s;
                margin-top: 10px;
            }
            
            .btn-login:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 20px rgba(0, 229, 255, 0.4);
            }
            
            .language-switcher {
                position: absolute;
                top: 20px;
                <?php echo $text_direction == 'rtl' ? 'left' : 'right'; ?>: 20px;
            }
        </style>
    </head>
    <body>
        <div class="language-switcher">
            <div class="btn-group">
                <a href="?lang=ar" class="btn btn-sm btn-outline-info <?php echo $current_language == 'ar' ? 'active' : ''; ?>">🇸🇦</a>
                <a href="?lang=tr" class="btn btn-sm btn-outline-info <?php echo $current_language == 'tr' ? 'active' : ''; ?>">🇹🇷</a>
                <a href="?lang=en" class="btn btn-sm btn-outline-info <?php echo $current_language == 'en' ? 'active' : ''; ?>">🇬🇧</a>
            </div>
        </div>
        
        <div class="login-box">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-satellite-dish"></i>
                </div>
                <h1 style="color: var(--primary); font-weight: 900;"><?php echo t('app_name'); ?></h1>
                <p style="color: #aaa; font-size: 14px;"><?php echo t('login_to_dashboard'); ?></p>
            </div>
            
            <?php if (isset($login_error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <input type="text" 
                           name="username" 
                           class="form-control" 
                           placeholder="<?php echo t('username'); ?>" 
                           required>
                </div>
                
                <div class="mb-3">
                    <input type="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="<?php echo t('password'); ?>" 
                           required>
                </div>
                
                <button type="submit" 
                        name="login" 
                        class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    <?php echo t('login'); ?>
                </button>
            </form>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// 4. تسجيل الخروج
// ============================================

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// ============================================
// 5. جلب بيانات المدير الحالي
// ============================================

$admin_id = $_SESSION['admin_id'];
$admin_role = $_SESSION['admin_role'];
$is_admin = ($admin_role === 'admin');

// جلب بيانات المدير بالكامل
$admin_query = $conn->prepare("
    SELECT *, 
           (SELECT COUNT(*) FROM subscribers WHERE created_by = admin_users.id) as total_subscribers,
           (SELECT COUNT(*) FROM subscribers WHERE created_by = admin_users.id AND expiry_date > NOW() AND status = 'active') as active_subscribers,
           (SELECT COUNT(*) FROM subscribers WHERE created_by = admin_users.id AND (expiry_date < NOW() OR status = 'expired')) as expired_subscribers
    FROM admin_users 
    WHERE id = ? AND status = 'active'
");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc();
$admin_query->close();

// ============================================
// 6. دوال المساعدة المحدثة
// ============================================

/**
 * توليد كود تفعيل
 */
function generateActivationCode($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * حساب تاريخ الانتهاء
 */
function calculateExpiryDate($plan_type, $is_trial = false) {
    $date = new DateTime();
    
    if ($is_trial) {
        $date->modify('+24 hours');
        return $date->format('Y-m-d H:i:s');
    }
    
    switch ($plan_type) {
        case '1month': $date->modify('+1 month'); break;
        case '3months': $date->modify('+3 months'); break;
        case '6months': $date->modify('+6 months'); break;
        case '1year': $date->modify('+1 year'); break;
        default: $date->modify('+1 month');
    }
    
    return $date->format('Y-m-d H:i:s');
}

/**
 * سعر الباقة
 */
function getPlanPrice($plan_type, $is_admin = false) {
    $prices = [
        'trial' => ['admin' => 0.00, 'reseller' => 0.20],
        '1month' => ['admin' => 2.00, 'reseller' => 3.00],
        '3months' => ['admin' => 3.00, 'reseller' => 5.00],
        '6months' => ['admin' => 5.00, 'reseller' => 7.00],
        '1year' => ['admin' => 7.00, 'reseller' => 10.00]
    ];
    
    return $prices[$plan_type][$is_admin ? 'admin' : 'reseller'] ?? 0;
}

/**
 * التحقق من إمكانية إنشاء حساب تجريبي
 */
function canCreateTrial($admin_data) {
    if ($admin_data['trial_credits'] <= 0) {
        return ['can' => false, 'message' => t('no_trial_credits')];
    }
    
    // التحقق من الحد اليومي
    if ($admin_data['last_trial_reset'] != date('Y-m-d')) {
        // إعادة تعيين العد اليومي
        $conn = $GLOBALS['conn'];
        $reset = $conn->prepare("UPDATE admin_users SET trials_created_today = 0, last_trial_reset = CURDATE() WHERE id = ?");
        $reset->bind_param("i", $admin_data['id']);
        $reset->execute();
        $admin_data['trials_created_today'] = 0;
    }
    
    if ($admin_data['trials_created_today'] >= $admin_data['max_trials_per_day']) {
        return ['can' => false, 'message' => t('daily_trial_limit_reached')];
    }
    
    return ['can' => true, 'message' => ''];
}

// ============================================
// 7. معالجة البروفايل
// ============================================

// 7.0.1 تحديث البروفايل
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $language = $_POST['language'] ?? 'ar';
    
    $stmt = $conn->prepare("
        UPDATE admin_users 
        SET full_name = ?, email = ?, phone = ?, language = ? 
        WHERE id = ?
    ");
    
    $stmt->bind_param("ssssi", $full_name, $email, $phone, $language, $admin_id);
    
    if ($stmt->execute()) {
        $_SESSION['admin_name'] = $full_name;
        $_SESSION['admin_language'] = $language;
        $admin['full_name'] = $full_name;
        $admin['email'] = $email;
        $admin['phone'] = $phone;
        $admin['language'] = $language;
        
        $message = t('profile_updated_success');
        $message_type = "success";
    } else {
        $message = t('profile_updated_error');
        $message_type = "error";
    }
    $stmt->close();
}

// 7.0.2 تغيير كلمة المرور
if (isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // التحقق من كلمة المرور الحالية
    $check = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
    $check->bind_param("i", $admin_id);
    $check->execute();
    $result = $check->get_result();
    $user = $result->fetch_assoc();
    $check->close();
    
    if (!password_verify($current_password, $user['password'])) {
        $message = t('current_password_incorrect');
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = t('passwords_not_match');
        $message_type = "error";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $admin_id);
        
        if ($stmt->execute()) {
            $message = t('password_changed_success');
            $message_type = "success";
        } else {
            $message = t('password_changed_error');
            $message_type = "error";
        }
        $stmt->close();
    }
}

// 7.0.3 رفع صورة البروفايل
if (isset($_POST['update_avatar']) && isset($_FILES['avatar'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_type = $_FILES['avatar']['type'];
        $file_size = $_FILES['avatar']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $message = t('invalid_image_type');
            $message_type = "error";
        } elseif ($file_size > $max_size) {
            $message = t('image_too_large');
            $message_type = "error";
        } else {
            // إنشاء اسم فريد للصورة
            $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'avatar_' . $admin_id . '_' . time() . '.' . $file_extension;
            $upload_path = 'uploads/avatars/' . $new_filename;
            
            // إنشاء المجلد إذا لم يكن موجوداً
            if (!is_dir('uploads/avatars')) {
                mkdir('uploads/avatars', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                // تحديث قاعدة البيانات
                $stmt = $conn->prepare("UPDATE admin_users SET avatar = ? WHERE id = ?");
                $stmt->bind_param("si", $upload_path, $admin_id);
                
                if ($stmt->execute()) {
                    $admin['avatar'] = $upload_path;
                    $message = t('avatar_uploaded_success');
                    $message_type = "success";
                } else {
                    $message = t('avatar_uploaded_error');
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = t('upload_failed');
                $message_type = "error";
            }
        }
    }
}

// ============================================
// 8. معالجة العمليات
// ============================================

$message = '';
$message_type = '';

// 8.1 إنشاء حساب تجريبي
if (isset($_POST['create_trial'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $server_id = isset($_POST['server_id']) ? (int)$_POST['server_id'] : 1;
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // التحقق من إمكانية إنشاء حساب تجريبي
    $trial_check = canCreateTrial($admin);
    if (!$trial_check['can']) {
        $message = $trial_check['message'];
        $message_type = 'error';
    } else {
        $conn->begin_transaction();
        
        try {
            // توليد كود تفعيل
            $activation_code = generateActivationCode();
            $expiry_date = calculateExpiryDate('trial', true);
            
            // إضافة المشترك التجريبي
            $stmt = $conn->prepare("
                INSERT INTO subscribers 
                (full_name, username, password, activation_code, expiry_date, server_id, created_by, email, phone, is_trial) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)
            ");
            
            if (!$stmt) {
                throw new Exception("خطأ في قاعدة البيانات: " . $conn->error);
            }
            
            // هنا الحل: 9 حروف (sssssiiss) تقابل 9 متغيرات بالضبط
            $stmt->bind_param(
                "sssssiiss",
                $full_name,       // 1 (s)
                $username,        // 2 (s)
                $password,        // 3 (s)
                $activation_code, // 4 (s)
                $expiry_date,     // 5 (s)
                $server_id,       // 6 (i)
                $admin_id,        // 7 (i)
                $email,           // 8 (s)
                $phone            // 9 (s)
            );
            
            if (!$stmt->execute()) {
                throw new Exception("خطأ في إنشاء الحساب التجريبي: " . $stmt->error);
            }
            
            $subscriber_id = $conn->insert_id;
            $stmt->close();
            
            // تحديث رصيد الحسابات التجريبية
            $update_trials = $conn->prepare("
                UPDATE admin_users 
                SET trial_credits = trial_credits - 1,
                    trials_created_today = trials_created_today + 1
                WHERE id = ?
            ");
            $update_trials->bind_param("i", $admin_id);
            $update_trials->execute();
            $update_trials->close();
            
            $conn->commit();
            
            // تحديث بيانات المدير في الجلسة
            $admin['trial_credits']--;
            $admin['trials_created_today']++;
            
            $message = t('trial_created_success') . "<br>" .
                      "<strong>" . t('activation_code') . ":</strong> {$activation_code}<br>" .
                      "<strong>" . t('expiry_date') . ":</strong> {$expiry_date}";
            $message_type = "success";
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "❌ " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// 8.2 إضافة مشترك عادي
if (isset($_POST['add_subscriber']) && !isset($_POST['create_trial'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $plan_type = $_POST['plan_type'];
    $server_id = (int)$_POST['server_id'];
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // توليد كود تفعيل
    $activation_code = generateActivationCode();
    
    // حساب تاريخ الانتهاء
    $expiry_date = calculateExpiryDate($plan_type, false);
    
    // حساب التكلفة
    $cost = getPlanPrice($plan_type, $is_admin);
    
    // التحقق من الرصيد (للموزعين فقط)
    if (!$is_admin && $admin['credits'] < $cost) {
        $message = t('insufficient_balance') . ": {$cost} " . t('credits');
        $message_type = "error";
    } else {
        $conn->begin_transaction();
        
        try {
            // إضافة المشترك
            $stmt = $conn->prepare("
                INSERT INTO subscribers 
                (full_name, username, password, activation_code, expiry_date, server_id, created_by, email, phone, notes, is_trial) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE)
            ");
            
            // التصحيح هنا:
            // تم تعديل "sssssiissss" إلى "sssssiisss" (10 أحرف فقط لتطابق الـ 10 خانات)
            $stmt->bind_param(
                "sssssiisss", 
                $full_name,       // s
                $username,        // s
                $password,        // s
                $activation_code, // s
                $expiry_date,     // s
                $server_id,       // i (رقم)
                $created_by,      // i (رقم - هذا المتغير ضروري هنا)
                $email,           // s
                $phone,           // s
                $notes            // s
            );
            
            if (!$stmt->execute()) {
                throw new Exception(t('add_subscriber_error') . ": " . $stmt->error);
            }
            
            $subscriber_id = $conn->insert_id;
            $stmt->close();
            
            // خصم الرصيد من الموزع
            if (!$is_admin) {
                $update_credits = $conn->prepare("
                    UPDATE admin_users 
                    SET credits = credits - ? 
                    WHERE id = ?
                ");
                $update_credits->bind_param("di", $cost, $admin_id);
                
                if (!$update_credits->execute()) {
                    throw new Exception(t('deduct_balance_error'));
                }
                
                $update_credits->close();
                $admin['credits'] -= $cost;
            }
            
            $conn->commit();
            
            $message = t('subscriber_added_success') . "<br>" .
                      "<strong>" . t('activation_code') . ":</strong> {$activation_code}<br>" .
                      "<strong>" . t('expiry_date') . ":</strong> {$expiry_date}";
            $message_type = "success";
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "❌ " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// 8.3 حذف مشترك
if (isset($_GET['delete_subscriber'])) {
    $subscriber_id = (int)$_GET['delete_subscriber'];
    
    $check = $conn->prepare("
        SELECT username FROM subscribers 
        WHERE id = ? AND (created_by = ? OR ? = TRUE)
    ");
    $check->bind_param("iii", $subscriber_id, $admin_id, $is_admin);
    $check->execute();
    $subscriber = $check->get_result()->fetch_assoc();
    $check->close();
    
    if ($subscriber) {
        $delete = $conn->prepare("DELETE FROM subscribers WHERE id = ?");
        $delete->bind_param("i", $subscriber_id);
        
        if ($delete->execute()) {
            $message = t('subscriber_deleted_success');
            $message_type = "success";
        }
        $delete->close();
    }
}

// 8.4 إضافة موزع جديد
if (isset($_POST['add_reseller'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $email = trim($_POST['email'] ?? '');
    $credits = floatval($_POST['credits'] ?? 0);
    $trial_credits = intval($_POST['trial_credits'] ?? 0);
    $max_trials_per_day = intval($_POST['max_trials_per_day'] ?? 5);
    
    // التحقق من عدم وجود اسم مستخدم مكرر
    $check = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $message = t('username_exists');
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO admin_users 
            (full_name, username, password, email, credits, trial_credits, max_trials_per_day, role, status, language) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'reseller', 'active', ?)
        ");
        
        $stmt->bind_param(
            "ssssdiis",
            $full_name,
            $username,
            $password,
            $email,
            $credits,
            $trial_credits,
            $max_trials_per_day,
            $current_language
        );
        
        if ($stmt->execute()) {
            $message = t('reseller_added_success');
            $message_type = "success";
        } else {
            $message = t('reseller_added_error');
            $message_type = "error";
        }
        $stmt->close();
    }
    $check->close();
}

// 8.5 إضافة رصيد للموزع
if (isset($_POST['add_credit'])) {
    $reseller_id = intval($_POST['reseller_id']);
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description'] ?? '');
    
    $stmt = $conn->prepare("UPDATE admin_users SET credits = credits + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $reseller_id);
    
    if ($stmt->execute()) {
        // تسجيل العملية
        $log = $conn->prepare("
            INSERT INTO activity_logs (admin_id, action, details) 
            VALUES (?, 'إضافة رصيد', ?)
        ");
        $details = "تم إضافة رصيد بقيمة {$amount} للموزع ID: {$reseller_id} - {$description}";
        $log->bind_param("is", $admin_id, $details);
        $log->execute();
        $log->close();
        
        $message = t('credit_added_success');
        $message_type = "success";
    } else {
        $message = t('credit_added_error');
        $message_type = "error";
    }
    $stmt->close();
}

// 8.6 إضافة حسابات تجريبية للموزع
if (isset($_POST['add_trial_credits'])) {
    $reseller_id = intval($_POST['reseller_id']);
    $trial_credits = intval($_POST['trial_credits']);
    
    $stmt = $conn->prepare("UPDATE admin_users SET trial_credits = trial_credits + ? WHERE id = ?");
    $stmt->bind_param("ii", $trial_credits, $reseller_id);
    
    if ($stmt->execute()) {
        // تسجيل العملية
        $log = $conn->prepare("
            INSERT INTO activity_logs (admin_id, action, details) 
            VALUES (?, 'إضافة حسابات تجريبية', ?)
        ");
        $details = "تم إضافة {$trial_credits} حساب تجريبي للموزع ID: {$reseller_id}";
        $log->bind_param("is", $admin_id, $details);
        $log->execute();
        $log->close();
        
        $message = t('trial_credits_added_success');
        $message_type = "success";
    } else {
        $message = t('trial_credits_added_error');
        $message_type = "error";
    }
    $stmt->close();
}

// 8.7 إضافة سيرفر
if (isset($_POST['add_server'])) {
    $server_name = trim($_POST['server_name']);
    $server_url = trim($_POST['server_url']);
    $stream_port = intval($_POST['stream_port']);
    $m3u_url = trim($_POST['m3u_url']);
    $status = $_POST['status'] ?? 'active';
    
    $stmt = $conn->prepare("
        INSERT INTO iptv_servers (server_name, server_url, stream_port, m3u_url, status) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "ssiss",
        $server_name,
        $server_url,
        $stream_port,
        $m3u_url,
        $status
    );
    
    if ($stmt->execute()) {
        $message = t('server_added_success');
        $message_type = "success";
    } else {
        $message = t('server_added_error');
        $message_type = "error";
    }
    $stmt->close();
}

// 8.8 حذف سيرفر
if (isset($_GET['delete_server'])) {
    $server_id = intval($_GET['delete_server']);
    
    // التحقق من عدم وجود مشتركين مرتبطين
    $check = $conn->prepare("SELECT COUNT(*) as count FROM subscribers WHERE server_id = ?");
    $check->bind_param("i", $server_id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $check->close();
    
    if ($result['count'] > 0) {
        $message = t('server_has_subscribers');
        $message_type = "error";
    } else {
        $delete = $conn->prepare("DELETE FROM iptv_servers WHERE id = ?");
        $delete->bind_param("i", $server_id);
        
        if ($delete->execute()) {
            $message = t('server_deleted_success');
            $message_type = "success";
        } else {
            $message = t('server_deleted_error');
            $message_type = "error";
        }
        $delete->close();
    }
}

// ============================================
// 9. واجهة لوحة التحكم (HTML)
// ============================================
?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>" dir="<?php echo $text_direction; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('app_name'); ?> - <?php echo t('dashboard'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00e5ff;
            --primary-dark: #006aff;
            --dark: #060a12;
            --card: #151921;
            --success: #2ed573;
            --danger: #ff4757;
            --warning: #ffa502;
            --info: #3498db;
        }
        
        body {
            background: var(--dark);
            color: white;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .glass-card {
            background: var(--card);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s;
        }
        
        .glass-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card {
            text-align: center;
            padding: 25px 15px;
            border-radius: 15px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--card), #1a1f2e);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 900;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #aaa;
            font-weight: 500;
        }
        
        .btn-primary-custom {
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border: none;
            color: black;
            font-weight: 700;
            padding: 12px 25px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 229, 255, 0.3);
            color: black;
        }
        
        .nav-tabs .nav-link {
            color: #aaa;
            border: none;
            padding: 12px 25px;
            border-radius: 10px 10px 0 0;
            margin: 0 2px;
        }
        
        .nav-tabs .nav-link.active {
            background: var(--card);
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
        }
        
        .table-dark-custom {
            background: rgba(11, 14, 20, 0.8);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table-dark-custom th {
            background: var(--card);
            border-color: #333;
            padding: 15px;
            font-weight: 700;
        }
        
        .table-dark-custom td {
            border-color: #333;
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .badge-status {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .badge-active {
            background: rgba(46, 213, 115, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .badge-expired {
            background: rgba(255, 71, 87, 0.2);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        .badge-trial {
            background: rgba(255, 165, 2, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }
        
        .form-control-custom {
            background: rgba(11, 14, 20, 0.8);
            border: 1px solid #333;
            color: white;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control-custom:focus {
            background: rgba(11, 14, 20, 0.9);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 0 0 0.3rem rgba(0, 229, 255, 0.25);
        }
        
        .sidebar {
            background: var(--card);
            min-height: 100vh;
            <?php echo $text_direction == 'rtl' ? 'border-left' : 'border-right'; ?>: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-profile {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 32px;
            font-weight: bold;
            color: black;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-menu .nav-link {
            color: #aaa;
            padding: 12px 25px;
            margin: 5px 0;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .nav-menu .nav-link:hover,
        .nav-menu .nav-link.active {
            background: rgba(0, 229, 255, 0.1);
            color: var(--primary);
        }
        
        .nav-menu .nav-link i {
            width: 25px;
            text-align: center;
            <?php echo $text_direction == 'rtl' ? 'margin-left' : 'margin-right'; ?>: 10px;
        }
        
        .language-switcher {
            margin-top: 20px;
            padding: 0 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
        }
        
        /* إشعارات */
        #notification-container {
            position: fixed;
            bottom: 20px;
            <?php echo $text_direction == 'rtl' ? 'left' : 'right'; ?>: 20px;
            z-index: 9999;
        }
        
        .notification {
            background: var(--primary);
            color: white;
            padding: 15px 20px;
            margin-top: 10px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            max-width: 400px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(100px); opacity: 0; }
        }
        
        /* صورة بروفايل كبيرة */
        .user-avatar-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: black;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <!-- نافذة الإشعارات -->
    <div id="notification-container">
        <!-- سيتم إضافة الإشعارات هنا -->
    </div>

    <!-- الرسائل العارضة -->
    <?php if (!empty($message)): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- مودال إضافة موزع -->
    <div class="modal fade" id="addResellerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: var(--card); color: white;">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        <?php echo t('add_reseller'); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('full_name'); ?> *</label>
                            <input type="text" name="full_name" class="form-control form-control-custom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('username'); ?> *</label>
                            <input type="text" name="username" class="form-control form-control-custom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('password'); ?> *</label>
                            <input type="password" name="password" class="form-control form-control-custom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('email'); ?></label>
                            <input type="email" name="email" class="form-control form-control-custom">
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo t('initial_balance'); ?></label>
                                    <input type="number" name="credits" class="form-control form-control-custom" value="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo t('trial_credits'); ?></label>
                                    <input type="number" name="trial_credits" class="form-control form-control-custom" value="5">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo t('max_trials_per_day'); ?></label>
                                    <input type="number" name="max_trials_per_day" class="form-control form-control-custom" value="5">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                        <button type="submit" name="add_reseller" class="btn btn-primary-custom"><?php echo t('add'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال إضافة رصيد -->
    <div class="modal fade" id="addCreditModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: var(--card); color: white;">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-coins me-2"></i>
                        <?php echo t('add_credit'); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="reseller_id" id="reseller_id_credit">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('reseller_name'); ?></label>
                            <input type="text" id="reseller_name_credit" class="form-control form-control-custom" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('amount'); ?> *</label>
                            <input type="number" name="amount" class="form-control form-control-custom" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('description'); ?></label>
                            <textarea name="description" class="form-control form-control-custom" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                        <button type="submit" name="add_credit" class="btn btn-success"><?php echo t('add'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال إضافة حسابات تجريبية -->
    <div class="modal fade" id="addTrialModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: var(--card); color: white;">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-vial me-2"></i>
                        <?php echo t('add_trial_credits'); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="reseller_id" id="reseller_id_trial">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('reseller_name'); ?></label>
                            <input type="text" id="reseller_name_trial" class="form-control form-control-custom" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('trial_credits'); ?> *</label>
                            <input type="number" name="trial_credits" class="form-control form-control-custom" value="5" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                        <button type="submit" name="add_trial_credits" class="btn btn-warning"><?php echo t('add'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال إضافة سيرفر -->
    <div class="modal fade" id="addServerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: var(--card); color: white;">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-server me-2"></i>
                        <?php echo t('add_server'); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('server_name'); ?> *</label>
                            <input type="text" name="server_name" class="form-control form-control-custom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('server_url'); ?> *</label>
                            <input type="text" name="server_url" class="form-control form-control-custom" placeholder="http://example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('stream_port'); ?> *</label>
                            <input type="number" name="stream_port" class="form-control form-control-custom" value="8080" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('m3u_url'); ?> *</label>
                            <input type="text" name="m3u_url" class="form-control form-control-custom" placeholder="http://example.com/get.php?username={username}&password={password}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('status'); ?></label>
                            <select name="status" class="form-select form-control-custom">
                                <option value="active"><?php echo t('active'); ?></option>
                                <option value="inactive"><?php echo t('inactive'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                        <button type="submit" name="add_server" class="btn btn-primary-custom"><?php echo t('add'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            
            <!-- الشريط الجانبي -->
            <div class="col-lg-2 col-md-3 sidebar">
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php 
                        if (isset($admin['avatar']) && !empty($admin['avatar'])) {
                            echo '<img src="' . htmlspecialchars($admin['avatar']) . '" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">';
                        } else {
                            echo strtoupper(substr($admin['full_name'] ?: $admin['username'], 0, 1));
                        }
                        ?>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($admin['full_name'] ?: $admin['username']); ?></h5>
                    <p class="text-secondary mb-2">
                        <span class="badge bg-<?php echo $is_admin ? 'info' : 'secondary'; ?>">
                            <?php echo $is_admin ? t('admin') : t('reseller'); ?>
                        </span>
                    </p>
                    
                    <!-- إحصائيات الموزع -->
                    <?php if (!$is_admin): ?>
                    <div class="mt-3">
                        <div class="text-primary fw-bold fs-4">
                            <?php echo number_format($admin['credits'], 2); ?>
                        </div>
                        <small class="text-secondary"><?php echo t('your_balance'); ?></small>
                    </div>
                    
                    <div class="mt-2">
                        <div class="text-warning fw-bold fs-5">
                            <?php echo $admin['trial_credits']; ?>
                        </div>
                        <small class="text-secondary"><?php echo t('trial_credits_remaining'); ?></small>
                    </div>
                    
                    <div class="mt-2">
                        <div class="text-info fw-bold">
                            <?php echo $admin['trials_created_today']; ?> / <?php echo $admin['max_trials_per_day']; ?>
                        </div>
                        <small class="text-secondary"><?php echo t('trials_today'); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="nav-menu">
                    <a href="#dashboard" class="nav-link active" data-bs-toggle="tab">
                        <i class="fas fa-tachometer-alt"></i> <?php echo t('menu_dashboard'); ?>
                    </a>
                    
                    <a href="#add-subscriber" class="nav-link" data-bs-toggle="tab">
                        <i class="fas fa-user-plus"></i> <?php echo t('menu_add_subscriber'); ?>
                    </a>
                    
                    <?php if ($is_admin): ?>
                    <a href="#resellers" class="nav-link" data-bs-toggle="tab">
                        <i class="fas fa-user-tie"></i> <?php echo t('menu_resellers'); ?>
                    </a>
                    
                    <a href="#servers" class="nav-link" data-bs-toggle="tab">
                        <i class="fas fa-server"></i> <?php echo t('menu_servers'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <a href="#subscribers" class="nav-link" data-bs-toggle="tab">
                        <i class="fas fa-users"></i> <?php echo t('menu_subscribers'); ?>
                    </a>
                    
                    <a href="#trials" class="nav-link" data-bs-toggle="tab">
                        <i class="fas fa-vial"></i> <?php echo t('menu_trials'); ?>
                    </a>
                    
                    <?php if ($is_admin): ?>
                    <a href="#reports" class="nav-link" data-bs-toggle="tab">
                        <i class="fas fa-chart-bar"></i> <?php echo t('menu_reports'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <!-- تبويب البروفايل الجديد -->
                    <a href="#profile" class="nav-link" data-bs-toggle="tab">
                        <i class="fas fa-user-circle"></i> <?php echo t('menu_profile'); ?>
                    </a>
                    
                    <hr class="my-3" style="border-color: #444;">
                    
                    <!-- اللغة -->
                    <div class="language-switcher dropdown mt-3 px-3">
                        <button class="btn btn-outline-info w-100 dropdown-toggle d-flex align-items-center justify-content-center" 
                                type="button" 
                                data-bs-toggle="dropdown">
                            <i class="fas fa-language me-2"></i>
                            <span>
                                <?php 
                                $language_names = [
                                    'ar' => '🇸🇦 العربية',
                                    'tr' => '🇹🇷 Türkçe', 
                                    'en' => '🇬🇧 English'
                                ];
                                echo $language_names[$current_language] ?? '🇸🇦 العربية';
                                ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu w-100">
                            <li>
                                <a class="dropdown-item d-flex align-items-center <?php echo $current_language == 'ar' ? 'active' : ''; ?>" 
                                   href="?lang=ar">
                                    <span class="me-2">🇸🇦</span>
                                    <span>العربية</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center <?php echo $current_language == 'tr' ? 'active' : ''; ?>" 
                                   href="?lang=tr">
                                    <span class="me-2">🇹🇷</span>
                                    <span>Türkçe</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center <?php echo $current_language == 'en' ? 'active' : ''; ?>" 
                                   href="?lang=en">
                                    <span class="me-2">🇬🇧</span>
                                    <span>English</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="mt-3 px-3">
                        <a href="?logout=1" class="btn btn-danger w-100">
                            <i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- المحتوى الرئيسي -->
            <div class="col-lg-10 col-md-9">
                <div class="container-fluid py-4">
                    
                    <!-- العنوان -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="glass-card d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-1">
                                        <i class="fas fa-broadcast-tower text-primary me-2"></i>
                                        <?php echo t('app_name'); ?>
                                    </h2>
                                    <p class="text-secondary mb-0">
                                        <?php echo t('welcome'); ?>, <?php echo htmlspecialchars($admin['full_name'] ?: $admin['username']); ?>!
                                        <?php if (!$is_admin): ?>
                                            <span class="badge bg-secondary ms-2"><?php echo t('reseller'); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="text-<?php echo $text_direction == 'rtl' ? 'start' : 'end'; ?>">
                                    <div class="text-primary fs-5">
                                        <i class="fas fa-clock me-2"></i>
                                        <?php echo date('Y-m-d H:i:s'); ?>
                                    </div>
                                    <small class="text-secondary">
                                        v2.0.0
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- الإحصائيات (للأدمن فقط أو إحصائيات محدودة للموزع) -->
                    <div class="row mb-4">
                        <?php if ($is_admin): ?>
                            <!-- إحصائيات كاملة للأدمن -->
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-card">
                                    <div class="stat-icon text-primary">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stat-number"><?php echo $admin['total_subscribers']; ?></div>
                                    <div class="stat-label"><?php echo t('total_subscribers'); ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-card">
                                    <div class="stat-icon text-success">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="stat-number"><?php echo $admin['active_subscribers']; ?></div>
                                    <div class="stat-label"><?php echo t('active_subscribers'); ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-card">
                                    <div class="stat-icon text-danger">
                                        <i class="fas fa-user-times"></i>
                                    </div>
                                    <div class="stat-number"><?php echo $admin['expired_subscribers']; ?></div>
                                    <div class="stat-label"><?php echo t('expired_subscribers'); ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-card">
                                    <div class="stat-icon text-warning">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                    <div class="stat-number"><?php echo number_format($admin['credits'], 2); ?></div>
                                    <div class="stat-label"><?php echo t('your_balance'); ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- إحصائيات محدودة للموزع -->
                            <div class="col-md-4 col-sm-6">
                                <div class="stat-card">
                                    <div class="stat-icon text-primary">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stat-number"><?php echo $admin['total_subscribers']; ?></div>
                                    <div class="stat-label"><?php echo t('total_subscribers'); ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 col-sm-6">
                                <div class="stat-card">
                                    <div class="stat-icon text-success">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="stat-number"><?php echo $admin['active_subscribers']; ?></div>
                                    <div class="stat-label"><?php echo t('active_subscribers'); ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 col-sm-6">
                                <div class="stat-card">
                                    <div class="stat-icon text-warning">
                                        <i class="fas fa-vial"></i>
                                    </div>
                                    <div class="stat-number"><?php echo $admin['trial_credits']; ?></div>
                                    <div class="stat-label"><?php echo t('trial_credits_remaining'); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- التبويبات -->
                    <div class="row">
                        <div class="col-12">
                            <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button">
                                        <i class="fas fa-tachometer-alt me-2"></i> <?php echo t('menu_dashboard'); ?>
                                    </button>
                                </li>
                                
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="add-subscriber-tab" data-bs-toggle="tab" data-bs-target="#add-subscriber" type="button">
                                        <i class="fas fa-user-plus me-2"></i> <?php echo t('menu_add_subscriber'); ?>
                                    </button>
                                </li>
                                
                                <?php if ($is_admin): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="resellers-tab" data-bs-toggle="tab" data-bs-target="#resellers" type="button">
                                        <i class="fas fa-user-tie me-2"></i> <?php echo t('menu_resellers'); ?>
                                    </button>
                                </li>
                                
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="servers-tab" data-bs-toggle="tab" data-bs-target="#servers" type="button">
                                        <i class="fas fa-server me-2"></i> <?php echo t('menu_servers'); ?>
                                    </button>
                                </li>
                                <?php endif; ?>
                                
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="subscribers-tab" data-bs-toggle="tab" data-bs-target="#subscribers" type="button">
                                        <i class="fas fa-users me-2"></i> <?php echo t('menu_subscribers'); ?>
                                    </button>
                                </li>
                                
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="trials-tab" data-bs-toggle="tab" data-bs-target="#trials" type="button">
                                        <i class="fas fa-vial me-2"></i> <?php echo t('menu_trials'); ?>
                                    </button>
                                </li>
                                
                                <?php if ($is_admin): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button">
                                        <i class="fas fa-chart-bar me-2"></i> <?php echo t('menu_reports'); ?>
                                    </button>
                                </li>
                                <?php endif; ?>
                                
                                <!-- تبويب البروفايل الجديد -->
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button">
                                        <i class="fas fa-user-circle me-2"></i> <?php echo t('menu_profile'); ?>
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="adminTabsContent">
                                
                                <!-- تبويب الإحصائيات -->
                                <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="glass-card">
                                                <h5 class="mb-4">
                                                    <i class="fas fa-tachometer-alt text-primary me-2"></i>
                                                    <?php echo t('dashboard'); ?>
                                                </h5>
                                                
                                                <?php if ($is_admin): ?>
                                                <!-- لوحة الأدمن الكاملة -->
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6 class="text-secondary mb-3"><?php echo t('recent_activity'); ?></h6>
                                                        <div class="list-group">
                                                            <?php
                                                            $activities = $conn->query("
                                                                SELECT al.*, au.username 
                                                                FROM activity_logs al 
                                                                LEFT JOIN admin_users au ON al.admin_id = au.id 
                                                                ORDER BY al.created_at DESC 
                                                                LIMIT 5
                                                            ");
                                                            
                                                            while ($activity = $activities->fetch_assoc()):
                                                            ?>
                                                            <div class="list-group-item bg-transparent text-white border-secondary">
                                                                <div class="d-flex justify-content-between">
                                                                    <small class="text-primary">
                                                                        <i class="fas fa-user me-1"></i>
                                                                        <?php echo htmlspecialchars($activity['username'] ?? 'System'); ?>
                                                                    </small>
                                                                    <small class="text-secondary">
                                                                        <?php echo date('H:i', strtotime($activity['created_at'])); ?>
                                                                    </small>
                                                                </div>
                                                                <div class="mt-1">
                                                                    <?php echo htmlspecialchars($activity['action']); ?>
                                                                </div>
                                                            </div>
                                                            <?php endwhile; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <h6 class="text-secondary mb-3"><?php echo t('quick_actions'); ?></h6>
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <a href="#add-subscriber" class="btn btn-primary-custom w-100" data-bs-toggle="tab">
                                                                    <i class="fas fa-user-plus me-2"></i> <?php echo t('add_new_subscriber'); ?>
                                                                </a>
                                                            </div>
                                                            <div class="col-6">
                                                                <a href="#resellers" class="btn btn-info w-100" data-bs-toggle="tab">
                                                                    <i class="fas fa-user-tie me-2"></i> <?php echo t('manage_resellers'); ?>
                                                                </a>
                                                            </div>
                                                            <div class="col-6">
                                                                <a href="#servers" class="btn btn-success w-100" data-bs-toggle="tab">
                                                                    <i class="fas fa-server me-2"></i> <?php echo t('manage_servers'); ?>
                                                                </a>
                                                            </div>
                                                            <div class="col-6">
                                                                <a href="#reports" class="btn btn-warning w-100" data-bs-toggle="tab">
                                                                    <i class="fas fa-chart-bar me-2"></i> <?php echo t('view_reports'); ?>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                <!-- لوحة الموزع المبسطة -->
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            <?php echo t('welcome_reseller'); ?>
                                                        </div>
                                                        
                                                        <div class="row g-3">
                                                            <div class="col-md-4">
                                                                <div class="card bg-dark border-primary">
                                                                    <div class="card-body text-center">
                                                                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                                                                        <h5><?php echo t('add_subscriber'); ?></h5>
                                                                        <p class="text-secondary"><?php echo t('add_subscriber_desc'); ?></p>
                                                                        <a href="#add-subscriber" class="btn btn-primary-custom w-100" data-bs-toggle="tab">
                                                                            <?php echo t('add'); ?>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="col-md-4">
                                                                <div class="card bg-dark border-success">
                                                                    <div class="card-body text-center">
                                                                        <i class="fas fa-users fa-3x text-success mb-3"></i>
                                                                        <h5><?php echo t('view_subscribers'); ?></h5>
                                                                        <p class="text-secondary"><?php echo t('view_subscribers_desc'); ?></p>
                                                                        <a href="#subscribers" class="btn btn-success w-100" data-bs-toggle="tab">
                                                                            <?php echo t('view'); ?>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="col-md-4">
                                                                <div class="card bg-dark border-warning">
                                                                    <div class="card-body text-center">
                                                                        <i class="fas fa-vial fa-3x text-warning mb-3"></i>
                                                                        <h5><?php echo t('create_trial'); ?></h5>
                                                                        <p class="text-secondary"><?php echo t('create_trial_desc'); ?></p>
                                                                        <a href="#trials" class="btn btn-warning w-100" data-bs-toggle="tab">
                                                                            <?php echo t('create'); ?>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- تبويب إضافة مشترك -->
                                <div class="tab-pane fade" id="add-subscriber" role="tabpanel">
                                    <div class="glass-card">
                                        <h5 class="mb-4">
                                            <i class="fas fa-user-plus text-success me-2"></i>
                                            <?php echo t('add_new_subscriber'); ?>
                                        </h5>
                                        
                                        <form method="POST">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label"><?php echo t('full_name'); ?> *</label>
                                                    <input type="text" 
                                                           name="full_name" 
                                                           class="form-control form-control-custom" 
                                                           placeholder="<?php echo t('full_name'); ?>" 
                                                           required>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label class="form-label"><?php echo t('username'); ?> *</label>
                                                    <input type="text" 
                                                           name="username" 
                                                           class="form-control form-control-custom" 
                                                           placeholder="<?php echo t('username'); ?>" 
                                                           required>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label class="form-label"><?php echo t('password'); ?> *</label>
                                                    <input type="text" 
                                                           name="password" 
                                                           class="form-control form-control-custom" 
                                                           value="<?php echo bin2hex(random_bytes(4)); ?>"
                                                           required>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label class="form-label"><?php echo t('plan'); ?> *</label>
                                                    <select name="plan_type" class="form-select form-control-custom" required>
                                                        <option value="1month"><?php echo t('1month'); ?> - <?php echo getPlanPrice('1month', $is_admin); ?> <?php echo t('credits'); ?></option>
                                                        <option value="3months"><?php echo t('3months'); ?> - <?php echo getPlanPrice('3months', $is_admin); ?> <?php echo t('credits'); ?></option>
                                                        <option value="6months"><?php echo t('6months'); ?> - <?php echo getPlanPrice('6months', $is_admin); ?> <?php echo t('credits'); ?></option>
                                                        <option value="1year"><?php echo t('1year'); ?> - <?php echo getPlanPrice('1year', $is_admin); ?> <?php echo t('credits'); ?></option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label class="form-label"><?php echo t('server'); ?> *</label>
                                                    <select name="server_id" class="form-select form-control-custom" required>
                                                        <?php
                                                        $servers = $conn->query("SELECT * FROM iptv_servers WHERE status = 'active'");
                                                        while ($server = $servers->fetch_assoc()):
                                                        ?>
                                                        <option value="<?php echo $server['id']; ?>">
                                                            <?php echo htmlspecialchars($server['server_name']); ?>
                                                        </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <label class="form-label"><?php echo t('email'); ?></label>
                                                    <input type="email" 
                                                           name="email" 
                                                           class="form-control form-control-custom" 
                                                           placeholder="example@email.com">
                                                </div>
                                                
                                                <div class="col-md-8">
                                                    <label class="form-label"><?php echo t('notes'); ?></label>
                                                    <textarea name="notes" 
                                                              class="form-control form-control-custom" 
                                                              placeholder="<?php echo t('notes'); ?>..."
                                                              rows="1"></textarea>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <div class="d-grid">
                                                        <button type="submit" 
                                                                name="add_subscriber" 
                                                                class="btn btn-primary-custom btn-lg">
                                                            <i class="fas fa-plus-circle me-2"></i>
                                                            <?php echo t('add_subscriber'); ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if ($is_admin): ?>
                                <!-- تبويب الموزعين (للأدمن فقط) -->
                                <div class="tab-pane fade" id="resellers" role="tabpanel">
                                    <div class="glass-card">
                                        <h5 class="mb-4">
                                            <i class="fas fa-user-tie text-warning me-2"></i>
                                            <?php echo t('manage_resellers'); ?>
                                        </h5>
                                        
                                        <!-- نموذج إضافة موزع -->
                                        <div class="mb-4">
                                            <button class="btn btn-primary-custom mb-3" data-bs-toggle="modal" data-bs-target="#addResellerModal">
                                                <i class="fas fa-plus-circle me-2"></i> <?php echo t('add_reseller'); ?>
                                            </button>
                                            
                                            <div class="table-responsive">
                                                <table class="table table-dark-custom table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th><?php echo t('full_name'); ?></th>
                                                            <th><?php echo t('username'); ?></th>
                                                            <th><?php echo t('email'); ?></th>
                                                            <th><?php echo t('balance'); ?></th>
                                                            <th><?php echo t('trial_credits'); ?></th>
                                                            <th><?php echo t('max_trials_per_day'); ?></th>
                                                            <th><?php echo t('status'); ?></th>
                                                            <th><?php echo t('created_at'); ?></th>
                                                            <th><?php echo t('actions'); ?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $resellers = $conn->query("
                                                            SELECT *, 
                                                                (SELECT COUNT(*) FROM subscribers WHERE created_by = admin_users.id) as total_subscribers
                                                            FROM admin_users 
                                                            WHERE role = 'reseller' 
                                                            ORDER BY created_at DESC
                                                        ");
                                                        
                                                        $counter = 1;
                                                        while ($reseller = $resellers->fetch_assoc()):
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo htmlspecialchars($reseller['full_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($reseller['username']); ?></td>
                                                            <td><?php echo htmlspecialchars($reseller['email']); ?></td>
                                                            <td>
                                                                <span class="badge bg-info">
                                                                    <?php echo number_format($reseller['credits'], 2); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-warning">
                                                                    <?php echo $reseller['trial_credits']; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary">
                                                                    <?php echo $reseller['max_trials_per_day']; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge <?php echo $reseller['status'] == 'active' ? 'badge-active' : 'badge-expired'; ?>">
                                                                    <?php echo $reseller['status']; ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('Y-m-d', strtotime($reseller['created_at'])); ?></td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <!-- زر إضافة رصيد -->
                                                                    <button class="btn btn-outline-success" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#addCreditModal"
                                                                            data-reseller-id="<?php echo $reseller['id']; ?>"
                                                                            data-reseller-name="<?php echo htmlspecialchars($reseller['full_name']); ?>"
                                                                            onclick="setResellerCredit(this)">
                                                                        <i class="fas fa-coins"></i>
                                                                    </button>
                                                                    
                                                                    <!-- زر إضافة حسابات تجريبية -->
                                                                    <button class="btn btn-outline-warning" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#addTrialModal"
                                                                            data-reseller-id="<?php echo $reseller['id']; ?>"
                                                                            data-reseller-name="<?php echo htmlspecialchars($reseller['full_name']); ?>"
                                                                            onclick="setResellerTrial(this)">
                                                                        <i class="fas fa-vial"></i>
                                                                    </button>
                                                                    
                                                                    <!-- زر حذف -->
                                                                    <button class="btn btn-outline-danger" 
                                                                            onclick="deleteReseller(<?php echo $reseller['id']; ?>, '<?php echo addslashes($reseller['full_name']); ?>')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- تبويب السيرفرات (للأدمن فقط) -->
                                <div class="tab-pane fade" id="servers" role="tabpanel">
                                    <div class="glass-card">
                                        <h5 class="mb-4">
                                            <i class="fas fa-server text-info me-2"></i>
                                            <?php echo t('manage_servers'); ?>
                                        </h5>
                                        
                                        <!-- نموذج إضافة سيرفر -->
                                        <button class="btn btn-primary-custom mb-3" data-bs-toggle="modal" data-bs-target="#addServerModal">
                                            <i class="fas fa-plus-circle me-2"></i> <?php echo t('add_server'); ?>
                                        </button>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-dark-custom table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th><?php echo t('server_name'); ?></th>
                                                        <th><?php echo t('server_url'); ?></th>
                                                        <th><?php echo t('stream_port'); ?></th>
                                                        <th><?php echo t('m3u_url'); ?></th>
                                                        <th><?php echo t('status'); ?></th>
                                                        <th><?php echo t('created_at'); ?></th>
                                                        <th><?php echo t('actions'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $servers = $conn->query("SELECT * FROM iptv_servers ORDER BY created_at DESC");
                                                    $counter = 1;
                                                    while ($server = $servers->fetch_assoc()):
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $counter++; ?></td>
                                                        <td><?php echo htmlspecialchars($server['server_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($server['server_url']); ?></td>
                                                        <td><?php echo $server['stream_port']; ?></td>
                                                        <td><small><?php echo htmlspecialchars(substr($server['m3u_url'], 0, 50)) . '...'; ?></small></td>
                                                        <td>
                                                            <span class="badge <?php echo $server['status'] == 'active' ? 'badge-active' : 'badge-expired'; ?>">
                                                                <?php echo $server['status']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('Y-m-d', strtotime($server['created_at'])); ?></td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <button class="btn btn-outline-danger" 
                                                                        onclick="deleteServer(<?php echo $server['id']; ?>, '<?php echo addslashes($server['server_name']); ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- تبويب المشتركين -->
                                <div class="tab-pane fade" id="subscribers" role="tabpanel">
                                    <div class="glass-card">
                                        <h5 class="mb-4">
                                            <i class="fas fa-users text-primary me-2"></i>
                                            <?php echo t('view_subscribers'); ?>
                                            <?php if (!$is_admin): ?>
                                                <small class="text-secondary">(<?php echo t('your_subscribers_only'); ?>)</small>
                                            <?php endif; ?>
                                        </h5>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-dark-custom table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th><?php echo t('full_name'); ?></th>
                                                        <th><?php echo t('username'); ?></th>
                                                        <th><?php echo t('activation_code'); ?></th>
                                                        <th><?php echo t('plan'); ?></th>
                                                        <th><?php echo t('status'); ?></th>
                                                        <th><?php echo t('expiry_date'); ?></th>
                                                        <th><?php echo t('actions'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if ($is_admin) {
                                                        $sql = "SELECT s.* FROM subscribers s ORDER BY s.created_at DESC";
                                                    } else {
                                                        $sql = "SELECT s.* FROM subscribers s WHERE s.created_by = {$admin_id} ORDER BY s.created_at DESC";
                                                    }
                                                    
                                                    $subscribers = $conn->query($sql);
                                                    $counter = 1;
                                                    
                                                    while ($sub = $subscribers->fetch_assoc()):
                                                        $is_expired = strtotime($sub['expiry_date']) < time();
                                                        $status = $is_expired ? 'expired' : $sub['status'];
                                                        $is_trial = $sub['is_trial'];
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $counter++; ?></td>
                                                        <td><?php echo htmlspecialchars($sub['full_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($sub['username']); ?></td>
                                                        <td>
                                                            <span class="badge bg-dark">
                                                                <?php echo $sub['activation_code']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($is_trial): ?>
                                                                <span class="badge badge-trial"><?php echo t('trial'); ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary"><?php echo t('paid'); ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($status === 'active'): ?>
                                                                <span class="badge badge-active"><?php echo t('active'); ?></span>
                                                            <?php elseif ($status === 'expired'): ?>
                                                                <span class="badge badge-expired"><?php echo t('expired'); ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary"><?php echo t('suspended'); ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo date('Y-m-d', strtotime($sub['expiry_date'])); ?>
                                                            <br>
                                                            <small class="text-secondary">
                                                                <?php
                                                                $remaining = floor((strtotime($sub['expiry_date']) - time()) / (60 * 60 * 24));
                                                                echo $remaining > 0 ? "({$remaining} " . t('days') . ")" : "(" . t('expired') . ")";
                                                                ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <button class="btn btn-outline-info" 
                                                                        onclick="copyToClipboard('<?php echo $sub['activation_code']; ?>')"
                                                                        title="<?php echo t('copy_code'); ?>">
                                                                    <i class="fas fa-copy"></i>
                                                                </button>
                                                                <a href="?delete_subscriber=<?php echo $sub['id']; ?>" 
                                                                   class="btn btn-outline-danger"
                                                                   onclick="return confirm('<?php echo t('confirm_delete_subscriber'); ?>: <?php echo addslashes($sub['full_name']); ?>?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- تبويب الحسابات التجريبية -->
                                <div class="tab-pane fade" id="trials" role="tabpanel">
                                    <div class="glass-card">
                                        <h5 class="mb-4">
                                            <i class="fas fa-vial text-warning me-2"></i>
                                            <?php echo t('manage_trial_accounts'); ?>
                                        </h5>
                                        
                                        <!-- معلومات الحسابات التجريبية -->
                                        <?php if (!$is_admin): ?>
                                        <div class="row mb-4">
                                            <div class="col-md-4">
                                                <div class="card bg-dark border-warning">
                                                    <div class="card-body text-center">
                                                        <div class="fs-1 fw-bold text-warning">
                                                            <?php echo $admin['trial_credits']; ?>
                                                        </div>
                                                        <div class="text-secondary"><?php echo t('trial_credits_remaining'); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="card bg-dark border-info">
                                                    <div class="card-body text-center">
                                                        <div class="fs-1 fw-bold text-info">
                                                            <?php echo $admin['trials_created_today']; ?> / <?php echo $admin['max_trials_per_day']; ?>
                                                        </div>
                                                        <div class="text-secondary"><?php echo t('trials_today'); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="card bg-dark border-success">
                                                    <div class="card-body text-center">
                                                        <div class="fs-1 fw-bold text-success">
                                                            24 <?php echo t('hours'); ?>
                                                        </div>
                                                        <div class="text-secondary"><?php echo t('trial_duration'); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- نموذج إنشاء حساب تجريبي -->
                                        <div class="mb-4">
                                            <h6 class="text-info mb-3">
                                                <i class="fas fa-plus-circle me-2"></i>
                                                <?php echo t('create_trial'); ?>
                                            </h6>
                                            
                                            <form method="POST">
                                                <div class="row g-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label"><?php echo t('full_name'); ?> *</label>
                                                        <input type="text" 
                                                               name="full_name" 
                                                               class="form-control form-control-custom" 
                                                               placeholder="<?php echo t('full_name'); ?>" 
                                                               required>
                                                    </div>
                                                    
                                                    <div class="col-md-3">
                                                        <label class="form-label"><?php echo t('username'); ?> *</label>
                                                        <input type="text" 
                                                               name="username" 
                                                               class="form-control form-control-custom" 
                                                               placeholder="<?php echo t('username'); ?>" 
                                                               required>
                                                    </div>
                                                    
                                                    <div class="col-md-3">
                                                        <label class="form-label"><?php echo t('password'); ?> *</label>
                                                        <input type="text" 
                                                               name="password" 
                                                               class="form-control form-control-custom" 
                                                               value="<?php echo bin2hex(random_bytes(4)); ?>"
                                                               required>
                                                    </div>
                                                    
                                                    <div class="col-md-3">
                                                        <label class="form-label"><?php echo t('server'); ?> *</label>
                                                        <select name="server_id" class="form-select form-control-custom" required>
                                                            <?php
                                                            $servers = $conn->query("SELECT * FROM iptv_servers WHERE status = 'active'");
                                                            while ($server = $servers->fetch_assoc()):
                                                            ?>
                                                            <option value="<?php echo $server['id']; ?>">
                                                                <?php echo htmlspecialchars($server['server_name']); ?>
                                                            </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label"><?php echo t('email'); ?></label>
                                                        <input type="email" 
                                                               name="email" 
                                                               class="form-control form-control-custom" 
                                                               placeholder="example@email.com">
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <label class="form-label"><?php echo t('phone'); ?></label>
                                                        <input type="tel" 
                                                               name="phone" 
                                                               class="form-control form-control-custom" 
                                                               placeholder="05XXXXXXXX">
                                                    </div>
                                                    
                                                    <div class="col-12">
                                                        <div class="d-grid">
                                                            <button type="submit" 
                                                                    name="create_trial" 
                                                                    class="btn btn-warning btn-lg"
                                                                    <?php echo (!$is_admin && $admin['trial_credits'] <= 0) ? 'disabled' : ''; ?>>
                                                                <i class="fas fa-vial me-2"></i>
                                                                <?php echo t('create_trial'); ?>
                                                                <?php if (!$is_admin): ?>
                                                                    (<?php echo $admin['trial_credits']; ?> <?php echo t('remaining'); ?>)
                                                                <?php endif; ?>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <!-- قائمة الحسابات التجريبية -->
                                        <div class="mt-4">
                                            <h6 class="text-info mb-3">
                                                <i class="fas fa-list me-2"></i>
                                                <?php echo t('trial_accounts_list'); ?>
                                            </h6>
                                            
                                            <div class="table-responsive">
                                                <table class="table table-dark-custom table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th><?php echo t('full_name'); ?></th>
                                                            <th><?php echo t('username'); ?></th>
                                                            <th><?php echo t('activation_code'); ?></th>
                                                            <th><?php echo t('expiry_date'); ?></th>
                                                            <th><?php echo t('trial_used'); ?></th>
                                                            <th><?php echo t('status'); ?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        if ($is_admin) {
                                                            $sql = "SELECT * FROM subscribers WHERE is_trial = TRUE ORDER BY created_at DESC";
                                                        } else {
                                                            $sql = "SELECT * FROM subscribers WHERE is_trial = TRUE AND created_by = {$admin_id} ORDER BY created_at DESC";
                                                        }
                                                        
                                                        $trials = $conn->query($sql);
                                                        $counter = 1;
                                                        
                                                        while ($trial = $trials->fetch_assoc()):
                                                            $is_expired = strtotime($trial['expiry_date']) < time();
                                                            $status = $is_expired ? 'expired' : 'active';
                                                            $trial_used = $trial['trial_used'] ? t('trial_used') : t('trial_not_used');
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo htmlspecialchars($trial['full_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($trial['username']); ?></td>
                                                            <td>
                                                                <span class="badge bg-dark">
                                                                    <?php echo $trial['activation_code']; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php echo date('Y-m-d H:i', strtotime($trial['expiry_date'])); ?>
                                                                <br>
                                                                <small class="text-warning">
                                                                    <?php
                                                                    $remaining = floor((strtotime($trial['expiry_date']) - time()) / (60 * 60));
                                                                    echo $remaining > 0 ? "({$remaining} " . t('hours') . ")" : "(" . t('expired') . ")";
                                                                    ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <?php if ($trial['trial_used']): ?>
                                                                    <span class="badge bg-success"><?php echo $trial_used; ?></span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary"><?php echo $trial_used; ?></span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($status === 'active'): ?>
                                                                    <span class="badge badge-active"><?php echo t('active'); ?></span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-expired"><?php echo t('expired'); ?></span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($is_admin): ?>
                                <!-- تبويب التقارير (للأدمن فقط) -->
                                <div class="tab-pane fade" id="reports" role="tabpanel">
                                    <div class="glass-card">
                                        <h5 class="mb-4">
                                            <i class="fas fa-chart-bar text-success me-2"></i>
                                            <?php echo t('view_reports'); ?>
                                        </h5>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card bg-dark border-success mb-3">
                                                    <div class="card-body">
                                                        <h6 class="text-success mb-3">
                                                            <i class="fas fa-chart-line me-2"></i>
                                                            <?php echo t('monthly_report'); ?>
                                                        </h6>
                                                        <?php
                                                        $monthly = $conn->query("
                                                            SELECT 
                                                                DATE_FORMAT(created_at, '%Y-%m') as month,
                                                                COUNT(*) as subscribers,
                                                                SUM(CASE WHEN is_trial = TRUE THEN 1 ELSE 0 END) as trials
                                                            FROM subscribers
                                                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                                                            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                                                            ORDER BY month DESC
                                                        ");
                                                        ?>
                                                        <table class="table table-dark table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th><?php echo t('month'); ?></th>
                                                                    <th><?php echo t('subscribers'); ?></th>
                                                                    <th><?php echo t('trials'); ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php while ($row = $monthly->fetch_assoc()): ?>
                                                                <tr>
                                                                    <td><?php echo $row['month']; ?></td>
                                                                    <td><?php echo $row['subscribers']; ?></td>
                                                                    <td><?php echo $row['trials']; ?></td>
                                                                </tr>
                                                                <?php endwhile; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="card bg-dark border-info mb-3">
                                                    <div class="card-body">
                                                        <h6 class="text-info mb-3">
                                                            <i class="fas fa-user-tie me-2"></i>
                                                            <?php echo t('resellers_report'); ?>
                                                        </h6>
                                                        <?php
                                                        $resellers_report = $conn->query("
                                                            SELECT 
                                                                au.full_name,
                                                                au.username,
                                                                COUNT(s.id) as total_subscribers,
                                                                SUM(CASE WHEN s.expiry_date > NOW() AND s.status = 'active' THEN 1 ELSE 0 END) as active_subscribers
                                                            FROM admin_users au
                                                            LEFT JOIN subscribers s ON au.id = s.created_by
                                                            WHERE au.role = 'reseller'
                                                            GROUP BY au.id
                                                            ORDER BY total_subscribers DESC
                                                        ");
                                                        ?>
                                                        <table class="table table-dark table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th><?php echo t('reseller'); ?></th>
                                                                    <th><?php echo t('total'); ?></th>
                                                                    <th><?php echo t('active'); ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php while ($row = $resellers_report->fetch_assoc()): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                                    <td><?php echo $row['total_subscribers']; ?></td>
                                                                    <td><?php echo $row['active_subscribers']; ?></td>
                                                                </tr>
                                                                <?php endwhile; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- تبويب البروفايل -->
                                <div class="tab-pane fade" id="profile" role="tabpanel">
                                    <div class="glass-card">
                                        <h5 class="mb-4">
                                            <i class="fas fa-user-circle text-primary me-2"></i>
                                            <?php echo t('menu_profile'); ?>
                                        </h5>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <!-- صورة البروفايل -->
                                                <div class="card bg-dark border-primary mb-4">
                                                    <div class="card-body text-center">
                                                        <div class="user-avatar-large mb-3">
                                                            <?php 
                                                            $initials = strtoupper(substr($admin['full_name'] ?: $admin['username'], 0, 1));
                                                            if (isset($admin['avatar']) && !empty($admin['avatar'])) {
                                                                echo '<img src="' . htmlspecialchars($admin['avatar']) . '" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">';
                                                            } else {
                                                                echo '<div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 150px; height: 150px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: black; font-size: 48px; font-weight: bold;">';
                                                                echo $initials;
                                                                echo '</div>';
                                                            }
                                                            ?>
                                                        </div>
                                                        <h4><?php echo htmlspecialchars($admin['full_name'] ?: $admin['username']); ?></h4>
                                                        <p class="text-secondary">
                                                            <span class="badge bg-<?php echo $is_admin ? 'info' : 'secondary'; ?>">
                                                                <?php echo $is_admin ? t('admin') : t('reseller'); ?>
                                                            </span>
                                                        </p>
                                                        <p class="text-secondary">
                                                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($admin['username']); ?>
                                                        </p>
                                                        <?php if (!empty($admin['email'])): ?>
                                                        <p class="text-secondary">
                                                            <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($admin['email']); ?>
                                                        </p>
                                                        <?php endif; ?>
                                                        <p class="text-secondary">
                                                            <i class="fas fa-calendar me-1"></i> <?php echo t('member_since'); ?>: <?php echo date('Y-m-d', strtotime($admin['created_at'])); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <!-- رفع صورة البروفايل -->
                                                <div class="card bg-dark border-info">
                                                    <div class="card-body">
                                                        <h6 class="text-info mb-3">
                                                            <i class="fas fa-camera me-2"></i>
                                                            <?php echo t('upload_avatar'); ?>
                                                        </h6>
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <div class="mb-3">
                                                                <input type="file" name="avatar" class="form-control form-control-custom" accept="image/*">
                                                                <small class="text-secondary"><?php echo t('max_size'); ?>: 2MB</small>
                                                            </div>
                                                            <button type="submit" name="update_avatar" class="btn btn-info w-100">
                                                                <i class="fas fa-upload me-2"></i>
                                                                <?php echo t('upload'); ?>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-8">
                                                <!-- معلومات الحساب -->
                                                <div class="card bg-dark border-success mb-4">
                                                    <div class="card-header border-success">
                                                        <h6 class="mb-0">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            <?php echo t('account_information'); ?>
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <form method="POST">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label"><?php echo t('full_name'); ?></label>
                                                                    <input type="text" 
                                                                           name="full_name" 
                                                                           class="form-control form-control-custom" 
                                                                           value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>">
                                                                </div>
                                                                
                                                                <div class="col-md-6">
                                                                    <label class="form-label"><?php echo t('email'); ?></label>
                                                                    <input type="email" 
                                                                           name="email" 
                                                                           class="form-control form-control-custom" 
                                                                           value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>">
                                                                </div>
                                                                
                                                                <div class="col-md-6">
                                                                    <label class="form-label"><?php echo t('phone'); ?></label>
                                                                    <input type="tel" 
                                                                           name="phone" 
                                                                           class="form-control form-control-custom" 
                                                                           value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                                                                </div>
                                                                
                                                                <div class="col-md-6">
                                                                    <label class="form-label"><?php echo t('language'); ?></label>
                                                                    <select name="language" class="form-select form-control-custom">
                                                                        <option value="ar" <?php echo ($admin['language'] ?? 'ar') == 'ar' ? 'selected' : ''; ?>>🇸🇦 العربية</option>
                                                                        <option value="tr" <?php echo ($admin['language'] ?? 'ar') == 'tr' ? 'selected' : ''; ?>>🇹🇷 Türkçe</option>
                                                                        <option value="en" <?php echo ($admin['language'] ?? 'ar') == 'en' ? 'selected' : ''; ?>>🇬🇧 English</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="col-12">
                                                                    <hr class="my-3">
                                                                    <button type="submit" name="update_profile" class="btn btn-success w-100">
                                                                        <i class="fas fa-save me-2"></i>
                                                                        <?php echo t('save_changes'); ?>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                                
                                                <!-- تغيير كلمة المرور -->
                                                <div class="card bg-dark border-warning mb-4">
                                                    <div class="card-header border-warning">
                                                        <h6 class="mb-0">
                                                            <i class="fas fa-lock me-2"></i>
                                                            <?php echo t('change_password'); ?>
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <form method="POST">
                                                            <div class="mb-3">
                                                                <label class="form-label"><?php echo t('current_password'); ?></label>
                                                                <input type="password" 
                                                                       name="current_password" 
                                                                       class="form-control form-control-custom" 
                                                                       required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label"><?php echo t('new_password'); ?></label>
                                                                <input type="password" 
                                                                       name="new_password" 
                                                                       class="form-control form-control-custom" 
                                                                       required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label"><?php echo t('confirm_password'); ?></label>
                                                                <input type="password" 
                                                                       name="confirm_password" 
                                                                       class="form-control form-control-custom" 
                                                                       required>
                                                            </div>
                                                            
                                                            <button type="submit" name="change_password" class="btn btn-warning w-100">
                                                                <i class="fas fa-key me-2"></i>
                                                                <?php echo t('change_password'); ?>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                                
                                                <!-- الإحصائيات الشخصية -->
                                                <?php if (!$is_admin): ?>
                                                <div class="card bg-dark border-primary">
                                                    <div class="card-header border-primary">
                                                        <h6 class="mb-0">
                                                            <i class="fas fa-chart-line me-2"></i>
                                                            <?php echo t('your_statistics'); ?>
                                                        </h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row text-center">
                                                            <div class="col-4">
                                                                <div class="text-primary fw-bold fs-3"><?php echo $admin['total_subscribers']; ?></div>
                                                                <small class="text-secondary"><?php echo t('total_subscribers'); ?></small>
                                                            </div>
                                                            <div class="col-4">
                                                                <div class="text-success fw-bold fs-3"><?php echo $admin['active_subscribers']; ?></div>
                                                                <small class="text-secondary"><?php echo t('active_subscribers'); ?></small>
                                                            </div>
                                                            <div class="col-4">
                                                                <div class="text-warning fw-bold fs-3"><?php echo $admin['trial_credits']; ?></div>
                                                                <small class="text-secondary"><?php echo t('trial_credits'); ?></small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- حقوق النشر -->
    <div id="copyright-footer" style="position: fixed; bottom: 10px; <?php echo $text_direction == 'rtl' ? 'left' : 'right'; ?>: 10px; z-index: 1000; cursor: pointer;" 
         onclick="toggleDeveloperInfo()">
        <div class="copyright-text" style="background: linear-gradient(90deg, #00e5ff, #006aff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-shadow: 0 0 10px rgba(0, 229, 255, 0.5); font-weight: 700;">
            <i class="fas fa-copyright"></i> 2024 MIRA TV PRO - <span class="developer-name"><?php echo t('copyright'); ?>: Süleyman Al-Ahmad</span>
            <br>
            <small style="font-size: 9px; opacity: 0.8;"><?php echo t('all_rights_reserved'); ?> | <?php echo t('version'); ?> 2.0.0</small>
        </div>
        
        <!-- معلومات المطور (مخفية) -->
        <div id="developer-info" style="display: none; position: absolute; bottom: 100%; <?php echo $text_direction == 'rtl' ? 'right' : 'left'; ?>: 0; background: rgba(0, 0, 0, 0.9); padding: 15px; border-radius: 10px; border: 1px solid var(--primary); min-width: 200px; margin-bottom: 10px; backdrop-filter: blur(10px);">
            <div class="text-center">
                <div style="font-size: 40px; margin-bottom: 10px;">
                    👨‍💻
                </div>
                <h6 style="color: var(--primary); font-weight: 700;">Süleyman Al-Ahmad</h6>
                <p style="color: #aaa; font-size: 12px; margin-bottom: 15px;">
                    <?php echo t('lead_developer'); ?> MIRA TV PRO
                </p>
                
                <!-- روابط التواصل -->
                <div class="d-flex justify-content-center gap-2">
                    <a href="https://facebook.com/suleyman.alahmad" 
                       target="_blank" 
                       class="btn btn-sm btn-primary d-flex align-items-center"
                       style="background: #1877F2; border: none;">
                        <i class="fab fa-facebook-f me-1"></i> Facebook
                    </a>
                    <a href="mailto:dev@suleyman.com" 
                       class="btn btn-sm btn-info d-flex align-items-center"
                       style="background: var(--primary); border: none; color: black;">
                        <i class="fas fa-envelope me-1"></i> Email
                    </a>
                </div>
                
                <!-- معلومات التواصل -->
                <div class="mt-3" style="border-top: 1px solid #444; padding-top: 10px;">
                    <small style="color: #888; font-size: 10px;">
                        <i class="fas fa-code me-1"></i> Full Stack Developer<br>
                        <i class="fas fa-map-marker-alt me-1"></i> Riyadh, Saudi Arabia
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // نسخ النص للحافظة
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification('تم النسخ: ' + text, 'success');
            });
        }
        
        // إدارة التبويبات
        document.addEventListener('DOMContentLoaded', function() {
            // تفعيل التنقل في الشريط الجانبي
            $('.nav-menu .nav-link').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                $('.nav-menu .nav-link').removeClass('active');
                $(this).addClass('active');
                
                // تفعيل التبويب المطلوب
                $('.nav-tabs button[data-bs-target="' + target + '"]').tab('show');
                
                // تحديث التبويب النشط في الناف بار
                $('.nav-tabs .nav-link').removeClass('active');
                $('.nav-tabs button[data-bs-target="' + target + '"]').addClass('active');
            });
            
            // حفظ التبويب النشط
            const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabs.forEach(tab => {
                tab.addEventListener('shown.bs.tab', function (e) {
                    localStorage.setItem('activeTab', e.target.getAttribute('data-bs-target'));
                });
            });
            
            // استعادة التبويب النشط
            const activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                const tabElement = document.querySelector(`button[data-bs-target="${activeTab}"]`);
                if (tabElement) {
                    new bootstrap.Tab(tabElement).show();
                    // تحديث الشريط الجانبي
                    $('.nav-menu .nav-link').removeClass('active');
                    $(`.nav-menu .nav-link[href="${activeTab}"]`).addClass('active');
                }
            }
            
            // تحديث الوقت
            function updateTime() {
                const now = new Date();
                const timeStr = now.toLocaleTimeString();
                document.querySelectorAll('.current-time').forEach(el => {
                    el.textContent = timeStr;
                });
            }
            setInterval(updateTime, 1000);
            updateTime();
        });
        
        // التحقق من نموذج الحساب التجريبي
        document.querySelector('form[method="POST"]')?.addEventListener('submit', function(e) {
            const trialBtn = this.querySelector('button[name="create_trial"]');
            if (trialBtn && trialBtn.disabled) {
                e.preventDefault();
                showNotification('<?php echo t('no_trial_credits'); ?>', 'error');
            }
        });
        
        // دالة لعرض الإشعارات
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notification-container');
            
            // ألوان حسب النوع
            const colors = {
                success: '#2ed573',
                error: '#ff4757',
                warning: '#ffa502',
                info: '#3498db'
            };
            
            // إنشاء الإشعار
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.style.cssText = `
                background: ${colors[type] || colors.info};
                color: white;
                padding: 15px 20px;
                margin-top: 10px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                max-width: 400px;
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="flex: 1;">
                        <i class="fas ${getNotificationIcon(type)} me-2"></i>
                        ${message}
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" 
                            style="background: transparent; border: none; color: white; cursor: pointer; margin-right: 10px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(notification);
            
            // إزالة تلقائية بعد 5 ثوان
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
            
            // تشغيل الصوت
            playSound(type);
        }
        
        // الحصول على الأيقونة المناسبة
        function getNotificationIcon(type) {
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            return icons[type] || 'fa-info-circle';
        }
        
        // تشغيل الصوت
        function playSound(type) {
            const audio = new Audio();
            
            // إنشاء أصوات باستخدام Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            let frequency;
            switch(type) {
                case 'success': frequency = 523.25; break; // C5
                case 'error': frequency = 349.23; break; // F4
                case 'warning': frequency = 392.00; break; // G4
                default: frequency = 440.00; // A4
            }
            
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = frequency;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        }
        
        // استبدال alert بـ showNotification
        window.alert = function(message) {
            showNotification(message, 'info');
        };
        
        // عرض رسائل PHP كإشعارات
        <?php if (!empty($message)): ?>
            setTimeout(() => {
                showNotification('<?php echo addslashes(strip_tags($message)); ?>', '<?php echo $message_type; ?>');
            }, 1000);
        <?php endif; ?>
        
        // وظائف إدارة الموزعين
        function setResellerCredit(button) {
            const resellerId = button.getAttribute('data-reseller-id');
            const resellerName = button.getAttribute('data-reseller-name');
            
            document.getElementById('reseller_id_credit').value = resellerId;
            document.getElementById('reseller_name_credit').value = resellerName;
        }
        
        function setResellerTrial(button) {
            const resellerId = button.getAttribute('data-reseller-id');
            const resellerName = button.getAttribute('data-reseller-name');
            
            document.getElementById('reseller_id_trial').value = resellerId;
            document.getElementById('reseller_name_trial').value = resellerName;
        }
        
        function deleteReseller(id, name) {
            if (confirm('هل أنت متأكد من حذف الموزع: ' + name + '؟')) {
                window.location.href = '?delete_reseller=' + id;
            }
        }
        
        function deleteServer(id, name) {
            if (confirm('هل أنت متأكد من حذف السيرفر: ' + name + '؟')) {
                window.location.href = '?delete_server=' + id;
            }
        }
        
        // إظهار/إخفاء معلومات المطور
        function toggleDeveloperInfo() {
            const info = document.getElementById('developer-info');
            const footer = document.getElementById('copyright-footer');
            
            if (info.style.display === 'none') {
                info.style.display = 'block';
                footer.style.zIndex = '9999';
            } else {
                info.style.display = 'none';
                footer.style.zIndex = '1000';
            }
        }
        
        // إغلاق عند النقر خارج الصندوق
        document.addEventListener('click', function(event) {
            const info = document.getElementById('developer-info');
            const footer = document.getElementById('copyright-footer');
            
            if (info.style.display === 'block' && 
                !footer.contains(event.target)) {
                info.style.display = 'none';
                footer.style.zIndex = '1000';
            }
        });
    </script>
</body>
</html>
<?php
// إغلاق اتصال قاعدة البيانات
$conn->close();
?>