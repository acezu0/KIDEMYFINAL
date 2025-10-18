<?php
session_start();
// In a real application, you would initialize the session and check authentication here
// For canvas execution, we are mocking the session user based on the provided API code's assumptions.
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    // Mock user for testing if session is not set (useful in canvas environment)
    if (isset($_GET['mock_login'])) {
        $_SESSION['user'] = [
            'id' => 101, // Mock student ID
            'name' => 'Alex P. Keaton',
            'role' => 'student'
        ];
    } else {
        // Fallback if not mocking
        // header('Location: login.php');
        // exit;
    }
}
$student_name = $_SESSION['user']['name'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard | Kidemy</title>
<!-- Google Fonts: Poppins -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght(300;400;600;700)&display=swap" rel="stylesheet">
<style>
/* Base Styles */
body {
  margin: 0;
  font-family: "Poppins", sans-serif;
  background: #f4f6f9;
  color: #333;
}
:root {
    --kidemy-green: #006c4f;
    --light-green: #eaf8f4;
    --text-color: #333;
    --border-radius: 12px;
    --shadow: 0 4px 12px rgba(0,0,0,0.08);
    --warning-yellow: #ffc107;
    --success-green: #28a745;
    --danger-red: #dc3545;
}

/* Sidebar Styles (Same as Teacher Dashboard) */
.sidebar {
  width: 240px;
  background-color: white;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  color: var(--text-color);
  display: flex;
  flex-direction: column;
  z-index: 1000;
  transition: transform 0.3s ease-in-out;
  box-shadow: 2px 0 5px rgba(0,0,0,0.05);
}
.sidebar h2 {
  text-align: left;
  padding: 1rem 1.5rem;
  font-weight: 700;
  margin: 0;
  color: var(--kidemy-green);
  font-size: 1.5rem;
}
.sidebar .user-info {
    padding: 0.5rem 1.5rem 1.5rem;
    color: #666;
    font-size: 0.9rem;
    border-bottom: 1px solid #eee;
    margin-bottom: 1.5rem;
}
.sidebar a {
  color: var(--text-color);
  padding: 0.8rem 1.5rem;
  text-decoration: none;
  display: block;
  font-weight: 600;
  margin: 0 1rem;
  border-radius: 8px;
  transition: background-color 0.2s, color 0.2s;
}
.sidebar a.active, .sidebar a:hover {
  background-color: var(--kidemy-green);
  color: white;
}
.sidebar a.logout {
    margin-top: auto;
    margin-bottom: 2rem;
    background-color: #f8d7da;
    color: #842029;
}
.sidebar a.logout:hover {
    background-color: var(--danger-red);
    color: white;
}

/* Main Content Area */
.main {
  margin-left: 240px;
  padding: 0 2rem 2rem 2rem;
  transition: margin-left 0.3s ease-in-out;
}
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}
.header h1 {
    font-weight: 700;
    color: var(--text-color);
    margin: 0;
    font-size: 1.8rem;
}
.header .welcome-text {
    background-color: var(--light-green);
    color: var(--kidemy-green);
    padding: 8px 15px;
    border-radius: 6px;
    font-weight: 600;
}

/* Card Styles */
.card {
  background: white;
  border-radius: var(--border-radius);
  padding: 1.5rem;
  box-shadow: var(--shadow);
  margin-bottom: 1.5rem;
}
.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* Course Card Specifics */
.course-card {
    border-left: 5px solid var(--kidemy-green);
    transition: transform 0.2s ease-in-out;
    cursor: pointer;
}
.course-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}
.course-card h3 {
    color: var(--kidemy-green);
    margin-top: 0;
}
.course-card p {
    color: #555;
    font-size: 0.95rem;
}

