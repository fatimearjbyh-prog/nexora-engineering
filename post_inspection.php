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
    isset($_GET["work_order_id"]) &&
    is_numeric($_GET["work_order_id"])
) {

    $work_order_id = (int) $_GET["work_order_id"];

} elseif (
    isset($_POST["work_order_id"]) &&
    is_numeric($_POST["work_order_id"])
) {

    $work_order_id = (int) $_POST["work_order_id"];

} else {

    header("Location: work_orders.php");
    exit;
}


/* =========================================
   AMBIL DATA WORK ORDER
========================================= */

$stmt_work_order = mysqli_prepare(
    $conn,
    "SELECT
        w.id,
        w.equipment_id,
        w.problem_description,
        w.action_taken,
        w.status,
        e.equipment_code,
        e.equipment_name

     FROM work_orders w

     JOIN equipment e
        ON w.equipment_id = e.id

     WHERE w.id = ?"
);

mysqli_stmt_bind_param(
    $stmt_work_order,
    "i",
    $work_order_id
);

mysqli_stmt_execute($stmt_work_order);

$result_work_order =
    mysqli_stmt_get_result($stmt_work_order);

$work_order =
    mysqli_fetch_assoc($result_work_order);


if (!$work_order) {

    header("Location: work_orders.php");
    exit;
}


/* Hanya Work Order Completed */

if ($work_order["status"] !== "Completed") {

    header("Location: work_orders.php");
    exit;
}


$equipment_id =
    (int) $work_order["equipment_id"];


/* =========================================
   CEK POST INSPECTION SUDAH ADA
========================================= */

$stmt_existing = mysqli_prepare(
    $conn,
    "SELECT id
     FROM inspection
     WHERE work_order_id = ?
     AND inspection_type = 'Post-Maintenance'
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt_existing,
    "i",
    $work_order_id
);

mysqli_stmt_execute($stmt_existing);

$result_existing =
    mysqli_stmt_get_result($stmt_existing);


if (mysqli_num_rows($result_existing) > 0) {

    header("Location: inspection.php");
    exit;
}


/* =========================================
   AMBIL TECHNICIAN
========================================= */

$technicians = mysqli_query(
    $conn,
    "SELECT
        id,
        full_name
     FROM users
     WHERE role = 'technician'
     ORDER BY full_name ASC"
);


