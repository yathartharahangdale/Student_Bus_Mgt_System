<?php include '../db_connect.php'; ?>

<?php
// --- Server-side logic (same as before) ---
$success = "";
$error = "";

// Delete driver
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM drivers WHERE driver_id = :id");
        $stmt->execute([':id' => $delete_id]);
        $success = "🗑️ Driver deleted successfully!";
    } catch (PDOException $e) {
        $error = "❌ Error deleting driver: " . $e->getMessage();
    }
}

// Add/Edit driver
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $current_license = "";
    if ($driver_id) {
        try {
            $stmt = $conn->prepare("SELECT license_file FROM drivers WHERE driver_id = :id");
            $stmt->execute([':id' => $driver_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $current_license = $row['license_file'] ?? "";
        } catch (PDOException $e) {}
    }

    $license_file_db = $current_license;

    if (!empty($_FILES['license']['name'])) {
        $upload_dir_rel = 'uploads/licenses/';
        $upload_dir_fs = __DIR__ . '/../' . $upload_dir_rel;

        if (!is_dir($upload_dir_fs)) mkdir($upload_dir_fs, 0777, true);

        $original_name = basename($_FILES['license']['name']);
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','pdf'];
        $maxSize = 2 * 1024 * 1024;

        if (!in_array($ext, $allowed)) {
            $error .= "❌ Invalid file type. Allowed: jpg, jpeg, png, pdf. ";
        } elseif ($_FILES['license']['size'] > $maxSize) {
            $error .= "❌ File too large. Max 2MB. ";
        } else {
            $file_name = time() . "_" . preg_replace('/[^A-Za-z0-9_.-]/', '_', $original_name);
            $target_fs = $upload_dir_fs . $file_name;
            $target_db = $upload_dir_rel . $file_name;

            if (move_uploaded_file($_FILES['license']['tmp_name'], $target_fs)) {
                $license_file_db = $target_db;
            } else {
                $error .= "❌ Failed to upload file. ";
            }
        }
    }

    if (empty($error)) {
        try {
            if ($driver_id) {
                $stmt = $conn->prepare("UPDATE drivers SET name=:name, phone=:phone, license_file=:license WHERE driver_id=:driver_id");
                $stmt->execute([
                    ':name' => $name,
                    ':phone' => $phone,
                    ':license' => $license_file_db,
                    ':driver_id' => $driver_id
                ]);
                $success = "✏️ Driver updated successfully!";
            } else {
                $stmt = $conn->prepare("INSERT INTO drivers (name, phone, license_file) VALUES (:name, :phone, :license)");
                $stmt->execute([
                    ':name' => $name,
                    ':phone' => $phone,
                    ':license' => $license_file_db
                ]);
                $success = "✅ Driver added successfully!";
            }
        } catch (PDOException $e) {
            $error = "❌ Database error: " . $e->getMessage();
        }
    }
}

// Fetch drivers
$drivers = $conn->query("SELECT * FROM drivers ORDER BY driver_id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch single driver for edit
$edit_driver = null;
if (isset($_GET['edit_id'])) {
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE driver_id=:id");
    $stmt->execute([':id' => $_GET['edit_id']]);
    $edit_driver = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Drivers - Admin Dashboard</title>

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

    input[type="text"], input[type="file"] {
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
        🔥 LIST VIEWS: CARDS + TABLE
    ---------------------------------------------------- */
    .view-controls { display:flex; gap:10px; align-items:center; margin-bottom:14px; }
    .view-controls .toggle-view { padding:8px 10px; border-radius:8px; cursor:pointer; background: rgba(255,255,255,0.12); }

    /* Card grid */
    .drivers-grid {
        display:grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
    }
    .driver-card {
        background: white;
        padding: 14px;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .driver-card:hover { transform: translateY(-6px); box-shadow: 0 16px 36px rgba(0,0,0,0.10); }
    .driver-card h4 { margin: 0 0 8px 0; }
    .driver-card p { margin: 6px 0; color: var(--muted); font-size: .95rem; }

    .action-row { display:flex; gap:8px; margin-top:10px; }
    .action-row a { padding:8px 10px; border-radius:8px; text-decoration:none; color:white; font-weight:600; font-size:.9rem; }
    .action-edit { background: #f59e0b; } /* amber */
    .action-delete { background: #ef4444; } /* red */

    /* Table view */
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

    <!-- Sidebar (same as dashboard) -->
    <div class="sidebar" id="sidebar">
        <h2>⚙️ Dashboard</h2>

        <a href="manage_student.php"><i class="fas fa-user-graduate"></i> Manage Students</a>
        <a href="manage_buses.php"><i class="fas fa-bus"></i> Manage Buses</a>
        <a href="manage_drivers.php" class="active"><i class="fas fa-id-card"></i> Manage Drivers</a>
        <a href="manage_routes.php"><i class="fas fa-route"></i> Manage Routes</a>
        <a href="manage_bus_allocation.php"><i class="fas fa-exchange-alt"></i>Manage Bus Allocation</a>

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
            <h1>Driver Management</h1>
            <p>View, add, edit or delete drivers — upload license files and manage driver data.</p>
        </div>

        <!-- messages & top controls -->
        <?php if (!empty($success)): ?>
            <div class="message success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>

        <div class="top-actions" style="justify-content:space-between; align-items:center;">
            <div style="display:flex; gap:12px; align-items:center;">
                <a class="back-btn" href="../admin/dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <div class="muted">Drivers: <strong><?= count($drivers) ?></strong></div>
            </div>

            <div style="display:flex; gap:10px; align-items:center;">
                <button class="btn btn-ghost" id="show-cards-btn">Cards View</button>
                <button class="btn btn-ghost" id="show-table-btn">Table View</button>
            </div>
            
        </div>

        <!-- Page row: left = form, right = list -->
        <div class="page-row" style="margin-top:16px;">
            <!-- form (left column) -->
            <div class="form-card">
                <h3><?= $edit_driver ? "Edit Driver" : "Add Driver" ?></h3>

                <form method="POST" enctype="multipart/form-data" style="margin-top:12px;">
                    <input type="hidden" name="driver_id" value="<?= htmlspecialchars($edit_driver['driver_id'] ?? '') ?>">

                    <div class="form-row">
                        <label class="small">Name</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($edit_driver['name'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <label class="small">Phone</label>
                        <input type="text" name="phone" required value="<?= htmlspecialchars($edit_driver['phone'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <label class="small">Upload License (jpg/png/pdf, max 2MB)</label>
                        <input type="file" name="license">
                        <?php if (!empty($edit_driver['license_file'])): ?>
                            <div style="margin-top:8px;">
                                Current License: <a href="<?= '../' . htmlspecialchars($edit_driver['license_file']) ?>" target="_blank">View</a>
                                <a href="manage_drivers.php" class="btn btn-ghost" style="margin-left:8px; padding:6px 10px;">Cancel</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:10px;">
                        <button type="submit" class="btn btn-primary"><?= $edit_driver ? "Update Driver" : "Add Driver" ?></button>
                        <?php if ($edit_driver): ?>
                            <a href="manage_drivers.php" class="btn btn-ghost" style="text-decoration:none; display:inline-flex; align-items:center;">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- list area (right column) -->
            <div>
                <!-- Cards view -->
                <div id="cards-view" class="drivers-grid" style="margin-bottom:18px;">
                    <?php if (!empty($drivers)): ?>
                        <?php foreach ($drivers as $d): ?>
                            <div class="driver-card">
                                <h4><?= htmlspecialchars($d['name']) ?></h4>
                                <p><strong>ID:</strong> <?= htmlspecialchars($d['driver_id']) ?></p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($d['phone'] ?? '—') ?></p>
                                <p>
                                    <strong>License:</strong>
                                    <?php if (!empty($d['license_file'])): ?>
                                        <a href="<?= '../' . htmlspecialchars($d['license_file']) ?>" target="_blank">View</a>
                                    <?php else: ?>
                                        None
                                    <?php endif; ?>
                                </p>

                                <div class="action-row">
                                    <a class="action-edit" href="manage_drivers.php?edit_id=<?= $d['driver_id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                                    <a class="action-delete" href="manage_drivers.php?delete_id=<?= $d['driver_id'] ?>"
                                        onclick="return confirm('Are you sure you want to delete this driver?');"><i class="fas fa-trash"></i> Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="muted">No drivers found.</div>
                    <?php endif; ?>
                </div>

                <!-- Table view (hidden by default) -->
                <div id="table-view" class="table-card" style="display:none;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:70px;">ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>License</th>
                                <th style="width:140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($drivers)): ?>
                                <?php foreach ($drivers as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['driver_id']) ?></td>
                                        <td><?= htmlspecialchars($d['name']) ?></td>
                                        <td><?= htmlspecialchars($d['phone'] ?? '—') ?></td>
                                        <td>
                                            <?php if (!empty($d['license_file'])): ?>
                                                <a href="<?= '../' . htmlspecialchars($d['license_file']) ?>" target="_blank">View</a>
                                            <?php else: ?>
                                                None
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a class="action-edit" href="manage_drivers.php?edit_id=<?= $d['driver_id'] ?>" style="margin-right:8px; padding:6px 8px;">Edit</a>
                                            <a class="action-delete" href="manage_drivers.php?delete_id=<?= $d['driver_id'] ?>"
                                               onclick="return confirm('Are you sure to delete this driver?');" style="padding:6px 8px;">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="muted">No drivers found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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

    // View toggles (cards / table)
    const cardsView = document.getElementById('cards-view');
    const tableView = document.getElementById('table-view');
    document.getElementById('show-cards-btn').addEventListener('click', () => {
        cardsView.style.display = 'grid';
        tableView.style.display = 'none';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    document.getElementById('show-table-btn').addEventListener('click', () => {
        cardsView.style.display = 'none';
        tableView.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // default: cards visible
    cardsView.style.display = 'grid';
    tableView.style.display = 'none';
</script>

</body>
</html>
