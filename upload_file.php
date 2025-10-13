<?php
// =======================================================
// 📂 upload_file.php — Handles teacher file uploads
// =======================================================
require_once 'connect.php'; // must return $pdo (PDO)
session_start();
header('Content-Type: application/json; charset=utf-8');

// =======================================================
// 🔐 Authentication Check
// =======================================================
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$teacher_id = $_SESSION['user']['id']; // UUID

// =======================================================
// 🧩 Check Request Validity
// =======================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (empty($_POST['folder_id']) || empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'Missing folder_id or file.']);
    exit();
}

$folder_id = $_POST['folder_id'];

// =======================================================
// 📁 Verify Folder Ownership
// =======================================================
$stmt = $pdo->prepare("SELECT id FROM folders WHERE id = :fid AND teacher_id = :tid LIMIT 1");
$stmt->execute([':fid' => $folder_id, ':tid' => $teacher_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Folder not found or not owned by you.']);
    exit();
}

// =======================================================
// 📎 Handle File Upload
// =======================================================
$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error (code ' . $file['error'] . ').']);
    exit();
}

$originalName = basename($file['name']);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowed = ['pdf', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, PPT, PPTX, JPG, PNG.']);
    exit();
}

// =======================================================
// 📦 Prepare Upload Directory
// =======================================================
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Unable to create uploads directory.']);
        exit();
    }
}

// =======================================================
// 🧠 Generate Unique File Name & Move File
// =======================================================
$uniqueName = uniqid('file_', true) . '.' . $ext;
$destination = $uploadDir . $uniqueName;
$relativePath = 'uploads/' . $uniqueName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
    exit();
}

// =======================================================
// 💾 Insert File Record in Database
// =======================================================
try {
    $stmt = $pdo->prepare("
        INSERT INTO files (folder_id, file_name, file_path, uploaded_by, uploaded_at)
        VALUES (:fid, :fname, :fpath, :uploaded_by, NOW())
    ");
    $stmt->execute([
        ':fid' => $folder_id,
        ':fname' => $originalName,
        ':fpath' => $relativePath,
        ':uploaded_by' => $teacher_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully!',
        'file_path' => $relativePath
    ]);
} catch (PDOException $e) {
    // Cleanup the file if DB insert fails
    if (file_exists($destination)) @unlink($destination);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
