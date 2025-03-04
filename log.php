<?php
// log.php

// Allow connections from any origin
header("Access-Control-Allow-Origin: *");

// Handle preflight OPTIONS request if necessary
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

header('Content-Type: application/json');

try {
    // Connect to (or create) the SQLite database
    $db = new SQLite3('../databases/ghostwhite.db');

    // Create the table if it doesn't exist with the new columns
    $db->exec("CREATE TABLE IF NOT EXISTS logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user TEXT,
        user_agent TEXT,
        screen_width INTEGER,
        screen_height INTEGER,
        viewport_width INTEGER,
        viewport_height INTEGER,
        language TEXT,
        languages TEXT,
        timezone_offset INTEGER,
        platform TEXT,
        connection_type TEXT,
        screen_color_depth INTEGER,
        url TEXT,
        referrer TEXT,
        timestamp TEXT,
        performance_timing TEXT,
        ip TEXT
    )");

    // Read the JSON payload from the request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!$data) {
        throw new Exception("Invalid JSON payload.");
    }

    // Retrieve values from the payload with defaults if missing
    $user               = $data['user'] ?? '';
    $user_agent         = $data['user_agent'] ?? '';
    $screen_width       = $data['screen_width'] ?? 0;
    $screen_height      = $data['screen_height'] ?? 0;
    $viewport_width     = $data['viewport_width'] ?? 0;
    $viewport_height    = $data['viewport_height'] ?? 0;
    $language           = $data['language'] ?? '';
    $languages          = $data['languages'] ?? '';
    $timezone_offset    = $data['timezone_offset'] ?? 0;
    $platform           = $data['platform'] ?? '';
    $connection_type    = $data['connection_type'] ?? '';
    $screen_color_depth = $data['screen_color_depth'] ?? 0;
    $url                = $data['url'] ?? '';
    $referrer           = $data['referrer'] ?? '';
    $timestamp          = $data['timestamp'] ?? date('c');
    $performance_timing = isset($data['performance_timing']) ? json_encode($data['performance_timing']) : '{}';
    $ip                 = $_SERVER['REMOTE_ADDR'];

    // Prepare and execute the insert statement
    $stmt = $db->prepare("INSERT INTO logs (
        user, user_agent, screen_width, screen_height, viewport_width, viewport_height, language, languages, timezone_offset, platform, connection_type, screen_color_depth, url, referrer, timestamp, performance_timing, ip
    ) VALUES (
        :user, :user_agent, :screen_width, :screen_height, :viewport_width, :viewport_height, :language, :languages, :timezone_offset, :platform, :connection_type, :screen_color_depth, :url, :referrer, :timestamp, :performance_timing, :ip
    )");
    $stmt->bindValue(':user', $user, SQLITE3_TEXT);
    $stmt->bindValue(':user_agent', $user_agent, SQLITE3_TEXT);
    $stmt->bindValue(':screen_width', $screen_width, SQLITE3_INTEGER);
    $stmt->bindValue(':screen_height', $screen_height, SQLITE3_INTEGER);
    $stmt->bindValue(':viewport_width', $viewport_width, SQLITE3_INTEGER);
    $stmt->bindValue(':viewport_height', $viewport_height, SQLITE3_INTEGER);
    $stmt->bindValue(':language', $language, SQLITE3_TEXT);
    $stmt->bindValue(':languages', $languages, SQLITE3_TEXT);
    $stmt->bindValue(':timezone_offset', $timezone_offset, SQLITE3_INTEGER);
    $stmt->bindValue(':platform', $platform, SQLITE3_TEXT);
    $stmt->bindValue(':connection_type', $connection_type, SQLITE3_TEXT);
    $stmt->bindValue(':screen_color_depth', $screen_color_depth, SQLITE3_INTEGER);
    $stmt->bindValue(':url', $url, SQLITE3_TEXT);
    $stmt->bindValue(':referrer', $referrer, SQLITE3_TEXT);
    $stmt->bindValue(':timestamp', $timestamp, SQLITE3_TEXT);
    $stmt->bindValue(':performance_timing', $performance_timing, SQLITE3_TEXT);
    $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
    $stmt->execute();

    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

