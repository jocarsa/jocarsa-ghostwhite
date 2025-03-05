<?php
session_start();

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
        $message = "Nombre de usuario o contraseña incorrectos.";
    }
}

if (isset($_SESSION['username'])) {
    // Get filters from GET parameters.
    $filterUser = isset($_GET['filter_user']) ? $_GET['filter_user'] : '';
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-2 weeks'));
    $endDate   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
    $section   = isset($_GET['section'])    ? $_GET['section']    : 'overview';

    // Fetch the assigned accounts for the logged in user from the user_accounts table.
    $currentUser = $_SESSION['username'];
    $assignedAccounts = [];
    $stmtAssign = $db->prepare("SELECT account FROM user_accounts WHERE user = :user");
    $stmtAssign->bindValue(':user', $currentUser, SQLITE3_TEXT);
    $resAssign = $stmtAssign->execute();
    while ($rowAssign = $resAssign->fetchArray(SQLITE3_ASSOC)) {
        $assignedAccounts[] = $rowAssign['account'];
    }
    // Build a clause to restrict logs to only those accounts.
    if (!empty($assignedAccounts)) {
        // Create a comma-separated, single-quoted list
        $inList = "'" . implode("','", $assignedAccounts) . "'";
        $accountsClause = " AND user IN ($inList)";
    } else {
        // If no account is assigned, force the query to return no rows.
        $accountsClause = " AND 0";
    }

    // Get distinct users from logs for sidebar (only within assigned accounts).
    $userQuery = "SELECT DISTINCT user FROM logs WHERE user <> '' " . $accountsClause . " ORDER BY user";
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
    $dataTimezones = [];
    $dataColorDepth = [];
    $dataUrls = [];
    $dataIps = [];

    if ($section === "overview") {
        // Visits per day.
        $q = "SELECT date(timestamp) as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
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
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
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
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
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
              WHERE timestamp >= datetime('now', '-24 hours')" . $accountsClause;
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
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
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
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
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
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
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
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
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
    } elseif ($section === "timezones") {
        $q = "SELECT timezone_offset as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtTimezones = $db->prepare($q);
        $stmtTimezones->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtTimezones->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtTimezones->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resTimezones = $stmtTimezones->execute();
        while ($row = $resTimezones->fetchArray(SQLITE3_ASSOC)) {
            $dataTimezones[] = $row;
        }
    } elseif ($section === "color_depth") {
        $q = "SELECT screen_color_depth as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtColorDepth = $db->prepare($q);
        $stmtColorDepth->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtColorDepth->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtColorDepth->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resColorDepth = $stmtColorDepth->execute();
        while ($row = $resColorDepth->fetchArray(SQLITE3_ASSOC)) {
            $dataColorDepth[] = $row;
        }
    } elseif ($section === "urls") {
        $q = "SELECT url as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtUrls = $db->prepare($q);
        $stmtUrls->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtUrls->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtUrls->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resUrls = $stmtUrls->execute();
        while ($row = $resUrls->fetchArray(SQLITE3_ASSOC)) {
            $dataUrls[] = $row;
        }
    } elseif ($section === "ips") {
        $q = "SELECT ip as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
        if ($filterUser !== '') { $q .= " AND user = :filter_user"; }
        $q .= " GROUP BY label ORDER BY visits DESC";
        $stmtIps = $db->prepare($q);
        $stmtIps->bindValue(':startDate', $startDate, SQLITE3_TEXT);
        $stmtIps->bindValue(':endDate', $endDate, SQLITE3_TEXT);
        if ($filterUser !== '') { $stmtIps->bindValue(':filter_user', $filterUser, SQLITE3_TEXT); }
        $resIps = $stmtIps->execute();
        while ($row = $resIps->fetchArray(SQLITE3_ASSOC)) {
            $dataIps[] = $row;
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
        <label for="username">Nombre de usuario:</label>
        <input type="text" name="username" id="username" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Contraseña:</label>
        <input type="password" name="password" id="password" required>
      </div>
      <div class="form-group">
        <button type="submit" name="login">Iniciar sesión</button>
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
        <h3>Usuarios</h3>
        <ul>
          <li>
            <a href="index.php?start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>">Todos los usuarios</a>
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
          <a href="index.php?action=logout">Cerrar sesión (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        </div>
      </nav>
      <!-- MAIN CONTENT -->
      <main id="content">
        <!-- SUB-TABS -->
        <div id="sub-tabs">
          <a href="index.php?section=overview&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="overview") echo 'class="active"'; ?>>Resumen</a>
          <a href="index.php?section=resolutions&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="resolutions") echo 'class="active"'; ?>>Resoluciones</a>
          <a href="index.php?section=os&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="os") echo 'class="active"'; ?>>Sistemas Operativos</a>
          <a href="index.php?section=browsers&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="browsers") echo 'class="active"'; ?>>Navegadores</a>
          <a href="index.php?section=languages&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="languages") echo 'class="active"'; ?>>Idiomas</a>
          <a href="index.php?section=timezones&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="timezones") echo 'class="active"'; ?>>Zonas Horarias</a>
          <a href="index.php?section=color_depth&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="color_depth") echo 'class="active"'; ?>>Profundidad de Color</a>
          <a href="index.php?section=urls&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="urls") echo 'class="active"'; ?>>URLs Visitadas</a>
          <a href="index.php?section=ips&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="ips") echo 'class="active"'; ?>>IPs</a>
          <a href="index.php?section=raw&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="raw") echo 'class="active"'; ?>>Datos Sin Procesar</a>
        	<a href="index.php?section=calendar&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="calendar") echo 'class="active"'; ?>>Calendario</a>
			<a href="index.php?section=heatmap&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="heatmap") echo 'class="active"'; ?>>Heatmap</a>
			<a href="index.php?section=robots&filter_user=<?php echo urlencode($filterUser); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="robots") echo 'class="active"'; ?>>Robots vs Humanos</a>

        </div>
        <!-- DATE FILTER -->
        <section class="filters">
          <h3>Filtro de Fecha</h3>
          <form method="get" action="index.php">
            <?php if ($filterUser !== ''): ?>
              <input type="hidden" name="filter_user" value="<?php echo htmlspecialchars($filterUser); ?>">
            <?php endif; ?>
            <?php if ($section !== ''): ?>
              <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
            <?php endif; ?>
            <label for="start_date">Fecha de Inicio:</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            <label for="end_date">Fecha de Fin:</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            <button type="submit">Aplicar</button>
          </form>
        </section>
        <!-- Global JavaScript variables and functions -->
        <script>
          // chartPreferences holds any saved preference per chart.
          // If a preference is not set, 'bar' is used by default.
          var chartPreferences = <?php echo json_encode($chartPrefs); ?>;
          function updateChartPreference(chartId, type) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_chart_preference.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.send("chart_id=" + encodeURIComponent(chartId) + "&chart_type=" + encodeURIComponent(type));
          }
        </script>
        <!-- STATISTICS CONTENT -->
        <section class="stats">
    <div class="chart-grid">
        <?php if ($section === "overview"): ?>
            <div class="chart-section">
                <h3>Visitas Por Día</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-day', dataDay, 'bar'); updateChartPreference('day', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-day', dataDay, 'line'); updateChartPreference('day', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-day', dataDay, 'pie'); updateChartPreference('day', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-day', dataDay, 'horizontal'); updateChartPreference('day', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-day', dataDay, 'table'); updateChartPreference('day', 'table');">Tabla</button>

                </div>
                <div id="chart-day" class="chart-container"></div>
            </div>
            <div class="chart-section">
                <h3>Visitas Por Semana</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-week', dataWeek, 'bar'); updateChartPreference('week', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-week', dataWeek, 'line'); updateChartPreference('week', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-week', dataWeek, 'pie'); updateChartPreference('week', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-week', dataWeek, 'horizontal'); updateChartPreference('week', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-day', dataWeek, 'table'); updateChartPreference('week', 'table');">Tabla</button>

                </div>
                <div id="chart-week" class="chart-container"></div>
            </div>
            <div class="chart-section">
                <h3>Visitas Por Mes</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-month', dataMonth, 'bar'); updateChartPreference('month', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-month', dataMonth, 'line'); updateChartPreference('month', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-month', dataMonth, 'pie'); updateChartPreference('month', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-month', dataMonth, 'horizontal'); updateChartPreference('month', 'horizontal');">Horizontal</button>
                    <button onclick="redrawChart('chart-day', dataMonth, 'table'); updateChartPreference('month', 'table');">Tabla</button>

                </div>
                <div id="chart-month" class="chart-container"></div>
            </div>
            <div class="chart-section">
                <h3>Visitas Por Hora (Últimas 24 Horas)</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-hour', dataHour, 'bar'); updateChartPreference('hour', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-hour', dataHour, 'line'); updateChartPreference('hour', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-hour', dataHour, 'pie'); updateChartPreference('hour', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-hour', dataHour, 'horizontal'); updateChartPreference('hour', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-day', dataHour, 'table'); updateChartPreference('hour', 'table');">Tabla</button>

                </div>
                <div id="chart-hour" class="chart-container"></div>
            </div>
            <script>
                var dataDay = <?php echo json_encode($dataDay); ?>;
                var dataWeek = <?php echo json_encode($dataWeek); ?>;
                var dataMonth = <?php echo json_encode($dataMonth); ?>;
                var dataHour = <?php echo json_encode($dataHour); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-day", dataDay, chartPreferences['day'] || 'bar');
                    redrawChart("chart-week", dataWeek, chartPreferences['week'] || 'bar');
                    redrawChart("chart-month", dataMonth, chartPreferences['month'] || 'bar');
                    redrawChart("chart-hour", dataHour, chartPreferences['hour'] || 'bar');
                });
            </script>
        <?php elseif ($section === "resolutions"): ?>
            <div class="chart-section">
                <h3>Resoluciones de Pantalla</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-resolutions', dataResolutions, 'bar'); updateChartPreference('resolutions', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-resolutions', dataResolutions, 'line'); updateChartPreference('resolutions', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-resolutions', dataResolutions, 'pie'); updateChartPreference('resolutions', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-resolutions', dataResolutions, 'horizontal'); updateChartPreference('resolutions', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-day', dataResolutions, 'table'); updateChartPreference('resolutions', 'table');">Tabla</button>

                </div>
                <div id="chart-resolutions" class="chart-container"></div>
            </div>
            <script>
                var dataResolutions = <?php echo json_encode($dataResolutions); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-resolutions", dataResolutions, chartPreferences['resolutions'] || 'bar');
                });
            </script>
        <?php elseif ($section === "os"): ?>
            <div class="chart-section">
                <h3>Sistemas Operativos</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-os', dataOS, 'bar'); updateChartPreference('os', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-os', dataOS, 'line'); updateChartPreference('os', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-os', dataOS, 'pie'); updateChartPreference('os', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-os', dataOS, 'horizontal'); updateChartPreference('os', 'horizontal');">Horizontal</button>
               	    <button onclick="redrawChart('chart-day', dataOS, 'table'); updateChartPreference('os', 'table');">Tabla</button>

                </div>
                <div id="chart-os" class="chart-container"></div>
            </div>
            <script>
                var dataOS = <?php echo json_encode($dataOS); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-os", dataOS, chartPreferences['os'] || 'bar');
                });
            </script>
        <?php elseif ($section === "browsers"): ?>
            <div class="chart-section">
                <h3>Navegadores</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-browsers', dataBrowsers, 'bar'); updateChartPreference('browsers', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-browsers', dataBrowsers, 'line'); updateChartPreference('browsers', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-browsers', dataBrowsers, 'pie'); updateChartPreference('browsers', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-browsers', dataBrowsers, 'horizontal'); updateChartPreference('browsers', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-day', dataBrowsers, 'table'); updateChartPreference('browsers', 'table');">Tabla</button>

                </div>
                <div id="chart-browsers" class="chart-container"></div>
            </div>
            <script>
                var dataBrowsers = <?php echo json_encode($dataBrowsers); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-browsers", dataBrowsers, chartPreferences['browsers'] || 'bar');
                });
            </script>
        <?php elseif ($section === "languages"): ?>
            <div class="chart-section">
                <h3>Idiomas</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-languages', dataLanguages, 'bar'); updateChartPreference('languages', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-languages', dataLanguages, 'line'); updateChartPreference('languages', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-languages', dataLanguages, 'pie'); updateChartPreference('languages', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-languages', dataLanguages, 'horizontal'); updateChartPreference('languages', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-day', dataLanguages, 'table'); updateChartPreference('languages', 'table');">Tabla</button>

                </div>
                <div id="chart-languages" class="chart-container"></div>
            </div>
            <script>
                var dataLanguages = <?php echo json_encode($dataLanguages); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-languages", dataLanguages, chartPreferences['languages'] || 'bar');
                });
            </script>
        <?php elseif ($section === "timezones"): ?>
            <div class="chart-section">
                <h3>Zonas Horarias</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-timezones', dataTimezones, 'bar'); updateChartPreference('timezones', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-timezones', dataTimezones, 'line'); updateChartPreference('timezones', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-timezones', dataTimezones, 'pie'); updateChartPreference('timezones', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-timezones', dataTimezones, 'horizontal'); updateChartPreference('timezones', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-day', dataTimezones, 'table'); updateChartPreference('timezones', 'table');">Tabla</button>

                </div>
                <div id="chart-timezones" class="chart-container"></div>
            </div>
            <script>
                var dataTimezones = <?php echo json_encode($dataTimezones); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-timezones", dataTimezones, chartPreferences['timezones'] || 'bar');
                });
            </script>
        <?php elseif ($section === "color_depth"): ?>
            <div class="chart-section">
                <h3>Profundidad de Color</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-color-depth', dataColorDepth, 'bar'); updateChartPreference('color_depth', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-color-depth', dataColorDepth, 'line'); updateChartPreference('color_depth', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-color-depth', dataColorDepth, 'pie'); updateChartPreference('color_depth', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-color-depth', dataColorDepth, 'horizontal'); updateChartPreference('color_depth', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-day', dataColorDepth, 'table'); updateChartPreference('color_depth', 'table');">Tabla</button>

                </div>
                <div id="chart-color-depth" class="chart-container"></div>
            </div>
            <script>
                var dataColorDepth = <?php echo json_encode($dataColorDepth); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-color-depth", dataColorDepth, chartPreferences['color_depth'] || 'bar');
                });
            </script>
        <?php elseif ($section === "urls"): ?>
            <div class="chart-section">
                <h3>URLs Visitadas</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-urls', dataUrls, 'bar'); updateChartPreference('urls', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-urls', dataUrls, 'line'); updateChartPreference('urls', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-urls', dataUrls, 'pie'); updateChartPreference('urls', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-urls', dataUrls, 'horizontal'); updateChartPreference('urls', 'horizontal');">Horizontal</button>
               	    <button onclick="redrawChart('chart-day', dataUrls, 'table'); updateChartPreference('urls', 'table');">Tabla</button>

                </div>
                <div id="chart-urls" class="chart-container"></div>
            </div>
            <script>
                var dataUrls = <?php echo json_encode($dataUrls); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-urls", dataUrls, chartPreferences['urls'] || 'bar');
                });
            </script>
        <?php elseif ($section === "ips"): ?>
            <div class="chart-section">
                <h3>IPs</h3>
                <div class="chart-controls">
                    <button onclick="redrawChart('chart-ips', dataIps, 'bar'); updateChartPreference('ips', 'bar');">Barra</button>
                    <button onclick="redrawChart('chart-ips', dataIps, 'line'); updateChartPreference('ips', 'line');">Línea</button>
                    <button onclick="redrawChart('chart-ips', dataIps, 'pie'); updateChartPreference('ips', 'pie');">Pastel</button>
                    <button onclick="redrawChart('chart-ips', dataIps, 'horizontal'); updateChartPreference('ips', 'horizontal');">Horizontal</button>
                	    <button onclick="redrawChart('chart-day', dataIps, 'table'); updateChartPreference('ips', 'table');">Tabla</button>

                </div>
                <div id="chart-ips" class="chart-container"></div>
            </div>
            <script>
                var dataIps = <?php echo json_encode($dataIps); ?>;
                document.addEventListener("DOMContentLoaded", function(){
                    redrawChart("chart-ips", dataIps, chartPreferences['ips'] || 'bar');
                });
            </script>
        <?php elseif ($section === "raw"): ?>
            <h3>Datos Sin Procesar de Analítica</h3>
            <?php
              $q = "SELECT * FROM logs WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
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
                <th>Usuario</th>
                <th>Agente de Usuario</th>
                <th>Pantalla (WxH)</th>
                <th>Ventana Gráfica (WxH)</th>
                <th>Idioma</th>
                <th>Idiomas</th>
                <th>Desfase de Zona Horaria</th>
                <th>Plataforma</th>
                <th>Conexión</th>
                <th>Profundidad de Color</th>
                <th>URL</th>
                <th>Referente</th>
                <th>Marca de Tiempo</th>
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
        
        <?php elseif ($section === "calendar"): ?>
			 <div class="chart-section calendar-container">
				  <h3>Calendario Mensual de Visitas</h3>
				  <?php
				  // Query the first and last visit dates based on the current accounts clause.
				  $q = "SELECT MIN(date(timestamp)) as first_date, MAX(date(timestamp)) as last_date FROM logs WHERE 1 $accountsClause";
				  $stmtMinMax = $db->query($q);
				  $minmax = $stmtMinMax->fetchArray(SQLITE3_ASSOC);
				  $first_date = $minmax['first_date'];
				  $last_date = $minmax['last_date'];

				  // Build an associative array with visits per day.
				  $visitsPerDay = [];
				  $q2 = "SELECT date(timestamp) as day, COUNT(*) as visits FROM logs WHERE 1 $accountsClause GROUP BY day";
				  $result2 = $db->query($q2);
				  while ($row = $result2->fetchArray(SQLITE3_ASSOC)) {
				      $visitsPerDay[$row['day']] = $row['visits'];
				  }

				  if (!$first_date || !$last_date) {
				      echo "<p>No hay registros de visitas.</p>";
				  } else {
				      // Initialize DateTime objects.
				      $start = new DateTime($first_date);
				      // Start at the first day of the month for the earliest access.
				      $start->modify('first day of this month');
				      $end = new DateTime($last_date);
				      // End after the last month (to include the complete final month).
				      $end->modify('first day of next month');

				      // Loop month by month.
				      $current = clone $start;
				      while ($current < $end) {
				          $year = $current->format('Y');
				          $monthName = $current->format('F');
				          $daysInMonth = $current->format('t');
				          // Determine the day of week the first day falls on (1=Monday, 7=Sunday).
				          $firstDayOfWeek = (int)$current->format('N');

				          echo "<h4>$monthName $year</h4>";
				          echo "<table border='1' cellpadding='5' cellspacing='0'  class='calendar-table'>";
				          // Table header with abbreviated day names.
				          echo "<tr>";
				          $daysOfWeek = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
				          foreach ($daysOfWeek as $dayName) {
				              echo "<th>$dayName</th>";
				          }
				          echo "</tr><tr>";

				          // Fill in empty cells until the first day of the month.
				          for ($i = 1; $i < $firstDayOfWeek; $i++) {
				              echo "<td>&nbsp;</td>";
				          }

				          // Print each day of the month.
				          for ($day = 1; $day <= $daysInMonth; $day++) {
				              // Build a date string in Y-m-d format.
				              $currentDateStr = $current->format('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
				              echo "<td>";
				              echo "<strong>$day</strong><br>";
				              if (isset($visitsPerDay[$currentDateStr])) {
				                  echo $visitsPerDay[$currentDateStr] . " visitas";
				              }
				              echo "</td>";

				              // Determine current cell index (starting from 1).
				              $cellIndex = $firstDayOfWeek + $day - 1;
				              // If the cell is the last in a week, end the row.
				              if ($cellIndex % 7 == 0 && $day != $daysInMonth) {
				                  echo "</tr><tr>";
				              }
				          }

				          // Fill remaining cells in the last row if needed.
				          $lastCellIndex = $firstDayOfWeek + $daysInMonth - 1;
				          $remaining = 7 - ($lastCellIndex % 7);
				          if ($remaining < 7) {
				              for ($i = 0; $i < $remaining; $i++) {
				                  echo "<td>&nbsp;</td>";
				              }
				          }
				          echo "</tr>";
				          echo "</table><br>";

				          // Move to the next month.
				          $current->modify('first day of next month');
				      }
				  }
				  ?>
			 </div>
			 <?php elseif ($section === "heatmap"): ?>
<div class="chart-section">
    <h3>Calendario de Diagrama de Semanas (Heatmap)</h3>
    <?php
    // Query daily visit counts applying the current accounts restrictions.
    $visitsPerDay = [];
    $qHeat = "SELECT date(timestamp) as day, COUNT(*) as visits FROM logs WHERE 1 $accountsClause GROUP BY day";
    $resHeat = $db->query($qHeat);
    while ($row = $resHeat->fetchArray(SQLITE3_ASSOC)) {
        $visitsPerDay[$row['day']] = $row['visits'];
    }
    
    // For this heatmap, we show data for the current year.
    $year = date('Y');
    $startDate = new DateTime("$year-01-01");
    $endDate = new DateTime("$year-12-31");
    
    // Build a grid based on ISO weeks.
    // Iterate day by day to build an array keyed by week number and day-of-week (1 = Monday, 7 = Sunday).
    $heatmap = [];
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        $week = $currentDate->format("W"); // ISO week number
        $dayOfWeek = $currentDate->format("N"); // 1 (Monday) to 7 (Sunday)
        $dateStr = $currentDate->format("Y-m-d");
        $visits = isset($visitsPerDay[$dateStr]) ? $visitsPerDay[$dateStr] : 0;
        $heatmap[$week][$dayOfWeek] = ['date' => $dateStr, 'visits' => $visits];
        $currentDate->modify('+1 day');
    }
    
    // Determine the maximum number of visits in a day (for color scaling).
    $maxVisits = 0;
    foreach ($visitsPerDay as $count) {
        if ($count > $maxVisits) {
            $maxVisits = $count;
        }
    }
    
    // Helper function: Returns a background color based on the visit count.
    function getHeatmapColor($visits, $maxVisits) {
        if ($visits == 0) {
            return "#ebedf0";
        }
        $ratio = $visits / ($maxVisits ? $maxVisits : 1);
        if ($ratio < 0.25) {
            return "#c6e48b";
        } elseif ($ratio < 0.5) {
            return "#7bc96f";
        } elseif ($ratio < 0.75) {
            return "#239a3b";
        } else {
            return "#196127";
        }
    }
    
    // Get sorted week numbers.
    $weeks = array_keys($heatmap);
    sort($weeks);
    
    // Build an array of month names for each week.
    $monthHeaders = [];
    foreach ($weeks as $wk) {
        $dt = new DateTime();
        $dt->setISODate($year, intval($wk));
        $monthHeaders[] = $dt->format("M");
    }
    
    // Group consecutive weeks that have the same month.
    $groups = [];
    $prev = null;
    $count = 0;
    foreach ($monthHeaders as $i => $month) {
        if ($month === $prev) {
            $count++;
        } else {
            if ($prev !== null) {
                $groups[] = ['month' => $prev, 'colspan' => $count];
            }
            $prev = $month;
            $count = 1;
        }
    }
    if ($prev !== null) {
        $groups[] = ['month' => $prev, 'colspan' => $count];
    }
    ?>
    <!-- Display the Year -->
    <div class="heatmap-year">
        <strong>Año <?php echo $year; ?></strong>
    </div>
    <div class="heatmap-container">
        <table class="heatmap-table">
            <!-- Month Header Row -->
            <tr>
                <th></th>
                <?php
                foreach ($groups as $group) {
                    echo "<th colspan='" . $group['colspan'] . "'>" . $group['month'] . "</th>";
                }
                ?>
            </tr>
            <!-- Week Numbers Header Row -->
            <tr>
                <th></th>
                <?php foreach ($weeks as $wk) { echo "<th>$wk</th>"; } ?>
            </tr>
            <?php
            // Days of week: 1 = Monday, …, 7 = Sunday.
            $dayNames = [1 => "Lun", 2 => "Mar", 3 => "Mié", 4 => "Jue", 5 => "Vie", 6 => "Sáb", 7 => "Dom"];
            for ($day = 1; $day <= 7; $day++) {
                echo "<tr>";
                // Row header: abbreviated day name.
                echo "<th>" . $dayNames[$day] . "</th>";
                foreach ($weeks as $wk) {
                    if (isset($heatmap[$wk][$day])) {
                        $cell = $heatmap[$wk][$day];
                        $color = getHeatmapColor($cell['visits'], $maxVisits);
                        $tooltip = $cell['date'] . ": " . $cell['visits'] . " visitas";
                        echo "<td style='background-color: $color;' title='$tooltip'></td>";
                    } else {
                        echo "<td></td>";
                    }
                }
                echo "</tr>";
            }
            ?>
        </table>
    </div>
