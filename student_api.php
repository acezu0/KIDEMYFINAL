<?php
// =======================================================
// 🟢 student_api.php — Data source for student dashboard
// =======================================================
require_once 'connect.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// 🔒 Security: Only logged-in students can access this API
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['user']['id'];
$action = $_GET['action'] ?? '';

if ($action === 'get_dashboard_data') {
    try {
        // =======================================================
        // 📚 Fetch enrolled courses with teacher info
        // =======================================================
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

        // =======================================================
        // 🧮 Compute dashboard stats
        // =======================================================
        $coursesEnrolled = count($courses);
        $lessonsCompleted = rand(0, 10); // Placeholder value
        $assignmentsDue = rand(0, 5);    // Placeholder value
        $overallProgress = $coursesEnrolled > 0 ? rand(40, 95) : 0;

        // =======================================================
        // 🗓️ Example upcoming deadlines (placeholder)
        // =======================================================
        $deadlines = [];
        if ($coursesEnrolled > 0) {
            foreach ($courses as $c) {
                $deadlines[] = [
                    'name' => $c['title'] . ' - Assignment 1',
                    'due' => date('M d, Y', strtotime('+' . rand(2, 10) . ' days'))
                ];
            }
        }

        // =======================================================
        // ✅ Return JSON response
        // =======================================================
        echo json_encode([
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
        exit();

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        exit();
    }
}

// 🚫 Fallback for invalid or missing action
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit();
