<?php
session_start();

// Make sure the user is logged in.
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chart_type'])) {
    $chartType = $_POST['chart_type'];
    $db = new SQLite3('analytics.db');

    // Ensure the preferences table exists.
    $db->exec("CREATE TABLE IF NOT EXISTS chart_preferences (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chart_type TEXT
    )");

    // For simplicity, clear previous entries and insert the new one.
    $db->exec("DELETE FROM chart_preferences");
    $stmt = $db->prepare("INSERT INTO chart_preferences (chart_type) VALUES (:chart_type)");
    $stmt->bindValue(':chart_type', $chartType, SQLITE3_TEXT);
    $stmt->execute();

    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error']);
?>

