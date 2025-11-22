<?php
include '../db_connect.php';
$success = "";
$error = "";

// ------------------------------
// UPDATE BUS ALLOCATION
// ------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_bus'])) {
    $student_id = $_POST['student_id'];
    $bus_id = !empty($_POST['bus_id']) ? $_POST['bus_id'] : null;

    try {
        $stmt = $conn->prepare("UPDATE students SET bus_id = :bus_id WHERE student_id = :student_id");
        $stmt->execute([
            ':bus_id' => $bus_id,
            ':student_id' => $student_id
        ]);
        $success = "🚌 Bus allocation updated!";
    } catch (PDOException $e) {
        $error = "❌ Error updating allocation: " . $e->getMessage();
    }
}

// ------------------------------
// FETCH STUDENTS + BUSES
// ------------------------------
try {
    $students = $conn->query("
        SELECT s.student_id, s.name AS student_name, s.bus_id,
               b.bus_number
        FROM students s
        LEFT JOIN buses b ON s.bus_id = b.bus_id
        ORDER BY s.student_id ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "❌ Error fetching students: " . $e->getMessage();
}

try {
    $buses = $conn->query("
        SELECT bus_id, bus_number
        FROM buses ORDER BY bus_number ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= "❌ Error fetching buses: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Bus Allocation</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    /* same CSS as Manage Students — shortened for clarity */
    :root {
        --bg1:#ffffff;--bg2:#f6f7fb;--text:#222;
        --card-bg:rgba(255,255,255,.35);
        --sidebar:rgba(255,255,255,.4);
        --shadow:rgba(0,0,0,.15);
        --accent:#ff7b00;--muted:#64748b;
    }
    body.dark {
        --bg1:#0d1117;--bg2:#161b22;--text:#f0f6fc;
        --card-bg:rgba(255,255,255,0.06);
        --sidebar:rgba(255,255,255,0.06);
        --shadow:rgba(255,255,255,0.06);
        --muted:#9aa6b2;
    }
    *{box-sizing:border-box;}
    body{margin:0;font-family:"Poppins",sans-serif;background:var(--bg2);color:var(--text);}

    .sidebar {
        width:250px;height:100vh;position:fixed;left:0;top:0;
        background:var(--sidebar);backdrop-filter:blur(12px);
        padding:20px;box-shadow:4px 0 15px var(--shadow);
    }
    .sidebar a{
        display:block;padding:12px 14px;margin:8px 0;text-decoration:none;color:var(--text);
        border-radius:10px;
    }
    .sidebar a.active,.sidebar a:hover{background:rgba(255,255,255,.18);transform:translateX(6px);}

    .main{margin-left:250px;padding:28px;}
    .header{
        padding:20px;border-radius:14px;color:white;
        background:linear-gradient(135deg,#667eea,#764ba2,#ff758c,#ff7eb3);
        background-size:300% 300%;animation:gradientMove 12s infinite;
        margin-bottom:24px;
    }

    .page-row{margin-top:16px;}
    .table-card{
        background:var(--card-bg);padding:12px;border-radius:12px;overflow:auto;
        box-shadow:0 8px 24px rgba(0,0,0,0.06);
    }

    table{width:100%;border-collapse:collapse;min-width:650px;}
    th,td{padding:12px;border-bottom:1px solid rgba(0,0,0,.04);}
    th{color:var(--muted);font-size:.9rem;}

    .message{padding:10px;border-radius:10px;margin-bottom:12px;font-weight:700;}
    .success{background:#e8fdf0;color:#1e7b34;}
    .error{background:#fde8e8;color:#b91c1c;}

    .toggle-btn{
        position:absolute;top:18px;right:22px;padding:8px 14px;
        background:var(--card-bg);border-radius:9px;border:none;
    }
    .menu-btn{display:none;}

    .back-btn{
        display:inline-block;background:#ef4444;color:white;padding:8px 12px;
        border-radius:8px;text-decoration:none;font-weight:700;
    }

    @media(max-width:1000px){
        .main{margin-left:0;padding:18px;}
        .sidebar{left:-260px;}
        .sidebar.active{left:0;}
        .menu-btn{display:inline-block;}
    }
</style>

</head>
<body>

<button class="toggle-btn" onclick="toggleMode()"><i class="fas fa-moon"></i> Mode</button>

<div class="sidebar" id="sidebar">
    <h2>⚙️ Dashboard</h2>
    <a href="manage_student.php"><i class="fas fa-user-graduate"></i> Manage Students</a>
    <a href="manage_buses.php"><i class="fas fa-bus"></i> Manage Buses</a>
    <a href="manage_drivers.php"><i class="fas fa-id-card"></i> Manage Drivers</a>
    <a href="manage_routes.php"><i class="fas fa-route"></i> Manage Routes</a>
    <a href="manage_bus_allocation.php" class="active"><i class="fas fa-exchange-alt"></i>Manage Bus Allocation</a>
    <br>
    <a href="../index.php" style="background:rgba(255,50,50,.35);color:white;text-align:center;">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<div class="main">
    <span class="menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></span>

    <div class="header">
        <h1>Student Bus Allocation</h1>
        <p>Assign or update buses for students easily.</p>
    </div>

    <?php if ($success): ?><div class="message success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?= $error ?></div><?php endif; ?>

    <div class="top-actions" style="display:flex;justify-content:space-between;margin-bottom:14px;">
        <div style="display:flex; gap:12px;">
            <a class="back-btn" href="../admin/dashboard.php">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
            <div class="muted">Students: <strong><?= count($students) ?></strong></div>
        </div>

        <div style="display:flex; gap:10px;">
            <div class="muted">Search:</div>
            <input type="text" id="searchInput" placeholder="🔍 Search..." onkeyup="searchTable()" 
                   style="padding:8px 10px;border-radius:8px;border:1px solid rgba(0,0,0,.1);">
        </div>
    </div>

    <h3 style="margin-top:0;">Bus Allocation List</h3>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Assigned Bus</th>
                    <th>Change Bus</th>
                </tr>
            </thead>
            <tbody id="students-tbody">
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><?= $s['student_id'] ?></td>
                    <td><?= htmlspecialchars($s['student_name']) ?></td>
                    <td><?= $s['bus_number'] ? htmlspecialchars($s['bus_number']) : "—" ?></td>
                    <td>
                        <form method="POST" style="display:flex; gap:8px; align-items:center;">
                            <input type="hidden" name="student_id" value="<?= $s['student_id'] ?>">
                            <select name="bus_id" style="padding:7px;border-radius:8px;">
                                <option value="">None</option>
                                <?php foreach ($buses as $bus): ?>
                                <option value="<?= $bus['bus_id'] ?>" 
                                    <?= $s['bus_id'] == $bus['bus_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($bus['bus_number']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="assign_bus" class="btn btn-primary" 
                                    style="padding:7px 14px;border-radius:8px;background:var(--accent);color:white;">Assign & Update
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
<script>
function toggleMode(){ document.body.classList.toggle('dark'); }
function toggleSidebar(){ document.getElementById('sidebar').classList.toggle('active'); }
function searchTable(){
    let q=document.getElementById("searchInput").value.toLowerCase();
    document.querySelectorAll("#students-tbody tr").forEach(r=>{
        r.style.display=r.innerText.toLowerCase().includes(q)?"":"none";
    });
}
</script>

</body>
</html>
