<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";


/* =========================================
   AMBIL DATA WORK ORDER
========================================= */

$query = mysqli_query(
    
    $conn,
    "SELECT
        w.*,
        e.equipment_code,
        e.equipment_name,
        u.full_name AS assigned_to_name,

        (
            SELECT i.id
            FROM inspection i
            WHERE i.work_order_id = w.id
              AND i.inspection_type = 'Post-Maintenance'
            ORDER BY i.id DESC
            LIMIT 1
        ) AS post_inspection_id

    FROM work_orders w

    JOIN equipment e
        ON w.equipment_id = e.id

    LEFT JOIN users u
        ON w.assigned_to = u.id

    ORDER BY
        CASE
            WHEN w.status = 'Open' THEN 1
            WHEN w.status = 'In Progress' THEN 2
            WHEN w.status = 'Completed' THEN 3
            ELSE 4
        END,
        w.created_at DESC"
);


?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Work Orders | Nexora Engineering</title>


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


        /* PRIORITY */

        .priority {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .priority-low {
            background: #dcfce7;
            color: #15803d;
        }

        .priority-medium {
            background: #fef3c7;
            color: #b45309;
        }

        .priority-high {
            background: #fee2e2;
            color: #dc2626;
        }


        /* STATUS */

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-open {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-progress {
            background: #fef3c7;
            color: #b45309;
        }

        .status-completed {
            background: #dcfce7;
            color: #15803d;
        }

        .manage-button {
            display: inline-block;
            padding: 7px 12px;
            background: #e0f7f4;
            color: #0f766e;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
        }

        .manage-button:hover {
            background: #bfece6;
        }

        .post-inspection-button {
            display: inline-block;
            padding: 7px 12px;
            background: #fef3c7;
            color: #b45309;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .post-inspection-button:hover {
            background: #fde68a;
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

    <a
        href="work_orders.php"
        class="active"
    >
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
                Work Orders
            </h1>

            <p>
                Monitor corrective work generated from equipment inspection findings.
            </p>

        </div>



        <div class="panel">


            <div class="panel-header">

                <h2>
                    Work Order List
                </h2>

                <p>
                    Repair and maintenance tasks assigned to technicians.
                </p>

            </div>



            <table>


                <thead>

                    <tr>

                        <th>WO No.</th>
                        <th>Equipment</th>
                        <th>Problem</th>
                        <th>Priority</th>
                        <th>PIC / Technician</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>

                </thead>


                <tbody>


                <?php

                if (mysqli_num_rows($query) > 0):

                    while ($data = mysqli_fetch_assoc($query)):


                        /* Priority */

                        $priority_class = "priority-low";

                        if ($data["priority"] === "Medium") {
                            $priority_class = "priority-medium";
                        }

                        if ($data["priority"] === "High") {
                            $priority_class = "priority-high";
                        }


                        /* Status */

                        $status_class = "status-open";

                        if ($data["status"] === "In Progress") {
                            $status_class = "status-progress";
                        }
                        if ($data["status"] === "Completed") {
                            $status_class = "status-completed";
                        }

                ?>


                    <tr>


                        <td>

                            <strong>

                                <?= "WO-" . str_pad(
                                    $data["id"],
                                    4,
                                    "0",
                                    STR_PAD_LEFT
                                ); ?>

                            </strong>

                        </td>


                        <td>

                            <strong>

                                <?= htmlspecialchars(
                                    $data["equipment_code"]
                                ); ?>

                            </strong>

                            <br>

                            <?= htmlspecialchars(
                                $data["equipment_name"]
                            ); ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $data["problem_description"]
                            ); ?>

                        </td>


                        <td>

                            <span class="priority <?= $priority_class; ?>">

                                <?= htmlspecialchars(
                                    $data["priority"]
                                ); ?>

                            </span>

                        </td>


                        <td>

                            <?= $data["assigned_to_name"]
                                ? htmlspecialchars(
                                    $data["assigned_to_name"]
                                )
                                : "Not Assigned"; ?>

                        </td>


                        <td>

                            <span class="status <?= $status_class; ?>">

                                <?= htmlspecialchars(
                                    $data["status"]
                                ); ?>

                            </span>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $data["created_at"]
                            ); ?>

                        </td>

                        <td>
                            <?php if ($data["status"] !== "Completed"): ?>

                                <a href="work_order_detail.php?id=<?= $data["id"]; ?>"
                                    class="manage-button">
                                    Manage
                            </a>

                                <?php elseif (!empty($data["post_inspection_id"])): ?>
                                    <span class="status status-completed">
                                        Inspected
                                    </span>
                                <?php else: ?>
                                    <a href="post_inspection.php?work_order_id=<?= $data["id"]; ?>"
                                        class="post-inspection-button">
                                        post inspection
                                </a>
                                <?php endif; ?>
                                    
                                
        

                        </td>


                    </tr>


                <?php

                    endwhile;

                else:

                ?>


                    <tr>

                        <td
                            colspan="8"
                            class="empty"
                        >

                            No work orders available.

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