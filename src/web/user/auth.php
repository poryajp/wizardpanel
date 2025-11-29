<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

function validateTelegramWebAppData($initData)
{
    if (!$initData) {
        return false;
    }

    $data = [];
    $pairs = explode('&', $initData);
    foreach ($pairs as $pair) {
        $parts = explode('=', $pair, 2);
        if (count($parts) === 2) {
            $key = urldecode($parts[0]);
            $value = urldecode($parts[1]);
            $data[$key] = $value;
        }
    }

    if (!isset($data['hash'])) {
        return false;
    }

    $hash = $data['hash'];
    unset($data['hash']);

    ksort($data);
    $data_check_string = [];
    foreach ($data as $key => $value) {
        $data_check_string[] = $key . '=' . $value;
    }
    $data_check_string = implode("\n", $data_check_string);

    $secret_key = hash_hmac('sha256', BOT_TOKEN, "WebAppData", true);
    $calculated_hash = bin2hex(hash_hmac('sha256', $data_check_string, $secret_key, true));

    if (strcmp($hash, $calculated_hash) !== 0) {
        return false;
    }

    // Check auth_date for freshness (e.g., within 24 hours)
    if (isset($data['auth_date'])) {
        if (time() - $data['auth_date'] > 86400) {
            return false;
        }
    }

    return json_decode($data['user'], true);
}
