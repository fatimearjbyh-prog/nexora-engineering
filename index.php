<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";

/* =========================
   DASHBOARD STATISTICS
========================= */

$total_equipment = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM equipment")
)["total"];

$normal_equipment = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM equipment
         WHERE condition_status = 'Normal'"
    )
)["total"];

$warning_equipment = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM equipment
         WHERE condition_status = 'Warning'"
    )
)["total"];

$breakdown_equipment = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM equipment
         WHERE condition_status = 'Breakdown'"
    )
)["total"];

$maintenance_due = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM equipment
         WHERE next_maintenance IS NOT NULL
         AND next_maintenance <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
    )
)["total"];

/* Equipment terbaru */
$recent_equipment = mysqli_query(
    $conn,
    "SELECT *
     FROM equipment
     ORDER BY id DESC
     LIMIT 5"
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Nexora Engineering</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f7fa;
            color: #243b53;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            width: 250px;
            height: 100vh;
            background: #102a43;
            color: white;
            padding: 30px 20px;
        }

        .brand {
            margin-bottom: 45px;
            padding-left: 10px;
        }

        .brand h2 {
            font-size: 26px;
            letter-spacing: 2px;
        }

        .brand span {
            display: block;
            color: #36c5b4;
            font-size: 13px;
            letter-spacing: 2px;
            margin-top: 5px;
        }

        .menu-title {
            font-size: 11px;
            color: #829ab1;
            text-transform: uppercase;
            margin: 25px 10px 10px;
        }

        .sidebar a {
            display: block;
            text-decoration: none;
            color: #d9e2ec;
            padding: 13px 15px;
            margin-bottom: 5px;
            border-radius: 8px;
            font-size: 15px;
        }

        .sidebar a:hover {
            background: #1f4868;
            color: white;
        }

        .sidebar a.active {
            background: #36c5b4;
            color: #102a43;
            font-weight: bold;
        }

        .sidebar a.disabled {
            color: #829ab1;
            cursor: default;
        }

        /* =========================
           MAIN CONTENT
        ========================= */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        /* =========================
           TOP BAR
        ========================= */

        .topbar {
            height: 80px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            border-bottom: 1px solid #e5e7eb;
        }

        .topbar h3 {
            color: #102a43;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: bold;
            color: #102a43;
        }

        .user-role {
            font-size: 12px;
            color: #829ab1;
            margin-top: 3px;
            text-transform: capitalize;
        }

        /* =========================
           CONTENT
        ========================= */

        .content {
            padding: 35px 40px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 30px;
            color: #102a43;
            margin-bottom: 7px;
        }

        .page-header p {
            color: #829ab1;
        }

        .add-button {
            background: #102a43;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
        }

        .add-button:hover {
            background: #1f4868;
        }

        /* =========================
           CARDS
        ========================= */

        .cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 18px;
            margin-bottom: 35px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
        }

        .card-title {
            font-size: 13px;
            color: #829ab1;
            margin-bottom: 12px;
        }

        .card-number {
            font-size: 30px;
            font-weight: bold;
            color: #102a43;
        }

        .normal .card-number {
            color: #15803d;
        }

        .warning .card-number {
            color: #d97706;
        }

        .breakdown .card-number {
            color: #dc2626;
        }

        .maintenance .card-number {
            color: #0f766e;
        }

        /* =========================
           TABLE
        ========================= */

        .panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .panel-header h2 {
            font-size: 19px;
            color: #102a43;
        }

        .view-all {
            text-decoration: none;
            color: #0f766e;
            font-size: 14px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 14px;
            background: #f4f7fa;
            color: #486581;
            font-size: 13px;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #edf2f7;
            font-size: 14px;
        }

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-normal {
            background: #dcfce7;
            color: #15803d;
        }

        .status-warning {
            background: #fef3c7;
            color: #b45309;
        }

        .status-breakdown {
            background: #fee2e2;
            color: #dc2626;
        }

        .empty {
            text-align: center;
            color: #829ab1;
            padding: 30px;
        }

        @media (max-width: 1100px) {

            .cards {
                grid-template-columns: repeat(2, 1fr);
            }

        }

    </style>

