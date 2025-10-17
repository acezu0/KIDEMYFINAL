<?php
// =======================================================
// 🟢 api.php — Teacher Dashboard Backend (FINAL)
// =======================================================
require_once 'connect.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// =======================================================
// 🔒 Authentication Check
// =======================================================
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

// =======================================================
// 🧩 MAIN ACTIONS
// =======================================================
try {
    switch ($action) {

        // =======================================================
        // 📚 1. Create New Course
        // =======================================================
        case 'create_course':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($title === '')
                respond(['success' => false, 'message' => 'Course title is required.'], 400);

            $stmt = $pdo->prepare("
                INSERT INTO courses (title, description, teacher_id, access_code, created_at)
                VALUES (:title, :desc, :tid, substr(md5(random()::text), 1, 8), NOW())
                RETURNING id, access_code, title, description, created_at
            ");
            $stmt->execute([':title' => $title, ':desc' => $description, ':tid' => $teacher_id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            respond(['success' => true, 'message' => 'Course created successfully!', 'course' => $course]);
            break;

        // =======================================================
        // 📖 2. Get Teacher’s Courses
        // =======================================================
        case 'get_courses':
            $stmt = $pdo->prepare("
                SELECT id, title, description, access_code, created_at
                FROM courses
                WHERE teacher_id = :tid
                ORDER BY created_at DESC
            ");
            $stmt->execute([':tid' => $teacher_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($courses)) {
                respond(['success' => true, 'message' => 'No courses found.', 'courses' => []]);
            }

            respond(['success' => true, 'courses' => $courses]);
            break;

        // =======================================================
        // 📁 3. Create Folder for a Course
        // =======================================================
        case 'create_folder':
            $course_id = $_POST['course_id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($course_id === '' || $name === '')
                respond(['success' => false, 'message' => 'Missing course_id or folder name.'], 400);

            // Verify teacher owns the course
            $check = $pdo->prepare("SELECT id FROM courses WHERE id = :cid AND teacher_id = :tid");
            $check->execute([':cid' => $course_id, ':tid' => $teacher_id]);
            if ($check->rowCount() === 0)
                respond(['success' => false, 'message' => 'You do not own this course.'], 403);

            $stmt = $pdo->prepare("
                INSERT INTO folders (course_id, name, description, teacher_id, created_at)
                VALUES (:cid, :name, :desc, :tid, NOW())
                RETURNING id, name, description, created_at
            ");
            $stmt->execute([
                ':cid' => $course_id,
                ':name' => $name,
                ':desc' => $description,
                ':tid' => $teacher_id
            ]);

            respond(['success' => true, 'message' => 'Folder created successfully!', 'folder' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            break;

        // =======================================================
        // 📂 4. Get Folders in a Course
        // =======================================================
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
            $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            respond(['success' => true, 'folders' => $folders]);
            break;

        // =======================================================
        // 📤 5. Upload File to a Folder
        // =======================================================
        case 'upload_file':
            if (!isset($_FILES['file']))
                respond(['success' => false, 'message' => 'No file uploaded.'], 400);

            $folder_id = $_POST['folder_id'] ?? '';
            if ($folder_id === '')
                respond(['success' => false, 'message' => 'Missing folder_id.'], 400);

            // Verify folder ownership
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

            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_name = basename($_FILES['file']['name']);
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

        // =======================================================
        // 📦 6. Get Files in a Folder
        // =======================================================
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

        // =======================================================
        // 🚫 Default Invalid Action
        // =======================================================
        default:
            respond(['success' => false, 'message' => 'Invalid or missing action.'], 400);
    }

} catch (PDOException $e) {
    respond(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
?>
