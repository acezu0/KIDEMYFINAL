<?php
// Start the session to manage user data (mock database) and state
session_start();

// Initialize mock user storage in the session if it doesn't exist
// In a real application, this array would be replaced by database interaction (PostgreSQL/PDO).
if (!isset($_SESSION['mock_db_users'])) {
    $_SESSION['mock_db_users'] = [];
}

$message = '';
$message_type = 'info';
$show_form = 'login'; // Default form view

// Helper function for secure input sanitization
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// ===================================
// --- 📝 Registration Handler ---
// ===================================
if (isset($_POST['register'])) {
    $show_form = 'register'; // Keep registration visible on error

    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Do not sanitize password before hashing
    $role = sanitize_input($_POST['role'] ?? '');

    // 1. Basic Validation
    if (empty($name) || empty($email) || empty($password) || !in_array($role, ['teacher', 'student'])) {
        $message = 'Error: Please ensure all fields are filled correctly and a role is selected.';
        $message_type = 'error';
    }
    // 2. Check for unique email (MOCK DB CHECK)
    elseif (isset($_SESSION['mock_db_users'][$email])) {
        $message = 'Error: This email is already registered. Please log in or use a different email.';
        $message_type = 'error';
    }
    // 3. Security check (password length)
    elseif (strlen($password) < 8) {
        $message = 'Error: Password must be at least 8 characters long.';
        $message_type = 'error';
    }
    else {
        // --- REAL DATABASE INTEGRATION POINT ---
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        /*
        // PLACE YOUR POSTGRESQL INSERT QUERY HERE using PDO:
        // Example: INSERT INTO public.users (email, password_hash, name, role) VALUES (:email, :hash, :name, :role)
        */

        // --- MOCK DATABASE INSERT (for demonstration only) ---
        $_SESSION['mock_db_users'][$email] = [
            'name' => $name,
            'password_hash' => $password_hash,
            'role' => $role,
            'id' => uniqid() // Mock UUID
        ];
        // -----------------------------------------------------

        $message = "Success! Account created for **$name** as a **$role**. Please log in now.";
        $message_type = 'success';
        $show_form = 'login'; // Switch to login form after successful registration
    }
}

// ===================================
// --- 🔒 Login Handler ---
// ===================================
if (isset($_POST['login'])) {
    $show_form = 'login'; // Keep login visible on error

    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $is_valid = false;
    $user = null;

    if (empty($email) || empty($password)) {
        $message = 'Error: Please enter both email and password.';
        $message_type = 'error';
    } else {
        // --- REAL DATABASE INTEGRATION POINT ---
        /*
        // PLACE YOUR POSTGRESQL SELECT QUERY HERE using PDO:
        // Example: SELECT id, password_hash, name, role FROM public.users WHERE email = :email
        // $user = $stmt->fetch(PDO::FETCH_ASSOC);
        */

        // --- MOCK DATABASE SELECT (for demonstration only) ---
        $user = $_SESSION['mock_db_users'][$email] ?? null;

        if ($user && password_verify($password, $user['password_hash'])) {
            $is_valid = true;
            // In a real application, you would set $_SESSION['user_id'] = $user['id'];
        }
        // -----------------------------------------------------

        if ($is_valid) {
            $message = "Login successful! Welcome back, **{$user['name']}** ({$user['role']}).";
            $message_type = 'success';
        } else {
            $message = 'Error: Invalid email or password. Please try again.';
            $message_type = 'error';
        }
    }
}

