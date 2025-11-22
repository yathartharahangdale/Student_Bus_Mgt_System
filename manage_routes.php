<?php include '../db_connect.php'; ?>

<?php
// -------------------------
// Server-side logic
// -------------------------
$success = "";
$error = "";

// Delete route
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM routes WHERE route_id = :id");
        $stmt->execute([':id' => $delete_id]);
        $success = "🗑️ Route deleted successfully!";
    } catch (PDOException $e) {
        $error = "❌ Error deleting route: " . $e->getMessage();
    }
}

// Add / Edit route
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_point = trim($_POST['start_point']);
    $end_point   = trim($_POST['end_point']);
    $route_id    = $_POST['route_id'] ?? null;

    try {
        if ($route_id) {
            $stmt = $conn->prepare("UPDATE routes SET start_point=:start_point, end_point=:end_point WHERE route_id=:route_id");
            $stmt->execute([
                ':start_point' => $start_point,
                ':end_point'   => $end_point,
                ':route_id'    => $route_id
            ]);
            $success = "✏️ Route updated successfully!";
        } else {
            $stmt = $conn->prepare("INSERT INTO routes (start_point, end_point) VALUES (:start_point, :end_point)");
            $stmt->execute([
                ':start_point' => $start_point,
                ':end_point'   => $end_point
            ]);
            $success = "✅ Route added successfully!";
        }
    } catch (PDOException $e) {
        $error = "❌ Database error: " . $e->getMessage();
    }
}

// Fetch all routes
$routes = $conn->query("SELECT * FROM routes ORDER BY route_id DESC")->fetchAll(PDO::FETCH_ASSOC);

// If editing
$edit_route = null;
if (isset($_GET['edit_id'])) {
    $stmt = $conn->prepare("SELECT * FROM routes WHERE route_id = :id");
    $stmt->execute([':id' => $_GET['edit_id']]);
    $edit_route = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Route Management - Admin Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/*     ---------- SAME STYLES AS STUDENT MANAGEMENT PAGE ---------- */

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

* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: "Poppins", sans-serif;
    background: var(--bg2);
    color: var(--text);
    transition: background 0.4s, color 0.4s;
}

/* Sidebar */
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

.main {
    margin-left: 250px;
    padding: 28px;
    transition: 0.4s;
}

/* HEADER */
.header {
    padding: 20px 22px;
    border-radius: 14px;
    background: linear-gradient(135deg, #667eea, #764ba2, #ff758c, #ff7eb3);
    background-size: 300% 300%;
    animation: gradientMove 12s infinite ease;
    color: white;
    margin-bottom: 24px;
    text-shadow: 0 3px 10px rgba(0,0,0,0.25);
}
@keyframes gradientMove {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
}
.header h1 { margin: 0; font-size: 1.6rem; }
.header p { margin: 6px 0 0 0; opacity: 0.95; }

/* Toggle */
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

/* Layout */
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

label.small {
    display: block;
    font-size: .9rem;
    color: var(--muted);
    margin-bottom: 6px;
}

input[type="text"] {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.08);
    background: white;
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 10px 14px;
    border-radius: 10px;
    border: none;
    font-weight: 700;
    cursor: pointer;
    color: white;
}
.btn-primary { background: var(--accent); }
.btn-ghost { background: transparent; border: 1px solid rgba(0,0,0,0.06); color: var(--text); }

/* Table */
.table-card {
    background: var(--card-bg);
    padding: 12px;
    border-radius: 12px;
    overflow: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}
