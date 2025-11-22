<?php include 'db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚍 Student Bus Management System</title>
    <link rel="stylesheet" href="css\style.css">
    <style>
        /* ✅ Light Gradient Background */
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(-45deg, #fceabb, #f8b500, #fad0c4, #ffd1ff);
            background-size: 400% 400%;
            animation: gradient 12s ease infinite;
            text-align: center;
        }

        @keyframes gradient {
            0% {background-position: 0% 50%;}
            50% {background-position: 100% 50%;}
            100% {background-position: 0% 50%;}
        }

        /* ✅ Card Container */
        .container {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 90%;
        }

        /* ✅ Title */
        h1 {
            margin-bottom: 10px;
            font-size: 2rem;
            color: #333;
        }

        p {
            margin-bottom: 30px;
            color: #555;
            font-size: 1.1rem;
        }

        /* ✅ Button Styling */
        .btn {
            display: block;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 15px;
            padding: 15px;
            margin: 10px 0;
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease-in-out;
        }

        .btn:hover {
            background: #ffffff;
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .btn i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚍 Student Bus Management System</h1>
        <p>Welcome! Choose an option below:</p>

        <a href="admin\dashboard.php" class="btn">
            <i class="fas fa-tools"></i> Admin Dashboard
        </a>

        <a href="reports\bus_allocation.php" class="btn">
            <i class="fas fa-clipboard-list"></i> View Bus Allocation Report
        </a>
    </div>
</body>
</html>