</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="brand">

        <h2>NEXORA</h2>

        <span>ENGINEERING</span>

    </div>

    <div class="menu-title">
        Main Menu
    </div>

    <a href="index.php" class="active">
        Dashboard
    </a>

    <a href="equipment.php">
        Equipment
    </a>

    <a href="inspection.php">
        Inspection
    </a>

    <a href="work_orders.php">
        Work Orders
    </a>

    <a href="history.php">
        History 
    </a>
    <a href="reports.php">
        Reports 
    </a>
    <?php if ($_SESSION["role"] === "admin"): ?>

        <a href="user_management.php">
            User Management
        </a>

    <?php endif; ?>

   
    
    <a href="logout.php"
        onclick="return confirm('Are you sure you want to logout?');"
        style="color: #f87171;">
        Logout
    </a>


</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">

    <div class="topbar">

        <h3>
            Maintenance & Equipment Inspection System
        </h3>

        <div class="user-info">

            <div class="user-name">
                <?= htmlspecialchars($_SESSION["full_name"]); ?>
            </div>

            <div class="user-role">
                <?= htmlspecialchars($_SESSION["role"]); ?>
            </div>

        </div>

    </div>


    <div class="content">

        <div class="page-header">

            <div>

                <h1>Dashboard</h1>

                <p>
                    Equipment and maintenance overview
                </p>

            </div>

            <a href="tambah.php"
               class="add-button">
                + Add Equipment
            </a>

        </div>


        <!-- STATISTIC CARDS -->

        <div class="cards">

            <div class="card">

                <div class="card-title">
                    Total Equipment
                </div>

                <div class="card-number">
                    <?= $total_equipment; ?>
                </div>

            </div>


            <div class="card normal">

                <div class="card-title">
                    Normal
                </div>

                <div class="card-number">
                    <?= $normal_equipment; ?>
                </div>

            </div>


            <div class="card warning">

                <div class="card-title">
                    Warning
                </div>

                <div class="card-number">
                    <?= $warning_equipment; ?>
                </div>

            </div>


            <div class="card breakdown">

                <div class="card-title">
                    Breakdown
                </div>

                <div class="card-number">
                    <?= $breakdown_equipment; ?>
                </div>

            </div>


            <div class="card maintenance">

                <div class="card-title">
                    Maintenance Due
                </div>

                <div class="card-number">
                    <?= $maintenance_due; ?>
                </div>

            </div>

        </div>


        <!-- RECENT EQUIPMENT -->

        <div class="panel">

            <div class="panel-header">

                <h2>
                    Recent Equipment
                </h2>

                <a href="equipment.php"
                   class="view-all">
                    View All Equipment →
                </a>

            </div>


            <table>

                <thead>

                    <tr>

                        <th>Code</th>

                        <th>Equipment Name</th>

                        <th>Location</th>

                        <th>Category</th>

                        <th>Condition</th>

                        <th>Next Maintenance</th>

                    </tr>

                </thead>

                <tbody>

                <?php if (mysqli_num_rows($recent_equipment) > 0): ?>

                    <?php while ($data = mysqli_fetch_assoc($recent_equipment)): ?>

                        <?php

                        $status_class = "status-normal";

                        if ($data["condition_status"] === "Warning") {
                            $status_class = "status-warning";
                        }

                        if ($data["condition_status"] === "Breakdown") {
                            $status_class = "status-breakdown";
                        }

                        ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($data["equipment_code"]); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($data["equipment_name"]); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($data["location"]); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($data["category"]); ?>
                            </td>

                            <td>

                                <span class="status <?= $status_class; ?>">

                                    <?= htmlspecialchars($data["condition_status"]); ?>

                                </span>

                            </td>

                            <td>

                                <?= $data["next_maintenance"]
                                    ? htmlspecialchars($data["next_maintenance"])
                                    : "-"; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6"
                            class="empty">

                            No equipment data available.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>

</html>