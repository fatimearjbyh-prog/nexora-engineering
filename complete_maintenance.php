<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

include "koneksi.php";


/* Hanya menerima request POST */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: maintenance.php");
    exit;
}


/* Validasi maintenance ID */

if (
    !isset($_POST["maintenance_id"]) ||
    !is_numeric($_POST["maintenance_id"])
) {
    header("Location: maintenance.php");
    exit;
}


$maintenance_id = (int) $_POST["maintenance_id"];
$completed_by = (int) $_SESSION["user_id"];


/* =========================================
   AMBIL DATA MAINTENANCE
========================================= */

$stmt_check = mysqli_prepare(
    $conn,
    "SELECT
        m.id,
        m.equipment_id,
        m.scheduled_date,
        m.completed_at,
        e.next_maintenance
     FROM maintenance_schedule m
     JOIN equipment e
        ON m.equipment_id = e.id
     WHERE m.id = ?"
);

mysqli_stmt_bind_param(
    $stmt_check,
    "i",
    $maintenance_id
);

mysqli_stmt_execute($stmt_check);

$result_check = mysqli_stmt_get_result($stmt_check);

$maintenance = mysqli_fetch_assoc($result_check);


/* Kalau data tidak ditemukan */

if (!$maintenance) {
    header("Location: maintenance.php");
    exit;
}


/* Kalau sudah pernah selesai */

if ($maintenance["completed_at"] !== null) {
    header("Location: maintenance.php");
    exit;
}


$equipment_id = $maintenance["equipment_id"];
$scheduled_date = $maintenance["scheduled_date"];


/* =========================================
   TRANSACTION
========================================= */

mysqli_begin_transaction($conn);

try {


    /* Tandai maintenance selesai */

    $stmt_complete = mysqli_prepare(
        $conn,
        "UPDATE maintenance_schedule
         SET
            completed_at = NOW(),
            completed_by = ?
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt_complete,
        "ii",
        $completed_by,
        $maintenance_id
    );

    mysqli_stmt_execute($stmt_complete);



    /* Update Last Maintenance pada Equipment */

    $stmt_last = mysqli_prepare(
        $conn,
        "UPDATE equipment
         SET last_maintenance = ?
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt_last,
        "si",
        $scheduled_date,
        $equipment_id
    );

    mysqli_stmt_execute($stmt_last);



    /*
       Kalau maintenance yang selesai adalah
       Next Maintenance utama dari Equipment,
       kosongkan Next Maintenance.
    */

    if ($maintenance["next_maintenance"] === $scheduled_date) {

        $stmt_next = mysqli_prepare(
            $conn,
            "UPDATE equipment
             SET next_maintenance = NULL
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt_next,
            "i",
            $equipment_id
        );

        mysqli_stmt_execute($stmt_next);
    }


    mysqli_commit($conn);


    header("Location: maintenance.php");
    exit;


} catch (Throwable $e) {


    mysqli_rollback($conn);

    die("Failed to complete maintenance.");

}

?>