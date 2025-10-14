<?php
// =======================================================
// 🧠 api.php — Unified API for Teacher Dashboard
// Handles folders, file uploads, courses, and enrollment
// =======================================================
require_once 'connect.php'; // must return $pdo (PDO)
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
                SELECT f.id, f.name, f.description, f.created_at, c.title AS course_title
                FROM folders f
                LEFT JOIN courses c ON f.course_id = c.id
                WHERE f.teacher_id = :tid
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([':tid' => $teacher_id]);
            respond(['success' => true, 'folders' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ===================================================
        // 🗂️ 2. Create Folder (linked to a Course)
        // ===================================================
        case 'create_folder':
        case 'createfolder':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $name = trim($input['folder_name'] ?? $_POST['folder_name'] ?? '');
            $description = trim($input['description'] ?? $_POST['description'] ?? '');
            $course_id = trim($input['course_id'] ?? $_POST['course_id'] ?? '');

            if ($name === '' || $course_id === '')
                respond(['success' => false, 'message' => 'Folder name and course are required.'], 400);

            // Verify teacher exists
            $verify = $pdo->prepare("SELECT id FROM users WHERE id = :tid AND role = 'teacher'");
            $verify->execute([':tid' => $teacher_id]);
            if ($verify->rowCount() === 0)
                respond(['success' => false, 'message' => 'Invalid teacher session.'], 403);

            // Validate course ownership
            $check = $pdo->prepare("SELECT id FROM courses WHERE id = :cid AND teacher_id = :tid");
            $check->execute([':cid' => $course_id, ':tid' => $teacher_id]);
            if ($check->rowCount() === 0)
                respond(['success' => false, 'message' => 'Invalid or unauthorized course.'], 400);

            $stmt = $pdo->prepare("
                INSERT INTO folders (name, description, teacher_id, course_id, created_at)
                VALUES (:n, :d, :tid, :cid, NOW())
                RETURNING id
            ");
            $stmt->execute([':n' => $name, ':d' => $description ?: null, ':tid' => $teacher_id, ':cid' => $course_id]);
            $folder_id = $stmt->fetchColumn();

            respond(['success' => true, 'message' => 'Folder created successfully.', 'folder_id' => $folder_id]);
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
        // ⬆️ 4. Upload file (merged upload_file.php logic)
        // ===================================================
        case 'upload_file':
        case 'uploadfile':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST')
                respond(['success' => false, 'message' => 'Invalid request method.'], 400);

            if (empty($_POST['folder_id']) || empty($_FILES['file']))
                respond(['success' => false, 'message' => 'Missing folder_id or file.'], 400);

            $folder_id = $_POST['folder_id'];

            // Verify folder ownership
            $stmt = $pdo->prepare("SELECT id, course_id FROM folders WHERE id = :fid AND teacher_id = :tid LIMIT 1");
            $stmt->execute([':fid' => $folder_id, ':tid' => $teacher_id]);
            $folder = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$folder)
                respond(['success' => false, 'message' => 'Folder not found or not owned by you.'], 403);

            $course_id = $folder['course_id'];
            $file = $_FILES['file'];

            if ($file['error'] !== UPLOAD_ERR_OK)
                respond(['success' => false, 'message' => 'File upload error (code ' . $file['error'] . ').'], 400);

            $originalName = basename($file['name']);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowed = ['pdf', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];

            if (!in_array($ext, $allowed))
                respond(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, PPT, PPTX, JPG, PNG.'], 400);

            // Prepare upload directory
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true))
                respond(['success' => false, 'message' => 'Unable to create uploads directory.'], 500);

            // Unique name
            $uniqueName = uniqid('file_', true) . '.' . $ext;
            $destination = $uploadDir . $uniqueName;
            $relativePath = 'uploads/' . $uniqueName;

            if (!move_uploaded_file($file['tmp_name'], $destination))
                respond(['success' => false, 'message' => 'Failed to move uploaded file.'], 500);

            // Insert into DB
            $stmt = $pdo->prepare("
                INSERT INTO files (folder_id, course_id, file_name, file_path, uploaded_by, uploaded_at)
                VALUES (:fid, :cid, :fname, :fpath, :uploaded_by, NOW())
            ");
            $stmt->execute([
                ':fid' => $folder_id,
                ':cid' => $course_id,
                ':fname' => $originalName,
                ':fpath' => $relativePath,
                ':uploaded_by' => $teacher_id
            ]);

            respond([
                'success' => true,
                'message' => 'File uploaded successfully!',
                'file_path' => $relativePath
            ]);
            break;

        // ===================================================
        // 🎓 5. Courses
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
        // 👩‍🎓 6. Enrollment (Reflects on Student Dashboard)
        // ===================================================
        case 'enroll_student_by_email':
            $course_id = $_POST['course_id'] ?? '';
            $email = trim($_POST['email'] ?? '');
            $teacher_id = $_SESSION['user']['id'];

            if (!$course_id || !$email) {
                respond(["success"=>false,"message"=>"Missing email or course ID."], 400);
            }

            // Find student by email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
            $stmt->execute([$email]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                respond(["success"=>false,"message"=>"No student found with that email."], 404);
            }

            // Check if already enrolled
            $check = $pdo->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
            $check->execute([$course_id, $student['id']]);
            if ($check->fetch()) {
                respond(["success"=>false,"message"=>"Student is already enrolled."]);
            }

            // Enroll the student
            $ins = $pdo->prepare("INSERT INTO enrollments (course_id, student_id) VALUES (?, ?)");
            $ins->execute([$course_id, $student['id']]);

            respond(["success"=>true,"message"=>"Student enrolled successfully."]);

        case 'get_enrolled_students':
            $course_id = $_GET['course_id'] ?? '';
            if (!$course_id) respond(['success' => false, 'message' => 'Missing course ID.'], 400);

            $stmt = $pdo->prepare("
                SELECT u.name, u.email
                FROM enrollments e
                JOIN users u ON e.student_id = u.id
                WHERE e.course_id = :cid
                ORDER BY u.name
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
