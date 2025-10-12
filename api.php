<?php
// =======================================================
// 🧠 api.php — Robust action parsing (supports snake/camel/case)
// =======================================================
require_once 'connect.php'; // returns $pdo (PDO connection)
session_start();

// Basic auth check for teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
$teacher_id = $_SESSION['user']['id']; // UUID

header('Content-Type: application/json');

// -----------------------------
// Normalize incoming action name
// -----------------------------
$rawAction = $_REQUEST['action'] ?? '';
// Lower-case, remove non-alphanumeric to normalize snake/camel/kebab etc.
$normalized = strtolower(preg_replace('/[^a-z0-9]/', '', $rawAction));

// Map normalized keys to canonical actions used below
$map = [
    'getfolders'   => 'getFolders',
    'get_folder'   => 'getFolders',
    'getfolders'   => 'getFolders',
    'getfiles'     => 'getFiles',
    'get_files'    => 'getFiles',
    'createfolder' => 'createFolder',
    'create_folder'=> 'createFolder',
    'deletefolder' => 'deleteFolder',
    'delete_folder'=> 'deleteFolder',
    'deletefile'   => 'deleteFile',
    'delete_file'  => 'deleteFile'
];

$action = $map[$normalized] ?? '';

// Small helper to respond and exit
function respond($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data);
    exit();
}

try {
    switch ($action) {

        // =======================================================
        // 📂 Get all folders for the current teacher
        // Endpoint examples:
        // api.php?action=getFolders
        // api.php?action=get_folders
        // =======================================================
        case 'getFolders':
            $query = "SELECT id, name, description, created_at
                      FROM folders
                      WHERE teacher_id = :teacher_id
                      ORDER BY created_at DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':teacher_id' => $teacher_id]);
            $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(['success' => true, 'folders' => $folders]);
            break;

        // =======================================================
        // 📁 Get files within a folder
        // Example: api.php?action=getFiles&folder_id=UUID
        // =======================================================
        case 'getFiles':
            $folder_id = $_GET['folder_id'] ?? $_REQUEST['folder_id'] ?? null;
            if (!$folder_id) respond(['success' => false, 'message' => 'Missing folder ID'], 400);

            $query = "SELECT id, file_name, file_path, uploaded_at, uploaded_by
                      FROM files
                      WHERE folder_id = :folder_id
                      ORDER BY uploaded_at DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':folder_id' => $folder_id]);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(['success' => true, 'files' => $files]);
            break;

        // =======================================================
        // 📘 Create a new folder
        // Accepts: JSON POST or Form POST (FormData) with either:
        // - name (or folder_name)
        // - description (optional)
        // =======================================================
        case 'createFolder':
            // Get data from JSON body OR from form fields
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) $input = [];

            // Merge with $_POST in case form-data or form submitted
            $name = $input['name'] ?? $input['folder_name'] ?? $_POST['name'] ?? $_POST['folder_name'] ?? null;
            $description = $input['description'] ?? $_POST['description'] ?? null;

            if (!$name || trim($name) === '') {
                respond(['success' => false, 'message' => 'Folder name is required'], 400);
            }

            $query = "INSERT INTO folders (name, description, teacher_id) VALUES (:name, :description, :teacher_id)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':name'       => trim($name),
                ':description'=> $description ?: null,
                ':teacher_id' => $teacher_id
            ]);

            respond(['success' => true, 'message' => 'Folder created successfully!']);
            break;

        // =======================================================
        // 🗑️ Delete a folder (and its files)
        // Example: api.php?action=deleteFolder&id=UUID
        // =======================================================
        case 'deleteFolder':
            $id = $_GET['id'] ?? $_REQUEST['id'] ?? null;
            if (!$id) respond(['success' => false, 'message' => 'Missing folder ID'], 400);

            // Delete the folder only if it belongs to this teacher
            $query = "DELETE FROM folders WHERE id = :id AND teacher_id = :teacher_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':id' => $id, ':teacher_id' => $teacher_id]);

            respond(['success' => true, 'message' => 'Folder deleted successfully!']);
            break;

        // =======================================================
        // 🗑️ Delete a file (removes file from disk and DB)
        // Example: api.php?action=deleteFile&id=UUID
        // =======================================================
        case 'deleteFile':
            $id = $_GET['id'] ?? $_REQUEST['id'] ?? null;
            if (!$id) respond(['success' => false, 'message' => 'Missing file ID'], 400);

            // Get file record
            $stmt = $pdo->prepare("SELECT file_path FROM files WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                respond(['success' => false, 'message' => 'File not found.'], 404);
            }

            $file_path = __DIR__ . '/' . $file['file_path'];
            if (file_exists($file_path)) {
                @unlink($file_path);
            }

            $delete = $pdo->prepare("DELETE FROM files WHERE id = :id");
            $delete->execute([':id' => $id]);

            respond(['success' => true, 'message' => 'File deleted successfully!']);
            break;

        default:
            respond(['success' => false, 'message' => 'Invalid or missing action'], 400);
    }
} catch (PDOException $e) {
    respond(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
}
