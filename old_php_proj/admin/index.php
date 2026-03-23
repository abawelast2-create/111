<?php
// admin/index.php - تحويل إلى dashboard
require_once __DIR__ . '/../includes/config.php';
header('Location: ' . SITE_URL . '/admin/dashboard.php'); exit;
