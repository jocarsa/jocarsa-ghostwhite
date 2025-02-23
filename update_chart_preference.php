<?php
session_start();

// Ensure the user is logged in.
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chart_id'], $_POST['chart_type'])) {
    $chartId = $_POST['chart_id'];
    $chartType = $_POST['chart_type'];
    $db = new SQLite3('analytics.db');

    // Ensure the chart_preferences table exists with a UNIQUE chart_id.
    $db->exec("CREATE TABLE IF NOT EXISTS chart_preferences (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        chart_id TEXT UNIQUE,
        chart_type TEXT
    )");

    // Use INSERT OR REPLACE to store/update the preference for the given chart.
    $stmt = $db->prepare("INSERT OR REPLACE INTO chart_preferences (chart_id, chart_type) VALUES (:chart_id, :chart_type)");
    $stmt->bindValue(':chart_id', $chartId, SQLITE3_TEXT);
    $stmt->bindValue(':chart_type', $chartType, SQLITE3_TEXT);
    $stmt->execute();

    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error']);
?>

