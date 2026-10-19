
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied.");
}

require_once "koneksi.php";

$error = "";

$user_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$user_id) {
    header("Location: user_management.php");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, full_name, username
     FROM users WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    header("Location: user_management.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";

    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";

    } else {
        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $update = mysqli_prepare(
            $conn,
            "UPDATE users SET password = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $update,
            "si",
            $hashed_password,
            $user_id
        );

        if (mysqli_stmt_execute($update)) {
            header("Location: user_management.php");
            exit;
        }

        $error = "Failed to reset password.";
        mysqli_stmt_close($update);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Reset Password | Nexora Engineering</title>

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
            margin-bottom: 25px;
        }

        .user-info {
            background: #f4f7fa;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .user-info p {
            margin: 6px 0;
        }

        label {
            display: block;
            margin: 18px 0 8px;
            font-size: 14px;
            font-weight: bold;
        }

        input {
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

    <h1>Reset Password</h1>

    <p class="description">
        Set a new password for the selected user.
    </p>

    <div class="user-info">
        <p>
            <strong>Full Name:</strong>
            <?= htmlspecialchars($user["full_name"]); ?>
        </p>

        <p>
            <strong>Username:</strong>
            <?= htmlspecialchars($user["username"]); ?>
        </p>
    </div>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>New Password</label>
        <input type="password"
               id="new_password"
               name="password"
               minlength="8"
               autocomplete="new-password"
               required>

        <label style="font-weight: normal;">
            <input type="checkbox"
                onclick="togglePassword()"
                style="width: auto;">
            Show Password
        </label>

        <label>Confirm New Password</label>
        <input type="password"
                id="confirm_password"
               name="confirm_password"
               minlength="8"
               autocomplete="new-password"
               required>

        <div class="actions">
            <button type="submit">
                Reset Password
            </button>

            <a href="user_management.php" class="cancel">
                Cancel
            </a>
        </div>

    </form>

</div>


<script>
function togglePassword() {
    const password = document.getElementById("new_password");
    const confirmPassword = document.getElementById("confirm_password");

    const type = password.type === "password" ? "text" : "password";

    password.type = type;
    confirmPassword.type = type;

    
}
</script>


</body>
</html>