/* =========================================
   SIMPAN POST INSPECTION
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $inspector_id =
        isset($_POST["inspector_id"])
        ? (int) $_POST["inspector_id"]
        : 0;

    $inspection_date =
        $_POST["inspection_date"];

    $visual_status =
        $_POST["visual_status"];

    $noise_status =
        $_POST["noise_status"];

    $vibration_status =
        $_POST["vibration_status"];

    $temperature_status =
        $_POST["temperature_status"];

    $leakage_status =
        $_POST["leakage_status"];

    $findings =
        trim($_POST["findings"]);


    $allowed_status = [
        "Normal",
        "Abnormal"
    ];


    /* =========================================
       VALIDASI
    ========================================= */

    if (
        $inspector_id <= 0 ||
        $inspection_date === ""
    ) {

        $error =
            "Inspector dan Inspection Date wajib diisi.";

    } elseif (
        !in_array($visual_status, $allowed_status, true) ||
        !in_array($noise_status, $allowed_status, true) ||
        !in_array($vibration_status, $allowed_status, true) ||
        !in_array($temperature_status, $allowed_status, true) ||
        !in_array($leakage_status, $allowed_status, true)
    ) {

        $error =
            "Status inspection tidak valid.";

    } else {


        /* =========================================
           VALIDASI INSPECTOR
        ========================================= */

        $stmt_inspector = mysqli_prepare(
            $conn,
            "SELECT id
             FROM users
             WHERE id = ?
             AND role = 'technician'"
        );

        mysqli_stmt_bind_param(
            $stmt_inspector,
            "i",
            $inspector_id
        );

        mysqli_stmt_execute($stmt_inspector);

        $result_inspector =
            mysqli_stmt_get_result($stmt_inspector);


        if (mysqli_num_rows($result_inspector) === 0) {

            $error =
                "Inspector yang dipilih tidak valid.";

        } else {


            /* =========================================
               TENTUKAN HASIL OTOMATIS
            ========================================= */

            if (
                $visual_status === "Normal" &&
                $noise_status === "Normal" &&
                $vibration_status === "Normal" &&
                $temperature_status === "Normal" &&
                $leakage_status === "Normal"
            ) {

                $inspection_result = "Normal";
                $equipment_condition = "Normal";

            } else {

                $inspection_result = "Warning";
                $equipment_condition = "Warning";
            }


            $inspection_type =
                "Post-Maintenance";


            /* =========================================
               TRANSACTION
            ========================================= */

            mysqli_begin_transaction($conn);


            try {


                /* Simpan Post Inspection */

                $sql = "INSERT INTO inspection
                        (
                            equipment_id,
                            inspector_id,
                            inspection_date,
                            inspection_type,
                            work_order_id,
                            visual_status,
                            noise_status,
                            vibration_status,
                            temperature_status,
                            leakage_status,
                            findings,
                            inspection_result
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";


                $stmt_insert =
                    mysqli_prepare(
                        $conn,
                        $sql
                    );


                mysqli_stmt_bind_param(
                    $stmt_insert,
                    "iississsssss",
                    $equipment_id,
                    $inspector_id,
                    $inspection_date,
                    $inspection_type,
                    $work_order_id,
                    $visual_status,
                    $noise_status,
                    $vibration_status,
                    $temperature_status,
                    $leakage_status,
                    $findings,
                    $inspection_result
                );


                mysqli_stmt_execute(
                    $stmt_insert
                );


                /* Update kondisi Equipment */

                $stmt_equipment =
                    mysqli_prepare(
                        $conn,
                        "UPDATE equipment
                         SET condition_status = ?
                         WHERE id = ?"
                    );


                mysqli_stmt_bind_param(
                    $stmt_equipment,
                    "si",
                    $equipment_condition,
                    $equipment_id
                );


                mysqli_stmt_execute(
                    $stmt_equipment
                );


                mysqli_commit($conn);


                header(
                    "Location: inspection.php"
                );

                exit;


            } catch (Throwable $e) {


                mysqli_rollback($conn);

                $error =
                    "Post-Maintenance Inspection gagal disimpan.";
            }
        }
    }
}


