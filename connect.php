<?php
// =======================================================
// --- 🐘 PostgreSQL (Supabase) Database Connection ---
// =======================================================

// --- 1. Connection Details ---
// You can find these details in your Supabase project's dashboard.
$host = 'db.gyiosfrjsbrkcsynxtkv.supabase.co';       // The host from your connection string
$port = '5432';                      // The port from your connection string
$dbname = "postgres";
$user = "postgres";
$password = "3D8DJDAL7N3"; // Your database password

// --- 2. Create the DSN (Data Source Name) ---
$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

// --- 3. Establish the Connection ---
try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // If connection fails, stop the script and display an error.
    header('Content-Type: text/plain');
    die("❌ Database Connection Failed: " . $e->getMessage());
}
?>