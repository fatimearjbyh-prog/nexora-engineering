<?php

include "koneksi.php";

$technicians = [

    [
        "username" => "technician02",
        "password" => "tech002",
        "full_name" => "Budi Santoso"
    ],

    [
        "username" => "technician03",
        "password" => "tech003",
        "full_name" => "Rizky Maulana"
    ],

    [
        "username" => "technician04",
        "password" => "tech004",
        "full_name" => "Dimas Pratama"
    ],

    [
        "username" => "technician05",
        "password" => "tech005",
        "full_name" => "Fajar Nugroho"
    ],

    [
        "username" => "technician06",
        "password" => "tech006",
        "full_name" => "Arif Setiawan"
    ]

];


foreach ($technicians as $technician) {

    $username = $technician["username"];
    $password = $technician["password"];
    $full_name = $technician["full_name"];
    $role = "technician";


    /* Cek username supaya tidak dobel */

    $check = mysqli_prepare(
        $conn,
        "SELECT id
         FROM users
         WHERE username = ?"
    );

    mysqli_stmt_bind_param(
        $check,
        "s",
        $username
    );

    mysqli_stmt_execute($check);

    $result = mysqli_stmt_get_result($check);


    if (mysqli_num_rows($result) > 0) {

        echo $username . " sudah tersedia, dilewati.<br>";
        continue;
    }


    /* Hash password */

    $hashed_password =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );


    /* Simpan technician */

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users
        (
            username,
            password,
            full_name,
            role
        )
        VALUES (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $username,
        $hashed_password,
        $full_name,
        $role
    );


    if (mysqli_stmt_execute($stmt)) {

        echo "Berhasil membuat: "
            . $full_name
            . " ("
            . $username
            . ")<br>";

    } else {

        echo "Gagal membuat: "
            . $full_name
            . "<br>";
    }
}

?>