/* Nomor Work Order */

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
        Post Inspection | Nexora Engineering
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


        /* FORM */

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 950px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
        }

        .source-box {
            background: #f4f7fa;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 28px;
        }

        .source-box h3 {
            color: #102a43;
            margin-bottom: 8px;
        }

        .source-box p {
            color: #627d98;
            font-size: 14px;
            margin-top: 5px;
        }

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

        .inspection-section {
            grid-column: 1 / -1;
            margin-top: 8px;
            padding-top: 22px;
            border-top: 1px solid #edf2f7;
        }

        .inspection-section h3 {
            color: #102a43;
            font-size: 17px;
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

        input[readonly] {
            background: #f4f7fa;
            color: #627d98;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #36c5b4;
            box-shadow: 0 0 0 3px rgba(54,197,180,0.12);
        }


        /* ACTION */

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

    <a
        href="inspection.php">
        Inspection
    </a>

    <a href="work_orders.php" class="active">
        Work Orders
    </a>

    <a href="history.php">
    History
    </a>

    <a
        href="reports.php">
        Reports
    </a>


    <?php if ($_SESSION["role"] === "admin"): ?>

        <a
            href="user_management.php"
        >
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
                Post-Maintenance Inspection
            </h1>

            <p>
                Verify equipment condition after corrective maintenance.
            </p>

        </div>



        <div class="form-card">


            <?php if ($error !== ""): ?>

                <div class="error">

                    <?= htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>



            <div class="source-box">


                <h3>
                    <?= htmlspecialchars($wo_number); ?>
                </h3>


                <p>

                    <strong>
                        Equipment:
                    </strong>

                    <?= htmlspecialchars(
                        $work_order["equipment_code"]
                        . " - "
                        . $work_order["equipment_name"]
                    ); ?>

                </p>


                <p>

                    <strong>
                        Original Problem:
                    </strong>

                    <?= htmlspecialchars(
                        $work_order["problem_description"]
                    ); ?>

                </p>


                <p>

                    <strong>
                        Action Taken:
                    </strong>

                    <?= $work_order["action_taken"]
                        ? htmlspecialchars(
                            $work_order["action_taken"]
                        )
                        : "-"; ?>

                </p>


            </div>



            <form method="POST">


                <input
                    type="hidden"
                    name="work_order_id"
                    value="<?= $work_order_id; ?>"
                >


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Inspector
                        </label>

                        <select
                            name="inspector_id"
                            required
                        >

                            <option value="">
                                -- Select Technician --
                            </option>


                            <?php while ($technician = mysqli_fetch_assoc($technicians)): ?>

                                <option
                                    value="<?= $technician["id"]; ?>"
                                >

                                    <?= htmlspecialchars(
                                        $technician["full_name"]
                                    ); ?>

                                </option>

                            <?php endwhile; ?>


                        </select>

                    </div>



                    <div class="form-group">

                        <label>
                            Inspection Date
                        </label>

                        <input
                            type="date"
                            name="inspection_date"
                            value="<?= date("Y-m-d"); ?>"
                            max="<?= date("Y-m-d"); ?>"
                            required
                        >

                    </div>



                    <div class="inspection-section">

                        <h3>
                            Condition Verification
                        </h3>

                    </div>



                    <div class="form-group">

                        <label>
                            Visual Condition
                        </label>

                        <select
                            name="visual_status"
                            required
                        >
                            <option value="">
                                -- Select Status --
                            </option>

                            <option value="Normal">
                                Normal
                            </option>

                            <option value="Abnormal">
                                Abnormal
                            </option>
                        </select>

                    </div>



                    <div class="form-group">

                        <label>
                            Noise Condition
                        </label>

                        <select
                            name="noise_status"
                            required
                        >
                            <option value="">
                                -- Select Status --
                            </option>

                            <option value="Normal">
                                Normal
                            </option>

                            <option value="Abnormal">
                                Abnormal
                            </option>
                        </select>

                    </div>



                    <div class="form-group">

                        <label>
                            Vibration Condition
                        </label>

                        <select
                            name="vibration_status"
                            required
                        >
                            <option value="">
                                -- Select Status --
                            </option>

                            <option value="Normal">
                                Normal
                            </option>

                            <option value="Abnormal">
                                Abnormal
                            </option>
                        </select>

                    </div>



                    <div class="form-group">

                        <label>
                            Temperature Condition
                        </label>

                        <select
                            name="temperature_status"
                            required
                        >
                            <option value="">
                                -- Select Status --
                            </option>

                            <option value="Normal">
                                Normal
                            </option>

                            <option value="Abnormal">
                                Abnormal
                            </option>
                        </select>

                    </div>



                    <div class="form-group">

                        <label>
                            Leakage Condition
                        </label>

                        <select
                            name="leakage_status"
                            required
                        >
                            <option value="">
                                -- Select Status --
                            </option>

                            <option value="Normal">
                                Normal
                            </option>

                            <option value="Abnormal">
                                Abnormal
                            </option>
                        </select>

                    </div>



                    <div class="form-group full">

                        <label>
                            Verification Findings / Notes
                        </label>

                        <textarea
                            name="findings"
                            placeholder="Example: Vibration returned to normal after bearing replacement."
                        ></textarea>

                    </div>


                </div>



                <div class="form-actions">


                    <button
                        type="submit"
                        class="save-button"
                    >
                        Save Post Inspection
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