<?php
header('Content-Type: application/json');
require_once 'connect.php';
session_start();

// --- Security Check: Ensure user is a logged-in student ---
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid action.'];
$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user']['id'];

if ($action === 'get_dashboard_data') {
    try {
        // Query for enrolled courses count
        $stmt_courses = $pdo->prepare("SELECT COUNT(*) FROM public.enrollments WHERE student_id = :student_id");
        $stmt_courses->execute(['student_id' => $user_id]);
        $courses_enrolled = $stmt_courses->fetchColumn();

        // Query for completed lessons count (assuming completion is per course)
        $stmt_lessons = $pdo->prepare("
            SELECT COUNT(l.id) 
            FROM public.lessons l
            JOIN public.enrollments e ON l.course_id = e.course_id
            WHERE e.student_id = :student_id AND e.completed_at IS NOT NULL
        ");
        $stmt_lessons->execute(['student_id' => $user_id]);
        $lessons_completed = $stmt_lessons->fetchColumn();
        
        // For overall progress, we need total lessons in enrolled courses.
        $stmt_total_lessons = $pdo->prepare("
            SELECT COUNT(l.id)
            FROM public.lessons l
            JOIN public.enrollments e ON l.course_id = e.course_id
            WHERE e.student_id = :student_id
        ");
        $stmt_total_lessons->execute(['student_id' => $user_id]);
        $total_lessons = $stmt_total_lessons->fetchColumn();
        $overall_progress = $total_lessons > 0 ? round(($lessons_completed / $total_lessons) * 100) : 0;

        // Assignments Due (files not yet submitted by the student)
        $stmt_assignments = $pdo->prepare("
            SELECT COUNT(f.id)
            FROM public.files f
            WHERE f.folder_id IN (SELECT c.id FROM public.courses c JOIN public.enrollments e ON c.id = e.course_id WHERE e.student_id = :student_id)
            AND f.id NOT IN (SELECT s.file_id FROM public.submissions s WHERE s.student_id = :student_id)
        ");
        $stmt_assignments->execute(['student_id' => $user_id]);
        $assignments_due = $stmt_assignments->fetchColumn();

        // Upcoming Deadlines (not directly supported by schema, will return empty for now)
        $deadlines = [];

        // Enrolled Courses list
        $stmt_enrolled_courses = $pdo->prepare("
            SELECT c.title, u.name as teacher
            FROM public.courses c
            JOIN public.enrollments e ON c.id = e.course_id
            JOIN public.users u ON c.teacher_id = u.id
            WHERE e.student_id = :student_id
        ");
        $stmt_enrolled_courses->execute(['student_id' => $user_id]);
        $enrolled_courses = $stmt_enrolled_courses->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'success' => true,
            'data' => [
                'coursesEnrolled' => $courses_enrolled,
                'lessonsCompleted' => $lessons_completed,
                'overallProgress' => $overall_progress,
                'assignmentsDue' => $assignments_due,
                'deadlines' => $deadlines,
                'courses' => $enrolled_courses,
                'user' => $_SESSION['user']
            ]
        ];

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>