/* Submission Table Styles */
.submission-card {
    padding: 0;
}
.submission-card table {
    width: 100%;
    border-collapse: collapse;
}
.submission-card th, .submission-card td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
.submission-card th {
    background-color: var(--light-green);
    color: var(--kidemy-green);
    font-weight: 600;
}
.submission-card tr:hover {
    background-color: #fcfcfc;
}
.status-badge {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
}
.status-unchecked { background-color: var(--warning-yellow); color: #856404; }
.status-checked { background-color: var(--success-green); color: white; }
.submission-action-btn {
    background-color: var(--danger-red);
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: background-color 0.2s;
}
.submission-action-btn:hover {
    background-color: #c82333;
}

/* Buttons and Inputs */
.btn {
  background-color: var(--kidemy-green);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.25rem;
  cursor: pointer;
  font-weight: 600;
  transition: background-color 0.2s, transform 0.1s;
}
.btn:hover {
  background-color: #018a66;
  transform: translateY(-1px);
}
.btn-secondary {
    background-color: #f0f0f0;
    color: var(--text-color);
}
.btn-secondary:hover {
    background-color: #e0e0e0;
}
.action-bar {
    display: flex;
    justify-content: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: none; /* Hidden by default */
    justify-content: center;
    align-items: center;
    z-index: 2000;
}
.modal-content {
    background: white;
    padding: 2rem;
    border-radius: var(--border-radius);
    width: 90%;
    max-width: 450px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    position: relative;
}
.modal-content input, .modal-content textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
    margin-bottom: 1rem;
    font-size: 1rem;
}
.modal-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 1.5rem;
    cursor: pointer;
    color: #999;
}
.modal-message {
    margin-top: 1rem;
    padding: 10px;
    border-radius: 6px;
    font-weight: 600;
    display: none;
}
.modal-message.success { background-color: #d4edda; color: #155724; }
.modal-message.error { background-color: #f8d7da; color: #721c24; }


/* Course Content Layout */
.course-content-grid {
    display: grid;
    grid-template-columns: 1fr 2fr; /* Folders on left, Files/Submission on right */
    gap: 2rem;
}
.folder-list-item {
    background-color: var(--light-green);
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: background-color 0.15s;
    border-left: 5px solid transparent;
    font-weight: 500;
}
.folder-list-item:hover {
    background-color: #dff0ea;
}
.folder-list-item.active {
    background-color: white;
    border-left: 5px solid var(--kidemy-green);
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.file-list-item {
    border-bottom: 1px solid #eee;
    padding: 10px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.file-list-item:last-child {
    border-bottom: none;
}


/* Mobile Adjustments */
.menu-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1001; 
    background: var(--kidemy-green);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 15px;
    font-size: 1.2rem;
    cursor: pointer;
}

 @media (max-width: 900px) {
  .sidebar { 
    transform: translateX(-240px);
  }
  .sidebar.open {
    transform: translateX(0);
  }
  .main { 
    margin-left: 0; 
    padding: 1rem; 
  }
  .menu-toggle {
    display: block; 
  }
  .header {
      margin-top: 50px;
      flex-direction: column;
      align-items: flex-start;
  }
  .header .welcome-text {
      margin-top: 10px;
  }
  .submission-card table {
      display: block;
      overflow-x: auto;
      white-space: nowrap;
      border-radius: var(--border-radius);
      border: 1px solid #eee;
  }
  .course-content-grid {
      grid-template-columns: 1fr; /* Stack columns */
  }
}
</style>
</head>
<body>

<!-- Mobile Menu Toggle Button -->
<button class="menu-toggle" onclick="toggleSidebar()">☰ Menu</button>

<div class="sidebar" id="sidebar">
  <h2>KIDEMY</h2>
  <div class="user-info">
      Hello, <b><?php echo htmlspecialchars($student_name); ?></b><br>
      Role: Student
  </div>
  <!-- Menu Items -->
  <a href="#" class="nav-link active" data-view="courses" onclick="switchView('courses', this)">📘 My Courses</a>
  <a href="#" class="nav-link" data-view="submissions" onclick="switchView('submissions', this)">📬 My Submissions</a>
  <a href="logout.php" class="logout">🚪 Sign Out</a>
</div>

<div class="main">
  
  <div class="header">
      <h1 id="mainTitle">My Courses</h1>
      <div class="welcome-text">Welcome back, <?php echo htmlspecialchars($student_name); ?>!</div>
  </div>

  <!-- 1. My Courses View -->
  <div id="courseView" class="dashboard-view" style="display: block;">
    <div class="action-bar">
        <button class="btn" onclick="openJoinCourseModal()">➕ Join New Course</button>
        <button class="btn btn-secondary" onclick="loadCourses()">🔄 Refresh Courses</button>
    </div>
    
    <div id="courseList" class="card-grid">
        <p style="padding: 1rem; text-align: center;">Loading courses...</p>
    </div>
  </div>
  
  <!-- 2. Course Content View (Hidden by default, used when a course card is clicked) -->
  <div id="courseContentView" class="dashboard-view" style="display: none;">
      <button class="btn btn-secondary" onclick="switchView('courses', document.querySelector('.nav-link[data-view="courses"]'))" style="margin-bottom: 1.5rem;">&#x2190; Back to My Courses</button>
      
      <div class="course-content-grid">
          
          <!-- Left Panel: Assignment Folders -->
          <div class="card">
              <h3 id="courseContentFoldersTitle">Course Folders</h3>
              <div id="courseFoldersList">
                  <p style="color: #666;">Select a course to view folders.</p>
              </div>
          </div>

          <!-- Right Panel: Folder Files and Submission -->
          <div class="card">
              <h3 id="courseContentFilesTitle">Folder Files & Submission</h3>
              <div id="folderFilesContent">
                  <p style="color: #666; font-style: italic;">Select a folder on the left to view required materials and submit your assignment.</p>
              </div>
          </div>
          
      </div>
  </div>


  <!-- 3. My Submissions View (Initially hidden) -->
  <div id="submissionView" class="dashboard-view" style="display: none;">
    <button class="btn btn-secondary" onclick="loadSubmissions()">🔄 Refresh Submissions</button>
    <div class="card submission-card" style="margin-top: 1.5rem;">
        <div id="submissionList">
            <!-- Table will be injected here -->
        </div>
    </div>
  </div>

</div>

<!-- Join Course Modal -->
<div id="joinCourseModal" class="modal-overlay">
    <div class="modal-content">
        <span class="modal-close" onclick="closeJoinCourseModal()">&times;</span>
        <h3>Join a Course</h3>
        <p>Enter the access code provided by your teacher.</p>
        <input type="text" id="accessCodeInput" placeholder="Enter Access Code (e.g., ABC-123)">
        <button class="btn" onclick="joinCourse()">Join Course</button>
        <div id="joinCourseMessage" class="modal-message" role="alert"></div>
    </div>
</div>

<!-- File Submission Modal -->
<div id="submissionModal" class="modal-overlay">
    <form class="modal-content" id="submissionForm" enctype="multipart/form-data">
        <span class="modal-close" onclick="closeSubmissionModal()">&times;</span>
        <h3 id="submissionModalTitle">Submit File to Folder</h3>
        <p>Select the file for this assignment.</p>
        
        <label for="fileUploadInput" style="display: block; font-weight: 600; margin-bottom: 5px;">Choose File</label>
        <input type="file" id="fileUploadInput" name="file" required style="margin-bottom: 1rem; border: none;">

        <input type="hidden" id="submissionFolderId" name="folder_id">
        <button type="submit" class="btn" id="uploadBtn">Upload Submission</button>
        <div id="submissionMessage" class="modal-message" role="alert"></div>
    </form>
</div>


<!-- Custom Confirmation Modal (Replaces alert/confirm) -->
<div id="confirmModal" class="modal-overlay">
    <div class="modal-content">
        <h3 id="confirmTitle">Confirm Action</h3>
        <p id="confirmMessage" style="margin-bottom: 1.5rem;">Are you sure you want to proceed?</p>
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button class="btn btn-secondary" onclick="closeConfirmModal(false)">Cancel</button>
            <button class="btn" style="background-color: var(--danger-red);" id="confirmActionBtn">Confirm</button>
        </div>
    </div>
</div>

<script>
// --- GLOBAL STATE ---
const API_URL = 'student_api.php';
let activeView = 'courses';
let confirmResolve = null; // For custom confirmation modal
let currentCourse = { id: null, title: null, folders: [] };
let activeFolder = { id: null, name: null };


// --- UTILITIES ---

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

/**
 * Custom confirmation dialog using a modal (replaces window.confirm).
 */
function customConfirm(title, message, confirmBtnText = 'Confirm') {
    return new Promise(resolve => {
        confirmResolve = resolve;
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMessage').textContent = message;
        
        const confirmBtn = document.getElementById('confirmActionBtn');
        confirmBtn.textContent = confirmBtnText;
        confirmBtn.onclick = () => closeConfirmModal(true);

        document.getElementById('confirmModal').style.display = 'flex';
    });
}

function closeConfirmModal(result) {
    document.getElementById('confirmModal').style.display = 'none';
    if (confirmResolve) {
        confirmResolve(result);
        confirmResolve = null;
    }
}

/**
 * Helper to show modal messages
 */
function showModalMessage(elementId, message, type) {
    const el = document.getElementById(elementId);
    el.textContent = message;
    el.className = `modal-message ${type}`;
    el.style.display = 'block';
    setTimeout(() => {
        el.style.display = 'none';
    }, 4000);
}

/**
 * Hides all main views.
 */
function hideAllViews() {
    document.querySelectorAll('.dashboard-view').forEach(view => {
        view.style.display = 'none';
    });
}

/**
 * Switches the main content view.
 */
function switchView(viewName, element) {
    hideAllViews(); // Hide all main views first
    
    // 1. Toggle Active Link in Sidebar
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    if (element) {
        element.classList.add('active');
        activeView = viewName;
    } else {
        // If switching to course content view, keep 'My Courses' link active
        document.querySelector('.nav-link[data-view="courses"]').classList.add('active');
        activeView = 'courseContent';
    }

    // 2. Toggle View Container and Load Data
    const titleElement = document.getElementById('mainTitle');

    if (viewName === 'courses') {
        document.getElementById('courseView').style.display = 'block';
        titleElement.textContent = 'My Courses';
        loadCourses();
    } else if (viewName === 'submissions') {
        document.getElementById('submissionView').style.display = 'block';
        titleElement.textContent = 'My Submissions';
        loadSubmissions();
    } else if (viewName === 'courseContent') {
        document.getElementById('courseContentView').style.display = 'block';
        titleElement.textContent = currentCourse.title || 'Course Content';
    }
    
    // Close sidebar on mobile
    if (window.innerWidth <= 900) {
        document.getElementById('sidebar').classList.remove('open');
    }
}


// --- 1. MY COURSES LOGIC ---

function openJoinCourseModal() {
    document.getElementById('joinCourseModal').style.display = 'flex';
    document.getElementById('accessCodeInput').value = '';
    document.getElementById('joinCourseMessage').style.display = 'none';
}

function closeJoinCourseModal() {
    document.getElementById('joinCourseModal').style.display = 'none';
}

async function loadCourses() {
    const container = document.getElementById('courseList');
    container.innerHTML = '<p style="padding: 1rem; text-align: center;">Fetching enrolled courses...</p>';

    try {
        const res = await fetch(`${API_URL}?action=get_enrolled_courses`, { credentials: 'include' });
        const data = await res.json();

        if (!data.success) {
            container.innerHTML = `<p style="color: var(--danger-red);">Error: ${data.message || 'Failed to load courses.'}</p>`;
            return;
        }

        if (data.courses.length === 0) {
            container.innerHTML = '<p style="padding: 1rem; text-align: center; color: #666;">You are not enrolled in any courses. Click "Join New Course" to get started!</p>';
            return;
        }

        container.innerHTML = '';
        data.courses.forEach(course => {
            const div = document.createElement('div');
            div.className = 'card course-card';
            div.onclick = () => viewCourseContent(course.id, course.title);
            div.innerHTML = `
                <h3 style="font-weight: 600;">&#128218; ${course.title}</h3>
                <p>${course.description || 'No description provided.'}</p>
                <small style="color: #666;">Taught by: <b>${course.teacher_name}</b></small>
            `;
            container.appendChild(div);
        });
        
    } catch (error) {
        console.error("Fetch error:", error);
        container.innerHTML = `<p style="color: var(--danger-red);">Connection error. Could not reach server API.</p>`;
    }
}

async function joinCourse() {
    const codeInput = document.getElementById('accessCodeInput');
    const code = codeInput.value.trim();
    if (!code) {
        showModalMessage('joinCourseMessage', 'Please enter an access code.', 'error');
        return;
    }

    const joinBtn = document.querySelector('#joinCourseModal .btn');
    joinBtn.disabled = true;
    joinBtn.textContent = 'Joining...';

    try {
        const formData = new FormData();
        formData.append('action', 'join_course');
        formData.append('access_code', code);

        const res = await fetch(API_URL, { method: 'POST', body: formData, credentials: 'include' });
        const data = await res.json();
        
        if (data.success) {
            showModalMessage('joinCourseMessage', `Successfully joined "${data.course.title}"!`, 'success');
            codeInput.value = '';
            setTimeout(() => {
                closeJoinCourseModal();
                loadCourses();
            }, 1000);
        } else {
            showModalMessage('joinCourseMessage', data.message || 'Failed to join course.', 'error');
        }
    } catch (error) {
        showModalMessage('joinCourseMessage', 'Network error. Could not join course.', 'error');
        console.error('Join course error:', error);
    } finally {
        joinBtn.disabled = false;
        joinBtn.textContent = 'Join Course';
    }
}


// --- 2. COURSE CONTENT LOGIC ---

async function viewCourseContent(courseId, courseTitle) {
    currentCourse.id = courseId;
    currentCourse.title = courseTitle;
    activeFolder.id = null;
    activeFolder.name = null;
    
    // Switch to the Course Content View
    switchView('courseContent', null); 
    
    // Load folders for the new course
    await fetchFolders(courseId, courseTitle);
}

async function fetchFolders(courseId, courseTitle) {
    const container = document.getElementById('courseFoldersList');
    document.getElementById('courseContentFoldersTitle').textContent = `${courseTitle} Folders`;
    container.innerHTML = '<p style="padding: 1rem; text-align: center; color: #666;">Loading folders...</p>';
    document.getElementById('folderFilesContent').innerHTML = '<p style="color: #666; font-style: italic;">Select a folder on the left to view required materials and submit your assignment.</p>';


    try {
        const res = await fetch(`${API_URL}?action=get_folders&course_id=${courseId}`, { credentials: 'include' });
        const data = await res.json();
        currentCourse.folders = data.success ? data.folders : [];

        if (!data.success) {
            container.innerHTML = `<p style="color: var(--danger-red);">Error: ${data.message || 'Failed to load folders.'}</p>`;
            return;
        }

        if (currentCourse.folders.length === 0) {
            container.innerHTML = '<p style="padding: 1rem; text-align: center; color: #666;">No assignments or folders have been posted for this course yet.</p>';
            return;
        }

        container.innerHTML = '';
        currentCourse.folders.forEach(folder => {
            const div = document.createElement('div');
            div.className = 'folder-list-item';
            div.innerHTML = `&#128193; ${folder.name}`;
            div.onclick = () => selectFolder(folder.id, folder.name);
            container.appendChild(div);
        });
        
    } catch (error) {
        console.error("Fetch folders error:", error);
        container.innerHTML = `<p style="color: var(--danger-red);">Connection error. Could not load course folders.</p>`;
    }
}

function selectFolder(folderId, folderName) {
    activeFolder.id = folderId;
    activeFolder.name = folderName;
    
    // Highlight active folder
    document.querySelectorAll('.folder-list-item').forEach(el => el.classList.remove('active'));
    document.querySelector(`.folder-list-item[onclick*="${folderId}"]`).classList.add('active');

    // Update title
    document.getElementById('courseContentFilesTitle').textContent = `Assignment: ${folderName}`;

    // Load files and render submission button
    fetchFiles(folderId);
}


async function fetchFiles(folderId) {
    const container = document.getElementById('folderFilesContent');
    container.innerHTML = '<p style="padding: 1rem; text-align: center; color: #666;">Loading required files...</p>';
    
    try {
        const res = await fetch(`${API_URL}?action=get_files&folder_id=${folderId}`, { credentials: 'include' });
        const data = await res.json();

        let html = '<h4 style="margin-top: 0; color: #555;">Required Materials</h4>';
        
        if (data.success && data.files.length > 0) {
            data.files.forEach(file => {
                // NOTE: We assume files are in a publicly accessible location based on file_path (e.g., /uploads/materials/...)
                html += `
                    <div class="file-list-item">
                        <span>&#128196; <b>${file.file_name}</b></span>
                        <a href="${file.file_path}" target="_blank" class="btn btn-secondary" style="padding: 5px 10px; font-weight: 500;">Download</a>
                    </div>
                `;
            });
        } else {
             html += '<p style="color: #666;">No required reading materials posted by the teacher.</p>';
        }

        // Add Submission Section
        html += `
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
                <h4 style="color: var(--kidemy-green);">Submit Your Work</h4>
                <p>Ready to turn in your assignment for <b>${activeFolder.name}</b>?</p>
                <button class="btn" onclick="openSubmissionModal(${folderId}, '${activeFolder.name}')">
                    &#128190; Upload Submission
                </button>
            </div>
        `;
        
        container.innerHTML = html;

    } catch (error) {
        console.error("Fetch files error:", error);
        container.innerHTML = `<p style="color: var(--danger-red);">Connection error. Could not load folder files.</p>`;
    }
}

function openSubmissionModal(folderId, folderName) {
    document.getElementById('submissionModal').style.display = 'flex';
    document.getElementById('submissionFolderId').value = folderId;
    document.getElementById('submissionModalTitle').textContent = `Submit File to: ${folderName}`;
    document.getElementById('submissionMessage').style.display = 'none';
    document.getElementById('fileUploadInput').value = ''; // Reset file input
}

function closeSubmissionModal() {
    document.getElementById('submissionModal').style.display = 'none';
}

document.getElementById('submissionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = e.target;
    const uploadBtn = document.getElementById('uploadBtn');
    
    if (document.getElementById('fileUploadInput').files.length === 0) {
        showModalMessage('submissionMessage', 'Please select a file to upload.', 'error');
        return;
    }

    // Disable button and show loading state
    uploadBtn.disabled = true;
    const originalText = uploadBtn.textContent;
    uploadBtn.textContent = 'Uploading...';
    
    try {
        const formData = new FormData(form);
        formData.append('action', 'upload_submission'); // Ensure API action is set
        
        const res = await fetch(API_URL, { method: 'POST', body: formData, credentials: 'include' });
        const data = await res.json();
        
        if (data.success) {
            showModalMessage('submissionMessage', data.message || 'File uploaded successfully!', 'success');
            // After successful upload, reload submissions list (and optionally close modal)
            setTimeout(() => {
                closeSubmissionModal();
                // Optionally switch to the submissions view to show the new entry
                // switchView('submissions', document.querySelector('.nav-link[data-view="submissions"]'));
            }, 1000);
        } else {
            showModalMessage('submissionMessage', data.message || 'File upload failed.', 'error');
        }
    } catch (error) {
        showModalMessage('submissionMessage', 'Network error during upload.', 'error');
        console.error('Submission error:', error);
    } finally {
        uploadBtn.disabled = false;
        uploadBtn.textContent = originalText;
    }
});


// --- 3. SUBMISSIONS LOGIC ---

async function loadSubmissions() {
    const container = document.getElementById('submissionList');
    container.innerHTML = '<p style="padding: 1rem; text-align: center; color: #666;">Fetching your submissions...</p>';

    try {
        const res = await fetch(`${API_URL}?action=get_submissions`, { credentials: 'include' });
        const data = await res.json();

        if (!data.success) {
            container.innerHTML = `<p style="color: var(--danger-red);">Error: ${data.message || 'Failed to load submissions.'}</p>`;
            return;
        }

        if (data.submissions.length === 0) {
            container.innerHTML = '<p style="padding: 1rem; text-align: center; color: #666;">You haven\'t submitted any files yet.</p>';
            return;
        }

        container.innerHTML = renderSubmissionTable(data.submissions);
        
    } catch (error) {
        console.error("Fetch error:", error);
        container.innerHTML = `<p style="color: var(--danger-red);">Connection error. Could not reach server API.</p>`;
    }
}

function renderSubmissionTable(submissions) {
    let tableHTML = `
        <table>
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Course / Assignment</th>
                    <th>Submitted On</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
    `;

    submissions.forEach(sub => {
        const statusClass = sub.checked == 1 ? 'status-checked' : 'status-unchecked';
        const statusText = sub.checked == 1 ? 'Checked' : 'Pending';
        const isChecked = sub.checked == 1;

        tableHTML += `
            <tr>
                <td>${sub.file_name}</td>
                <td><b>${sub.course_title}</b> / ${sub.folder_name}</td>
                <td>${new Date(sub.uploaded_at).toLocaleString()}</td>
                <td><span class="${statusClass} status-badge">${statusText}</span></td>
                <td>
                    <button 
                        class="submission-action-btn" 
                        onclick='deleteSubmission(${sub.id}, ${isChecked})'
                        ${isChecked ? 'disabled' : ''}
                        title="${isChecked ? 'Cannot delete checked submissions.' : 'Delete this submission.'}"
                    >
                        &#10006; Delete
                    </button>
                </td>
            </tr>
        `;
    });

    tableHTML += '</tbody></table>';
    return tableHTML;
}

async function deleteSubmission(submissionId, isChecked) {
    if (isChecked) {
        alert('Cannot delete a submission that has already been checked by the teacher.'); 
        return;
    }

    const confirmed = await customConfirm(
        `Delete Submission`,
        `Are you sure you want to delete this submission? This action cannot be undone.`,
        `Delete`
    );

    if (!confirmed) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete_submission');
        formData.append('submission_id', submissionId);

        const res = await fetch(API_URL, { method: 'POST', body: formData, credentials: 'include' });
        const data = await res.json();

        if (data.success) {
            alert(data.message);
            loadSubmissions();
        } else {
            alert(`Deletion failed: ${data.message}`);
        }
    } catch (error) {
        alert('Error connecting to the server.');
        console.error("Delete submission error:", error);
    }
}


// --- STARTUP ---
document.addEventListener('DOMContentLoaded', () => {
    // 1. Initial view load (My Courses)
    switchView('courses', document.querySelector('.nav-link[data-view="courses"]'));
});
</script>
</body>
</html>