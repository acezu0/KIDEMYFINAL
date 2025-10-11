<?php
session_start();
require_once 'connect.php'; // Ensure this provides $pdo connection

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

$user = $_SESSION['user'];
$teacher_id = $user['id'];
$action = $_REQUEST['action'] ?? '';

if (!$action) {
  echo json_encode(["success" => false, "message" => "No action specified."]);
  exit;
}

try {
  // 🔹 CREATE FOLDER
  if ($action === 'create_folder') {
    $folder_name = trim($_POST['folder_name'] ?? '');
    if ($folder_name === '') {
      echo json_encode(["success" => false, "message" => "Missing required fields."]);
      exit;
    }

    $stmt = $pdo->prepare("INSERT INTO folders (teacher_id, name, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$teacher_id, $folder_name]);

    echo json_encode(["success" => true, "message" => "Folder created successfully."]);
  }

  // 🔹 GET FOLDERS
  elseif ($action === 'get_folders') {
    $stmt = $pdo->prepare("SELECT id, name, created_at FROM folders WHERE teacher_id = ? ORDER BY created_at DESC");
    $stmt->execute([$teacher_id]);
    $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $folders]);
  }

  // 🔹 DELETE FOLDER
  elseif ($action === 'delete_folder') {
    $folder_id = $_GET['folder_id'] ?? '';
    if ($folder_id === '') {
      echo json_encode(["success" => false, "message" => "Missing folder ID."]);
      exit;
    }

    // Delete files first
    $pdo->prepare("DELETE FROM files WHERE folder_id = ?")->execute([$folder_id]);
    $pdo->prepare("DELETE FROM folders WHERE id = ? AND teacher_id = ?")->execute([$folder_id, $teacher_id]);

    echo json_encode(["success" => true, "message" => "Folder deleted successfully."]);
  }

  // 🔹 GET FILES IN FOLDER
  elseif ($action === 'get_files') {
    $folder_id = $_GET['folder_id'] ?? '';
    if ($folder_id === '') {
      echo json_encode(["success" => false, "message" => "Missing folder ID."]);
      exit;
    }

    $stmt = $pdo->prepare("SELECT id, file_name, file_url, uploaded_at FROM files WHERE folder_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$folder_id]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $files]);
  }

  // 🔹 UPLOAD FILE
  elseif ($action === 'upload_file') {
    if (!isset($_FILES['file']) || !isset($_POST['folder_id'])) {
      echo json_encode(["success" => false, "message" => "Missing required fields."]);
      exit;
    }

    $folder_id = $_POST['folder_id'];
    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
      echo json_encode(["success" => false, "message" => "File upload error."]);
      exit;
    }

    // Store file in local /uploads
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $file['name']);
    $target_path = $upload_dir . $filename;
    move_uploaded_file($file['tmp_name'], $target_path);

    $file_url = 'uploads/' . $filename;

    $stmt = $pdo->prepare("INSERT INTO files (folder_id, file_name, file_url, uploaded_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$folder_id, $file['name'], $file_url]);

    echo json_encode(["success" => true, "message" => "File uploaded successfully."]);
  }

  // 🔹 DELETE FILE
  elseif ($action === 'delete_file') {
    $file_id = $_GET['file_id'] ?? '';
    if ($file_id === '') {
      echo json_encode(["success" => false, "message" => "Missing file ID."]);
      exit;
    }

    // Delete physical file
    $stmt = $pdo->prepare("SELECT file_url FROM files WHERE id = ?");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($file && file_exists(__DIR__ . '/' . $file['file_url'])) {
      unlink(__DIR__ . '/' . $file['file_url']);
    }

    $pdo->prepare("DELETE FROM files WHERE id = ?")->execute([$file_id]);
    echo json_encode(["success" => true, "message" => "File deleted successfully."]);
  }

  else {
    echo json_encode(["success" => false, "message" => "Invalid action."]);
  }

} catch (Exception $e) {
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
