<?php include '../db_connect.php'; ?>

<?php
// -------------------------
// Server-side logic
// -------------------------
$success = "";
$error = "";

// Delete bus
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM buses WHERE bus_id = :id");
        $stmt->execute([':id' => $delete_id]);
        $success = "🗑️ Bus deleted successfully!";
    } catch (PDOException $e) {
        $error = "❌ Error deleting bus: " . $e->getMessage();
    }
}

// Add / Edit bus
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bus_number = trim($_POST['bus_no'] ?? '');
    $capacity   = (int) ($_POST['capacity'] ?? 0);
    $driver_id  = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
    $route_id   = !empty($_POST['route_id']) ? $_POST['route_id'] : null;

    // If editing
    if (!empty($_POST['bus_id'])) {
        $bus_id = $_POST['bus_id'];
        try {
            $stmt = $conn->prepare("UPDATE buses SET bus_number=:bus_number, capacity=:capacity, driver_id=:driver_id, route_id=:route_id WHERE bus_id=:bus_id");
            $stmt->execute([
                ':bus_number' => $bus_number,
                ':capacity'   => $capacity,
                ':driver_id'  => $driver_id,
                ':route_id'   => $route_id,
                ':bus_id'     => $bus_id
            ]);
            $success = "✏️ Bus updated successfully!";
        } catch (PDOException $e) {
            $error = "❌ Error updating bus: " . $e->getMessage();
        }
    } else { // Add new
        try {
            $stmt = $conn->prepare("INSERT INTO buses (bus_number, capacity, driver_id, route_id) VALUES (:bus_number, :capacity, :driver_id, :route_id)");
            $stmt->execute([
                ':bus_number' => $bus_number,
                ':capacity'   => $capacity,
                ':driver_id'  => $driver_id,
                ':route_id'   => $route_id
            ]);
            $success = "✅ Bus added successfully!";
        } catch (PDOException $e) {
            $error = "❌ Error adding bus: " . $e->getMessage();
        }
    }
}

