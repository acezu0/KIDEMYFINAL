<?php
// =======================================================
// 🧠 api.php — Unified API for Teacher Dashboard
// Handles folders, file uploads, courses, and enrollment
// =======================================================
require_once 'connect.php'; // must return $pdo
session_start();
header('Content-Type: application/json; charset=utf-8');

// =======================================================
// 🔒 Session & Role Check
// =======================================================
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in as a teacher.']);
    exit();
}

$teacher_id = $_SESSION['user']['id']; // UUID from session

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// Normalize action (accept camelCase, snake_case, etc.)
$raw = $_REQUEST['action'] ?? '';
$action = strtolower(preg_replace('/[^a-z0-9_]/', '', $raw));

try {
    switch ($action) {

        // ===================================================
        // 📁 1. Get all folders owned by teacher
        // ===================================================
        case 'get_folders':
        case 'getfolders':
            $stmt = $pdo->prepare("
                SELECT id, name, description, created_at
                FROM folders
                WHERE teacher_id = :tid
                ORDER BY created_at DESC
            ");
            $stmt->execute([':tid' => $teacher_id]);
            respond(['success' => true, 'folders' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ===================================================
        // 🗂️ 2. Create a folder
        // ===================================================
        case 'create_folder':
        case 'createfolder':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $name = trim($input['folder_name'] ?? $input['name'] ?? $_POST['folder_name'] ?? '');
            $description = trim($input['description'] ?? $_POST['description'] ?? '');

            if ($name === '') respond(['success' => false, 'message' => 'Folder name required.'], 400);

            $stmt = $pdo->prepare("
                INSERT INTO folders (name, description, teacher_id, created_at)
                VALUES (:n, :d, :tid, NOW())
            ");
            $stmt->execute([':n' => $name, ':d' => $description ?: null, ':tid' => $teacher_id]);
            respond(['success' => true, 'message' => 'Folder created successfully.']);
            break;

        // ===================================================
        // 📄 3. Get files inside a folder
        // ===================================================
        case 'get_files':
        case 'getfiles':
            $folder_id = $_GET['folder_id'] ?? $_POST['folder_id'] ?? '';
            if (!$folder_id) respond(['success' => false, 'message' => 'Missing folder ID.'], 400);

            $stmt = $pdo->prepare("
                SELECT id, file_name, file_path, uploaded_by, uploaded_at, mime_type
                FROM files
                WHERE folder_id = :fid
                ORDER BY uploaded_at DESC
            ");
            $stmt->execute([':fid' => $folder_id]);
            respond(['success' => true, 'files' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ===================================================
        // ⬆️ 4. Upload file into folder
        // ===================================================
        case 'upload_file':
        case 'uploadfile':
            $folder_id = $_POST['folder_id'] ?? '';
            if (!$folder_id) respond(['success' => false, 'message' => 'Missing folder ID.'], 400);
            if (empty($_FILES['file']['name'])) respond(['success' => false, 'message' => 'No file uploaded or upload error.'], 400);

            // Ensure upload directory exists
            $upload_dir = __DIR__ . '/uploads/' . $teacher_id . '/' . $folder_id;
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file = $_FILES['file'];
            $file_name = basename($file['name']);
            $target_path = $upload_dir . '/' . $file_name;
            $relative_path = 'uploads/' . $teacher_id . '/' . $folder_id . '/' . $file_name;

            $allowed = ['pdf', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'gif'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                respond(['success' => false, 'message' => 'Unsupported file type.'], 400);
            }

            if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                respond(['success' => false, 'message' => 'File upload failed.'], 500);
            }

            $stmt = $pdo->prepare("
                INSERT INTO files (folder_id, file_name, file_path, uploaded_by, uploaded_at, mime_type)
                VALUES (:fid, :fname, :fpath, :uid, NOW(), :mime)
            ");
            $stmt->execute([
                ':fid' => $folder_id,
                ':fname' => $file_name,
                ':fpath' => $relative_path,
                ':uid' => $teacher_id,
                ':mime' => $file['type'] ?? null
            ]);

            respond(['success' => true, 'message' => 'File uploaded successfully.']);
            break;

        // ===================================================
        // ❌ 5. Delete a file
        // ===================================================
        case 'delete_file':
        case 'deletefile':
            $id = $_GET['id'] ?? $_POST['id'] ?? '';
            if (!$id) respond(['success' => false, 'message' => 'Missing file ID.'], 400);

            $stmt = $pdo->prepare("SELECT file_path FROM files WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$file) respond(['success' => false, 'message' => 'File not found.'], 404);

            $abs_path = __DIR__ . '/' . $file['file_path'];
            if (file_exists($abs_path)) @unlink($abs_path);

            $pdo->prepare("DELETE FROM files WHERE id = :id")->execute([':id' => $id]);
            respond(['success' => true, 'message' => 'File deleted successfully.']);
            break;

        // ===================================================
        // 🧾 6. Delete a folder (and its files)
        // ===================================================
        case 'delete_folder':
        case 'deletefolder':
            $id = $_GET['id'] ?? $_POST['id'] ?? '';
            if (!$id) respond(['success' => false, 'message' => 'Missing folder ID.'], 400);

            // Delete files on disk + DB
            $stmt = $pdo->prepare("SELECT file_path FROM files WHERE folder_id = :fid");
            $stmt->execute([':fid' => $id]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $file) {
                $abs = __DIR__ . '/' . $file['file_path'];
                if (file_exists($abs)) @unlink($abs);
            }

            $pdo->prepare("DELETE FROM files WHERE folder_id = :fid")->execute([':fid' => $id]);
            $pdo->prepare("DELETE FROM folders WHERE id = :fid AND teacher_id = :tid")
                ->execute([':fid' => $id, ':tid' => $teacher_id]);

            respond(['success' => true, 'message' => 'Folder and its files deleted.']);
            break;

        // ===================================================
        // 🎓 7. Courses
        // ===================================================
        case 'get_courses':
        case 'getcourses':
            $stmt = $pdo->prepare("
                SELECT id, title, description, created_at
                FROM courses
                WHERE teacher_id = :tid
                ORDER BY created_at DESC
            ");
            $stmt->execute([':tid' => $teacher_id]);
            respond(['success' => true, 'courses' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'create_course':
        case 'createcourse':
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            if (!$title) respond(['success' => false, 'message' => 'Course title required.'], 400);

            $stmt = $pdo->prepare("
                INSERT INTO courses (title, description, teacher_id, created_at)
                VALUES (:t, :d, :tid, NOW())
            ");
            $stmt->execute([':t' => $title, ':d' => $description ?: null, ':tid' => $teacher_id]);
            respond(['success' => true, 'message' => 'Course created successfully.']);
            break;

        // ===================================================
        // 👩‍🎓 8. Enrollment
        // ===================================================
        case 'enroll_student':
        case 'enrollstudent':
            $email = $_POST['student_email'] ?? '';
            $course_id = $_POST['course_id'] ?? '';
            if (!$email || !$course_id)
                respond(['success' => false, 'message' => 'Missing student email or course ID.'], 400);

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :e AND role = 'student' LIMIT 1");
            $stmt->execute([':e' => $email]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$student) respond(['success' => false, 'message' => 'Student not found.'], 404);

            $stmt = $pdo->prepare("
                INSERT INTO enrollments (student_id, course_id, enrolled_at)
                VALUES (:sid, :cid, NOW())
                ON CONFLICT (student_id, course_id) DO NOTHING
            ");
            $stmt->execute([':sid' => $student['id'], ':cid' => $course_id]);
            respond(['success' => true, 'message' => 'Student enrolled successfully.']);
            break;

        case 'get_enrolled_students':
        case 'getenrolledstudents':
            $course_id = $_GET['course_id'] ?? $_POST['course_id'] ?? '';
            if (!$course_id) respond(['success' => false, 'message' => 'Missing course ID.'], 400);

            $stmt = $pdo->prepare("
                SELECT u.id, u.name, u.email, e.enrolled_at
                FROM enrollments e
                JOIN users u ON u.id = e.student_id
                WHERE e.course_id = :cid
                ORDER BY e.enrolled_at DESC
            ");
            $stmt->execute([':cid' => $course_id]);
            respond(['success' => true, 'students' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ===================================================
        // 🚫 Default
        // ===================================================
        default:
            respond(['success' => false, 'message' => 'Invalid or missing action.'], 400);
    }

} catch (PDOException $e) {
    respond(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
?>
