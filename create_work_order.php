<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";

$error = "";


/* =========================================
   AMBIL INSPECTION ID
========================================= */

if (
    isset($_GET["inspection_id"]) &&
    is_numeric($_GET["inspection_id"])
) {

    $inspection_id = (int) $_GET["inspection_id"];

} elseif (
    isset($_POST["inspection_id"]) &&
    is_numeric($_POST["inspection_id"])
) {

    $inspection_id = (int) $_POST["inspection_id"];

} else {

    header("Location: inspection.php");
    exit;
}


/* =========================================
   AMBIL DATA INSPECTION
========================================= */

$stmt_inspection = mysqli_prepare(
    $conn,
    "SELECT
        i.id,
        i.equipment_id,
        i.inspector_id,
        i.inspection_result,
        i.findings,
        i.inspection_date,
        e.equipment_code,
        e.equipment_name

     FROM inspection i

     JOIN equipment e
        ON i.equipment_id = e.id

     WHERE i.id = ?"
);

mysqli_stmt_bind_param(
    $stmt_inspection,
    "i",
    $inspection_id
);

mysqli_stmt_execute($stmt_inspection);

$result_inspection =
    mysqli_stmt_get_result($stmt_inspection);

$inspection =
    mysqli_fetch_assoc($result_inspection);


if (!$inspection) {

    header("Location: inspection.php");
    exit;
}


/* Hanya inspection Warning yang bisa dibuat Work Order */

if ($inspection["inspection_result"] !== "Warning") {

    header("Location: inspection.php");
    exit;
}


$equipment_id =
    (int) $inspection["equipment_id"];

$problem_description =
    $inspection["findings"] ?: "Abnormal condition detected during inspection";


/* =========================================
   CEK WORK ORDER SUDAH ADA ATAU BELUM
========================================= */

$stmt_existing = mysqli_prepare(
    $conn,
    "SELECT id
     FROM work_orders
     WHERE inspection_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt_existing,
    "i",
    $inspection_id
);

mysqli_stmt_execute($stmt_existing);

$result_existing =
    mysqli_stmt_get_result($stmt_existing);

$existing_work_order =
    mysqli_fetch_assoc($result_existing);


if ($existing_work_order) {

    header("Location: work_orders.php");
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
   SIMPAN WORK ORDER
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $problem_description =
        trim($_POST["problem_description"]);

    $priority =
        $_POST["priority"];

    $assigned_to =
        isset($_POST["assigned_to"])
        ? (int) $_POST["assigned_to"]
        : 0;


    $allowed_priority = [
        "Low",
        "Medium",
        "High"
    ];


    if ($problem_description === "") {

        $error = "Problem Description wajib diisi.";

    } elseif (
        !in_array(
            $priority,
            $allowed_priority,
            true
        )
    ) {

        $error = "Priority tidak valid.";

    } elseif ($assigned_to <= 0) {

        $error = "PIC / Technician wajib dipilih.";

    } else {


        /* =========================================
           VALIDASI TECHNICIAN
        ========================================= */

        $stmt_technician = mysqli_prepare(
            $conn,
            "SELECT id
             FROM users
             WHERE id = ?
             AND role = 'technician'"
        );

        mysqli_stmt_bind_param(
            $stmt_technician,
            "i",
            $assigned_to
        );

        mysqli_stmt_execute($stmt_technician);

        $result_technician =
            mysqli_stmt_get_result($stmt_technician);


        if (mysqli_num_rows($result_technician) === 0) {

            $error = "Technician yang dipilih tidak valid.";

        } else {


            /* =========================================
               INSERT WORK ORDER
            ========================================= */

            $status = "Open";

            $sql = "INSERT INTO work_orders
                    (
                        inspection_id,
                        equipment_id,
                        problem_description,
                        priority,
                        assigned_to,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare(
                $conn,
                $sql
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iissis",
                $inspection_id,
                $equipment_id,
                $problem_description,
                $priority,
                $assigned_to,
                $status
            );


            if (mysqli_stmt_execute($stmt)) {

                header("Location: work_orders.php");
                exit;

            } else {

                $error = "Work Order gagal dibuat.";
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Work Order | Nexora Engineering</title>


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

        .inspection-source {
            background: #f4f7fa;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 25px;
        }

        .inspection-source strong {
            color: #102a43;
        }

        .inspection-source p {
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

    <a href="maintenance.php">
        Maintenance Schedule
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

    <a href="maintenance_history.php">
        Maintenance History
    </a>


    <div class="menu-title">
        Management
    </div>


    <a
        href="#"
        class="disabled"
    >
        Reports
    </a>


    <?php if ($_SESSION["role"] === "admin"): ?>

        <a
            href="#"
            class="disabled"
        >
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
                Create Work Order
            </h1>

            <p>
                Create corrective work from equipment inspection findings.
            </p>

        </div>



        <div class="form-card">


            <?php if ($error !== ""): ?>

                <div class="error">

                    <?= htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>



            <div class="inspection-source">


                <strong>
                    Source: Inspection #<?= $inspection_id; ?>
                </strong>


                <p>
                    Inspection Date:
                    <?= htmlspecialchars(
                        $inspection["inspection_date"]
                    ); ?>
                </p>


            </div>



            <form method="POST">


                <input
                    type="hidden"
                    name="inspection_id"
                    value="<?= $inspection_id; ?>"
                >


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Equipment
                        </label>

                        <input
                            type="text"
                            value="<?= htmlspecialchars(
                                $inspection["equipment_code"]
                                . " - "
                                . $inspection["equipment_name"]
                            ); ?>"
                            readonly
                        >

                    </div>



                    <div class="form-group">

                        <label>
                            Priority
                        </label>

                        <select
                            name="priority"
                            required
                        >

                            <option value="">
                                -- Select Priority --
                            </option>

                            <option value="Low">
                                Low
                            </option>

                            <option value="Medium">
                                Medium
                            </option>

                            <option value="High">
                                High
                            </option>

                        </select>

                    </div>



                    <div class="form-group">

                        <label>
                            PIC / Technician
                        </label>

                        <select
                            name="assigned_to"
                            required
                        >

                            <option value="">
                                -- Select Technician --
                            </option>


                            <?php while ($technician = mysqli_fetch_assoc($technicians)): ?>

                                <option
                                    value="<?= $technician["id"]; ?>"
                                    <?= (int) $technician["id"] === (int) $inspection["inspector_id"]
                                        ? "selected"
                                        : ""; ?>
                                >
                                    <?= htmlspecialchars($technician["full_name"]); ?>

                                </option>

                            <?php endwhile; ?>


                        </select>

                    </div>



                    <div class="form-group">

                        <label>
                            Initial Status
                        </label>

                        <input
                            type="text"
                            value="Open"
                            readonly
                        >

                    </div>



                    <div class="form-group full">

                        <label>
                            Problem Description
                        </label>

                        <textarea
                            name="problem_description"
                            required
                        ><?= htmlspecialchars($problem_description); ?></textarea>

                    </div>


                </div>



                <div class="form-actions">


                    <button
                        type="submit"
                        class="save-button"
                    >
                        Create Work Order
                    </button>


                    <a
                        href="inspection.php"
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