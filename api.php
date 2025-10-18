<?php
// final_api.php — unified backend for Kidemy Teacher Dashboard
session_start();
header("Content-Type: application/json");

// ✅ Ensure only logged-in teachers can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// ✅ Supabase connection details
// You can store these securely in an .env file or constants.php
define('SUPABASE_URL', 'https://YOUR-PROJECT-ID.supabase.co');
define('SUPABASE_KEY', 'YOUR-SERVICE-ROLE-KEY'); // Service key required for insert/update
define('SUPABASE_TABLE_COURSES', 'courses');
define('SUPABASE_TABLE_FOLDERS', 'folders');

// ✅ Helper function for Supabase REST calls
function supabase_request($method, $table, $params = [], $body = null) {
    $url = SUPABASE_URL . "/rest/v1/" . $table;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $opts = [
        "http" => [
            "method" => $method,
            "header" => [
                "Content-Type: application/json",
                "apikey: " . SUPABASE_KEY,
                "Authorization: Bearer " . SUPABASE_KEY,
                "Prefer: return=representation"
            ],
        ]
    ];

    if ($body) {
        $opts["http"]["content"] = json_encode($body);
    }

    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        return ["success" => false, "message" => "Supabase request failed."];
    }

    $data = json_decode($response, true);
    return ["success" => true, "data" => $data];
}

// ✅ Determine action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    /* --------------------------------------------------------------------------
     *  COURSES
     * -------------------------------------------------------------------------- */
    case 'get_courses':
        $teacherId = $_SESSION['user']['id'];
        $res = supabase_request('GET', SUPABASE_TABLE_COURSES, ['teacher_id' => "eq.$teacherId"]);
        if (!$res['success']) {
            echo json_encode(["success" => false, "message" => "Failed to fetch courses."]);
            exit;
        }
        echo json_encode(["success" => true, "courses" => $res['data']]);
        break;

    case 'create_course':
        $teacherId = $_SESSION['user']['id'];
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($title === '') {
            echo json_encode(["success" => false, "message" => "Course title is required."]);
            exit;
        }

        // Generate unique access code
        $accessCode = substr(strtoupper(bin2hex(random_bytes(3))), 0, 6);
        $course = [
            "teacher_id" => $teacherId,
            "title" => $title,
            "description" => $desc,
            "access_code" => $accessCode,
            "created_at" => date('c')
        ];

        $res = supabase_request('POST', SUPABASE_TABLE_COURSES, [], [$course]);
        if (!$res['success']) {
            echo json_encode(["success" => false, "message" => "Failed to create course."]);
            exit;
        }

        echo json_encode(["success" => true, "message" => "Course created successfully!", "course" => $res['data'][0]]);
        break;

    /* --------------------------------------------------------------------------
     *  FOLDERS (Lesson Manager)
     * -------------------------------------------------------------------------- */
    case 'get_folders':
        $teacherId = $_SESSION['user']['id'];
        $res = supabase_request('GET', SUPABASE_TABLE_FOLDERS, ['teacher_id' => "eq.$teacherId"]);
        if (!$res['success']) {
            echo json_encode(["success" => false, "message" => "Failed to fetch folders."]);
            exit;
        }
        echo json_encode(["success" => true, "folders" => $res['data']]);
        break;

    case 'create_folder':
        $teacherId = $_SESSION['user']['id'];
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            echo json_encode(["success" => false, "message" => "Folder name is required."]);
            exit;
        }

        $folder = [
            "teacher_id" => $teacherId,
            "name" => $name,
            "created_at" => date('c')
        ];

        $res = supabase_request('POST', SUPABASE_TABLE_FOLDERS, [], [$folder]);
        if (!$res['success']) {
            echo json_encode(["success" => false, "message" => "Failed to create folder."]);
            exit;
        }

        echo json_encode(["success" => true, "message" => "Folder created successfully!", "folder" => $res['data'][0]]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action."]);
        break;
}
