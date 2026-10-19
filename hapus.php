<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/* Hanya Admin yang boleh menghapus equipment */
if ($_SESSION["role"] !== "admin") {
    header("Location: equipment.php");
    exit;
}

include "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: equipment.php");
    exit;
}

if (!isset($_POST["id"]) || !is_numeric($_POST["id"])) {
    header("Location: equipment.php");
    exit;
}

$id = (int) $_POST["id"];

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM equipment WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

header("Location: equipment.php");
exit;

?>