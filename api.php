<?php
require_once 'connect.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

/**
 * FINAL API — unified backend for teacher_dashboard.php
 * Supports:
 *   - create_course
 *   - get_courses
 *   - enroll_student
 *   - create_folder
 *   - get_folders
 *   - upload_file
 *   - get_files
 */

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$teacher_id = $_SESSION['user']['id'];
$action = strtolower(trim($_GET['action'] ?? $_POST['action'] ?? 'get_courses'));

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

try {
    switch ($action) {

        // ✅ Create a new course
        case 'create_course':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($title === '')
                respond(['success' => false, 'message' => 'Course title is required.'], 400);

            // Generate random access code
            $access_code = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);

            $stmt = $pdo->prepare("
                INSERT INTO courses (title, description, teacher_id, access_code, created_at)
                VALUES (:title, :desc, :tid, :code, NOW())
                RETURNING id, title, description, access_code
            ");
            $stmt->execute([
                ':title' => $title,
                ':desc' => $description,
                ':tid' => $teacher_id,
                ':code' => $access_code
            ]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            respond(['success' => true, 'message' => 'Course created successfully!', 'course' => $course]);
            break;

        // ✅ Get all courses for this teacher
        case 'get_courses':
            $stmt = $pdo->prepare("
                SELECT id, title, description, access_code, created_at
                FROM courses
                WHERE teacher_id = :tid
                ORDER BY created_at DESC
            ");
            $stmt->execute([':tid' => $teacher_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            respond(['success' => true, 'courses' => $courses]);
            break;

        // ✅ Enroll a student (optional extra feature)
        case 'enroll_student':
            $student_email = trim($_POST['student_email'] ?? '');
            $course_id = trim($_POST['course_id'] ?? '');

            if ($student_email === '' || $course_id === '')
                respond(['success' => false, 'message' => 'Student email and course are required.'], 400);

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND role = 'student'");
            $stmt->execute([':email' => $student_email]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student)
                respond(['success' => false, 'message' => 'Student not found.'], 404);

            $student_id = $student['id'];

            $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = :cid AND teacher_id = :tid");
            $stmt->execute([':cid' => $course_id, ':tid' => $teacher_id]);
            if ($stmt->rowCount() === 0)
                respond(['success' => false, 'message' => 'You do not own this course.'], 403);

            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (:sid, :cid)");
            $stmt->execute([':sid' => $student_id, ':cid' => $course_id]);

            respond(['success' => true, 'message' => 'Student enrolled successfully!']);
            break;

        // ✅ Create a folder under a course
        case 'create_folder':
            $course_id = $_POST['course_id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($course_id === '' || $name === '')
                respond(['success' => false, 'message' => 'Missing course_id or folder name.'], 400);

            $check = $pdo->prepare("SELECT id FROM courses WHERE id = :cid AND teacher_id = :tid");
            $check->execute([':cid' => $course_id, ':tid' => $teacher_id]);
            if ($check->rowCount() === 0)
                respond(['success' => false, 'message' => 'You do not own this course.'], 403);

            $stmt = $pdo->prepare("
                INSERT INTO folders (course_id, name, description, teacher_id, created_at)
                VALUES (:cid, :name, :desc, :tid, NOW())
                RETURNING id, name, description
            ");
            $stmt->execute([
                ':cid' => $course_id,
                ':name' => $name,
                ':desc' => $description,
                ':tid' => $teacher_id
            ]);
            $folder = $stmt->fetch(PDO::FETCH_ASSOC);

            respond(['success' => true, 'message' => 'Folder created successfully!', 'folder' => $folder]);
            break;

        // ✅ Get all folders for a course
        case 'get_folders':
            $course_id = $_GET['course_id'] ?? $_POST['course_id'] ?? '';
            if ($course_id === '')
                respond(['success' => false, 'message' => 'Missing course_id.'], 400);

            $stmt = $pdo->prepare("
                SELECT id, name, description, created_at
                FROM folders
                WHERE course_id = :cid
                ORDER BY created_at ASC
            ");
            $stmt->execute([':cid' => $course_id]);
            respond(['success' => true, 'folders' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ✅ Upload a file to a folder
        case 'upload_file':
            if (!isset($_FILES['file']))
                respond(['success' => false, 'message' => 'No file uploaded.'], 400);

            $folder_id = $_POST['folder_id'] ?? '';
            if ($folder_id === '')
                respond(['success' => false, 'message' => 'Missing folder_id.'], 400);

            // Check permission
            $check = $pdo->prepare("
                SELECT f.course_id 
                FROM folders f 
                JOIN courses c ON c.id = f.course_id 
                WHERE f.id = :fid AND c.teacher_id = :tid
            ");
            $check->execute([':fid' => $folder_id, ':tid' => $teacher_id]);
            $folder = $check->fetch(PDO::FETCH_ASSOC);

            if (!$folder)
                respond(['success' => false, 'message' => 'Unauthorized or invalid folder.'], 403);

            // Handle upload
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($_FILES['file']['name']));
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
                $stmt = $pdo->prepare("
                    INSERT INTO files (folder_id, course_id, file_name, file_path, uploaded_by, uploaded_at)
                    VALUES (:fid, :cid, :fname, :fpath, :tid, NOW())
                ");
                $stmt->execute([
                    ':fid' => $folder_id,
                    ':cid' => $folder['course_id'],
                    ':fname' => $file_name,
                    ':fpath' => 'uploads/' . $file_name,
                    ':tid' => $teacher_id
                ]);

                respond(['success' => true, 'message' => 'File uploaded successfully!']);
            } else {
                respond(['success' => false, 'message' => 'File upload failed.'], 500);
            }
            break;

        // ✅ Get all files in a folder
        case 'get_files':
            $folder_id = $_GET['folder_id'] ?? $_POST['folder_id'] ?? '';
            if ($folder_id === '')
                respond(['success' => false, 'message' => 'Missing folder_id.'], 400);

            $stmt = $pdo->prepare("
                SELECT id, file_name, file_path, uploaded_at 
                FROM files 
                WHERE folder_id = :fid
                ORDER BY uploaded_at DESC
            ");
            $stmt->execute([':fid' => $folder_id]);
            respond(['success' => true, 'files' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        default:
            respond(['success' => false, 'message' => 'Invalid or missing action.'], 400);
    }

} catch (PDOException $e) {
    respond(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
