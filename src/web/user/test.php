<?php
// Simple test file to check if everything is working
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Testing config.php...<br>";
require_once __DIR__ . '/../../includes/config.php';
echo "✅ Config loaded. BOT_TOKEN exists: " . (defined('BOT_TOKEN') ? 'YES' : 'NO') . "<br>";
echo "✅ BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "<br><br>";

echo "2. Testing db.php...<br>";
require_once __DIR__ . '/../../includes/db.php';
echo "✅ Database connection loaded<br><br>";

echo "3. Testing functions.php...<br>";
require_once __DIR__ . '/../../includes/functions.php';
echo "✅ Functions loaded<br><br>";

echo "4. Testing auth.php...<br>";
require_once __DIR__ . '/auth.php';
echo "✅ Auth loaded<br><br>";

echo "5. Testing session.php...<br>";
require_once __DIR__ . '/session.php';
echo "✅ Session loaded<br><br>";

echo "<strong>All systems operational!</strong><br>";
echo "You can now delete this file (test.php).";
