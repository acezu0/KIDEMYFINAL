<?php
// =======================================================
// 🟢 Supabase Connection
// =======================================================
require_once 'connect.php'; // make sure this returns a $pdo connection
session_start();

function sanitize($data) {
  return htmlspecialchars(strip_tags(trim($data)));
}

$message = '';
$message_type = '';
$show_form = 'login';

// =======================================================
// 🟢 Handle Form Submissions
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 🔸 REGISTER
  if (isset($_POST['register'])) {
    $show_form = 'register';
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? '');

    if (!$name || !$email || !$password || !in_array($role, ['teacher', 'student'])) {
      $message = 'Please fill in all fields correctly.';
      $message_type = 'error';
    } else {
      try {
        $stmt = $pdo->prepare("SELECT 1 FROM public.users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
          $message = 'Email already registered.';
          $message_type = 'error';
        } else {
          $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 8]);
          $pdo->prepare("INSERT INTO public.users (name, email, password_hash, role) VALUES (:n, :e, :h, :r)")
              ->execute(['n' => $name, 'e' => $email, 'h' => $hash, 'r' => $role]);
          $message = 'Account created successfully! You can now log in.';
          $message_type = 'success';
          $show_form = 'login';
        }
      } catch (Exception $e) {
        $message = 'Database error during registration.';
        $message_type = 'error';
      }
    }
  }

  // 🔸 LOGIN
  if (isset($_POST['login'])) {
    $show_form = 'login';
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
      $message = 'Please enter valid credentials.';
      $message_type = 'error';
    } else {
      try {
        $stmt = $pdo->prepare("SELECT id, name, role, password_hash FROM public.users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
          $_SESSION['user'] = $user;
          $redirect = $user['role'] === 'teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php';
          header("Location: $redirect");
          exit;
        } else {
          $message = 'Invalid email or password.';
          $message_type = 'error';
        }
      } catch (Exception $e) {
        $message = 'Database error during login.';
        $message_type = 'error';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kidemy | Login & Register</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: "Poppins", sans-serif;
    }

    body {
      height: 100vh;
      background: linear-gradient(135deg, #bbf7d0, #86efac);
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .wrapper {
      width: 900px;
      max-width: 95%;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      display: flex;
      overflow: hidden;
    }

    .left-panel {
      flex: 1;
      background: linear-gradient(135deg, #22c55e, #16a34a);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      padding: 50px 30px;
      text-align: center;
    }

    .left-panel img {
      width: 220px;
      margin-bottom: 25px;
    }

    .left-panel h2 {
      font-size: 1.8em;
      font-weight: 600;
    }

    .right-panel {
      flex: 1;
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    h2 {
      color: #14532d;
      margin-bottom: 10px;
      font-size: 1.9em;
      text-align: left;
    }

    p.subtitle {
      color: #4b5563;
      margin-bottom: 30px;
    }

    .form-group {
      margin-bottom: 18px;
      position: relative;
    }

    label {
      font-weight: 600;
      color: #14532d;
      display: block;
      margin-bottom: 6px;
    }

    input, select {
      width: 100%;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid #d1fae5;
      font-size: 15px;
      transition: 0.2s;
    }

    input:focus, select:focus {
      border-color: #22c55e;
      outline: none;
      box-shadow: 0 0 0 2px #bbf7d0;
    }

    .toggle-password {
      position: absolute;
      right: 14px;
      top: 38px;
      cursor: pointer;
      color: #16a34a;
      font-size: 16px;
    }

    button {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 10px;
      background: linear-gradient(135deg, #22c55e, #16a34a);
      color: #fff;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 10px;
      transition: 0.2s;
    }

    button:hover {
      background: #15803d;
    }

    .toggle-link {
      text-align: center;
      color: #15803d;
      margin-top: 15px;
      font-size: 14px;
      cursor: pointer;
    }

    .hidden {
      display: none;
    }

    .message {
      text-align: center;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 500;
      font-size: 14px;
    }

    .error {
      background: #fee2e2;
      color: #b91c1c;
    }

    .success {
      background: #dcfce7;
      color: #166534;
    }

    @media (max-width: 768px) {
      .wrapper {
        flex-direction: column;
      }
      .left-panel {
        padding: 40px 20px;
      }
      .right-panel {
        padding: 40px 25px;
      }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="left-panel">
      <img src="https://cdn-icons-png.flaticon.com/512/2721/2721296.png" alt="Focus Illustration">
      <h2>Welcome to Kidemy!</h2>
      <p>Empowering students and teachers through digital learning.</p>
    </div>

    <div class="right-panel">
      <?php if ($message): ?>
        <div class="message <?= $message_type ?>"><?= $message ?></div>
      <?php endif; ?>

      <!-- LOGIN FORM -->
      <form method="POST" id="loginForm" <?= $show_form === 'register' ? 'class="hidden"' : '' ?>>
        <h2>Welcome Back!</h2>
        <p class="subtitle">Log in to your account</p>

        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" required>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" id="loginPassword" required>
          <span class="toggle-password" onclick="togglePassword('loginPassword', this)">👁️</span>
        </div>

        <button type="submit" name="login">Login</button>
        <div class="toggle-link" id="goRegister">Don’t have an account? Register</div>
      </form>

      <!-- REGISTER FORM -->
      <form method="POST" id="registerForm" <?= $show_form === 'login' ? 'class="hidden"' : '' ?>>
        <h2>Create Account</h2>
        <p class="subtitle">Register your new account</p>

        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="name" required>
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" required>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" id="registerPassword" required>
          <span class="toggle-password" onclick="togglePassword('registerPassword', this)">👁️</span>
        </div>

        <div class="form-group">
          <label>Role</label>
          <select name="role" required>
            <option value="">Select Role</option>
            <option value="teacher">Teacher</option>
            <option value="student">Student</option>
          </select>
        </div>

        <button type="submit" name="register">Register</button>
        <div class="toggle-link" id="goLogin">Already have an account? Login</div>
      </form>
    </div>
  </div>

  <script>
    // Toggle between login/register forms
    const goLogin = document.getElementById('goLogin');
    const goRegister = document.getElementById('goRegister');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if (goLogin && goRegister) {
      goRegister.addEventListener('click', () => {
        loginForm.classList.add('hidden');
        registerForm.classList.remove('hidden');
      });
      goLogin.addEventListener('click', () => {
        registerForm.classList.add('hidden');
        loginForm.classList.remove('hidden');
      });
    }

    // Password visibility toggle
    function togglePassword(id, el) {
      const input = document.getElementById(id);
      if (input.type === "password") {
        input.type = "text";
        el.textContent = "🙈";
      } else {
        input.type = "password";
        el.textContent = "👁️";
      }
    }
  </script>
</body>
</html>
