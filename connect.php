<?php
// =======================================================
// 🔗 connect.php — Unified Supabase + PHP Session Handler
// =======================================================

// --- Safe session start ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 🔐 Supabase Configuration ---
define('SUPABASE_URL', 'https://gyiosfrjsbrkcsynxtkv.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imd5aW9zZnJqc2Jya2NzeW54dGt2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTkyOTA0OTcsImV4cCI6MjA3NDg2NjQ5N30.Yc08sv62N1Xi3uKpD5bMjqC6s5LRlgmneDRk8AmqCCo');

// --- 🐘 PostgreSQL (optional for admin scripts) ---
$use_local_pg = true; // set to false if using only Supabase REST API

if ($use_local_pg) {
    $host = 'db.gyiosfrjsbrkcsynxtkv.supabase.co';
    $port = '5432';
    $dbname = 'postgres';
    $user = 'postgres';
    $password = '3D8DJDAL7N3';

    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
    }
} else {
    $pdo = null; // only Supabase REST used
}

// --- 👤 Logged in user shortcut ---
$_USER = $_SESSION['user'] ?? null;

// --- ✅ Require Teacher Access ---
function require_teacher() {
    global $_USER;
    if (!$_USER || $_USER['role'] !== 'teacher') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized (teacher only).']);
        exit();
    }
}

// --- 🧰 Supabase REST Request Helper ---
function supabase_fetch($table, $filter = '') {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    if ($filter) $url .= '?' . $filter;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
?>
