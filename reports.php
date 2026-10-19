<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";


// Mengambil tanggal dari form Reports
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Validasi periode tanggal
$filter_active = false;

if ($start_date !== '' && $end_date !== '') {
    $start = DateTime::createFromFormat('!Y-m-d', $start_date);
    $end = DateTime::createFromFormat('!Y-m-d', $end_date);

    $filter_active =
        $start !== false &&
        $end !== false &&
        $start->format('Y-m-d') === $start_date &&
        $end->format('Y-m-d') === $end_date &&
        $start_date <= $end_date;
}




/* =========================================
   LAPORAN INSPECTION DAN WORK ORDERS
========================================= */


// Total Inspection berdasarkan periode
if ($filter_active) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total
         FROM inspection
         WHERE DATE(inspection_date) BETWEEN ? AND ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $start_date,
        $end_date
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $total_inspection = mysqli_fetch_assoc($result)["total"];

    mysqli_stmt_close($stmt);

} else {

    $total_inspection = mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total FROM inspection"
        )
    )["total"];

}


// Inspection Normal
$normal_inspection = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM inspection
         WHERE inspection_result = 'Normal'"
    )
)["total"];

// Inspection Warning
$warning_inspection = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM inspection
         WHERE inspection_result = 'Warning'"
    )
)["total"];

// Work Orders Completed
$completed_work_orders = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM work_orders
         WHERE status = 'Completed'"
    )
)["total"];

// Rekap Inspection per Equipment
$query = mysqli_query(
    $conn,
    "SELECT
        e.equipment_code,
        e.equipment_name,
        COUNT(i.id) AS total_inspection,
        SUM(
            CASE
                WHEN i.inspection_result = 'Normal'
                THEN 1 ELSE 0
            END
        ) AS total_normal,
        SUM(
            CASE
                WHEN i.inspection_result = 'Warning'
                THEN 1 ELSE 0
            END
        ) AS total_warning
     FROM equipment e
     LEFT JOIN inspection i
        ON e.id = i.equipment_id
     GROUP BY
        e.id,
        e.equipment_code,
        e.equipment_name
     ORDER BY e.equipment_code ASC"
);



?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Reports | Nexora Engineering</title>


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


        /* SIDEBAR */

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


        /* MAIN */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }


        /* TOPBAR */

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


        /* CONTENT */

        .content {
            padding: 35px 40px;
        }

        .page-header {
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


        /* PANEL */

        .panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
        }

        .panel-header {
            margin-bottom: 22px;
        }

        .panel-header h2 {
            color: #102a43;
            font-size: 19px;
            margin-bottom: 5px;
        }

        .panel-header p {
            color: #829ab1;
            font-size: 14px;
        }


        /* TABLE */

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

        tbody tr:hover {
            background: #f8fafc;
        }


        /* STATUS */

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .completed {
            background: #dcfce7;
            color: #15803d;
        }

        .empty {
            text-align: center;
            color: #829ab1;
            padding: 35px;
        }

    </style>

</head>


<body>


<div class="sidebar">

    <div class="brand">

        <h2>NEXORA</h2>
        <span>ENGINEERING</span>

    </div>


    <div class="menu-title">
        Main Menu
    </div>


    <a href="index.php">
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

    <a href="reports.php" class="active">
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

            <h1>
                Reports
            </h1>

            <p>
                Review equipment inspection results and completed work orders.
            </p>

        </div>

        
        <form method="GET" action="reports.php" style="
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: end;
            gap: 15px;
            flex-wrap: wrap;
        ">

            <div>
                <label for="start_date">Start Date</label>
                <input
                    type="date"
                    id="start_date"
                    name="start_date"
                    value="<?= htmlspecialchars($_GET['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    style="display: block; padding: 10px; margin-top: 8px;"
                >
            </div>

            <div>
                <label for="end_date">End Date</label>
                <input
                    type="date"
                    id="end_date"
                    name="end_date"
                    value="<?= htmlspecialchars($_GET['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    style="display: block; padding: 10px; margin-top: 8px;"
                >
             </div>

                <button type="submit" style="
                padding: 11px 20px;
                background: #102a43;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
            ">
                Filter
            </button>

            <a href="reports.php" style="
                padding: 11px 20px;
                background: #edf2f7;
                color: #334e68;
                border-radius: 8px;
                text-decoration: none;
            ">
                Reset
            </a>

        </form>


        
        <div style="
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        ">

            <div class="panel">
                <p>Total Inspections</p>
                <h1><?= $total_inspection; ?></h1>
            </div>

            <div class="panel">
                <p>Normal Inspections</p>
                <h1 style="color: #15803d;">
                    <?= $normal_inspection; ?>
                </h1>
            </div>

            <div class="panel">
                <p>Warning Inspections</p>
                <h1 style="color: #b45309;">
                    <?= $warning_inspection; ?>
                </h1>
             </div>

            <div class="panel">
                <p>Completed Work Orders</p>
                <h1 style="color: #1d4ed8;">
                    <?= $completed_work_orders; ?>
                </h1>
            </div>

        </div>


        <div class="panel">


            <div class="panel-header">

                <h2>
                    Equipment Inspection Report
                </h2>

                <p>
                    Inspection results summary for each registered equipment.
                </p>

            </div>



            <table>


                <thead>

                    <tr>

                        <th>No</th>
                        <th>Equipment Code</th>
                        <th>Equipment Name</th>
                        <th>Total Inspection</th>
                        <th>Normal</th>
                        <th>Warning</th>
                        

                    </tr>

                </thead>


                <tbody>


                <?php

                $no = 1;

                if (mysqli_num_rows($query) > 0):

                    while ($data = mysqli_fetch_assoc($query)):

                ?>


                    <tr>


                        <td>
                            <?= $no++; ?>
                        </td>

                        <td>
                            <strong>
                            <?= htmlspecialchars($data["equipment_code"]); ?>
                            </strong>

                        </td>


                        <td>

                            
                                <?= htmlspecialchars($data["equipment_name"]); ?>
                            

                            <div>

                            <?= (int) $data["total_inspection"]; ?>

                            <div>

                        </td>


                        <td>

                            <?= (int) $data["total_normal"]; ?>

                        </td>


                        <td>

                            <?= (int) $data["total_warning"]; ?>
                        </td>
                        
                        


                    </tr>


                <?php

                    endwhile;

                else:

                ?>


                    <tr>

                        <td
                            colspan="6"
                            class="empty"
                        >

                        No activity history available.

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