th, td {
    padding: 12px 10px;
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

/* messages */
.message { padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; font-weight:700; display:inline-block; }
.success { background: #e8fdf0; color: #1e7b34; }
.error { background: #fde8e8; color: #b91c1c; }

/* Search bar */
.search-box { display:flex; justify-content:flex-end; margin-bottom:12px; }
.search-box input { padding:8px 10px; width:260px; border-radius:8px; border:1px solid rgba(0,0,0,0.08); }

/* Back button */
.back-btn {
    background:#ef4444;
    padding:8px 12px;
    border-radius:8px;
    color:white;
    text-decoration:none;
    font-weight:700;
}

/* Responsive */
@media (max-width:1000px) {
    .page-row { grid-template-columns: 1fr; }
    .main { margin-left:0; padding:18px; }
    .sidebar { left:-260px; }
    .sidebar.active { left:0; }
}
</style>
</head>
<body>

<button class="toggle-btn" onclick="toggleMode()">
    <i class="fas fa-moon"></i> Mode
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h2>⚙️ Dashboard</h2>

    <a href="manage_student.php"><i class="fas fa-user-graduate"></i> Manage Students</a>
    <a href="manage_buses.php"><i class="fas fa-bus"></i> Manage Buses</a>
    <a href="manage_drivers.php"><i class="fas fa-id-card"></i> Manage Drivers</a>
    <a class="active" href="manage_routes.php"><i class="fas fa-route"></i> Manage Routes</a>
    <a href="manage_bus_allocation.php"><i class="fas fa-exchange-alt"></i>Manage Bus Allocation</a>

    <br>
    <a href="../index.php" style="background: rgba(255,50,50,0.35); color:white; text-align:center;">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<!-- Main -->
<div class="main">

    <!-- Header -->
    <div class="header">
        <h1>Route Management</h1>
        <p>View, add, edit or delete routes — manage transport network easily.</p>
    </div>

    <!-- messages -->
    <?php if ($success): ?><div class="message success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?= $error ?></div><?php endif; ?>

    <!-- top actions -->
    <div class="top-actions" style="justify-content:space-between; display:flex; align-items:center;">
        <div style="display:flex; gap:12px; align-items:center;">
            <a class="back-btn" href="../admin/dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <div class="muted">Routes: <strong><?= count($routes) ?></strong></div>
        </div>

        <div style="display:flex; gap:10px; align-items:center;">
            <div class="muted">Search:</div>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search routes..." onkeyup="searchTable()">
            </div>
        </div>
    </div>

    <!-- Layout -->
    <div class="page-row" style="margin-top:16px;">

        <!-- Form -->
        <div class="form-card">
            <h3><?= $edit_route ? "Edit Route" : "Add Route" ?></h3>

            <form method="POST" style="margin-top:12px;">
                <input type="hidden" name="route_id" value="<?= htmlspecialchars($edit_route['route_id'] ?? '') ?>">

                <div class="form-row">
                    <label class="small">Start Point</label>
                    <input type="text" name="start_point" required value="<?= htmlspecialchars($edit_route['start_point'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <label class="small">End Point</label>
                    <input type="text" name="end_point" required value="<?= htmlspecialchars($edit_route['end_point'] ?? '') ?>">
                </div>

                <div style="display:flex; gap:10px; margin-top:10px;">
                    <button type="submit" class="btn btn-primary"><?= $edit_route ? "Update Route" : "Add Route" ?></button>

                    <?php if ($edit_route): ?>
                        <a href="manage_routes.php" class="btn btn-ghost" style="display:inline-flex; align-items:center;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div>
            <h3 style="margin:0 0 12px 0;">Route List</h3>

            <div class="table-card">
                <?php if (!empty($routes)): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width:80px;">ID</th>
                            <th>Start Point</th>
                            <th>End Point</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="routes-tbody">
                        <?php foreach ($routes as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['route_id']) ?></td>
                            <td><?= htmlspecialchars($r['start_point']) ?></td>
                            <td><?= htmlspecialchars($r['end_point']) ?></td>
                            <td>
                                <a class="action-btn edit-btn" href="manage_routes.php?edit_id=<?= $r['route_id'] ?>">Edit</a>
                                <a class="action-btn delete-btn" 
                                   href="manage_routes.php?delete_id=<?= $r['route_id'] ?>"
                                   onclick="return confirm('Are you sure you want to delete this route?');">
                                   Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="muted">No routes found.</div>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div><!-- /main -->

<script>
// Dark mode
function toggleMode() {
    document.body.classList.toggle('dark');
}

// Sidebar (mobile)
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

// Search
function searchTable() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let rows = document.querySelectorAll("#routes-tbody tr");

    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}
</script>

</body>
</html>
