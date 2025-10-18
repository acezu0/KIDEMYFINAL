<?php
// Teacher API - Using local database like student_api.php
require_once 'connect.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$teacher_id = $_SESSION['user']['id'];
$action = strtolower(trim($_GET['action'] ?? $_POST['action'] ?? ''));

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

function handleFileUpload($file, $targetDir = 'uploads/materials/') {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK)
        return ['error' => 'Invalid file upload.'];

    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
    $targetPath = $targetDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath))
        return ['error' => 'Failed to move uploaded file.'];

    return ['path' => $targetPath, 'name' => $file['name']];
}

try {
    switch ($action) {
        case 'get_courses':
            $stmt = $pdo->prepare("
                SELECT id, title, description, access_code, created_at
                FROM courses 
                WHERE teacher_id = :tid
                ORDER BY created_at DESC
            ");
            $stmt->execute([':tid' => $teacher_id]);
            respond(['success' => true, 'courses' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'create_course':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if ($title === '') {
                respond(['success' => false, 'message' => 'Course title is required.'], 400);
            }

            // Generate unique access code
            $accessCode = substr(strtoupper(bin2hex(random_bytes(3))), 0, 6);
            
            $stmt = $pdo->prepare("
                INSERT INTO courses (teacher_id, title, description, access_code, created_at)
                VALUES (:tid, :title, :desc, :code, NOW())
                RETURNING id, title, description, access_code, created_at
            ");
            $stmt->execute([
                ':tid' => $teacher_id,
                ':title' => $title,
                ':desc' => $description,
                ':code' => $accessCode
            ]);
            
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            respond(['success' => true, 'message' => 'Course created successfully!', 'course' => $course]);
            break;

        case 'get_folders':
            $stmt = $pdo->prepare("
                SELECT f.id, f.name, f.course_id, f.created_at, c.title as course_title
                FROM folders f
                JOIN courses c ON f.course_id = c.id
                WHERE c.teacher_id = :tid
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([':tid' => $teacher_id]);
            respond(['success' => true, 'folders' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'create_folder':
            $name = trim($_POST['name'] ?? '');
            $courseId = $_POST['course_id'] ?? null;
            
            if ($name === '') {
                respond(['success' => false, 'message' => 'Folder name is required.'], 400);
            }

            // If no course_id provided, we need to create a folder without a course
            // But first verify the teacher owns the course if course_id is provided
            if ($courseId) {
                $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = :cid AND teacher_id = :tid");
                $stmt->execute([':cid' => $courseId, ':tid' => $teacher_id]);
                if (!$stmt->fetch()) {
                    respond(['success' => false, 'message' => 'Course not found or access denied.'], 403);
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO folders (name, course_id, created_at)
                VALUES (:name, :cid, NOW())
                RETURNING id, name, course_id, created_at
            ");
            $stmt->execute([
                ':name' => $name,
                ':cid' => $courseId
            ]);
            
            $folder = $stmt->fetch(PDO::FETCH_ASSOC);
            respond(['success' => true, 'message' => 'Folder created successfully!', 'folder' => $folder]);
            break;

        case 'get_files':
            $folderId = $_GET['folder_id'] ?? '';
            if (!$folderId) {
                respond(['success' => false, 'message' => 'Folder ID is required.'], 400);
            }

            $stmt = $pdo->prepare("
                SELECT id, file_name, file_path, uploaded_at, uploaded_by
                FROM files 
                WHERE folder_id = :fid
                ORDER BY uploaded_at DESC
            ");
            $stmt->execute([':fid' => $folderId]);
            respond(['success' => true, 'files' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'upload_file':
            $folderId = $_POST['folder_id'] ?? '';
            $file = $_FILES['file'] ?? null;
            
            if (!$folderId || !$file) {
                respond(['success' => false, 'message' => 'Folder ID and file are required.'], 400);
            }

            $upload = handleFileUpload($file);
            if (isset($upload['error'])) {
                respond(['success' => false, 'message' => $upload['error']], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO files (folder_id, file_name, file_path, uploaded_by, uploaded_at)
                VALUES (:fid, :fname, :fpath, :uid, NOW())
                RETURNING id, file_name, file_path, uploaded_at
            ");
            $stmt->execute([
                ':fid' => $folderId,
                ':fname' => $upload['name'],
                ':fpath' => $upload['path'],
                ':uid' => $teacher_id
            ]);
            
            $fileData = $stmt->fetch(PDO::FETCH_ASSOC);
            respond(['success' => true, 'message' => 'File uploaded successfully!', 'file' => $fileData]);
            break;

        case 'delete_file':
            $fileId = $_POST['file_id'] ?? '';
            if (!$fileId) {
                respond(['success' => false, 'message' => 'File ID is required.'], 400);
            }

            // First get file info to delete the actual file
            $stmt = $pdo->prepare("SELECT file_path FROM files WHERE id = :fid");
            $stmt->execute([':fid' => $fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                respond(['success' => false, 'message' => 'File not found.'], 404);
            }
            
            // Delete the actual file
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }

            // Delete the database record
            $stmt = $pdo->prepare("DELETE FROM files WHERE id = :fid");
            $stmt->execute([':fid' => $fileId]);
            
            respond(['success' => true, 'message' => 'File deleted successfully!']);
            break;

        default:
            respond(['success' => false, 'message' => 'Invalid action.'], 400);
    }

} catch (PDOException $e) {
    respond(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
?>