// Map message type to CSS classes
$message_classes = [
    'info' => 'msg-info',
    'success' => 'msg-success',
    'error' => 'msg-error'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidEMY! Login & Register</title>
    <style>
        /* CSS RESET */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* Green Color Palette (similar to the provided image's clean aesthetic) */
        :root {
            --color-primary: #10B981;    /* Main Vibrant Green (like the button) */
            --color-primary-dark: #059669; /* Darker Green for hover */
            --color-light-green: #ECFDF5; /* Very light green for background details */
            --color-background-soft: #fbfdff; /* Off-white background */
            --color-text-main: #374151; /* Dark Gray for main text */
            --color-text-sub: #6B7280;  /* Light Gray for secondary text */
            --color-border: #D1D5DB;    /* Light border color */
            --color-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: var(--color-background-soft);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* Main Layout Card */
        .main-card {
            display: flex;
            width: 100%;
            max-width: 950px;
            min-height: 550px; /* Set min height to match design */
            background-color: #fff;
            border-radius: 16px;
            box-shadow: var(--color-shadow);
            overflow: hidden;
            position: relative;
        }

        /* Left Side (Illustration) */
        .illustration-side {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-color: var(--color-light-green); /* Light green background */
            position: relative;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--color-text-main);
        }

        .logo span {
            color: var(--color-primary);
        }
        
        /* Mock Illustration Placeholder */
        .mock-illustration {
            width: 100%;
            height: 250px;
            background-color: #D1FAE5; /* A slightly darker green */
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary-dark);
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            opacity: 0.8;
            margin: auto 0;
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
        }

        /* Right Side (Form) */
        .form-side {
            flex: 1.2; /* Slightly wider for the form */
            padding: 60px 50px;
            position: relative;
        }
        
        /* Removed .header-link CSS as requested */

        .welcome-text h2 {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-text-main);
            margin-bottom: 5px;
        }
        .welcome-text p {
            font-size: 16px;
            color: var(--color-text-sub);
            margin-bottom: 30px;
        }

        /* --- Form Elements --- */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text-main);
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            font-size: 16px;
            color: var(--color-text-main);
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input:focus {
            border-color: var(--color-primary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }

        /* --- Buttons --- */
        .primary-button {
            width: 100%;
            padding: 14px;
            margin-top: 15px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
            background-color: var(--color-primary);
            color: #fff;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.4);
        }

        .primary-button:hover {
            background-color: var(--color-primary-dark);
            transform: translateY(-1px);
        }

        .toggle-link {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: var(--color-text-sub);
        }

        .toggle-link a {
            color: var(--color-primary-dark);
            text-decoration: none;
            font-weight: 600;
        }

        .toggle-link a:hover {
            text-decoration: underline;
        }

        /* --- Role Selection --- */
        .role-options {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }

        .role-label {
            flex: 1;
            padding: 12px;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background-color: #fff;
        }

        .role-label:hover {
            border-color: var(--color-primary);
        }

        .role-label input[type="radio"] {
            display: none;
        }

        .role-label input[type="radio"]:checked + span {
            color: var(--color-primary-dark);
            font-weight: 700;
        }
        
        .role-label input[type="radio"]:checked {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }
        
        /* --- Form Toggling & Animation --- */
        .form-box {
            display: none;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.4s ease, transform 0.4s ease;
            position: absolute; 
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 60px 50px; /* Match form-side padding */
        }

        .form-box.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
            position: relative;
        }

        /* --- Message Box Styling --- */
        .message-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            border-left: 5px solid;
            display: none;
            width: 100%;
        }

        .msg-success {
            background-color: #D1FAE5;
            color: var(--color-primary-dark);
            border-color: var(--color-primary);
        }

        .msg-error {
            background-color: #FEE2E2;
            color: #DC2626;
            border-color: #F87171;
        }
        
        /* Media Queries for Responsiveness */
        @media (max-width: 850px) {
            .illustration-side {
                display: none; /* Hide illustration on smaller screens */
            }
            .form-side, .form-box {
                flex: 1;
                padding: 40px 30px;
            }
            /* Removed header-link media query adjustments */
            .main-card {
                min-height: 500px;
            }
        }
        @media (max-width: 480px) {
            .welcome-text h2 {
                font-size: 28px;
            }
            .form-side, .form-box {
                padding: 30px 20px;
            }
        }

    </style>
