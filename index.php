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

// Create a table to store the chart type preference if it doesn't exist.
$db->exec("CREATE TABLE IF NOT EXISTS chart_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chart_type TEXT
)");

// Retrieve the default chart type, defaulting to 'bar'.
$defaultChartType = 'bar';
$stmtPref = $db->query("SELECT chart_type FROM chart_preferences ORDER BY id DESC LIMIT 1");
if ($pref = $stmtPref->fetchArray(SQLITE3_ASSOC)) {
    $defaultChartType = $pref['chart_type'];
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

if (isset($_SESSION['username'])) {
    // Get filters from GET parameters.
    $filterUser = isset($_GET['filter_user']) ? $_GET['filter_user'] : '';
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-2 weeks'));
    $endDate   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
    $section   = isset($_GET['section'])    ? $_GET['section']    : 'overview';

    // Get distinct users from logs for sidebar.
    $userQuery = "SELECT DISTINCT user FROM logs WHERE user <> '' ORDER BY user";
    $userResult = $db->query($userQuery);
    $users = [];
    while ($u = $userResult->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $u['user'];
    }

    // Prepare data arrays for statistics.
    $dataDay = [];
    $dataWeek = [];
    $dataMonth = [];
    $dataHour = [];
    $dataResolutions = [];
    $dataOS = [];
    $dataBrowsers = [];
    $dataLanguages = [];

    if ($section === "overview") {
        // Visits per day.
        $q = "SELECT date(timestamp) as label, count(*) as visits 
              FROM logs 
              WHERE date(timestamp) BETWEEN :startDate AND :endDate";
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY label";
        $stmtDay = $db->prepare($q);
        $stmtDay->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtDay->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtDay->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resDay = $stmtDay->execute();
        while ($row = $resDay->fetchArray(SQLITE3_ASSOC)) {
            $dataDay[] = $row;
        }
        // Visits per week.
        $q = "SELECT strftime('%Y-%W', timestamp) as label, count(*) as visits 
              FROM logs 
              WHERE date(timestamp) BETWEEN :startDate AND :endDate";
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY label";
        $stmtWeek = $db->prepare($q);
        $stmtWeek->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtWeek->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtWeek->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resWeek = $stmtWeek->execute();
        while ($row = $resWeek->fetchArray(SQLITE3_ASSOC)) {
            $dataWeek[] = $row;
        }
        // Visits per month.
        $q = "SELECT strftime('%Y-%m', timestamp) as label, count(*) as visits 
              FROM logs 
              WHERE date(timestamp) BETWEEN :startDate AND :endDate";
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY label";
        $stmtMonth = $db->prepare($q);
        $stmtMonth->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtMonth->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtMonth->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resMonth = $stmtMonth->execute();
        while ($row = $resMonth->fetchArray(SQLITE3_ASSOC)) {
            $dataMonth[] = $row;
        }
        // Visits per hour (last 24 hours).
        $q = "SELECT strftime('%H', timestamp) as label, count(*) as visits 
              FROM logs 
              WHERE timestamp >= datetime('now', '-24 hours')";
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY label";
        $stmtHour = $db->prepare($q);
        if ($filterUser !== '') { $stmtHour->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resHour = $stmtHour->execute();
        while ($row = $resHour->fetchArray(SQLITE3_ASSOC)) {
            $dataHour[] = $row;
        }
    } elseif ($section === "resolutions") {
        $q = "SELECT (screen_width || 'x' || screen_height) as label, count(*) as visits 
              FROM logs 
              WHERE date(timestamp) BETWEEN :startDate AND :endDate";
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtRes = $db->prepare($q);
        $stmtRes->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtRes->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtRes->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resRes = $stmtRes->execute();
        while ($row = $resRes->fetchArray(SQLITE3_ASSOC)) {
            $dataResolutions[] = $row;
        }
    } elseif ($section === "os") {
        $q = "SELECT platform as label, count(*) as visits 
              FROM logs 
              WHERE date(timestamp) BETWEEN :startDate AND :endDate";
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtOS = $db->prepare($q);
        $stmtOS->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtOS->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtOS->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resOS = $stmtOS->execute();
        while ($row = $resOS->fetchArray(SQLITE3_ASSOC)) {
            $dataOS[] = $row;
        }
    } elseif ($section === "browsers") {
        $q = "SELECT 
                CASE 
                  WHEN user_agent LIKE '%Chrome%' AND user_agent NOT LIKE '%Edge%' THEN 'Chrome'
                  WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                  WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                  WHEN user_agent LIKE '%Edge%' THEN 'Edge'
                  ELSE 'Other'
                END as label, count(*) as visits 
              FROM logs 
              WHERE date(timestamp) BETWEEN :startDate AND :endDate";
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtBrowsers = $db->prepare($q);
        $stmtBrowsers->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtBrowsers->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtBrowsers->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resBrowsers = $stmtBrowsers->execute();
        while ($row = $resBrowsers->fetchArray(SQLITE3_ASSOC)) {
            $dataBrowsers[] = $row;
        }
    } elseif ($section === "languages") {
        $q = "SELECT language as label, count(*) as visits 
              FROM logs 
              WHERE date(timestamp) BETWEEN :startDate AND :endDate";
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtLang = $db->prepare($q);
        $stmtLang->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtLang->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtLang->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resLang = $stmtLang->execute();
        while ($row = $resLang->fetchArray(SQLITE3_ASSOC)) {
            $dataLanguages[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>jocarsa | ghostwhite</title>
  <link rel="stylesheet" href="admin.css">
  <link rel="icon" type="image/svg+xml" href="ghostwhite.png" />
</head>
<body>
<?php if (!isset($_SESSION['username'])): ?>
  <!-- LOGIN FORM -->
  <div class="login-container">
    <img src="ghostwhite.png" alt="Logo">
    <h2>jocarsa | ghostwhite</h2>
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
  <div id="wrapper">
    <!-- HEADER -->
    <header>
      <img src="ghostwhite.png" alt="Logo">
      <h1>jocarsa | ghostwhite</h1>
    </header>
    <div id="main-container">
      <!-- SIDEBAR -->
      <nav id="sidebar">
        <h3>Users</h3>
        <ul>
          <li>
            <a href="index.php?start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>">All Users</a>
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
      <!-- MAIN CONTENT -->
      <main id="content">
        <!-- SUB-TABS -->
        <div id="sub-tabs">
          <a href="index.php?section=overview&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="overview") echo 'class="active"'; ?>>Overview</a>
          <a href="index.php?section=resolutions&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="resolutions") echo 'class="active"'; ?>>Resolutions</a>
          <a href="index.php?section=os&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="os") echo 'class="active"'; ?>>Operating Systems</a>
          <a href="index.php?section=browsers&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="browsers") echo 'class="active"'; ?>>Browsers</a>
          <a href="index.php?section=languages&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="languages") echo 'class="active"'; ?>>Languages</a>
          <a href="index.php?section=raw&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="raw") echo 'class="active"'; ?>>Raw Data</a>
        </div>
        <!-- DATE FILTER -->
        <section class="filters">
          <h3>Date Filter</h3>
          <form method="get" action="index.php">
            <?php if ($filterUser !== ''): ?>
              <input type="hidden" name="filter_user" value="<?php echo htmlspecialchars($filterUser); ?>">
            <?php endif; ?>
            <?php if ($section !== ''): ?>
              <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
            <?php endif; ?>
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            <button type="submit">Apply</button>
          </form>
        </section>
        <!-- Declare the defaultChartType variable for all sections -->
        <script>
          var defaultChartType = <?php echo json_encode($defaultChartType); ?>;
        </script>
        <!-- STATS CONTENT -->
        <section class="stats">
          <?php if ($section === "overview"): ?>
            <div class="chart-section">
              <h3>Visits Per Day</h3>
              <div class="chart-controls">
                <button onclick="redrawChart('chart-day', dataDay, 'bar'); updateChartPreference('bar');">Bar</button>
                <button onclick="redrawChart('chart-day', dataDay, 'line'); updateChartPreference('line');">Line</button>
                <button onclick="redrawChart('chart-day', dataDay, 'pie'); updateChartPreference('pie');">Pie</button>
              </div>
              <div id="chart-day" class="chart-container"></div>
            </div>
            <div class="chart-section">
              <h3>Visits Per Week</h3>
              <div class="chart-controls">
                <button onclick="redrawChart('chart-week', dataWeek, 'bar'); updateChartPreference('bar');">Bar</button>
                <button onclick="redrawChart('chart-week', dataWeek, 'line'); updateChartPreference('line');">Line</button>
                <button onclick="redrawChart('chart-week', dataWeek, 'pie'); updateChartPreference('pie');">Pie</button>
              </div>
              <div id="chart-week" class="chart-container"></div>
            </div>
            <div class="chart-section">
              <h3>Visits Per Month</h3>
              <div class="chart-controls">
                <button onclick="redrawChart('chart-month', dataMonth, 'bar'); updateChartPreference('bar');">Bar</button>
                <button onclick="redrawChart('chart-month', dataMonth, 'line'); updateChartPreference('line');">Line</button>
                <button onclick="redrawChart('chart-month', dataMonth, 'pie'); updateChartPreference('pie');">Pie</button>
              </div>
              <div id="chart-month" class="chart-container"></div>
            </div>
            <div class="chart-section">
              <h3>Visits Per Hour (Last 24 Hours)</h3>
              <div class="chart-controls">
                <button onclick="redrawChart('chart-hour', dataHour, 'bar'); updateChartPreference('bar');">Bar</button>
                <button onclick="redrawChart('chart-hour', dataHour, 'line'); updateChartPreference('line');">Line</button>
                <button onclick="redrawChart('chart-hour', dataHour, 'pie'); updateChartPreference('pie');">Pie</button>
              </div>
              <div id="chart-hour" class="chart-container"></div>
            </div>
            <script>
              var dataDay = <?php echo json_encode($dataDay); ?>;
              var dataWeek = <?php echo json_encode($dataWeek); ?>;
              var dataMonth = <?php echo json_encode($dataMonth); ?>;
              var dataHour = <?php echo json_encode($dataHour); ?>;
              document.addEventListener("DOMContentLoaded", function(){
                redrawChart("chart-day", dataDay, defaultChartType);
                redrawChart("chart-week", dataWeek, defaultChartType);
                redrawChart("chart-month", dataMonth, defaultChartType);
                redrawChart("chart-hour", dataHour, defaultChartType);
              });
            </script>
          <?php elseif ($section === "resolutions"): ?>
            <div class="chart-section">
              <h3>Screen Resolutions</h3>
              <div class="chart-controls">
                <button onclick="redrawChart('chart-resolutions', dataResolutions, 'bar'); updateChartPreference('bar');">Bar</button>
                <button onclick="redrawChart('chart-resolutions', dataResolutions, 'line'); updateChartPreference('line');">Line</button>
                <button onclick="redrawChart('chart-resolutions', dataResolutions, 'pie'); updateChartPreference('pie');">Pie</button>
              </div>
              <div id="chart-resolutions" class="chart-container"></div>
            </div>
            <script>
              var dataResolutions = <?php echo json_encode($dataResolutions); ?>;
              document.addEventListener("DOMContentLoaded", function(){
                redrawChart("chart-resolutions", dataResolutions, defaultChartType);
              });
            </script>
          <?php elseif ($section === "os"): ?>
            <div class="chart-section">
              <h3>Operating Systems</h3>
              <div class="chart-controls">
                <button onclick="redrawChart('chart-os', dataOS, 'bar'); updateChartPreference('bar');">Bar</button>
                <button onclick="redrawChart('chart-os', dataOS, 'line'); updateChartPreference('line');">Line</button>
                <button onclick="redrawChart('chart-os', dataOS, 'pie'); updateChartPreference('pie');">Pie</button>
              </div>
              <div id="chart-os" class="chart-container"></div>
            </div>
            <script>
              var dataOS = <?php echo json_encode($dataOS); ?>;
              document.addEventListener("DOMContentLoaded", function(){
                redrawChart("chart-os", dataOS, defaultChartType);
              });
            </script>
          <?php elseif ($section === "browsers"): ?>
            <div class="chart-section">
              <h3>Browsers</h3>
              <div class="chart-controls">
                <button onclick="redrawChart('chart-browsers', dataBrowsers, 'bar'); updateChartPreference('bar');">Bar</button>
                <button onclick="redrawChart('chart-browsers', dataBrowsers, 'line'); updateChartPreference('line');">Line</button>
                <button onclick="redrawChart('chart-browsers', dataBrowsers, 'pie'); updateChartPreference('pie');">Pie</button>
              </div>
              <div id="chart-browsers" class="chart-container"></div>
            </div>
            <script>
              var dataBrowsers = <?php echo json_encode($dataBrowsers); ?>;
              document.addEventListener("DOMContentLoaded", function(){
                redrawChart("chart-browsers", dataBrowsers, defaultChartType);
              });
            </script>
          <?php elseif ($section === "languages"): ?>
            <div class="chart-section">
              <h3>Languages</h3>
              <div class="chart-controls">
                <button onclick="redrawChart('chart-languages', dataLanguages, 'bar'); updateChartPreference('bar');">Bar</button>
                <button onclick="redrawChart('chart-languages', dataLanguages, 'line'); updateChartPreference('line');">Line</button>
                <button onclick="redrawChart('chart-languages', dataLanguages, 'pie'); updateChartPreference('pie');">Pie</button>
              </div>
              <div id="chart-languages" class="chart-container"></div>
            </div>
            <script>
              var dataLanguages = <?php echo json_encode($dataLanguages); ?>;
              document.addEventListener("DOMContentLoaded", function(){
                redrawChart("chart-languages", dataLanguages, defaultChartType);
              });
            </script>
          <?php elseif ($section === "raw"): ?>
            <h3>Raw Analytics Data</h3>
            <?php
              $q = "SELECT * FROM logs WHERE date(timestamp) BETWEEN :startDate AND :endDate";
              if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
              $q .= " ORDER BY id DESC";
              $stmtRaw = $db->prepare($q);
              $stmtRaw->bindValue(':startDate', $startDate, SQLITE3_TEXT);
              $stmtRaw->bindValue(':endDate', $endDate, SQLITE3_TEXT);
              if ($filterUser !== '') { $stmtRaw->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
              $resRaw = $stmtRaw->execute();
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
                <th>IP</th>
              </tr>
              <?php while ($row = $resRaw->fetchArray(SQLITE3_ASSOC)): ?>
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
                  <td><?php echo htmlspecialchars($row['ip']); ?></td>
                </tr>
              <?php endwhile; ?>
            </table>
          <?php endif; ?>
        </section>
      </main>
    </div>
  </div>
  <!-- Include the charts library -->
  <script src="charts.js"></script>
<?php endif; ?>
</body>
</html>

