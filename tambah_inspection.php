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
        equipment_name
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
   SIMPAN INSPECTION
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $equipment_id = (int) $_POST["equipment_id"];
    $inspector_id = (int) $_POST["inspector_id"];
    $inspection_date = $_POST["inspection_date"];

    $visual_status = $_POST["visual_status"];
    $noise_status = $_POST["noise_status"];
    $vibration_status = $_POST["vibration_status"];
    $temperature_status = $_POST["temperature_status"];
    $leakage_status = $_POST["leakage_status"];

    $findings = trim($_POST["findings"]);


    $allowed_status = [
        "Normal",
        "Abnormal"
    ];


    /* =========================================
       VALIDASI
    ========================================= */

    if (
        $equipment_id <= 0 ||
        $inspector_id <= 0 ||
        $inspection_date === ""
    ) {

        $error = "Equipment, Inspector, dan Inspection Date wajib diisi.";

    } elseif (
        !in_array($visual_status, $allowed_status, true) ||
        !in_array($noise_status, $allowed_status, true) ||
        !in_array($vibration_status, $allowed_status, true) ||
        !in_array($temperature_status, $allowed_status, true) ||
        !in_array($leakage_status, $allowed_status, true)
    ) {

        $error = "Status inspection tidak valid.";

    } else {


        /* =========================================
           CEK EQUIPMENT
        ========================================= */

        $stmt_equipment = mysqli_prepare(
            $conn,
            "SELECT id
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


        if (mysqli_num_rows($result_equipment) === 0) {

            $error = "Equipment tidak ditemukan.";

        } else {


            /* =========================================
               CEK INSPECTOR
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

                $error = "Inspector yang dipilih tidak valid.";

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

                } else {

                    $inspection_result = "Warning";
                }


                /* =========================================
                   SIMPAN KE DATABASE
                ========================================= */

                $sql = "INSERT INTO inspection
                        (
                            equipment_id,
                            inspector_id,
                            inspection_date,
                            visual_status,
                            noise_status,
                            vibration_status,
                            temperature_status,
                            leakage_status,
                            findings,
                            inspection_result
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare(
                    $conn,
                    $sql
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "iissssssss",
                    $equipment_id,
                    $inspector_id,
                    $inspection_date,
                    $visual_status,
                    $noise_status,
                    $vibration_status,
                    $temperature_status,
                    $leakage_status,
                    $findings,
                    $inspection_result
                );


                
                 if (mysqli_stmt_execute($stmt)) {

                    // Jika ditemukan kondisi Abnormal,
                    // perbarui status Equipment menjadi Warning.
                    if ($inspection_result === "Warning") {

                        $update_sql = "
                            UPDATE equipment
                            SET condition_status = 'Warning'
                            WHERE id = ?
                            AND condition_status = 'Normal'
                        ";

                        $update_stmt = mysqli_prepare($conn, $update_sql);

                        mysqli_stmt_bind_param(
                            $update_stmt,
                            "i",
                            $equipment_id
                        );

                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);
                    }

                    header("Location: inspection.php");
                    exit;

                } else {

                    $error = "Inspection gagal disimpan.";
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>New Inspection | Nexora Engineering</title>


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

        .form-card h2 {
            color: #102a43;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .form-description {
            color: #829ab1;
            font-size: 14px;
            margin-bottom: 28px;
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

        .inspection-section {
            grid-column: 1 / -1;
            margin-top: 8px;
            padding-top: 22px;
            border-top: 1px solid #edf2f7;
        }

        .inspection-section h3 {
            color: #102a43;
            font-size: 17px;
            margin-bottom: 18px;
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

        
.menu-toggle,
.close-sidebar {
    display: none;
}

@media (max-width: 768px) {

    .sidebar {
        left: -250px;
        top: 0;
        z-index: 1000;
        transition: left 0.3s ease;
        overflow-y: auto;
    }

    .sidebar.open {
        left: 0;
    }

    .main {
        margin-left: 0;
        width: 100%;
        min-width: 0;
    }

    .menu-toggle {
        display: block;
        background: #102a43;
        color: white;
        border: none;
        padding: 10px 13px;
        border-radius: 8px;
        font-size: 20px;
        cursor: pointer;
        flex-shrink: 0;
    }

    .close-sidebar {
        display: block;
        position: absolute;
        top: 15px;
        right: 15px;
        background: transparent;
        color: white;
        border: none;
        font-size: 28px;
        cursor: pointer;
    }

    .topbar {
        height: auto;
        min-height: 70px;
        padding: 15px;
        gap: 10px;
    }

    .topbar h3 {
        font-size: 13px;
    }

    .user-info {
        font-size: 12px;
    }

    .content {
        padding: 20px 15px;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    /* Form Add Equipment */
    .panel {
        max-width: 100%;
        min-width: 0;
    }

    input,
    select,
    textarea {
        max-width: 100%;
    }

    body {
        overflow-x: hidden;
    }
}


    </style>

</head>


<body>


<div class="sidebar">

    <button class="close-sidebar" type="button" onclick="toggleSidebar()">
         &times;
    </button>

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

    
    <a href="inspection.php" class="active">
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


</div>



<div class="main">


    <div class="topbar">

        <button class="menu-toggle" type="button" onclick="toggleSidebar()">
            ☰
        </button>

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
                New Inspection
            </h1>

            <p>
                Record equipment condition and inspection findings.
            </p>

        </div>



        <div class="form-card">


            <h2>
                Inspection Information
            </h2>

            <p class="form-description">
                Complete the equipment inspection checklist below.
            </p>


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
                            Condition Checklist
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
                            Findings / Notes
                        </label>

                        <textarea
                            name="findings"
                            placeholder="Example: High vibration detected near bearing."
                        ></textarea>

                    </div>


                </div>



                <div class="form-actions">


                    <button
                        type="submit"
                        class="save-button"
                    >
                        Save Inspection
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

<script src="responsive.js"></script>
</body>

</html>