</div>
<?php elseif ($section === "robots"): ?>
    <?php
    // Define a condition for bot user agents.
    // Adjust keywords as needed.
    $botCondition = "user_agent LIKE '%bot%' OR user_agent LIKE '%spider%' OR user_agent LIKE '%crawl%' OR user_agent LIKE '%slurp%'";

    // Get overall counts.
    $queryBots = "SELECT COUNT(*) as count FROM logs WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause . " AND ($botCondition)";
    $stmtBots = $db->prepare($queryBots);
    $stmtBots->bindValue(':startDate', $startDate, SQLITE3_TEXT);
    $stmtBots->bindValue(':endDate', $endDate, SQLITE3_TEXT);
    $resultBots = $stmtBots->execute();
    $botsOverall = $resultBots->fetchArray(SQLITE3_ASSOC)['count'];

    $queryTotal = "SELECT COUNT(*) as count FROM logs WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause;
    $stmtTotal = $db->prepare($queryTotal);
    $stmtTotal->bindValue(':startDate', $startDate, SQLITE3_TEXT);
    $stmtTotal->bindValue(':endDate', $endDate, SQLITE3_TEXT);
    $resultTotal = $stmtTotal->execute();
    $totalOverall = $resultTotal->fetchArray(SQLITE3_ASSOC)['count'];

    $humansOverall = $totalOverall - $botsOverall;

    // Daily breakdown: one row per day with counts for bots and humans.
    $dailyData = [];
    $qDaily = "SELECT date(timestamp) as label,
         SUM(CASE WHEN ($botCondition) THEN 1 ELSE 0 END) as bot_visits,
         SUM(CASE WHEN NOT ($botCondition) THEN 1 ELSE 0 END) as human_visits
         FROM logs
         WHERE date(timestamp) BETWEEN :startDate AND :endDate" . $accountsClause . "
         GROUP BY label
         ORDER BY label";
    $stmtDaily = $db->prepare($qDaily);
    $stmtDaily->bindValue(':startDate', $startDate, SQLITE3_TEXT);
    $stmtDaily->bindValue(':endDate', $endDate, SQLITE3_TEXT);
    $resDaily = $stmtDaily->execute();
    while ($row = $resDaily->fetchArray(SQLITE3_ASSOC)) {
        $dailyData[] = $row;
    }
    ?>
    <h2>Robots vs Humanos</h2>
    <p><strong>Total Robots:</strong> <?php echo $botsOverall; ?></p>
    <p><strong>Total Humanos:</strong> <?php echo $humansOverall; ?></p>

    <!-- Total Aggregated Chart -->
    <h3>Total Robots vs Humanos</h3>
    <div class="chart-section">
      <div class="chart-controls">
          <button onclick="redrawChart('chart-total', dataTotal, 'bar'); updateChartPreference('total', 'bar');">Barra</button>
          <button onclick="redrawChart('chart-total', dataTotal, 'line'); updateChartPreference('total', 'line');">Línea</button>
          <button onclick="redrawChart('chart-total', dataTotal, 'pie'); updateChartPreference('total', 'pie');">Pastel</button>
          <button onclick="redrawChart('chart-total', dataTotal, 'horizontal'); updateChartPreference('total', 'horizontal');">Horizontal</button>
          <button onclick="redrawChart('chart-total', dataTotal, 'table'); updateChartPreference('total', 'table');">Tabla</button>
      </div>
      <div id="chart-total" class="chart-container"></div>
    </div>

    <!-- Daily Breakdown Charts -->
    <h3>Desglose Diario</h3>
    <!-- Chart for daily robot visits -->
    <div class="chart-section">
      <h4>Robots Diarios</h4>
      <div class="chart-controls">
          <button onclick="redrawChart('chart-robots', dataRobots, 'bar'); updateChartPreference('robots', 'bar');">Barra</button>
          <button onclick="redrawChart('chart-robots', dataRobots, 'line'); updateChartPreference('robots', 'line');">Línea</button>
          <button onclick="redrawChart('chart-robots', dataRobots, 'pie'); updateChartPreference('robots', 'pie');">Pastel</button>
          <button onclick="redrawChart('chart-robots', dataRobots, 'horizontal'); updateChartPreference('robots', 'horizontal');">Horizontal</button>
          <button onclick="redrawChart('chart-robots', dataRobots, 'table'); updateChartPreference('robots', 'table');">Tabla</button>
      </div>
      <div id="chart-robots" class="chart-container"></div>
    </div>

    <!-- Chart for daily human visits -->
    <div class="chart-section">
      <h4>Humanos Diarios</h4>
      <div class="chart-controls">
          <button onclick="redrawChart('chart-humans', dataHumans, 'bar'); updateChartPreference('humans', 'bar');">Barra</button>
          <button onclick="redrawChart('chart-humans', dataHumans, 'line'); updateChartPreference('humans', 'line');">Línea</button>
          <button onclick="redrawChart('chart-humans', dataHumans, 'pie'); updateChartPreference('humans', 'pie');">Pastel</button>
          <button onclick="redrawChart('chart-humans', dataHumans, 'horizontal'); updateChartPreference('humans', 'horizontal');">Horizontal</button>
          <button onclick="redrawChart('chart-humans', dataHumans, 'table'); updateChartPreference('humans', 'table');">Tabla</button>
      </div>
      <div id="chart-humans" class="chart-container"></div>
    </div>

    <script>
      // Prepare arrays for the aggregated and daily data.
      var dataTotal = [
          { label: "Robots", visits: <?php echo $botsOverall; ?> },
          { label: "Humanos", visits: <?php echo $humansOverall; ?> }
      ];
      var dataRobots = [];
      var dataHumans = [];
      <?php foreach($dailyData as $d): ?>
         dataRobots.push({ label: "<?php echo $d['label']; ?>", visits: <?php echo $d['bot_visits']; ?> });
         dataHumans.push({ label: "<?php echo $d['label']; ?>", visits: <?php echo $d['human_visits']; ?> });
      <?php endforeach; ?>

      document.addEventListener("DOMContentLoaded", function(){
          // Render the overall total chart using the user's saved preference (default to 'pie').
          redrawChart("chart-total", dataTotal, chartPreferences['total'] || 'pie');
          // Render the daily breakdown charts.
          redrawChart("chart-robots", dataRobots, chartPreferences['robots'] || 'bar');
          redrawChart("chart-humans", dataHumans, chartPreferences['humans'] || 'bar');
      });
    </script>
<?php endif; ?>



    </div>
</section>

      </main>
    </div>
  </div>
  <!-- Include the chart library -->
  <script src="charts.js"></script>
<?php endif; ?>
</body>
</html>

