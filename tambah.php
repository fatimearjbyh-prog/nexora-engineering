<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $equipment_code = trim($_POST["equipment_code"]);
    $equipment_name = trim($_POST["equipment_name"]);
    $location = trim($_POST["location"]);
    $category = trim($_POST["category"]);
    $condition_status = $_POST["condition_status"];
    $last_maintenance = $_POST["last_maintenance"];
    $next_maintenance = $_POST["next_maintenance"];
    

    $sql = "INSERT INTO equipment
            (
                equipment_code,
                equipment_name,
                location,
                category,
                condition_status,
                last_maintenance,
                next_maintenance
                
            )
            VALUES (
            ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''))";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sssssss",
        $equipment_code,
        $equipment_name,
        $location,
        $category,
        $condition_status,
        $last_maintenance,
        $next_maintenance
    );

    if (mysqli_stmt_execute($stmt)) {

        header("Location: equipment.php");
        exit;

    } else {

        if (mysqli_errno($conn) == 1062) {
            $error = "Equipment code sudah digunakan. Gunakan kode lain.";
        } else {
            $error = "Data gagal disimpan.";
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

    <title>Add Equipment | Nexora Engineering</title>

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
        select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            font-size: 14px;
            color: #243b53;
            background: white;
            outline: none;
        }

        input:focus,
        select:focus {
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

        .cancel-button:hover {
            background: #dfe7ef;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        @media (max-width: 900px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
            }

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


<!-- SIDEBAR -->

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

    <a href="equipment.php"
       class="active">
        Equipment
    </a>

    <a href="inspection.php">
        Inspection
    </a>

    <a href="work_ordes.php">
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


<!-- MAIN -->

<div class="main">


    <!-- TOPBAR -->

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


    <!-- CONTENT -->

    <div class="content">

        <div class="page-header">

            <h1>Add Equipment</h1>

            <p>
                Register new equipment into the Nexora Engineering system.
            </p>

        </div>


        <div class="form-card">

            <h2>Equipment Information</h2>

            <p class="form-description">
                Complete the information below to register new equipment.
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
                            Equipment Code
                        </label>

                        <input
                            type="text"
                            name="equipment_code"
                            placeholder="Example: EQ-002"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Equipment Name
                        </label>

                        <input
                            type="text"
                            name="equipment_name"
                            placeholder="Example: Generator 01"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            placeholder="Example: Utility Area"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Category
                        </label>

                        <input
                            type="text"
                            name="category"
                            placeholder="Example: Generator"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Condition Status
                        </label>

                        <select
                            name="condition_status"
                            required
                        >

                            <option value="">
                                -- Select Condition --
                            </option>

                            <option value="Normal">
                                Normal
                            </option>

                            <option value="Warning">
                                Warning
                            </option>

                            <option value="Breakdown">
                                Breakdown
                            </option>

                        </select>

                    </div>

                    <div class="form-group">

                         <label>
                            Last Maintenance
                        </label>

                        <input
                            type="date"
                            name="last_maintenance"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Next Maintenance
                        </label>

                        <input
                            type="date"
                            name="next_maintenance"
                    >

                    </div>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="save-button"
                    >
                        Save Equipment
                    </button>

                    <a
                        href="equipment.php"
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