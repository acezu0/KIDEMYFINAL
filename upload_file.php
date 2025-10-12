<?php
// =======================================================
// 🟢 upload_file.php — Handles teacher file uploads
// =======================================================
require_once 'connect.php'; // your PDO connection
session_start();

// 🔒 Check if logged in and is a teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$teacher_id = $_SESSION['user']['id']; // UUID of the teacher

// =======================================================
// 🧩 Handle Upload Request
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['folder_id']) || !isset($_FILES['file'])) {
        echo json_encode(['success' => false, 'message' => 'Missing folder or file data.']);
        exit();
    }

    $folder_id = $_POST['folder_id']; // UUID
    $upload_dir = __DIR__ . '/uploads/';

    // Ensure the uploads directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['file'];
    $file_name = basename($file['name']);
    $file_tmp_path = $file['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // =======================================================
    // 🧠 Allowed file types (PDF, PPT, PPTX, Images)
    // =======================================================
    $allowed_ext = ['pdf', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, PPT, JPG, PNG.']);
        exit();
    }

    // Generate unique file name to prevent collisions
    $unique_name = uniqid('file_', true) . '.' . $file_ext;
    $file_path = $upload_dir . $unique_name;

    // Move uploaded file to /uploads directory
    if (move_uploaded_file($file_tmp_path, $file_path)) {
        try {
            // Store relative path (so it works even if you move the project)
            $relative_path = 'uploads/' . $unique_name;

            // =======================================================
            // 💾 Insert file record into DB
            // =======================================================
            $query = "INSERT INTO files (folder_id, file_name, file_path, uploaded_by)
                      VALUES (:folder_id, :file_name, :file_path, :uploaded_by)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':folder_id'   => $folder_id,
                ':file_name'   => $file_name,
                ':file_path'   => $relative_path,
                ':uploaded_by' => $teacher_id
            ]);

            echo json_encode(['success' => true, 'message' => 'File uploaded successfully!']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}