<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";

$error = "";


/* =========================================
   AMBIL EQUIPMENT ID
========================================= */

if (isset($_GET["equipment_id"]) && is_numeric($_GET["equipment_id"])) {

    $equipment_id = (int) $_GET["equipment_id"];

} elseif (isset($_POST["equipment_id"]) && is_numeric($_POST["equipment_id"])) {

    $equipment_id = (int) $_POST["equipment_id"];

} else {

    header("Location: maintenance.php");
    exit;
}


/* =========================================
   AMBIL DATA EQUIPMENT
========================================= */

$stmt_equipment = mysqli_prepare(
    $conn,
    "SELECT
        id,
        equipment_code,
        equipment_name,
        next_maintenance
     FROM equipment
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $stmt_equipment,
    "i",
    $equipment_id
);

mysqli_stmt_execute($stmt_equipment);

$result_equipment = mysqli_stmt_get_result($stmt_equipment);

$equipment = mysqli_fetch_assoc($result_equipment);


if (!$equipment) {

    header("Location: maintenance.php");
    exit;
}


if (empty($equipment["next_maintenance"])) {

    $error = "Equipment ini belum memiliki Next Maintenance.";
}


$scheduled_date = $equipment["next_maintenance"];


/* =========================================
   CEK APAKAH DETAIL MAINTENANCE SUDAH ADA
========================================= */

$maintenance_id = null;
$maintenance_type = "";
$description = "";
$assigned_to = "";


if (!empty($scheduled_date)) {

    $stmt_existing = mysqli_prepare(
        $conn,
        "SELECT
            id,
            maintenance_type,
            description,
            assigned_to
         FROM maintenance_schedule
         WHERE equipment_id = ?
         AND scheduled_date = ?
         ORDER BY id DESC
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt_existing,
        "is",
        $equipment_id,
        $scheduled_date
    );

    mysqli_stmt_execute($stmt_existing);

    $result_existing = mysqli_stmt_get_result($stmt_existing);

    if ($existing = mysqli_fetch_assoc($result_existing)) {

        $maintenance_id = $existing["id"];
        $maintenance_type = $existing["maintenance_type"];
        $description = $existing["description"];
        $assigned_to = $existing["assigned_to"];
    }
}


/* =========================================
   SIMPAN DETAIL MAINTENANCE
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($error)) {

    $maintenance_type = trim($_POST["maintenance_type"]);
    $description = trim($_POST["description"]);

    $assigned_to =
        $_POST["assigned_to"] !== ""
        ? (int) $_POST["assigned_to"]
        : null;


    if ($maintenance_type === "") {

        $error = "Maintenance Type wajib dipilih.";

    } elseif ($assigned_to === null) {

        $error = "PIC / Technician wajib dipilih.";

    } else {

        /*
        Cek bahwa user yang dipilih benar-benar Technician
        */

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

            /*
            Kalau record sudah ada → UPDATE
            */

            if ($maintenance_id !== null) {

                $sql = "UPDATE maintenance_schedule
                        SET
                            maintenance_type = ?,
                            description = ?,
                            assigned_to = ?
                        WHERE id = ?";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssii",
                    $maintenance_type,
                    $description,
                    $assigned_to,
                    $maintenance_id
                );

            } else {

                /*
                Kalau belum ada → INSERT
                */

                $sql = "INSERT INTO maintenance_schedule
                        (
                            equipment_id,
                            maintenance_type,
                            scheduled_date,
                            description,
                            assigned_to
                        )
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "isssi",
                    $equipment_id,
                    $maintenance_type,
                    $scheduled_date,
                    $description,
                    $assigned_to
                );
            }


            if (mysqli_stmt_execute($stmt)) {

                header("Location: maintenance.php");
                exit;

            } else {

                $error = "Detail maintenance gagal disimpan.";
            }
        }
    }
}


/* =========================================
   AMBIL DAFTAR TECHNICIAN
========================================= */

$technicians = mysqli_query(
    $conn,
    "SELECT id, full_name
     FROM users
     WHERE role = 'technician'
     ORDER BY full_name ASC"
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Maintenance Details | Nexora Engineering</title>


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
            max-width: 900px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
        }

        .equipment-box {
            background: #f4f7fa;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 25px;
        }

        .equipment-code {
            font-weight: bold;
            color: #102a43;
            margin-bottom: 5px;
        }

        .equipment-name {
            color: #486581;
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
            resize: vertical;
            min-height: 110px;
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
            background: #102a43;
            color: white;
            border: none;
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

    <a href="maintenance.php"
       class="active">
        Maintenance Schedule
    </a>

    <a href="#"
       class="disabled">
        Inspection
    </a>

    <a href="#"
       class="disabled">
        Work Orders
    </a>

    <a href="maintenance_history.php">
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
                Maintenance Details
            </h1>

            <p>
                Assign technician and maintenance information.
            </p>

        </div>



        <div class="form-card">


            <?php if ($error !== ""): ?>

                <div class="error">

                    <?= htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>



            <div class="equipment-box">

                <div class="equipment-code">

                    <?= htmlspecialchars($equipment["equipment_code"]); ?>

                </div>

                <div class="equipment-name">

                    <?= htmlspecialchars($equipment["equipment_name"]); ?>

                </div>

            </div>



            <form method="POST">


                <input
                    type="hidden"
                    name="equipment_id"
                    value="<?= $equipment_id; ?>"
                >


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Scheduled Date
                        </label>

                        <input
                            type="date"
                            value="<?= htmlspecialchars($scheduled_date); ?>"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Maintenance Type
                        </label>

                        <select
                            name="maintenance_type"
                            required
                        >

                            <option value="">
                                -- Select Maintenance Type --
                            </option>

                            <option
                                value="Preventive Maintenance"
                                <?= $maintenance_type === "Preventive Maintenance"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Preventive Maintenance
                            </option>

                            <option
                                value="Corrective Maintenance"
                                <?= $maintenance_type === "Corrective Maintenance"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Corrective Maintenance
                            </option>

                            <option
                                value="Inspection"
                                <?= $maintenance_type === "Inspection"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Inspection
                            </option>

                            <option
                                value="Lubrication"
                                <?= $maintenance_type === "Lubrication"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Lubrication
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
                                    <?= (string)$assigned_to === (string)$technician["id"]
                                        ? "selected"
                                        : ""; ?>
                                >

                                    <?= htmlspecialchars($technician["full_name"]); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>



                    <div class="form-group full">

                        <label>
                            Description
                        </label>

                        <textarea
                            name="description"
                            placeholder="Example: Check bearing, lubrication and motor condition."
                        ><?= htmlspecialchars($description); ?></textarea>

                    </div>


                </div>



                <div class="form-actions">


                    <button
                        type="submit"
                        class="save-button"
                    >
                        Save Details
                    </button>


                    <a
                        href="maintenance.php"
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