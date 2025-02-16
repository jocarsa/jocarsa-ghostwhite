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

// Process logout.
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
    <!-- LOGIN FORM -->
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
    <?php
    // Get filter parameters.
    $filterUser = isset($_GET['filter_user']) ? $_GET['filter_user'] : '';
    // Default date range: last two weeks.
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-2 weeks'));
    $endDate   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');

    // Get distinct users from logs for the left nav.
    $userQuery = "SELECT DISTINCT user FROM logs WHERE user <> '' ORDER BY user";
    $userResult = $db->query($userQuery);
    $users = [];
    while ($u = $userResult->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $u['user'];
    }

    // Build the query for logs with date and user filters.
    $query = "SELECT * FROM logs WHERE date(timestamp) BETWEEN :startDate AND :endDate";
    if ($filterUser !== '') {
        $query .= " AND user = :user";
    }
    $query .= " ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':startDate', $startDate, SQLITE3_TEXT);
    $stmt->bindValue(':endDate', $endDate, SQLITE3_TEXT);
    if ($filterUser !== '') {
        $stmt->bindValue(':user', $filterUser, SQLITE3_TEXT);
    }
    $logsResult = $stmt->execute();
    ?>
    <div id="wrapper">
        <header>
            <h1>Jocarsa Analytics - Admin Panel</h1>
        </header>
        <div id="main-container">
            <nav id="sidebar">
                <h3>Users</h3>
                <ul>
                    <li>
                        <a href="index.php?start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>">
                            All Users
                        </a>
                    </li>
                    <?php foreach ($users as $usr): ?>
                        <li>
                            <a href="index.php?filter_user=<?php echo urlencode($usr); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>">
                                <?php echo htmlspecialchars($usr); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="logout">
                    <a href="index.php?action=logout">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                </div>
            </nav>
            <main id="content">
                <section class="filters">
                    <h3>Date Filter</h3>
                    <form method="get" action="index.php">
                        <?php if ($filterUser !== ''): ?>
                            <input type="hidden" name="filter_user" value="<?php echo htmlspecialchars($filterUser); ?>">
                        <?php endif; ?>
                        <label for="start_date">Start Date:</label>
                        <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                        <label for="end_date">End Date:</label>
                        <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                        <button type="submit">Apply</button>
                    </form>
                </section>
                <section class="logs">
                    <h3>Analytics Logs<?php if ($filterUser !== '') { echo " - " . htmlspecialchars($filterUser); } ?></h3>
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
                        <?php while ($row = $logsResult->fetchArray(SQLITE3_ASSOC)): ?>
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
                </section>
            </main>
        </div>
    </div>
<?php endif; ?>
</body>
</html>

