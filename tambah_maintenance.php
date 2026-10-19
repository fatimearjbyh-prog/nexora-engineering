<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";

$error = "";


/* =========================================
   AMBIL DAFTAR EQUIPMENT
========================================= */

$equipment_list = mysqli_query(
    $conn,
    "SELECT
        id,
        equipment_code,
        equipment_name,
        next_maintenance
     FROM equipment
     ORDER BY equipment_code ASC"
);


/* =========================================
   AMBIL DAFTAR TECHNICIAN
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
   SIMPAN SCHEDULE
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $equipment_id = (int) $_POST["equipment_id"];
    $maintenance_type = trim($_POST["maintenance_type"]);
    $scheduled_date = $_POST["scheduled_date"];

    $assigned_to =
        $_POST["assigned_to"] !== ""
        ? (int) $_POST["assigned_to"]
        : null;

    $description = trim($_POST["description"]);


    /* Validasi field utama */

    if (
        $equipment_id <= 0 ||
        $maintenance_type === "" ||
        $scheduled_date === ""
    ) {

        $error = "Equipment, Maintenance Type, dan Scheduled Date wajib diisi.";

    } else {

        /* =========================================
           CEK EQUIPMENT
        ========================================= */

        $stmt_equipment = mysqli_prepare(
            $conn,
            "SELECT
                id,
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

        $result_equipment =
            mysqli_stmt_get_result($stmt_equipment);

        $equipment =
            mysqli_fetch_assoc($result_equipment);


        if (!$equipment) {

            $error = "Equipment tidak ditemukan.";

        }

        /* =========================================
           CEK JANGAN SAMA DENGAN NEXT MAINTENANCE
        ========================================= */

        elseif (
            !empty($equipment["next_maintenance"]) &&
            $equipment["next_maintenance"] === $scheduled_date
        ) {

            $error =
                "Tanggal tersebut sudah menjadi Next Maintenance utama pada Equipment.";

        }

        /* =========================================
           CEK DUPLIKAT SCHEDULE
        ========================================= */

        else {

            $stmt_duplicate = mysqli_prepare(
                $conn,
                "SELECT id
                 FROM maintenance_schedule
                 WHERE equipment_id = ?
                 AND scheduled_date = ?
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $stmt_duplicate,
                "is",
                $equipment_id,
                $scheduled_date
            );

            mysqli_stmt_execute($stmt_duplicate);

            $result_duplicate =
                mysqli_stmt_get_result($stmt_duplicate);


            if (mysqli_num_rows($result_duplicate) > 0) {

                $error =
                    "Schedule untuk equipment dan tanggal tersebut sudah tersedia.";

            }

            /* =========================================
               SIMPAN
            ========================================= */

            else {

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


                if (mysqli_stmt_execute($stmt)) {

                    header("Location: maintenance.php");
                    exit;

                } else {

                    $error = "Schedule gagal disimpan.";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Schedule | Nexora Engineering</title>


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

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

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

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 950px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
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
                Add Maintenance Schedule
            </h1>

            <p>
                Create an additional maintenance schedule for registered equipment.
            </p>

        </div>



        <div class="form-card">


            <?php if ($error !== ""): ?>

                <div class="error">

                    <?= htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>



            <form method="POST">


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Equipment
                        </label>

                        <select
                            name="equipment_id"
                            required
                        >

                            <option value="">
                                -- Select Equipment --
                            </option>


                            <?php while ($equipment = mysqli_fetch_assoc($equipment_list)): ?>

                                <option
                                    value="<?= $equipment["id"]; ?>"
                                >

                                    <?= htmlspecialchars(
                                        $equipment["equipment_code"]
                                        . " - "
                                        . $equipment["equipment_name"]
                                    ); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

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

                            <option value="Preventive Maintenance">
                                Preventive Maintenance
                            </option>

                            <option value="Corrective Maintenance">
                                Corrective Maintenance
                            </option>

                            <option value="Inspection">
                                Inspection
                            </option>

                            <option value="Lubrication">
                                Lubrication
                            </option>

                        </select>

                    </div>



                    <div class="form-group">

                        <label>
                            Scheduled Date
                        </label>

                        <input
                            type="date"
                            name="scheduled_date"
                            required
                        >

                    </div>



                    <div class="form-group">

                        <label>
                            PIC / Technician
                        </label>

                        <select
                            name="assigned_to"
                        >

                            <option value="">
                                -- Not Assigned --
                            </option>


                            <?php while ($technician = mysqli_fetch_assoc($technicians)): ?>

                                <option
                                    value="<?= $technician["id"]; ?>"
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
                            placeholder="Example: Additional bearing inspection or lubrication."
                        ></textarea>

                    </div>


                </div>



                <div class="form-actions">


                    <button
                        type="submit"
                        class="save-button"
                    >
                        Save Schedule
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