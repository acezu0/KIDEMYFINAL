<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
  header('Location: login.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teacher Dashboard | Kidemy</title>
<!-- Google Fonts: Poppins (Used for the Kidemy theme) -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght @300;400;600;700&display=swap" rel="stylesheet">
<!-- Ensure the link to your existing CSS remains if you have custom styles there -->
<link rel="stylesheet" href="kidemy.css"> 
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
    --light-green: #eaf8f4; /* Light background for inner cards */
    --text-color: #333;
    --border-radius: 12px;
    --shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* Sidebar Styles */
.sidebar {
  width: 240px;
  background-color: white; /* Changed to white background for modern look like screenshot */
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
    margin-top: auto; /* Push logout to the bottom */
    margin-bottom: 2rem;
    background-color: #f8d7da;
    color: #842029;
}
.sidebar a.logout:hover {
    background-color: #dc3545;
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

/* Manager Grid Layout (The main UI from the screenshot) */
.manager-grid {
    display: grid;
    grid-template-columns: 3fr 7fr; /* Left column narrower, right column wider */
    gap: 2rem;
}
.left-panel {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}


/* Card Styles */
.card {
  background: white;
  border-radius: var(--border-radius);
  padding: 1.5rem;
  box-shadow: var(--shadow);
}
.folder-input-card, .folder-list-card {
    background-color: var(--light-green); /* Light green background for left cards */
}

.input-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.input-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
    margin-bottom: 1rem;
}

/* Buttons */
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
.btn.add-btn {
    margin-bottom: 1.5rem;
}

/* Folder List Item Styles */
.folder-item {
    background-color: white;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: background-color 0.2s, transform 0.1s;
    border-left: 5px solid transparent;
}
.folder-item:hover {
    background-color: #fff;
    border-left: 5px solid #00c087; /* Brighter green hover border */
}
.folder-item.active {
    border-left: 5px solid var(--kidemy-green);
    background-color: #fff;
}
.folder-item h4 {
    margin: 0;
    font-weight: 600;
    color: var(--kidemy-green);
}
.folder-item small {
    color: #666;
    font-size: 0.8rem;
}


/* Course List View Styles (Used when view is 'courses') */
#courseList {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}
.course-card {
    border-left: 5px solid var(--kidemy-green);
    padding: 1.5rem;
}
.course-card h3 {
    color: var(--kidemy-green);
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
      margin-top: 50px; /* Space for the fixed menu button */
  }
  .manager-grid {
    grid-template-columns: 1fr; /* Stack columns */
    gap: 1.5rem;
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
      Hello, <b><?php echo htmlspecialchars($_SESSION['user']['name']); ?>!</b><br>
      Role: Teacher
  </div>
  <!-- Menu Items - Lesson Manager is active by default -->
  <a href="#" class="nav-link active" data-view="lessons" onclick="switchView('lessons', this)">📁 Lesson Manager</a>
  <a href="#" class="nav-link" data-view="courses" onclick="switchView('courses', this)">📘 My Courses</a>
  <a href="logout.php" class="logout">🚪 Sign Out</a>
</div>

<div class="main">
  
  <div class="header">
      <h1 id="mainTitle">Lesson Manager</h1>
      <div class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>!</div>
  </div>

  <!-- Lesson Manager View Container -->
  <div id="lessonManagerView" class="dashboard-view manager-grid">
    
    <div class="left-panel">
        <!-- 1. Create New Lesson Folder Card -->
        <div class="card folder-input-card">
            <h3>Create New Lesson Folder</h3>
            <div class="input-group">
                <label for="folderName">Folder/Lesson Name</label>
                <input type="text" id="folderName" placeholder="e.g., 'Algebra Unit 1'" maxlength="100">
                <button class="btn" onclick="createFolder()">Create Folder</button>
            </div>
        </div>
        
        <!-- 2. Lesson Folders List Card -->
        <div class="card folder-list-card">
            <h3>Lesson Folders</h3>
            <div id="folderList">
                <p style="color: #666; margin: 0;">Loading folders...</p>
            </div>
        </div>
    </div>
    
    <!-- 3. Folder Contents Card (Right Panel) -->
    <div class="right-panel">
        <div class="card" style="height: 100%; min-height: 400px;">
            <h3>Folder Contents</h3>
            <div id="folderContents">
                <p style="color: #666; font-style: italic;">Select a folder to see its contents, or create a new folder above.</p>
            </div>
        </div>
    </div>
  </div>

  <!-- Course View Container (Initially hidden) -->
  <div id="courseView" class="dashboard-view" style="display: none;">
    <button class="btn add-btn" id="addCourseBtn">➕ Add New Course</button>
    <div id="courseList">Loading courses...</div>
  </div>

</div>


<script>
// --- GLOBAL STATE & MOCK DATA ---
// Removed the mockFolders array. Folders will now load empty unless previously saved in localStorage.
const EMPTY_FOLDERS = []; 
let activeFolderId = null;
const TODO_STORAGE_KEY = 'kidemyTeacherFolders';

// --- UTILITIES AND DATA MANAGEMENT ---

function getFolders() {
    try {
        const stored = localStorage.getItem(TODO_STORAGE_KEY);
        // If nothing is stored, return the empty array, not mockFolders
        return stored ? JSON.parse(stored) : EMPTY_FOLDERS;
    } catch (e) {
        console.error("Error loading folders from localStorage:", e);
        return EMPTY_FOLDERS;
    }
}

function saveFolders(folders) {
    try {
        localStorage.setItem(TODO_STORAGE_KEY, JSON.stringify(folders));
    } catch (e) {
        console.error("Error saving folders to localStorage:", e);
    }
}

// --- UI HELPERS ---

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
}

/**
 * Switches the main content view between 'courses' and 'lessons'.
 * @param {string} viewName - 'courses' or 'lessons'.
 * @param {HTMLElement} element - The clicked navigation link element.
 */
function switchView(viewName, element) {
    // 1. Toggle Active Link
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    element.classList.add('active');

    // 2. Toggle View Containers
    const lessonView = document.getElementById('lessonManagerView');
    const courseView = document.getElementById('courseView');
    
    lessonView.style.display = viewName === 'lessons' ? 'grid' : 'none';
    courseView.style.display = viewName === 'courses' ? 'block' : 'none';

    // 3. Update Title
    document.getElementById('mainTitle').textContent = viewName === 'lessons' ? 'Lesson Manager' : 'My Courses';

    // 4. Load Data
    if (viewName === 'courses') {
        loadCourses();
    } else {
        renderFolderList();
    }
    
    // Close sidebar on mobile after switching
    if (window.innerWidth <= 900) {
        document.getElementById('sidebar').classList.remove('open');
    }
}

// --- LESSON MANAGER LOGIC (Mocked with LocalStorage) ---

function renderFolderList() {
    const container = document.getElementById('folderList');
    const folders = getFolders();
    container.innerHTML = '';

    if (folders.length === 0) {
        container.innerHTML = '<p style="color: #666; margin: 0;">No folders yet. Create your first one above!</p>';
        return;
    }

    folders.forEach(folder => {
        const div = document.createElement('div');
        div.className = `folder-item ${folder.id === activeFolderId ? 'active' : ''}`;
        div.innerHTML = `
            <h4 style="display: flex; align-items: center; gap: 5px;"><span style="font-size: 1.2rem;">&#128193;</span> ${folder.name}</h4>
            <small>Created: ${folder.created}</small>
        `;
        div.onclick = () => selectFolder(folder.id, folder.name);
        container.appendChild(div);
    });
}

function createFolder() {
    const input = document.getElementById('folderName');
    const name = input.value.trim();

    if (name === '') {
        alert('Please enter a folder name.');
        return;
    }

    const folders = getFolders();
    const newFolder = {
        id: Date.now().toString(),
        name: name,
        created: new Date().toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }) // Matches 10/11/2025 format
    };

    folders.unshift(newFolder); // Add to the top
    saveFolders(folders);
    input.value = '';
    renderFolderList();
    
    // Optionally select the newly created folder
    selectFolder(newFolder.id, newFolder.name);
}

