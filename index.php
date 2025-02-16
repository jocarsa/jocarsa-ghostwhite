<?php
// admin.php

// HTTP Basic Authentication (username: jocarsa, password: jocarsa)
if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== 'jocarsa' || 
    $_SERVER['PHP_AUTH_PW'] !== 'jocarsa') {
    
    header('WWW-Authenticate: Basic realm="Jocarsa Analytics Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Jocarsa Analytics - Admin Panel</title>
  <style>
    body { 
      font-family: Arial, sans-serif; 
      margin: 20px; 
      background: ghostwhite; 
    }
    table { 
      border-collapse: collapse; 
      width: 100%; 
      font-size: 12px;
    }
    th, td { 
      border: 1px solid #ccc; 
      padding: 4px; 
      text-align: left; 
    }
    th { 
      background: #f0f0f0; 
    }
    h1 { color: #333; }
  </style>
</head>
<body>
  <h1>Jocarsa Analytics - Admin Panel</h1>
  <?php
  try {
      // Connect to the SQLite database
      $db = new SQLite3('analytics.db');
      $result = $db->query("SELECT * FROM logs ORDER BY id DESC");

      echo "<table>";
      echo "<tr>
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
            </tr>";

      while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
          echo "<tr>
                  <td>" . htmlspecialchars($row['id']) . "</td>
                  <td>" . htmlspecialchars($row['user']) . "</td>
                  <td>" . htmlspecialchars($row['user_agent']) . "</td>
                  <td>" . htmlspecialchars($row['screen_width']) . " x " . htmlspecialchars($row['screen_height']) . "</td>
                  <td>" . htmlspecialchars($row['viewport_width']) . " x " . htmlspecialchars($row['viewport_height']) . "</td>
                  <td>" . htmlspecialchars($row['language']) . "</td>
                  <td>" . htmlspecialchars($row['languages']) . "</td>
                  <td>" . htmlspecialchars($row['timezone_offset']) . "</td>
                  <td>" . htmlspecialchars($row['platform']) . "</td>
                  <td>" . htmlspecialchars($row['connection_type']) . "</td>
                  <td>" . htmlspecialchars($row['screen_color_depth']) . "</td>
                  <td>" . htmlspecialchars($row['url']) . "</td>
                  <td>" . htmlspecialchars($row['referrer']) . "</td>
                  <td>" . htmlspecialchars($row['timestamp']) . "</td>
                  <td>" . htmlspecialchars($row['performance_timing']) . "</td>
                  <td>" . htmlspecialchars($row['ip']) . "</td>
                </tr>";
      }
      echo "</table>";
  } catch (Exception $e) {
      echo "Error: " . $e->getMessage();
  }
  ?>
</body>
</html>

