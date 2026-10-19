
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Hanya Admin yang boleh membuat akun
if ($_SESSION["role"] !== "admin") {
    http_response_code(403);
    exit("Access denied.");
}

require_once "koneksi.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";

    if (
        $full_name === "" ||
        $username === "" ||
        $password === "" ||
        $role === ""
    ) {
        $error = "All fields are required.";

    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";

    } elseif (!in_array($role, ["admin", "technician"], true)) {
        $error = "Invalid user role.";

    } else {

        // Periksa apakah username sudah digunakan
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE username = ?"
        );

        mysqli_stmt_bind_param($check, "s", $username);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Username already exists.";
        } else {

            // Password disimpan sebagai hash
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users
                (full_name, username, password, role)
                VALUES (?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $full_name,
                $username,
                $hashed_password,
                $role
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: user_management.php");
                exit;
            } else {
                $error = "Failed to create user.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add User | Nexora Engineering</title>

    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            background: #f4f7fa;
            color: #243b53;
        }

        .container {
            max-width: 650px;
            margin: 60px auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
        }

        h1 {
            color: #102a43;
            margin-top: 0;
        }

        .description {
            color: #829ab1;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin: 18px 0 8px;
            font-size: 14px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        button, .cancel {
            padding: 13px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        button {
            background: #102a43;
            color: white;
            border: none;
            cursor: pointer;
        }

        .cancel {
            background: #edf2f7;
            color: #334e68;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Add User</h1>
    <p class="description">
        Create a new administrator or technician account.
    </p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Full Name</label>
        <input type="text"
               name="full_name"
               maxlength="100"
               required>

        <label>Username</label>
        <input type="text"
               name="username"
               maxlength="50"
               required>

        <label>Password</label>
        <input type="password"
               name="password"
               minlength="8"
               required>

        <label>Role</label>
        <select name="role" required>
            <option value="">-- Select Role --</option>
            <option value="technician">Technician</option>
            <option value="admin">Admin</option>
        </select>

        <div class="actions">
            <button type="submit">
                Save User
            </button>

            <a href="user_management.php" class="cancel">
                Cancel
            </a>
        </div>

    </form>

</div>

</body>
</html>
