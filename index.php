<?php
session_start();

// Connect to (or create) the SQLite database.
$db = new SQLite3('analytics.db');

// Create the users table if it doesn't exist.
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    password TEXT
)");

// Insert the default user if it does not exist.
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

// Handle logout action.
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

$message = '';

// Process login submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit;
    } else {
        $message = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Jocarsa Analytics - Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<?php if (!isset($_SESSION['username'])): ?>
    <!-- Login Form -->
    <div class="login-container">
        <h2>Jocarsa Analytics - Admin Login</h2>
        <?php if ($message): ?>
            <p class="error"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form method="post" action="index.php">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <button type="submit" name="login">Login</button>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Admin Control Panel -->
    <header>
        <h1>Jocarsa Analytics - Admin Panel</h1>
    </header>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="index.php?action=logout">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
    </nav>
    <div class="container">
        <h2>Analytics Logs</h2>
        <?php
            // Retrieve logs from the "logs" table.
            $result = $db->query("SELECT * FROM logs ORDER BY id DESC");
        ?>
        <table>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>User Agent</th>
                <th>Screen (WxH)</th>
                <th>Viewport (WxH)</th>
                <th>Language</th>
                <th>Languages</th>
                <th>Timezone Offset</th>
                <th>Platform</th>
                <th>Connection</th>
                <th>Color Depth</th>
                <th>URL</th>
                <th>Referrer</th>
                <th>Timestamp</th>
                <th>Performance Timing</th>
                <th>IP</th>
            </tr>
            <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td><?php echo htmlspecialchars($row['user']); ?></td>
                <td><?php echo htmlspecialchars($row['user_agent']); ?></td>
                <td><?php echo htmlspecialchars($row['screen_width']) . " x " . htmlspecialchars($row['screen_height']); ?></td>
                <td><?php echo htmlspecialchars($row['viewport_width']) . " x " . htmlspecialchars($row['viewport_height']); ?></td>
                <td><?php echo htmlspecialchars($row['language']); ?></td>
                <td><?php echo htmlspecialchars($row['languages']); ?></td>
                <td><?php echo htmlspecialchars($row['timezone_offset']); ?></td>
                <td><?php echo htmlspecialchars($row['platform']); ?></td>
                <td><?php echo htmlspecialchars($row['connection_type']); ?></td>
                <td><?php echo htmlspecialchars($row['screen_color_depth']); ?></td>
                <td><?php echo htmlspecialchars($row['url']); ?></td>
                <td><?php echo htmlspecialchars($row['referrer']); ?></td>
                <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                <td><?php echo htmlspecialchars($row['performance_timing']); ?></td>
                <td><?php echo htmlspecialchars($row['ip']); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
<?php endif; ?>
</body>
</html>

