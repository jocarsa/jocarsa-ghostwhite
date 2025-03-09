<?php
session_start();

// ----- Handle Logout -----
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// ----- Process Admin Login -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    // Only accept hard-coded admin credentials.
    if ($username === 'jocarsa' && $password === 'jocarsa') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['username'] = $username;
        header("Location: admin.php");
        exit;
    } else {
        $loginError = "Nombre de usuario o contraseña incorrectos.";
    }
}

// If not logged in, show login form.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true):
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Admin Login | jocarsa</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <div class="login-container">
    <img src="ghostwhite.png" alt="Logo">
    <h2>Admin Login</h2>
    <?php if (isset($loginError)): ?>
      <p class="error"><?php echo htmlspecialchars($loginError); ?></p>
    <?php endif; ?>
    <form method="post" action="admin.php">
      <div class="form-group">
        <label for="username">Nombre de usuario:</label>
        <input type="text" name="username" id="username" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Contraseña:</label>
        <input type="password" name="password" id="password" required>
      </div>
      <div class="form-group">
        <button type="submit" name="admin_login">Iniciar sesión</button>
      </div>
    </form>
  </div>
</body>
</html>
<?php
exit;
endif;

// ----- Admin is Logged In; Set up Database Connection -----
$db = new SQLite3('../../databases/ghostwhite.db');

// Create the new table to map registered users to log accounts.
$db->exec("CREATE TABLE IF NOT EXISTS user_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user TEXT,
    account TEXT,
    UNIQUE(user, account)
)");

// ----- Determine Which Section to Display -----
// 'section' can be "assign" (Asignar cuenta) or "users" (CRUD de usuarios)
$section = isset($_GET['section']) ? $_GET['section'] : 'assign';

// ----- Process Assignment Form Submission -----
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $assignedUser = trim($_POST['assigned_user'] ?? '');
    $accountUser  = trim($_POST['account_user'] ?? '');
    if ($assignedUser && $accountUser) {
        $stmt = $db->prepare("INSERT OR IGNORE INTO user_accounts (user, account) VALUES (:user, :account)");
        $stmt->bindValue(':user', $assignedUser, SQLITE3_TEXT);
        $stmt->bindValue(':account', $accountUser, SQLITE3_TEXT);
        $stmt->execute();
        $message = "Asignación agregada correctamente.";
    } else {
        $message = "Por favor seleccione un usuario y una cuenta.";
    }
}

// ----- Process Assignment Deletion -----
if (isset($_GET['action']) && $_GET['action'] === 'delete_assign' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("DELETE FROM user_accounts WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    $message = "Asignación eliminada.";
}

// ----- Process CRUD for Users -----
// Adding a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $newUser = trim($_POST['new_username'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    if ($newUser && $newPass) {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT OR IGNORE INTO users (username, password) VALUES (:username, :password)");
        $stmt->bindValue(':username', $newUser, SQLITE3_TEXT);
        $stmt->bindValue(':password', $hashed, SQLITE3_TEXT);
        $stmt->execute();
        $message = "Usuario '$newUser' agregado.";
    } else {
        $message = "Por favor ingrese un nombre de usuario y contraseña.";
    }
}
// Deleting a user
if (isset($_GET['action']) && $_GET['action'] === 'delete_user' && isset($_GET['uid'])) {
    $uid = intval($_GET['uid']);
    // (Optional: Prevent deletion of the main admin user)
    $stmt = $db->prepare("DELETE FROM users WHERE id = :uid");
    $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
    $stmt->execute();
    $message = "Usuario eliminado.";
}
// Updating a user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $uid       = intval($_POST['uid'] ?? 0);
    $newUser   = trim($_POST['edit_username'] ?? '');
    $newPass   = $_POST['edit_password'] ?? '';
    if ($uid && $newUser) {
        // Only update password if provided
        if ($newPass) {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username = :username, password = :password WHERE id = :uid");
            $stmt->bindValue(':password', $hashed, SQLITE3_TEXT);
        } else {
            $stmt = $db->prepare("UPDATE users SET username = :username WHERE id = :uid");
        }
        $stmt->bindValue(':username', $newUser, SQLITE3_TEXT);
        $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $stmt->execute();
        $message = "Usuario actualizado.";
    } else {
        $message = "Faltan datos para actualizar.";
    }
}

