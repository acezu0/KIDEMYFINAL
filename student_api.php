<?php
// =======================================================
// 🟢 student_api.php — Unified Student Dashboard + Course Access
// =======================================================
require_once 'connect.php';
session_start();
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
$action = strtolower($_GET['action'] ?? $_POST['action'] ?? '');

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

try {
    switch ($action) {

        // =======================================================
        // 📊 1. Dashboard Overview (stats + courses + deadlines)
        // =======================================================
        case 'get_dashboard_data':
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.title,
                    c.description,
                    u.name AS teacher
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                JOIN users u ON c.teacher_id = u.id
                WHERE e.student_id = :sid
            ");
            $stmt->execute([':sid' => $student_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $coursesEnrolled = count($courses);
            $lessonsCompleted = rand(0, 10); // Placeholder
            $assignmentsDue = rand(0, 5);    // Placeholder
            $overallProgress = $coursesEnrolled > 0 ? rand(40, 95) : 0;

            // Generate sample upcoming deadlines
            $deadlines = [];
            foreach ($courses as $c) {
                $deadlines[] = [
                    'name' => $c['title'] . ' - Assignment 1',
                    'due' => date('M d, Y', strtotime('+' . rand(2, 10) . ' days'))
                ];
            }

            respond([
                'success' => true,
                'data' => [
                    'coursesEnrolled'   => $coursesEnrolled,
                    'lessonsCompleted'  => $lessonsCompleted,
                    'overallProgress'   => $overallProgress,
                    'assignmentsDue'    => $assignmentsDue,
                    'courses'           => $courses,
                    'deadlines'         => $deadlines
                ]
            ]);

        // =======================================================
        // 📚 2. Get Enrolled Courses
        // =======================================================
        case 'get_enrolled_courses':
            $stmt = $pdo->prepare("
                SELECT 
                    c.id, 
                    c.title, 
                    c.description, 
                    c.created_at, 
                    u.name AS teacher_name
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                JOIN users u ON c.teacher_id = u.id
                WHERE e.student_id = :sid
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([':sid' => $student_id]);
            respond(['success' => true, 'courses' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

        // =======================================================
        // 📁 3. Get Folders inside a Course
        // =======================================================
        case 'get_folders':
            $course_id = $_GET['course_id'] ?? $_POST['course_id'] ?? '';
            if (!$course_id) {
                respond(['success' => false, 'message' => 'Missing course_id'], 400);
            }

            $stmt = $pdo->prepare("
                SELECT id, name, description, created_at 
                FROM folders 
                WHERE course_id = :cid 
                ORDER BY created_at ASC
            ");
            $stmt->execute([':cid' => $course_id]);
            respond(['success' => true, 'folders' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

        // =======================================================
        // 📄 4. Get Files inside a Folder
        // =======================================================
        case 'get_files':
            $folder_id = $_GET['folder_id'] ?? $_POST['folder_id'] ?? '';
            if (!$folder_id) {
                respond(['success' => false, 'message' => 'Missing folder_id'], 400);
            }

            $stmt = $pdo->prepare("
                SELECT file_name, file_path, uploaded_at 
                FROM files 
                WHERE folder_id = :fid 
                ORDER BY uploaded_at DESC
            ");
            respond(['success' => true, 'files' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

        // =======================================================
        // 🗂 5. Get ALL Files across all Enrolled Courses
        // =======================================================
        case 'get_all_files_for_student':
            $stmt = $pdo->prepare("
                SELECT 
                    c.title AS course_title,
                    f.file_name,
                    f.file_path,
                    f.uploaded_at,
                    d.name AS folder_name
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                JOIN folders d ON d.course_id = c.id
                JOIN files f ON f.folder_id = d.id
                WHERE e.student_id = :sid
                ORDER BY c.title, f.uploaded_at DESC
            ");
            $stmt->execute([':sid' => $student_id]);
            respond(['success' => true, 'files' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

        // =======================================================
        // 🚫 Default Invalid Action
        // =======================================================
        default:
            respond(['success' => false, 'message' => 'Invalid or missing action'], 400);
    }

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ], 500);
}
?>
