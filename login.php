<?php
// ==============================
// 🔹 Backend Placeholder (optional)
// ==============================
// You can connect to Supabase or your own DB here later.
// For now, forms just redirect or display success messages.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];
        // TODO: Validate login using database or Supabase API
        echo "<script>alert('Login successful for $email!');</script>";
    }

    if ($_POST['action'] === 'register') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        // TODO: Insert user into database or Supabase
        echo "<script>alert('Account created for $name! Please login now.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login & Registration</title>
<style>
    * {
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        margin: 0;
        height: 100vh;
        background: linear-gradient(135deg, #a8e6cf, #56c596);
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .container {
        width: 400px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .form-container {
        padding: 40px;
    }

    h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    input, select {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border-radius: 8px;
        border: 1px solid #ccc;
        outline: none;
        transition: border 0.3s ease;
    }

    input:focus, select:focus {
        border-color: #56c596;
    }

    button {
        width: 100%;
        padding: 12px;
        background-color: #56c596;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    button:hover {
        background-color: #45a17a;
    }

    .toggle-link {
        text-align: center;
        margin-top: 15px;
        font-size: 14px;
    }

    .toggle-link a {
        color: #56c596;
        text-decoration: none;
        font-weight: bold;
    }

    .toggle-link a:hover {
        text-decoration: underline;
    }

    /* Hide register form initially */
    #registerForm {
        display: none;
    }
</style>
</head>
<body>

<div class="container">

    <!-- 🔹 LOGIN FORM -->
    <div class="form-container" id="loginForm">
        <h2>Login</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="login">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <div class="toggle-link">
            Don’t have an account? <a href="#" onclick="toggleForms('register')">Register here</a>
        </div>
    </div>

    <!-- 🔹 REGISTER FORM -->
    <div class="form-container" id="registerForm">
        <h2>Register</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="register">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit">Create Account</button>
        </form>
        <div class="toggle-link">
            Already have an account? <a href="#" onclick="toggleForms('login')">Login here</a>
        </div>
    </div>

</div>

<script>
function toggleForms(form) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if (form === 'register') {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
    } else {
        registerForm.style.display = 'none';
        loginForm.style.display = 'block';
    }
}
</script>

</body>
</html>