// ----- Fetch Data for the Two Sections -----
if ($section === 'assign') {
    // Fetch current assignments
    $assignments = [];
    $res = $db->query("SELECT * FROM user_accounts ORDER BY id DESC");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $assignments[] = $row;
    }
    // Get list of registered users
    $usersList = [];
    $resUsers = $db->query("SELECT username FROM users ORDER BY username");
    while ($row = $resUsers->fetchArray(SQLITE3_ASSOC)) {
        $usersList[] = $row['username'];
    }
    // Get distinct log accounts from logs table
    $accountsList = [];
    $resAccounts = $db->query("SELECT DISTINCT user as account FROM logs WHERE user <> '' ORDER BY account");
    while ($row = $resAccounts->fetchArray(SQLITE3_ASSOC)) {
        $accountsList[] = $row['account'];
    }
} elseif ($section === 'users') {
    // Fetch all users for CRUD operations
    $usersData = [];
    $resUsers = $db->query("SELECT * FROM users ORDER BY username");
    while ($row = $resUsers->fetchArray(SQLITE3_ASSOC)) {
        $usersData[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Admin Panel | jocarsa</title>
  <link rel="stylesheet" href="admin.css">
  <link rel="icon" type="image/svg+xml" href="ghostwhite.png" />
  <style>
    /* Minimal extra styling for the admin navigation pane */
    #admin-wrapper { display: flex; min-height: 100vh; }
    #admin-sidebar { width: 220px; background-color: #333; padding: 20px; box-sizing: border-box; }
    #admin-sidebar h3 { color: ghostwhite; margin-top: 0; }
    #admin-sidebar ul { list-style: none; padding: 0; }
    #admin-sidebar ul li { margin-bottom: 10px; }
    #admin-sidebar ul li a { color: ghostwhite; text-decoration: none; padding: 8px 12px; display: block; background-color: #444; border-radius: 3px; }
    #admin-sidebar ul li a:hover, #admin-sidebar ul li a.active { background-color: #575757; }
    #admin-content { flex: 1; padding: 20px; box-sizing: border-box; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table, th, td { border: 1px solid #ccc; }
    th, td { padding: 8px; text-align: left; }
    form { margin-top: 20px; }
    .message { color: green; margin-top: 10px; }
  </style>
</head>
<body>
<div id="admin-wrapper">
  <!-- Left Navigation Pane -->
  <nav id="admin-sidebar">
    <h3>Panel de Administración</h3>
    <ul>
      <li><a href="admin.php?section=assign" <?php if($section === 'assign') echo 'class="active"'; ?>>Asignar Cuenta a Usuario</a></li>
      <li><a href="admin.php?section=users" <?php if($section === 'users') echo 'class="active"'; ?>>Gestión de Usuarios</a></li>
    </ul>
    <div class="logout" style="margin-top:20px;">
      <a href="admin.php?action=logout">Cerrar sesión (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
    </div>
  </nav>

  <!-- Main Content Area -->
  <div id="admin-content">
    <?php if ($message): ?>
      <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($section === 'assign'): ?>
      <h2>Asignar Cuenta a Usuario</h2>
      <!-- Form to assign a log account to a registered user -->
      <form method="post" action="admin.php?section=assign">
        <label for="assigned_user">Usuario Registrado:</label>
        <select name="assigned_user" id="assigned_user" required>
          <option value="">-- Seleccione un usuario --</option>
          <?php foreach ($usersList as $user): ?>
            <option value="<?php echo htmlspecialchars($user); ?>"><?php echo htmlspecialchars($user); ?></option>
          <?php endforeach; ?>
        </select>
        <br><br>
        <label for="account_user">Cuenta (logs.user):</label>
        <select name="account_user" id="account_user" required>
          <option value="">-- Seleccione una cuenta --</option>
          <?php foreach ($accountsList as $acc): ?>
            <option value="<?php echo htmlspecialchars($acc); ?>"><?php echo htmlspecialchars($acc); ?></option>
          <?php endforeach; ?>
        </select>
        <br><br>
        <button type="submit" name="assign">Asignar</button>
      </form>

      <!-- List current assignments -->
      <h3>Asignaciones Actuales</h3>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuario Registrado</th>
            <th>Cuenta (logs.user)</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($assignments)): ?>
            <tr><td colspan="4">No hay asignaciones.</td></tr>
          <?php else: ?>
            <?php foreach ($assignments as $assign): ?>
              <tr>
                <td><?php echo htmlspecialchars($assign['id']); ?></td>
                <td><?php echo htmlspecialchars($assign['user']); ?></td>
                <td><?php echo htmlspecialchars($assign['account']); ?></td>
                <td><a href="admin.php?section=assign&amp;action=delete_assign&amp;id=<?php echo intval($assign['id']); ?>" onclick="return confirm('¿Eliminar esta asignación?');">Eliminar</a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

    <?php elseif ($section === 'users'): ?>
      <h2>Gestión de Usuarios</h2>
      <!-- Form to add a new user -->
      <form method="post" action="admin.php?section=users">
        <input type="hidden" name="action" value="add_user">
        <label for="new_username">Nuevo Usuario:</label>
        <input type="text" name="new_username" id="new_username" required>
        <br><br>
        <label for="new_password">Contraseña:</label>
        <input type="password" name="new_password" id="new_password" required>
        <br><br>
        <button type="submit">Agregar Usuario</button>
      </form>

      <!-- List existing users with options to edit or delete -->
      <h3>Usuarios Registrados</h3>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre de Usuario</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($usersData)): ?>
            <tr><td colspan="3">No hay usuarios.</td></tr>
          <?php else: ?>
            <?php foreach ($usersData as $user): ?>
              <tr>
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td>
                  <!-- If this row is being edited, show a form -->
                  <?php if (isset($_GET['edit']) && intval($_GET['edit']) === intval($user['id'])): ?>
                    <form method="post" action="admin.php?section=users&amp;edit=<?php echo intval($user['id']); ?>">
                      <input type="hidden" name="action" value="update_user">
                      <input type="hidden" name="uid" value="<?php echo intval($user['id']); ?>">
                      <input type="text" name="edit_username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                      <input type="password" name="edit_password" placeholder="Nueva contraseña (opcional)">
                      <button type="submit">Guardar</button>
                    </form>
                  <?php else: ?>
                    <?php echo htmlspecialchars($user['username']); ?>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!(isset($_GET['edit']) && intval($_GET['edit']) === intval($user['id']))): ?>
                    <a href="admin.php?section=users&amp;edit=<?php echo intval($user['id']); ?>">Editar</a> |
                    <a href="admin.php?section=users&amp;action=delete_user&amp;uid=<?php echo intval($user['id']); ?>" onclick="return confirm('¿Eliminar este usuario?');">Eliminar</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

    <?php endif; ?>
  </div>
</div>
</body>
</html>

