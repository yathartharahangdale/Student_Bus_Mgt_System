<?php include '../db_connect.php'; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Dashboard — Match Buses Style</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --bg2:#f6f7fb;--text:#222;--card-bg:rgba(255,255,255,0.35);--shadow:rgba(0,0,0,0.15);--accent:#ff7b00;--muted:#64748b;
}
body.dark{--bg2:#161b22;--text:#f0f6fc;--card-bg:rgba(255,255,255,0.06);--shadow:rgba(255,255,255,0.06);--muted:#9aa6b2}
*{box-sizing:border-box}
body{margin:0;font-family:"Poppins",sans-serif;background:var(--bg2);color:var(--text)}
.sidebar{width:250px;height:100vh;position:fixed;background:var(--card-bg);backdrop-filter:blur(12px);padding:20px;left:0;top:0;box-shadow:4px 0 15px var(--shadow)}
.sidebar h2{font-size:1.6rem;margin-bottom:20px;text-align:center}
.sidebar a{display:block;padding:12px 14px;margin:8px 0;text-decoration:none;color:var(--text);border-radius:10px}
.sidebar a.active,.sidebar a:hover{background:rgba(255,255,255,0.18);transform:translateX(6px)}
.main{margin-left:250px;padding:28px}
.header{padding:20px 22px;border-radius:14px;background:linear-gradient(135deg,#667eea,#764ba2,#ff758c,#ff7eb3);color:white;margin-bottom:24px}
.header h1{margin:0;font-size:1.6rem}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:22px}
.card{background:var(--card-bg);padding:22px;border-radius:14px;backdrop-filter:blur(8px);box-shadow:0 8px 24px rgba(0,0,0,0.06);text-align:center}
.card i{font-size:2.1rem;margin-bottom:10px}
.back-btn{display:inline-block;padding:8px 12px;border-radius:8px;background:#ef4444;color:white;text-decoration:none;font-weight:700}
.toggle-btn{position:absolute;top:18px;right:22px;padding:8px 14px;background:var(--card-bg);border-radius:9px;border:none}
@media(max-width:1000px){.main{padding:18px;margin-left:0}.sidebar{left:-260px}.sidebar.active{left:0}}
</style>
</head>
<body>

<button class="toggle-btn" onclick="toggleMode()"><i class="fas fa-moon"></i></button>

<div class="sidebar" id="sidebar">
  <h2>⚙️ Dashboard</h2>

        <a href="manage_student.php" class="active"><i class="fas fa-user-graduate"></i> Manage Students</a>
        <a href="manage_buses.php"><i class="fas fa-bus"></i> Manage Buses</a>
        <a href="manage_drivers.php"><i class="fas fa-id-card"></i> Manage Drivers</a>
        <a href="manage_routes.php"><i class="fas fa-route"></i> Manage Routes</a>
        <a href="manage_bus_allocation.php" ><i class="fas fa-exchange-alt"></i>Manage Bus Allocation</a>

        <br>
        <a href="../index.php" style="background: rgba(255,50,50,0.35); color: white; text-align:center;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>

</div>

<div class="main">
  <div class="header">
    <h1>Welcome Admin 👋</h1>
    <p>Manage students, buses, drivers & routes from one place.</p>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
    <div class="muted">Welcome back</div>
    <a href="../index.php" class="back-btn"><i class="fas fa-home"></i> Home</a>
  </div>

  <div class="grid">
    <a class="card" href="manage_student.php"><i class="fas fa-user-graduate"></i><h3>Manage Students</h3></a>
    <a class="card" href="manage_buses.php"><i class="fas fa-bus"></i><h3>Manage Buses</h3></a>
    <a class="card" href="manage_drivers.php"><i class="fas fa-id-card"></i><h3>Manage Drivers</h3></a>
    <a class="card" href="manage_routes.php"><i class="fas fa-route"></i><h3>Manage Routes</h3></a>
    <a class="card" href="manage_bus_allocation.php"><i class="fas fa-exchange-alt"></i><h3>Manage Bus Allocation</h3></a>
  </div>
</div>

<script>
function toggleMode(){document.body.classList.toggle('dark')}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('active')}
</script>
</body>
</html>

