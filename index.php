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

// Create a table to store the chart type preference for each chart.
// Note: chart_id is marked UNIQUE so that we can use INSERT OR REPLACE.
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
    $dataTimezones = [];
    $dataColorDepth = [];
    $dataUrls = [];
    $dataIps = [];

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
    } elseif ($section === "timezones") {
        $q = "SELECT timezone_offset as label, count(*) as visits
              FROM logs
              WHERE date(timestamp) BETWEEN :startDate AND :endDate";
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
              WHERE date(timestamp) BETWEEN :startDate AND :endDate";
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
              WHERE date(timestamp) BETWEEN :startDate AND :endDate";
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
              WHERE date(timestamp) BETWEEN :startDate AND :endDate";
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
  <!-- FORMULARIO DE INICIO DE SESIÓN -->
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
    <!-- ENCABEZADO -->
    <header>
      <img src="ghostwhite.png" alt="Logo">
      <h1>jocarsa | ghostwhite</h1>
    </header>
    <div id="main-container">
      <!-- BARRA LATERAL -->
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
      <!-- CONTENIDO PRINCIPAL -->
      <main id="content">
        <!-- SUB-Pestañas -->
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
        </div>
        <!-- FILTRO DE FECHA -->
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
        <!-- Variables y funciones JavaScript globales -->
        <script>
          // chartPreferences contiene cualquier preferencia guardada por gráfico.
          // Si la preferencia de un gráfico no está establecida, se usa 'bar' por defecto.
          var chartPreferences = <?php echo json_encode($chartPrefs); ?>;
          function updateChartPreference(chartId, type) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_chart_preference.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.send("chart_id=" + encodeURIComponent(chartId) + "&chart_type=" + encodeURIComponent(type));
          }
        </script>
        <!-- CONTENIDO DE ESTADÍSTICAS -->
        <section class="stats">
          <?php if ($section === "overview"): ?>
            <div class="chart-section">
              <h3>Visitas Por Día</h3>
              <div class="chart-controls">
                <button onclick="redrawChart('chart-day', dataDay, 'bar'); updateChartPreference('day', 'bar');">Barra</button>
                <button onclick="redrawChart('chart-day', dataDay, 'line'); updateChartPreference('day', 'line');">Línea</button>
                <button onclick="redrawChart('chart-day', dataDay, 'pie'); updateChartPreference('day', 'pie');">Pastel</button>
                <button onclick="redrawChart('chart-day', dataDay, 'horizontal'); updateChartPreference('day', 'horizontal');">Horizontal</button>
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
          <?php endif; ?>
        </section>
      </main>
    </div>
  </div>
  <!-- Incluir la librería de gráficos -->
  <script src="charts.js"></script>
<?php endif; ?>
</body>
</html>