function selectFolder(id, name) {
    activeFolderId = id;
    renderFolderList(); // Re-render to highlight the active folder

    const contentsDiv = document.getElementById('folderContents');
    contentsDiv.innerHTML = `
        <h4 style="color: var(--kidemy-green);">${name} Contents</h4>
        <p style="margin-bottom: 20px;">This is where the list of files and materials for the folder **${name}** would appear.</p>
        <button class="btn" style="background-color: #ffc107; color: var(--text-color);" onclick="uploadMaterial('${id}')">Upload Material to This Folder</button>
        <div style="margin-top: 20px; padding: 10px; border: 1px dashed #ccc; border-radius: 8px;">
            <p style="font-size: 0.9rem; margin: 0;">Mock File List (1 PDF, 2 DOCs):</p>
            <ul style="list-style-type: none; padding: 0; margin: 10px 0 0 0;">
                <li>&#128196; Reading Guide.pdf</li>
                <li>&#128195; Worksheet 1.docx</li>
                <li>&#128195; Homework Key.docx</li>
            </ul>
        </div>
    `;
}

function uploadMaterial(folderId) {
    // This function would open a real upload modal/form
    alert(`Initiating upload process for Folder ID: ${folderId}. 

(A real application would show a robust file upload form here.)`);
}

// --- COURSE MANAGEMENT LOGIC (From previous state) ---

