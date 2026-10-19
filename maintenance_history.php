<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";


/* =========================================
   AMBIL MAINTENANCE YANG SUDAH SELESAI
========================================= */

$query = mysqli_query(
    $conn,
    "SELECT
        m.*,
        e.equipment_code,
        e.equipment_name,

        technician.full_name AS assigned_to_name,
        completed_user.full_name AS completed_by_name

     FROM maintenance_schedule m

     JOIN equipment e
        ON m.equipment_id = e.id

     LEFT JOIN users technician
        ON m.assigned_to = technician.id

     LEFT JOIN users completed_user
        ON m.completed_by = completed_user.id

     WHERE m.completed_at IS NOT NULL

     ORDER BY m.completed_at DESC"
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Maintenance History | Nexora Engineering</title>


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

    <a href="maintenance.php">
        Maintenance Schedule
    </a>

    <a href="inspection.php">
        Inspection
    </a>

    <a href="work_orders.php">
        Work Orders
    </a>

    <a href="maintenance_history.php"
       class="active">
        Maintenance History
    </a>


    <div class="menu-title">
        Management
    </div>


    <a href="#"
       class="disabled">
        Reports
    </a>


    <?php if ($_SESSION["role"] === "admin"): ?>

        <a href="#"
           class="disabled">
            User Management
        </a>

    <?php endif; ?>

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
                Maintenance History
            </h1>

            <p>
                Review completed maintenance activities and responsible personnel.
            </p>

        </div>



        <div class="panel">


            <div class="panel-header">

                <h2>
                    Completed Maintenance
                </h2>

                <p>
                    Historical maintenance records for registered equipment.
                </p>

            </div>



            <table>


                <thead>

                    <tr>

                        <th>No</th>
                        <th>Equipment</th>
                        <th>Maintenance Type</th>
                        <th>Scheduled Date</th>
                        <th>PIC / Technician</th>
                        <th>Completed By</th>
                        <th>Completed At</th>
                        <th>Description</th>
                        <th>Status</th>

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

                            <br>

                            <?= htmlspecialchars($data["equipment_name"]); ?>

                        </td>


                        <td>

                            <?= htmlspecialchars($data["maintenance_type"]); ?>

                        </td>


                        <td>

                            <?= htmlspecialchars($data["scheduled_date"]); ?>

                        </td>


                        <td>

                            <?= $data["assigned_to_name"]
                                ? htmlspecialchars($data["assigned_to_name"])
                                : "-"; ?>

                        </td>


                        <td>

                            <?= $data["completed_by_name"]
                                ? htmlspecialchars($data["completed_by_name"])
                                : "-"; ?>

                        </td>


                        <td>

                            <?= htmlspecialchars($data["completed_at"]); ?>

                        </td>


                        <td>

                            <?= $data["description"]
                                ? htmlspecialchars($data["description"])
                                : "-"; ?>

                        </td>


                        <td>

                            <span class="status completed">

                                Completed

                            </span>

                        </td>


                    </tr>


                <?php

                    endwhile;

                else:

                ?>


                    <tr>

                        <td
                            colspan="9"
                            class="empty"
                        >

                            No completed maintenance history available.

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