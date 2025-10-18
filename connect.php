<?php
// =======================================================
// 🧩 connect.php — Kidemy Unified Connection (Supabase + PostgreSQL + Session)
// =======================================================

// --- Safe session start ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =======================================================
// --- 🌐 Supabase Configuration ---
// =======================================================
define('SUPABASE_URL', 'https://gyiosfrjsbrkcsynxtkv.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imd5aW9zZnJqc2Jya2NzeW54dGt2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTkyOTA0OTcsImV4cCI6MjA3NDg2NjQ5N30.Yc08sv62N1Xi3uKpD5bMjqC6s5LRlgmneDRk8AmqCCo');

// =======================================================
// --- 🐘 PostgreSQL (Direct Supabase DB Connection) ---
// =======================================================

// If you want to use direct SQL connection instead of REST API, set this true
$use_local_pg = true;

if ($use_local_pg) {
    $host = 'db.gyiosfrjsbrkcsynxtkv.supabase.co';
    $port = '5432';
    $dbname = 'postgres';
    $user = 'postgres';
    $password = '3D8DJDAL7N3'; // ⚠️ keep this secure!

    // --- Create DSN (Data Source Name) ---
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => '❌ Database Connection Failed: ' . $e->getMessage()
        ]);
        exit();
    }
} else {
    $pdo = null; // Using Supabase REST API instead of direct DB
}

// =======================================================
// --- 👤 Session Helper Functions ---
// =======================================================

// Shortcut for current user
$_USER = $_SESSION['user'] ?? null;

// Ensure teacher is logged in
function require_teacher() {
    global $_USER;
    if (!$_USER || $_USER['role'] !== 'teacher') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized (teacher only).']);
        exit();
    }
}

// =======================================================
// --- ✅ Connection Summary (Optional for Debug) ---
// =======================================================
// Uncomment this if you need to verify connection during testing:
// echo json_encode([
//     'session_active' => session_status() === PHP_SESSION_ACTIVE,
//     'supabase_url' => SUPABASE_URL,
//     'pdo_connected' => isset($pdo) && $pdo instanceof PDO
// ]);
?>