async function loadCourses() {
    const container = document.getElementById('courseList');
    container.innerHTML = 'Loading courses...';

    try {
        const res = await fetch('api.php?action=get_courses', { credentials: 'include' });
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        
        const data = await res.json();

        if (!data.success) {
            container.innerHTML = `<p style="color: red;">Error loading courses: ${data.message || 'Unknown API error.'}</p>`;
            return;
        }

        if (.courses.length === 0) {
            container.innerHTML = '<p>No courses yet. Click “Add New Course” to start one!</p>';
            return;
        }

        container.innerHTML = ''; 
        data.courses.forEach(course => {
            const div = document.createElement('div');
            div.className = 'card course-card';
            div.innerHTML = `
                <h3 style="font-weight: 600;">${course.title}</h3>
                <p style="margin: 0.5rem 0 1rem;">${course.description || 'No description provided.'}</p>
                <small style="color: #666;">Access code: <b>${course.access_code}</b></small>
                <button class="btn" style="margin-top: 1rem;" onclick="openCourse(${course.id})">Open Course</button>
            `;
            container.appendChild(div);
        });
    } catch (error) {
        console.error("Fetch error:", error);
        container.innerHTML = `<p style="color: red;">Could not connect to the API. Check console for details.</p>`;
    }
}

document.getElementById('addCourseBtn').addEventListener('click', async () => {
    // NOTE: This uses the browser's native prompt/alert for simplicity, 
    // but should be replaced with a custom modal in a real application.
    const title = prompt('Enter course title:');
    if (!title || title.trim() === '') return;
    const desc = prompt('Enter description (optional):') || '';

    try {
        const formData = new FormData();
        formData.append('action', 'create_course');
        formData.append('title', title);
        formData.append('description', desc);

        const res = await fetch('api.php', { method: 'POST', body: formData, credentials: 'include' });
        const data = await res.json();
        
        // Using alert as a quick-fix for API interaction confirmation
        if (data.success) {
            alert(`Course created successfully: ${data.message}`);
        } else {
             alert(`Failed to create course: ${data.message}`);
        }
    } catch (error) {
         alert('Error connecting to the server.');
        console.error("Course creation error:", error);
    }
    
    loadCourses();
});

function openCourse(id) {
    window.location.href = 'teacher_course.php?id=' + id;
}

// --- STARTUP ---
document.addEventListener('DOMContentLoaded', () => {
    // Start by rendering the Lesson Manager (the default view)
    renderFolderList();

    // Set the initial active class on the Lesson Manager link
    document.querySelector('.nav-link[data-view="lessons"]').classList.add('active');
});
</script>
</body>
</html>