// Fetch reference data
$drivers = $conn->query("SELECT driver_id, name FROM drivers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$routes  = $conn->query("SELECT route_id, start_point, end_point FROM routes ORDER BY route_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch buses list with joins
$buses = $conn->query("
    SELECT b.bus_id, b.bus_number, b.capacity, d.name AS driver_name, r.start_point, r.end_point 
    FROM buses b
    LEFT JOIN drivers d ON b.driver_id = d.driver_id
    LEFT JOIN routes r ON b.route_id = r.route_id
    ORDER BY b.bus_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// If editing, load bus data
$edit_bus = null;
if (isset($_GET['edit_id'])) {
    $stmt = $conn->prepare("SELECT * FROM buses WHERE bus_id = :id");
    $stmt->execute([':id' => $_GET['edit_id']]);
    $edit_bus = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Manage Buses - Admin Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    /* ----------------------------------------------------
        🔥 GLOBAL STYLES + DARK & LIGHT MODE
    ---------------------------------------------------- */
    :root {
        --bg1: #ffffff;
        --bg2: #f6f7fb;
        --text: #222;
        --card-bg: rgba(255, 255, 255, 0.35);
        --sidebar: rgba(255,255,255,0.4);
        --shadow: rgba(0,0,0,0.15);
        --accent: #ff7b00;
        --muted: #64748b;
    }

    body.dark {
        --bg1: #0d1117;
        --bg2: #161b22;
        --text: #f0f6fc;
        --card-bg: rgba(255,255,255,0.06);
        --sidebar: rgba(255,255,255,0.06);
        --shadow: rgba(255,255,255,0.06);
        --muted: #9aa6b2;
    }

    /* ----------------------------------------------------
        🔥 BASE STYLES
    ---------------------------------------------------- */
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: "Poppins", sans-serif;
        background: var(--bg2);
        color: var(--text);
        transition: background 0.4s, color 0.4s;
    }

    /* ----------------------------------------------------
        🔥 SIDEBAR
    ---------------------------------------------------- */
    .sidebar {
        width: 250px;
        height: 100vh;
        position: fixed;
        background: var(--sidebar);
        backdrop-filter: blur(12px);
        padding: 20px;
        left: 0;
        top: 0;
        box-shadow: 4px 0 15px var(--shadow);
        transition: 0.4s;
        z-index: 30;
    }

    .sidebar h2 {
        font-size: 1.6rem;
        margin-bottom: 20px;
        text-align: center;
    }

    .sidebar a {
        display: block;
        padding: 12px 14px;
        margin: 8px 0;
        text-decoration: none;
        font-size: 15px;
        color: var(--text);
        border-radius: 10px;
        transition: 0.25s;
    }

    .sidebar a i { margin-right: 10px; }
    .sidebar a:hover, .sidebar a.active {
        background: rgba(255,255,255,0.18);
        transform: translateX(6px);
    }

    /* ----------------------------------------------------
        🔥 MAIN CONTENT AREA
    ---------------------------------------------------- */
    .main {
        margin-left: 250px;
        padding: 28px;
        transition: 0.4s;
    }

    /* ----------------------------------------------------
        🔥 HEADER WITH ANIMATED GRADIENT
    ---------------------------------------------------- */
    .header {
        padding: 20px 22px;
        border-radius: 14px;
        background: linear-gradient(135deg, #667eea, #764ba2, #ff758c, #ff7eb3);
        background-size: 300% 300%;
        animation: gradientMove 12s infinite ease;
        color: white;
        text-shadow: 0 3px 10px rgba(0,0,0,0.25);
        margin-bottom: 24px;
    }
    @keyframes gradientMove {
        0% {background-position: 0% 50%;}
        50% {background-position: 100% 50%;}
        100% {background-position: 0% 50%;}
    }
    .header h1 { margin: 0; font-size: 1.6rem; }
    .header p { margin: 6px 0 0 0; opacity: 0.95; }

    /* ----------------------------------------------------
        🔥 TOGGLE + MENU BTN
    ---------------------------------------------------- */
    .toggle-btn {
        position: absolute;
        top: 18px;
        right: 22px;
        padding: 8px 14px;
        font-size: 0.95rem;
        background: var(--card-bg);
        border-radius: 9px;
        cursor: pointer;
        border: none;
        color: var(--text);
        backdrop-filter: blur(8px);
        box-shadow: 0 4px 12px var(--shadow);
        transition: 0.25s;
        z-index: 50;
    }
    .toggle-btn:hover { transform: scale(1.05); }

    .menu-btn { display: none; font-size: 22px; cursor: pointer; margin-bottom: 12px; }

    /* ----------------------------------------------------
        🔥 PAGE LAYOUT (form + content area)
    ---------------------------------------------------- */
    .page-row {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 22px;
        align-items: start;
    }

    /* Form card */
    .form-card {
        background: var(--card-bg);
        padding: 18px;
        border-radius: 12px;
        backdrop-filter: blur(8px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    .form-card h3 { margin-top: 0; }
    .form-row { margin-bottom: 12px; }
    label.small { font-size: .9rem; color: var(--muted); display:block; margin-bottom:6px; }

    input[type="text"], input[type="number"], select {
        width: 100%;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.08);
        font-size: .95rem;
        background: white;
    }

    .btn {
        display: inline-block;
        padding: 10px 14px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-weight: 700;
        color: white;
    }
    .btn-primary { background: var(--accent); }
    .btn-primary:hover { opacity: .95; transform: translateY(-2px); }
    .btn-ghost {
        background: transparent;
        color: var(--text);
        border: 1px solid rgba(0,0,0,0.06);
    }

    /* small helpers */
    .top-actions { display:flex; gap:12px; align-items:center; margin-bottom:14px; }
    .muted { color:var(--muted); font-size:.95rem; }

    /* ----------------------------------------------------
        🔥 LIST: TABLE
    ---------------------------------------------------- */
    .table-card {
        background: var(--card-bg);
        padding: 12px;
        border-radius: 12px;
        overflow: auto;
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
        font-size: .95rem;
    }
    th, td {
        padding: 12px 10px;
        text-align: left;
        border-bottom: 1px solid rgba(0,0,0,0.04);
    }
    th { color: var(--muted); font-weight:700; font-size:.9rem; }

    tr:hover td { background: rgba(0,0,0,0.02); }

    .action-btn {
        padding: 8px 10px;
        border-radius: 8px;
        text-decoration: none;
        color: white;
        font-weight:600;
        font-size:.9rem;
    }
    .edit-btn { background: #f59e0b; }
    .delete-btn { background: #ef4444; }

    /* Messages */
    .message { padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; font-weight:700; display:inline-block; }
    .success { background: #e8fdf0; color: #1e7b34; }
    .error { background: #fde8e8; color: #b91c1c; }

    /* Back button */
    .back-btn {
        display:inline-block;
        padding: 8px 12px;
        border-radius:8px;
        text-decoration:none;
        font-weight:700;
        color:white;
        background:#ef4444;
    }

    /* ----------------------------------------------------
        🔥 RESPONSIVE
    ---------------------------------------------------- */
    @media (max-width: 1000px) {
        .page-row { grid-template-columns: 1fr; }
        .main { padding: 18px; margin-left: 0; }
        .sidebar { left: -260px; position: fixed; }
        .sidebar.active { left: 0; }
        .menu-btn { display:inline-block; }
    }
</style>
</head>
<body>

    <!-- Dark mode toggle -->
    <button class="toggle-btn" onclick="toggleMode()">
        <i class="fas fa-moon"></i> Mode
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <h2>⚙️ Dashboard</h2>

        <a href="manage_student.php"><i class="fas fa-user-graduate"></i> Manage Students</a>
        <a href="manage_buses.php" class="active"><i class="fas fa-bus"></i> Manage Buses</a>
        <a href="manage_drivers.php"><i class="fas fa-id-card"></i> Manage Drivers</a>
        <a href="manage_routes.php"><i class="fas fa-route"></i> Manage Routes</a>
        <a href="manage_bus_allocation.php" ><i class="fas fa-exchange-alt"></i>Manage Bus Allocation</a>

        <br>
        <a href="../index.php" style="background: rgba(255,50,50,0.35); color: white; text-align:center;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <!-- Main content -->
    <div class="main">
        <!-- mobile menu button -->
        <span class="menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></span>

        <!-- Header -->
        <div class="header">
            <h1> Bus Management</h1>
            <p>View, add, edit or delete buses — assign drivers and routes easily.</p>
        </div>

        <!-- messages & top controls -->
        <?php if (!empty($success)): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="top-actions" style="justify-content:space-between; align-items:center;">
            <div style="display:flex; gap:12px; align-items:center;">
                <a class="back-btn" href="../admin/dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <div class="muted">Buses: <strong><?= count($buses) ?></strong></div>
            </div>
            
        </div>

        <!-- Page row: left = form, right = table -->
        <div class="page-row" style="margin-top:16px;">
            <!-- form (left column) -->
            <div class="form-card">
                <h3><?= $edit_bus ? "Edit Bus" : "Add Bus" ?></h3>

                <form method="POST" style="margin-top:12px;">
                    <input type="hidden" name="bus_id" value="<?= htmlspecialchars($edit_bus['bus_id'] ?? '') ?>">

                    <div class="form-row">
                        <label class="small">Bus Number</label>
                        <input type="text" name="bus_no" required value="<?= htmlspecialchars($edit_bus['bus_number'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <label class="small">Capacity</label>
                        <input type="number" name="capacity" required min="1" value="<?= htmlspecialchars($edit_bus['capacity'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <label class="small">Assign Driver</label>
                        <select name="driver_id" required>
                            <option value="">-- Select Driver --</option>
                            <?php foreach ($drivers as $drv): ?>
                                <option value="<?= $drv['driver_id'] ?>" <?= (!empty($edit_bus) && $edit_bus['driver_id']==$drv['driver_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($drv['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <label class="small">Assign Route</label>
                        <select name="route_id" required>
                            <option value="">-- Select Route --</option>
                            <?php foreach ($routes as $rt): ?>
                                <option value="<?= $rt['route_id'] ?>" <?= (!empty($edit_bus) && $edit_bus['route_id']==$rt['route_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($rt['start_point']) ?> ➡ <?= htmlspecialchars($rt['end_point']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:10px;">
                        <button type="submit" class="btn btn-primary"><?= $edit_bus ? "Update Bus" : "Add Bus" ?></button>
                        <?php if ($edit_bus): ?>
                            <a href="manage_buses.php" class="btn btn-ghost" style="display:inline-flex; align-items:center; text-decoration:none;">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- table area (right column) -->
            <div>
                <h3 style="margin-top:0; margin-bottom:12px;">Bus List</h3>
                <div class="table-card">
                    <?php if (!empty($buses)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:80px;">ID</th>
                                    <th>Bus No</th>
                                    <th style="width:95px;">Capacity</th>
                                    <th>Driver</th>
                                    <th>Route</th>
                                    <th style="width:140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($buses as $bus): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($bus['bus_id']) ?></td>
                                        <td><?= htmlspecialchars($bus['bus_number']) ?></td>
                                        <td><?= htmlspecialchars($bus['capacity']) ?></td>
                                        <td><?= htmlspecialchars($bus['driver_name'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($bus['start_point'] ?? '—') ?> <?= (!empty($bus['start_point']) || !empty($bus['end_point'])) ? '➡' : '' ?> <?= htmlspecialchars($bus['end_point'] ?? '') ?></td>
                                        <td>
                                            <a class="action-btn edit-btn" href="manage_buses.php?edit_id=<?= $bus['bus_id'] ?>">Edit</a>
                                            <a class="action-btn delete-btn" href="manage_buses.php?delete_id=<?= $bus['bus_id'] ?>" onclick="return confirm('Are you sure you want to delete this bus?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="muted">No buses found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /page-row -->

    </div><!-- /main -->

<script>
    // Dark mode toggle
    function toggleMode() {
        document.body.classList.toggle('dark');
    }

    // Sidebar toggle for mobile
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>
