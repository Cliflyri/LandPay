-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 31, 2026 at 06:01 PM
-- Server version: 10.11.19-MariaDB-log
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mohavedeals_landpay`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notices`
--

CREATE TABLE `admin_notices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_change_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `dismissed_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `client_payment_intent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `secure_message_thread_id` bigint(20) UNSIGNED DEFAULT NULL,
  `provider_event_id` varchar(120) DEFAULT NULL,
  `provider_event_type` varchar(80) DEFAULT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_notices`
--

INSERT INTO `admin_notices` (`id`, `type`, `client_id`, `client_change_request_id`, `title`, `message`, `dismissed_by_user_id`, `dismissed_at`, `created_at`, `updated_at`, `client_payment_intent_id`, `secure_message_thread_id`, `provider_event_id`, `provider_event_type`, `invoice_id`) VALUES
(1, 'portal_invitation_accepted', 17, NULL, 'Portal invitation accepted', 'Chris Costa - chris@mohavedeals.com activated portal access.', 1, '2026-08-11 18:22:36', '2026-08-11 17:48:52', '2026-08-11 18:22:36', NULL, NULL, NULL, NULL, NULL),
(2, 'online_payment_received', 17, NULL, 'Online payment received', 'Chris Costa paid $75.00 by Square on Aug 11, 2026. Payment posted successfully.', 1, '2026-08-12 20:54:31', '2026-08-11 18:21:46', '2026-08-12 20:54:31', 3, NULL, NULL, NULL, NULL),
(3, 'online_payment_received', 17, NULL, 'Online payment received', 'Chris Costa paid $135.00 by Square on Aug 11, 2026. Payment posted successfully.', 1, '2026-08-12 20:54:33', '2026-08-11 18:31:55', '2026-08-12 20:54:33', 4, NULL, NULL, NULL, NULL),
(4, 'online_payment_received', 17, NULL, 'Online payment received', 'Chris Costa paid $45.00 by Stripe on Aug 11, 2026. Payment posted successfully.', 1, '2026-08-12 20:54:27', '2026-08-11 19:03:24', '2026-08-12 20:54:27', 5, NULL, NULL, NULL, NULL),
(5, 'online_payment_received', 17, NULL, 'Online payment received', 'Chris Costa paid $1.00 by Square on Aug 11, 2026. Payment posted successfully.', 1, '2026-08-12 20:56:30', '2026-08-11 19:10:55', '2026-08-12 20:56:30', 6, NULL, NULL, NULL, NULL),
(6, 'online_payment_received', 17, NULL, 'Online payment received', 'Chris Costa paid $0.65 by Stripe on Aug 11, 2026. Payment posted successfully.', 1, '2026-08-12 20:56:28', '2026-08-11 19:45:30', '2026-08-12 20:56:28', 10, NULL, NULL, NULL, NULL),
(8, 'portal_invitation_accepted', 16, NULL, 'Portal invitation accepted', 'Joyce Costa - joygr8@yahoo.com activated portal access.', 1, '2026-08-12 20:56:02', '2026-08-12 20:39:27', '2026-08-12 20:56:02', NULL, NULL, NULL, NULL, NULL),
(9, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $115.00 by Zelle for plan Testprop1.', 1, '2026-08-12 20:54:56', '2026-08-12 20:40:54', '2026-08-12 20:54:56', 11, NULL, NULL, NULL, NULL),
(10, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $115.00 by Zelle for plan Testprop1.', 1, '2026-08-12 20:55:55', '2026-08-12 20:44:41', '2026-08-12 20:55:55', 12, NULL, NULL, NULL, NULL),
(11, 'secure_message_reply', 16, NULL, 'Secure message reply', 'Joyce Costa replied to \"Let me know if you get this\".', 1, '2026-08-12 20:53:55', '2026-08-12 20:45:51', '2026-08-12 20:53:55', NULL, 2, NULL, NULL, NULL),
(12, 'secure_message_reply', 16, NULL, 'Secure message reply', 'Joyce Costa replied to \"Let me know if you get this\".', 1, '2026-08-12 21:36:44', '2026-08-12 21:17:59', '2026-08-12 21:36:44', NULL, 2, NULL, NULL, NULL),
(13, 'secure_message_reply', 16, NULL, 'Secure message reply', 'Joyce Costa replied to \"Let me know if you get this\".', 1, '2026-08-12 21:36:44', '2026-08-12 21:19:27', '2026-08-12 21:36:44', NULL, 2, NULL, NULL, NULL),
(14, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $115.00 by Zelle for plan Testprop1.', 1, '2026-08-13 18:35:24', '2026-08-13 17:13:22', '2026-08-13 18:35:24', 13, NULL, NULL, NULL, NULL),
(15, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $145.00 by Zelle for plan Testprop1.', 1, '2026-08-14 22:55:30', '2026-08-14 17:43:33', '2026-08-14 22:55:30', 14, NULL, NULL, NULL, NULL),
(16, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $115.00 by Venmo for plan Testprop1.', 1, '2026-08-15 21:05:38', '2026-08-15 16:39:46', '2026-08-15 21:05:38', 16, NULL, NULL, NULL, NULL),
(17, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $230.00 by Zelle for plan Testprop1.', 1, '2026-08-15 21:05:23', '2026-08-15 16:45:18', '2026-08-15 21:05:23', 17, NULL, NULL, NULL, NULL),
(18, 'secure_message_reply', 16, NULL, 'Secure message reply', 'Joyce Costa replied to \"Thank you\".', 1, '2026-08-16 10:33:52', '2026-08-16 04:14:46', '2026-08-16 10:33:52', NULL, 4, NULL, NULL, NULL),
(19, 'secure_message_reply', 16, NULL, 'Secure message reply', 'Joyce Costa replied to \"Thank you\".', 1, '2026-08-16 17:33:48', '2026-08-16 15:47:04', '2026-08-16 17:33:48', NULL, 4, NULL, NULL, NULL),
(20, 'portal_invitation_accepted', 1, NULL, 'Portal invitation accepted', 'Ernest Hayes - ernesth33jr@gmail.com activated portal access.', 1, '2026-08-17 18:49:55', '2026-08-17 15:46:53', '2026-08-17 18:49:55', NULL, NULL, NULL, NULL, NULL),
(21, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $125.00 by Zelle for plan Testprop1.', 1, '2026-08-17 18:02:48', '2026-08-17 17:34:29', '2026-08-17 18:02:48', 18, NULL, NULL, NULL, NULL),
(22, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $105.00 by Zelle for plan Testprop1.', 1, '2026-08-18 16:40:13', '2026-08-18 15:28:03', '2026-08-18 16:40:13', 19, NULL, NULL, NULL, NULL),
(23, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $50.00 by Zelle for plan Testprop1.', 1, '2026-08-21 12:33:59', '2026-08-20 21:53:04', '2026-08-21 12:33:59', 20, NULL, NULL, NULL, NULL),
(24, 'portal_invitation_accepted', 2, NULL, 'Portal invitation accepted', 'Tami McCarthy - tamiwicchick@aol.com activated portal access.', 1, '2026-08-21 15:50:54', '2026-08-21 15:02:27', '2026-08-21 15:50:54', NULL, NULL, NULL, NULL, NULL),
(25, 'client_contact_change', 2, 1, 'Client contact update requested', 'Tami McCarthy submitted 6 contact change(s) for review.', 1, '2026-08-21 15:47:45', '2026-08-21 15:03:07', '2026-08-21 15:47:45', NULL, NULL, NULL, NULL, NULL),
(26, 'secure_message_reply', 16, NULL, 'New secure message', 'Joyce Costa sent a new secure message.', 1, '2026-08-21 18:17:16', '2026-08-21 17:56:57', '2026-08-21 18:17:16', NULL, 7, NULL, NULL, NULL),
(27, 'secure_message_reply', 16, NULL, 'Secure message reply', 'Joyce Costa replied to \"Original pjurchase price of property\".', 1, '2026-08-21 20:44:02', '2026-08-21 20:17:37', '2026-08-21 20:44:02', NULL, 7, NULL, NULL, NULL),
(28, 'secure_message_reply', 16, NULL, 'Secure message reply', 'Joyce Costa replied to \"Original pjurchase price of property\".', 1, '2026-08-22 11:02:07', '2026-08-22 01:16:12', '2026-08-22 11:02:07', NULL, 7, NULL, NULL, NULL),
(29, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $115.00 by Zelle for plan Testprop1.', 1, '2026-08-23 17:16:41', '2026-08-23 17:12:43', '2026-08-23 17:16:41', 21, NULL, NULL, NULL, NULL),
(30, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $127.00 by Zelle for plan Testprop1.', 1, '2026-08-24 00:24:36', '2026-08-24 00:24:18', '2026-08-24 00:24:36', 22, NULL, NULL, NULL, NULL),
(31, 'client_payment_announced', 16, NULL, 'Payment intended', 'Joyce Costa intends to pay $115.00 by Zelle for plan Testprop1.', 1, '2026-08-27 13:24:38', '2026-08-24 14:12:16', '2026-08-27 13:24:38', 23, NULL, NULL, NULL, NULL),
(32, 'secure_message_reply', 16, NULL, 'Secure message reply', 'Joyce Costa replied to \"Notices\".', 1, '2026-08-28 01:23:11', '2026-08-28 01:02:50', '2026-08-28 01:23:11', NULL, 8, NULL, NULL, NULL),
(33, 'secure_message_reply', 16, NULL, 'Secure message reply', 'Joyce Costa replied to \"Notices\".', 1, '2026-08-28 18:55:30', '2026-08-28 18:04:26', '2026-08-28 18:55:30', NULL, 8, NULL, NULL, NULL),
(34, 'online_payment_received', 17, NULL, 'Online payment received', 'Chris Costa paid $1.34 by Square on Aug 30, 2026. Payment posted successfully.', NULL, NULL, '2026-08-30 22:23:13', '2026-08-30 22:23:13', 29, NULL, NULL, NULL, NULL),
(35, 'online_payment_received', 17, NULL, 'Online payment received', 'Chris Costa paid $1.00 by Square on Aug 30, 2026. Payment posted successfully.', NULL, NULL, '2026-08-30 22:27:48', '2026-08-30 22:27:48', 30, NULL, NULL, NULL, NULL),
(36, 'client_payment_announced', 17, NULL, 'Payment intended', 'Chris Costa intends to pay $5.00 by Zelle for plan Testprop2.', NULL, NULL, '2026-08-31 13:05:46', '2026-08-31 13:05:46', 31, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'client_payments_enabled', '1', '2026-08-08 16:53:59', '2026-08-08 16:53:59'),
(2, 'client_payments_custom_amount', '1', '2026-08-08 16:53:59', '2026-08-08 16:53:59'),
(3, 'card_provider', 'square', '2026-08-08 16:53:59', '2026-08-11 19:46:11'),
(4, 'payment_intent_expiry_days', '14', '2026-08-08 16:53:59', '2026-08-08 16:53:59'),
(5, 'company_name', 'Mohave Deals LandPay', '2026-08-08 16:55:10', '2026-08-08 16:55:10'),
(6, 'company_email', 'chris@mohavedeals.com', '2026-08-08 16:55:10', '2026-08-08 16:55:10'),
(7, 'company_phone', '928-288-0556', '2026-08-08 16:55:10', '2026-08-08 16:55:10'),
(8, 'reply_to_email', 'chris@mohavedeals.com', '2026-08-08 16:55:10', '2026-08-12 16:54:23'),
(9, 'email_footer', 'Thank you for choosing Mohave Deals LandPay!', '2026-08-08 16:55:10', '2026-08-08 16:55:10'),
(10, 'smtp_enabled', '1', '2026-08-10 12:41:29', '2026-08-10 12:41:29'),
(11, 'smtp_host', 'mail.mohavedeals.com', '2026-08-10 12:41:29', '2026-08-10 12:41:29'),
(12, 'smtp_port', '465', '2026-08-10 12:41:29', '2026-08-10 12:41:29'),
(13, 'smtp_security', 'tls', '2026-08-10 12:41:29', '2026-08-10 12:41:29'),
(14, 'smtp_username', 'mailer@mohavedeals.com', '2026-08-10 12:41:29', '2026-08-10 12:41:29'),
(15, 'smtp_from_address', 'chris@mohavedeals.com', '2026-08-10 12:41:29', '2026-08-10 12:41:29'),
(16, 'smtp_from_name', 'Mohave Deals LandPay', '2026-08-10 12:41:29', '2026-08-10 12:41:29'),
(17, 'smtp_ehlo_domain', NULL, '2026-08-10 12:41:29', '2026-08-10 12:41:29'),
(18, 'smtp_password', 'eyJpdiI6Ilk1bWFtbmZ1Tlpya2FjeUJLemZIYXc9PSIsInZhbHVlIjoicXdKSlFiaEROSzFRQ3F6VlZaWXd5UT09IiwibWFjIjoiY2JlZWI3MmRiNmY3Y2NkNjIyNjNmMGQ3Njk3YzMxMWEzMzcwMzMyMmM2MzI1YzZkYzQ1M2FlZjZlNGI5MWFjZiIsInRhZyI6IiJ9', '2026-08-10 12:41:29', '2026-08-10 12:41:29'),
(19, 'payment_melio_enabled', '1', '2026-08-11 17:29:14', '2026-08-11 17:29:32'),
(20, 'payment_melio_name', 'Melio Payments', '2026-08-11 17:29:14', '2026-08-11 17:29:14'),
(21, 'payment_melio_instructions', 'Melio offers quick and easy direct payments.', '2026-08-11 17:29:14', '2026-08-11 17:29:14'),
(22, 'payment_melio_recipient', 'mohavedeals', '2026-08-11 17:29:14', '2026-08-11 17:29:14'),
(23, 'payment_melio_link', 'https://melio.me/mohavedeals', '2026-08-11 17:29:14', '2026-08-11 17:29:14'),
(24, 'payment_melio_image_url', 'https://melio.com/wp-content/uploads/2022/07/Melio_Logo_Purple_01.svg', '2026-08-11 17:29:14', '2026-08-11 17:29:14'),
(25, 'payment_melio_button', 'I sent this payment', '2026-08-11 17:29:14', '2026-08-11 17:29:14'),
(26, 'square_environment', 'live', '2026-08-11 17:44:54', '2026-08-11 18:34:56'),
(27, 'square_public_id', 'LSER7260N94E5', '2026-08-11 17:44:54', '2026-08-11 18:34:56'),
(28, 'square_api_secret', 'eyJpdiI6ImZuN015cGRQY0FrY0hmNkRVZUlrMWc9PSIsInZhbHVlIjoiUjd4ampqNnlXdXhvb3dFSUU4STkzdz09IiwibWFjIjoiNjg1NmY5NDYxODMzODNlZmM2MmEzNmMyNWQ5MmUzYTk1YTA5YWZjNjkxMTE1NWJlZmY0N2YwOThhNTRmNWMwMSIsInRhZyI6IiJ9', '2026-08-11 17:44:54', '2026-08-31 02:03:24'),
(29, 'square_webhook_secret', 'eyJpdiI6Ilh4RHZ4eWMvS1pjQ2YyY1J0OVByM1E9PSIsInZhbHVlIjoiWndQOHFHbDJMVmRMQm5XTlkyK3dxYk15VVdJTXp2Zkc4RC9IYU1GQm84ND0iLCJtYWMiOiIzNjJmM2NhMjVkNmVjOTVhMTg0ODQ1MmFhYjc4YWNmYTIyMWZiNGRhNjU0YWNlMjIyMjY5YzE1MDMzY2RiYTgzIiwidGFnIjoiIn0=', '2026-08-11 17:47:50', '2026-08-30 22:22:51'),
(30, 'payment_cash_app_enabled', '1', '2026-08-11 17:50:39', '2026-08-11 17:50:39'),
(31, 'payment_cash_app_name', 'Cash App', '2026-08-11 17:50:39', '2026-08-11 17:50:39'),
(32, 'payment_cash_app_instructions', 'If payment is coming from an account other than your own, please notify admin.', '2026-08-11 17:50:39', '2026-08-11 17:50:39'),
(33, 'payment_cash_app_recipient', '$MohaveDeals', '2026-08-11 17:50:39', '2026-08-11 17:50:39'),
(34, 'payment_cash_app_link', 'https://cash.app/$MohaveDeals', '2026-08-11 17:50:39', '2026-08-11 17:50:39'),
(35, 'payment_cash_app_image_url', 'https://mohavedeals.com/images/payment/cashapp.png', '2026-08-11 17:50:39', '2026-08-11 17:50:39'),
(36, 'payment_cash_app_button', 'I sent this payment', '2026-08-11 17:50:39', '2026-08-11 17:50:39'),
(37, 'payment_venmo_enabled', '1', '2026-08-11 17:50:56', '2026-08-11 17:50:56'),
(38, 'payment_venmo_name', 'Venmo', '2026-08-11 17:50:56', '2026-08-11 17:50:56'),
(39, 'payment_venmo_instructions', '', '2026-08-11 17:50:56', '2026-08-11 17:50:56'),
(40, 'payment_venmo_recipient', 'Chris-Mohavedeals', '2026-08-11 17:50:56', '2026-08-11 17:50:56'),
(41, 'payment_venmo_link', 'https://venmo.com/u/Chris-Mohavedeals', '2026-08-11 17:50:56', '2026-08-11 17:50:56'),
(42, 'payment_venmo_image_url', 'https://mohavedeals.com/images/payment/venmo.png', '2026-08-11 17:50:56', '2026-08-11 17:50:56'),
(43, 'payment_venmo_button', 'I sent this payment', '2026-08-11 17:50:56', '2026-08-11 17:50:56'),
(44, 'payment_card_enabled', '1', '2026-08-11 17:51:51', '2026-08-11 17:51:51'),
(45, 'payment_card_name', 'Credit/Debit Card', '2026-08-11 17:51:51', '2026-08-31 02:29:15'),
(46, 'payment_card_instructions', '', '2026-08-11 17:51:51', '2026-08-11 17:51:51'),
(47, 'payment_card_recipient', '', '2026-08-11 17:51:51', '2026-08-11 17:51:51'),
(48, 'payment_card_link', '', '2026-08-11 17:51:51', '2026-08-11 17:51:51'),
(49, 'payment_card_image_url', 'https://mohavedeals.com/images/payment/ccard.png', '2026-08-11 17:51:51', '2026-08-11 17:51:51'),
(50, 'payment_card_button', 'Continue to secure checkout', '2026-08-11 17:51:51', '2026-08-11 17:51:51'),
(51, 'stripe_environment', 'live', '2026-08-11 19:01:38', '2026-08-11 19:07:48'),
(52, 'stripe_public_id', 'pk_live_51KmQnEIC2f6Um3lLMfAbqQEeqAySk3uAPoIncVV7ti6NYzhpsECx0Crpo0LGKQ9fgl5GBk403DU9jlqlIACLH8gx00ITmYUXh4', '2026-08-11 19:01:38', '2026-08-11 19:07:48'),
(53, 'stripe_webhook_secret', 'eyJpdiI6IkYyMVJ5SHhmNW5Fd1RoZDlZaVRjQnc9PSIsInZhbHVlIjoibCtrMTFBMUdRYzBDUXBFVlhCTU44LzE5anV5L3RXM2tHOHJuTWEyWEJQUXZZODJUeUxKMzFBQnNwbU9lbVd1YyIsIm1hYyI6IjNmN2U3ZWQzNzFmMjkxYzRlYWUxNTg5NjdmYThjM2Y3YWY5OWY1MjU0MmNmYmI4YmUxODQzMzBjOWUzYjE0ZTkiLCJ0YWciOiIifQ==', '2026-08-11 19:01:38', '2026-08-11 19:13:27'),
(54, 'stripe_api_secret', 'eyJpdiI6Iis1TmN5ekl2NCtHNDIyUm4vNXhmaEE9PSIsInZhbHVlIjoiQXVCMG9PMmNsRXRWSXowSUw5S0JmQmtHanJRaWUxZERwMytpcUlINzR2UjYvb3prcmgxSWx5MzN6dVRkK0gxMEhacHV6TFNLSm03Q3hYWllQbFBlaDRuVnc4aWVUQ2NRRHZvb1F3clVMVVpuQkppcTlkUDNud09PdTNoeUlkZjZJWVJGanFUcUhkUEoyZTQzMmVLa3p3PT0iLCJtYWMiOiIxZmEzNGRjNWVmMDQ0ZDQ2ODQzOTYzZjUzMWE5NTc1YWZlOWFjNDg1YTRhYzgzZGY1MmVmYzA1NjJjY2I0MmZkIiwidGFnIjoiIn0=', '2026-08-11 19:01:38', '2026-08-11 19:39:10'),
(55, 'secure_message_admin_email_enabled', '1', '2026-08-12 16:48:37', '2026-08-12 16:54:28'),
(56, 'reminders_automated_enabled', '1', '2026-08-16 01:44:51', '2026-08-19 21:36:20'),
(57, 'reminders_before_days', '3', '2026-08-16 01:44:51', '2026-08-16 01:44:51'),
(58, 'reminders_on_due', '1', '2026-08-16 01:44:51', '2026-08-16 01:44:51'),
(59, 'reminders_after_interval', '4', '2026-08-16 01:44:51', '2026-08-19 21:43:08'),
(60, 'reminders_after_max', '2', '2026-08-16 01:44:51', '2026-08-19 21:43:08'),
(61, 'payment_zelle_enabled', '1', '2026-08-19 22:44:19', '2026-08-19 22:44:19'),
(62, 'payment_zelle_name', 'Zelle', '2026-08-19 22:44:19', '2026-08-19 22:44:19'),
(63, 'payment_zelle_instructions', 'If this payment is coming from an account other than your name please send us a text or email letting us know.', '2026-08-19 22:44:19', '2026-08-19 22:44:19'),
(64, 'payment_zelle_recipient', 'sales@mohavedeals.com', '2026-08-19 22:44:19', '2026-08-19 22:44:19'),
(65, 'payment_zelle_link', '', '2026-08-19 22:44:19', '2026-08-19 22:44:19'),
(66, 'payment_zelle_image_url', 'https://mohavedeals.com/images/payment/zelle.png', '2026-08-19 22:44:19', '2026-08-19 22:44:19'),
(67, 'payment_zelle_button', 'I sent this payment', '2026-08-19 22:44:19', '2026-08-19 22:44:19'),
(68, 'square_application_id', 'sq0idp-Cn20VKyQ120qpANVIW146Q', '2026-08-30 19:36:57', '2026-08-30 19:36:57'),
(69, 'square_checkout_experience', 'landpay', '2026-08-30 19:36:57', '2026-08-30 19:58:25'),
(70, 'square_processing_fee_enabled', '1', '2026-08-30 19:36:57', '2026-08-30 19:36:57'),
(71, 'square_processing_fee_percent', '2.9', '2026-08-30 19:36:57', '2026-08-30 19:36:57'),
(72, 'square_processing_fee_amount', '30', '2026-08-30 19:36:57', '2026-08-30 19:36:57'),
(73, 'square_processing_fee_cap', '', '2026-08-30 19:36:57', '2026-08-30 19:36:57'),
(74, 'square_processing_fee_adjust', '1', '2026-08-30 19:36:57', '2026-08-31 02:20:58'),
(75, 'payment_other_enabled', '0', '2026-08-31 02:22:24', '2026-08-31 02:30:45'),
(76, 'payment_other_name', 'Bank Wire', '2026-08-31 02:22:24', '2026-08-31 02:28:47'),
(77, 'payment_other_instructions', 'If you would like to make a larger payment by wire transfer, please notify us above, and we will send instructions through secure messaging.  Thank you.', '2026-08-31 02:22:24', '2026-08-31 02:28:09'),
(78, 'payment_other_recipient', '', '2026-08-31 02:22:24', '2026-08-31 02:22:24'),
(79, 'payment_other_link', '', '2026-08-31 02:22:24', '2026-08-31 02:22:24'),
(80, 'payment_other_image_url', '', '2026-08-31 02:22:24', '2026-08-31 02:26:33'),
(81, 'payment_other_button', 'I will make this payment', '2026-08-31 02:22:24', '2026-08-31 02:22:24'),
(82, 'invoice_view_admin_notice_enabled', '1', '2026-08-31 13:03:43', '2026-08-31 13:03:43'),
(83, 'admin_notice_email_invoice', '1', '2026-08-31 13:03:43', '2026-08-31 13:03:43'),
(84, 'admin_notice_email_payments', '1', '2026-08-31 13:03:43', '2026-08-31 13:03:43'),
(85, 'admin_notice_email_secure_messages', '1', '2026-08-31 13:03:43', '2026-08-31 13:03:43'),
(86, 'admin_notice_email_documents', '1', '2026-08-31 13:03:43', '2026-08-31 13:03:43'),
(87, 'admin_notice_email_account_portal', '1', '2026-08-31 13:03:43', '2026-08-31 13:03:43'),
(88, 'admin_notice_email_address', '', '2026-08-31 13:03:43', '2026-08-31 13:03:43');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `actor_type` varchar(24) NOT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `actor_client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event` varchar(100) NOT NULL,
  `auditable_type` varchar(100) NOT NULL,
  `auditable_id` bigint(20) UNSIGNED NOT NULL,
  `before_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before_values`)),
  `after_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `uuid`, `actor_type`, `actor_user_id`, `actor_client_id`, `event`, `auditable_type`, `auditable_id`, `before_values`, `after_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, '14427bfd-5b8d-48db-aeb5-c4e4e0c16113', 'administrator', 1, NULL, 'payment_plan.amended', 'App\\Models\\PaymentPlan', 1, '{\"plan\":{\"plan_number\":\"333-18-048\",\"title\":\"Shadehouse Dr. .22 Acres Kingman\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2024-12-23T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":230000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"id\":1,\"payment_plan_id\":1,\"frequency\":\"monthly\",\"invoice_day\":3,\"due_days_after_issue\":5,\"grace_days\":1,\"scheduled_payment_amount\":11500,\"monthly_service_fee\":1500,\"stage_one_enabled\":1,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":2,\"stage_two_enabled\":0,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":60,\"effective_from\":\"2024-12-23\",\"effective_to\":null,\"reason\":null,\"created_by_user_id\":1,\"created_at\":\"2026-08-09 20:42:02\",\"updated_at\":\"2026-08-09 20:42:02\"}}', '{\"plan\":{\"plan_number\":\"333-18-048\",\"title\":\"Shadehouse Dr. .22 Acres Kingman\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2024-12-23T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":230000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"payment_plan_id\":1,\"frequency\":\"monthly\",\"invoice_day\":\"3\",\"due_days_after_issue\":\"5\",\"grace_days\":\"1\",\"scheduled_payment_amount\":11500,\"monthly_service_fee\":1500,\"stage_one_enabled\":true,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":2,\"stage_two_enabled\":false,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":\"60\",\"effective_from\":\"2026-08-10 00:00:00\",\"reason\":\"previously paid in credit\",\"created_by_user_id\":1,\"updated_at\":\"2026-08-09 20:58:38\",\"created_at\":\"2026-08-09 20:58:38\",\"id\":2},\"reason\":\"previously paid in credit\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-10 03:58:38'),
(2, '16bda4ea-e0a9-4c23-9a36-34b1d2da322b', 'administrator', 1, NULL, 'payment_plan.amended', 'App\\Models\\PaymentPlan', 4, '{\"plan\":{\"plan_number\":\"333-18-440 (1)\",\"title\":\"Painted Rock Valle Vista\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-10T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":292100,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"id\":5,\"payment_plan_id\":4,\"frequency\":\"monthly\",\"invoice_day\":3,\"due_days_after_issue\":5,\"grace_days\":5,\"scheduled_payment_amount\":13500,\"monthly_service_fee\":1500,\"stage_one_enabled\":1,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":6,\"stage_two_enabled\":0,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":60,\"effective_from\":\"2026-08-10\",\"effective_to\":null,\"reason\":null,\"created_by_user_id\":1,\"created_at\":\"2026-08-10 09:17:38\",\"updated_at\":\"2026-08-10 09:17:38\"}}', '{\"plan\":{\"plan_number\":\"333-18-440 (1)\",\"title\":\"Painted Rock Valle Vista North\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-10T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":292100,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"payment_plan_id\":4,\"frequency\":\"monthly\",\"invoice_day\":\"3\",\"due_days_after_issue\":\"5\",\"grace_days\":\"5\",\"scheduled_payment_amount\":13500,\"monthly_service_fee\":1500,\"stage_one_enabled\":true,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":6,\"stage_two_enabled\":false,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":\"60\",\"effective_from\":\"2026-08-11 00:00:00\",\"reason\":\"Property desription change\",\"created_by_user_id\":1,\"updated_at\":\"2026-08-10 09:20:52\",\"created_at\":\"2026-08-10 09:20:52\",\"id\":7},\"reason\":\"Property desription change\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-10 16:20:52'),
(3, '3649660a-30a3-4a14-ba46-cf98b3a0ff9d', 'administrator', 1, NULL, 'payment_plan.paused', 'App\\Models\\PaymentPlan', 12, NULL, '{\"pause\":{\"payment_plan_id\":12,\"pause_date\":\"2026-08-10 00:00:00\",\"planned_resume_date\":null,\"reason\":\"Client manual payments\",\"paused_by_user_id\":1,\"updated_at\":\"2026-08-10 10:01:17\",\"created_at\":\"2026-08-10 10:01:17\",\"id\":1}}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-10 17:01:17'),
(4, '56514de5-f1ec-4755-bf8c-a2d8e0e6bb2e', 'administrator', 1, NULL, 'payment_plan.amended', 'App\\Models\\PaymentPlan', 14, '{\"plan\":{\"plan_number\":\"314-20-022\",\"title\":\"Truxton, AZ\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-02-05T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":549500,\"documentation_fee_standard\":44900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"id\":16,\"payment_plan_id\":14,\"frequency\":\"monthly\",\"invoice_day\":3,\"due_days_after_issue\":5,\"grace_days\":2,\"scheduled_payment_amount\":15000,\"monthly_service_fee\":1500,\"stage_one_enabled\":1,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":3,\"stage_two_enabled\":0,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":60,\"effective_from\":\"2026-02-05\",\"effective_to\":null,\"reason\":null,\"created_by_user_id\":1,\"created_at\":\"2026-08-10 10:04:19\",\"updated_at\":\"2026-08-10 10:04:19\"}}', '{\"plan\":{\"plan_number\":\"314-20-022\",\"title\":\"Truxton, AZ\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-10T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":549500,\"documentation_fee_standard\":44900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"payment_plan_id\":14,\"frequency\":\"monthly\",\"invoice_day\":\"3\",\"due_days_after_issue\":\"5\",\"grace_days\":\"2\",\"scheduled_payment_amount\":15000,\"monthly_service_fee\":1500,\"stage_one_enabled\":true,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":3,\"stage_two_enabled\":false,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":\"60\",\"effective_from\":\"2026-08-11 00:00:00\",\"reason\":\"Amend contract start date to Landpay date\",\"created_by_user_id\":1,\"updated_at\":\"2026-08-10 10:12:15\",\"created_at\":\"2026-08-10 10:12:15\",\"id\":19},\"reason\":\"Amend contract start date to Landpay date\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-10 17:12:15'),
(5, '940907f4-babe-4622-b394-d99f6cedc156', 'administrator', 1, NULL, 'payment_plan.paused', 'App\\Models\\PaymentPlan', 5, NULL, '{\"pause\":{\"payment_plan_id\":5,\"pause_date\":\"2026-08-10 00:00:00\",\"planned_resume_date\":null,\"reason\":\"Client manual payments\",\"paused_by_user_id\":1,\"updated_at\":\"2026-08-10 10:41:13\",\"created_at\":\"2026-08-10 10:41:13\",\"id\":2}}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-10 17:41:13'),
(6, '0b6cf17e-bca6-49a8-8af6-29b4cdd7fcc3', 'administrator', 1, NULL, 'payment_plan.paused', 'App\\Models\\PaymentPlan', 4, NULL, '{\"pause\":{\"payment_plan_id\":4,\"pause_date\":\"2026-08-10 00:00:00\",\"planned_resume_date\":null,\"reason\":\"Client manual payments\",\"paused_by_user_id\":1,\"updated_at\":\"2026-08-10 10:41:28\",\"created_at\":\"2026-08-10 10:41:28\",\"id\":3}}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-10 17:41:28'),
(7, '1e2bf34d-8f36-4ebc-9392-03efefe068fb', 'administrator', 1, NULL, 'payment_plan.amended', 'App\\Models\\PaymentPlan', 17, '{\"plan\":{\"plan_number\":\"Testprop1\",\"title\":\"1.14 Imaginary Acres of land\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-11T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":250000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"id\":20,\"payment_plan_id\":17,\"frequency\":\"monthly\",\"invoice_day\":3,\"due_days_after_issue\":5,\"grace_days\":4,\"scheduled_payment_amount\":10000,\"monthly_service_fee\":1500,\"stage_one_enabled\":1,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":5,\"stage_two_enabled\":0,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":60,\"effective_from\":\"2026-08-11\",\"effective_to\":null,\"reason\":null,\"created_by_user_id\":1,\"created_at\":\"2026-08-11 09:51:44\",\"updated_at\":\"2026-08-11 09:51:44\"}}', '{\"plan\":{\"plan_number\":\"Testprop1\",\"title\":\"1.14 Imaginary Acres of land\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-11T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":250000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"payment_plan_id\":17,\"frequency\":\"monthly\",\"invoice_day\":\"3\",\"due_days_after_issue\":\"5\",\"grace_days\":\"4\",\"scheduled_payment_amount\":10000,\"monthly_service_fee\":1500,\"stage_one_enabled\":true,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":5,\"stage_two_enabled\":false,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":\"60\",\"effective_from\":\"2026-08-12 00:00:00\",\"reason\":\"Test mode enabled\",\"created_by_user_id\":1,\"updated_at\":\"2026-08-11 09:52:09\",\"created_at\":\"2026-08-11 09:52:09\",\"id\":21},\"reason\":\"Test mode enabled\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-11 16:52:09'),
(8, 'cf5fb61f-003f-47b9-bcd1-2d4eebfb5787', 'administrator', 1, NULL, 'payment_plan.amended', 'App\\Models\\PaymentPlan', 1, '{\"plan\":{\"plan_number\":\"333-18-048\",\"title\":\"Shadehouse Dr. .22 Acres Kingman\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2024-12-23T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":230000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"id\":2,\"payment_plan_id\":1,\"frequency\":\"monthly\",\"invoice_day\":3,\"due_days_after_issue\":5,\"grace_days\":1,\"scheduled_payment_amount\":11500,\"monthly_service_fee\":1500,\"stage_one_enabled\":1,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":2,\"stage_two_enabled\":0,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":60,\"effective_from\":\"2026-08-10\",\"effective_to\":null,\"reason\":\"previously paid in credit\",\"created_by_user_id\":1,\"created_at\":\"2026-08-09 20:58:38\",\"updated_at\":\"2026-08-09 20:58:38\"}}', '{\"plan\":{\"plan_number\":\"333-18-048\",\"title\":\"Shadehouse Dr. .22 Acres Kingman\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-10T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":230000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"payment_plan_id\":1,\"frequency\":\"monthly\",\"invoice_day\":\"3\",\"due_days_after_issue\":\"5\",\"grace_days\":\"1\",\"scheduled_payment_amount\":11500,\"monthly_service_fee\":1500,\"stage_one_enabled\":true,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":2,\"stage_two_enabled\":false,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":\"60\",\"effective_from\":\"2026-08-12 00:00:00\",\"reason\":\"Adjust start date\",\"created_by_user_id\":1,\"updated_at\":\"2026-08-11 12:53:39\",\"created_at\":\"2026-08-11 12:53:39\",\"id\":22},\"reason\":\"Adjust start date\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-11 19:53:39'),
(9, 'aa23abaa-bed3-4c9f-926e-53f2055b13d7', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-12T16:54:03-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-12 23:54:03'),
(10, '5c5fb048-f560-404e-9ca1-2c5952db7b45', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-12T16:54:03-07:00\",\"start_audit_id\":9}', '{\"ended_at\":\"2026-08-12T16:54:20-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-12 23:54:20'),
(11, '41fb297b-95b7-4521-a8ec-961694ef013d', 'administrator', 1, NULL, 'payment_plan.amended', 'App\\Models\\PaymentPlan', 18, '{\"plan\":{\"plan_number\":\"Testprop2\",\"title\":\"2.35 Acres of Imaginary land\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-11T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":300000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"id\":23,\"payment_plan_id\":18,\"frequency\":\"monthly\",\"invoice_day\":3,\"due_days_after_issue\":5,\"grace_days\":0,\"scheduled_payment_amount\":12000,\"monthly_service_fee\":1500,\"stage_one_enabled\":1,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":1,\"stage_two_enabled\":0,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":60,\"effective_from\":\"2026-08-11\",\"effective_to\":null,\"reason\":null,\"created_by_user_id\":1,\"created_at\":\"2026-08-11 13:49:55\",\"updated_at\":\"2026-08-11 13:49:55\"}}', '{\"plan\":{\"plan_number\":\"Testprop2\",\"title\":\"2.35 Acres of Imaginary land\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-11T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":300000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"payment_plan_id\":18,\"frequency\":\"monthly\",\"invoice_day\":\"14\",\"due_days_after_issue\":\"5\",\"grace_days\":\"0\",\"scheduled_payment_amount\":12000,\"monthly_service_fee\":1500,\"stage_one_enabled\":true,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":1,\"stage_two_enabled\":false,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":\"60\",\"effective_from\":\"2026-08-14 00:00:00\",\"reason\":\"change date\",\"created_by_user_id\":1,\"updated_at\":\"2026-08-13 17:33:40\",\"created_at\":\"2026-08-13 17:33:40\",\"id\":24},\"reason\":\"change date\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-14 00:33:40'),
(12, 'db954617-34ee-4e85-99f4-cf779e2d6afc', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-14T21:48:21-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-15 04:48:21'),
(13, '631d95a2-fce5-4f14-9f8f-a45b33e26b71', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-14T21:48:21-07:00\",\"start_audit_id\":12}', '{\"ended_at\":\"2026-08-14T21:56:08-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-15 04:56:08'),
(14, 'ddae007d-9d03-4cbb-a344-40eca671651b', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-15T09:12:30-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-15 16:12:30'),
(15, 'b38ad278-d411-4248-98ec-e59604ca53ac', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-15T09:12:30-07:00\",\"start_audit_id\":14}', '{\"ended_at\":\"2026-08-15T09:12:41-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-15 16:12:41'),
(16, '8014081c-0f9a-4a29-a823-e0358c3bbfd3', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-15T17:06:30-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 00:06:30'),
(17, '1dbaf47b-e40b-4eba-93b1-00cde4b08d39', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-15T18:55:09-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 01:55:09'),
(18, '28c2081a-c990-4db0-963a-12158f76a68d', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-15T18:55:09-07:00\",\"start_audit_id\":17}', '{\"ended_at\":\"2026-08-15T18:55:23-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 01:55:23'),
(19, 'a937fa83-48a8-4ac4-a2eb-8cadd0287a4c', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-15T18:55:33-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 01:55:33'),
(20, '45af9847-0d98-46ab-a79e-a54a09ee32bf', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-15T18:55:33-07:00\",\"start_audit_id\":19}', '{\"ended_at\":\"2026-08-15T18:56:18-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 01:56:18'),
(21, '378990d9-f963-4afb-a782-583b0faca830', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-15T18:56:44-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 01:56:44'),
(22, '700704cc-d901-4c8a-9bb1-2b17924a601f', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-15T18:56:44-07:00\",\"start_audit_id\":21}', '{\"ended_at\":\"2026-08-15T21:43:03-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 04:43:03'),
(23, 'b47f2a51-c088-4c42-b45b-81ceccce9844', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-15T21:48:39-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 04:48:39'),
(24, '14e6318f-7879-4dcd-853d-714c7b86d02b', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-15T21:48:39-07:00\",\"start_audit_id\":23}', '{\"ended_at\":\"2026-08-15T21:49:20-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 04:49:20'),
(25, '1eebd550-d698-4a2d-95d8-4c93b5740ed0', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-15T21:53:19-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 04:53:19'),
(26, '857125a3-c6ab-4ea6-8ff3-f81cdd996eab', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-15T21:53:19-07:00\",\"start_audit_id\":25}', '{\"ended_at\":\"2026-08-15T21:56:40-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 04:56:40'),
(27, '77dd45ca-3040-4815-bb9c-2e1ad848d1b0', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-15T21:59:48-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 04:59:48'),
(28, '7c90f842-ad44-49be-b92e-e51e4020704b', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-15T21:59:48-07:00\",\"start_audit_id\":27}', '{\"ended_at\":\"2026-08-15T21:59:59-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 04:59:59'),
(29, 'ebe2a018-aaa5-4541-a5ba-9c99656178dd', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-16T13:34:14-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 20:34:14'),
(30, '5055e419-0385-467d-bf47-c7394e186229', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-16T13:34:14-07:00\",\"start_audit_id\":29}', '{\"ended_at\":\"2026-08-16T13:34:27-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 20:34:27'),
(31, 'e446fe1d-6026-47c0-95c6-d195b90a50d2', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-16T15:32:45-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 22:32:45'),
(32, '3fc7c8fa-9d87-4063-bf33-2cb73644126e', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-16T15:32:45-07:00\",\"start_audit_id\":31}', '{\"ended_at\":\"2026-08-16T15:34:12-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 22:34:12'),
(33, '5f38ee0c-0cc7-4d33-b182-4a3fe698f78c', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-16T15:34:43-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-16 22:34:43'),
(34, '6013a149-5fc2-482c-9eeb-2c198c19c267', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-16T15:34:43-07:00\",\"start_audit_id\":33}', '{\"ended_at\":\"2026-08-16T17:02:47-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-17 00:02:47'),
(35, 'e8b11cfd-de0e-433f-9ad8-7ca414fd66e4', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-17T09:53:51-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-17 16:53:51'),
(36, 'a5ab98b8-dac3-48d0-a321-cdbceab7a82b', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-17T09:53:51-07:00\",\"start_audit_id\":35}', '{\"ended_at\":\"2026-08-17T10:21:23-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-17 17:21:23'),
(37, '1e43fea9-0bf8-4f84-94f1-8a22631d45cf', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-17T10:51:29-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-17 17:51:29'),
(38, '2f7abc5f-45c7-484c-9b17-8ba89fc5e54b', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-17T10:51:29-07:00\",\"start_audit_id\":37}', '{\"ended_at\":\"2026-08-17T10:51:39-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-17 17:51:39'),
(39, '7f23f62f-4497-47a0-a126-7e3e95a1eca5', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-17T11:51:37-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-17 18:51:37'),
(40, 'b3f7cf7d-497d-43ab-ae00-ba2c34e43c68', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-17T11:51:37-07:00\",\"start_audit_id\":39}', '{\"ended_at\":\"2026-08-17T11:52:11-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-17 18:52:11'),
(41, 'fc203db6-69cf-4610-a319-65e45d3aaca3', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-17T11:58:28-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-17 18:58:28'),
(42, 'c208503e-c294-436e-9546-612b9e6894ff', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-17T11:58:28-07:00\",\"start_audit_id\":41}', '{\"ended_at\":\"2026-08-17T11:58:53-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-17 18:58:53'),
(43, '5721d0b8-ecd6-4e77-8384-e2fe5f2cbff3', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-17T14:03:56-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-17 21:03:56'),
(44, '4fa149b6-2bad-48c1-803c-493488f97aa9', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-17T14:03:56-07:00\",\"start_audit_id\":43}', '{\"ended_at\":\"2026-08-17T14:49:50-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-17 21:49:50'),
(45, '58b82a02-7b11-4183-a5ae-a3240c4afdf4', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-17T17:11:11-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-18 00:11:11'),
(46, '4753199c-5ce5-41b8-b3bc-6999eaea977a', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-17T17:11:11-07:00\",\"start_audit_id\":45}', '{\"ended_at\":\"2026-08-17T17:11:48-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-18 00:11:48'),
(47, 'c76403c3-1207-4ce4-b356-c37936221812', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-17T21:54:34-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-18 04:54:34'),
(48, 'a98803da-87b4-4d9a-902f-6fd11b163efb', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-17T21:54:34-07:00\",\"start_audit_id\":47}', '{\"ended_at\":\"2026-08-17T21:54:56-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-18 04:54:56'),
(49, '450487f6-2e48-430e-8037-572d4fec8393', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-18T14:09:09-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-18 21:09:09'),
(50, 'a8e19db2-c912-4c66-8421-866a2d34747f', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-18T14:09:09-07:00\",\"start_audit_id\":49}', '{\"ended_at\":\"2026-08-18T14:09:17-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-18 21:09:17'),
(51, '19e8cdf0-d752-4368-98d9-8007ac507c51', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-18T22:17:45-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-19 05:17:45'),
(52, 'cec4aac5-a00d-477f-9766-d624a4f7e627', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-18T22:17:45-07:00\",\"start_audit_id\":51}', '{\"ended_at\":\"2026-08-18T22:18:00-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-19 05:18:00'),
(53, '6c0d5b1f-fb33-4406-a01d-c0c9614add7e', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-19T17:09:23-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 00:09:23'),
(54, '9f094f83-31cc-4d84-b1e4-898525fe6eab', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-19T17:09:23-07:00\",\"start_audit_id\":53}', '{\"ended_at\":\"2026-08-19T17:09:37-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 00:09:37'),
(55, '78666ad4-8499-4d99-80d5-e45b64a2f1da', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-19T17:43:53-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 00:43:53'),
(56, '51ed1e70-6349-4e9a-83ba-718e45207b01', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-19T17:43:53-07:00\",\"start_audit_id\":55}', '{\"ended_at\":\"2026-08-19T17:44:12-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 00:44:12'),
(57, '1a730f7a-a9d1-4f18-87c9-d5e319327daa', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-19T17:51:54-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 00:51:54'),
(58, '760426dd-d437-4572-b1bb-703f1169a208', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-19T17:51:54-07:00\",\"start_audit_id\":57}', '{\"ended_at\":\"2026-08-19T17:52:02-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 00:52:02'),
(59, '2dfdb1a4-c588-4969-8ec7-22a261bf2684', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-19T17:53:16-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 00:53:16'),
(60, 'a3c531cc-23ef-4fd2-baa7-a0040684a549', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-19T17:53:16-07:00\",\"start_audit_id\":59}', '{\"ended_at\":\"2026-08-19T17:53:33-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 00:53:33'),
(61, '007c01f7-d209-45f2-95aa-cc404770d073', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-19T18:43:29-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 01:43:29'),
(62, 'd0479067-4782-41c3-9956-9682e0d529b3', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-19T18:43:29-07:00\",\"start_audit_id\":61}', '{\"ended_at\":\"2026-08-19T18:43:43-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 01:43:43'),
(63, 'bb777afc-be7f-46f9-a452-1155a417bcdc', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-19T18:45:07-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 01:45:07'),
(64, '29150328-558c-4791-9724-ec60ddf23c53', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-19T18:45:07-07:00\",\"start_audit_id\":63}', '{\"ended_at\":\"2026-08-19T19:13:50-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 02:13:50'),
(65, 'a787f4a9-0b45-48c6-b0d2-0365db1306b4', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-19T22:04:51-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 05:04:51'),
(66, 'f4478bd4-90a4-4d85-b8d3-41f4003e7a3a', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-19T22:04:51-07:00\",\"start_audit_id\":65}', '{\"ended_at\":\"2026-08-19T22:05:23-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 05:05:23'),
(67, 'f3cdc784-d4c8-4159-9c4a-f47f1d90eb50', 'administrator', 1, 1, 'client_portal.admin_access_started', 'App\\Models\\Client', 1, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-20T07:19:11-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 14:19:11'),
(68, '78745b70-4674-4797-94d5-06ce8ffb7215', 'administrator', 1, 1, 'client_portal.admin_access_ended', 'App\\Models\\Client', 1, '{\"started_at\":\"2026-08-20T07:19:11-07:00\",\"start_audit_id\":67}', '{\"ended_at\":\"2026-08-20T07:19:27-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-20 14:19:27'),
(69, 'c3629059-0375-4198-aae5-833b7cdcbcbc', 'administrator', 1, 2, 'client_portal.admin_access_started', 'App\\Models\\Client', 2, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-21T11:48:22-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-21 18:48:22'),
(70, '730852d5-5134-4aac-9be2-4a4d1835df96', 'administrator', 1, 2, 'client_portal.admin_access_ended', 'App\\Models\\Client', 2, '{\"started_at\":\"2026-08-21T11:48:22-07:00\",\"start_audit_id\":69}', '{\"ended_at\":\"2026-08-21T11:50:40-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-21 18:50:40'),
(71, '4af7bc55-592e-4a10-9028-7c689255d40d', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-21T14:17:43-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-21 21:17:43'),
(72, 'a992d118-6cbd-4418-8ea7-2a4c08ea8f88', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-21T19:47:15-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-22 02:47:15'),
(73, 'd7b67176-01ea-40a3-8d8b-6562d4cb5ea3', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-21T19:47:15-07:00\",\"start_audit_id\":72}', '{\"ended_at\":\"2026-08-21T19:47:25-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-22 02:47:25'),
(74, '245579a9-7035-4f82-af6e-774fc909a9d4', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-21T19:48:04-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-22 02:48:04'),
(75, '80c5a7df-9777-4be6-a5d0-c8be0ab4361e', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-22T07:02:23-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-22 14:02:23'),
(76, 'db990e3c-4ff0-42e8-9ed1-2e0d8a6ad719', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-22T07:02:23-07:00\",\"start_audit_id\":75}', '{\"ended_at\":\"2026-08-22T07:02:36-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-22 14:02:36'),
(77, '070a2c86-74ae-4697-b04c-d475f50012eb', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-22T07:03:12-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-22 14:03:12'),
(78, '9e04eb14-0a2d-4c85-a295-bfd20d846bc0', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-22T07:03:12-07:00\",\"start_audit_id\":77}', '{\"ended_at\":\"2026-08-22T10:54:50-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-22 17:54:50'),
(79, '035b4fbd-7598-4831-aa22-3fada0c84cb6', 'administrator', 1, NULL, 'payment_plan.amended', 'App\\Models\\PaymentPlan', 4, '{\"plan\":{\"plan_number\":\"333-18-440 (1)\",\"title\":\"Painted Rock Valle Vista North\",\"asset_description\":null,\"notes\":null,\"status\":\"paused\",\"plan_start_date\":\"2026-08-10T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":292100,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"id\":7,\"payment_plan_id\":4,\"frequency\":\"monthly\",\"invoice_day\":3,\"due_days_after_issue\":5,\"grace_days\":5,\"scheduled_payment_amount\":13500,\"monthly_service_fee\":1500,\"stage_one_enabled\":1,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":6,\"stage_two_enabled\":0,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":60,\"effective_from\":\"2026-08-11\",\"effective_to\":null,\"reason\":\"Property desription change\",\"created_by_user_id\":1,\"created_at\":\"2026-08-10 09:20:52\",\"updated_at\":\"2026-08-10 09:20:52\"}}', '{\"plan\":{\"plan_number\":\"333-18-440 (1)\",\"title\":\"Painted Rock Valle Vista North\",\"asset_description\":null,\"notes\":null,\"status\":\"paused\",\"plan_start_date\":\"2026-08-10T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":317000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"payment_plan_id\":4,\"frequency\":\"monthly\",\"invoice_day\":\"3\",\"due_days_after_issue\":\"5\",\"grace_days\":\"5\",\"scheduled_payment_amount\":13500,\"monthly_service_fee\":1500,\"stage_one_enabled\":true,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":6,\"stage_two_enabled\":false,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":\"60\",\"effective_from\":\"2026-08-23 00:00:00\",\"reason\":\"adjust import information\",\"created_by_user_id\":1,\"updated_at\":\"2026-08-22 11:06:42\",\"created_at\":\"2026-08-22 11:06:42\",\"id\":25},\"reason\":\"adjust import information\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-22 18:06:42'),
(80, '29eb3442-06df-406a-8ccd-f9c26b535a48', 'administrator', 1, NULL, 'payment_plan.amended', 'App\\Models\\PaymentPlan', 5, '{\"plan\":{\"plan_number\":\"333-18-162 (2)\",\"title\":\"Painted Rock Valle Vista South\",\"asset_description\":null,\"notes\":null,\"status\":\"paused\",\"plan_start_date\":\"2026-08-10T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":185100,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"id\":6,\"payment_plan_id\":5,\"frequency\":\"monthly\",\"invoice_day\":3,\"due_days_after_issue\":5,\"grace_days\":5,\"scheduled_payment_amount\":10000,\"monthly_service_fee\":0,\"stage_one_enabled\":1,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":6,\"stage_two_enabled\":0,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":60,\"effective_from\":\"2026-08-10\",\"effective_to\":null,\"reason\":null,\"created_by_user_id\":1,\"created_at\":\"2026-08-10 09:20:22\",\"updated_at\":\"2026-08-10 09:20:22\"}}', '{\"plan\":{\"plan_number\":\"333-18-162 (2)\",\"title\":\"Painted Rock Valle Vista South\",\"asset_description\":null,\"notes\":null,\"status\":\"paused\",\"plan_start_date\":\"2026-08-10T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":210000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"payment_plan_id\":5,\"frequency\":\"monthly\",\"invoice_day\":\"3\",\"due_days_after_issue\":\"5\",\"grace_days\":\"5\",\"scheduled_payment_amount\":10000,\"monthly_service_fee\":0,\"stage_one_enabled\":true,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":6,\"stage_two_enabled\":false,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":\"60\",\"effective_from\":\"2026-08-23 00:00:00\",\"reason\":\"correct import details\",\"created_by_user_id\":1,\"updated_at\":\"2026-08-22 13:01:47\",\"created_at\":\"2026-08-22 13:01:47\",\"id\":26},\"reason\":\"correct import details\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-22 20:01:47'),
(81, '42603448-ee8a-4dc7-9282-f1060dfd7086', 'administrator', 1, 2, 'client_portal.admin_access_started', 'App\\Models\\Client', 2, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-22T17:10:25-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-23 00:10:25'),
(82, 'f50087de-d791-4def-a2a5-4ea4acd1a520', 'administrator', 1, 2, 'client_portal.admin_access_ended', 'App\\Models\\Client', 2, '{\"started_at\":\"2026-08-22T17:10:25-07:00\",\"start_audit_id\":81}', '{\"ended_at\":\"2026-08-22T17:11:21-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-23 00:11:21'),
(83, 'bc85fbba-94af-454e-a8e9-2e9e5ecca037', 'administrator', 1, 2, 'client_portal.admin_access_started', 'App\\Models\\Client', 2, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-23T06:38:45-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-23 13:38:45'),
(84, 'cf55661d-d20a-4daf-b260-c5b5d39a9d3e', 'administrator', 1, 2, 'client_portal.admin_access_ended', 'App\\Models\\Client', 2, '{\"started_at\":\"2026-08-23T06:38:45-07:00\",\"start_audit_id\":83}', '{\"ended_at\":\"2026-08-23T11:08:46-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-23 18:08:46'),
(85, 'b5955a4b-7166-4216-9631-e8f393a5bf53', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-23T13:17:04-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-23 20:17:04'),
(86, '9efbebd8-2002-4b25-b0a8-6a44b8841431', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-23T13:17:04-07:00\",\"start_audit_id\":85}', '{\"ended_at\":\"2026-08-23T13:37:16-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-23 20:37:16'),
(87, '79764828-43e6-412d-8e32-85e5d126bf38', 'administrator', 1, 2, 'client_portal.admin_access_started', 'App\\Models\\Client', 2, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-23T13:39:27-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-23 20:39:27');
INSERT INTO `audit_logs` (`id`, `uuid`, `actor_type`, `actor_user_id`, `actor_client_id`, `event`, `auditable_type`, `auditable_id`, `before_values`, `after_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(88, 'e08da09e-7d4c-4332-a4a9-b351f73ebe0f', 'administrator', 1, 2, 'client_portal.admin_access_ended', 'App\\Models\\Client', 2, '{\"started_at\":\"2026-08-23T13:39:27-07:00\",\"start_audit_id\":87}', '{\"ended_at\":\"2026-08-23T17:23:27-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-24 00:23:27'),
(89, 'd26a4845-3e8a-4485-8506-ddd13759d7f9', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-25T09:12:31-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-25 16:12:31'),
(90, 'e48f29f1-d5dc-416e-a28f-e2298181f2ae', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-25T09:12:31-07:00\",\"start_audit_id\":89}', '{\"ended_at\":\"2026-08-25T09:12:59-07:00\"}', '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', '2026-08-25 16:12:59'),
(91, '6e3033f3-bbb9-4fd1-adbb-22ef019293e3', 'administrator', 1, NULL, 'administrator.logged_out_all_devices', 'App\\Models\\User', 1, NULL, NULL, '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-27 16:24:54'),
(92, '0ead2cba-b778-4d7a-8b76-c8f4b3e02fb5', 'administrator', 1, NULL, 'administrator.logged_out_all_devices', 'App\\Models\\User', 1, NULL, NULL, '69.24.120.94', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-27 16:34:28'),
(93, '18969a4e-bdf9-458b-9465-953590a7a717', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-28T14:56:15-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-28 21:56:15'),
(94, 'cdab60c4-8e39-4349-814b-6b1446458170', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-28T14:56:15-07:00\",\"start_audit_id\":93}', '{\"ended_at\":\"2026-08-28T14:56:46-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-28 21:56:46'),
(95, '193fdac8-753f-4e4f-9385-df49f57c3a43', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-28T15:03:01-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-28 22:03:01'),
(96, '4021bdc9-d971-490c-9835-4ca17acc3060', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-28T15:03:01-07:00\",\"start_audit_id\":95}', '{\"ended_at\":\"2026-08-28T15:03:21-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-28 22:03:21'),
(97, 'f4807801-2c1c-4797-89a9-dd0d331d7f9f', 'administrator', 1, NULL, 'payment_plan.amended', 'App\\Models\\PaymentPlan', 17, '{\"plan\":{\"plan_number\":\"Testprop1\",\"title\":\"1.14 Imaginary Acres of land\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-11T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":250000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"id\":21,\"payment_plan_id\":17,\"frequency\":\"monthly\",\"invoice_day\":3,\"due_days_after_issue\":5,\"grace_days\":4,\"scheduled_payment_amount\":10000,\"monthly_service_fee\":1500,\"stage_one_enabled\":1,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":5,\"stage_two_enabled\":0,\"stage_two_fee_type\":null,\"stage_two_fixed_amount\":null,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":null,\"default_eligibility_days\":60,\"effective_from\":\"2026-08-12\",\"effective_to\":null,\"reason\":\"Test mode enabled\",\"created_by_user_id\":1,\"created_at\":\"2026-08-11 09:52:09\",\"updated_at\":\"2026-08-11 09:52:09\"}}', '{\"plan\":{\"plan_number\":\"Testprop1\",\"title\":\"1.14 Imaginary Acres of land\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-11T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":250000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"payment_plan_id\":17,\"frequency\":\"monthly\",\"invoice_day\":\"3\",\"due_days_after_issue\":\"3\",\"grace_days\":\"2\",\"scheduled_payment_amount\":10000,\"monthly_service_fee\":1500,\"stage_one_enabled\":true,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":3,\"stage_two_enabled\":true,\"stage_two_fee_type\":\"fixed\",\"stage_two_fixed_amount\":5000,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":\"6\",\"default_eligibility_days\":\"60\",\"effective_from\":\"2026-08-29 00:00:00\",\"reason\":\"adjust late fees\",\"created_by_user_id\":1,\"updated_at\":\"2026-08-28 15:04:33\",\"created_at\":\"2026-08-28 15:04:33\",\"id\":27},\"reason\":\"adjust late fees\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-28 22:04:33'),
(98, '7e5422a3-8fbe-4aee-b78e-4728817ced40', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-28T15:04:40-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-28 22:04:40'),
(99, '12aa07a0-1d2a-4f95-ba62-cfc7fe362b5e', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-28T15:08:44-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-28 22:08:44'),
(100, '776d14b6-27ee-48c7-9117-75eaba8a961e', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-28T15:08:44-07:00\",\"start_audit_id\":99}', '{\"ended_at\":\"2026-08-28T15:11:43-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-28 22:11:43'),
(101, '3ae3aca8-e5ad-4205-8dd7-eefe09420fcb', 'administrator', 1, NULL, 'payment_plan.amended', 'App\\Models\\PaymentPlan', 17, '{\"plan\":{\"plan_number\":\"Testprop1\",\"title\":\"1.14 Imaginary Acres of land\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-11T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":250000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"id\":27,\"payment_plan_id\":17,\"frequency\":\"monthly\",\"invoice_day\":3,\"due_days_after_issue\":3,\"grace_days\":2,\"scheduled_payment_amount\":10000,\"monthly_service_fee\":1500,\"stage_one_enabled\":1,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":3,\"stage_two_enabled\":1,\"stage_two_fee_type\":\"fixed\",\"stage_two_fixed_amount\":5000,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":6,\"default_eligibility_days\":60,\"effective_from\":\"2026-08-29\",\"effective_to\":null,\"reason\":\"adjust late fees\",\"created_by_user_id\":1,\"created_at\":\"2026-08-28 15:04:33\",\"updated_at\":\"2026-08-28 15:04:33\"}}', '{\"plan\":{\"plan_number\":\"Testprop1\",\"title\":\"1.14 Imaginary Acres of land\",\"asset_description\":null,\"notes\":null,\"status\":\"active\",\"plan_start_date\":\"2026-08-11T07:00:00.000000Z\",\"first_payment_amount\":null,\"first_due_date\":null,\"purchase_price\":250000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"billing_terms\":{\"payment_plan_id\":17,\"frequency\":\"monthly\",\"invoice_day\":\"3\",\"due_days_after_issue\":\"3\",\"grace_days\":\"1\",\"scheduled_payment_amount\":10000,\"monthly_service_fee\":1500,\"stage_one_enabled\":true,\"stage_one_fee_type\":\"fixed\",\"stage_one_fixed_amount\":2500,\"stage_one_percentage_rate\":null,\"stage_one_minimum_amount\":0,\"stage_one_days_late\":2,\"stage_two_enabled\":true,\"stage_two_fee_type\":\"fixed\",\"stage_two_fixed_amount\":5000,\"stage_two_percentage_rate\":null,\"stage_two_minimum_amount\":0,\"stage_two_days_late\":\"6\",\"default_eligibility_days\":\"60\",\"effective_from\":\"2026-08-29 00:00:00\",\"reason\":\"change grace days\",\"created_by_user_id\":1,\"updated_at\":\"2026-08-28 15:13:24\",\"created_at\":\"2026-08-28 15:13:24\",\"id\":28},\"reason\":\"change grace days\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-28 22:13:24'),
(102, '30380eca-3f5c-41c4-8e97-28ee807aec0c', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-30T14:56:41-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-30 21:56:41'),
(103, '2480370b-3264-41ec-9c0e-6ac49a4c66b4', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-30T14:56:41-07:00\",\"start_audit_id\":102}', '{\"ended_at\":\"2026-08-30T14:56:43-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-30 21:56:43'),
(104, '8f235033-6bcc-467d-99c1-05b5313c7b48', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-30T14:56:46-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-30 21:56:46'),
(105, '5379709a-85b8-4c81-8a1d-9e21efe82f95', 'administrator', 1, 16, 'client_portal.admin_access_ended', 'App\\Models\\Client', 16, '{\"started_at\":\"2026-08-30T14:56:46-07:00\",\"start_audit_id\":104}', '{\"ended_at\":\"2026-08-30T14:57:29-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-30 21:57:29'),
(106, '9befb740-5c79-49ce-9076-ac8802f75081', 'administrator', 1, 16, 'client_portal.admin_access_started', 'App\\Models\\Client', 16, NULL, '{\"mode\":\"read_only\",\"started_at\":\"2026-08-30T22:50:41-07:00\"}', '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', '2026-08-31 05:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `billing_defaults`
--

CREATE TABLE `billing_defaults` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `frequency` varchar(24) NOT NULL DEFAULT 'monthly',
  `invoice_day` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `due_days_after_issue` smallint(5) UNSIGNED NOT NULL DEFAULT 5,
  `grace_days` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `scheduled_payment_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `monthly_service_fee` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `stage_one_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `stage_one_fee_type` varchar(24) DEFAULT NULL,
  `stage_one_fixed_amount` bigint(20) UNSIGNED DEFAULT NULL,
  `stage_one_percentage_rate` decimal(7,4) DEFAULT NULL,
  `stage_one_minimum_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `stage_one_days_late` smallint(5) UNSIGNED DEFAULT NULL,
  `stage_two_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `stage_two_fee_type` varchar(24) DEFAULT NULL,
  `stage_two_fixed_amount` bigint(20) UNSIGNED DEFAULT NULL,
  `stage_two_percentage_rate` decimal(7,4) DEFAULT NULL,
  `stage_two_minimum_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `stage_two_days_late` smallint(5) UNSIGNED DEFAULT NULL,
  `default_eligibility_days` smallint(5) UNSIGNED NOT NULL DEFAULT 60,
  `reminder_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reminder_settings`)),
  `updated_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `client_type` varchar(24) NOT NULL DEFAULT 'individual',
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `preferred_name` varchar(100) DEFAULT NULL,
  `organization_name` varchar(180) DEFAULT NULL,
  `email` varchar(254) DEFAULT NULL,
  `primary_phone` varchar(32) DEFAULT NULL,
  `secondary_phone` varchar(32) DEFAULT NULL,
  `address_line_1` varchar(150) DEFAULT NULL,
  `address_line_2` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state_region` varchar(100) DEFAULT NULL,
  `postal_code` varchar(24) DEFAULT NULL,
  `country_code` char(2) NOT NULL DEFAULT 'US',
  `status` varchar(24) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `updated_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `uuid`, `client_type`, `first_name`, `middle_name`, `last_name`, `preferred_name`, `organization_name`, `email`, `primary_phone`, `secondary_phone`, `address_line_1`, `address_line_2`, `city`, `state_region`, `postal_code`, `country_code`, `status`, `notes`, `created_by_user_id`, `updated_by_user_id`, `archived_at`, `created_at`, `updated_at`) VALUES
(1, '76fb9b83-a29f-40b9-bb67-6628cfce7df6', 'individual', 'Ernest', NULL, 'Hayes', NULL, NULL, 'ernesth33jr@gmail.com', '802-310-7857', NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-09 23:02:37', '2026-08-13 20:41:22'),
(2, '117fc3cf-8a87-4403-985b-5109be0a0c76', 'individual', 'Tami', NULL, 'McCarthy', NULL, NULL, 'tamiwicchick@aol.com', '6192410275', NULL, '5630 S. Hwy 95, Ste 5', NULL, 'Fort Mohave', 'AZ', '86426-6041', 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 12:46:44', '2026-08-21 15:47:57'),
(3, '1abcc600-4470-461b-b69e-383ac34e4660', 'individual', 'Obadiah and Angel', NULL, 'Israel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 13:09:35', '2026-08-10 13:09:35'),
(4, '5c816150-e754-47fd-843a-8ef4db915f02', 'individual', 'Kelly - Michael W', NULL, 'Kelly', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 13:14:44', '2026-08-10 13:14:44'),
(5, '9884015f-d33e-4097-b0af-192b204c6cc3', 'individual', 'Vanessa', NULL, 'Forbes', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 13:29:59', '2026-08-10 13:29:59'),
(6, '6dfe7962-0bd0-4c8a-a70e-3cdb788fb507', 'individual', 'Fredy', NULL, 'Contreras', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, '2026-08-10 19:24:22', '2026-08-10 13:34:23', '2026-08-10 19:24:22'),
(7, 'e3bcbee2-9542-4382-9264-a77945cc07e0', 'individual', 'Jonathon', NULL, 'Mastrangelo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 13:35:17', '2026-08-10 13:35:17'),
(8, '37bfc0e3-7a12-4e23-8823-cbda89075b29', 'individual', 'Darcy', NULL, 'Riffe', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 13:48:06', '2026-08-10 13:48:06'),
(9, '03877e3a-97d8-4c1f-9934-0608be0a1bd1', 'individual', 'Jacob', NULL, 'Coker', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 13:51:09', '2026-08-10 13:51:09'),
(10, '44c0d389-ee18-4bdb-9e0b-6ededa653c92', 'individual', 'Chris', NULL, 'Osorio', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 13:58:34', '2026-08-10 13:58:34'),
(11, 'fad48205-d020-4f8c-85a5-4503d2345a0e', 'individual', 'James', NULL, 'Klosterman', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 14:00:05', '2026-08-10 14:00:05'),
(12, '28306515-6977-4da0-8325-b8e4701caf0e', 'individual', 'Yvonne', NULL, 'Harris', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 14:01:45', '2026-08-10 14:01:45'),
(13, 'b38cc9a7-ab52-4ccc-b880-f5b2e0072c9c', 'individual', 'Nick', NULL, 'Sanchez', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 14:02:59', '2026-08-10 14:02:59'),
(14, '5733df94-528d-47a5-9da7-1ea2e7cbb2c6', 'individual', 'Gilbert', NULL, 'Burciaga', 'BCP Contractors', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 14:04:33', '2026-08-10 14:35:10'),
(15, '4efd5903-607c-4ae9-9cbf-29cece056850', 'individual', 'Edgar', NULL, 'Diaz', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-10 14:08:47', '2026-08-10 14:08:47'),
(16, 'a032b7c6-a955-4fe6-b509-59e277ed34df', 'individual', 'Joyce', NULL, 'Costa', NULL, NULL, 'joygr8@yahoo.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'US', 'active', NULL, 1, 1, NULL, '2026-08-11 13:50:46', '2026-08-11 13:50:46'),
(17, '8abeda0d-d933-4b37-b72f-1bc293507d34', 'individual', 'Chris', NULL, 'Costa', NULL, NULL, 'chris@mohavedeals.com', NULL, NULL, NULL, NULL, NULL, NULL, '86426', 'US', 'active', NULL, 1, 1, NULL, '2026-08-11 17:48:37', '2026-08-30 21:48:57');

-- --------------------------------------------------------

--
-- Table structure for table `client_change_requests`
--

CREATE TABLE `client_change_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `portal_account_id` bigint(20) UNSIGNED NOT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`changes`)),
  `status` varchar(24) NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_change_requests`
--

INSERT INTO `client_change_requests` (`id`, `client_id`, `portal_account_id`, `changes`, `status`, `reviewed_by_user_id`, `reviewed_at`, `admin_note`, `created_at`, `updated_at`) VALUES
(1, 2, 4, '{\"primary_phone\":{\"from\":null,\"to\":\"6192410275\"},\"address_line_1\":{\"from\":null,\"to\":\"5630 S. Hwy 95, Ste 5\"},\"address_line_2\":{\"from\":null,\"to\":\"5\"},\"city\":{\"from\":null,\"to\":\"Fort Mohave\"},\"state_region\":{\"from\":null,\"to\":\"AZ\"},\"postal_code\":{\"from\":null,\"to\":\"86426-6041\"}}', 'applied', 1, '2026-08-21 15:47:45', NULL, '2026-08-21 15:03:07', '2026-08-21 15:47:45');

-- --------------------------------------------------------

--
-- Table structure for table `client_contacts`
--

CREATE TABLE `client_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_general_contact` tinyint(1) NOT NULL DEFAULT 0,
  `is_emergency_contact` tinyint(1) NOT NULL DEFAULT 0,
  `is_continuity_contact` tinyint(1) NOT NULL DEFAULT 0,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `email` varchar(254) DEFAULT NULL,
  `primary_phone` varchar(32) DEFAULT NULL,
  `secondary_phone` varchar(32) DEFAULT NULL,
  `address_line_1` varchar(150) DEFAULT NULL,
  `address_line_2` varchar(150) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state_region` varchar(100) DEFAULT NULL,
  `postal_code` varchar(24) DEFAULT NULL,
  `country_code` char(2) NOT NULL DEFAULT 'US',
  `preferred_contact_method` varchar(24) DEFAULT NULL,
  `priority` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `permission_scope` varchar(24) NOT NULL DEFAULT 'contact_only',
  `status` varchar(24) NOT NULL DEFAULT 'active',
  `effective_from` date NOT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `end_reason` varchar(255) DEFAULT NULL,
  `replaced_by_contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_payment_intents`
--

CREATE TABLE `client_payment_intents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `portal_account_id` bigint(20) UNSIGNED NOT NULL,
  `method` varchar(40) NOT NULL,
  `amount` bigint(20) UNSIGNED NOT NULL,
  `base_amount` bigint(20) UNSIGNED DEFAULT NULL,
  `processing_fee_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `card_type` varchar(20) DEFAULT NULL,
  `payment_type` varchar(30) NOT NULL DEFAULT 'regular',
  `overpayment_disposition` varchar(30) DEFAULT NULL,
  `client_reference` varchar(150) DEFAULT NULL,
  `client_note` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'announced',
  `provider` varchar(20) DEFAULT NULL,
  `provider_checkout_id` varchar(190) DEFAULT NULL,
  `provider_payment_id` varchar(190) DEFAULT NULL,
  `checkout_url` text DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `payment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_payment_intents`
--

INSERT INTO `client_payment_intents` (`id`, `uuid`, `payment_plan_id`, `client_id`, `portal_account_id`, `method`, `amount`, `base_amount`, `processing_fee_amount`, `card_type`, `payment_type`, `overpayment_disposition`, `client_reference`, `client_note`, `status`, `provider`, `provider_checkout_id`, `provider_payment_id`, `checkout_url`, `expires_at`, `payment_id`, `received_at`, `cancelled_at`, `created_at`, `updated_at`) VALUES
(1, 'ba95fcaf-46d1-49fd-936a-3b79c10874a9', 18, 17, 1, 'card', 5000, NULL, 0, NULL, 'regular', 'principal', NULL, NULL, 'announced', NULL, NULL, NULL, NULL, '2026-08-25 17:59:04', NULL, NULL, NULL, '2026-08-11 17:59:04', '2026-08-11 17:59:04'),
(2, '71e9e36b-8374-4ef0-92eb-3f5b77c0f364', 18, 17, 1, 'card', 7500, NULL, 0, NULL, 'regular', 'principal', NULL, NULL, 'failed', 'square', NULL, NULL, NULL, '2026-08-25 18:20:03', NULL, NULL, NULL, '2026-08-11 18:20:03', '2026-08-11 18:20:03'),
(3, 'e049cee3-9f9f-4b6c-9857-2a0e31a53532', 18, 17, 1, 'card', 7500, NULL, 0, NULL, 'regular', 'principal', NULL, NULL, 'received', 'square', '4k0xZ4rLealqvCiq86Ezpmt6xIEZY', 'RS5climqc9lOvAQA0e9ipaoWCJPZY', 'https://sandbox.square.link/u/uFuRKNQy', '2026-08-12 18:21:23', 1, '2026-08-11 18:21:46', NULL, '2026-08-11 18:21:22', '2026-08-11 18:21:46'),
(4, '8b83044c-1628-4dd1-b4b2-6168e34d785a', 18, 17, 1, 'card', 13500, NULL, 0, NULL, 'regular', 'principal', NULL, 'This is for the first payment', 'received', 'square', '6XptehrVmX2dkEplpScsgHuCTOQZY', '1MDdi7er1uMEgXKQmPMBWECx5mJZY', 'https://sandbox.square.link/u/Tf7JQp01', '2026-08-12 18:31:44', 2, '2026-08-11 18:31:55', NULL, '2026-08-11 18:31:43', '2026-08-11 18:31:55'),
(5, '4cd96bee-865f-4320-bad0-41c2d7180125', 18, 17, 1, 'card', 4500, NULL, 0, NULL, 'regular', 'principal', NULL, NULL, 'received', 'stripe', 'cs_test_a1NiaooWyiec2rq4m86rruegUAHHvYL5KKM4qcQtyb48GthLTixz4gVGzQ', 'pi_3U3NkLIC2f6Um3lL01Z6jg9s', 'https://checkout.stripe.com/c/pay/cs_test_a1NiaooWyiec2rq4m86rruegUAHHvYL5KKM4qcQtyb48GthLTixz4gVGzQ#fidnandhYHdWcXxpYCc%2FJ2FgY2RwaXEnKSdicGRmZGhqaWBTZHdsZGtxJz8nZmprcXdqaScpJ2R1bE5gfCc%2FJ3VuWnFgdnFaMDROaFRrQExGN2MzUGg2aUl1X1VMNnRVdlU2XG1%2FYklsfWBwdktMZzYwUU1iMWFscTVNVEtSX2lPTDRna2A8T3ZNRGE8VjZcb19iSn82PXx1XDRmXVNvUVw1NVM9QjFIamFcJyknY3dqaFZgd3Ngdyc%2FcXdwYCknZ2RmbmJ3anBrYUZqaWp3Jz8nJmNjY2NjYycpJ2lkfGpwcVF8dWAnPyd2bGtiaWBabHFgaCcpJ2BrZGdpYFVpZGZgbWppYWB3dic%2FcXdwYHgl', '2026-08-12 19:02:09', 3, '2026-08-11 19:03:24', NULL, '2026-08-11 19:02:07', '2026-08-11 19:03:24'),
(6, 'b621dbc2-5b59-4cda-9b19-3ceba74685f2', 18, 17, 1, 'card', 100, NULL, 0, NULL, 'regular', 'principal', NULL, 'Testing square', 'received', 'square', '8GDk5rJWQzv5HXEfmmZmq0ZWDeAZY', 'R610ZdDwlaCbSCbz3BJVtGW5NcRZY', 'https://square.link/u/Few9Cnc0', '2026-08-12 19:09:57', 4, '2026-08-11 19:10:55', NULL, '2026-08-11 19:09:57', '2026-08-11 19:10:55'),
(7, '2dbb21d2-5768-4312-86c2-afb2c009b116', 18, 17, 1, 'card', 110, NULL, 0, NULL, 'regular', 'principal', NULL, 'testing stripe', 'announced', NULL, NULL, NULL, NULL, '2026-08-25 19:12:11', NULL, NULL, NULL, '2026-08-11 19:12:11', '2026-08-11 19:12:11'),
(8, '630b929d-b20c-4b1b-afc0-e50d085a7ed9', 18, 17, 1, 'card', 110, NULL, 0, NULL, 'regular', 'principal', NULL, 'testing stripe', 'announced', NULL, NULL, NULL, NULL, '2026-08-25 19:13:34', NULL, NULL, NULL, '2026-08-11 19:13:34', '2026-08-11 19:13:34'),
(9, 'b926e7a8-d518-40b0-8a1f-90734a2112d8', 18, 17, 1, 'card', 110, NULL, 0, NULL, 'regular', 'principal', NULL, 'testing stripe', 'announced', NULL, NULL, NULL, NULL, '2026-08-25 19:34:14', NULL, NULL, NULL, '2026-08-11 19:34:14', '2026-08-11 19:34:14'),
(10, '96d04bd7-46be-40cf-82b5-1426f3c7fb63', 18, 17, 1, 'card', 65, NULL, 0, NULL, 'regular', 'principal', NULL, NULL, 'received', 'stripe', 'cs_live_a1uqx2tcELK0wro9AyZnHQTBfLwKgRdrGog1cEFloNdg5i9qDMlq83xc1Z', 'pi_3U3OOwIC2f6Um3lL0BfiHwu3', 'https://checkout.stripe.com/c/pay/cs_live_a1uqx2tcELK0wro9AyZnHQTBfLwKgRdrGog1cEFloNdg5i9qDMlq83xc1Z#fidnandhYHdWcXxpYCc%2FJ2FgY2RwaXEnKSdicGRmZGhqaWBTZHdsZGtxJz8nZmprcXdqaScpJ2R1bE5gfCc%2FJ3VuWmlsc2BaMDROaFRrQExGN2MzUGg2aUlIY0RndFRAYHREfFZuNnBEVWpMa2ZTUzJxbDNLXH9tdXZARn01Rnd1ajVJQk5UPGNiaTBCR24xNTZBUDxvaXRpTERGSU09Yn01NUxRaFxQXW0xJyknY3dqaFZgd3Ngdyc%2FcXdwYCknZ2RmbmJ3anBrYUZqaWp3Jz8nJmNjY2NjYycpJ2lkfGpwcVF8dWAnPyd2bGtiaWBabHFgaCcpJ2BrZGdpYFVpZGZgbWppYWB3dic%2FcXdwYHgl', '2026-08-12 19:44:06', 5, '2026-08-11 19:45:30', NULL, '2026-08-11 19:44:05', '2026-08-11 19:45:30'),
(11, 'bd11281d-3030-487b-a838-daaa7f0ba389', 17, 16, 2, 'zelle', 11500, NULL, 0, NULL, 'regular', NULL, NULL, NULL, 'received', NULL, NULL, NULL, NULL, '2026-08-26 20:40:54', 6, '2026-08-12 20:54:56', NULL, '2026-08-12 20:40:54', '2026-08-12 20:54:56'),
(12, '52a44bd9-0931-4b0b-9016-0842a2424c3c', 17, 16, 2, 'zelle', 11500, NULL, 0, NULL, 'regular', NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL, '2026-08-26 20:44:41', NULL, NULL, '2026-08-12 20:55:55', '2026-08-12 20:44:41', '2026-08-12 20:55:55'),
(13, 'ea5b4593-768b-4adf-a700-a3e362eb0e60', 17, 16, 2, 'zelle', 11500, NULL, 0, NULL, 'regular', NULL, NULL, NULL, 'received', NULL, NULL, NULL, NULL, '2026-08-27 17:13:22', 7, '2026-08-13 18:35:24', NULL, '2026-08-13 17:13:22', '2026-08-13 18:35:24'),
(14, 'e1948323-a1df-49fd-b510-4fa7fd5661b3', 17, 16, 2, 'zelle', 14500, NULL, 0, NULL, 'regular', 'principal', NULL, NULL, 'received', NULL, NULL, NULL, NULL, '2026-08-28 17:43:33', 8, '2026-08-14 22:55:30', NULL, '2026-08-14 17:43:33', '2026-08-14 22:55:30'),
(15, 'e7f6ce91-490a-46fa-8344-13abfc45be71', 17, 16, 2, 'card', 11500, NULL, 0, NULL, 'regular', NULL, NULL, NULL, 'checkout_pending', 'square', 'sMCUNm2QH0HBEa0OKdqKrPdnODFZY', NULL, 'https://square.link/u/PMEzz6yX', '2026-08-16 16:37:08', NULL, NULL, NULL, '2026-08-15 16:37:08', '2026-08-15 16:37:08'),
(16, '57c9b105-1fb7-402d-b92c-9a9f5773b28e', 17, 16, 2, 'venmo', 11500, NULL, 0, NULL, 'regular', NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL, '2026-08-29 16:39:46', NULL, NULL, '2026-08-15 21:05:38', '2026-08-15 16:39:46', '2026-08-15 21:05:38'),
(17, '0a0f6693-95a5-4360-839e-1d05aa147dd9', 17, 16, 2, 'zelle', 23000, NULL, 0, NULL, 'regular', 'next_invoice_credit', NULL, NULL, 'received', NULL, NULL, NULL, NULL, '2026-08-29 16:45:18', 9, '2026-08-15 21:05:23', NULL, '2026-08-15 16:45:18', '2026-08-15 21:05:23'),
(18, 'feb30ec0-7776-4cd9-91f2-4e149239d4a3', 17, 16, 2, 'zelle', 12500, NULL, 0, NULL, 'regular', 'next_invoice_credit', NULL, NULL, 'received', NULL, NULL, NULL, NULL, '2026-08-31 17:34:29', 11, '2026-08-17 18:02:48', NULL, '2026-08-17 17:34:29', '2026-08-17 18:02:48'),
(19, 'dc012f38-6a27-4d20-b45d-a07a9c3ffd64', 17, 16, 2, 'zelle', 10500, NULL, 0, NULL, 'regular', NULL, NULL, NULL, 'received', NULL, NULL, NULL, NULL, '2026-09-01 15:28:03', 12, '2026-08-18 16:40:13', NULL, '2026-08-18 15:28:03', '2026-08-18 16:40:13'),
(20, 'bd617121-e6c6-43e0-bd82-11683bc0305f', 17, 16, 2, 'zelle', 5000, NULL, 0, NULL, 'regular', NULL, NULL, NULL, 'received', NULL, NULL, NULL, NULL, '2026-09-03 21:53:04', 13, '2026-08-21 12:33:59', NULL, '2026-08-20 21:53:04', '2026-08-21 12:33:59'),
(21, '067e06ab-b33e-4de4-82cc-614ebbefc389', 17, 16, 2, 'zelle', 11500, NULL, 0, NULL, 'regular', NULL, NULL, NULL, 'received', NULL, NULL, NULL, NULL, '2026-09-06 17:12:43', 16, '2026-08-23 17:16:41', NULL, '2026-08-23 17:12:43', '2026-08-23 17:16:41'),
(22, '3a5e0ff8-3802-4b96-aa81-980fa841a260', 17, 16, 2, 'zelle', 12700, NULL, 0, NULL, 'regular', NULL, NULL, NULL, 'received', NULL, NULL, NULL, NULL, '2026-09-07 00:24:18', 17, '2026-08-24 00:24:36', NULL, '2026-08-24 00:24:18', '2026-08-24 00:24:36'),
(23, '5891244c-7d05-46c6-ab9e-b68c1960a3aa', 17, 16, 2, 'zelle', 11500, NULL, 0, NULL, 'regular', NULL, NULL, NULL, 'received', NULL, NULL, NULL, NULL, '2026-09-07 14:12:16', 18, '2026-08-27 13:24:38', NULL, '2026-08-24 14:12:16', '2026-08-27 13:24:38'),
(24, 'df6f4138-1c4a-4ff9-84b2-ce610c838365', 18, 17, 1, 'card', 100, 100, 0, NULL, 'regular', 'principal', NULL, NULL, 'checkout_pending', 'square', 'K7GGxCcI3xB1FXA2WrtjZYLiOFFZY', NULL, 'https://square.link/u/spDuCdfe', '2026-08-31 18:58:01', NULL, NULL, NULL, '2026-08-30 18:58:00', '2026-08-30 18:58:01'),
(25, 'bd4bede8-9565-492f-bf5f-bd4cf34235b0', 18, 17, 1, 'card', 100, 100, 0, NULL, 'regular', 'principal', NULL, NULL, 'checkout_pending', 'square', 'KRnPAcvxZjn1QvhaHWuKu9UVSSEZY', NULL, 'https://square.link/u/mcWWMBBi', '2026-08-31 19:32:06', NULL, NULL, NULL, '2026-08-30 19:32:06', '2026-08-30 19:32:06'),
(26, '6e67fb87-8b10-455e-a058-738c2ba06a88', 18, 17, 1, 'card', 134, 100, 34, 'CREDIT', 'regular', 'principal', NULL, NULL, 'announced', 'square', NULL, NULL, NULL, '2026-09-13 21:55:56', NULL, NULL, NULL, '2026-08-30 21:55:56', '2026-08-30 21:55:56'),
(27, 'a63ad459-a4a1-4e40-95a5-795df3fe2880', 18, 17, 1, 'card', 134, 100, 34, 'CREDIT', 'regular', 'principal', NULL, NULL, 'failed', 'square', NULL, NULL, NULL, '2026-09-13 22:20:15', NULL, NULL, NULL, '2026-08-30 22:20:15', '2026-08-30 22:20:16'),
(28, 'b92ec642-3499-4ac1-ba4e-141cc91e4f53', 18, 17, 1, 'card', 134, 100, 34, 'CREDIT', 'regular', 'principal', NULL, NULL, 'failed', 'square', NULL, NULL, NULL, '2026-09-13 22:21:44', NULL, NULL, NULL, '2026-08-30 22:21:44', '2026-08-30 22:21:44'),
(29, 'fafdc022-d6cc-46bd-a8d2-e2a537a2588b', 18, 17, 1, 'card', 134, 100, 34, 'CREDIT', 'regular', 'principal', NULL, NULL, 'received', 'square', NULL, 'LJQzCKZSG2cGnsRnAahyTxbdQ58YY', NULL, '2026-09-13 22:23:11', 22, '2026-08-30 22:23:13', NULL, '2026-08-30 22:23:11', '2026-08-30 22:23:13'),
(30, '9376d63c-bc28-4084-b4ad-295e10f21702', 18, 17, 1, 'card', 100, 100, 0, 'DEBIT', 'regular', 'principal', NULL, NULL, 'received', 'square', NULL, 'NgFqdt9gdA5ISqCRthCxsF8Iq0aZY', NULL, '2026-09-13 22:27:46', 23, '2026-08-30 22:27:48', NULL, '2026-08-30 22:27:46', '2026-08-30 22:27:48'),
(31, '98ab8e58-f569-4489-9e33-d8fbfbdc6471', 18, 17, 1, 'zelle', 500, NULL, 0, NULL, 'regular', 'principal', NULL, NULL, 'announced', NULL, NULL, NULL, NULL, '2026-09-14 13:05:46', NULL, NULL, NULL, '2026-08-31 13:05:46', '2026-08-31 13:05:46');

-- --------------------------------------------------------

--
-- Table structure for table `contract_status_events`
--

CREATE TABLE `contract_status_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `event_type` varchar(32) NOT NULL,
  `effective_at` timestamp NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `administrator_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `system_eligibility_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`system_eligibility_details`)),
  `contract_balance_snapshot` bigint(20) UNSIGNED NOT NULL,
  `open_invoice_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `paid_in_value_snapshot` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `related_prior_event_id` bigint(20) UNSIGNED DEFAULT NULL,
  `idempotency_key` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_deliveries`
--

CREATE TABLE `email_deliveries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recipient_client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sent_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `template_slug` varchar(80) NOT NULL,
  `recipient_email` varchar(254) NOT NULL,
  `subject_snapshot` varchar(255) NOT NULL,
  `body_snapshot` longtext NOT NULL,
  `delivery_format` varchar(20) NOT NULL DEFAULT 'inline',
  `status` varchar(24) NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `failure_message` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_deliveries`
--

INSERT INTO `email_deliveries` (`id`, `invoice_id`, `payment_id`, `payment_plan_id`, `recipient_client_id`, `sent_by_user_id`, `template_slug`, `recipient_email`, `subject_snapshot`, `body_snapshot`, `delivery_format`, `status`, `sent_at`, `failed_at`, `failure_message`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, NULL, 17, 1, 'portal-invitation', 'chris@mohavedeals.com', 'Set up your Mohave Deals LandPay client portal', '<p>Hello Chris Costa,</p><p>You have been invited to securely access your payment-plan account.</p><p><a href=\"https://landpay.mohavedeals.com/portal/invitation/WA6VR2F1X1ON1mp3gsd10PTb7WuctHCTpfRzf4052evn6aykQ8QtMTDuNBBBGHBT\" style=\"display:inline-block;padding:12px 20px;background:#173f40;color:#ffffff;text-decoration:none;border-radius:4px\">Create my portal password</a></p><p>This single-use link expires August 13, 2026 1:48 PM MST. If you did not expect this invitation, you may ignore this email.</p>', 'inline', 'sent', '2026-08-11 17:48:40', NULL, NULL, '2026-08-11 17:48:39', '2026-08-11 17:48:40'),
(2, 6, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260812 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260812</strong> is ready. The amount due is <strong>$115.00</strong> by August 17, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/6\">http://landpay.mohavedeals.com/portal/invoices/6</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-12 10:00:03', NULL, NULL, '2026-08-12 10:00:03', '2026-08-12 10:00:03'),
(3, NULL, NULL, NULL, 16, 1, 'portal-invitation', 'joygr8@yahoo.com', 'Set up your Mohave Deals LandPay client portal', '<p>Hello Joyce Costa,</p><p>You have been invited to securely access your payment-plan account.</p><p><a href=\"https://landpay.mohavedeals.com/portal/invitation/nHSbWKFpl710dL1XmYC3pFqN01ckBrCBvU6p5M1ka7Q9LqWZgtVNjYyAAQKbzJaX\" style=\"display:inline-block;padding:12px 20px;background:#173f40;color:#ffffff;text-decoration:none;border-radius:4px\">Create my portal password</a></p><p>This single-use link expires August 14, 2026 12:44 PM MST. If you did not expect this invitation, you may ignore this email.</p>', 'inline', 'sent', '2026-08-12 16:44:24', NULL, NULL, '2026-08-12 16:44:24', '2026-08-12 16:44:24'),
(4, NULL, 6, 17, 16, 1, 'payment-receipt', 'joygr8@yahoo.com', 'Payment receipt for $115.00', '<p>Hello Joyce Costa,</p><p>Thank you. We received your payment of <strong>$115.00</strong> on August 12, 2026.</p><p>View this payment: <a href=\"https://landpay.mohavedeals.com/portal/payments/6\">https://landpay.mohavedeals.com/portal/payments/6</a></p><p>Your payment receipt is included below and attached as a PDF.</p><p>Visit your client portal: <a href=\"https://landpay.mohavedeals.com/portal\">https://landpay.mohavedeals.com/portal</a></p>', 'both', 'sent', '2026-08-12 20:55:03', NULL, NULL, '2026-08-12 20:55:03', '2026-08-12 20:55:03'),
(5, 7, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260813 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260813</strong> is ready. The amount due is <strong>$115.00</strong> by August 18, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/7\">http://landpay.mohavedeals.com/portal/invoices/7</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-13 10:00:03', NULL, NULL, '2026-08-13 10:00:03', '2026-08-13 10:00:03'),
(6, NULL, 7, 17, 16, 1, 'payment-receipt', 'joygr8@yahoo.com', 'Payment receipt for $115.00', '<p>Hello Joyce Costa,</p><p>Thank you. We received your payment of <strong>$115.00</strong> on August 13, 2026.</p><p>View this payment: <a href=\"https://landpay.mohavedeals.com/portal/payments/7\">https://landpay.mohavedeals.com/portal/payments/7</a></p><p>Your payment receipt is included below and attached as a PDF.</p><p>Visit your client portal: <a href=\"https://landpay.mohavedeals.com/portal\">https://landpay.mohavedeals.com/portal</a></p>', 'both', 'sent', '2026-08-13 18:35:24', NULL, NULL, '2026-08-13 18:35:24', '2026-08-13 18:35:24'),
(7, NULL, NULL, NULL, 1, 1, 'portal-invitation', 'ernesth33jr@gmail.com', 'Set up your Mohave Deals LandPay client portal', '<p>Hello Ernest Hayes,</p><p>You have been invited to securely access your payment-plan account.</p><p><a href=\"https://landpay.mohavedeals.com/portal/invitation/thN6LX3IczaJTKb8exLyczcIxIak5N6JkNmYyQMafKSssyeyPyWB8yTFrsbbabTR\" style=\"display:inline-block;padding:12px 20px;background:#173f40;color:#ffffff;text-decoration:none;border-radius:4px\">Create my portal password</a></p><p>This single-use link expires August 15, 2026 4:43 PM MST. If you did not expect this invitation, you may ignore this email.</p>', 'inline', 'sent', '2026-08-13 20:43:23', NULL, NULL, '2026-08-13 20:43:23', '2026-08-13 20:43:23'),
(8, 9, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260814 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260814</strong> is ready. The amount due is <strong>$115.00</strong> by August 19, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/9\">http://landpay.mohavedeals.com/portal/invoices/9</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-14 10:00:11', NULL, NULL, '2026-08-14 10:00:03', '2026-08-14 10:00:11'),
(9, NULL, 8, 17, 16, 1, 'payment-receipt', 'joygr8@yahoo.com', 'Payment receipt for $145.00', '<p>Hello Joyce Costa,</p><p>Thank you. We received your payment of <strong>$145.00</strong> on August 14, 2026.</p><p>View this payment: <a href=\"https://landpay.mohavedeals.com/portal/payments/8\">https://landpay.mohavedeals.com/portal/payments/8</a></p><p>Your payment receipt is included below and attached as a PDF.</p><p>Visit your client portal: <a href=\"https://landpay.mohavedeals.com/portal\">https://landpay.mohavedeals.com/portal</a></p>', 'both', 'sent', '2026-08-14 22:55:31', NULL, NULL, '2026-08-14 22:55:30', '2026-08-14 22:55:31'),
(10, 11, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260815 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260815</strong> is ready. The amount due is <strong>$115.00</strong> by August 20, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/11\">http://landpay.mohavedeals.com/portal/invoices/11</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-15 10:00:05', NULL, NULL, '2026-08-15 10:00:03', '2026-08-15 10:00:05'),
(11, NULL, 9, 17, 16, 1, 'payment-receipt', 'joygr8@yahoo.com', 'Payment receipt for $230.00', '<p>Hello Joyce Costa,</p><p>Thank you. We received your payment of <strong>$230.00</strong> on August 15, 2026.</p><p>View this payment: <a href=\"https://landpay.mohavedeals.com/portal/payments/9\">https://landpay.mohavedeals.com/portal/payments/9</a></p><p>Your payment receipt is included below and attached as a PDF.</p><p>Visit your client portal: <a href=\"https://landpay.mohavedeals.com/portal\">https://landpay.mohavedeals.com/portal</a></p>', 'both', 'sent', '2026-08-15 21:05:24', NULL, NULL, '2026-08-15 21:05:23', '2026-08-15 21:05:24'),
(12, 12, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260816 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260816</strong> is ready. The amount due is <strong>$0.00</strong> by August 21, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/12\">http://landpay.mohavedeals.com/portal/invoices/12</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-16 10:00:03', NULL, NULL, '2026-08-16 10:00:02', '2026-08-16 10:00:03'),
(13, 13, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260817 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260817</strong> is ready. The amount due is <strong>$115.00</strong> by August 22, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/13\">http://landpay.mohavedeals.com/portal/invoices/13</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-17 10:00:04', NULL, NULL, '2026-08-17 10:00:03', '2026-08-17 10:00:04'),
(14, NULL, NULL, NULL, 1, 1, 'portal-invitation', 'ernesth33jr@gmail.com', 'Set up your Mohave Deals LandPay client portal', '<p>Hello Ernest Hayes,</p><p>You have been invited to securely access your payment-plan account.</p><p><a href=\"https://landpay.mohavedeals.com/portal/invitation/eDgniq5IOZ9goaizJLHIwyNOSct2Ow5vXtaTg3HMapaY6KKR5FPKotNuMp9x8qk8\" style=\"display:inline-block;padding:12px 20px;background:#173f40;color:#ffffff;text-decoration:none;border-radius:4px\">Create my portal password</a></p><p>This single-use link expires August 19, 2026 6:48 AM MST. If you did not expect this invitation, you may ignore this email.</p>', 'inline', 'sent', '2026-08-17 10:48:16', NULL, NULL, '2026-08-17 10:48:15', '2026-08-17 10:48:16'),
(15, NULL, 11, 17, 16, 1, 'payment-receipt', 'joygr8@yahoo.com', 'Payment receipt for $125.00', '<p>Hello Joyce Costa,</p><p>Thank you. We received your payment of <strong>$125.00</strong> on August 17, 2026.</p><p>View this payment: <a href=\"https://landpay.mohavedeals.com/portal/payments/11\">https://landpay.mohavedeals.com/portal/payments/11</a></p><p>Your payment receipt is included below and attached as a PDF.</p><p>Visit your client portal: <a href=\"https://landpay.mohavedeals.com/portal\">https://landpay.mohavedeals.com/portal</a></p>', 'both', 'sent', '2026-08-17 18:02:48', NULL, NULL, '2026-08-17 18:02:48', '2026-08-17 18:02:48'),
(16, 14, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260818 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260818</strong> is ready. The amount due is <strong>$105.00</strong> by August 23, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/14\">http://landpay.mohavedeals.com/portal/invoices/14</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-18 10:00:03', NULL, NULL, '2026-08-18 10:00:03', '2026-08-18 10:00:03'),
(17, NULL, 12, 17, 16, 1, 'payment-receipt', 'joygr8@yahoo.com', 'Payment receipt for $105.00', '<p>Hello Joyce Costa,</p><p>Thank you. We received your payment of <strong>$105.00</strong> on August 18, 2026.</p><p>View this payment: <a href=\"https://landpay.mohavedeals.com/portal/payments/12\">https://landpay.mohavedeals.com/portal/payments/12</a></p><p>Your payment receipt is included below and attached as a PDF.</p><p>Visit your client portal: <a href=\"https://landpay.mohavedeals.com/portal\">https://landpay.mohavedeals.com/portal</a></p>', 'both', 'sent', '2026-08-18 16:40:13', NULL, NULL, '2026-08-18 16:40:13', '2026-08-18 16:40:13'),
(18, 15, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260819 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260819</strong> is ready. The amount due is <strong>$115.00</strong> by August 24, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/15\">http://landpay.mohavedeals.com/portal/invoices/15</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-19 10:00:03', NULL, NULL, '2026-08-19 10:00:02', '2026-08-19 10:00:03'),
(19, 16, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260820 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260820</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by August 25, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/16\">http://landpay.mohavedeals.com/portal/invoices/16</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-20 10:00:04', NULL, NULL, '2026-08-20 10:00:03', '2026-08-20 10:00:04'),
(20, NULL, NULL, NULL, 2, 1, 'portal-invitation', 'tamiwicchick@aol.com', 'Set up your Mohave Deals LandPay client portal', '<p>Hello Tami McCarthy,</p><p>You have been invited to securely access your payment-plan account.</p><p><a href=\"https://landpay.mohavedeals.com/portal/invitation/2FxAIro2VdYU4XDohESFuk1v2iLhYZCmJGcwvFIBqU0nFHh09wrTqxjqqVkgpM5W\" style=\"display:inline-block;padding:12px 20px;background:#173f40;color:#ffffff;text-decoration:none;border-radius:4px\">Create my portal password</a></p><p>This single-use link expires August 22, 2026 7:24 AM MST. If you did not expect this invitation, you may ignore this email.</p>', 'inline', 'sent', '2026-08-20 11:25:09', NULL, NULL, '2026-08-20 11:24:58', '2026-08-20 11:25:09'),
(21, 17, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260821 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260821</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by August 26, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/17\">http://landpay.mohavedeals.com/portal/invoices/17</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-21 10:00:08', NULL, NULL, '2026-08-21 10:00:03', '2026-08-21 10:00:08'),
(22, NULL, 13, 17, 16, 1, 'payment-receipt', 'joygr8@yahoo.com', 'Payment receipt for $50.00', '<p>Hello Joyce Costa,</p><p>Thank you. We received your payment of <strong>$50.00</strong> on August 21, 2026.</p><p>View this payment: <a href=\"https://landpay.mohavedeals.com/portal/payments/13\">https://landpay.mohavedeals.com/portal/payments/13</a></p><p>Your payment receipt is included below and attached as a PDF.</p><p>Visit your client portal: <a href=\"https://landpay.mohavedeals.com/portal\">https://landpay.mohavedeals.com/portal</a></p>', 'both', 'sent', '2026-08-21 12:34:00', NULL, NULL, '2026-08-21 12:33:59', '2026-08-21 12:34:00'),
(23, 18, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260822 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260822</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by August 27, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/18\">http://landpay.mohavedeals.com/portal/invoices/18</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-22 10:00:08', NULL, NULL, '2026-08-22 10:00:03', '2026-08-22 10:00:08'),
(24, 19, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260823 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260823</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by August 28, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/19\">http://landpay.mohavedeals.com/portal/invoices/19</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-23 10:00:04', NULL, NULL, '2026-08-23 10:00:03', '2026-08-23 10:00:04'),
(25, 20, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260824 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260824</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by August 29, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/20\">http://landpay.mohavedeals.com/portal/invoices/20</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-24 10:00:04', NULL, NULL, '2026-08-24 10:00:03', '2026-08-24 10:00:04'),
(26, 21, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260825 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260825</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by August 30, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/21\">http://landpay.mohavedeals.com/portal/invoices/21</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-25 10:00:03', NULL, NULL, '2026-08-25 10:00:03', '2026-08-25 10:00:03'),
(27, 22, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260826 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260826</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by August 31, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/22\">http://landpay.mohavedeals.com/portal/invoices/22</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-26 10:00:04', NULL, NULL, '2026-08-26 10:00:03', '2026-08-26 10:00:04'),
(28, 23, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260827 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260827</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by September 1, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/23\">http://landpay.mohavedeals.com/portal/invoices/23</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-27 10:00:04', NULL, NULL, '2026-08-27 10:00:03', '2026-08-27 10:00:04'),
(29, NULL, 18, 17, 16, 1, 'payment-receipt', 'joygr8@yahoo.com', 'Payment receipt for $115.00', '<p>Hello Joyce Costa,</p><p>Thank you. We received your payment of <strong>$115.00</strong> on August 27, 2026.</p><p>View this payment: <a href=\"https://landpay.mohavedeals.com/portal/payments/18\">https://landpay.mohavedeals.com/portal/payments/18</a></p><p>Your payment receipt is included below and attached as a PDF.</p><p>Visit your client portal: <a href=\"https://landpay.mohavedeals.com/portal\">https://landpay.mohavedeals.com/portal</a></p>', 'both', 'sent', '2026-08-27 13:24:38', NULL, NULL, '2026-08-27 13:24:38', '2026-08-27 13:24:38'),
(30, 24, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260828 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260828</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by September 2, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/24\">http://landpay.mohavedeals.com/portal/invoices/24</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-28 10:00:04', NULL, NULL, '2026-08-28 10:00:03', '2026-08-28 10:00:04'),
(31, 25, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260829 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260829</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by September 1, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/25\">http://landpay.mohavedeals.com/portal/invoices/25</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-29 10:00:03', NULL, NULL, '2026-08-29 10:00:02', '2026-08-29 10:00:03'),
(32, 26, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260830 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260830</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by September 2, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/26\">http://landpay.mohavedeals.com/portal/invoices/26</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-30 10:00:04', NULL, NULL, '2026-08-30 10:00:03', '2026-08-30 10:00:04'),
(33, 28, NULL, 17, 16, 1, 'invoice-email', 'joygr8@yahoo.com', 'Invoice INV-17-20260831 from Mohave Deals LandPay', '<p>Hello Joyce Costa,</p><p>Your invoice <strong>INV-17-20260831</strong> is ready. The amount due is <strong>$115.00</strong> and considered late by September 3, 2026.</p><p>View this invoice: <a href=\"http://landpay.mohavedeals.com/portal/invoices/28\">http://landpay.mohavedeals.com/portal/invoices/28</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-31 10:00:05', NULL, NULL, '2026-08-31 10:00:02', '2026-08-31 10:00:05'),
(34, 10, NULL, 18, 17, 1, 'invoice-email', 'chris@mohavedeals.com', 'Invoice INV-18-202608 from Mohave Deals LandPay', '<p>Hello Chris Costa,</p><p>Your invoice <strong>INV-18-202608</strong> is ready. The amount due is <strong>$0.00</strong> and considered late by August 19, 2026.</p><p>View this invoice: <a href=\"https://landpay.mohavedeals.com/portal/invoices/10\">https://landpay.mohavedeals.com/portal/invoices/10</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 'inline', 'sent', '2026-08-31 11:45:18', NULL, NULL, '2026-08-31 11:45:17', '2026-08-31 11:45:18');

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(80) NOT NULL,
  `name` varchar(120) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` longtext NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `slug`, `name`, `subject`, `body_html`, `active`, `created_at`, `updated_at`) VALUES
(1, 'payment-reminder', 'Payment reminder', 'Payment reminder for invoice {{ invoice_number }}', '<p>Hello {{ client_name }},</p><p>This is a friendly reminder that invoice <strong>{{ invoice_number }}</strong> is due with a balance of <strong>{{ amount_due }}</strong> and is late after {{ due_date }}.</p><p>View this invoice: <a href=\"{{ invoice_portal_url }}\">{{ invoice_portal_url }}</a></p><p><b>If payment has already been sent, please disregard this message.</b> Contact us if you have questions or need to discuss the account.</p><p>Visit your client portal: <a href=\"{{ client_portal_url }}\">{{ client_portal_url }}</a></p><p>{{ late_fee_notice }}</p>', 1, '2026-08-07 22:32:45', '2026-08-20 11:27:48'),
(2, 'invoice-email', 'Invoice email', 'Invoice {{ invoice_number }} from {{ company_name }}', '<p>Hello {{ client_name }},</p><p>Your invoice <strong>{{ invoice_number }}</strong> is ready. The amount due is <strong>{{ amount_due }}</strong> and considered late by {{ due_date }}.</p><p>View this invoice: <a href=\"{{ invoice_portal_url }}\">{{ invoice_portal_url }}</a></p><p>Invoice details are included with this email. Please contact us if you have any questions.</p>', 1, '2026-08-07 22:32:45', '2026-08-19 21:45:22'),
(3, 'payment-receipt', 'Payment receipt', 'Payment receipt for {{ payment_amount }}', '<p>Hello {{ client_name }},</p><p>Thank you. We received your payment of <strong>{{ payment_amount }}</strong> on {{ payment_date }}.</p><p>View this payment: <a href=\"{{ payment_portal_url }}\">{{ payment_portal_url }}</a></p><p>Your payment receipt is included below and attached as a PDF.</p><p>Visit your client portal: <a href=\"{{ client_portal_url }}\">{{ client_portal_url }}</a></p>', 1, '2026-08-07 22:32:45', '2026-08-11 16:12:22'),
(4, 'payment-reversal', 'Payment reversal notice', 'Payment reversal notice for {{ payment_amount }}', '<p>Hello {{ client_name }},</p><p>A payment of <strong>{{ payment_amount }}</strong> dated {{ payment_date }} was reversed by the plan administrator.</p><p>The earlier receipt should no longer be treated as valid. Please contact us if you have questions.</p><p>Visit your client portal: <a href=\"{{ client_portal_url }}\">{{ client_portal_url }}</a></p>', 1, '2026-08-07 22:32:45', '2026-08-11 16:13:52'),
(5, 'portal-invitation', 'Client portal invitation', 'Set up your {{ company_name }} client portal', '<p>Hello {{ client_name }},</p><p>You have been invited to securely access your payment-plan account.</p><p><a href=\"{{ invitation_link }}\" style=\"display:inline-block;padding:12px 20px;background:#173f40;color:#ffffff;text-decoration:none;border-radius:4px\">Create my portal password</a></p><p>This single-use link expires {{ invitation_expires }}. If you did not expect this invitation, you may ignore this email.</p>', 1, '2026-08-07 22:32:45', '2026-08-07 22:32:45');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_assessments`
--

CREATE TABLE `fee_assessments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `recurring_fee_rule_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_item_id` bigint(20) UNSIGNED NOT NULL,
  `financial_transaction_id` bigint(20) UNSIGNED NOT NULL,
  `period_key` varchar(20) NOT NULL,
  `effective_date` date NOT NULL,
  `amount` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_transactions`
--

CREATE TABLE `financial_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `gross_amount` bigint(20) UNSIGNED NOT NULL,
  `effective_date` date NOT NULL,
  `posted_at` timestamp NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `actor_type` varchar(24) NOT NULL,
  `posted_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `posted_by_client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `authorized_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `authorized_at` timestamp NULL DEFAULT NULL,
  `reversal_of_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `idempotency_key` varchar(100) DEFAULT NULL,
  `source_reference` varchar(150) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `financial_transactions`
--

INSERT INTO `financial_transactions` (`id`, `uuid`, `payment_plan_id`, `invoice_id`, `type`, `gross_amount`, `effective_date`, `posted_at`, `description`, `reason`, `actor_type`, `posted_by_user_id`, `posted_by_client_id`, `authorized_by_user_id`, `authorized_at`, `reversal_of_transaction_id`, `idempotency_key`, `source_reference`, `metadata`, `created_at`) VALUES
(1, 'e92f5921-e56c-4ece-8f2b-62f681807749', 1, NULL, 'opening_purchase_balance', 254900, '2024-12-23', '2026-08-10 00:42:02', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:378b4398-5c56-403d-a8e8-1ab360f71d31', NULL, '{\"purchase_price\":230000,\"documentation_fee_standard\":24900,\"documentation_fee_charged\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 00:42:02'),
(2, '0a1afe6b-3e34-48ca-b2a1-9f40b061cf59', 1, NULL, 'adjustment', 239900, '2026-08-10', '2026-08-10 00:58:38', 'Amount previously paid in adjustment', 'previously paid in credit', 'administrator', 1, NULL, NULL, NULL, NULL, NULL, NULL, '{\"previous_amount\":0,\"new_amount\":239900}', '2026-08-10 00:58:38'),
(3, '3bab573d-600a-44c2-bce4-af9dfa2da382', 1, 1, 'invoice_charge', 11500, '2025-01-03', '2026-08-10 10:00:02', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:92c23727-2a51-4e7d-b7fd-0d7dc798f234:scheduled-payment', NULL, NULL, '2026-08-10 10:00:02'),
(4, '3847f2ad-9285-46c6-be1a-35131032f7c9', 1, 1, 'recurring_fee', 1500, '2025-01-03', '2026-08-10 10:00:02', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:92c23727-2a51-4e7d-b7fd-0d7dc798f234:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-10 10:00:02'),
(5, '1b52ef39-f3cd-4305-bab1-0b59ba2c0d4d', 1, 2, 'invoice_charge', 3500, '2025-02-03', '2026-08-10 10:00:02', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:f37c0c04-071f-456e-9d7f-a594f6ea7319:scheduled-payment', NULL, NULL, '2026-08-10 10:00:02'),
(6, '75515285-e13f-43be-9bea-46d12a147b74', 1, 2, 'recurring_fee', 1500, '2025-02-03', '2026-08-10 10:00:02', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:f37c0c04-071f-456e-9d7f-a594f6ea7319:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-10 10:00:02'),
(7, '333a2350-2d41-4adc-a73c-1ee1af35d131', 1, 1, 'adjustment', 13000, '2026-08-10', '2026-08-10 11:42:20', 'Invoice deleted by administrator', 'incorrect date', 'administrator', 1, NULL, NULL, NULL, NULL, 'invoice:void:92c23727-2a51-4e7d-b7fd-0d7dc798f234', NULL, NULL, '2026-08-10 11:42:20'),
(8, '7f6a154e-1ffd-4910-8890-ab3f1f1010f0', 1, 2, 'adjustment', 5000, '2026-08-10', '2026-08-10 11:42:32', 'Invoice deleted by administrator', 'incorrect date', 'administrator', 1, NULL, NULL, NULL, NULL, 'invoice:void:f37c0c04-071f-456e-9d7f-a594f6ea7319', NULL, NULL, '2026-08-10 11:42:32'),
(9, 'd9e774c4-00c0-4992-999e-3edf6de55a84', 2, NULL, 'opening_purchase_balance', 1674900, '2026-08-10', '2026-08-10 12:50:07', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:3cfbb76b-e363-4a00-8eae-f590a658d273', NULL, '{\"purchase_price\":1650000,\"documentation_fee_standard\":24900,\"documentation_fee_charged\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 12:50:07'),
(10, 'd67cd131-aafe-4e28-aa3e-48cb9a7304e6', 2, NULL, 'opening_principal_credit', 606300, '2026-08-10', '2026-08-10 12:50:07', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:3cfbb76b-e363-4a00-8eae-f590a658d273', NULL, '{\"opening_principal_credit\":606300}', '2026-08-10 12:50:07'),
(11, '10c8ca0d-c14d-4abf-b1f8-8000b5c051a1', 3, NULL, 'opening_purchase_balance', 1114900, '2026-08-10', '2026-08-10 13:13:08', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:95f45fa7-9427-42c5-9d17-37e4e027d2ce', NULL, '{\"purchase_price\":1090000,\"documentation_fee_standard\":24900,\"documentation_fee_charged\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 13:13:08'),
(12, 'c5d5d0fc-d4b1-477e-bae5-cb07cd6c0cfa', 3, NULL, 'opening_principal_credit', 453500, '2026-08-10', '2026-08-10 13:13:08', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:95f45fa7-9427-42c5-9d17-37e4e027d2ce', NULL, '{\"opening_principal_credit\":453500}', '2026-08-10 13:13:08'),
(13, 'd04a96ab-6fe2-40ae-b373-d2124d8bcbaf', 4, NULL, 'opening_purchase_balance', 317000, '2026-08-10', '2026-08-10 13:17:38', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:77311b80-8f6e-47bc-98c3-68cabd53369b', NULL, '{\"purchase_price\":292100,\"documentation_fee_standard\":24900,\"documentation_fee_charged\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 13:17:38'),
(14, '2cf4bc60-8935-4b31-b054-8f465fb7d901', 4, NULL, 'opening_principal_credit', 269500, '2026-08-10', '2026-08-10 13:17:38', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:77311b80-8f6e-47bc-98c3-68cabd53369b', NULL, '{\"opening_principal_credit\":269500}', '2026-08-10 13:17:38'),
(15, 'c29cde7b-6a70-4714-af74-6e0272ff12ff', 5, NULL, 'opening_purchase_balance', 210000, '2026-08-10', '2026-08-10 13:20:22', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:fd5ded0c-3b72-4a7a-b8d1-36fe287bf2da', NULL, '{\"purchase_price\":185100,\"documentation_fee_standard\":24900,\"documentation_fee_charged\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 13:20:22'),
(16, '8bf824c6-dc52-4f50-9568-41bcca5af4c7', 5, NULL, 'opening_principal_credit', 95100, '2026-08-10', '2026-08-10 13:20:22', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:fd5ded0c-3b72-4a7a-b8d1-36fe287bf2da', NULL, '{\"opening_principal_credit\":95100}', '2026-08-10 13:20:22'),
(17, 'f39fd735-e31a-442d-92d1-582a7484469c', 6, NULL, 'opening_purchase_balance', 325000, '2026-08-10', '2026-08-10 13:31:46', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:aa2d42ec-aefa-4d0a-bc68-393000835259', NULL, '{\"purchase_price\":305100,\"documentation_fee_standard\":19900,\"documentation_fee_charged\":19900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 13:31:46'),
(18, '55ec0f59-4e8a-4020-8e8e-1651faa269d1', 6, NULL, 'opening_principal_credit', 325000, '2026-08-10', '2026-08-10 13:31:46', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:aa2d42ec-aefa-4d0a-bc68-393000835259', NULL, '{\"opening_principal_credit\":325000}', '2026-08-10 13:31:46'),
(19, 'b4ecc9cb-a892-4922-8318-5a24f15ea27d', 7, NULL, 'opening_purchase_balance', 325000, '2026-08-10', '2026-08-10 13:33:45', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:0f21f6ae-33a1-4226-90c3-219e2ed32c40', NULL, '{\"purchase_price\":305100,\"documentation_fee_standard\":19900,\"documentation_fee_charged\":19900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 13:33:45'),
(20, 'd5e97c0b-eb97-47d7-94fc-cd759e91caba', 7, NULL, 'opening_principal_credit', 180000, '2026-08-10', '2026-08-10 13:33:45', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:0f21f6ae-33a1-4226-90c3-219e2ed32c40', NULL, '{\"opening_principal_credit\":180000}', '2026-08-10 13:33:45'),
(21, '1ff23221-4716-4f51-8d8c-564b4493d19e', 8, NULL, 'opening_purchase_balance', 614900, '2026-08-10', '2026-08-10 13:38:57', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:f251d5a8-136b-4f65-871e-d3921e465dcf', NULL, '{\"purchase_price\":590000,\"documentation_fee_standard\":24900,\"documentation_fee_charged\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 13:38:57'),
(22, '96ea2c1a-ea77-4b16-bfea-d714a245e0a1', 8, NULL, 'opening_principal_credit', 541300, '2026-08-10', '2026-08-10 13:38:57', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:f251d5a8-136b-4f65-871e-d3921e465dcf', NULL, '{\"opening_principal_credit\":541300}', '2026-08-10 13:38:57'),
(23, 'ec253331-3e9a-42cc-a301-ee372bddc60e', 9, NULL, 'opening_purchase_balance', 324800, '2026-08-10', '2026-08-10 13:50:57', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:e23afee3-cc06-44e7-af8d-7830c19d5bae', NULL, '{\"purchase_price\":299900,\"documentation_fee_standard\":24900,\"documentation_fee_charged\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 13:50:57'),
(24, '9edd1a5c-0615-4865-a11a-bff7e7e1560e', 9, NULL, 'opening_principal_credit', 244800, '2026-08-10', '2026-08-10 13:50:57', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:e23afee3-cc06-44e7-af8d-7830c19d5bae', NULL, '{\"opening_principal_credit\":244800}', '2026-08-10 13:50:57'),
(25, '62fea5ea-6eac-4f9a-b033-9c3e399d3919', 10, NULL, 'opening_purchase_balance', 324800, '2026-08-10', '2026-08-10 13:58:18', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:0d86e5e5-b277-4899-80d2-7151402581ef', NULL, '{\"purchase_price\":299900,\"documentation_fee_standard\":24900,\"documentation_fee_charged\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 13:58:18'),
(26, '4866baa1-a39a-42fb-8f98-400e52221828', 10, NULL, 'opening_principal_credit', 238400, '2026-08-10', '2026-08-10 13:58:18', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:0d86e5e5-b277-4899-80d2-7151402581ef', NULL, '{\"opening_principal_credit\":238400}', '2026-08-10 13:58:18'),
(27, '2e2f26c7-3375-4dfc-8a57-008f236dd5ac', 11, NULL, 'opening_purchase_balance', 594800, '2026-08-10', '2026-08-10 13:59:44', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:f8f4ca82-8174-4f67-b1a6-a21c6caa3986', NULL, '{\"purchase_price\":549900,\"documentation_fee_standard\":44900,\"documentation_fee_charged\":44900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 13:59:44'),
(28, 'c45eed61-2984-447b-ac94-e5ff227b8add', 11, NULL, 'opening_principal_credit', 261900, '2026-08-10', '2026-08-10 13:59:44', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:f8f4ca82-8174-4f67-b1a6-a21c6caa3986', NULL, '{\"opening_principal_credit\":261900}', '2026-08-10 13:59:44'),
(29, '2d0c7a5a-f321-4823-830f-b5007bb1cef6', 12, NULL, 'opening_purchase_balance', 644800, '2026-08-10', '2026-08-10 14:01:02', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:5c1c72fc-7a7d-4338-9a3b-d6b52b666116', NULL, '{\"purchase_price\":599900,\"documentation_fee_standard\":44900,\"documentation_fee_charged\":44900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 14:01:02'),
(30, '2b7059be-7b68-4bea-9c5c-bb3c79ec4d4a', 12, NULL, 'opening_principal_credit', 255500, '2026-08-10', '2026-08-10 14:01:02', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:5c1c72fc-7a7d-4338-9a3b-d6b52b666116', NULL, '{\"opening_principal_credit\":255500}', '2026-08-10 14:01:02'),
(31, 'e1a6c58e-f5e6-4011-9696-6cf74abb781d', 13, NULL, 'opening_purchase_balance', 1244900, '2026-08-10', '2026-08-10 14:02:45', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:b154101e-9080-4dde-9dd3-b06f5867214e', NULL, '{\"purchase_price\":1200000,\"documentation_fee_standard\":44900,\"documentation_fee_charged\":44900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 14:02:45'),
(32, '1d50a246-5d6a-4f48-8b82-df6e1eefc5a8', 13, NULL, 'opening_principal_credit', 451400, '2026-08-10', '2026-08-10 14:02:45', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:b154101e-9080-4dde-9dd3-b06f5867214e', NULL, '{\"opening_principal_credit\":451400}', '2026-08-10 14:02:45'),
(33, '6dc78dde-e70c-43f2-8f65-1072dc24f84b', 14, NULL, 'opening_purchase_balance', 594400, '2026-02-05', '2026-08-10 14:04:19', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:75626c9b-c9c6-44bd-8282-f44f7e3fd1a9', NULL, '{\"purchase_price\":549500,\"documentation_fee_standard\":44900,\"documentation_fee_charged\":44900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 14:04:19'),
(34, 'ab77cfd4-22db-4f5c-9168-ea67d385c490', 14, NULL, 'opening_principal_credit', 259300, '2026-02-05', '2026-08-10 14:04:19', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:75626c9b-c9c6-44bd-8282-f44f7e3fd1a9', NULL, '{\"opening_principal_credit\":259300}', '2026-08-10 14:04:19'),
(35, '93a5502e-a957-4293-b671-70b1acd4bddf', 15, NULL, 'opening_purchase_balance', 344800, '2026-08-10', '2026-08-10 14:08:10', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:a54d63a5-6d87-4cb8-8e9e-6308e1efac42', NULL, '{\"purchase_price\":299900,\"documentation_fee_standard\":44900,\"documentation_fee_charged\":44900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 14:08:10'),
(36, '47d2d90b-1c87-4493-9220-d0a438b9cbde', 15, NULL, 'opening_principal_credit', 251900, '2026-08-10', '2026-08-10 14:08:10', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:a54d63a5-6d87-4cb8-8e9e-6308e1efac42', NULL, '{\"opening_principal_credit\":251900}', '2026-08-10 14:08:10'),
(37, '00563f52-b1ac-49ae-91b8-2af022f0bc4b', 16, NULL, 'opening_purchase_balance', 344800, '2026-08-10', '2026-08-10 14:10:31', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:58c5b5c6-c48f-4a11-b443-de83964736a4', NULL, '{\"purchase_price\":299900,\"documentation_fee_standard\":44900,\"documentation_fee_charged\":44900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-10 14:10:31'),
(38, '776f9995-065a-4a85-9992-c60fcb3eb711', 16, NULL, 'opening_principal_credit', 150300, '2026-08-10', '2026-08-10 14:10:31', 'Amount previously paid in', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-principal-credit:58c5b5c6-c48f-4a11-b443-de83964736a4', NULL, '{\"opening_principal_credit\":150300}', '2026-08-10 14:10:31'),
(39, '88588dc2-3acc-4633-a7cb-2babd3b9edd2', 11, 3, 'invoice_charge', 15000, '2026-08-03', '2026-08-10 23:00:43', 'Plan payment', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'manual-invoice:9e64cc35-7500-4908-a202-1575968bfd1e:item:0', NULL, NULL, '2026-08-10 23:00:43'),
(40, '44c5ae53-a1d1-4380-b0aa-ad5edc37c389', 11, 3, 'invoice_charge', 1500, '2026-08-03', '2026-08-10 23:00:43', 'Service fee', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'manual-invoice:9e64cc35-7500-4908-a202-1575968bfd1e:item:1', NULL, NULL, '2026-08-10 23:00:43'),
(41, 'e651ccd2-1fd7-466c-9bc2-f4a11f8325c4', 1, 4, 'invoice_charge', 11500, '2025-03-03', '2026-08-11 10:00:04', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:a381672c-e1e7-4df3-b62c-4b225f42aa67:scheduled-payment', NULL, NULL, '2026-08-11 10:00:04'),
(42, '9ac683bc-c8b2-4fcc-8b77-c5eb9e4e72ff', 1, 4, 'recurring_fee', 1500, '2025-03-03', '2026-08-11 10:00:04', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:a381672c-e1e7-4df3-b62c-4b225f42aa67:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-11 10:00:04'),
(43, '9946490e-a7af-428e-9495-b916b2ae5ae8', 1, 5, 'invoice_charge', 3500, '2025-04-03', '2026-08-11 10:00:04', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:cde7535a-9011-484e-8adb-7d4bd271dc24:scheduled-payment', NULL, NULL, '2026-08-11 10:00:04'),
(44, '700d5c51-40eb-4849-8edb-c54275b09ce4', 1, 5, 'recurring_fee', 1500, '2025-04-03', '2026-08-11 10:00:04', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:cde7535a-9011-484e-8adb-7d4bd271dc24:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-11 10:00:04'),
(45, 'df3f7e96-d5fa-40e9-9453-fd1bd25f1ba7', 17, NULL, 'opening_purchase_balance', 274900, '2026-08-11', '2026-08-11 13:51:44', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:91aa1ba0-cef5-4c76-b566-8dea8add047e', NULL, '{\"purchase_price\":250000,\"documentation_fee_standard\":24900,\"documentation_fee_charged\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-11 13:51:44'),
(46, '099b3d95-9d09-44ed-abe8-402de82d2c95', 1, 5, 'adjustment', 5000, '2026-08-11', '2026-08-11 16:54:09', 'Invoice deleted by administrator', 'wrong invoice due to backdated start of contract', 'administrator', 1, NULL, NULL, NULL, NULL, 'invoice:void:cde7535a-9011-484e-8adb-7d4bd271dc24', NULL, NULL, '2026-08-11 16:54:09'),
(47, '0d3acbb9-8349-4850-bc25-14c0ce12dcfb', 1, 4, 'adjustment', 13000, '2026-08-11', '2026-08-11 16:54:29', 'Invoice deleted by administrator', 'auto generated invoice deleted due to wrong start date', 'administrator', 1, NULL, NULL, NULL, NULL, 'invoice:void:a381672c-e1e7-4df3-b62c-4b225f42aa67', NULL, NULL, '2026-08-11 16:54:29'),
(48, '1b715cfc-b5b0-4cd6-82a0-1020442e1ab0', 18, NULL, 'opening_purchase_balance', 324900, '2026-08-11', '2026-08-11 17:49:55', 'Opening contract balance', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'opening-contract-balance:02b869ce-7ed7-4511-b90d-960d11688b60', NULL, '{\"purchase_price\":300000,\"documentation_fee_standard\":24900,\"documentation_fee_charged\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null,\"documentation_fee_waived_by_user_id\":null,\"documentation_fee_waived_at\":null}', '2026-08-11 17:49:55'),
(49, '9032e84a-295e-4604-9b4a-f96aff2f4d9b', 18, NULL, 'payment', 7500, '2026-08-11', '2026-08-11 18:21:46', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'provider:square:RS5climqc9lOvAQA0e9ipaoWCJPZY', NULL, '{\"payment_type\":\"regular\"}', '2026-08-11 18:21:46'),
(50, '72ed76b8-d59c-45e2-883b-fb797a8b6fa4', 18, NULL, 'payment', 13500, '2026-08-11', '2026-08-11 18:31:55', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'provider:square:1MDdi7er1uMEgXKQmPMBWECx5mJZY', NULL, '{\"payment_type\":\"regular\"}', '2026-08-11 18:31:55'),
(51, '1cad17bb-e3e4-47ff-b6c5-3bdd5a7e0d6d', 18, NULL, 'payment', 4500, '2026-08-11', '2026-08-11 19:03:24', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'provider:stripe:pi_3U3NkLIC2f6Um3lL01Z6jg9s', NULL, '{\"payment_type\":\"regular\"}', '2026-08-11 19:03:24'),
(52, '30daa63c-e0c6-4847-adf3-4ae853d6fa7b', 18, NULL, 'payment', 100, '2026-08-11', '2026-08-11 19:10:55', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'provider:square:R610ZdDwlaCbSCbz3BJVtGW5NcRZY', NULL, '{\"payment_type\":\"regular\"}', '2026-08-11 19:10:55'),
(53, 'eb336fd1-1ac0-4641-8e84-aba22e193f10', 18, NULL, 'payment', 65, '2026-08-11', '2026-08-11 19:45:30', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'provider:stripe:pi_3U3OOwIC2f6Um3lL0BfiHwu3', NULL, '{\"payment_type\":\"regular\"}', '2026-08-11 19:45:30'),
(54, '24660b25-8932-4ccb-90de-8fc0d43a18ae', 17, 6, 'invoice_charge', 10000, '2026-08-12', '2026-08-12 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:32ae251f-c8a8-4fb6-bc84-b2c72bb3d131:scheduled-payment', NULL, NULL, '2026-08-12 10:00:03'),
(55, '16dd122e-4482-4a2f-9e5f-90fa947e6cce', 17, 6, 'recurring_fee', 1500, '2026-08-12', '2026-08-12 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:32ae251f-c8a8-4fb6-bc84-b2c72bb3d131:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-12 10:00:03'),
(56, '37488a27-cda9-419b-b0fe-cfc162eef55c', 17, NULL, 'payment', 11500, '2026-08-12', '2026-08-12 20:54:56', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:afc23931-99ba-45ea-b91a-36e83fc18e08', NULL, '{\"payment_type\":\"regular\"}', '2026-08-12 20:54:56'),
(57, 'ad5f69b9-eea1-48c6-a609-9d9b49d09b41', 17, 7, 'invoice_charge', 10000, '2026-08-13', '2026-08-13 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:1fc366ff-f300-4818-b7a1-72440aeb8419:scheduled-payment', NULL, NULL, '2026-08-13 10:00:03'),
(58, 'c2d38c83-891b-4e56-8c6b-d91042bd9101', 17, 7, 'recurring_fee', 1500, '2026-08-13', '2026-08-13 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:1fc366ff-f300-4818-b7a1-72440aeb8419:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-13 10:00:03'),
(59, '99060129-cdd3-4c56-9515-859e7bf5de81', 17, NULL, 'payment', 11500, '2026-08-13', '2026-08-13 18:35:24', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:a4bdc810-7e3c-4f49-80f2-a7fcfd1d3cec', NULL, '{\"payment_type\":\"regular\"}', '2026-08-13 18:35:24'),
(60, '2a9c2253-f130-43de-87fc-fad5b4b42028', 11, 3, 'recurring_fee', 2500, '2026-08-12', '2026-08-13 19:14:40', 'Late Fee added 8/12/26', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'late-fee:9e64cc35-7500-4908-a202-1575968bfd1e:stage-1', NULL, '{\"stage\":1,\"unpaid_scheduled_payment\":15000}', '2026-08-13 19:14:40'),
(61, 'c45ea151-915c-443e-8d6b-034373912a47', 18, 8, 'invoice_charge', 10000, '2026-08-13', '2026-08-13 21:34:19', 'Plan payment', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'manual-invoice:e3745a1f-3c38-4be6-b792-24a54a46c860:item:0', NULL, NULL, '2026-08-13 21:34:19'),
(62, 'c552eb54-c047-4885-a4a5-31f398a21fac', 18, 8, 'invoice_charge', 1500, '2026-08-13', '2026-08-13 21:34:19', 'fee', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'manual-invoice:e3745a1f-3c38-4be6-b792-24a54a46c860:item:1', NULL, NULL, '2026-08-13 21:34:19'),
(63, 'f3d519a3-2425-468f-822d-5b99c33ee7d0', 18, 8, 'adjustment', 11500, '2026-08-13', '2026-08-13 21:34:53', 'Invoice deleted by administrator', 'testing', 'administrator', 1, NULL, NULL, NULL, NULL, 'invoice:void:e3745a1f-3c38-4be6-b792-24a54a46c860', NULL, NULL, '2026-08-13 21:34:53'),
(64, '30e891d2-6cbd-4c93-964f-fe08a54cb83f', 17, 9, 'invoice_charge', 10000, '2026-08-14', '2026-08-14 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:78e5c04a-59e4-44c9-af12-6a71fcfa926c:scheduled-payment', NULL, NULL, '2026-08-14 10:00:03'),
(65, '16efd500-d64e-4168-a8f3-cb8f56600d30', 17, 9, 'recurring_fee', 1500, '2026-08-14', '2026-08-14 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:78e5c04a-59e4-44c9-af12-6a71fcfa926c:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-14 10:00:03'),
(66, '989504db-5dbe-4f77-91c3-9437a55abd7f', 18, 10, 'invoice_charge', 12000, '2026-08-14', '2026-08-14 10:00:11', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:6c5d9013-6419-4f56-b5ff-0a7968a45112:scheduled-payment', NULL, NULL, '2026-08-14 10:00:11'),
(67, '8917f477-e934-4449-abc7-bb0712c25d77', 18, 10, 'recurring_fee', 1500, '2026-08-14', '2026-08-14 10:00:11', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:6c5d9013-6419-4f56-b5ff-0a7968a45112:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-14 10:00:11'),
(68, '241e3964-18f5-4363-8101-73aa230ddc6d', 17, NULL, 'payment', 14500, '2026-08-14', '2026-08-14 22:55:30', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:1eecd94d-5ba5-4956-b969-9f103f872230', NULL, '{\"payment_type\":\"regular\"}', '2026-08-14 22:55:30'),
(69, '38021209-696d-483f-92e7-bda98d38fd18', 17, 11, 'invoice_charge', 10000, '2026-08-15', '2026-08-15 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:b29effd9-2baa-498b-8d8c-5402faa4343f:scheduled-payment', NULL, NULL, '2026-08-15 10:00:03'),
(70, 'f5a996c3-a504-4fcc-8cc0-3beaf4decf11', 17, 11, 'recurring_fee', 1500, '2026-08-15', '2026-08-15 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:b29effd9-2baa-498b-8d8c-5402faa4343f:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-15 10:00:03'),
(71, 'e89b3021-27ff-4e6c-ac64-0ad264d43b61', 17, NULL, 'payment', 23000, '2026-08-15', '2026-08-15 21:05:23', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:e0069ddf-d2d9-4692-9f89-84acb05f1782', NULL, '{\"payment_type\":\"regular\"}', '2026-08-15 21:05:23'),
(72, '2950746a-24cf-480d-8a27-55d6c14a4e1d', 17, 12, 'invoice_charge', 10000, '2026-08-16', '2026-08-16 10:00:02', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:c9d92d59-6403-4a94-9ddb-65768b57cde6:scheduled-payment', NULL, NULL, '2026-08-16 10:00:02'),
(73, '385d4517-c44e-480c-a0b3-0aa3b86b0721', 17, 12, 'recurring_fee', 1500, '2026-08-16', '2026-08-16 10:00:02', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:c9d92d59-6403-4a94-9ddb-65768b57cde6:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-16 10:00:02'),
(74, '45f60091-40f6-4824-bef2-c12ad7249fe5', 17, 12, 'credit_application', 11500, '2026-08-16', '2026-08-16 10:00:02', 'Account credit applied to invoice', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:c9d92d59-6403-4a94-9ddb-65768b57cde6:account-credit', NULL, NULL, '2026-08-16 10:00:02'),
(75, 'bc712325-b429-4460-a6b6-43e09ef2a53a', 18, NULL, 'payment', 27000, '2026-08-16', '2026-08-16 19:32:05', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:a3796ea6-4ead-4b97-82f2-bf642b4c88fb', NULL, '{\"payment_type\":\"regular\"}', '2026-08-16 19:32:05'),
(76, '1f6a8431-b248-4997-aeca-598a725dbde9', 17, 13, 'invoice_charge', 10000, '2026-08-17', '2026-08-17 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:f0414091-4a1c-41a9-acf2-4c13b695eac4:scheduled-payment', NULL, NULL, '2026-08-17 10:00:03'),
(77, '610c0b74-6363-48e0-9fe5-ec00823f3577', 17, 13, 'recurring_fee', 1500, '2026-08-17', '2026-08-17 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:f0414091-4a1c-41a9-acf2-4c13b695eac4:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-17 10:00:03'),
(78, 'a84cdcb8-5567-4867-9d37-3ece12321b27', 17, NULL, 'payment', 12500, '2026-08-17', '2026-08-17 18:02:48', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:6da7ed87-b821-4ab3-b32b-3c140c9624bb', NULL, '{\"payment_type\":\"regular\"}', '2026-08-17 18:02:48'),
(79, 'e13b5bee-283e-42d2-acce-835dd22255d3', 17, 14, 'invoice_charge', 10000, '2026-08-18', '2026-08-18 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:18ff9b81-dd35-43f4-af87-17864e9d4055:scheduled-payment', NULL, NULL, '2026-08-18 10:00:03'),
(80, 'fd017744-59b3-4eea-989a-48a6fd3709ea', 17, 14, 'recurring_fee', 1500, '2026-08-18', '2026-08-18 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:18ff9b81-dd35-43f4-af87-17864e9d4055:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-18 10:00:03'),
(81, '83748147-1d2c-4be5-adcf-2cca97fc4b4e', 17, 14, 'credit_application', 1000, '2026-08-18', '2026-08-18 10:00:03', 'Account credit applied to invoice', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:18ff9b81-dd35-43f4-af87-17864e9d4055:account-credit', NULL, NULL, '2026-08-18 10:00:03'),
(82, '3d096044-e772-4a69-8d8c-dd020eae0fc1', 17, NULL, 'payment', 10500, '2026-08-18', '2026-08-18 16:40:13', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:f8b08a17-9e17-4fcb-9d8d-f7edbc732ab4', NULL, '{\"payment_type\":\"regular\"}', '2026-08-18 16:40:13'),
(83, '67927c85-3f1e-4133-bd4a-96ff33e099fd', 17, 15, 'invoice_charge', 10000, '2026-08-19', '2026-08-19 10:00:02', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:860f6936-d01e-4bf9-a76c-59dad0cbf59f:scheduled-payment', NULL, NULL, '2026-08-19 10:00:02'),
(84, 'ecaa2244-1e1f-453c-8a2d-20143cba2ed5', 17, 15, 'recurring_fee', 1500, '2026-08-19', '2026-08-19 10:00:02', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:860f6936-d01e-4bf9-a76c-59dad0cbf59f:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-19 10:00:02'),
(85, 'ee37cabd-499f-41b2-8dfe-291a2876ca35', 17, 16, 'invoice_charge', 10000, '2026-08-20', '2026-08-20 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:dc6a87d8-d91b-4329-ae5c-d5280947dd1e:scheduled-payment', NULL, NULL, '2026-08-20 10:00:03'),
(86, 'ad4cc9db-bc80-4dd7-803b-a67b050d7aa3', 17, 16, 'recurring_fee', 1500, '2026-08-20', '2026-08-20 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:dc6a87d8-d91b-4329-ae5c-d5280947dd1e:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-20 10:00:03'),
(87, 'a24947ef-27cb-4488-993b-d2b9cef47a09', 17, 17, 'invoice_charge', 10000, '2026-08-21', '2026-08-21 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:d17f7755-351c-49a6-b04c-0efd669f25ac:scheduled-payment', NULL, NULL, '2026-08-21 10:00:03'),
(88, '4fd5ddf3-af8d-4ef3-bb84-b4d19e329119', 17, 17, 'recurring_fee', 1500, '2026-08-21', '2026-08-21 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:d17f7755-351c-49a6-b04c-0efd669f25ac:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-21 10:00:03'),
(89, '6dd91dcc-0092-4597-81ae-7c9f74479e16', 17, NULL, 'payment', 5000, '2026-08-21', '2026-08-21 12:33:59', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:7c29d089-af18-421a-97fb-ff416ba46582', NULL, '{\"payment_type\":\"regular\"}', '2026-08-21 12:33:59'),
(90, '08be3531-018d-4b3a-821a-2d1da5fa289b', 17, 18, 'invoice_charge', 10000, '2026-08-22', '2026-08-22 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:e95a6935-c28e-46fc-a192-67d01630a719:scheduled-payment', NULL, NULL, '2026-08-22 10:00:03'),
(91, '607bfcc3-636d-4034-81cd-c09e5ac6a156', 17, 18, 'recurring_fee', 1500, '2026-08-22', '2026-08-22 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:e95a6935-c28e-46fc-a192-67d01630a719:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-22 10:00:03'),
(92, '14f89327-24e0-4a23-9217-e6a4fb072cba', 4, NULL, 'payment', 15000, '2026-08-22', '2026-08-22 14:55:33', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:e134701b-6f0d-4226-9dd8-9880a5421a5a', NULL, '{\"payment_type\":\"regular\"}', '2026-08-22 14:55:33'),
(93, '865d62db-d8a1-4fee-aef1-a14fb2d10c61', 5, NULL, 'payment', 10000, '2026-08-22', '2026-08-22 14:58:02', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:71c3fe64-3136-474b-897d-f278f9cadf67', NULL, '{\"payment_type\":\"regular\"}', '2026-08-22 14:58:02'),
(94, 'bac9c057-b343-4779-895c-6322d49580cf', 4, NULL, 'adjustment', 24900, '2026-08-23', '2026-08-22 15:06:42', 'Contract amount amendment', 'adjust import information', 'administrator', 1, NULL, NULL, NULL, NULL, NULL, NULL, '{\"contract_amounts_before\":{\"purchase_price\":292100,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"contract_amounts_after\":{\"purchase_price\":317000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null}}', '2026-08-22 15:06:42'),
(95, '938d740d-e076-434d-ba0c-d94eccf52f02', 4, NULL, 'adjustment', 24900, '2026-08-23', '2026-08-22 15:06:42', 'Amount previously paid in adjustment', 'adjust import information', 'administrator', 1, NULL, NULL, NULL, NULL, NULL, NULL, '{\"previous_amount\":269500,\"new_amount\":294400}', '2026-08-22 15:06:42'),
(96, 'e366c8d6-ead1-459e-917d-2c9a6de45f8f', 5, NULL, 'adjustment', 24900, '2026-08-23', '2026-08-22 17:01:47', 'Contract amount amendment', 'correct import details', 'administrator', 1, NULL, NULL, NULL, NULL, NULL, NULL, '{\"contract_amounts_before\":{\"purchase_price\":185100,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null},\"contract_amounts_after\":{\"purchase_price\":210000,\"documentation_fee_standard\":24900,\"documentation_fee_waived\":0,\"documentation_fee_waiver_reason\":null}}', '2026-08-22 17:01:47'),
(97, 'e635de9b-7faa-48bd-93f3-47a7eb2752e0', 5, NULL, 'adjustment', 24900, '2026-08-23', '2026-08-22 17:01:47', 'Amount previously paid in adjustment', 'correct import details', 'administrator', 1, NULL, NULL, NULL, NULL, NULL, NULL, '{\"previous_amount\":95100,\"new_amount\":120000}', '2026-08-22 17:01:47'),
(98, 'e12a437e-fa17-44c3-8fbb-37f6834ba20c', 17, 19, 'invoice_charge', 10000, '2026-08-23', '2026-08-23 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:a6d05ec4-88d9-4629-b175-ba8ec3b65e9b:scheduled-payment', NULL, NULL, '2026-08-23 10:00:03'),
(99, 'd315cee0-6f95-4f5d-9299-cfa156dd30cd', 17, 19, 'recurring_fee', 1500, '2026-08-23', '2026-08-23 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:a6d05ec4-88d9-4629-b175-ba8ec3b65e9b:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-23 10:00:03'),
(100, '0b827255-efe2-44eb-b48f-3d4e49a0283c', 17, NULL, 'payment', 11500, '2026-08-23', '2026-08-23 17:16:41', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:7d095e41-0746-4e91-8216-c55f7ebb60de', NULL, '{\"payment_type\":\"regular\"}', '2026-08-23 17:16:41'),
(101, '638ab174-fadc-40b5-81dd-3b9775db7a21', 17, 16, 'adjustment', 2500, '2026-08-20', '2026-08-23 17:19:09', 'Invoice edited', 'Invoice edited by administrator', 'administrator', 1, NULL, NULL, NULL, NULL, 'invoice:edit:dc6a87d8-d91b-4329-ae5c-d5280947dd1e:d457e0d2-ae14-47e5-90c9-099c800e0a32', NULL, NULL, '2026-08-23 17:19:09'),
(102, 'd57b048e-5d89-4c5f-8bdd-a80d5442a049', 17, NULL, 'payment', 12700, '2026-08-23', '2026-08-24 00:24:36', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:18a3b8c8-7d12-4045-9f7c-b12a5e50c798', NULL, '{\"payment_type\":\"regular\"}', '2026-08-24 00:24:36'),
(103, 'd73a4b6c-92d3-4883-8718-5bd872a3203d', 17, 20, 'invoice_charge', 10000, '2026-08-24', '2026-08-24 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:b014b4c7-c930-4c73-a8cd-df6c54d455f5:scheduled-payment', NULL, NULL, '2026-08-24 10:00:03'),
(104, 'cd91b953-100c-4b95-8569-315c56a4935b', 17, 20, 'recurring_fee', 1500, '2026-08-24', '2026-08-24 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:b014b4c7-c930-4c73-a8cd-df6c54d455f5:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-24 10:00:03'),
(105, '57282c7b-33fb-4b99-839e-1224e981bad4', 17, 21, 'invoice_charge', 10000, '2026-08-25', '2026-08-25 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:128c0ac0-8593-4d4b-91ea-ff4b02f0b49b:scheduled-payment', NULL, NULL, '2026-08-25 10:00:03'),
(106, 'a667675d-9e41-4246-a107-6cb399d9115c', 17, 21, 'recurring_fee', 1500, '2026-08-25', '2026-08-25 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:128c0ac0-8593-4d4b-91ea-ff4b02f0b49b:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-25 10:00:03'),
(107, 'a6253a60-fe98-4f61-b0ed-e84f034ac507', 17, 22, 'invoice_charge', 10000, '2026-08-26', '2026-08-26 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:36b7a105-709a-47dc-ba27-78c64d437b9b:scheduled-payment', NULL, NULL, '2026-08-26 10:00:03'),
(108, '87af632d-c55b-4a3f-88b0-bdd2f1a8a503', 17, 22, 'recurring_fee', 1500, '2026-08-26', '2026-08-26 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:36b7a105-709a-47dc-ba27-78c64d437b9b:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-26 10:00:03'),
(109, '72d12814-e38e-4e90-9f94-a4f392a39e66', 17, 23, 'invoice_charge', 10000, '2026-08-27', '2026-08-27 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:5fb0f9e1-1211-4dc4-b410-547b0ac478f2:scheduled-payment', NULL, NULL, '2026-08-27 10:00:03'),
(110, 'eb8bf01b-0d7d-42a7-b66a-867870e8f872', 17, 23, 'recurring_fee', 1500, '2026-08-27', '2026-08-27 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:5fb0f9e1-1211-4dc4-b410-547b0ac478f2:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-27 10:00:03'),
(111, '5e296471-eada-4bf8-9962-bff66ce25c34', 17, NULL, 'payment', 11500, '2026-08-27', '2026-08-27 13:24:38', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:beac5881-bcf9-4bf4-98bb-7b8bc291cc4a', NULL, '{\"payment_type\":\"regular\"}', '2026-08-27 13:24:38'),
(112, '28d99073-7319-4110-a5cf-e5fb9b6e2821', 17, 24, 'invoice_charge', 10000, '2026-08-28', '2026-08-28 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:dab8e5d6-67e8-40f0-831f-fadd2863a00d:scheduled-payment', NULL, NULL, '2026-08-28 10:00:03'),
(113, '3a2f5d98-27e5-4119-abc6-1a2898eec7c2', 17, 24, 'recurring_fee', 1500, '2026-08-28', '2026-08-28 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:dab8e5d6-67e8-40f0-831f-fadd2863a00d:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-28 10:00:03'),
(114, '17f56705-66f9-4b66-8785-3646e1dc126f', 17, 25, 'invoice_charge', 10000, '2026-08-29', '2026-08-29 10:00:02', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:2ab18a0f-800c-4ebc-b2a5-23b131feb1c0:scheduled-payment', NULL, NULL, '2026-08-29 10:00:02'),
(115, '87306757-1e25-4951-8457-15be11098345', 17, 25, 'recurring_fee', 1500, '2026-08-29', '2026-08-29 10:00:02', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:2ab18a0f-800c-4ebc-b2a5-23b131feb1c0:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-29 10:00:02'),
(116, '3b8f0aa7-e1ad-4486-a516-3c565fa16561', 17, 26, 'invoice_charge', 10000, '2026-08-30', '2026-08-30 10:00:03', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:5d967232-007c-4d12-8c55-bdf52a17da46:scheduled-payment', NULL, NULL, '2026-08-30 10:00:03'),
(117, '36347cec-58bf-44e6-a042-f3012706934f', 17, 26, 'recurring_fee', 1500, '2026-08-30', '2026-08-30 10:00:03', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:5d967232-007c-4d12-8c55-bdf52a17da46:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-30 10:00:03'),
(118, 'ea2547b3-4cd3-4343-928a-bb021f8732a2', 11, 3, 'adjustment', 581, '2026-08-03', '2026-08-30 13:25:25', 'Invoice edited', 'Invoice edited by administrator', 'administrator', 1, NULL, NULL, NULL, NULL, 'invoice:edit:9e64cc35-7500-4908-a202-1575968bfd1e:87501274-a347-4342-8117-346d1a99c33b', NULL, NULL, '2026-08-30 13:25:25'),
(119, '70e3babf-aa7f-4369-871e-f4c708615166', 11, NULL, 'payment', 19581, '2026-08-30', '2026-08-30 13:27:37', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:b4ba44b2-783a-481e-8ce6-73c2bec5d7d4', NULL, '{\"payment_type\":\"regular\"}', '2026-08-30 13:27:37'),
(120, 'f8585dda-b039-45fb-af1d-d21254b68666', 11, NULL, 'reversal', 19581, '2026-08-30', '2026-08-30 13:28:33', 'Payment reversal', 'Mistaken Entry', 'administrator', 1, NULL, NULL, NULL, 119, NULL, NULL, NULL, '2026-08-30 13:28:33'),
(121, '59cd0405-3a6e-4b71-89da-33c29c775bb4', 11, NULL, 'payment', 19581, '2026-08-30', '2026-08-30 13:40:54', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:eb6dcbdf-ea0a-4467-b816-d03156166e02', NULL, '{\"payment_type\":\"regular\"}', '2026-08-30 13:40:54'),
(122, 'b67ea2d1-914e-4e80-9be8-3dfe7b9570e2', 11, NULL, 'reversal', 19581, '2026-08-30', '2026-08-30 13:57:00', 'Payment reversal', 'Payment system revision testing - removed payment', 'administrator', 1, NULL, NULL, NULL, 121, NULL, NULL, NULL, '2026-08-30 13:57:00'),
(123, '973f3f90-a6a7-45b0-a5fd-1637819a234a', 11, NULL, 'payment', 19581, '2026-08-30', '2026-08-30 15:13:50', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'payment:9c23dc0f-0236-49ec-b8e4-7a968a73b35c', NULL, '{\"payment_type\":\"regular\"}', '2026-08-30 15:13:50'),
(124, 'a8bc7a25-dfc4-47f0-9f72-b3760ce3fa45', 18, NULL, 'payment', 134, '2026-08-30', '2026-08-30 22:23:13', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'provider:square:LJQzCKZSG2cGnsRnAahyTxbdQ58YY', NULL, '{\"payment_type\":\"regular\",\"processing_fee_amount\":34}', '2026-08-30 22:23:13'),
(125, 'faa32f99-9d7c-4093-835b-28f23351c5da', 18, NULL, 'payment', 100, '2026-08-30', '2026-08-30 22:27:48', 'Payment received', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'provider:square:NgFqdt9gdA5ISqCRthCxsF8Iq0aZY', NULL, '{\"payment_type\":\"regular\",\"processing_fee_amount\":0}', '2026-08-30 22:27:48'),
(126, 'bb7c0de7-9fc0-4802-b0e5-22c6aa4cb6bc', 18, 27, 'invoice_charge', 3000, '2026-08-30', '2026-08-30 23:24:00', 'Plan payment', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'manual-invoice:54cca471-ec01-4508-81c9-365aaee40651:item:0', NULL, NULL, '2026-08-30 23:24:00'),
(127, 'eb93dead-fac5-4111-a053-4a55eb72c813', 18, 27, 'credit_application', 3000, '2026-08-30', '2026-08-30 23:24:00', 'Account credit applied to invoice', NULL, 'administrator', 1, NULL, NULL, NULL, NULL, 'manual-invoice:54cca471-ec01-4508-81c9-365aaee40651:account-credit', NULL, NULL, '2026-08-30 23:24:00'),
(128, '5c114f21-939e-460f-afe9-8b0f6c50156a', 18, 27, 'reversal', 3000, '2026-08-30', '2026-08-31 00:28:43', 'Account credit restored for deleted invoice', 'Testing', 'administrator', 1, NULL, NULL, NULL, 127, 'invoice:void:54cca471-ec01-4508-81c9-365aaee40651:credit:127', NULL, NULL, '2026-08-31 00:28:43'),
(129, 'fafbde86-58ba-4e09-bcfa-7f76aa3d1a67', 18, 27, 'adjustment', 3000, '2026-08-30', '2026-08-31 00:28:43', 'Invoice deleted by administrator', 'Testing', 'administrator', 1, NULL, NULL, NULL, NULL, 'invoice:void:54cca471-ec01-4508-81c9-365aaee40651', NULL, NULL, '2026-08-31 00:28:43'),
(130, 'c9b6cb69-9f66-4c66-8730-8be2a25c7420', 17, 28, 'invoice_charge', 10000, '2026-08-31', '2026-08-31 10:00:02', 'Monthly scheduled purchase payment', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:ee2e8174-7292-4d46-a471-d2c7a85fde27:scheduled-payment', NULL, NULL, '2026-08-31 10:00:02'),
(131, '01ac2cd9-9be5-4ae3-930f-96faa5df7b4f', 17, 28, 'recurring_fee', 1500, '2026-08-31', '2026-08-31 10:00:02', 'Monthly service fee', NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'monthly-invoice:ee2e8174-7292-4d46-a471-d2c7a85fde27:monthly-service-fee', NULL, '{\"standard_amount\":1500,\"waived_amount\":0,\"waiver_reason\":null}', '2026-08-31 10:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_billing_term_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'draft',
  `issued_at` timestamp NULL DEFAULT NULL,
  `operationally_closed_at` timestamp NULL DEFAULT NULL,
  `reopened_at` timestamp NULL DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `generation_source` varchar(24) NOT NULL DEFAULT 'administrator',
  `first_viewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `uuid`, `payment_plan_id`, `payment_plan_billing_term_id`, `invoice_number`, `period_start`, `period_end`, `issue_date`, `due_date`, `status`, `issued_at`, `operationally_closed_at`, `reopened_at`, `created_by_user_id`, `created_at`, `updated_at`, `generation_source`, `first_viewed_at`) VALUES
(1, '92c23727-2a51-4e7d-b7fd-0d7dc798f234', 1, NULL, 'INV-1-202501', '2025-01-01', '2025-01-31', '2025-01-03', '2025-01-08', 'voided', '2026-08-10 10:00:02', '2026-08-10 11:42:20', NULL, 1, '2026-08-10 10:00:02', '2026-08-10 11:42:20', 'system', NULL),
(2, 'f37c0c04-071f-456e-9d7f-a594f6ea7319', 1, NULL, 'INV-1-202502', '2025-02-01', '2025-02-28', '2025-02-03', '2025-02-08', 'voided', '2026-08-10 10:00:02', '2026-08-10 11:42:32', NULL, 1, '2026-08-10 10:00:02', '2026-08-10 11:42:32', 'system', NULL),
(3, '9e64cc35-7500-4908-a202-1575968bfd1e', 11, NULL, 'M11-260803-V0', NULL, NULL, '2026-08-03', '2026-08-08', 'paid', '2026-08-10 23:00:43', NULL, NULL, 1, '2026-08-10 23:00:43', '2026-08-30 15:13:50', 'manual', NULL),
(4, 'a381672c-e1e7-4df3-b62c-4b225f42aa67', 1, NULL, 'INV-1-202503', '2025-03-01', '2025-03-31', '2025-03-03', '2025-03-08', 'voided', '2026-08-11 10:00:04', '2026-08-11 16:54:29', NULL, 1, '2026-08-11 10:00:04', '2026-08-11 16:54:29', 'system', NULL),
(5, 'cde7535a-9011-484e-8adb-7d4bd271dc24', 1, NULL, 'INV-1-202504', '2025-04-01', '2025-04-30', '2025-04-03', '2025-04-08', 'voided', '2026-08-11 10:00:04', '2026-08-11 16:54:09', NULL, 1, '2026-08-11 10:00:04', '2026-08-11 16:54:09', 'system', NULL),
(6, '32ae251f-c8a8-4fb6-bc84-b2c72bb3d131', 17, NULL, 'INV-17-20260812', '2026-08-12', '2026-08-12', '2026-08-12', '2026-08-17', 'paid', '2026-08-12 10:00:03', NULL, NULL, 1, '2026-08-12 10:00:03', '2026-08-12 20:54:56', 'system', NULL),
(7, '1fc366ff-f300-4818-b7a1-72440aeb8419', 17, NULL, 'INV-17-20260813', '2026-08-13', '2026-08-13', '2026-08-13', '2026-08-18', 'paid', '2026-08-13 10:00:03', NULL, NULL, 1, '2026-08-13 10:00:03', '2026-08-13 18:35:24', 'system', NULL),
(8, 'e3745a1f-3c38-4be6-b792-24a54a46c860', 18, 23, 'M18-260813-RB', NULL, NULL, '2026-08-13', '2026-08-18', 'voided', '2026-08-13 21:34:19', '2026-08-13 21:34:53', NULL, 1, '2026-08-13 21:34:19', '2026-08-13 21:34:53', 'manual', NULL),
(9, '78e5c04a-59e4-44c9-af12-6a71fcfa926c', 17, 21, 'INV-17-20260814', '2026-08-14', '2026-08-14', '2026-08-14', '2026-08-19', 'paid', '2026-08-14 10:00:03', NULL, NULL, 1, '2026-08-14 10:00:03', '2026-08-14 22:55:30', 'system', NULL),
(10, '6c5d9013-6419-4f56-b5ff-0a7968a45112', 18, 24, 'INV-18-202608', '2026-08-01', '2026-08-31', '2026-08-14', '2026-08-19', 'paid', '2026-08-14 10:00:11', NULL, NULL, 1, '2026-08-14 10:00:11', '2026-08-31 12:05:23', 'system', '2026-08-31 12:05:23'),
(11, 'b29effd9-2baa-498b-8d8c-5402faa4343f', 17, 21, 'INV-17-20260815', '2026-08-15', '2026-08-15', '2026-08-15', '2026-08-20', 'paid', '2026-08-15 10:00:03', NULL, NULL, 1, '2026-08-15 10:00:03', '2026-08-15 21:05:23', 'system', NULL),
(12, 'c9d92d59-6403-4a94-9ddb-65768b57cde6', 17, 21, 'INV-17-20260816', '2026-08-16', '2026-08-16', '2026-08-16', '2026-08-21', 'paid', '2026-08-16 10:00:02', NULL, NULL, 1, '2026-08-16 10:00:02', '2026-08-16 10:00:02', 'system', NULL),
(13, 'f0414091-4a1c-41a9-acf2-4c13b695eac4', 17, 21, 'INV-17-20260817', '2026-08-17', '2026-08-17', '2026-08-17', '2026-08-22', 'paid', '2026-08-17 10:00:03', NULL, NULL, 1, '2026-08-17 10:00:03', '2026-08-17 18:02:48', 'system', NULL),
(14, '18ff9b81-dd35-43f4-af87-17864e9d4055', 17, 21, 'INV-17-20260818', '2026-08-18', '2026-08-18', '2026-08-18', '2026-08-23', 'paid', '2026-08-18 10:00:03', NULL, NULL, 1, '2026-08-18 10:00:03', '2026-08-18 16:40:13', 'system', NULL),
(15, '860f6936-d01e-4bf9-a76c-59dad0cbf59f', 17, 21, 'INV-17-20260819', '2026-08-19', '2026-08-19', '2026-08-19', '2026-08-24', 'paid', '2026-08-19 10:00:02', NULL, NULL, 1, '2026-08-19 10:00:02', '2026-08-23 17:16:41', 'system', NULL),
(16, 'dc6a87d8-d91b-4329-ae5c-d5280947dd1e', 17, 21, 'INV-17-20260820', '2026-08-20', '2026-08-20', '2026-08-20', '2026-08-25', 'paid', '2026-08-20 10:00:03', NULL, NULL, 1, '2026-08-20 10:00:03', '2026-08-24 00:24:36', 'system', NULL),
(17, 'd17f7755-351c-49a6-b04c-0efd669f25ac', 17, 21, 'INV-17-20260821', '2026-08-21', '2026-08-21', '2026-08-21', '2026-08-26', 'paid', '2026-08-21 10:00:03', NULL, NULL, 1, '2026-08-21 10:00:03', '2026-08-27 13:24:38', 'system', NULL),
(18, 'e95a6935-c28e-46fc-a192-67d01630a719', 17, 21, 'INV-17-20260822', '2026-08-22', '2026-08-22', '2026-08-22', '2026-08-27', 'partially_paid', '2026-08-22 10:00:03', NULL, NULL, 1, '2026-08-22 10:00:03', '2026-08-27 13:24:38', 'system', NULL),
(19, 'a6d05ec4-88d9-4629-b175-ba8ec3b65e9b', 17, 21, 'INV-17-20260823', '2026-08-23', '2026-08-23', '2026-08-23', '2026-08-28', 'issued', '2026-08-23 10:00:03', NULL, NULL, 1, '2026-08-23 10:00:03', '2026-08-23 10:00:03', 'system', NULL),
(20, 'b014b4c7-c930-4c73-a8cd-df6c54d455f5', 17, 21, 'INV-17-20260824', '2026-08-24', '2026-08-24', '2026-08-24', '2026-08-29', 'issued', '2026-08-24 10:00:03', NULL, NULL, 1, '2026-08-24 10:00:03', '2026-08-24 10:00:03', 'system', NULL),
(21, '128c0ac0-8593-4d4b-91ea-ff4b02f0b49b', 17, 21, 'INV-17-20260825', '2026-08-25', '2026-08-25', '2026-08-25', '2026-08-30', 'issued', '2026-08-25 10:00:03', NULL, NULL, 1, '2026-08-25 10:00:03', '2026-08-25 10:00:03', 'system', NULL),
(22, '36b7a105-709a-47dc-ba27-78c64d437b9b', 17, 21, 'INV-17-20260826', '2026-08-26', '2026-08-26', '2026-08-26', '2026-08-31', 'issued', '2026-08-26 10:00:03', NULL, NULL, 1, '2026-08-26 10:00:03', '2026-08-26 10:00:03', 'system', NULL),
(23, '5fb0f9e1-1211-4dc4-b410-547b0ac478f2', 17, 21, 'INV-17-20260827', '2026-08-27', '2026-08-27', '2026-08-27', '2026-09-01', 'issued', '2026-08-27 10:00:03', NULL, NULL, 1, '2026-08-27 10:00:03', '2026-08-27 10:00:03', 'system', NULL),
(24, 'dab8e5d6-67e8-40f0-831f-fadd2863a00d', 17, 21, 'INV-17-20260828', '2026-08-28', '2026-08-28', '2026-08-28', '2026-09-02', 'issued', '2026-08-28 10:00:03', NULL, NULL, 1, '2026-08-28 10:00:03', '2026-08-28 10:00:03', 'system', NULL),
(25, '2ab18a0f-800c-4ebc-b2a5-23b131feb1c0', 17, 27, 'INV-17-20260829', '2026-08-29', '2026-08-29', '2026-08-29', '2026-09-01', 'issued', '2026-08-29 10:00:02', NULL, NULL, 1, '2026-08-29 10:00:02', '2026-08-29 10:00:02', 'system', NULL),
(26, '5d967232-007c-4d12-8c55-bdf52a17da46', 17, 27, 'INV-17-20260830', '2026-08-30', '2026-08-30', '2026-08-30', '2026-09-02', 'issued', '2026-08-30 10:00:03', NULL, NULL, 1, '2026-08-30 10:00:03', '2026-08-30 10:00:03', 'system', NULL),
(27, '54cca471-ec01-4508-81c9-365aaee40651', 18, 24, 'M18-260830-MX', NULL, NULL, '2026-08-30', '2026-09-04', 'voided', '2026-08-30 23:24:00', '2026-08-31 00:28:43', NULL, 1, '2026-08-30 23:24:00', '2026-08-31 00:28:43', 'manual', NULL),
(28, 'ee2e8174-7292-4d46-a471-d2c7a85fde27', 17, 27, 'INV-17-20260831', '2026-08-31', '2026-08-31', '2026-08-31', '2026-09-03', 'issued', '2026-08-31 10:00:02', NULL, NULL, 1, '2026-08-31 10:00:02', '2026-08-31 10:00:02', 'system', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `source_transaction_id` bigint(20) UNSIGNED NOT NULL,
  `item_type` varchar(40) NOT NULL,
  `late_fee_stage` varchar(16) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `standard_amount` bigint(20) NOT NULL,
  `amount` bigint(20) NOT NULL,
  `waived_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `waiver_reason` varchar(500) DEFAULT NULL,
  `waived_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `waived_at` timestamp NULL DEFAULT NULL,
  `retired_at` timestamp NULL DEFAULT NULL,
  `display_order` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `source_transaction_id`, `item_type`, `late_fee_stage`, `description`, `standard_amount`, `amount`, `waived_amount`, `waiver_reason`, `waived_by_user_id`, `waived_at`, `retired_at`, `display_order`, `created_at`) VALUES
(1, 1, 3, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 11500, 11500, 0, NULL, NULL, NULL, NULL, 1, '2026-08-10 10:00:02'),
(2, 1, 4, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-10 10:00:02'),
(3, 2, 5, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 3500, 3500, 0, NULL, NULL, NULL, NULL, 1, '2026-08-10 10:00:02'),
(4, 2, 6, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-10 10:00:02'),
(5, 3, 39, 'scheduled_purchase_payment', NULL, 'Plan payment', 15000, 15000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-10 23:00:43'),
(6, 3, 40, 'administrative_fee', NULL, 'Service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-10 23:00:43'),
(7, 4, 41, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 11500, 11500, 0, NULL, NULL, NULL, NULL, 1, '2026-08-11 10:00:04'),
(8, 4, 42, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-11 10:00:04'),
(9, 5, 43, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 3500, 3500, 0, NULL, NULL, NULL, NULL, 1, '2026-08-11 10:00:04'),
(10, 5, 44, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-11 10:00:04'),
(11, 6, 54, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-12 10:00:03'),
(12, 6, 55, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-12 10:00:03'),
(13, 7, 57, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-13 10:00:03'),
(14, 7, 58, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-13 10:00:03'),
(15, 3, 60, 'late_fee_stage_1', 'stage_1', 'Late Fee added 8/12/26', 2500, 2500, 0, NULL, NULL, NULL, NULL, 3, '2026-08-13 19:14:40'),
(16, 8, 61, 'scheduled_purchase_payment', NULL, 'Plan payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-13 21:34:19'),
(17, 8, 62, 'administrative_fee', NULL, 'fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-13 21:34:19'),
(18, 9, 64, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-14 10:00:03'),
(19, 9, 65, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-14 10:00:03'),
(20, 10, 66, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 12000, 12000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-14 10:00:11'),
(21, 10, 67, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-14 10:00:11'),
(22, 11, 69, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-15 10:00:03'),
(23, 11, 70, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-15 10:00:03'),
(24, 12, 72, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-16 10:00:02'),
(25, 12, 73, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-16 10:00:02'),
(26, 13, 76, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-17 10:00:03'),
(27, 13, 77, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-17 10:00:03'),
(28, 14, 79, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-18 10:00:03'),
(29, 14, 80, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-18 10:00:03'),
(30, 15, 83, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-19 10:00:02'),
(31, 15, 84, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-19 10:00:02'),
(32, 16, 85, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-20 10:00:03'),
(33, 16, 86, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-20 10:00:03'),
(34, 17, 87, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-21 10:00:03'),
(35, 17, 88, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-21 10:00:03'),
(36, 18, 90, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-22 10:00:03'),
(37, 18, 91, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-22 10:00:03'),
(38, 19, 98, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-23 10:00:03'),
(39, 19, 99, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-23 10:00:03'),
(40, 16, 101, 'administrative_fee', NULL, 'Late fee', 2500, 2500, 0, NULL, NULL, NULL, NULL, 1, '2026-08-23 17:19:09'),
(41, 20, 103, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-24 10:00:03'),
(42, 20, 104, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-24 10:00:03'),
(43, 21, 105, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-25 10:00:03'),
(44, 21, 106, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-25 10:00:03'),
(45, 22, 107, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-26 10:00:03'),
(46, 22, 108, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-26 10:00:03'),
(47, 23, 109, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-27 10:00:03'),
(48, 23, 110, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-27 10:00:03'),
(49, 24, 112, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-28 10:00:03'),
(50, 24, 113, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-28 10:00:03'),
(51, 25, 114, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-29 10:00:02'),
(52, 25, 115, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-29 10:00:02'),
(53, 26, 116, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-30 10:00:03'),
(54, 26, 117, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-30 10:00:03'),
(55, 3, 118, 'administrative_fee', NULL, 'Fee', 581, 581, 0, NULL, NULL, NULL, NULL, 1, '2026-08-30 13:25:25'),
(56, 27, 126, 'scheduled_purchase_payment', NULL, 'Plan payment', 3000, 3000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-30 23:24:00'),
(57, 28, 130, 'scheduled_purchase_payment', NULL, 'Scheduled purchase payment', 10000, 10000, 0, NULL, NULL, NULL, NULL, 1, '2026-08-31 10:00:02'),
(58, 28, 131, 'monthly_service_fee', NULL, 'Monthly service fee', 1500, 1500, 0, NULL, NULL, NULL, NULL, 2, '2026-08-31 10:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_reminders`
--

CREATE TABLE `invoice_reminders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recipient_email` varchar(254) NOT NULL,
  `balance_snapshot` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'pending',
  `automated` tinyint(1) NOT NULL DEFAULT 0,
  `trigger_date` date DEFAULT NULL,
  `trigger_type` varchar(32) DEFAULT NULL,
  `sent_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `failure_message` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_reminders`
--

INSERT INTO `invoice_reminders` (`id`, `invoice_id`, `payment_plan_id`, `recipient_client_id`, `recipient_email`, `balance_snapshot`, `status`, `automated`, `trigger_date`, `trigger_type`, `sent_by_user_id`, `sent_at`, `failed_at`, `failure_message`, `created_at`, `updated_at`) VALUES
(1, 10, 18, 17, 'chris@mohavedeals.com', 13500, 'sent', 1, '2026-08-16', 'before_due', NULL, '2026-08-16 11:00:02', NULL, NULL, '2026-08-16 11:00:02', '2026-08-16 11:00:02'),
(2, 15, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-21', 'before_due', NULL, '2026-08-21 11:00:04', NULL, NULL, '2026-08-21 11:00:03', '2026-08-21 11:00:04'),
(3, 16, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-22', 'before_due', NULL, '2026-08-22 11:00:03', NULL, NULL, '2026-08-22 11:00:03', '2026-08-22 11:00:03'),
(4, 17, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-23', 'before_due', NULL, '2026-08-23 11:00:03', NULL, NULL, '2026-08-23 11:00:03', '2026-08-23 11:00:03'),
(5, 18, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-24', 'before_due', NULL, '2026-08-24 11:00:05', NULL, NULL, '2026-08-24 11:00:03', '2026-08-24 11:00:05'),
(6, 19, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-25', 'before_due', NULL, '2026-08-25 11:00:03', NULL, NULL, '2026-08-25 11:00:03', '2026-08-25 11:00:03'),
(7, 17, 17, 16, 'joygr8@yahoo.com', 7800, 'sent', 1, '2026-08-26', 'due_date', NULL, '2026-08-26 11:00:04', NULL, NULL, '2026-08-26 11:00:03', '2026-08-26 11:00:04'),
(8, 20, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-26', 'before_due', NULL, '2026-08-26 11:00:04', NULL, NULL, '2026-08-26 11:00:04', '2026-08-26 11:00:04'),
(9, 18, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-27', 'due_date', NULL, '2026-08-27 11:00:03', NULL, NULL, '2026-08-27 11:00:02', '2026-08-27 11:00:03'),
(10, 21, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-27', 'before_due', NULL, '2026-08-27 11:00:03', NULL, NULL, '2026-08-27 11:00:03', '2026-08-27 11:00:03'),
(11, 19, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-28', 'due_date', NULL, '2026-08-28 11:00:07', NULL, NULL, '2026-08-28 11:00:03', '2026-08-28 11:00:07'),
(12, 22, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-28', 'before_due', NULL, '2026-08-28 11:00:07', NULL, NULL, '2026-08-28 11:00:07', '2026-08-28 11:00:07'),
(13, 20, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-29', 'due_date', NULL, '2026-08-29 11:00:03', NULL, NULL, '2026-08-29 11:00:03', '2026-08-29 11:00:03'),
(14, 23, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-29', 'before_due', NULL, '2026-08-29 11:00:04', NULL, NULL, '2026-08-29 11:00:03', '2026-08-29 11:00:04'),
(15, 25, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-29', 'before_due', NULL, '2026-08-29 11:00:04', NULL, NULL, '2026-08-29 11:00:04', '2026-08-29 11:00:04'),
(16, 21, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-30', 'due_date', NULL, '2026-08-30 11:00:04', NULL, NULL, '2026-08-30 11:00:03', '2026-08-30 11:00:04'),
(17, 24, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-30', 'before_due', NULL, '2026-08-30 11:00:04', NULL, NULL, '2026-08-30 11:00:04', '2026-08-30 11:00:04'),
(18, 26, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-30', 'before_due', NULL, '2026-08-30 11:00:04', NULL, NULL, '2026-08-30 11:00:04', '2026-08-30 11:00:04'),
(19, 18, 17, 16, 'joygr8@yahoo.com', 7800, 'sent', 1, '2026-08-31', 'past_due_1', NULL, '2026-08-31 11:00:03', NULL, NULL, '2026-08-31 11:00:02', '2026-08-31 11:00:03'),
(20, 22, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-31', 'due_date', NULL, '2026-08-31 11:00:03', NULL, NULL, '2026-08-31 11:00:03', '2026-08-31 11:00:03'),
(21, 28, 17, 16, 'joygr8@yahoo.com', 11500, 'sent', 1, '2026-08-31', 'before_due', NULL, '2026-08-31 11:00:04', NULL, NULL, '2026-08-31 11:00:03', '2026-08-31 11:00:04');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_07_26_140000_create_clients_table', 1),
(5, '2026_07_26_140100_create_payment_plans_table', 1),
(6, '2026_07_26_140200_create_payment_plan_clients_table', 1),
(7, '2026_07_26_140300_create_client_contacts_table', 1),
(8, '2026_07_26_140400_create_audit_logs_table', 1),
(9, '2026_07_26_150000_create_billing_and_contract_status_tables', 1),
(10, '2026_07_26_150100_create_invoices_table', 1),
(11, '2026_07_26_150200_create_financial_transactions_table', 1),
(12, '2026_07_26_150300_create_invoice_items_table', 1),
(13, '2026_07_26_150400_create_recurring_fee_tables', 1),
(14, '2026_07_26_150500_create_transaction_effects_table', 1),
(15, '2026_07_26_150600_create_payment_tables', 1),
(16, '2026_07_26_150700_add_idempotency_to_contract_status_events', 1),
(17, '2026_07_27_120000_add_first_payment_amount_to_payment_plans', 1),
(18, '2026_07_27_130000_expand_contact_risk_acknowledgment_method', 1),
(19, '2026_07_31_120000_add_apn_to_payment_plans', 1),
(20, '2026_08_02_120000_add_contract_amounts_to_payment_plans', 1),
(21, '2026_08_02_130000_create_invoice_reminders_table', 1),
(22, '2026_08_02_140000_create_email_settings_tables', 1),
(23, '2026_08_02_150000_add_reminder_automation_fields', 1),
(24, '2026_08_02_160000_add_payment_to_email_deliveries', 1),
(25, '2026_08_02_170000_create_portal_accounts_table', 1),
(26, '2026_08_02_180000_create_portal_workflow_tables', 1),
(27, '2026_08_03_120000_remove_contract_balance_from_payment_receipt_template', 1),
(28, '2026_08_03_130000_add_plan_pauses_and_automatic_invoicing', 1),
(29, '2026_08_04_120000_add_invoice_replacement_link', 1),
(30, '2026_08_04_130000_remove_invoice_replacement_link', 1),
(31, '2026_08_04_140000_create_client_payment_intents', 1),
(32, '2026_08_05_120000_add_cancellation_to_client_payment_intents', 1),
(33, '2026_08_06_150000_add_accelerated_testing_mode_to_payment_plans_table', 1),
(34, '2026_08_10_120000_create_monthly_service_fee_satisfactions_table', 2),
(35, '2026_08_10_121000_add_billing_month_to_payment_allocations_table', 2),
(36, '2026_08_11_120000_add_retired_at_to_invoice_items', 3),
(37, '2026_08_11_170000_create_secure_messages', 4),
(38, '2026_08_13_120000_add_billing_terms_to_invoices', 5),
(39, '2026_08_13_000000_add_encrypted_token_to_portal_invitations_table', 6),
(40, '2026_08_13_100000_create_shared_documents_table', 7),
(41, '2026_08_14_000000_reconcile_shared_document_schema', 7),
(42, '2026_08_14_140000_ensure_secure_message_document_tables', 7),
(43, '2026_08_14_143354_make_invoice_item_amounts_signed', 7),
(44, '2026_08_17_120000_create_secure_message_revisions_table', 8),
(45, '2026_08_19_140000_add_scheduled_invoice_email_setting', 9),
(46, '2026_08_30_220000_add_square_fee_to_client_payment_intents', 10),
(47, '2026_08_30_230000_add_provider_event_to_admin_notices', 11),
(48, '2026_08_30_240000_add_first_viewed_at_to_invoices', 12);

-- --------------------------------------------------------

--
-- Table structure for table `monthly_service_fee_satisfactions`
--

CREATE TABLE `monthly_service_fee_satisfactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `billing_month` date NOT NULL,
  `note` varchar(500) NOT NULL,
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `revoked_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `monthly_service_fee_satisfactions`
--

INSERT INTO `monthly_service_fee_satisfactions` (`id`, `payment_plan_id`, `billing_month`, `note`, `created_by_user_id`, `created_at`, `updated_at`, `revoked_by_user_id`, `revoked_at`) VALUES
(1, 12, '2026-08-01', 'Included in imported history', 1, '2026-08-10 22:53:38', '2026-08-10 22:53:38', NULL, NULL),
(2, 16, '2026-08-01', 'Included in imported history', 1, '2026-08-10 22:57:20', '2026-08-10 22:57:20', NULL, NULL),
(3, 15, '2026-08-01', 'Included in imported history', 1, '2026-08-10 22:57:42', '2026-08-10 22:57:42', NULL, NULL),
(4, 14, '2026-08-01', 'Included in imported history', 1, '2026-08-10 22:57:55', '2026-08-10 22:57:55', NULL, NULL),
(5, 13, '2026-08-01', 'Included in imported history', 1, '2026-08-10 22:58:07', '2026-08-10 22:58:07', NULL, NULL),
(6, 10, '2026-08-01', 'Included in imported history', 1, '2026-08-10 23:01:04', '2026-08-10 23:01:04', NULL, NULL),
(7, 9, '2026-08-01', 'Included in imported history', 1, '2026-08-10 23:01:14', '2026-08-10 23:01:14', NULL, NULL),
(8, 8, '2026-08-01', 'Included in imported history', 1, '2026-08-10 23:01:32', '2026-08-10 23:01:32', NULL, NULL),
(9, 7, '2026-08-01', 'Included in imported history', 1, '2026-08-10 23:01:47', '2026-08-10 23:01:47', NULL, NULL),
(10, 4, '2026-08-01', 'Included in imported history', 1, '2026-08-10 23:02:02', '2026-08-10 23:02:02', NULL, NULL),
(11, 2, '2026-08-01', 'Included in imported history', 1, '2026-08-10 23:02:25', '2026-08-10 23:02:25', NULL, NULL),
(12, 1, '2026-08-01', 'Included in imported history', 1, '2026-08-10 23:02:33', '2026-08-10 23:02:33', NULL, NULL),
(13, 3, '2026-08-01', 'Included in imported history', 1, '2026-08-10 23:02:44', '2026-08-10 23:02:44', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `financial_transaction_id` bigint(20) UNSIGNED NOT NULL,
  `payer_client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `received_date` date NOT NULL,
  `payment_method` varchar(32) NOT NULL,
  `external_reference` varchar(150) DEFAULT NULL,
  `gross_amount` bigint(20) UNSIGNED NOT NULL,
  `current_invoice_amount` bigint(20) UNSIGNED NOT NULL,
  `overpayment_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `overpayment_disposition` varchar(32) DEFAULT NULL,
  `decision_source` varchar(32) DEFAULT NULL,
  `decision_selected_at` timestamp NULL DEFAULT NULL,
  `instruction_recorded_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `financial_transaction_id`, `payer_client_id`, `received_date`, `payment_method`, `external_reference`, `gross_amount`, `current_invoice_amount`, `overpayment_amount`, `overpayment_disposition`, `decision_source`, `decision_selected_at`, `instruction_recorded_by_user_id`, `created_at`) VALUES
(1, 49, 17, '2026-08-11', 'card', 'square:RS5climqc9lOvAQA0e9ipaoWCJPZY', 7500, 0, 7500, 'principal', 'administrator_recorded', '2026-08-11 18:21:46', 1, '2026-08-11 18:21:46'),
(2, 50, 17, '2026-08-11', 'card', 'square:1MDdi7er1uMEgXKQmPMBWECx5mJZY', 13500, 0, 13500, 'principal', 'administrator_recorded', '2026-08-11 18:31:55', 1, '2026-08-11 18:31:55'),
(3, 51, 17, '2026-08-11', 'card', 'stripe:pi_3U3NkLIC2f6Um3lL01Z6jg9s', 4500, 0, 4500, 'principal', 'administrator_recorded', '2026-08-11 19:03:24', 1, '2026-08-11 19:03:24'),
(4, 52, 17, '2026-08-11', 'card', 'square:R610ZdDwlaCbSCbz3BJVtGW5NcRZY', 100, 0, 100, 'principal', 'administrator_recorded', '2026-08-11 19:10:55', 1, '2026-08-11 19:10:55'),
(5, 53, 17, '2026-08-11', 'card', 'stripe:pi_3U3OOwIC2f6Um3lL0BfiHwu3', 65, 0, 65, 'principal', 'administrator_recorded', '2026-08-11 19:45:30', 1, '2026-08-11 19:45:30'),
(6, 56, 16, '2026-08-12', 'zelle', 'Testing only', 11500, 11500, 0, NULL, NULL, NULL, NULL, '2026-08-12 20:54:56'),
(7, 59, 16, '2026-08-13', 'zelle', NULL, 11500, 11500, 0, NULL, NULL, NULL, NULL, '2026-08-13 18:35:24'),
(8, 68, 16, '2026-08-14', 'zelle', NULL, 14500, 11500, 3000, 'principal', 'administrator_recorded', '2026-08-14 22:55:30', 1, '2026-08-14 22:55:30'),
(9, 71, 16, '2026-08-15', 'zelle', NULL, 23000, 11500, 11500, 'next_invoice_credit', 'administrator_recorded', '2026-08-15 21:05:23', 1, '2026-08-15 21:05:23'),
(10, 75, NULL, '2026-08-16', 'other', 'TESTING', 27000, 13500, 13500, 'next_invoice_credit', 'administrator_recorded', '2026-08-16 19:32:05', 1, '2026-08-16 19:32:05'),
(11, 78, 16, '2026-08-17', 'zelle', NULL, 12500, 11500, 1000, 'next_invoice_credit', 'administrator_recorded', '2026-08-17 18:02:48', 1, '2026-08-17 18:02:48'),
(12, 82, 16, '2026-08-18', 'zelle', NULL, 10500, 10500, 0, NULL, NULL, NULL, NULL, '2026-08-18 16:40:13'),
(13, 89, 16, '2026-08-21', 'zelle', NULL, 5000, 5000, 0, NULL, NULL, NULL, NULL, '2026-08-21 12:33:59'),
(14, 92, NULL, '2026-08-22', 'other', NULL, 15000, 0, 13500, 'principal', 'administrator_recorded', '2026-08-22 14:55:33', 1, '2026-08-22 14:55:33'),
(15, 93, NULL, '2026-08-22', 'other', NULL, 10000, 0, 10000, 'principal', 'administrator_recorded', '2026-08-22 14:58:02', 1, '2026-08-22 14:58:02'),
(16, 100, 16, '2026-08-23', 'zelle', NULL, 11500, 11500, 0, NULL, NULL, NULL, NULL, '2026-08-23 17:16:41'),
(17, 102, 16, '2026-08-23', 'zelle', NULL, 12700, 12700, 0, NULL, NULL, NULL, NULL, '2026-08-24 00:24:36'),
(18, 111, 16, '2026-08-27', 'zelle', NULL, 11500, 11500, 0, NULL, NULL, NULL, NULL, '2026-08-27 13:24:38'),
(19, 119, NULL, '2026-08-30', 'other', NULL, 19581, 18081, 0, NULL, NULL, NULL, NULL, '2026-08-30 13:27:37'),
(20, 121, NULL, '2026-08-30', 'other', NULL, 19581, 19581, 0, NULL, NULL, NULL, NULL, '2026-08-30 13:40:54'),
(21, 123, NULL, '2026-08-30', 'other', NULL, 19581, 19581, 0, NULL, NULL, NULL, NULL, '2026-08-30 15:13:50'),
(22, 124, 17, '2026-08-30', 'card', 'square:LJQzCKZSG2cGnsRnAahyTxbdQ58YY', 134, 0, 100, 'principal', 'administrator_recorded', '2026-08-30 22:23:13', 1, '2026-08-30 22:23:13'),
(23, 125, 17, '2026-08-30', 'card', 'square:NgFqdt9gdA5ISqCRthCxsF8Iq0aZY', 100, 0, 100, 'principal', 'administrator_recorded', '2026-08-30 22:27:48', 1, '2026-08-30 22:27:48');

-- --------------------------------------------------------

--
-- Table structure for table `payment_allocations`
--

CREATE TABLE `payment_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `allocation_type` varchar(32) NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fee_assessment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `billing_month` date DEFAULT NULL,
  `amount` bigint(20) UNSIGNED NOT NULL,
  `display_order` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_allocations`
--

INSERT INTO `payment_allocations` (`id`, `payment_id`, `allocation_type`, `invoice_id`, `invoice_item_id`, `fee_assessment_id`, `billing_month`, `amount`, `display_order`, `created_at`) VALUES
(1, 1, 'purchase_balance', NULL, NULL, NULL, NULL, 7500, 1, '2026-08-11 18:21:46'),
(2, 2, 'purchase_balance', NULL, NULL, NULL, NULL, 13500, 1, '2026-08-11 18:31:55'),
(3, 3, 'purchase_balance', NULL, NULL, NULL, NULL, 4500, 1, '2026-08-11 19:03:24'),
(4, 4, 'purchase_balance', NULL, NULL, NULL, NULL, 100, 1, '2026-08-11 19:10:55'),
(5, 5, 'purchase_balance', NULL, NULL, NULL, NULL, 65, 1, '2026-08-11 19:45:30'),
(6, 6, 'invoice_item', 6, 12, NULL, NULL, 1500, 1, '2026-08-12 20:54:56'),
(7, 6, 'invoice_item', 6, 11, NULL, NULL, 10000, 2, '2026-08-12 20:54:56'),
(8, 7, 'invoice_item', 7, 14, NULL, NULL, 1500, 1, '2026-08-13 18:35:24'),
(9, 7, 'invoice_item', 7, 13, NULL, NULL, 10000, 2, '2026-08-13 18:35:24'),
(10, 8, 'invoice_item', 9, 19, NULL, NULL, 1500, 1, '2026-08-14 22:55:30'),
(11, 8, 'invoice_item', 9, 18, NULL, NULL, 10000, 2, '2026-08-14 22:55:30'),
(12, 8, 'purchase_balance', NULL, NULL, NULL, NULL, 3000, 3, '2026-08-14 22:55:30'),
(13, 9, 'invoice_item', 11, 23, NULL, NULL, 1500, 1, '2026-08-15 21:05:23'),
(14, 9, 'invoice_item', 11, 22, NULL, NULL, 10000, 2, '2026-08-15 21:05:23'),
(15, 9, 'client_credit', NULL, NULL, NULL, NULL, 11500, 3, '2026-08-15 21:05:23'),
(16, 10, 'invoice_item', 10, 21, NULL, NULL, 1500, 1, '2026-08-16 19:32:05'),
(17, 10, 'invoice_item', 10, 20, NULL, NULL, 12000, 2, '2026-08-16 19:32:05'),
(18, 10, 'client_credit', NULL, NULL, NULL, NULL, 13500, 3, '2026-08-16 19:32:05'),
(19, 11, 'invoice_item', 13, 27, NULL, NULL, 1500, 1, '2026-08-17 18:02:48'),
(20, 11, 'invoice_item', 13, 26, NULL, NULL, 10000, 2, '2026-08-17 18:02:48'),
(21, 11, 'client_credit', NULL, NULL, NULL, NULL, 1000, 3, '2026-08-17 18:02:48'),
(22, 12, 'invoice_item', 14, 29, NULL, NULL, 1500, 1, '2026-08-18 16:40:13'),
(23, 12, 'invoice_item', 14, 28, NULL, NULL, 9000, 2, '2026-08-18 16:40:13'),
(24, 13, 'invoice_item', 15, 31, NULL, NULL, 1500, 1, '2026-08-21 12:33:59'),
(25, 13, 'invoice_item', 15, 30, NULL, NULL, 3500, 2, '2026-08-21 12:33:59'),
(26, 14, 'service_fee', NULL, NULL, NULL, '2026-08-01', 1500, 1, '2026-08-22 14:55:33'),
(27, 14, 'purchase_balance', NULL, NULL, NULL, NULL, 13500, 2, '2026-08-22 14:55:33'),
(28, 15, 'purchase_balance', NULL, NULL, NULL, NULL, 10000, 1, '2026-08-22 14:58:02'),
(29, 16, 'invoice_item', 15, 30, NULL, NULL, 6500, 1, '2026-08-23 17:16:41'),
(30, 16, 'invoice_item', 16, 33, NULL, NULL, 1500, 2, '2026-08-23 17:16:41'),
(31, 16, 'invoice_item', 16, 32, NULL, NULL, 3500, 3, '2026-08-23 17:16:41'),
(32, 17, 'invoice_item', 16, 40, NULL, NULL, 2500, 1, '2026-08-24 00:24:36'),
(33, 17, 'invoice_item', 16, 32, NULL, NULL, 6500, 2, '2026-08-24 00:24:36'),
(34, 17, 'invoice_item', 17, 35, NULL, NULL, 1500, 3, '2026-08-24 00:24:36'),
(35, 17, 'invoice_item', 17, 34, NULL, NULL, 2200, 4, '2026-08-24 00:24:36'),
(36, 18, 'invoice_item', 17, 34, NULL, NULL, 7800, 1, '2026-08-27 13:24:38'),
(37, 18, 'invoice_item', 18, 37, NULL, NULL, 1500, 2, '2026-08-27 13:24:38'),
(38, 18, 'invoice_item', 18, 36, NULL, NULL, 2200, 3, '2026-08-27 13:24:38'),
(39, 19, 'service_fee', NULL, NULL, NULL, '2026-08-01', 1500, 1, '2026-08-30 13:27:37'),
(40, 19, 'invoice_item', 3, 55, NULL, NULL, 581, 2, '2026-08-30 13:27:37'),
(41, 19, 'invoice_item', 3, 6, NULL, NULL, 1500, 3, '2026-08-30 13:27:37'),
(42, 19, 'invoice_item', 3, 15, NULL, NULL, 2500, 4, '2026-08-30 13:27:37'),
(43, 19, 'invoice_item', 3, 5, NULL, NULL, 13500, 5, '2026-08-30 13:27:37'),
(44, 20, 'invoice_item', 3, 55, NULL, NULL, 581, 1, '2026-08-30 13:40:54'),
(45, 20, 'invoice_item', 3, 6, NULL, NULL, 1500, 2, '2026-08-30 13:40:54'),
(46, 20, 'invoice_item', 3, 15, NULL, NULL, 2500, 3, '2026-08-30 13:40:54'),
(47, 20, 'invoice_item', 3, 5, NULL, NULL, 15000, 4, '2026-08-30 13:40:54'),
(48, 21, 'invoice_item', 3, 55, NULL, NULL, 581, 1, '2026-08-30 15:13:50'),
(49, 21, 'invoice_item', 3, 6, NULL, NULL, 1500, 2, '2026-08-30 15:13:50'),
(50, 21, 'invoice_item', 3, 15, NULL, NULL, 2500, 3, '2026-08-30 15:13:50'),
(51, 21, 'invoice_item', 3, 5, NULL, NULL, 15000, 4, '2026-08-30 15:13:50'),
(52, 22, 'purchase_balance', NULL, NULL, NULL, NULL, 100, 1, '2026-08-30 22:23:13'),
(53, 22, 'processing_fee', NULL, NULL, NULL, NULL, 34, 2, '2026-08-30 22:23:13'),
(54, 23, 'purchase_balance', NULL, NULL, NULL, NULL, 100, 1, '2026-08-30 22:27:48');

-- --------------------------------------------------------

--
-- Table structure for table `payment_plans`
--

CREATE TABLE `payment_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `plan_number` varchar(40) NOT NULL,
  `apn` varchar(80) DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `asset_description` text DEFAULT NULL,
  `purchase_price` bigint(20) UNSIGNED DEFAULT NULL,
  `documentation_fee_standard` bigint(20) UNSIGNED DEFAULT NULL,
  `documentation_fee_waived` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `documentation_fee_waiver_reason` varchar(500) DEFAULT NULL,
  `original_purchase_balance` bigint(20) UNSIGNED NOT NULL,
  `first_payment_amount` bigint(20) UNSIGNED DEFAULT NULL,
  `customary_monthly_payment` bigint(20) UNSIGNED NOT NULL,
  `monthly_service_fee` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `monthly_due_day` tinyint(3) UNSIGNED NOT NULL,
  `first_due_date` date DEFAULT NULL,
  `plan_start_date` date NOT NULL,
  `maturity_date` date DEFAULT NULL,
  `grace_period_days` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `status` varchar(24) NOT NULL DEFAULT 'draft',
  `automated_reminders_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `scheduled_invoice_email_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `activated_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `updated_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `automatic_invoice_email_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `accelerated_testing_mode` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_plans`
--

INSERT INTO `payment_plans` (`id`, `uuid`, `plan_number`, `apn`, `title`, `asset_description`, `purchase_price`, `documentation_fee_standard`, `documentation_fee_waived`, `documentation_fee_waiver_reason`, `original_purchase_balance`, `first_payment_amount`, `customary_monthly_payment`, `monthly_service_fee`, `monthly_due_day`, `first_due_date`, `plan_start_date`, `maturity_date`, `grace_period_days`, `status`, `automated_reminders_enabled`, `scheduled_invoice_email_enabled`, `activated_at`, `closed_at`, `notes`, `created_by_user_id`, `updated_by_user_id`, `created_at`, `updated_at`, `automatic_invoice_email_enabled`, `accelerated_testing_mode`) VALUES
(1, '378b4398-5c56-403d-a8e8-1ab360f71d31', '333-18-048', '333-18-048', 'Shadehouse Dr. .22 Acres Kingman', NULL, 230000, 24900, 0, NULL, 254900, NULL, 11500, 1500, 3, NULL, '2026-08-10', NULL, 1, 'active', 1, 1, '2026-08-10 00:42:02', NULL, NULL, 1, 1, '2026-08-10 00:42:02', '2026-08-11 16:53:39', 0, 0),
(2, '3cfbb76b-e363-4a00-8eae-f590a658d273', '215-05-196', '215-05-196', '2.35 Acres on Aquarius/Jaguar Golden Valley', NULL, 1650000, 24900, 0, NULL, 1674900, NULL, 28500, 1500, 3, NULL, '2026-08-10', NULL, 4, 'active', 1, 1, '2026-08-10 12:50:07', NULL, NULL, 1, 1, '2026-08-10 12:50:07', '2026-08-10 12:50:07', 0, 0),
(3, '95f45fa7-9427-42c5-9d17-37e4e027d2ce', '217-06-064', '217-06-064', 'Epidote Rd, Golden Valley', NULL, 1090000, 24900, 0, NULL, 1114900, NULL, 18500, 1500, 3, NULL, '2026-08-10', NULL, 5, 'active', 1, 1, '2026-08-10 13:13:08', NULL, NULL, 1, 1, '2026-08-10 13:13:08', '2026-08-10 13:13:08', 0, 0),
(4, '77311b80-8f6e-47bc-98c3-68cabd53369b', '333-18-440 (1)', '333-18-440 (1)', 'Painted Rock Valle Vista North', NULL, 317000, 24900, 0, NULL, 341900, NULL, 13500, 1500, 3, NULL, '2026-08-10', NULL, 5, 'paused', 1, 1, '2026-08-10 13:17:38', NULL, NULL, 1, 1, '2026-08-10 13:17:38', '2026-08-22 15:06:42', 0, 0),
(5, 'fd5ded0c-3b72-4a7a-b8d1-36fe287bf2da', '333-18-162 (2)', '333-18-162 (2)', 'Painted Rock Valle Vista South', NULL, 210000, 24900, 0, NULL, 234900, NULL, 10000, 0, 3, NULL, '2026-08-10', NULL, 5, 'paused', 1, 1, '2026-08-10 13:20:22', NULL, NULL, 1, 1, '2026-08-10 13:20:22', '2026-08-22 17:01:47', 0, 0),
(6, 'aa2d42ec-aefa-4d0a-bc68-393000835259', '333-19-334 (1)', '333-19-334 (1)', 'Painted Rock Valle Vista South', NULL, 305100, 19900, 0, NULL, 325000, NULL, 10000, 1500, 3, NULL, '2026-08-10', NULL, 0, 'active', 1, 1, '2026-08-10 13:31:46', NULL, NULL, 1, 1, '2026-08-10 13:31:46', '2026-08-10 13:31:46', 0, 0),
(7, '0f21f6ae-33a1-4226-90c3-219e2ed32c40', '333-19-335 (2)', '333-19-335 (2)', 'Painted Rock Valle Vista South', NULL, 305100, 19900, 0, NULL, 325000, NULL, 10000, 1500, 3, NULL, '2026-08-10', NULL, 5, 'active', 1, 1, '2026-08-10 13:33:45', NULL, NULL, 1, 1, '2026-08-10 13:33:45', '2026-08-10 13:33:45', 0, 0),
(8, 'f251d5a8-136b-4f65-871e-d3921e465dcf', '215-07-203A', '215-07-203A', 'Jacala Dr. East', NULL, 590000, 24900, 0, NULL, 614900, NULL, 20000, 1500, 3, NULL, '2026-08-10', NULL, 4, 'active', 1, 1, '2026-08-10 13:38:57', NULL, NULL, 1, 1, '2026-08-10 13:38:57', '2026-08-10 13:38:57', 0, 0),
(9, 'e23afee3-cc06-44e7-af8d-7830c19d5bae', '105-28-307', '105-28-307', 'Sun Valley South Quarter Acre', NULL, 299900, 24900, 0, NULL, 324800, NULL, 12000, 1500, 3, NULL, '2026-08-10', NULL, 2, 'active', 1, 1, '2026-08-10 13:50:57', NULL, NULL, 1, 1, '2026-08-10 13:50:57', '2026-08-10 13:50:57', 0, 0),
(10, '0d86e5e5-b277-4899-80d2-7151402581ef', '105-36-113', '105-36-113', 'Sun Valley N of Hwy Quarter Acre', NULL, 299900, 24900, 0, NULL, 324800, NULL, 12000, 1500, 3, NULL, '2026-08-10', NULL, 3, 'active', 1, 1, '2026-08-10 13:58:18', NULL, NULL, 1, 1, '2026-08-10 13:58:18', '2026-08-10 13:58:18', 0, 0),
(11, 'f8f4ca82-8174-4f67-b1a6-a21c6caa3986', '105-21-021', '105-21-021', 'Sun Valley S of Hwy 1.25 Acres', NULL, 549900, 44900, 0, NULL, 594800, NULL, 15000, 1500, 3, NULL, '2026-08-10', NULL, 3, 'active', 1, 1, '2026-08-10 13:59:44', NULL, NULL, 1, 1, '2026-08-10 13:59:44', '2026-08-10 13:59:44', 0, 0),
(12, '5c1c72fc-7a7d-4338-9a3b-d6b52b666116', '105-20-005', '105-20-005', 'Sun Valley S of Hwy 1.25 Acres', NULL, 599900, 44900, 0, NULL, 644800, NULL, 15000, 1500, 3, NULL, '2026-08-10', NULL, 4, 'paused', 1, 1, '2026-08-10 14:01:02', NULL, NULL, 1, 1, '2026-08-10 14:01:02', '2026-08-10 14:01:17', 0, 0),
(13, 'b154101e-9080-4dde-9dd3-b06f5867214e', '217-06-095', '217-06-095', 'Muscovite Rd, Golden Valley', NULL, 1200000, 44900, 0, NULL, 1244900, NULL, 20000, 1500, 3, NULL, '2026-08-10', NULL, 2, 'active', 1, 1, '2026-08-10 14:02:45', NULL, NULL, 1, 1, '2026-08-10 14:02:45', '2026-08-10 14:02:45', 0, 0),
(14, '75626c9b-c9c6-44bd-8282-f44f7e3fd1a9', '314-20-022', '314-20-022', 'Truxton, AZ', NULL, 549500, 44900, 0, NULL, 594400, NULL, 15000, 1500, 3, NULL, '2026-08-10', NULL, 2, 'active', 1, 1, '2026-08-10 14:04:19', NULL, NULL, 1, 1, '2026-08-10 14:04:19', '2026-08-10 14:12:15', 0, 0),
(15, 'a54d63a5-6d87-4cb8-8e9e-6308e1efac42', 'RGRRU1S5L897', 'RGRRU1S5L897', 'Rio Grande River Ranchos Unit 1, S5, L897', NULL, 299900, 44900, 0, NULL, 344800, NULL, 12000, 1500, 3, NULL, '2026-08-10', NULL, 1, 'active', 1, 1, '2026-08-10 14:08:10', NULL, NULL, 1, 1, '2026-08-10 14:08:10', '2026-08-10 14:08:10', 0, 0),
(16, '58c5b5c6-c48f-4a11-b443-de83964736a4', '105-32-037', '105-32-037', 'Holbrook - 7628 Pine St .25 Acres', NULL, 299900, 44900, 0, NULL, 344800, NULL, 13000, 1500, 3, NULL, '2026-08-10', NULL, 4, 'active', 1, 1, '2026-08-10 14:10:31', NULL, NULL, 1, 1, '2026-08-10 14:10:31', '2026-08-10 14:10:31', 0, 0),
(17, '91aa1ba0-cef5-4c76-b566-8dea8add047e', 'Testprop1', 'Testprop1', '1.14 Imaginary Acres of land', NULL, 250000, 24900, 0, NULL, 274900, NULL, 10000, 1500, 3, NULL, '2026-08-11', NULL, 1, 'active', 1, 1, '2026-08-11 13:51:44', NULL, NULL, 1, 1, '2026-08-11 13:51:44', '2026-08-28 19:13:24', 1, 1),
(18, '02b869ce-7ed7-4511-b90d-960d11688b60', 'Testprop2', 'Testprop2', '2.35 Acres of Imaginary land', NULL, 300000, 24900, 0, NULL, 324900, NULL, 12000, 1500, 14, NULL, '2026-08-11', NULL, 0, 'active', 1, 1, '2026-08-11 17:49:55', NULL, NULL, 1, 1, '2026-08-11 17:49:55', '2026-08-13 21:33:40', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `payment_plan_billing_terms`
--

CREATE TABLE `payment_plan_billing_terms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `frequency` varchar(24) NOT NULL DEFAULT 'monthly',
  `invoice_day` tinyint(3) UNSIGNED NOT NULL,
  `due_days_after_issue` smallint(5) UNSIGNED NOT NULL,
  `grace_days` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `scheduled_payment_amount` bigint(20) UNSIGNED NOT NULL,
  `monthly_service_fee` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `stage_one_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `stage_one_fee_type` varchar(24) DEFAULT NULL,
  `stage_one_fixed_amount` bigint(20) UNSIGNED DEFAULT NULL,
  `stage_one_percentage_rate` decimal(7,4) DEFAULT NULL,
  `stage_one_minimum_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `stage_one_days_late` smallint(5) UNSIGNED DEFAULT NULL,
  `stage_two_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `stage_two_fee_type` varchar(24) DEFAULT NULL,
  `stage_two_fixed_amount` bigint(20) UNSIGNED DEFAULT NULL,
  `stage_two_percentage_rate` decimal(7,4) DEFAULT NULL,
  `stage_two_minimum_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `stage_two_days_late` smallint(5) UNSIGNED DEFAULT NULL,
  `default_eligibility_days` smallint(5) UNSIGNED NOT NULL DEFAULT 60,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_plan_billing_terms`
--

INSERT INTO `payment_plan_billing_terms` (`id`, `payment_plan_id`, `frequency`, `invoice_day`, `due_days_after_issue`, `grace_days`, `scheduled_payment_amount`, `monthly_service_fee`, `stage_one_enabled`, `stage_one_fee_type`, `stage_one_fixed_amount`, `stage_one_percentage_rate`, `stage_one_minimum_amount`, `stage_one_days_late`, `stage_two_enabled`, `stage_two_fee_type`, `stage_two_fixed_amount`, `stage_two_percentage_rate`, `stage_two_minimum_amount`, `stage_two_days_late`, `default_eligibility_days`, `effective_from`, `effective_to`, `reason`, `created_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'monthly', 3, 5, 1, 11500, 1500, 1, 'fixed', 2500, NULL, 0, 2, 0, NULL, NULL, NULL, 0, NULL, 60, '2024-12-23', '2026-08-09', 'previously paid in credit', 1, '2026-08-10 00:42:02', '2026-08-10 00:58:38'),
(2, 1, 'monthly', 3, 5, 1, 11500, 1500, 1, 'fixed', 2500, NULL, 0, 2, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', '2026-08-11', 'previously paid in credit', 1, '2026-08-10 00:58:38', '2026-08-11 16:53:39'),
(3, 2, 'monthly', 3, 5, 4, 28500, 1500, 1, 'fixed', 2500, NULL, 0, 5, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 12:50:07', '2026-08-10 12:50:07'),
(4, 3, 'monthly', 3, 5, 5, 18500, 1500, 1, 'fixed', 2500, NULL, 0, 6, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 13:13:08', '2026-08-10 13:13:08'),
(5, 4, 'monthly', 3, 5, 5, 13500, 1500, 1, 'fixed', 2500, NULL, 0, 6, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', '2026-08-10', NULL, 1, '2026-08-10 13:17:38', '2026-08-10 13:20:52'),
(6, 5, 'monthly', 3, 5, 5, 10000, 0, 1, 'fixed', 2500, NULL, 0, 6, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', '2026-08-22', NULL, 1, '2026-08-10 13:20:22', '2026-08-22 17:01:47'),
(7, 4, 'monthly', 3, 5, 5, 13500, 1500, 1, 'fixed', 2500, NULL, 0, 6, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-11', '2026-08-22', 'Property desription change', 1, '2026-08-10 13:20:52', '2026-08-22 15:06:42'),
(8, 6, 'monthly', 3, 5, 0, 10000, 1500, 1, 'fixed', 2500, NULL, 0, 1, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 13:31:46', '2026-08-10 13:31:46'),
(9, 7, 'monthly', 3, 5, 5, 10000, 1500, 1, 'fixed', 2500, NULL, 0, 6, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 13:33:45', '2026-08-10 13:33:45'),
(10, 8, 'monthly', 3, 5, 4, 20000, 1500, 1, 'fixed', 2500, NULL, 0, 5, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 13:38:57', '2026-08-10 13:38:57'),
(11, 9, 'monthly', 3, 5, 2, 12000, 1500, 1, 'fixed', 2500, NULL, 0, 3, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 13:50:57', '2026-08-10 13:50:57'),
(12, 10, 'monthly', 3, 5, 3, 12000, 1500, 1, 'fixed', 2500, NULL, 0, 4, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 13:58:18', '2026-08-10 13:58:18'),
(13, 11, 'monthly', 3, 5, 3, 15000, 1500, 1, 'fixed', 2500, NULL, 0, 4, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 13:59:44', '2026-08-10 13:59:44'),
(14, 12, 'monthly', 3, 5, 4, 15000, 1500, 1, 'fixed', 2500, NULL, 0, 5, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 14:01:02', '2026-08-10 14:01:02'),
(15, 13, 'monthly', 3, 5, 2, 20000, 1500, 1, 'fixed', 2500, NULL, 0, 3, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 14:02:45', '2026-08-10 14:02:45'),
(16, 14, 'monthly', 3, 5, 2, 15000, 1500, 1, 'fixed', 2500, NULL, 0, 3, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-02-05', '2026-08-10', NULL, 1, '2026-08-10 14:04:19', '2026-08-10 14:12:15'),
(17, 15, 'monthly', 3, 5, 1, 12000, 1500, 1, 'fixed', 2500, NULL, 0, 2, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 14:08:10', '2026-08-10 14:08:10'),
(18, 16, 'monthly', 3, 5, 4, 13000, 1500, 1, 'fixed', 2500, NULL, 0, 5, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-10', NULL, NULL, 1, '2026-08-10 14:10:31', '2026-08-10 14:10:31'),
(19, 14, 'monthly', 3, 5, 2, 15000, 1500, 1, 'fixed', 2500, NULL, 0, 3, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-11', NULL, 'Amend contract start date to Landpay date', 1, '2026-08-10 14:12:15', '2026-08-10 14:12:15'),
(20, 17, 'monthly', 3, 5, 4, 10000, 1500, 1, 'fixed', 2500, NULL, 0, 5, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-11', '2026-08-11', NULL, 1, '2026-08-11 13:51:44', '2026-08-11 13:52:09'),
(21, 17, 'monthly', 3, 5, 4, 10000, 1500, 1, 'fixed', 2500, NULL, 0, 5, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-12', '2026-08-28', 'Test mode enabled', 1, '2026-08-11 13:52:09', '2026-08-28 19:04:33'),
(22, 1, 'monthly', 3, 5, 1, 11500, 1500, 1, 'fixed', 2500, NULL, 0, 2, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-12', NULL, 'Adjust start date', 1, '2026-08-11 16:53:39', '2026-08-11 16:53:39'),
(23, 18, 'monthly', 3, 5, 0, 12000, 1500, 1, 'fixed', 2500, NULL, 0, 1, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-11', '2026-08-13', NULL, 1, '2026-08-11 17:49:55', '2026-08-13 21:33:40'),
(24, 18, 'monthly', 14, 5, 0, 12000, 1500, 1, 'fixed', 2500, NULL, 0, 1, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-14', NULL, 'change date', 1, '2026-08-13 21:33:40', '2026-08-13 21:33:40'),
(25, 4, 'monthly', 3, 5, 5, 13500, 1500, 1, 'fixed', 2500, NULL, 0, 6, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-23', NULL, 'adjust import information', 1, '2026-08-22 15:06:42', '2026-08-22 15:06:42'),
(26, 5, 'monthly', 3, 5, 5, 10000, 0, 1, 'fixed', 2500, NULL, 0, 6, 0, NULL, NULL, NULL, 0, NULL, 60, '2026-08-23', NULL, 'correct import details', 1, '2026-08-22 17:01:47', '2026-08-22 17:01:47'),
(27, 17, 'monthly', 3, 3, 2, 10000, 1500, 1, 'fixed', 2500, NULL, 0, 3, 1, 'fixed', 5000, NULL, 0, 6, 60, '2026-08-29', '2026-08-28', 'adjust late fees', 1, '2026-08-28 19:04:33', '2026-08-28 19:13:24'),
(28, 17, 'monthly', 3, 3, 1, 10000, 1500, 1, 'fixed', 2500, NULL, 0, 2, 1, 'fixed', 5000, NULL, 0, 6, 60, '2026-08-29', NULL, 'change grace days', 1, '2026-08-28 19:13:24', '2026-08-28 19:13:24');

-- --------------------------------------------------------

--
-- Table structure for table `payment_plan_clients`
--

CREATE TABLE `payment_plan_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(24) NOT NULL,
  `responsibility` varchar(24) NOT NULL DEFAULT 'joint',
  `receives_invoices` tinyint(1) NOT NULL DEFAULT 1,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `end_reason` varchar(255) DEFAULT NULL,
  `contact_risk_acknowledged_at` timestamp NULL DEFAULT NULL,
  `contact_risk_acknowledgment_method` varchar(64) DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `ended_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_plan_clients`
--

INSERT INTO `payment_plan_clients` (`id`, `payment_plan_id`, `client_id`, `role`, `responsibility`, `receives_invoices`, `effective_from`, `effective_to`, `end_reason`, `contact_risk_acknowledged_at`, `contact_risk_acknowledgment_method`, `created_by_user_id`, `ended_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'primary', 'joint', 1, '2024-12-23', NULL, NULL, '2026-08-10 00:42:02', 'admin_contract_acceptance', 1, NULL, '2026-08-10 00:42:02', '2026-08-10 00:42:02'),
(2, 2, 2, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 12:50:07', 'admin_contract_acceptance', 1, NULL, '2026-08-10 12:50:07', '2026-08-10 12:50:07'),
(3, 3, 3, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 13:13:08', 'admin_contract_acceptance', 1, NULL, '2026-08-10 13:13:08', '2026-08-10 13:13:08'),
(4, 4, 4, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 13:17:38', 'admin_contract_acceptance', 1, NULL, '2026-08-10 13:17:38', '2026-08-10 13:17:38'),
(5, 5, 4, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 13:20:22', 'admin_contract_acceptance', 1, NULL, '2026-08-10 13:20:22', '2026-08-10 13:20:22'),
(6, 6, 5, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 13:31:46', 'admin_contract_acceptance', 1, NULL, '2026-08-10 13:31:46', '2026-08-10 13:31:46'),
(7, 7, 5, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 13:33:45', 'admin_contract_acceptance', 1, NULL, '2026-08-10 13:33:45', '2026-08-10 13:33:45'),
(8, 8, 7, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 13:38:57', 'admin_contract_acceptance', 1, NULL, '2026-08-10 13:38:57', '2026-08-10 13:38:57'),
(9, 9, 8, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 13:50:57', 'admin_contract_acceptance', 1, NULL, '2026-08-10 13:50:57', '2026-08-10 13:50:57'),
(10, 10, 9, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 13:58:18', 'admin_contract_acceptance', 1, NULL, '2026-08-10 13:58:18', '2026-08-10 13:58:18'),
(11, 11, 10, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 13:59:44', 'admin_contract_acceptance', 1, NULL, '2026-08-10 13:59:44', '2026-08-10 13:59:44'),
(12, 12, 11, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 14:01:02', 'admin_contract_acceptance', 1, NULL, '2026-08-10 14:01:02', '2026-08-10 14:01:02'),
(13, 13, 12, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 14:02:45', 'admin_contract_acceptance', 1, NULL, '2026-08-10 14:02:45', '2026-08-10 14:02:45'),
(14, 14, 13, 'primary', 'joint', 1, '2026-02-05', NULL, NULL, '2026-08-10 14:04:19', 'admin_contract_acceptance', 1, NULL, '2026-08-10 14:04:19', '2026-08-10 14:04:19'),
(15, 15, 14, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 14:08:10', 'admin_contract_acceptance', 1, NULL, '2026-08-10 14:08:10', '2026-08-10 14:08:10'),
(16, 16, 15, 'primary', 'joint', 1, '2026-08-10', NULL, NULL, '2026-08-10 14:10:31', 'admin_contract_acceptance', 1, NULL, '2026-08-10 14:10:31', '2026-08-10 14:10:31'),
(17, 17, 16, 'primary', 'joint', 1, '2026-08-11', NULL, NULL, '2026-08-11 13:51:44', 'admin_contract_acceptance', 1, NULL, '2026-08-11 13:51:44', '2026-08-11 13:51:44'),
(18, 18, 17, 'primary', 'joint', 1, '2026-08-11', NULL, NULL, '2026-08-11 17:49:55', 'admin_contract_acceptance', 1, NULL, '2026-08-11 17:49:55', '2026-08-11 17:49:55');

-- --------------------------------------------------------

--
-- Table structure for table `payment_plan_pauses`
--

CREATE TABLE `payment_plan_pauses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `pause_date` date NOT NULL,
  `planned_resume_date` date DEFAULT NULL,
  `resume_date` date DEFAULT NULL,
  `reason` varchar(500) NOT NULL,
  `resume_note` varchar(500) DEFAULT NULL,
  `paused_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `resumed_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `resumed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_plan_pauses`
--

INSERT INTO `payment_plan_pauses` (`id`, `payment_plan_id`, `pause_date`, `planned_resume_date`, `resume_date`, `reason`, `resume_note`, `paused_by_user_id`, `resumed_by_user_id`, `resumed_at`, `created_at`, `updated_at`) VALUES
(1, 12, '2026-08-10', NULL, NULL, 'Client manual payments', NULL, 1, NULL, NULL, '2026-08-10 14:01:17', '2026-08-10 14:01:17'),
(2, 5, '2026-08-10', NULL, NULL, 'Client manual payments', NULL, 1, NULL, NULL, '2026-08-10 14:41:13', '2026-08-10 14:41:13'),
(3, 4, '2026-08-10', NULL, NULL, 'Client manual payments', NULL, 1, NULL, NULL, '2026-08-10 14:41:28', '2026-08-10 14:41:28');

-- --------------------------------------------------------

--
-- Table structure for table `portal_accounts`
--

CREATE TABLE `portal_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(254) NOT NULL,
  `password` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `portal_accounts`
--

INSERT INTO `portal_accounts` (`id`, `client_id`, `email`, `password`, `enabled`, `remember_token`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 17, 'chris@mohavedeals.com', '$2y$12$lX9T3wXKdHQlOrKUH7LPButvnwU9qft6jh4iVvq5twLT4q/wbiSwm', 1, NULL, '2026-08-31 12:05:23', '2026-08-11 17:48:52', '2026-08-31 12:05:23'),
(2, 16, 'joygr8@yahoo.com', '$2y$12$gOLe7Ztz1Lbpesay54dQMud7bQtEpWz0wBgRC6V6NBD6ur0N2OzIO', 1, 'm2hVcb0yc4gXUWe1GfyAZb9L1BJOsTTlDhWE98e3DKgAiySSWJe6vyoDjXNB', '2026-08-28 18:02:52', '2026-08-12 20:39:27', '2026-08-28 18:02:52'),
(3, 1, 'ernesth33jr@gmail.com', '$2y$12$vH9Iq7da9.PzLpIlhHTsrOUTYyeW2NwM/L/58553RIBVRjRGHVGgq', 1, 'TBdRPBVoFx6sbaJzvliOhqYugtwWWL3wKlKFQ3sSWETtGdp17plVTy7rWPlw', '2026-08-17 15:47:24', '2026-08-17 15:46:53', '2026-08-17 15:47:24'),
(4, 2, 'tamiwicchick@aol.com', '$2y$12$lUQpHfEM5hNx9u1sOcRWVuUblZ0aE03FLTGnOF1k.b1vO7kbn3QEG', 1, 'qH6eO6ZDkwxP9wYL6mXHbsw37iWUWi9AXbWtK33efpMRQvq6NVDujNfZlt0R', '2026-08-21 15:02:46', '2026-08-21 15:02:27', '2026-08-21 15:02:46');

-- --------------------------------------------------------

--
-- Table structure for table `portal_invitations`
--

CREATE TABLE `portal_invitations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `invited_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(254) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `encrypted_token` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `portal_invitations`
--

INSERT INTO `portal_invitations` (`id`, `client_id`, `invited_by_user_id`, `email`, `token_hash`, `encrypted_token`, `expires_at`, `accepted_at`, `revoked_at`, `created_at`, `updated_at`) VALUES
(1, 17, 1, 'chris@mohavedeals.com', '596a788bac73f82c261242fbbdbf46f35bf8bffbb47ccaf7404b7fc25cec654a', NULL, '2026-08-13 17:48:39', '2026-08-11 17:48:52', NULL, '2026-08-11 17:48:39', '2026-08-11 17:48:52'),
(2, 16, 1, 'joygr8@yahoo.com', '897987850d786bca5db26910797e86746fdca63d18c85584fcacf461f8fae929', NULL, '2026-08-14 16:44:24', '2026-08-12 20:39:27', NULL, '2026-08-12 16:44:24', '2026-08-12 20:39:27'),
(3, 1, 1, 'ernesth33jr@gmail.com', '167b46b10161bdf4368c869e683e1aa2eba32addd439dad7cfec267496a51cd6', NULL, '2026-08-15 20:43:23', NULL, '2026-08-17 10:48:15', '2026-08-13 20:43:23', '2026-08-17 10:48:15'),
(4, 1, 1, 'ernesth33jr@gmail.com', '34c9e0052844b1aa16d18d840fca0afc6b8500058f86d1132ad23c70cbc43a74', 'eyJpdiI6Ijk1eit3ZWZNUG1XODZ4Q09SVzhJTkE9PSIsInZhbHVlIjoiZXVyNjU5ZDM0eXAzSDE3NHNWejE4NTVLMFJweFdpeWJ6cldnNmpzNXc5QjVXbFkrQmNUYlcyTmR5QkJlcGQ2VXdLdkVrV0JiQTl1Z1Aza0U4WUhHcEwzVkVJbTVSNnpjSTlWcDdpVXc1Y1E9IiwibWFjIjoiMGQzODA3MDdlZTJkMWRhMDM3MDY1ZjQyOGE3ZGVkYjNjY2YyYjM5NzcwNjRkY2ZmMDVjMGZiYzBhNGMwZGMzZSIsInRhZyI6IiJ9', '2026-08-19 10:48:15', '2026-08-17 15:46:53', NULL, '2026-08-17 10:48:15', '2026-08-17 15:46:53'),
(5, 2, 1, 'tamiwicchick@aol.com', '45e6709e22377017be1a41163cb424e87549eee7cfe12afaa4b8df1ce61abb99', 'eyJpdiI6ImV6eHIzYWppZjVobktsTzhNb0FPWVE9PSIsInZhbHVlIjoiVTkxMS96elMvR0pkOVEyNXdUOTRtVkR2cXdiMHR6aFlPdVIreTlsWEdNMkU4QVdrVkVaNnQxN2FEbHlabDVuTHpFcGJCWFlwRU4wS3ZxQVRLT0pwbmlyM1ZUSXRmc3dXSE13aUVHSDh0VnM9IiwibWFjIjoiMmEzMjQzNGI0OTg2N2VkOTNjYWQ0NTMzZmRmNDVhMTFkODkxOGZmYzkxZmVkNDZmY2U1NGEwZDFjNGM4YmE5MCIsInRhZyI6IiJ9', '2026-08-22 11:24:58', '2026-08-21 15:02:27', NULL, '2026-08-20 11:24:58', '2026-08-21 15:02:27');

-- --------------------------------------------------------

--
-- Table structure for table `recurring_fee_rules`
--

CREATE TABLE `recurring_fee_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `amount` bigint(20) UNSIGNED NOT NULL,
  `frequency` varchar(24) NOT NULL DEFAULT 'monthly',
  `due_day` tinyint(3) UNSIGNED NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'active',
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `updated_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `secure_messages`
--

CREATE TABLE `secure_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `secure_message_thread_id` bigint(20) UNSIGNED NOT NULL,
  `sender_type` varchar(20) NOT NULL,
  `sender_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sender_client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `body` text NOT NULL,
  `attachment_disk` varchar(30) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_mime` varchar(100) DEFAULT NULL,
  `attachment_size` bigint(20) UNSIGNED DEFAULT NULL,
  `client_viewed_at` timestamp NULL DEFAULT NULL,
  `admin_viewed_at` timestamp NULL DEFAULT NULL,
  `attachment_downloaded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `secure_messages`
--

INSERT INTO `secure_messages` (`id`, `uuid`, `secure_message_thread_id`, `sender_type`, `sender_user_id`, `sender_client_id`, `body`, `attachment_disk`, `attachment_path`, `attachment_name`, `attachment_mime`, `attachment_size`, `client_viewed_at`, `admin_viewed_at`, `attachment_downloaded_at`, `created_at`, `updated_at`) VALUES
(3, 'f2ae1250-ff41-4edc-98ef-ea3d30f3e28f', 2, 'admin', 1, NULL, 'This is a message through the landpay message system, let me know if you get it by replying here.', NULL, NULL, NULL, NULL, NULL, '2026-08-12 20:41:57', NULL, NULL, '2026-08-12 18:46:45', '2026-08-12 20:41:57'),
(4, '754ebb5c-fc95-4bbe-8f82-fd4f80f12302', 2, 'client', NULL, 16, 'Message received', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-12 20:53:55', NULL, '2026-08-12 20:45:51', '2026-08-12 20:53:55'),
(5, '6dc959ba-de4b-426b-a96d-fee4f08fb207', 2, 'admin', 1, NULL, 'Applied the payment, please let me know if everything looks ok and is understandable from your point of view.', NULL, NULL, NULL, NULL, NULL, '2026-08-12 21:12:27', NULL, NULL, '2026-08-12 21:01:35', '2026-08-12 21:12:27'),
(6, '07620e47-ef20-4953-9559-7078ddf08f8d', 2, 'client', NULL, 16, 'Yes everything is fine.  Question? should you put a note that says the $15.00 fee monthly payment does not reduce the principal balance and is strictly for (can\'t remember what it said.  That way there will be no confusion when they print out their receipt.  And in fact is that true?', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-12 21:36:44', NULL, '2026-08-12 21:17:59', '2026-08-12 21:36:44'),
(7, 'd87c39f3-db99-4975-90ef-95df9c0a5ca1', 2, 'client', NULL, 16, 'Also when I select Browse it comes up with your desktop download items.', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-12 21:36:44', NULL, '2026-08-12 21:19:27', '2026-08-12 21:36:44'),
(8, 'e31cc600-a724-47c7-9988-b5da58f6f10f', 2, 'admin', 1, NULL, '1)Yes, that is established with the initial contract. \r\n2) browse is to upload a document or picture into this chat. Here\'s an example.', 'local', 'secure-messages/63955cf0-96b3-4b3b-91d9-1440e2fd2dc0.jpg', '20260503_104022 (Large).jpg', 'image/jpeg', 444597, '2026-08-12 22:10:54', NULL, NULL, '2026-08-12 21:38:18', '2026-08-12 22:10:54'),
(9, '857d3468-3cae-479d-b4ee-a46e03c05a46', 3, 'admin', 1, NULL, 'Send a payment notice for double this time $230, and click keep as account credit toward next invoice and see if it automatically pays the next invoice after this one.\r\nThank you,\r\nLove,\r\nChris', NULL, NULL, NULL, NULL, NULL, '2026-08-15 16:40:51', NULL, NULL, '2026-08-15 13:13:55', '2026-08-15 16:40:51'),
(10, '1f00a034-a153-4820-ba37-39613fb436ed', 3, 'admin', 1, NULL, 'Perfect, thanks.  Let me know if the next invoice automatically uses this credit. \r\nLove,\r\nChris', NULL, NULL, NULL, NULL, NULL, '2026-08-15 22:18:06', NULL, NULL, '2026-08-15 21:07:03', '2026-08-15 22:18:06'),
(11, '8aaf18c8-9e3f-455d-bfda-3e33f972b701', 4, 'admin', 1, NULL, 'Please keep playing around with this, I\'ve noticed a couple of other improvements I\'ve made as a result - very helpful thankyou.\r\nLove,\r\nChris', NULL, NULL, NULL, NULL, NULL, '2026-08-16 04:14:16', NULL, NULL, '2026-08-16 01:57:59', '2026-08-16 04:14:16'),
(12, '355b700b-3892-49de-8e0d-51c0d8e7fb53', 5, 'admin', 1, NULL, 'There is a new documents area to share documents.  Notice the tab next to messages.', NULL, NULL, NULL, NULL, NULL, '2026-08-16 04:14:58', NULL, NULL, '2026-08-16 01:59:23', '2026-08-16 04:14:58'),
(13, 'bfae14f4-aa5e-4f79-a5ab-f1df06b2d8bf', 4, 'client', NULL, 16, 'Will do. Love,Mom', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-16 10:33:52', NULL, '2026-08-16 04:14:46', '2026-08-16 10:33:52'),
(14, 'f481369c-2c07-42dd-b75f-4b42d66ee495', 4, 'client', NULL, 16, 'How do I get my unpaid balance as noted when I go to Get Help:  Secure access\r\nYour account documents\r\nSee balances and due dates, open invoices, and keep a permanent payment receipt history.', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-16 17:33:48', NULL, '2026-08-16 15:47:04', '2026-08-16 17:33:48'),
(15, '929d2127-2176-4984-a458-cb5f768b0233', 4, 'admin', 1, NULL, 'The current due invoice amount is shown on the main page of your client portal.\r\n\r\nThe balance due to payoff the entire contract is shown by clicking \"Account\" and looking at the bottom of the page under the card where you can change your contact info or password.', NULL, NULL, NULL, NULL, NULL, '2026-08-16 17:57:12', NULL, NULL, '2026-08-16 17:36:07', '2026-08-17 11:13:48'),
(16, 'a37d99b5-c285-4e7b-b740-f1f478b3350f', 6, 'admin', 1, NULL, 'Hi Ernie, \r\n\r\nYes, you are very close to payoff, congratulations!  If you can pay $165 next month that will complete your payoff.  (Otherwise you can make your normal payment, and the October payoff will be $50.)\r\n\r\nOnce the final payment is made there is a form that I will prepare for you here.  You will need to print, have notarized, and mail back to us.  The county requires the actual wet signature notarized copy, so the original needs to be mailed.  I take care of all the other steps including filing your deed with the county, and they will mail your recorded deed directly to you at whatever address you provide.  Your future tax bills will also go to the same address.\r\n\r\nFor your question about leaving the property to someone when you pass, this can be accomplished through an additional Beneficiary Deed.  I can  prepare and draft one for you as well as file it along with the other paperwork for an additional $80 if you would like.  This would be one more form that you would need to have notarized, and mail back along with the other.\r\n\r\nFor the beneficiary - no, she will not need to sign anything or do anything.  You actually don’t even need to notify her.  Arizona law specifically says that the beneficiary\'s signature, consent, agreement, and even notice to the beneficiary are not required during the owner\'s lifetime. (Obviously you should have a will or some other method to notify the person if you haven’t told them prior)  \r\n\r\nTheir full legal name including middle name is all you need for this.  You can also name a successor beneficiary should you outlive the first.  Let me know if you have any questions about this.\r\n\r\n-Chris', NULL, NULL, NULL, NULL, NULL, '2026-08-17 15:47:52', NULL, NULL, '2026-08-17 11:19:14', '2026-08-17 15:47:52'),
(17, '4e8c8b65-80d6-4ad8-94d2-faf7a37a27a3', 7, 'client', NULL, 16, 'Wehhere do I find the original purchase price so I can check my balance after payment?', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 18:17:16', NULL, '2026-08-21 17:56:57', '2026-08-21 18:17:16'),
(18, 'b329b92b-e44c-4a22-be14-53076859def6', 7, 'admin', 1, NULL, 'It will show on the account page, not having it there was an oversight on my part but I will add it.', NULL, NULL, NULL, NULL, NULL, '2026-08-21 20:16:58', NULL, NULL, '2026-08-21 18:54:09', '2026-08-21 20:16:58'),
(19, 'd9b1ed66-8157-4cfb-8461-b8f9b375ee4d', 7, 'client', NULL, 16, 'Great.', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 20:44:02', NULL, '2026-08-21 20:17:37', '2026-08-21 20:44:02'),
(20, '13adf23d-7500-423a-88cb-cf6d63970274', 7, 'admin', 1, NULL, 'Client portal update is complete, you\'ll see the purchase price on the bottom of the account page now.', NULL, NULL, NULL, NULL, NULL, '2026-08-22 01:05:06', NULL, NULL, '2026-08-21 23:47:57', '2026-08-22 01:05:06'),
(21, '712841a8-02fd-4226-ad04-b097b5930752', 7, 'client', NULL, 16, 'Okay I see that now.  What happened to the list of invoice payments?  Can\'t find them anymore.', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-22 11:02:07', NULL, '2026-08-22 01:16:12', '2026-08-22 11:02:07'),
(22, '8bcd8269-2663-4584-9c45-765096e68799', 7, 'admin', 1, NULL, 'I think you\'re talking about the information in the 2 cards at the bottom of the main dashboard page.', NULL, NULL, NULL, NULL, NULL, '2026-08-22 17:28:04', NULL, NULL, '2026-08-22 11:03:36', '2026-08-22 17:28:04'),
(23, '2b689917-9f3d-4ecc-8910-a0660f8ceb43', 8, 'admin', 1, NULL, 'Are you getting any of the following regarding late invoices:\r\n1) notice on the client portal page\r\n2) emails', NULL, NULL, NULL, NULL, NULL, '2026-08-28 01:01:31', NULL, NULL, '2026-08-27 13:35:50', '2026-08-28 01:01:31'),
(24, '187ec08c-8686-411c-aaa8-a627d94b4bc9', 8, 'client', NULL, 16, 'Gota bunch and thought I would see what happened with a late payment if I ignored it.  That plus it takes so long to open any emails in Yahoo.', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-28 01:23:11', NULL, '2026-08-28 01:02:50', '2026-08-28 01:23:11'),
(25, '4b9b95b6-7cf9-4e9c-8b79-c5329803dd16', 8, 'admin', 1, NULL, 'Logged in and cleared cache and rebooted firefox, should be all set.', NULL, NULL, NULL, NULL, NULL, '2026-08-28 18:02:53', NULL, NULL, '2026-08-28 01:24:15', '2026-08-28 18:02:53'),
(26, 'e9a309b4-8f8f-4114-a75c-8a807460b2ea', 8, 'client', NULL, 16, 'thanks. slightly better but still not like before.  I am blocking and deleting a slew of things to see if that will help.  thanks for all your help.  Love', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-28 18:55:30', NULL, '2026-08-28 18:04:26', '2026-08-28 18:55:30');

-- --------------------------------------------------------

--
-- Table structure for table `secure_message_attachments`
--

CREATE TABLE `secure_message_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `secure_message_id` bigint(20) UNSIGNED NOT NULL,
  `disk` varchar(30) NOT NULL DEFAULT 'local',
  `path` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mime` varchar(100) NOT NULL,
  `size` bigint(20) UNSIGNED NOT NULL,
  `client_downloaded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `secure_message_attachments`
--

INSERT INTO `secure_message_attachments` (`id`, `uuid`, `secure_message_id`, `disk`, `path`, `name`, `mime`, `size`, `client_downloaded_at`, `created_at`, `updated_at`) VALUES
(1, '46234cc6-2850-4e85-9c28-1e36ddf5d5f5', 22, 'local', 'secure-message-attachments/f60ac31f-a42a-46fb-9afa-c3745bbb39ce.png', 'image.png', 'image/png', 77779, NULL, '2026-08-22 11:03:36', '2026-08-22 11:03:36');

-- --------------------------------------------------------

--
-- Table structure for table `secure_message_documents`
--

CREATE TABLE `secure_message_documents` (
  `secure_message_id` bigint(20) UNSIGNED NOT NULL,
  `shared_document_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `secure_message_documents`
--

INSERT INTO `secure_message_documents` (`secure_message_id`, `shared_document_id`, `created_at`, `updated_at`) VALUES
(12, 1, '2026-08-16 01:59:23', '2026-08-16 01:59:23');

-- --------------------------------------------------------

--
-- Table structure for table `secure_message_revisions`
--

CREATE TABLE `secure_message_revisions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `secure_message_id` bigint(20) UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `edited_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `secure_message_revisions`
--

INSERT INTO `secure_message_revisions` (`id`, `secure_message_id`, `body`, `edited_by_user_id`, `created_at`) VALUES
(1, 15, 'The current due invoice amount is shown on the main page of your client portal.\r\n\r\nThe balance due to payoff the entire contract is shown by clicking \"Account\" and looking at the bottom of the page under where you can change your contact info or password.', 1, '2026-08-17 11:13:48'),
(2, 25, 'Will log in now', 1, '2026-08-28 01:28:29');

-- --------------------------------------------------------

--
-- Table structure for table `secure_message_threads`
--

CREATE TABLE `secure_message_threads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `category` varchar(30) NOT NULL DEFAULT 'general',
  `starred_at` timestamp NULL DEFAULT NULL,
  `latest_message_at` timestamp NULL DEFAULT NULL,
  `notification_last_sent_at` timestamp NULL DEFAULT NULL,
  `notification_status` varchar(20) DEFAULT NULL,
  `reminder_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `secure_message_threads`
--

INSERT INTO `secure_message_threads` (`id`, `uuid`, `client_id`, `payment_plan_id`, `subject`, `category`, `starred_at`, `latest_message_at`, `notification_last_sent_at`, `notification_status`, `reminder_count`, `created_at`, `updated_at`) VALUES
(2, 'e03a5403-9599-43ee-b7d5-8b271442b144', 16, NULL, 'Let me know if you get this', 'general', NULL, '2026-08-12 21:38:18', '2026-08-12 21:38:18', 'sent', 0, '2026-08-12 18:46:45', '2026-08-12 21:38:18'),
(3, '96b27f41-161f-4f10-a7ef-958815bcefdc', 16, NULL, 'Next payment', 'general', NULL, '2026-08-15 21:07:03', '2026-08-15 21:07:04', 'sent', 0, '2026-08-15 13:13:55', '2026-08-15 21:07:04'),
(4, 'a8728db7-1c0b-46b6-9366-2a98923c8a5e', 16, 17, 'Thank you', 'general', NULL, '2026-08-16 17:36:07', '2026-08-16 17:36:07', 'sent', 0, '2026-08-16 01:57:59', '2026-08-16 17:36:07'),
(5, '08eec4c7-15b4-4d2b-b5e9-f7c5c8968ee8', 16, 17, 'New document: File0361.JPG', 'general', NULL, '2026-08-16 01:59:23', '2026-08-16 01:59:23', 'sent', 0, '2026-08-16 01:59:23', '2026-08-16 01:59:23'),
(6, '4beb10ad-7eef-4faa-9390-dc9ac0eea412', 1, 1, 'Deed / Closing / Beneficiary Questions', 'general', NULL, '2026-08-17 11:19:14', '2026-08-17 11:19:14', 'sent', 0, '2026-08-17 11:19:14', '2026-08-17 13:48:55'),
(7, '2ec69cd6-0dbd-43da-a7d1-15e47b43c1ae', 16, NULL, 'Original pjurchase price of property', 'general', NULL, '2026-08-22 11:03:36', '2026-08-22 11:03:41', 'sent', 0, '2026-08-21 17:56:57', '2026-08-22 11:03:41'),
(8, '91c7cb9b-c97d-4311-93b7-17813886b780', 16, 17, 'Notices', 'general', NULL, '2026-08-28 18:04:26', '2026-08-28 01:24:16', 'sent', 0, '2026-08-27 13:35:50', '2026-08-28 18:04:26');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('bosVZEMLBkdZOovbxHp2uAgqqdhaFIUZPkSSd4dk', NULL, '168.144.120.239', 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0', 'eyJfdG9rZW4iOiI4bmpxRG1LaGFqdlhGb2xsaW13QWI4VUFDTkFoZVdCdzZ1SGxpSWt0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9sYW5kcGF5Lm1vaGF2ZWRlYWxzLmNvbSIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1788162918),
('EnKEcRI9OMeS9c9n9kWS0xM6NK74NntFA3zLTIbu', NULL, '188.166.82.50', 'Mozilla/5.0 (compatible; ForestEngine/1.0; +https://forestengine.net/)', 'eyJfdG9rZW4iOiJDSHlzME5TMFRwRnRTTXB4aHZ6UzVPYnNHenhaWk5TYmQ0SlE4TlEzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9sYW5kcGF5Lm1vaGF2ZWRlYWxzLmNvbSIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1788174253),
('eSCFLOFwDytpeL0fkrpFNbP9fs7n7E78PzGxUVoL', 1, '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', 'eyJfdG9rZW4iOiJNelNqMjdnbGdZY041V2R6Vkk4VFBWaFNyWEw0YVViYkpxMTN6OEd4IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL2xhbmRwYXkubW9oYXZlZGVhbHMuY29tXC9wb3J0YWxcL2ludm9pY2VzXC8xMCJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL2xhbmRwYXkubW9oYXZlZGVhbHMuY29tXC9hZG1pblwvZGFzaGJvYXJkXC9zdGF0dXMiLCJyb3V0ZSI6ImFkbWluLmRhc2hib2FyZC5zdGF0dXMifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=', 1788213501),
('IWb0mAl2XnlDLk3MUXVQS4l3Z9YNMwfOlPBPT21F', NULL, '188.166.36.145', 'Mozilla/5.0 (compatible; ForestEngine/1.0; +https://forestengine.net/)', 'eyJfdG9rZW4iOiJsNnJWUXpNa2Foc25HeG9JQ3dSU0FiTE9FYmxYRDAxaXVReEpmVUp3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC93d3cubGFuZHBheS5tb2hhdmVkZWFscy5jb20iLCJyb3V0ZSI6ImhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==', 1788197596),
('O2VOGNk5lcJRPBSj0h5LRZ5TVVBmNcNFC2AF6E57', NULL, '157.173.126.12', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:8.0) Gecko/20100101 Firefox/8.0', 'eyJfdG9rZW4iOiJNS3hNa1REbkFMcWwyeTZhS2ttbUNaeERQTnJTYkRBb3ZxRzZUeXNMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9sYW5kcGF5Lm1vaGF2ZWRlYWxzLmNvbSIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1788202054),
('SVdmWZ59bClfHqjsjqPMaWvGtqe3mysu9ITZXhAb', NULL, '161.97.67.244', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', 'eyJfdG9rZW4iOiJiMENxYzN3SHlZREhtUTJ1eURleXB6R0ZTeHRqcGtpZHRUQTE5VWlWIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC93d3cubGFuZHBheS5tb2hhdmVkZWFscy5jb20iLCJyb3V0ZSI6ImhvbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==', 1788201709),
('w9aUWhPghUkJynQy4ioxKGZkteHlWvNCxT8xxXTu', 1, '69.24.120.81', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', 'eyJfdG9rZW4iOiJNWTMxanhUQ1dzZkpUTUhjN1d0UVZQV0JDQ09rY2txVVlKajJPR1NlIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9sYW5kcGF5Lm1vaGF2ZWRlYWxzLmNvbVwvcG9ydGFsXC9pbnZvaWNlc1wvMTAiLCJyb3V0ZSI6InBvcnRhbC5pbnZvaWNlcy5zaG93In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fY2xpZW50XzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjF9', 1788204003),
('ZOqKfgTV6W6Uzk9NBo2vAVzueC9ro5McuZu25Ylr', NULL, '188.166.49.36', 'Mozilla/5.0 (compatible; ForestEngine/1.0; +https://forestengine.net/)', 'eyJfdG9rZW4iOiJIMzFNb2RUMFlHdHY1QWV6Q0IxQVAwOXkwbnhOTDVsOUlZV0M3RXQ5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9sYW5kcGF5Lm1vaGF2ZWRlYWxzLmNvbSIsInJvdXRlIjoiaG9tZSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1788163515),
('ZxZmnG9rQXnVlh9xv5AmaOm66QagwnwSMgZARepL', NULL, '168.144.120.239', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJFYUoxR2hFeldoTFl5NHZuQksxQzNEdVRKamJFOTdFQUZyazFITzRPIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xhbmRwYXkubW9oYXZlZGVhbHMuY29tIiwicm91dGUiOiJob21lIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1788162916);

-- --------------------------------------------------------

--
-- Table structure for table `shared_documents`
--

CREATE TABLE `shared_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uploaded_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uploaded_by_client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(30) NOT NULL DEFAULT 'general',
  `disk` varchar(30) NOT NULL DEFAULT 'local',
  `path` varchar(255) NOT NULL,
  `mime` varchar(100) NOT NULL,
  `size` bigint(20) UNSIGNED NOT NULL,
  `visible_to_client` tinyint(1) NOT NULL DEFAULT 1,
  `client_viewed_at` timestamp NULL DEFAULT NULL,
  `client_downloaded_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shared_documents`
--

INSERT INTO `shared_documents` (`id`, `uuid`, `client_id`, `payment_plan_id`, `uploaded_by_user_id`, `uploaded_by_client_id`, `name`, `category`, `disk`, `path`, `mime`, `size`, `visible_to_client`, `client_viewed_at`, `client_downloaded_at`, `archived_at`, `created_at`, `updated_at`) VALUES
(1, 'f80f736b-5198-4f09-9b08-85c32c7aec93', 16, 17, 1, NULL, 'File0361.JPG', 'property_image', 'local', 'shared-documents/246106e5-5b1d-4ffe-8e9a-30be74c008b5.jpg', 'image/jpeg', 4587520, 1, NULL, NULL, NULL, '2026-08-16 01:59:23', '2026-08-16 01:59:23');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_effects`
--

CREATE TABLE `transaction_effects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `financial_transaction_id` bigint(20) UNSIGNED NOT NULL,
  `effect_type` varchar(32) NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount_delta` bigint(20) NOT NULL,
  `component` varchar(40) NOT NULL,
  `invoice_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fee_assessment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaction_effects`
--

INSERT INTO `transaction_effects` (`id`, `financial_transaction_id`, `effect_type`, `invoice_id`, `amount_delta`, `component`, `invoice_item_id`, `fee_assessment_id`, `description`, `created_at`) VALUES
(1, 1, 'purchase_balance', NULL, 230000, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 00:42:02'),
(2, 1, 'purchase_balance', NULL, 24900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 00:42:02'),
(3, 2, 'purchase_balance', NULL, -239900, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in adjustment', '2026-08-10 00:58:38'),
(4, 3, 'invoice_due', 1, 11500, 'scheduled_purchase_payment', 1, NULL, 'Scheduled purchase payment due', '2026-08-10 10:00:02'),
(5, 4, 'invoice_due', 1, 1500, 'monthly_service_fee', 2, NULL, 'Monthly service fee due', '2026-08-10 10:00:02'),
(6, 5, 'invoice_due', 2, 3500, 'scheduled_purchase_payment', 3, NULL, 'Scheduled purchase payment due', '2026-08-10 10:00:02'),
(7, 6, 'invoice_due', 2, 1500, 'monthly_service_fee', 4, NULL, 'Monthly service fee due', '2026-08-10 10:00:02'),
(8, 7, 'invoice_due', 1, -13000, 'other', NULL, NULL, 'Invoice obligation removed', '2026-08-10 11:42:20'),
(9, 8, 'invoice_due', 2, -5000, 'other', NULL, NULL, 'Invoice obligation removed', '2026-08-10 11:42:32'),
(10, 9, 'purchase_balance', NULL, 1650000, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 12:50:07'),
(11, 9, 'purchase_balance', NULL, 24900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 12:50:07'),
(12, 10, 'purchase_balance', NULL, -606300, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 12:50:07'),
(13, 11, 'purchase_balance', NULL, 1090000, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 13:13:08'),
(14, 11, 'purchase_balance', NULL, 24900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 13:13:08'),
(15, 12, 'purchase_balance', NULL, -453500, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 13:13:08'),
(16, 13, 'purchase_balance', NULL, 292100, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 13:17:38'),
(17, 13, 'purchase_balance', NULL, 24900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 13:17:38'),
(18, 14, 'purchase_balance', NULL, -269500, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 13:17:38'),
(19, 15, 'purchase_balance', NULL, 185100, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 13:20:22'),
(20, 15, 'purchase_balance', NULL, 24900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 13:20:22'),
(21, 16, 'purchase_balance', NULL, -95100, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 13:20:22'),
(22, 17, 'purchase_balance', NULL, 305100, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 13:31:46'),
(23, 17, 'purchase_balance', NULL, 19900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 13:31:46'),
(24, 18, 'purchase_balance', NULL, -325000, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 13:31:46'),
(25, 19, 'purchase_balance', NULL, 305100, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 13:33:45'),
(26, 19, 'purchase_balance', NULL, 19900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 13:33:45'),
(27, 20, 'purchase_balance', NULL, -180000, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 13:33:45'),
(28, 21, 'purchase_balance', NULL, 590000, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 13:38:57'),
(29, 21, 'purchase_balance', NULL, 24900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 13:38:57'),
(30, 22, 'purchase_balance', NULL, -541300, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 13:38:57'),
(31, 23, 'purchase_balance', NULL, 299900, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 13:50:57'),
(32, 23, 'purchase_balance', NULL, 24900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 13:50:57'),
(33, 24, 'purchase_balance', NULL, -244800, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 13:50:57'),
(34, 25, 'purchase_balance', NULL, 299900, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 13:58:18'),
(35, 25, 'purchase_balance', NULL, 24900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 13:58:18'),
(36, 26, 'purchase_balance', NULL, -238400, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 13:58:18'),
(37, 27, 'purchase_balance', NULL, 549900, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 13:59:44'),
(38, 27, 'purchase_balance', NULL, 44900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 13:59:44'),
(39, 28, 'purchase_balance', NULL, -261900, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 13:59:44'),
(40, 29, 'purchase_balance', NULL, 599900, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 14:01:02'),
(41, 29, 'purchase_balance', NULL, 44900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 14:01:02'),
(42, 30, 'purchase_balance', NULL, -255500, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 14:01:02'),
(43, 31, 'purchase_balance', NULL, 1200000, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 14:02:45'),
(44, 31, 'purchase_balance', NULL, 44900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 14:02:45'),
(45, 32, 'purchase_balance', NULL, -451400, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 14:02:45'),
(46, 33, 'purchase_balance', NULL, 549500, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 14:04:19'),
(47, 33, 'purchase_balance', NULL, 44900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 14:04:19'),
(48, 34, 'purchase_balance', NULL, -259300, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 14:04:19'),
(49, 35, 'purchase_balance', NULL, 299900, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 14:08:10'),
(50, 35, 'purchase_balance', NULL, 44900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 14:08:10'),
(51, 36, 'purchase_balance', NULL, -251900, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 14:08:10'),
(52, 37, 'purchase_balance', NULL, 299900, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-10 14:10:31'),
(53, 37, 'purchase_balance', NULL, 44900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-10 14:10:31'),
(54, 38, 'purchase_balance', NULL, -150300, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in', '2026-08-10 14:10:31'),
(55, 39, 'invoice_due', 3, 15000, 'scheduled_purchase_payment', 5, NULL, 'Plan payment due', '2026-08-10 23:00:43'),
(56, 40, 'invoice_due', 3, 1500, 'administrative_fee', 6, NULL, 'Service fee due', '2026-08-10 23:00:43'),
(57, 41, 'invoice_due', 4, 11500, 'scheduled_purchase_payment', 7, NULL, 'Scheduled purchase payment due', '2026-08-11 10:00:04'),
(58, 42, 'invoice_due', 4, 1500, 'monthly_service_fee', 8, NULL, 'Monthly service fee due', '2026-08-11 10:00:04'),
(59, 43, 'invoice_due', 5, 3500, 'scheduled_purchase_payment', 9, NULL, 'Scheduled purchase payment due', '2026-08-11 10:00:04'),
(60, 44, 'invoice_due', 5, 1500, 'monthly_service_fee', 10, NULL, 'Monthly service fee due', '2026-08-11 10:00:04'),
(61, 45, 'purchase_balance', NULL, 250000, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-11 13:51:44'),
(62, 45, 'purchase_balance', NULL, 24900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-11 13:51:44'),
(63, 46, 'invoice_due', 5, -5000, 'other', NULL, NULL, 'Invoice obligation removed', '2026-08-11 16:54:09'),
(64, 47, 'invoice_due', 4, -13000, 'other', NULL, NULL, 'Invoice obligation removed', '2026-08-11 16:54:29'),
(65, 48, 'purchase_balance', NULL, 300000, 'purchase_price_principal', NULL, NULL, 'Opening purchase-price principal', '2026-08-11 17:49:55'),
(66, 48, 'purchase_balance', NULL, 24900, 'documentation_fee_principal', NULL, NULL, 'Opening documentation-fee principal', '2026-08-11 17:49:55'),
(67, 49, 'purchase_balance', NULL, -7500, 'purchase_price_principal', NULL, NULL, 'Overpayment applied to principal', '2026-08-11 18:21:46'),
(68, 50, 'purchase_balance', NULL, -13500, 'purchase_price_principal', NULL, NULL, 'Overpayment applied to principal', '2026-08-11 18:31:55'),
(69, 51, 'purchase_balance', NULL, -4500, 'purchase_price_principal', NULL, NULL, 'Overpayment applied to principal', '2026-08-11 19:03:24'),
(70, 52, 'purchase_balance', NULL, -100, 'purchase_price_principal', NULL, NULL, 'Overpayment applied to principal', '2026-08-11 19:10:55'),
(71, 53, 'purchase_balance', NULL, -65, 'purchase_price_principal', NULL, NULL, 'Overpayment applied to principal', '2026-08-11 19:45:30'),
(72, 54, 'invoice_due', 6, 10000, 'scheduled_purchase_payment', 11, NULL, 'Scheduled purchase payment due', '2026-08-12 10:00:03'),
(73, 55, 'invoice_due', 6, 1500, 'monthly_service_fee', 12, NULL, 'Monthly service fee due', '2026-08-12 10:00:03'),
(74, 56, 'invoice_due', 6, -1500, 'monthly_service_fee', 12, NULL, 'Payment applied to Monthly service fee', '2026-08-12 20:54:56'),
(75, 56, 'invoice_due', 6, -10000, 'scheduled_purchase_payment', 11, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-12 20:54:56'),
(76, 56, 'purchase_balance', NULL, -10000, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-12 20:54:56'),
(77, 57, 'invoice_due', 7, 10000, 'scheduled_purchase_payment', 13, NULL, 'Scheduled purchase payment due', '2026-08-13 10:00:03'),
(78, 58, 'invoice_due', 7, 1500, 'monthly_service_fee', 14, NULL, 'Monthly service fee due', '2026-08-13 10:00:03'),
(79, 59, 'invoice_due', 7, -1500, 'monthly_service_fee', 14, NULL, 'Payment applied to Monthly service fee', '2026-08-13 18:35:24'),
(80, 59, 'invoice_due', 7, -10000, 'scheduled_purchase_payment', 13, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-13 18:35:24'),
(81, 59, 'purchase_balance', NULL, -10000, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-13 18:35:24'),
(82, 60, 'invoice_due', 3, 2500, 'late_fee_stage_1', 15, NULL, 'Late Fee added 8/12/26', '2026-08-13 19:14:40'),
(83, 61, 'invoice_due', 8, 10000, 'scheduled_purchase_payment', 16, NULL, 'Plan payment due', '2026-08-13 21:34:19'),
(84, 62, 'invoice_due', 8, 1500, 'administrative_fee', 17, NULL, 'fee due', '2026-08-13 21:34:19'),
(85, 63, 'invoice_due', 8, -11500, 'other', NULL, NULL, 'Invoice obligation removed', '2026-08-13 21:34:53'),
(86, 64, 'invoice_due', 9, 10000, 'scheduled_purchase_payment', 18, NULL, 'Scheduled purchase payment due', '2026-08-14 10:00:03'),
(87, 65, 'invoice_due', 9, 1500, 'monthly_service_fee', 19, NULL, 'Monthly service fee due', '2026-08-14 10:00:03'),
(88, 66, 'invoice_due', 10, 12000, 'scheduled_purchase_payment', 20, NULL, 'Scheduled purchase payment due', '2026-08-14 10:00:11'),
(89, 67, 'invoice_due', 10, 1500, 'monthly_service_fee', 21, NULL, 'Monthly service fee due', '2026-08-14 10:00:11'),
(90, 68, 'invoice_due', 9, -1500, 'monthly_service_fee', 19, NULL, 'Payment applied to Monthly service fee', '2026-08-14 22:55:30'),
(91, 68, 'invoice_due', 9, -10000, 'scheduled_purchase_payment', 18, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-14 22:55:30'),
(92, 68, 'purchase_balance', NULL, -10000, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-14 22:55:30'),
(93, 68, 'purchase_balance', NULL, -3000, 'purchase_price_principal', NULL, NULL, 'Overpayment applied to principal', '2026-08-14 22:55:30'),
(94, 69, 'invoice_due', 11, 10000, 'scheduled_purchase_payment', 22, NULL, 'Scheduled purchase payment due', '2026-08-15 10:00:03'),
(95, 70, 'invoice_due', 11, 1500, 'monthly_service_fee', 23, NULL, 'Monthly service fee due', '2026-08-15 10:00:03'),
(96, 71, 'invoice_due', 11, -1500, 'monthly_service_fee', 23, NULL, 'Payment applied to Monthly service fee', '2026-08-15 21:05:23'),
(97, 71, 'invoice_due', 11, -10000, 'scheduled_purchase_payment', 22, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-15 21:05:23'),
(98, 71, 'purchase_balance', NULL, -10000, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-15 21:05:23'),
(99, 71, 'client_credit', NULL, 11500, 'unapplied_credit', NULL, NULL, 'Credit for a future invoice or refund', '2026-08-15 21:05:23'),
(100, 72, 'invoice_due', 12, 10000, 'scheduled_purchase_payment', 24, NULL, 'Scheduled purchase payment due', '2026-08-16 10:00:02'),
(101, 73, 'invoice_due', 12, 1500, 'monthly_service_fee', 25, NULL, 'Monthly service fee due', '2026-08-16 10:00:02'),
(102, 74, 'client_credit', NULL, -11500, 'unapplied_credit', NULL, NULL, 'Account credit applied to invoice INV-17-20260816', '2026-08-16 10:00:02'),
(103, 74, 'invoice_due', 12, -1500, 'monthly_service_fee', 25, NULL, 'Account credit applied', '2026-08-16 10:00:02'),
(104, 74, 'invoice_due', 12, -10000, 'scheduled_purchase_payment', 24, NULL, 'Account credit applied', '2026-08-16 10:00:02'),
(105, 74, 'purchase_balance', NULL, -10000, 'purchase_price_principal', NULL, NULL, 'Account credit applied to principal', '2026-08-16 10:00:02'),
(106, 75, 'invoice_due', 10, -1500, 'monthly_service_fee', 21, NULL, 'Payment applied to Monthly service fee', '2026-08-16 19:32:05'),
(107, 75, 'invoice_due', 10, -12000, 'scheduled_purchase_payment', 20, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-16 19:32:05'),
(108, 75, 'purchase_balance', NULL, -12000, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-16 19:32:05'),
(109, 75, 'client_credit', NULL, 13500, 'unapplied_credit', NULL, NULL, 'Credit for a future invoice or refund', '2026-08-16 19:32:05'),
(110, 76, 'invoice_due', 13, 10000, 'scheduled_purchase_payment', 26, NULL, 'Scheduled purchase payment due', '2026-08-17 10:00:03'),
(111, 77, 'invoice_due', 13, 1500, 'monthly_service_fee', 27, NULL, 'Monthly service fee due', '2026-08-17 10:00:03'),
(112, 78, 'invoice_due', 13, -1500, 'monthly_service_fee', 27, NULL, 'Payment applied to Monthly service fee', '2026-08-17 18:02:48'),
(113, 78, 'invoice_due', 13, -10000, 'scheduled_purchase_payment', 26, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-17 18:02:48'),
(114, 78, 'purchase_balance', NULL, -10000, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-17 18:02:48'),
(115, 78, 'client_credit', NULL, 1000, 'unapplied_credit', NULL, NULL, 'Credit for a future invoice or refund', '2026-08-17 18:02:48'),
(116, 79, 'invoice_due', 14, 10000, 'scheduled_purchase_payment', 28, NULL, 'Scheduled purchase payment due', '2026-08-18 10:00:03'),
(117, 80, 'invoice_due', 14, 1500, 'monthly_service_fee', 29, NULL, 'Monthly service fee due', '2026-08-18 10:00:03'),
(118, 81, 'client_credit', NULL, -1000, 'unapplied_credit', NULL, NULL, 'Account credit applied to invoice INV-17-20260818', '2026-08-18 10:00:03'),
(119, 81, 'invoice_due', 14, -1000, 'monthly_service_fee', 29, NULL, 'Account credit applied', '2026-08-18 10:00:03'),
(120, 82, 'invoice_due', 14, -1500, 'monthly_service_fee', 29, NULL, 'Payment applied to Monthly service fee', '2026-08-18 16:40:13'),
(121, 82, 'invoice_due', 14, -9000, 'scheduled_purchase_payment', 28, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-18 16:40:13'),
(122, 82, 'purchase_balance', NULL, -9000, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-18 16:40:13'),
(123, 83, 'invoice_due', 15, 10000, 'scheduled_purchase_payment', 30, NULL, 'Scheduled purchase payment due', '2026-08-19 10:00:02'),
(124, 84, 'invoice_due', 15, 1500, 'monthly_service_fee', 31, NULL, 'Monthly service fee due', '2026-08-19 10:00:02'),
(125, 85, 'invoice_due', 16, 10000, 'scheduled_purchase_payment', 32, NULL, 'Scheduled purchase payment due', '2026-08-20 10:00:03'),
(126, 86, 'invoice_due', 16, 1500, 'monthly_service_fee', 33, NULL, 'Monthly service fee due', '2026-08-20 10:00:03'),
(127, 87, 'invoice_due', 17, 10000, 'scheduled_purchase_payment', 34, NULL, 'Scheduled purchase payment due', '2026-08-21 10:00:03'),
(128, 88, 'invoice_due', 17, 1500, 'monthly_service_fee', 35, NULL, 'Monthly service fee due', '2026-08-21 10:00:03'),
(129, 89, 'invoice_due', 15, -1500, 'monthly_service_fee', 31, NULL, 'Payment applied to Monthly service fee', '2026-08-21 12:33:59'),
(130, 89, 'invoice_due', 15, -3500, 'scheduled_purchase_payment', 30, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-21 12:33:59'),
(131, 89, 'purchase_balance', NULL, -3500, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-21 12:33:59'),
(132, 90, 'invoice_due', 18, 10000, 'scheduled_purchase_payment', 36, NULL, 'Scheduled purchase payment due', '2026-08-22 10:00:03'),
(133, 91, 'invoice_due', 18, 1500, 'monthly_service_fee', 37, NULL, 'Monthly service fee due', '2026-08-22 10:00:03'),
(134, 92, 'purchase_balance', NULL, -13500, 'purchase_price_principal', NULL, NULL, 'Overpayment applied to principal', '2026-08-22 14:55:33'),
(135, 93, 'purchase_balance', NULL, -10000, 'purchase_price_principal', NULL, NULL, 'Overpayment applied to principal', '2026-08-22 14:58:02'),
(136, 94, 'purchase_balance', NULL, 24900, 'purchase_price_principal', NULL, NULL, 'Purchase-price amendment', '2026-08-22 15:06:42'),
(137, 95, 'purchase_balance', NULL, -24900, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in adjustment', '2026-08-22 15:06:42'),
(138, 96, 'purchase_balance', NULL, 24900, 'purchase_price_principal', NULL, NULL, 'Purchase-price amendment', '2026-08-22 17:01:47'),
(139, 97, 'purchase_balance', NULL, -24900, 'opening_principal_credit', NULL, NULL, 'Amount previously paid in adjustment', '2026-08-22 17:01:47'),
(140, 98, 'invoice_due', 19, 10000, 'scheduled_purchase_payment', 38, NULL, 'Scheduled purchase payment due', '2026-08-23 10:00:03'),
(141, 99, 'invoice_due', 19, 1500, 'monthly_service_fee', 39, NULL, 'Monthly service fee due', '2026-08-23 10:00:03'),
(142, 100, 'invoice_due', 15, -6500, 'scheduled_purchase_payment', 30, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-23 17:16:41'),
(143, 100, 'purchase_balance', NULL, -6500, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-23 17:16:41'),
(144, 100, 'invoice_due', 16, -1500, 'monthly_service_fee', 33, NULL, 'Payment applied to Monthly service fee', '2026-08-23 17:16:41'),
(145, 100, 'invoice_due', 16, -3500, 'scheduled_purchase_payment', 32, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-23 17:16:41'),
(146, 100, 'purchase_balance', NULL, -3500, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-23 17:16:41'),
(147, 101, 'invoice_due', 16, 2500, 'administrative_fee', 40, NULL, 'Late fee due', '2026-08-23 17:19:09'),
(148, 102, 'invoice_due', 16, -2500, 'administrative_fee', 40, NULL, 'Payment applied to Late fee', '2026-08-24 00:24:36'),
(149, 102, 'invoice_due', 16, -6500, 'scheduled_purchase_payment', 32, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-24 00:24:36'),
(150, 102, 'purchase_balance', NULL, -6500, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-24 00:24:36'),
(151, 102, 'invoice_due', 17, -1500, 'monthly_service_fee', 35, NULL, 'Payment applied to Monthly service fee', '2026-08-24 00:24:36'),
(152, 102, 'invoice_due', 17, -2200, 'scheduled_purchase_payment', 34, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-24 00:24:36'),
(153, 102, 'purchase_balance', NULL, -2200, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-24 00:24:36'),
(154, 103, 'invoice_due', 20, 10000, 'scheduled_purchase_payment', 41, NULL, 'Scheduled purchase payment due', '2026-08-24 10:00:03'),
(155, 104, 'invoice_due', 20, 1500, 'monthly_service_fee', 42, NULL, 'Monthly service fee due', '2026-08-24 10:00:03'),
(156, 105, 'invoice_due', 21, 10000, 'scheduled_purchase_payment', 43, NULL, 'Scheduled purchase payment due', '2026-08-25 10:00:03'),
(157, 106, 'invoice_due', 21, 1500, 'monthly_service_fee', 44, NULL, 'Monthly service fee due', '2026-08-25 10:00:03'),
(158, 107, 'invoice_due', 22, 10000, 'scheduled_purchase_payment', 45, NULL, 'Scheduled purchase payment due', '2026-08-26 10:00:03'),
(159, 108, 'invoice_due', 22, 1500, 'monthly_service_fee', 46, NULL, 'Monthly service fee due', '2026-08-26 10:00:03'),
(160, 109, 'invoice_due', 23, 10000, 'scheduled_purchase_payment', 47, NULL, 'Scheduled purchase payment due', '2026-08-27 10:00:03'),
(161, 110, 'invoice_due', 23, 1500, 'monthly_service_fee', 48, NULL, 'Monthly service fee due', '2026-08-27 10:00:03'),
(162, 111, 'invoice_due', 17, -7800, 'scheduled_purchase_payment', 34, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-27 13:24:38'),
(163, 111, 'purchase_balance', NULL, -7800, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-27 13:24:38'),
(164, 111, 'invoice_due', 18, -1500, 'monthly_service_fee', 37, NULL, 'Payment applied to Monthly service fee', '2026-08-27 13:24:38'),
(165, 111, 'invoice_due', 18, -2200, 'scheduled_purchase_payment', 36, NULL, 'Payment applied to Scheduled purchase payment', '2026-08-27 13:24:38'),
(166, 111, 'purchase_balance', NULL, -2200, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-27 13:24:38'),
(167, 112, 'invoice_due', 24, 10000, 'scheduled_purchase_payment', 49, NULL, 'Scheduled purchase payment due', '2026-08-28 10:00:03'),
(168, 113, 'invoice_due', 24, 1500, 'monthly_service_fee', 50, NULL, 'Monthly service fee due', '2026-08-28 10:00:03'),
(169, 114, 'invoice_due', 25, 10000, 'scheduled_purchase_payment', 51, NULL, 'Scheduled purchase payment due', '2026-08-29 10:00:02'),
(170, 115, 'invoice_due', 25, 1500, 'monthly_service_fee', 52, NULL, 'Monthly service fee due', '2026-08-29 10:00:02'),
(171, 116, 'invoice_due', 26, 10000, 'scheduled_purchase_payment', 53, NULL, 'Scheduled purchase payment due', '2026-08-30 10:00:03'),
(172, 117, 'invoice_due', 26, 1500, 'monthly_service_fee', 54, NULL, 'Monthly service fee due', '2026-08-30 10:00:03'),
(173, 118, 'invoice_due', 3, 581, 'administrative_fee', 55, NULL, 'Fee due', '2026-08-30 13:25:25'),
(174, 119, 'invoice_due', 3, -581, 'administrative_fee', 55, NULL, 'Payment applied to Fee', '2026-08-30 13:27:37'),
(175, 119, 'invoice_due', 3, -1500, 'administrative_fee', 6, NULL, 'Payment applied to Service fee', '2026-08-30 13:27:37'),
(176, 119, 'invoice_due', 3, -2500, 'late_fee_stage_1', 15, NULL, 'Payment applied to Late Fee added 8/12/26', '2026-08-30 13:27:37'),
(177, 119, 'invoice_due', 3, -13500, 'scheduled_purchase_payment', 5, NULL, 'Payment applied to Plan payment', '2026-08-30 13:27:37'),
(178, 119, 'purchase_balance', NULL, -13500, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-30 13:27:37'),
(179, 120, 'invoice_due', 3, 581, 'administrative_fee', 55, NULL, 'Reversal: Payment applied to Fee', '2026-08-30 13:28:33'),
(180, 120, 'invoice_due', 3, 1500, 'administrative_fee', 6, NULL, 'Reversal: Payment applied to Service fee', '2026-08-30 13:28:33'),
(181, 120, 'invoice_due', 3, 2500, 'late_fee_stage_1', 15, NULL, 'Reversal: Payment applied to Late Fee added 8/12/26', '2026-08-30 13:28:33'),
(182, 120, 'invoice_due', 3, 13500, 'scheduled_purchase_payment', 5, NULL, 'Reversal: Payment applied to Plan payment', '2026-08-30 13:28:33'),
(183, 120, 'purchase_balance', NULL, 13500, 'purchase_price_principal', NULL, NULL, 'Reversal: Scheduled payment applied to principal', '2026-08-30 13:28:33'),
(184, 121, 'invoice_due', 3, -581, 'administrative_fee', 55, NULL, 'Payment applied to Fee', '2026-08-30 13:40:54'),
(185, 121, 'invoice_due', 3, -1500, 'administrative_fee', 6, NULL, 'Payment applied to Service fee', '2026-08-30 13:40:54'),
(186, 121, 'invoice_due', 3, -2500, 'late_fee_stage_1', 15, NULL, 'Payment applied to Late Fee added 8/12/26', '2026-08-30 13:40:54'),
(187, 121, 'invoice_due', 3, -15000, 'scheduled_purchase_payment', 5, NULL, 'Payment applied to Plan payment', '2026-08-30 13:40:54'),
(188, 121, 'purchase_balance', NULL, -15000, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-30 13:40:54'),
(189, 122, 'invoice_due', 3, 581, 'administrative_fee', 55, NULL, 'Reversal: Payment applied to Fee', '2026-08-30 13:57:00'),
(190, 122, 'invoice_due', 3, 1500, 'administrative_fee', 6, NULL, 'Reversal: Payment applied to Service fee', '2026-08-30 13:57:00'),
(191, 122, 'invoice_due', 3, 2500, 'late_fee_stage_1', 15, NULL, 'Reversal: Payment applied to Late Fee added 8/12/26', '2026-08-30 13:57:00'),
(192, 122, 'invoice_due', 3, 15000, 'scheduled_purchase_payment', 5, NULL, 'Reversal: Payment applied to Plan payment', '2026-08-30 13:57:00'),
(193, 122, 'purchase_balance', NULL, 15000, 'purchase_price_principal', NULL, NULL, 'Reversal: Scheduled payment applied to principal', '2026-08-30 13:57:00'),
(194, 123, 'invoice_due', 3, -581, 'administrative_fee', 55, NULL, 'Payment applied to Fee', '2026-08-30 15:13:50'),
(195, 123, 'invoice_due', 3, -1500, 'administrative_fee', 6, NULL, 'Payment applied to Service fee', '2026-08-30 15:13:50'),
(196, 123, 'invoice_due', 3, -2500, 'late_fee_stage_1', 15, NULL, 'Payment applied to Late Fee added 8/12/26', '2026-08-30 15:13:50'),
(197, 123, 'invoice_due', 3, -15000, 'scheduled_purchase_payment', 5, NULL, 'Payment applied to Plan Payment', '2026-08-30 15:13:50'),
(198, 123, 'purchase_balance', NULL, -15000, 'purchase_price_principal', NULL, NULL, 'Scheduled payment applied to principal', '2026-08-30 15:13:50'),
(199, 124, 'purchase_balance', NULL, -100, 'purchase_price_principal', NULL, NULL, 'Overpayment applied to principal', '2026-08-30 22:23:13'),
(200, 125, 'purchase_balance', NULL, -100, 'purchase_price_principal', NULL, NULL, 'Overpayment applied to principal', '2026-08-30 22:27:48'),
(201, 126, 'invoice_due', 27, 3000, 'scheduled_purchase_payment', 56, NULL, 'Plan payment due', '2026-08-30 23:24:00'),
(202, 127, 'client_credit', NULL, -3000, 'unapplied_credit', NULL, NULL, 'Account credit applied to invoice M18-260830-MX', '2026-08-30 23:24:00'),
(203, 127, 'invoice_due', 27, -3000, 'scheduled_purchase_payment', 56, NULL, 'Account credit applied', '2026-08-30 23:24:00'),
(204, 127, 'purchase_balance', NULL, -3000, 'purchase_price_principal', NULL, NULL, 'Account credit applied to principal', '2026-08-30 23:24:00'),
(205, 128, 'client_credit', NULL, 3000, 'unapplied_credit', NULL, NULL, 'Reversal: Account credit applied to invoice M18-260830-MX', '2026-08-31 00:28:43'),
(206, 128, 'invoice_due', 27, 3000, 'scheduled_purchase_payment', 56, NULL, 'Reversal: Account credit applied', '2026-08-31 00:28:43'),
(207, 128, 'purchase_balance', NULL, 3000, 'purchase_price_principal', NULL, NULL, 'Reversal: Account credit applied to principal', '2026-08-31 00:28:43'),
(208, 129, 'invoice_due', 27, -3000, 'other', NULL, NULL, 'Invoice obligation removed', '2026-08-31 00:28:43'),
(209, 130, 'invoice_due', 28, 10000, 'scheduled_purchase_payment', 57, NULL, 'Scheduled purchase payment due', '2026-08-31 10:00:02'),
(210, 131, 'invoice_due', 28, 1500, 'monthly_service_fee', 58, NULL, 'Monthly service fee due', '2026-08-31 10:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(254) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(32) NOT NULL DEFAULT 'administrator',
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `email_verified_at`, `password`, `role`, `status`, `last_login_at`, `last_login_ip`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, '3b89c3b1-2fe3-48f4-a05c-6a38ce598ace', 'admin', 'chris@mohavedeals.com', '2026-07-27 06:21:33', '$2y$12$bStcon2AJ6VfgKQ/TcErJOJ/urK7z4T3v9HSh0anziU1hxrcH6R0a', 'administrator', 'active', NULL, NULL, 'VuiNaltiXSmbZGIwmGi1s57WXwOhD1ykxgD6QVDOw62LEYL5ZpnFDxI4q3MY', '2026-07-27 06:21:33', '2026-07-27 06:21:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notices`
--
ALTER TABLE `admin_notices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_notices_provider_event_id_unique` (`provider_event_id`),
  ADD KEY `admin_notices_client_id_foreign` (`client_id`),
  ADD KEY `admin_notices_client_change_request_id_foreign` (`client_change_request_id`),
  ADD KEY `admin_notices_dismissed_by_user_id_foreign` (`dismissed_by_user_id`),
  ADD KEY `admin_notices_type_index` (`type`),
  ADD KEY `admin_notices_dismissed_at_index` (`dismissed_at`),
  ADD KEY `admin_notices_client_payment_intent_id_foreign` (`client_payment_intent_id`),
  ADD KEY `admin_notices_secure_message_thread_id_foreign` (`secure_message_thread_id`),
  ADD KEY `admin_notices_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `app_settings_key_unique` (`key`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `audit_logs_uuid_unique` (`uuid`),
  ADD KEY `audit_logs_auditable_type_auditable_id_created_at_index` (`auditable_type`,`auditable_id`,`created_at`),
  ADD KEY `audit_logs_actor_user_id_created_at_index` (`actor_user_id`,`created_at`),
  ADD KEY `audit_logs_actor_client_id_created_at_index` (`actor_client_id`,`created_at`),
  ADD KEY `audit_logs_event_created_at_index` (`event`,`created_at`);

--
-- Indexes for table `billing_defaults`
--
ALTER TABLE `billing_defaults`
  ADD PRIMARY KEY (`id`),
  ADD KEY `billing_defaults_updated_by_user_id_foreign` (`updated_by_user_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clients_uuid_unique` (`uuid`),
  ADD KEY `clients_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `clients_updated_by_user_id_foreign` (`updated_by_user_id`),
  ADD KEY `clients_last_name_first_name_index` (`last_name`,`first_name`),
  ADD KEY `clients_email_index` (`email`),
  ADD KEY `clients_primary_phone_index` (`primary_phone`),
  ADD KEY `clients_status_index` (`status`);

--
-- Indexes for table `client_change_requests`
--
ALTER TABLE `client_change_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_change_requests_client_id_foreign` (`client_id`),
  ADD KEY `client_change_requests_portal_account_id_foreign` (`portal_account_id`),
  ADD KEY `client_change_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  ADD KEY `client_change_requests_status_index` (`status`);

--
-- Indexes for table `client_contacts`
--
ALTER TABLE `client_contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_contacts_uuid_unique` (`uuid`),
  ADD KEY `client_contacts_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `client_contacts_updated_by_user_id_foreign` (`updated_by_user_id`),
  ADD KEY `client_contacts_replaced_by_contact_id_foreign` (`replaced_by_contact_id`),
  ADD KEY `client_contacts_client_id_status_priority_index` (`client_id`,`status`,`priority`),
  ADD KEY `client_contacts_payment_plan_id_status_priority_index` (`payment_plan_id`,`status`,`priority`);

--
-- Indexes for table `client_payment_intents`
--
ALTER TABLE `client_payment_intents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_payment_intents_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `client_payment_intents_provider_checkout_id_unique` (`provider_checkout_id`),
  ADD UNIQUE KEY `client_payment_intents_provider_payment_id_unique` (`provider_payment_id`),
  ADD KEY `client_payment_intents_portal_account_id_foreign` (`portal_account_id`),
  ADD KEY `client_payment_intents_payment_id_foreign` (`payment_id`),
  ADD KEY `client_payment_intents_payment_plan_id_status_index` (`payment_plan_id`,`status`),
  ADD KEY `client_payment_intents_client_id_status_index` (`client_id`,`status`),
  ADD KEY `client_payment_intents_status_index` (`status`);

--
-- Indexes for table `contract_status_events`
--
ALTER TABLE `contract_status_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contract_status_events_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `contract_status_events_idempotency_key_unique` (`idempotency_key`),
  ADD KEY `contract_status_events_administrator_user_id_foreign` (`administrator_user_id`),
  ADD KEY `contract_status_events_related_prior_event_id_foreign` (`related_prior_event_id`),
  ADD KEY `contract_status_events_payment_plan_id_effective_at_id_index` (`payment_plan_id`,`effective_at`,`id`),
  ADD KEY `contract_status_events_event_type_effective_at_index` (`event_type`,`effective_at`);

--
-- Indexes for table `email_deliveries`
--
ALTER TABLE `email_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_deliveries_payment_plan_id_foreign` (`payment_plan_id`),
  ADD KEY `email_deliveries_recipient_client_id_foreign` (`recipient_client_id`),
  ADD KEY `email_deliveries_sent_by_user_id_foreign` (`sent_by_user_id`),
  ADD KEY `email_deliveries_invoice_id_created_at_index` (`invoice_id`,`created_at`),
  ADD KEY `email_deliveries_template_slug_sent_at_index` (`template_slug`,`sent_at`),
  ADD KEY `email_deliveries_payment_id_template_slug_sent_at_index` (`payment_id`,`template_slug`,`sent_at`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_templates_slug_unique` (`slug`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `fee_assessments`
--
ALTER TABLE `fee_assessments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fee_assessments_plan_rule_period_unique` (`payment_plan_id`,`recurring_fee_rule_id`,`period_key`),
  ADD UNIQUE KEY `fee_assessments_financial_transaction_id_unique` (`financial_transaction_id`),
  ADD KEY `fee_assessments_recurring_fee_rule_id_foreign` (`recurring_fee_rule_id`),
  ADD KEY `fee_assessments_invoice_item_id_foreign` (`invoice_item_id`),
  ADD KEY `fee_assessments_invoice_id_effective_date_index` (`invoice_id`,`effective_date`);

--
-- Indexes for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `financial_transactions_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `financial_transactions_reversal_of_transaction_id_unique` (`reversal_of_transaction_id`),
  ADD UNIQUE KEY `financial_transactions_idempotency_key_unique` (`idempotency_key`),
  ADD KEY `financial_transactions_posted_by_user_id_foreign` (`posted_by_user_id`),
  ADD KEY `financial_transactions_posted_by_client_id_foreign` (`posted_by_client_id`),
  ADD KEY `financial_transactions_authorized_by_user_id_foreign` (`authorized_by_user_id`),
  ADD KEY `financial_transactions_payment_plan_id_effective_date_id_index` (`payment_plan_id`,`effective_date`,`id`),
  ADD KEY `financial_transactions_invoice_id_effective_date_id_index` (`invoice_id`,`effective_date`,`id`),
  ADD KEY `financial_transactions_type_posted_at_index` (`type`,`posted_at`),
  ADD KEY `financial_transactions_source_reference_index` (`source_reference`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  ADD KEY `invoices_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `invoices_payment_plan_id_due_date_index` (`payment_plan_id`,`due_date`),
  ADD KEY `invoices_payment_plan_id_status_due_date_index` (`payment_plan_id`,`status`,`due_date`),
  ADD KEY `invoices_generation_source_index` (`generation_source`),
  ADD KEY `invoices_payment_plan_billing_term_id_foreign` (`payment_plan_billing_term_id`),
  ADD KEY `invoices_first_viewed_at_index` (`first_viewed_at`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_items_invoice_id_late_fee_stage_unique` (`invoice_id`,`late_fee_stage`),
  ADD KEY `invoice_items_waived_by_user_id_foreign` (`waived_by_user_id`),
  ADD KEY `invoice_items_invoice_id_display_order_index` (`invoice_id`,`display_order`),
  ADD KEY `invoice_items_invoice_id_item_type_index` (`invoice_id`,`item_type`),
  ADD KEY `invoice_items_source_transaction_id_index` (`source_transaction_id`),
  ADD KEY `invoice_items_retired_at_index` (`retired_at`);

--
-- Indexes for table `invoice_reminders`
--
ALTER TABLE `invoice_reminders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_reminder_trigger_unique` (`invoice_id`,`trigger_date`,`trigger_type`),
  ADD KEY `invoice_reminders_recipient_client_id_foreign` (`recipient_client_id`),
  ADD KEY `invoice_reminders_sent_by_user_id_foreign` (`sent_by_user_id`),
  ADD KEY `invoice_reminders_invoice_id_created_at_index` (`invoice_id`,`created_at`),
  ADD KEY `invoice_reminders_payment_plan_id_sent_at_index` (`payment_plan_id`,`sent_at`),
  ADD KEY `invoice_reminders_recipient_email_sent_at_index` (`recipient_email`,`sent_at`),
  ADD KEY `invoice_reminders_automated_trigger_date_status_index` (`automated`,`trigger_date`,`status`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monthly_service_fee_satisfactions`
--
ALTER TABLE `monthly_service_fee_satisfactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_fee_satisfaction_plan_month_uq` (`payment_plan_id`,`billing_month`),
  ADD KEY `monthly_service_fee_satisfactions_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `monthly_service_fee_satisfactions_revoked_by_user_id_foreign` (`revoked_by_user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_financial_transaction_id_unique` (`financial_transaction_id`),
  ADD KEY `payments_instruction_recorded_by_user_id_foreign` (`instruction_recorded_by_user_id`),
  ADD KEY `payments_payer_client_id_received_date_index` (`payer_client_id`,`received_date`),
  ADD KEY `payments_payment_method_external_reference_index` (`payment_method`,`external_reference`);

--
-- Indexes for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_allocations_payment_id_display_order_index` (`payment_id`,`display_order`),
  ADD KEY `payment_allocations_invoice_id_index` (`invoice_id`),
  ADD KEY `payment_allocations_invoice_item_id_index` (`invoice_item_id`),
  ADD KEY `payment_allocations_fee_assessment_id_index` (`fee_assessment_id`),
  ADD KEY `payment_allocations_billing_month_index` (`billing_month`);

--
-- Indexes for table `payment_plans`
--
ALTER TABLE `payment_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_plans_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `payment_plans_plan_number_unique` (`plan_number`),
  ADD KEY `payment_plans_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `payment_plans_updated_by_user_id_foreign` (`updated_by_user_id`),
  ADD KEY `payment_plans_monthly_due_day_index` (`monthly_due_day`),
  ADD KEY `payment_plans_first_due_date_index` (`first_due_date`),
  ADD KEY `payment_plans_status_index` (`status`),
  ADD KEY `payment_plans_apn_index` (`apn`);

--
-- Indexes for table `payment_plan_billing_terms`
--
ALTER TABLE `payment_plan_billing_terms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_plan_billing_terms_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `payment_plan_billing_terms_payment_plan_id_effective_to_index` (`payment_plan_id`,`effective_to`),
  ADD KEY `payment_plan_billing_terms_payment_plan_id_effective_from_index` (`payment_plan_id`,`effective_from`);

--
-- Indexes for table `payment_plan_clients`
--
ALTER TABLE `payment_plan_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_plan_clients_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `payment_plan_clients_ended_by_user_id_foreign` (`ended_by_user_id`),
  ADD KEY `payment_plan_clients_payment_plan_id_effective_to_index` (`payment_plan_id`,`effective_to`),
  ADD KEY `payment_plan_clients_client_id_effective_to_index` (`client_id`,`effective_to`),
  ADD KEY `payment_plan_clients_payment_plan_id_role_effective_to_index` (`payment_plan_id`,`role`,`effective_to`);

--
-- Indexes for table `payment_plan_pauses`
--
ALTER TABLE `payment_plan_pauses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_plan_pauses_paused_by_user_id_foreign` (`paused_by_user_id`),
  ADD KEY `payment_plan_pauses_resumed_by_user_id_foreign` (`resumed_by_user_id`),
  ADD KEY `payment_plan_pauses_payment_plan_id_pause_date_resume_date_index` (`payment_plan_id`,`pause_date`,`resume_date`);

--
-- Indexes for table `portal_accounts`
--
ALTER TABLE `portal_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `portal_accounts_client_id_unique` (`client_id`),
  ADD UNIQUE KEY `portal_accounts_email_unique` (`email`),
  ADD KEY `portal_accounts_enabled_index` (`enabled`);

--
-- Indexes for table `portal_invitations`
--
ALTER TABLE `portal_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `portal_invitations_token_hash_unique` (`token_hash`),
  ADD KEY `portal_invitations_invited_by_user_id_foreign` (`invited_by_user_id`),
  ADD KEY `portal_invitations_client_id_created_at_index` (`client_id`,`created_at`),
  ADD KEY `portal_invitations_expires_at_index` (`expires_at`);

--
-- Indexes for table `recurring_fee_rules`
--
ALTER TABLE `recurring_fee_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `recurring_fee_rules_uuid_unique` (`uuid`),
  ADD KEY `recurring_fee_rules_created_by_user_id_foreign` (`created_by_user_id`),
  ADD KEY `recurring_fee_rules_updated_by_user_id_foreign` (`updated_by_user_id`),
  ADD KEY `recurring_fee_rules_payment_plan_id_status_effective_from_index` (`payment_plan_id`,`status`,`effective_from`);

--
-- Indexes for table `secure_messages`
--
ALTER TABLE `secure_messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `secure_messages_uuid_unique` (`uuid`),
  ADD KEY `secure_messages_sender_user_id_foreign` (`sender_user_id`),
  ADD KEY `secure_messages_sender_client_id_foreign` (`sender_client_id`),
  ADD KEY `secure_messages_secure_message_thread_id_sender_type_index` (`secure_message_thread_id`,`sender_type`);

--
-- Indexes for table `secure_message_attachments`
--
ALTER TABLE `secure_message_attachments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `secure_message_attachments_uuid_unique` (`uuid`),
  ADD KEY `secure_message_attachments_secure_message_id_foreign` (`secure_message_id`);

--
-- Indexes for table `secure_message_documents`
--
ALTER TABLE `secure_message_documents`
  ADD PRIMARY KEY (`secure_message_id`,`shared_document_id`),
  ADD KEY `secure_message_documents_shared_document_id_foreign` (`shared_document_id`);

--
-- Indexes for table `secure_message_revisions`
--
ALTER TABLE `secure_message_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `secure_message_revisions_secure_message_id_foreign` (`secure_message_id`),
  ADD KEY `secure_message_revisions_edited_by_user_id_foreign` (`edited_by_user_id`);

--
-- Indexes for table `secure_message_threads`
--
ALTER TABLE `secure_message_threads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `secure_message_threads_uuid_unique` (`uuid`),
  ADD KEY `secure_message_threads_client_id_foreign` (`client_id`),
  ADD KEY `secure_message_threads_payment_plan_id_foreign` (`payment_plan_id`),
  ADD KEY `secure_message_threads_starred_at_index` (`starred_at`),
  ADD KEY `secure_message_threads_latest_message_at_index` (`latest_message_at`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `shared_documents`
--
ALTER TABLE `shared_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shared_documents_uuid_unique` (`uuid`),
  ADD KEY `shared_documents_payment_plan_id_foreign` (`payment_plan_id`),
  ADD KEY `shared_documents_uploaded_by_user_id_foreign` (`uploaded_by_user_id`),
  ADD KEY `shared_documents_uploaded_by_client_id_foreign` (`uploaded_by_client_id`),
  ADD KEY `shared_documents_client_id_created_at_index` (`client_id`,`created_at`),
  ADD KEY `shared_documents_visible_to_client_index` (`visible_to_client`),
  ADD KEY `shared_documents_archived_at_index` (`archived_at`);

--
-- Indexes for table `transaction_effects`
--
ALTER TABLE `transaction_effects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_effects_financial_transaction_id_id_index` (`financial_transaction_id`,`id`),
  ADD KEY `transaction_effects_invoice_id_effect_type_id_index` (`invoice_id`,`effect_type`,`id`),
  ADD KEY `transaction_effects_effect_type_financial_transaction_id_index` (`effect_type`,`financial_transaction_id`),
  ADD KEY `transaction_effects_invoice_item_id_index` (`invoice_item_id`),
  ADD KEY `transaction_effects_fee_assessment_id_index` (`fee_assessment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notices`
--
ALTER TABLE `admin_notices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `app_settings`
--
ALTER TABLE `app_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `billing_defaults`
--
ALTER TABLE `billing_defaults`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `client_change_requests`
--
ALTER TABLE `client_change_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `client_contacts`
--
ALTER TABLE `client_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_payment_intents`
--
ALTER TABLE `client_payment_intents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `contract_status_events`
--
ALTER TABLE `contract_status_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_deliveries`
--
ALTER TABLE `email_deliveries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_assessments`
--
ALTER TABLE `fee_assessments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `invoice_reminders`
--
ALTER TABLE `invoice_reminders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `monthly_service_fee_satisfactions`
--
ALTER TABLE `monthly_service_fee_satisfactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `payment_plans`
--
ALTER TABLE `payment_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `payment_plan_billing_terms`
--
ALTER TABLE `payment_plan_billing_terms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `payment_plan_clients`
--
ALTER TABLE `payment_plan_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `payment_plan_pauses`
--
ALTER TABLE `payment_plan_pauses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `portal_accounts`
--
ALTER TABLE `portal_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `portal_invitations`
--
ALTER TABLE `portal_invitations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `recurring_fee_rules`
--
ALTER TABLE `recurring_fee_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `secure_messages`
--
ALTER TABLE `secure_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `secure_message_attachments`
--
ALTER TABLE `secure_message_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `secure_message_revisions`
--
ALTER TABLE `secure_message_revisions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `secure_message_threads`
--
ALTER TABLE `secure_message_threads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `shared_documents`
--
ALTER TABLE `shared_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transaction_effects`
--
ALTER TABLE `transaction_effects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=211;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_notices`
--
ALTER TABLE `admin_notices`
  ADD CONSTRAINT `admin_notices_client_change_request_id_foreign` FOREIGN KEY (`client_change_request_id`) REFERENCES `client_change_requests` (`id`),
  ADD CONSTRAINT `admin_notices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `admin_notices_client_payment_intent_id_foreign` FOREIGN KEY (`client_payment_intent_id`) REFERENCES `client_payment_intents` (`id`),
  ADD CONSTRAINT `admin_notices_dismissed_by_user_id_foreign` FOREIGN KEY (`dismissed_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `admin_notices_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `admin_notices_secure_message_thread_id_foreign` FOREIGN KEY (`secure_message_thread_id`) REFERENCES `secure_message_threads` (`id`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_actor_client_id_foreign` FOREIGN KEY (`actor_client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `audit_logs_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `billing_defaults`
--
ALTER TABLE `billing_defaults`
  ADD CONSTRAINT `billing_defaults_updated_by_user_id_foreign` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `clients_updated_by_user_id_foreign` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `client_change_requests`
--
ALTER TABLE `client_change_requests`
  ADD CONSTRAINT `client_change_requests_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `client_change_requests_portal_account_id_foreign` FOREIGN KEY (`portal_account_id`) REFERENCES `portal_accounts` (`id`),
  ADD CONSTRAINT `client_change_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `client_contacts`
--
ALTER TABLE `client_contacts`
  ADD CONSTRAINT `client_contacts_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `client_contacts_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `client_contacts_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`),
  ADD CONSTRAINT `client_contacts_replaced_by_contact_id_foreign` FOREIGN KEY (`replaced_by_contact_id`) REFERENCES `client_contacts` (`id`),
  ADD CONSTRAINT `client_contacts_updated_by_user_id_foreign` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `client_payment_intents`
--
ALTER TABLE `client_payment_intents`
  ADD CONSTRAINT `client_payment_intents_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `client_payment_intents_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  ADD CONSTRAINT `client_payment_intents_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`),
  ADD CONSTRAINT `client_payment_intents_portal_account_id_foreign` FOREIGN KEY (`portal_account_id`) REFERENCES `portal_accounts` (`id`);

--
-- Constraints for table `contract_status_events`
--
ALTER TABLE `contract_status_events`
  ADD CONSTRAINT `contract_status_events_administrator_user_id_foreign` FOREIGN KEY (`administrator_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `contract_status_events_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`),
  ADD CONSTRAINT `contract_status_events_related_prior_event_id_foreign` FOREIGN KEY (`related_prior_event_id`) REFERENCES `contract_status_events` (`id`);

--
-- Constraints for table `email_deliveries`
--
ALTER TABLE `email_deliveries`
  ADD CONSTRAINT `email_deliveries_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `email_deliveries_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  ADD CONSTRAINT `email_deliveries_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`),
  ADD CONSTRAINT `email_deliveries_recipient_client_id_foreign` FOREIGN KEY (`recipient_client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `email_deliveries_sent_by_user_id_foreign` FOREIGN KEY (`sent_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `fee_assessments`
--
ALTER TABLE `fee_assessments`
  ADD CONSTRAINT `fee_assessments_financial_transaction_id_foreign` FOREIGN KEY (`financial_transaction_id`) REFERENCES `financial_transactions` (`id`),
  ADD CONSTRAINT `fee_assessments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `fee_assessments_invoice_item_id_foreign` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`),
  ADD CONSTRAINT `fee_assessments_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`),
  ADD CONSTRAINT `fee_assessments_recurring_fee_rule_id_foreign` FOREIGN KEY (`recurring_fee_rule_id`) REFERENCES `recurring_fee_rules` (`id`);

--
-- Constraints for table `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD CONSTRAINT `financial_transactions_authorized_by_user_id_foreign` FOREIGN KEY (`authorized_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `financial_transactions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `financial_transactions_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`),
  ADD CONSTRAINT `financial_transactions_posted_by_client_id_foreign` FOREIGN KEY (`posted_by_client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `financial_transactions_posted_by_user_id_foreign` FOREIGN KEY (`posted_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `financial_transactions_reversal_of_transaction_id_foreign` FOREIGN KEY (`reversal_of_transaction_id`) REFERENCES `financial_transactions` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `invoices_payment_plan_billing_term_id_foreign` FOREIGN KEY (`payment_plan_billing_term_id`) REFERENCES `payment_plan_billing_terms` (`id`),
  ADD CONSTRAINT `invoices_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `invoice_items_source_transaction_id_foreign` FOREIGN KEY (`source_transaction_id`) REFERENCES `financial_transactions` (`id`),
  ADD CONSTRAINT `invoice_items_waived_by_user_id_foreign` FOREIGN KEY (`waived_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoice_reminders`
--
ALTER TABLE `invoice_reminders`
  ADD CONSTRAINT `invoice_reminders_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `invoice_reminders_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`),
  ADD CONSTRAINT `invoice_reminders_recipient_client_id_foreign` FOREIGN KEY (`recipient_client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `invoice_reminders_sent_by_user_id_foreign` FOREIGN KEY (`sent_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `monthly_service_fee_satisfactions`
--
ALTER TABLE `monthly_service_fee_satisfactions`
  ADD CONSTRAINT `monthly_service_fee_satisfactions_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `monthly_service_fee_satisfactions_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`),
  ADD CONSTRAINT `monthly_service_fee_satisfactions_revoked_by_user_id_foreign` FOREIGN KEY (`revoked_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_financial_transaction_id_foreign` FOREIGN KEY (`financial_transaction_id`) REFERENCES `financial_transactions` (`id`),
  ADD CONSTRAINT `payments_instruction_recorded_by_user_id_foreign` FOREIGN KEY (`instruction_recorded_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payments_payer_client_id_foreign` FOREIGN KEY (`payer_client_id`) REFERENCES `clients` (`id`);

--
-- Constraints for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD CONSTRAINT `payment_allocations_fee_assessment_id_foreign` FOREIGN KEY (`fee_assessment_id`) REFERENCES `fee_assessments` (`id`),
  ADD CONSTRAINT `payment_allocations_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `payment_allocations_invoice_item_id_foreign` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`),
  ADD CONSTRAINT `payment_allocations_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`);

--
-- Constraints for table `payment_plans`
--
ALTER TABLE `payment_plans`
  ADD CONSTRAINT `payment_plans_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payment_plans_updated_by_user_id_foreign` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payment_plan_billing_terms`
--
ALTER TABLE `payment_plan_billing_terms`
  ADD CONSTRAINT `payment_plan_billing_terms_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payment_plan_billing_terms_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`);

--
-- Constraints for table `payment_plan_clients`
--
ALTER TABLE `payment_plan_clients`
  ADD CONSTRAINT `payment_plan_clients_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `payment_plan_clients_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payment_plan_clients_ended_by_user_id_foreign` FOREIGN KEY (`ended_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payment_plan_clients_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`);

--
-- Constraints for table `payment_plan_pauses`
--
ALTER TABLE `payment_plan_pauses`
  ADD CONSTRAINT `payment_plan_pauses_paused_by_user_id_foreign` FOREIGN KEY (`paused_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payment_plan_pauses_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`),
  ADD CONSTRAINT `payment_plan_pauses_resumed_by_user_id_foreign` FOREIGN KEY (`resumed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `portal_accounts`
--
ALTER TABLE `portal_accounts`
  ADD CONSTRAINT `portal_accounts_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Constraints for table `portal_invitations`
--
ALTER TABLE `portal_invitations`
  ADD CONSTRAINT `portal_invitations_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `portal_invitations_invited_by_user_id_foreign` FOREIGN KEY (`invited_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `recurring_fee_rules`
--
ALTER TABLE `recurring_fee_rules`
  ADD CONSTRAINT `recurring_fee_rules_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `recurring_fee_rules_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`),
  ADD CONSTRAINT `recurring_fee_rules_updated_by_user_id_foreign` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `secure_messages`
--
ALTER TABLE `secure_messages`
  ADD CONSTRAINT `secure_messages_secure_message_thread_id_foreign` FOREIGN KEY (`secure_message_thread_id`) REFERENCES `secure_message_threads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `secure_messages_sender_client_id_foreign` FOREIGN KEY (`sender_client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `secure_messages_sender_user_id_foreign` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `secure_message_attachments`
--
ALTER TABLE `secure_message_attachments`
  ADD CONSTRAINT `secure_message_attachments_secure_message_id_foreign` FOREIGN KEY (`secure_message_id`) REFERENCES `secure_messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `secure_message_documents`
--
ALTER TABLE `secure_message_documents`
  ADD CONSTRAINT `secure_message_documents_secure_message_id_foreign` FOREIGN KEY (`secure_message_id`) REFERENCES `secure_messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `secure_message_documents_shared_document_id_foreign` FOREIGN KEY (`shared_document_id`) REFERENCES `shared_documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `secure_message_revisions`
--
ALTER TABLE `secure_message_revisions`
  ADD CONSTRAINT `secure_message_revisions_edited_by_user_id_foreign` FOREIGN KEY (`edited_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `secure_message_revisions_secure_message_id_foreign` FOREIGN KEY (`secure_message_id`) REFERENCES `secure_messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `secure_message_threads`
--
ALTER TABLE `secure_message_threads`
  ADD CONSTRAINT `secure_message_threads_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `secure_message_threads_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shared_documents`
--
ALTER TABLE `shared_documents`
  ADD CONSTRAINT `shared_documents_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `shared_documents_payment_plan_id_foreign` FOREIGN KEY (`payment_plan_id`) REFERENCES `payment_plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shared_documents_uploaded_by_client_id_foreign` FOREIGN KEY (`uploaded_by_client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shared_documents_uploaded_by_user_id_foreign` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transaction_effects`
--
ALTER TABLE `transaction_effects`
  ADD CONSTRAINT `transaction_effects_fee_assessment_id_foreign` FOREIGN KEY (`fee_assessment_id`) REFERENCES `fee_assessments` (`id`),
  ADD CONSTRAINT `transaction_effects_financial_transaction_id_foreign` FOREIGN KEY (`financial_transaction_id`) REFERENCES `financial_transactions` (`id`),
  ADD CONSTRAINT `transaction_effects_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `transaction_effects_invoice_item_id_foreign` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
