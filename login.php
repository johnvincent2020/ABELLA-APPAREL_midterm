<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];
$activeForm = $_SESSION['active_form'] ?? 'login';
session_unset();
function showForm($error) {
    $hasError = !empty($error);
    $class = $hasError ? 'error-message show' : 'error-message';
    $text = $hasError ? htmlspecialchars($error) : '';
    return "<div class='error-slot'><p class='$class'>$text</p></div>";
}
function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Register</title>
    <link rel="stylesheet" href="stylee.css">
</head>
<body>
<div class="container">
    <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
        <form action="login_register.php" method="POST">
            <h2>Login</h2>
            <?= showForm($errors['login']); ?>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login" class="gold-btn">Login</button>
            <p>
                Don't have an account?
                <a href="#" onclick="showForm('register-form'); return false;">
                    Register
                </a>
            </p>
        </form>
    </div>
    <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
        <form action="login_register.php" method="POST">
            <h2>Register</h2>
            <?= showForm($errors['register']); ?>
            <input type="text" name="name" placeholder="Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register">Register</button>
            <p>
                Already have an account?
                <a href="#" onclick="showForm('login-form'); return false;">
                    Login
                </a>
            </p>
        </form>
    </div>
</div>
<script src="script.js"></script>
</body>
</html>