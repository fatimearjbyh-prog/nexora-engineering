<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";



$query = mysqli_query(
    $conn,
    "SELECT
        e.*,

        (
            SELECT i.inspection_date
            FROM inspection i
            WHERE i.equipment_id = e.id
            ORDER BY i.inspection_date DESC, i.id DESC
            LIMIT 1
        ) AS last_inspection,

        CASE
            WHEN e.condition_status = 'Normal'
            AND (
                SELECT i.inspection_result
                FROM inspection i
                WHERE i.equipment_id = e.id
                ORDER BY i.inspection_date DESC, i.id DESC
                LIMIT 1
            ) = 'Normal'
            THEN (
                SELECT DATE_ADD(
                    i.inspection_date,
                    INTERVAL 7 DAY
                )
                FROM inspection i
                WHERE i.equipment_id = e.id
                ORDER BY i.inspection_date DESC, i.id DESC
                LIMIT 1
            )
            ELSE NULL
        END AS next_inspection

    FROM equipment e
    ORDER BY e.id DESC"
);


?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Equipment | Nexora Engineering</title>

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
            text-decoration: none;
            background: #102a43;
            color: white;
            padding: 13px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .add-button:hover {
            background: #1f4868;
        }

        /* TABLE */

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
            padding: 35px;
        }
        .edit-button {
        display: inline-block;
        padding: 7px 13px;
        background: #e0f7f4;
        color: #0f766e;
        text-decoration: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: bold;
}

.edit-button:hover {
    background: #c7eee9;
}
.action-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.delete-form {
    margin: 0;
}

.delete-button {
    padding: 7px 13px;
    background: #fee2e2;
    color: #dc2626;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
}

.delete-button:hover {
    background: #fecaca;
}
    </style>

</head>

<body>


<!-- SIDEBAR -->

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

    <a href="equipment.php"
       class="active">
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


<!-- MAIN -->

<div class="main">


    <!-- TOPBAR -->

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


    <!-- CONTENT -->

    <div class="content">

        <div class="page-header">

            <div>

                <h1>Equipment</h1>

                <p>
                    Manage and monitor registered equipment.
                </p>

            </div>

            <a href="tambah.php"
               class="add-button">

                + Add Equipment

            </a>

        </div>


        <div class="panel">

            <div class="panel-header">

                <h2>
                    Equipment List
                </h2>

                <p>
                    Registered equipment at Nexora Engineering.
                </p>

            </div>


            <table>

                <thead>

                    <tr>

                        <th>No</th>
                        <th>Code</th>
                        <th>Equipment Name</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th>Condition</th>
                        <th>Last Inspection</th>
                        <th>Next Inspection</th>
                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php

                $no = 1;

                if (mysqli_num_rows($query) > 0):

                    while ($data = mysqli_fetch_assoc($query)):

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
                            <?= $no++; ?>
                        </td>

                        <td>
                            <strong>
                                <?= htmlspecialchars($data["equipment_code"]); ?>
                            </strong>
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

                            <?= $data["last_inspection"]
                                ? htmlspecialchars($data["last_inspection"])
                                : "-"; ?>

                        </td>

                        
                        <td>
                            <?php
                            if ($data["next_inspection"]) {
                                echo htmlspecialchars($data["next_inspection"]);
                            } elseif ($data["condition_status"] === "Warning" ||
                                    $data["condition_status"] === "Breakdown") {
                                echo "Menunggu perbaikan";
                            } else {
                                echo "Belum dijadwalkan";
                            }
                            ?>
                        </td>
                        <td>

    <div class="action-buttons">

        <a href="edit.php?id=<?= $data["id"]; ?>"
           class="edit-button">
            Edit
        </a>

        <?php if ($_SESSION["role"] === "admin"): ?>

            <form
                action="hapus.php"
                method="POST"
                class="delete-form"
                onsubmit="return confirm('Are you sure you want to delete this equipment?');"
            >

                <input
                    type="hidden"
                    name="id"
                    value="<?= $data["id"]; ?>"
                >

                <button
                    type="submit"
                    class="delete-button"
                >
                    Delete
                </button>

            </form>

        <?php endif; ?>

    </div>

</td>
</tr>
<?php

                    endwhile;

                else:

                ?>

                    <tr>

                        <td colspan="9"
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