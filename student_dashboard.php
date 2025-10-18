<?php
// =======================================================
// 🟢 student_api.php — Student Dashboard + File Submission (Final)
// =======================================================
require_once 'connect.php';
header('Content-Type: application/json; charset=utf-8');

// =======================================================
// 🔒 Authentication Check
// =======================================================
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['user']['id'];
$action = strtolower(trim($_GET['action'] ?? $_POST['action'] ?? ''));

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// =======================================================
// 📁 File Upload Helper
// =======================================================
function handleFileUpload($file, $targetDir = 'uploads/submissions/') {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK)
        return ['error' => 'Invalid file upload.'];

    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
    $targetPath = $targetDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath))
        return ['error' => 'Failed to move uploaded file.'];

    return ['path' => $targetPath, 'name' => $file['name']];
}

// =======================================================
// 🧩 Main Actions
// =======================================================
try {
    switch ($action) {

        // =======================================================
        // 🎟️ 1. Join a Course
        // =======================================================
        case 'join_course':
        case 'joincourse':
            $code = trim($_POST['access_code'] ?? $_GET['access_code'] ?? '');
            if ($code === '') respond(['success' => false, 'message' => 'Access code is required.'], 400);

            $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE access_code = :code LIMIT 1");
            $stmt->execute([':code' => $code]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$course) respond(['success' => false, 'message' => 'Invalid access code.'], 404);

            $check = $pdo->prepare("SELECT id FROM enrollments WHERE course_id = :cid AND student_id = :sid");
            $check->execute([':cid' => $course['id'], ':sid' => $student_id]);
            if ($check->fetch()) respond(['success' => false, 'message' => 'Already enrolled.'], 400);

            $pdo->prepare("INSERT INTO enrollments (course_id, student_id, enrolled_at) VALUES (:cid, :sid, NOW())")
                ->execute([':cid' => $course['id'], ':sid' => $student_id]);

            respond(['success' => true, 'message' => 'Successfully joined course!', 'course' => $course]);
            break;

        // =======================================================
        // 📚 2. Get Enrolled Courses
        // =======================================================
        case 'get_enrolled_courses':
        case 'getcourses':
            $stmt = $pdo->prepare("
                SELECT 
                    c.id, c.title, c.description, c.access_code, c.created_at,
                    u.name AS teacher_name
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                JOIN users u ON c.teacher_id = u.id
                WHERE e.student_id = :sid
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([':sid' => $student_id]);
            respond(['success' => true, 'courses' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // =======================================================
        // 📁 3. Get Folders inside a Course
        // =======================================================
        case 'get_folders':
        case 'getfolders':
            $course_id = $_GET['course_id'] ?? $_POST['course_id'] ?? '';
            if (!$course_id) respond(['success' => false, 'message' => 'Missing course_id'], 400);

            $check = $pdo->prepare("SELECT 1 FROM enrollments WHERE course_id = :cid AND student_id = :sid");
            $check->execute([':cid' => $course_id, ':sid' => $student_id]);
            if ($check->rowCount() === 0)
                respond(['success' => false, 'message' => 'Not enrolled in this course.'], 403);

            $stmt = $pdo->prepare("SELECT id, name, description, created_at FROM folders WHERE course_id = :cid ORDER BY created_at ASC");
            $stmt->execute([':cid' => $course_id]);
            respond(['success' => true, 'folders' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // =======================================================
        // 📄 4. Get Files inside a Folder
        // =======================================================
        case 'get_files':
        case 'getfiles':
            $folder_id = $_GET['folder_id'] ?? $_POST['folder_id'] ?? '';
            if (!$folder_id) respond(['success' => false, 'message' => 'Missing folder_id'], 400);

            $stmt = $pdo->prepare("
                SELECT f.course_id
                FROM folders f
                JOIN enrollments e ON e.course_id = f.course_id
                WHERE f.id = :fid AND e.student_id = :sid
            ");
            $stmt->execute([':fid' => $folder_id, ':sid' => $student_id]);
            if ($stmt->rowCount() === 0)
                respond(['success' => false, 'message' => 'Access denied.'], 403);

            $stmt = $pdo->prepare("
                SELECT id, file_name, file_path, uploaded_at, description
                FROM files 
                WHERE folder_id = :fid
                ORDER BY uploaded_at DESC
            ");
            $stmt->execute([':fid' => $folder_id]);
            respond(['success' => true, 'files' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // =======================================================
        // 🧷 5. Upload a Submission (Student Upload)
        // =======================================================
        case 'upload_submission':
            $folder_id = $_POST['folder_id'] ?? '';
            $file = $_FILES['file'] ?? null;
            if (!$folder_id || !$file) respond(['success' => false, 'message' => 'Missing folder_id or file.'], 400);

            // Verify folder access
            $stmt = $pdo->prepare("
                SELECT f.id AS folder_id, f.course_id 
                FROM folders f 
                JOIN enrollments e ON e.course_id = f.course_id 
                WHERE f.id = :fid AND e.student_id = :sid
            ");
            $stmt->execute([':fid' => $folder_id, ':sid' => $student_id]);
            $folder = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$folder) respond(['success' => false, 'message' => 'Invalid folder or not enrolled.'], 403);

            $upload = handleFileUpload($file);
            if (isset($upload['error'])) respond(['success' => false, 'message' => $upload['error']], 400);

            $insert = $pdo->prepare("
                INSERT INTO submissions (student_id, folder_id, file_name, file_url, uploaded_at)
                VALUES (:sid, :fid, :fname, :furl, NOW())
                RETURNING id
            ");
            $insert->execute([
                ':sid' => $student_id,
                ':fid' => $folder_id,
                ':fname' => $upload['name'],
                ':furl' => $upload['path']
            ]);
            $submission_id = $insert->fetchColumn();

            respond(['success' => true, 'message' => 'File uploaded successfully!', 'submission_id' => $submission_id]);
            break;

        // =======================================================
        // 📋 6. Get All Student Submissions
        // =======================================================
        case 'get_submissions':
            $stmt = $pdo->prepare("
                SELECT 
                    s.id, s.file_name, s.file_url, s.uploaded_at, s.checked,
                    f.name AS folder_name, c.title AS course_title
                FROM submissions s
                JOIN folders f ON s.folder_id = f.id
                JOIN courses c ON f.course_id = c.id
                WHERE s.student_id = :sid
                ORDER BY s.uploaded_at DESC
            ");
            $stmt->execute([':sid' => $student_id]);
            respond(['success' => true, 'submissions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // =======================================================
        // ❌ 7. Delete a Submission
        // =======================================================
        case 'delete_submission':
            $submission_id = $_POST['submission_id'] ?? '';
            if (!$submission_id) respond(['success' => false, 'message' => 'Missing submission_id'], 400);

            $stmt = $pdo->prepare("SELECT file_url, checked FROM submissions WHERE id = :sid AND student_id = :uid");
            $stmt->execute([':sid' => $submission_id, ':uid' => $student_id]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$file) respond(['success' => false, 'message' => 'Submission not found.'], 404);
            if ($file['checked']) respond(['success' => false, 'message' => 'Cannot delete checked submission.'], 403);

            if (file_exists($file['file_url'])) unlink($file['file_url']);
            $pdo->prepare("DELETE FROM submissions WHERE id = :sid")->execute([':sid' => $submission_id]);

            respond(['success' => true, 'message' => 'Submission deleted.']);
            break;

        // =======================================================
        // 🚫 Default Invalid Action
        // =======================================================
        default:
            respond(['success' => false, 'message' => 'Invalid or missing action parameter.'], 400);
    }

} catch (PDOException $e) {
    respond(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
?>