</head>
<body>

    <div class="main-card">
        
        <!-- Left Side: Illustration and Logo -->
        <div class="illustration-side">
            <div class="logo">Kid<span>EMY!</span></div>
            <div class="mock-illustration">
                Illustration Placeholder <br>(Style of the provided image)
            </div>
            <!-- Empty space for clean bottom alignment -->
            <div></div>
        </div>

        <!-- Right Side: Forms -->
        <div class="form-side">
            
            <!-- PHP Message Display - Centered within the form area -->
            <?php if (!empty($message)): ?>
                <div id="message" class="message-box <?= $message_classes[$message_type] ?>" style="display: block;">
                    <?= nl2br(str_replace(['**'], ['<strong>'], htmlspecialchars($message))) ?>
                </div>
            <?php endif; ?>

            <!-- ======================= -->
            <!-- 🔒 Login Form -->
            <!-- ======================= -->
            <div id="login-form" class="form-box">
                <!-- REMOVED: header-link for SIGN UP -->
                <div class="welcome-text">
                    <h2>Welcome Back!</h2>
                    <p>Sign in to continue your learning journey.</p>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="login" value="1">
                    <div class="form-group">
                        <label for="login_email">Email Address</label>
                        <input type="email" id="login_email" name="email" placeholder="example@kidemy.com" required 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="login_password">Password</label>
                        <input type="password" id="login_password" name="password" placeholder="8+ characters" required>
                    </div>
                    <!-- Note: Forgot Password link goes here if desired -->
                    <button type="submit" class="primary-button">
                        Login
                    </button>
                    <p class="toggle-link">
                        Don't have an account? 
                        <a href="javascript:void(0);" onclick="showForm('register-form')">Register Now</a>
                    </p>
                </form>
            </div>

            <!-- ======================= -->
            <!-- 📝 Registration Form -->
            <!-- ======================= -->
            <div id="register-form" class="form-box">
                <!-- REMOVED: header-link for SIGN IN -->
                <div class="welcome-text">
                    <h2>Register Your Account</h2>
                    <p>Join KidEMY to access courses and resources.</p>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="register" value="1">
                    
                    <div class="form-group">
                        <label for="reg_name">Name</label>
                        <input type="text" id="reg_name" name="name" placeholder="E.g., Chris" required 
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="reg_email">Email</label>
                        <input type="email" id="reg_email" name="email" placeholder="Used for sign in" required 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="reg_password">Password</label>
                        <input type="password" id="reg_password" name="password" placeholder="8+ characters" required>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <div class="role-options">
                            <label for="role_teacher" class="role-label">
                                <input type="radio" id="role_teacher" name="role" value="teacher" required 
                                    <?= (($_POST['role'] ?? '') === 'teacher') ? 'checked' : '' ?>>
                                <span>Teacher</span>
                            </label>
                            <label for="role_student" class="role-label">
                                <input type="radio" id="role_student" name="role" value="student" required 
                                    <?= (($_POST['role'] ?? '') === 'student') ? 'checked' : '' ?>>
                                <span>Student</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="primary-button">
                        Register
                    </button>
                    <p class="toggle-link">
                        Already registered? 
                        <a href="javascript:void(0);" onclick="showForm('login-form')">Sign in here</a>
                    </p>
                </form>
            </div>

        </div>

    </div>

    <script>
        /**
         * Toggles the visibility of the login and registration forms with animation.
         * @param {string} formId The ID of the form to show ('login-form' or 'register-form').
         */
        function showForm(formId) {
            // Get all form elements
            const formBoxes = document.querySelectorAll(".form-box");
            const targetForm = document.getElementById(formId);
            
            // Remove 'active' class from all forms
            formBoxes.forEach(form => form.classList.remove('active'));
            
            // Add 'active' class to the target form
            if (targetForm) {
                targetForm.classList.add('active');
            }

            // Optionally hide the message box when switching forms manually
            // We only hide it if the user manually toggles the form, not on page load after a form submission.
            const messageBox = document.getElementById('message');
            if (messageBox) {
                // If message is currently visible and a manual toggle occurs, hide it
                if (messageBox.style.display === 'block') {
                    // Check if the current visible form is the one we are leaving (not perfect, but an attempt)
                    const currentlyActiveForm = document.querySelector('.form-box.active');
                    if (currentlyActiveForm && currentlyActiveForm.id !== formId) {
                         messageBox.style.display = 'none';
                    }
                }
            }
        }

        // Set the initial form state based on the PHP logic after page load/submission
        window.onload = function() {
            // The PHP variable $show_form determines which form ID to show.
            showForm('<?= $show_form ?>-form'); 
        };
    </script>
</body>
</html>
