-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 18, 2026 at 10:15 AM
-- Server version: 10.6.24-MariaDB-cll-lve
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tv`
--

DELIMITER $$
--
-- Procedures
--
$$

$$

$$

--
-- Functions
--
$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `details`, `created_at`) VALUES
(1, 3, 'إضافة رصيد', 'تم إضافة رصيد بقيمة 50 للموزع ID: 4 - ', '2026-01-31 16:04:54'),
(2, 3, 'إضافة رصيد', 'تم إضافة رصيد بقيمة 5000 للموزع ID: 4 - ', '2026-01-31 16:05:38'),
(3, 3, 'إضافة حسابات تجريبية', 'تم إضافة 50 حساب تجريبي للموزع ID: 4', '2026-01-31 16:10:17'),
(4, 3, 'إضافة حسابات تجريبية', 'تم إضافة 50 حساب تجريبي للموزع ID: 4', '2026-01-31 16:11:24');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `credits` decimal(10,2) DEFAULT 0.00,
  `trial_credits` int(11) DEFAULT 0,
  `max_trials_per_day` int(11) DEFAULT 5,
  `trials_created_today` int(11) DEFAULT 0,
  `last_trial_reset` date DEFAULT NULL,
  `role` enum('admin','reseller') DEFAULT 'reseller',
  `status` enum('active','suspended') DEFAULT 'active',
  `language` varchar(2) DEFAULT 'ar',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `full_name`, `username`, `password`, `email`, `phone`, `avatar`, `credits`, `trial_credits`, `max_trials_per_day`, `trials_created_today`, `last_trial_reset`, `role`, `status`, `language`, `last_login`, `created_at`, `updated_at`) VALUES
(3, 'مدير النظام', 'admin', '$2y$10$v8WMll/WjXytSHvw/M4McODqRN0LbsDiTUN49iaE5mfdD3L1okjlO', 'admin@miratv.com', '05389208046', 'uploads/avatars/avatar_3_1769793336.jpg', 1000.00, 100, 5, 0, '2026-01-30', 'admin', 'active', 'ar', '2026-02-18 01:22:41', '2026-01-30 17:04:16', '2026-02-17 22:22:41'),
(7, 'ui', 'u', '$2y$10$HwbCZ71tdoheBRnY4IHqwOY4vOLu9LlxraFWzuYhWof71QMPZhJ9q', 'company@example.com', NULL, NULL, 10.00, 5, 5, 0, NULL, 'reseller', 'active', 'ar', '2026-02-01 20:42:20', '2026-02-01 17:42:09', '2026-02-01 17:42:20'),
(8, 'MUHAMMED', 'RS1', '$2y$10$yoiD5WwdJV2OA9w.LFrTrOIaXamjTWguZLGQ3r1Q2r2NAWAxuOWYe', 'ma645258@gmail.com', NULL, NULL, 10.00, 100, 50, 0, '2026-02-05', 'reseller', 'active', 'ar', '2026-02-05 01:18:01', '2026-02-04 22:17:41', '2026-02-04 22:19:54');

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `template_key` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `language` varchar(2) DEFAULT 'ar',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `template_key`, `subject`, `content`, `variables`, `language`, `status`, `created_at`, `updated_at`) VALUES
(5, 'welcome_reseller', 'مرحباً بك في MIRA TV PRO', '<h1>مرحباً {full_name}</h1><p>تم إنشاء حسابك بنجاح في نظام MIRA TV PRO</p><p>بيانات الدخول:<br>اسم المستخدم: {username}<br>كلمة المرور: {password}</p><p>رابط الدخول: {login_url}</p>', '[\"full_name\", \"username\", \"password\", \"login_url\"]', 'ar', 'active', '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(6, 'new_subscriber', 'حساب جديد في MIRA TV PRO', '<h1>حساب جديد</h1><p>تم إنشاء حساب جديد للمشترك {full_name}</p><p>كود التفعيل: {activation_code}<br>تاريخ الانتهاء: {expiry_date}</p>', '[\"full_name\", \"activation_code\", \"expiry_date\"]', 'ar', 'active', '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(7, 'subscription_expiring', 'اشتراكك على وشك الانتهاء', '<h1>تنبيه انتهاء الاشتراك</h1><p>اشتراك المستخدم {username} سينتهي بعد {remaining_days} يوم</p><p>تاريخ الانتهاء: {expiry_date}</p>', '[\"username\", \"remaining_days\", \"expiry_date\"]', 'ar', 'active', '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(8, 'password_reset', 'إعادة تعيين كلمة المرور', '<h1>إعادة تعيين كلمة المرور</h1><p>لإعادة تعيين كلمة المرور، اضغط على الرابط التالي:</p><p><a href=\"{reset_link}\">{reset_link}</a></p><p>الرابط صالح لمدة ساعة واحدة</p>', '[\"reset_link\"]', 'ar', 'active', '2026-01-30 17:04:16', '2026-01-30 17:04:16');

-- --------------------------------------------------------

--
-- Table structure for table `iptv_servers`
--

CREATE TABLE `iptv_servers` (
  `id` int(11) NOT NULL,
  `server_name` varchar(100) NOT NULL,
  `server_url` varchar(255) NOT NULL,
  `stream_port` int(11) NOT NULL,
  `m3u_url` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `iptv_servers`
--

INSERT INTO `iptv_servers` (`id`, `server_name`, `server_url`, `stream_port`, `m3u_url`, `status`, `created_at`, `updated_at`) VALUES
(2, 'سيرفر MIRA TV', 'http://server.miratv.com', 8080, 'http://server.miratv.com/get.php?username={username}&password={password}&type=m3u', 'active', '2026-01-30 17:04:16', '2026-01-30 17:04:16');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('admin','subscriber') DEFAULT 'admin',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` int(11) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `plan_code` varchar(50) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `price_admin` decimal(10,2) NOT NULL,
  `price_reseller` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `status` enum('active','inactive') DEFAULT 'active',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `plan_name`, `plan_code`, `duration_days`, `price_admin`, `price_reseller`, `description`, `features`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(6, 'حساب تجريبي', 'trial', 1, 0.00, 0.20, 'حساب تجريبي لمدة 24 ساعة', '[\"عدد قنوات غير محدود\", \"جودة HD\", \"دعم جميع الأجهزة\"]', 'active', 0, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(7, 'شهر واحد', '1month', 30, 2.00, 3.00, 'اشتراك شهر واحد', '[\"عدد قنوات غير محدود\", \"جودة HD\", \"دعم جميع الأجهزة\", \"إعادة تشغيل\"]', 'active', 0, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(8, '3 أشهر', '3months', 90, 3.00, 5.00, 'اشتراك 3 أشهر', '[\"عدد قنوات غير محدود\", \"جودة HD\", \"دعم جميع الأجهزة\", \"إعادة تشغيل\", \"تسجيل\"]', 'active', 0, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(9, '6 أشهر', '6months', 180, 5.00, 7.00, 'اشتراك 6 أشهر', '[\"عدد قنوات غير محدود\", \"جودة HD\", \"دعم جميع الأجهزة\", \"إعادة تشغيل\", \"تسجيل\", \"دعم فني متميز\"]', 'active', 0, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(10, 'سنة كاملة', '1year', 365, 7.00, 10.00, 'اشتراك سنة كاملة', '[\"عدد قنوات غير محدود\", \"جودة HD\", \"دعم جميع الأجهزة\", \"إعادة تشغيل\", \"تسجيل\", \"دعم فني متميز\", \"ترقية مجانية\"]', 'active', 0, '2026-01-30 17:04:16', '2026-01-30 17:04:16');

-- --------------------------------------------------------

--
-- Table structure for table `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `activation_code` varchar(20) NOT NULL,
  `expiry_date` datetime NOT NULL,
  `server_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_trial` tinyint(1) DEFAULT 0,
  `trial_used` tinyint(1) DEFAULT 0,
  `status` enum('active','suspended','expired') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `total_logins` int(11) DEFAULT 0,
  `max_devices` int(11) DEFAULT 1,
  `device_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `subscribers`
--
DELIMITER $$
CREATE TRIGGER `after_subscriber_login` AFTER UPDATE ON `subscribers` FOR EACH ROW BEGIN
        IF NEW.last_login != OLD.last_login THEN
        -- تسجيل إحصائيات الاستخدام
    INSERT INTO usage_stats(
        subscriber_id,
        ACTION,
        ip_address,
        user_agent
    )
VALUES(NEW.id, 'login', NULL, NULL) ;
    END IF ;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_subscriber_insert` BEFORE INSERT ON `subscribers` FOR EACH ROW BEGIN
    DECLARE
        admin_credits DECIMAL(10, 2) ; DECLARE admin_role VARCHAR(20) ; DECLARE plan_price DECIMAL(10, 2) ;
        -- إذا كان ليس حساب تجريبي
        IF NEW.is_trial = FALSE THEN
        -- الحصول على بيانات المدير
    SELECT
        credits,
        role
    INTO admin_credits, admin_role
FROM
    admin_users
WHERE
    id = NEW.created_by ;
    -- الحصول على سعر الباقة
SELECT CASE WHEN
    admin_role = 'admin' THEN price_admin ELSE price_reseller
END
INTO plan_price
FROM
    plans
WHERE
    duration_days = CASE WHEN NEW.expiry_date IS NOT NULL THEN DATEDIFF(
        NEW.expiry_date,
        NEW.created_at
    ) ELSE 30
    END
LIMIT 1 ;
-- إذا كان موزع ولا يوجد رصيد كافي
IF admin_role = 'reseller' AND admin_credits < plan_price THEN SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT
    = 'رصيد غير كافي لإضافة مشترك جديد' ;
END IF ;
    END IF ;
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_subscribers_update` BEFORE UPDATE ON `subscribers` FOR EACH ROW BEGIN
    SET NEW
        .updated_at = NOW() ;
        -- إذا تغيرت حالة المشترك
        IF OLD.status != NEW.status THEN
    INSERT INTO activity_logs(admin_id, ACTION, details)
VALUES(
    NEW.created_by,
    'تغيير حالة المشترك',
    CONCAT(
        'تم تغيير حالة المشترك ',
        OLD.full_name,
        ' من ',
        OLD.status,
        ' إلى ',
        NEW.status
    )
) ;
        END IF ;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('admin','subscriber') DEFAULT 'subscriber',
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','float','boolean','json') DEFAULT 'string',
  `category` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(16, 'app_name', 'MIRA TV PRO', 'string', 'general', 'اسم التطبيق', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(17, 'app_version', '2.0.0', 'string', 'general', 'إصدار التطبيق', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(18, 'currency', 'USD', 'string', 'financial', 'العملة المستخدمة', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(19, 'default_language', 'ar', 'string', 'general', 'اللغة الافتراضية', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(20, 'timezone', 'Asia/Riyadh', 'string', 'general', 'المنطقة الزمنية', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(21, 'max_trials_per_day_reseller', '5', 'integer', 'reseller', 'الحد الأقصى للحسابات التجريبية يومياً للموزع', 0, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(22, 'trial_duration_hours', '24', 'integer', 'general', 'مدة الحساب التجريبي بالساعات', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(23, 'max_devices_per_subscriber', '1', 'integer', 'subscriber', 'الحد الأقصى للأجهزة لكل مشترك', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(24, 'enable_registration', '1', 'boolean', 'general', 'تفعيل تسجيل الموزعين الجدد', 0, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(25, 'maintenance_mode', '0', 'boolean', 'general', 'وضع الصيانة', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(26, 'contact_email', 'support@miratv.com', 'string', 'contact', 'البريد الإلكتروني للدعم', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(27, 'contact_phone', '+966501234567', 'string', 'contact', 'رقم الهاتف للدعم', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(28, 'facebook_url', 'https://facebook.com/suleyman.alahmad', 'string', 'social', 'رابط فيسبوك المطور', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(29, 'developer_name', 'Süleyman Al-Ahmad', 'string', 'developer', 'اسم المطور', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16'),
(30, 'copyright_text', 'جميع الحقوق محفوظة © 2024 MIRA TV PRO', 'string', 'general', 'نص حقوق النشر', 1, '2026-01-30 17:04:16', '2026-01-30 17:04:16');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('admin','subscriber') DEFAULT 'subscriber',
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `transaction_type` enum('credit','debit','transfer','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_id` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usage_stats`
--

CREATE TABLE `usage_stats` (
  `id` int(11) NOT NULL,
  `subscriber_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `device_info` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_activity_logs_admin_created` (`admin_id`,`created_at`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_admin_users_role` (`role`),
  ADD KEY `idx_admin_users_status` (`status`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_key` (`template_key`),
  ADD KEY `idx_template_key` (`template_key`),
  ADD KEY `idx_language` (`language`);

--
-- Indexes for table `iptv_servers`
--
ALTER TABLE `iptv_servers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_user_type` (`user_type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_code` (`plan_code`),
  ADD KEY `idx_plan_code` (`plan_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `activation_code` (`activation_code`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `idx_activation_code` (`activation_code`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_is_trial` (`is_trial`),
  ADD KEY `idx_subscribers_expiry_status` (`expiry_date`,`status`),
  ADD KEY `idx_subscribers_created_by_status` (`created_by`,`status`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_ticket_number` (`ticket_number`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_transactions_admin_created` (`admin_id`,`created_at`);

--
-- Indexes for table `usage_stats`
--
ALTER TABLE `usage_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subscriber_id` (`subscriber_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `iptv_servers`
--
ALTER TABLE `iptv_servers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usage_stats`
--
ALTER TABLE `usage_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD CONSTRAINT `subscribers_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `iptv_servers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `subscribers_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `usage_stats`
--
ALTER TABLE `usage_stats`
  ADD CONSTRAINT `usage_stats_ibfk_1` FOREIGN KEY (`subscriber_id`) REFERENCES `subscribers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
