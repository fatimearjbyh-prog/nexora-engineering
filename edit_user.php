
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Hanya Admin yang boleh mengedit pengguna
if ($_SESSION["role"] !== "admin") {
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

// Mengambil data pengguna yang akan diedit
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, full_name, username, role
     FROM users
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    header("Location: user_management.php");
    exit;
}

// Proses penyimpanan perubahan
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $role = $_POST["role"] ?? "";

    if ($full_name === "" || $username === "") {
        $error = "Full Name and Username are required.";

    } elseif (!in_array($role, ["admin", "technician"], true)) {
        $error = "Invalid role.";

    } elseif ($user_id === (int) $_SESSION["user_id"]
              && $role !== "admin") {
        $error = "You cannot remove your own Admin role.";

    } else {

        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users
             WHERE username = ? AND id != ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "si",
            $username,
            $user_id
        );

        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Username already exists.";

        } else {

            $update = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET full_name = ?, username = ?, role = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "sssi",
                $full_name,
                $username,
                $role,
                $user_id
            );

            if (mysqli_stmt_execute($update)) {
                header("Location: user_management.php");
                exit;
            }

            $error = "Failed to update user.";
            mysqli_stmt_close($update);
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

    <title>Edit User | Nexora Engineering</title>

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

    <h1>Edit User</h1>

    <p class="description">
        Update administrator or technician information.
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
               value="<?= htmlspecialchars(
                   $_POST["full_name"] ?? $user["full_name"]
               ); ?>"
               required>

        <label>Username</label>
        <input type="text"
               name="username"
               maxlength="50"
               value="<?= htmlspecialchars(
                   $_POST["username"] ?? $user["username"]
               ); ?>"
               required>

        <label>Role</label>
        <?php $selected_role = $_POST["role"] ?? $user["role"]; ?>

        <select name="role" required>
            <option value="technician"
                <?= $selected_role === "technician" ? "selected" : ""; ?>>
                Technician
            </option>

            <option value="admin"
                <?= $selected_role === "admin" ? "selected" : ""; ?>>
                Admin
            </option>
        </select>

        <div class="actions">
            <button type="submit">
                Save Changes
            </button>

            <a href="user_management.php" class="cancel">
                Cancel
            </a>
        </div>

    </form>

</div>

</body>
</html>

