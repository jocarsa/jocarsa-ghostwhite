<?php
	// Connect to (or create) the SQLite database.
$db = new SQLite3('../databases/ghostwhite.db');

// Create the users table if it doesn't exist.
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    password TEXT
)");

// Insert default user if not present.
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = :username");
$stmt->bindValue(':username', 'jocarsa', SQLITE3_TEXT);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
if ($row['count'] == 0) {
    $hashed = password_hash('jocarsa', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
    $stmt->bindValue(':username', 'jocarsa', SQLITE3_TEXT);
    $stmt->bindValue(':password', $hashed, SQLITE3_TEXT);
    $stmt->execute();
}

// Create a table to store the chart type preference for each chart.
$db->exec("CREATE TABLE IF NOT EXISTS chart_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chart_id TEXT UNIQUE,
    chart_type TEXT
)");

// Retrieve all chart preferences into an associative array.
$chartPrefs = [];
$resPrefs = $db->query("SELECT chart_id, chart_type FROM chart_preferences");
while ($row = $resPrefs->fetchArray(SQLITE3_ASSOC)) {
    $chartPrefs[$row['chart_id']] = $row['chart_type'];
}

?>
