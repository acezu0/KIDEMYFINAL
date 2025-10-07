<?php
session_start();

// =======================================================
// 🟢 Supabase PostgreSQL Connection
// =======================================================
$host = "db.gyiosfrjsbrkcsynxtkv.supabase.co";
$dbname = "postgres";
$user = "postgres";
$password = "3D8DJDAL7N3";

try {
    $dsn = "pgsql:host=$host;port=5432;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit;
}

$message = "";

// =======================================================
// ✉️ 1. Email Verification Handling
// =======================================================
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT id, verification_token_expires_at FROM public.users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $expiresAt = strtotime($user['verification_token_expires_at']);
        if (time() < $expiresAt) {
            $updateStmt = $pdo->prepare("UPDATE public.users 
                SET email_verified = TRUE, verification_token = NULL, verification_token_expires_at = NULL 
                WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            $message = "✅ Email successfully verified! You can now log in.";
        } else {
            $message = "⏰ Verification token has expired. Please register again.";
        }
    } else {
        $message = "❌ Invalid verification token.";
    }
}

// =======================================================
// 📝 2. Registration Handling
// =======================================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_GET['token'])) {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $role = "student";

    // Generate verification token
    $token = bin2hex(random_bytes(16));
    $expires = date("Y-m-d H:i:s", strtotime("+1 day"));

    try {
        $stmt = $pdo->prepare("INSERT INTO public.users 
            (name, email, password_hash, role, verification_token, verification_token_expires_at)
            VALUES (:name, :email, :password, :role, :token, :expires)");

        $stmt->execute([
            ":name" => $name,
            ":email" => $email,
            ":password" => $password,
            ":role" => $role,
            ":token" => $token,
            ":expires" => $expires
        ]);

        // Send verification link
        $verify_link = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?token=$token";

        // ⚠️ You can replace this with PHPMailer or SMTP later
        mail($email, "Verify your email", "Click this link to verify: $verify_link");

        $message = "✅ Registration successful! Check your email for the verification link.";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'unique')) {
            $message = "⚠️ Email already registered. Please log in instead.";
        } else {
            $message = "❌ Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register / Verify Email</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #a8e6cf, #56c596);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        h1 {
            color: #2e8b57;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        button {
            background: #2e8b57;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
        }
        button:hover {
            background: #3cb371;
        }
        .msg {
            margin-top: 10px;
            font-weight: bold;
            color: #333;
        }
        a {
            color: #2e8b57;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <?php if (!isset($_GET['token'])): ?>
        <h1>Register</h1>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign Up</button>
        </form>
    <?php else: ?>
        <h1>Email Verification</h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <p><a href="login.php">Go to Login Page</a></p>
    <?php endif; ?>

    <?php if ($message && !isset($_GET['token'])): ?>
        <p class="msg"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
</div>
</body>
</html>
