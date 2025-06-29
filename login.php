<?php
session_start();

$correct_username = "admin";
$correct_password = "secret123"; // Change this

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? '';
    $password = $_POST["password"] ?? '';

    if ($username === $correct_username && $password === $correct_password) {
        $_SESSION["admin_logged_in"] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Invalid login credentials";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Admin Login</title></head>
<body>
    <h2>Admin Login</h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        Username: <input name="username" required><br>
        Password: <input name="password" type="password" required><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>
