<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";

$error = "";


/* =========================================
   AMBIL WORK ORDER ID
========================================= */

if (
    isset($_GET["id"]) &&
    is_numeric($_GET["id"])
) {

    $work_order_id = (int) $_GET["id"];

} elseif (
    isset($_POST["id"]) &&
    is_numeric($_POST["id"])
) {

    $work_order_id = (int) $_POST["id"];

} else {

    header("Location: work_orders.php");
    exit;
}


/* =========================================
   FUNCTION AMBIL WORK ORDER
========================================= */

function getWorkOrder($conn, $work_order_id) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            w.*,
            e.equipment_code,
            e.equipment_name,
            u.full_name AS technician_name

         FROM work_orders w

         JOIN equipment e
            ON w.equipment_id = e.id

         LEFT JOIN users u
            ON w.assigned_to = u.id

         WHERE w.id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $work_order_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($result);
}


/* =========================================
   AMBIL DATA AWAL
========================================= */

$work_order = getWorkOrder(
    $conn,
    $work_order_id
);


if (!$work_order) {

    header("Location: work_orders.php");
    exit;
}


/* =========================================
   UPDATE WORK ORDER
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $status = $_POST["status"];
    $action_taken = trim($_POST["action_taken"]);


    $allowed_status = [
        "Open",
        "In Progress",
        "Completed"
    ];


    if (
        !in_array(
            $status,
            $allowed_status,
            true
        )
    ) {

        $error = "Status Work Order tidak valid.";

    } elseif (
        $status === "Completed" &&
        $action_taken === ""
    ) {

        $error =
            "Action Taken wajib diisi sebelum Work Order diselesaikan.";

    } else {


        /* =========================================
           JIKA COMPLETED
        ========================================= */

        if ($status === "Completed") {

            $stmt_update = mysqli_prepare(
                $conn,
                "UPDATE work_orders
                 SET
                    status = ?,
                    action_taken = ?,
                    completed_at = NOW()
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt_update,
                "ssi",
                $status,
                $action_taken,
                $work_order_id
            );


        /* =========================================
           OPEN / IN PROGRESS
        ========================================= */

        } else {

            $stmt_update = mysqli_prepare(
                $conn,
                "UPDATE work_orders
                 SET
                    status = ?,
                    action_taken = ?,
                    completed_at = NULL
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt_update,
                "ssi",
                $status,
                $action_taken,
                $work_order_id
            );
        }


        if (mysqli_stmt_execute($stmt_update)) {

            header("Location: work_orders.php");
            exit;

        } else {

            $error = "Work Order gagal diperbarui.";
        }
    }
}


/* =========================================
   REFRESH DATA
========================================= */

$work_order = getWorkOrder(
    $conn,
    $work_order_id
);

$wo_number =
    "WO-" .
    str_pad(
        $work_order["id"],
        4,
        "0",
        STR_PAD_LEFT
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

    <title>
        Manage Work Order | Nexora Engineering
    </title>


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


        /* CARD */

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 950px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
        }

        .wo-info {
            background: #f4f7fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 28px;
        }

        .wo-info h3 {
            color: #102a43;
            margin-bottom: 12px;
        }

        .wo-info p {
            color: #627d98;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .wo-info strong {
            color: #334e68;
        }


        /* FORM */

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: bold;
            color: #334e68;
            margin-bottom: 8px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            font-size: 14px;
            color: #243b53;
            background: white;
            outline: none;
        }

        input[readonly],
        textarea[readonly] {
            background: #f4f7fa;
            color: #627d98;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        select:focus,
        textarea:focus {
            border-color: #36c5b4;
            box-shadow: 0 0 0 3px rgba(54,197,180,0.12);
        }


        /* BUTTON */

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .save-button {
            border: none;
            background: #102a43;
            color: white;
            padding: 13px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .save-button:hover {
            background: #1f4868;
        }

        .cancel-button {
            background: #edf2f7;
            color: #334e68;
            text-decoration: none;
            padding: 13px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-size: 14px;
        }

    </style>

</head>


<body>


<div class="sidebar">

    <div class="brand">

        <h2>NEXORA</h2>

        <span>
            ENGINEERING
        </span>

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

        <a
            href="user_management.php">
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

                <?= htmlspecialchars(
                    $_SESSION["full_name"]
                ); ?>

            </div>

            <div class="user-role">

                <?= htmlspecialchars(
                    $_SESSION["role"]
                ); ?>

            </div>

        </div>

    </div>



    <div class="content">


        <div class="page-header">

            <h1>
                Manage Work Order
            </h1>

            <p>
                Update corrective maintenance progress and actions.
            </p>

        </div>



        <div class="form-card">


            <?php if ($error !== ""): ?>

                <div class="error">

                    <?= htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>



            <div class="wo-info">

                <h3>
                    <?= htmlspecialchars($wo_number); ?>
                </h3>

                <p>
                    <strong>Equipment:</strong>

                    <?= htmlspecialchars(
                        $work_order["equipment_code"]
                        . " - "
                        . $work_order["equipment_name"]
                    ); ?>
                </p>

                <p>
                    <strong>Priority:</strong>

                    <?= htmlspecialchars(
                        $work_order["priority"]
                    ); ?>
                </p>

                <p>
                    <strong>PIC / Technician:</strong>

                    <?= $work_order["technician_name"]
                        ? htmlspecialchars(
                            $work_order["technician_name"]
                        )
                        : "Not Assigned"; ?>
                </p>

                <p>
                    <strong>Created At:</strong>

                    <?= htmlspecialchars(
                        $work_order["created_at"]
                    ); ?>
                </p>

            </div>



            <form method="POST">


                <input
                    type="hidden"
                    name="id"
                    value="<?= $work_order_id; ?>"
                >


                <div class="form-grid">


                    <div class="form-group full">

                        <label>
                            Problem Description
                        </label>

                        <textarea
                            readonly
                        ><?= htmlspecialchars(
                            $work_order["problem_description"]
                        ); ?></textarea>

                    </div>



                    <div class="form-group">

                        <label>
                            Work Order Status
                        </label>

                        <select
                            name="status"
                            required
                        >

                            <option
                                value="Open"
                                <?= $work_order["status"] === "Open"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Open
                            </option>

                            <option
                                value="In Progress"
                                <?= $work_order["status"] === "In Progress"
                                    ? "selected"
                                    : ""; ?>
                            >
                                In Progress
                            </option>

                            <option
                                value="Completed"
                                <?= $work_order["status"] === "Completed"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Completed
                            </option>

                        </select>

                    </div>



                    <div class="form-group full">

                        <label>
                            Action Taken
                        </label>

                        <textarea
                            name="action_taken"
                            placeholder="Example: Bearing inspected and replaced. Vibration returned to normal level."
                        ><?= htmlspecialchars(
                            $work_order["action_taken"] ?? ""
                        ); ?></textarea>

                    </div>


                </div>



                <div class="form-actions">


                    <button
                        type="submit"
                        class="save-button"
                    >
                        Update Work Order
                    </button>


                    <a
                        href="work_orders.php"
                        class="cancel-button"
                    >
                        Cancel
                    </a>


                </div>


            </form>


        </div>


    </div>


</div>


</body>

</html>