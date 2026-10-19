<?php

session_start();
include "koneksi.php";

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $sql = "SELECT id, username, password, full_name, role
            FROM users
            WHERE username = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user["password"])) {

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["role"] = $user["role"];

        header("Location: index.php");
        exit;

    } else {
        $error = "Username atau password tidak sesuai.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login | Nexora Engineering</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #f3f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 900px;
            min-height: 520px;
            background: white;
            display: flex;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 15px 45px rgba(0,0,0,0.12);
        }

        .brand-section {
            width: 50%;
            background: #102a43;
            color: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-logo {
            font-size: 34px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .brand-subtitle {
            color: #36c5b4;
            font-size: 16px;
            margin-bottom: 40px;
        }

        .brand-section h2 {
            font-size: 27px;
            margin-bottom: 18px;
        }

        .brand-section p {
            color: #d9e2ec;
            line-height: 1.7;
        }

        .login-section {
            width: 50%;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-section h1 {
            color: #102a43;
            margin-bottom: 8px;
        }

        .login-section .subtitle {
            color: #7b8794;
            margin-bottom: 35px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #334e68;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            outline: none;
            font-size: 15px;
        }

        input:focus {
            border-color: #36c5b4;
        }

        .login-button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #102a43;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 5px;
        }

        .login-button:hover {
            background: #1f4868;
        }

        .error {
            background: #ffe8e8;
            color: #b42318;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .footer {
            margin-top: 30px;
            color: #9aa5b1;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 800px) {

            .login-container {
                width: 92%;
                flex-direction: column;
            }

            .brand-section,
            .login-section {
                width: 100%;
                padding: 40px;
            }

        }

    </style>

</head>

<body>

<div class="login-container">

    <div class="brand-section">

        <div class="brand-logo">
            NEXORA
        </div>

        <div class="brand-subtitle">
            ENGINEERING
        </div>

        <h2>
            Maintenance & Equipment Inspection System
        </h2>

        <p>
            Manage equipment, preventive maintenance,
            inspections, work orders, and engineering
            activities in one integrated system.
        </p>

    </div>

    <div class="login-section">

        <h1>Welcome Back</h1>

        <p class="subtitle">
            Sign in to access Nexora Maintenance System
        </p>

        <?php if ($error !== ""): ?>

            <div class="error">
                <?= htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label>Username</label>

                <input
                    type="text"
                    name="username"
                    placeholder="Enter your username"
                    required
                >

            </div>

            <div class="form-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>

            <button
                type="submit"
                class="login-button"
            >
                Sign In
            </button>

        </form>

        <div class="footer">
            © 2026 Nexora Engineering
        </div>

    </div>

</div>

</body>
</html>