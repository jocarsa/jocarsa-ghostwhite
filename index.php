<?php
session_start();

include "inc/inicializarbasededatos.php";
include "inc/cerrarsesion.php";

$message = '';

include "inc/procesarlogin.php";

if (isset($_SESSION['username'])) {
    // Get filters from GET parameters.
    $filterUser = isset($_GET['filter_user']) ? $_GET['filter_user'] : '';
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-2 weeks'));
    $endDate   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');
    // Set default section to "dashboard" instead of "overview"
    $section   = isset($_GET['section'])    ? $_GET['section']    : 'dashboard';

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

    // Prepare data arrays for statistics (for sections other than dashboard).
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

    // (Other section‑specific queries occur in the included files below.)
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>jocarsa | ghostwhite</title>
  <link rel="stylesheet" href="admin/admin.css">
  <link rel="icon" type="image/svg+xml" href="ghostwhite.png" />
  <!-- Load charts.js in the head so its functions are available to all inline scripts -->
  <script src="charts.js"></script>
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
      <!-- SIDEBAR (Usuarios nav with Dashboard link) -->
      <nav id="sidebar">
        <h3>Usuarios</h3>
        <ul>
          <!-- Add Dashboard link here -->
          <li>
            <a href="index.php?section=dashboard&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="dashboard") echo 'class="active"'; ?>>Dashboard</a>
          </li>
          <li>
            <a href="index.php?start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>">Todos los usuarios</a>
          </li>
          <?php foreach ($users as $usr): ?>
            <li>
              <a href="index.php?filter_user=<?php echo urlencode($usr); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>">
                <?php echo htmlspecialchars($usr); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="logout">
          <a href="index.php?action=logout">Cerrar sesión (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        </div>
      </nav>
      <!-- SUB NAVIGATION (Secciones except Dashboard) -->
      <nav id="sub-nav">
        <h3>Secciones</h3>
        <ul>
          <li>
            <a href="index.php?section=overview&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="overview") echo 'class="active"'; ?>>Resumen</a>
          </li>
          <li><a href="index.php?section=resolutions&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="resolutions") echo 'class="active"'; ?>>Resoluciones</a></li>
          <li><a href="index.php?section=os&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="os") echo 'class="active"'; ?>>Sistemas Operativos</a></li>
          <li><a href="index.php?section=browsers&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="browsers") echo 'class="active"'; ?>>Navegadores</a></li>
          <li><a href="index.php?section=languages&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="languages") echo 'class="active"'; ?>>Idiomas</a></li>
          <li><a href="index.php?section=timezones&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="timezones") echo 'class="active"'; ?>>Zonas Horarias</a></li>
          <li><a href="index.php?section=color_depth&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="color_depth") echo 'class="active"'; ?>>Profundidad de Color</a></li>
          <li><a href="index.php?section=urls&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="urls") echo 'class="active"'; ?>>URLs Visitadas</a></li>
          <li><a href="index.php?section=ips&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="ips") echo 'class="active"'; ?>>IPs</a></li>
          <li><a href="index.php?section=raw&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="raw") echo 'class="active"'; ?>>Datos Sin Procesar</a></li>
          <li><a href="index.php?section=calendar&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="calendar") echo 'class="active"'; ?>>Calendario</a></li>
          <li><a href="index.php?section=heatmap&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="heatmap") echo 'class="active"'; ?>>Heatmap</a></li>
          <li><a href="index.php?section=robots&amp;filter_user=<?php echo urlencode($filterUser); ?>&amp;start_date=<?php echo urlencode($startDate); ?>&amp;end_date=<?php echo urlencode($endDate); ?>" <?php if($section=="robots") echo 'class="active"'; ?>>Robots vs Humanos</a></li>
        </ul>
      </nav>
      <!-- MAIN CONTENT -->
      <main id="content">
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
            <?php
              if ($section === "dashboard") {
                  include "inc/secciones/dashboard.php";
              } elseif ($section === "overview") {
              		include "inc/secciones/vistazo.php";
                  include "partes/vistazo.php";
              } elseif ($section === "resolutions") {
              		include "inc/secciones/resoluciones.php";
                  include "partes/resoluciones.php";
              } elseif ($section === "os") {
              		include "inc/secciones/os.php";
                  include "partes/os.php";
              } elseif ($section === "browsers") {
              		include "inc/secciones/navegadores.php";
                  include "partes/navegadores.php";
              } elseif ($section === "languages") {
              		include "inc/secciones/idiomas.php";
                  include "partes/idiomas.php";
              } elseif ($section === "timezones") {
              		include "inc/secciones/timezones.php";
                  include "partes/timezones.php";
              } elseif ($section === "color_depth") {
              		include "inc/secciones/color.php";
                  include "partes/colores.php";
              } elseif ($section === "urls") {
              		include "inc/secciones/urls.php";
                  include "partes/urls.php";
              } elseif ($section === "ips") {
              		include "inc/secciones/ips.php";
                  include "partes/ips.php";
              } elseif ($section === "raw") {
              		
                  include "partes/crudo.php";
              } elseif ($section === "calendar") {
              		
                  include "partes/calendario.php";
              } elseif ($section === "heatmap") {
              		
                  include "partes/mapadecalor.php";
              } elseif ($section === "robots") {
              		
                  include "partes/robots.php";
              }
            ?>
          </div>
        </section>
      </main>
    </div>
  </div>
<?php endif; ?>
</body>
</html>

