<?php
session_start();

// ✅ Prevent duplicate session warning
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// ✅ Only teachers allowed
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
  header('Location: login.php');
  exit;
}

// ✅ Secure Supabase credentials (set once in connect.php)
require_once 'connect.php'; 

$supabase_url  = defined('SUPABASE_URL') ? SUPABASE_URL : '';
$supabase_anon = defined('SUPABASE_ANON_KEY') ? SUPABASE_ANON_KEY : '';

// ✅ Teacher info
$teacher_id   = $_SESSION['user']['id'] ?? '';
$teacher_name = htmlspecialchars($_SESSION['user']['name'] ?? 'Teacher');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Teacher Dashboard | Kidemy</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="kidemy.css">

<style>
body{margin:0;font-family:"Poppins",sans-serif;background:#f4f6f9;color:#333}
:root{--kidemy-green:#006c4f;--light-green:#eaf8f4;--text-color:#333;--border-radius:12px;--shadow:0 4px 12px rgba(0,0,0,0.08)}
.quick-access-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.qa-card{background:#fff;border-radius:12px;padding:12px;box-shadow:var(--shadow);display:flex;flex-direction:column;gap:8px}
.qa-card .meta{font-size:0.85rem;color:#666}
.recent-list{display:flex;flex-direction:column;gap:8px}
.recent-item{background:#fff;border-radius:10px;padding:10px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 3px rgba(0,0,0,0.04)}
.toast-container{position:fixed;right:20px;bottom:20px;z-index:2000;display:flex;flex-direction:column;gap:10px;align-items:flex-end}
.toast{min-width:220px;padding:12px 14px;border-radius:10px;color:#fff;font-weight:600;box-shadow:0 6px 18px rgba(0,0,0,0.12);transform:translateY(0);opacity:1;transition:transform .25s,opacity .25s}
.toast.success{background:linear-gradient(90deg,#28a745,#1c7a3a)}
.toast.error{background:linear-gradient(90deg,#dc3545,#b02a37)}
.toast.info{background:linear-gradient(90deg,#0d6efd,#084ea6)}
</style>
</head>

<body>
<!-- Sidebar -->
<button class="menu-toggle" onclick="toggleSidebar()">☰ Menu</button>

<div class="sidebar" id="sidebar">
  <h2>KIDEMY</h2>
  <div class="user-info">
    Hello, <b><?php echo $teacher_name; ?></b><br>
    Role: Teacher
  </div>
  <a href="#" class="nav-link active" data-view="lessons" onclick="switchView('lessons', this)">📁 Lesson Manager</a>
  <a href="#" class="nav-link" data-view="courses" onclick="switchView('courses', this)">📘 My Courses</a>
  <a href="logout.php" class="logout">🚪 Sign Out</a>
</div>

<div class="main">
  <div class="header">
    <h1 id="mainTitle">Lesson Manager</h1>
    <div class="welcome-text">Welcome, <?php echo $teacher_name; ?>!</div>
  </div>

  <!-- LESSON MANAGER -->
  <div id="lessonManagerView" class="dashboard-view manager-grid">
    <div class="left-panel">
      <div class="card folder-input-card">
        <h3>Create New Lesson Folder</h3>
        <input type="text" id="folderName" placeholder="e.g., Algebra Unit 1" maxlength="100">
        <select id="folderCourseSelect" style="width:100%;padding:10px;border-radius:6px;border:1px solid #ccc;margin-bottom:10px;">
          <option value="">Select course (optional)</option>
        </select>
        <button class="btn" onclick="createFolder()">Create Folder</button>
      </div>

      <div class="card folder-list-card">
        <h3>Lesson Folders</h3>
        <div id="folderList"><p style="color:#666;margin:0;">Loading folders...</p></div>
      </div>
    </div>

    <div class="right-panel">
      <div class="card" style="min-height:220px;">
        <h3>Folder Contents</h3>
        <div id="folderContents"><p style="color:#666;font-style:italic">Select a folder to see its contents.</p></div>
      </div>

      <div style="height:18px"></div>
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
          <h3 style="margin:0">Quick Access</h3>
          <small style="color:#666">Pinned & recent items</small>
        </div>
        <div class="quick-access-grid" id="quickAccessGrid"></div>
        <hr style="margin:12px 0;border:none;border-top:1px solid #eee" />
        <h4 style="margin:8px 0">Recently Edited</h4>
        <div class="recent-list" id="recentList"></div>
      </div>
    </div>
  </div>

  <!-- COURSE VIEW -->
  <div id="courseView" class="dashboard-view" style="display:none">
    <button class="btn add-btn" id="addCourseBtn" onclick="openCourseModal()">➕ Add New Course</button>
    <div id="courseList">Loading courses...</div>
  </div>
</div>

<!-- Course Creation Modal -->
<div id="courseModal" class="modal-overlay">
  <div class="modal-content">
    <span class="modal-close" onclick="closeCourseModal()">&times;</span>
    <h3>Create New Course</h3>
    <p>Create a course and generate an access code for students to join.</p>
    
    <input type="text" id="courseTitle" placeholder="Course Title (e.g., Mathematics 101)" required>
    <textarea id="courseDescription" placeholder="Course Description (optional)" rows="3"></textarea>
    
    <button class="btn" onclick="createCourse()">Create Course</button>
    <div id="courseMessage" class="modal-message" role="alert"></div>
  </div>
</div>

<!-- File Upload Modal -->
<div id="fileUploadModal" class="modal-overlay">
  <div class="modal-content">
    <span class="modal-close" onclick="closeFileUploadModal()">&times;</span>
    <h3 id="fileUploadTitle">Upload Files</h3>
    <p>Select files to upload to this folder (PDF, PPT, images, etc.)</p>
    
    <input type="file" id="fileUploadInput" multiple accept=".pdf,.ppt,.pptx,.doc,.docx,.jpg,.jpeg,.png,.gif">
    <input type="hidden" id="uploadFolderId">
    
    <button class="btn" onclick="uploadFiles()">Upload Files</button>
    <div id="fileUploadMessage" class="modal-message" role="alert"></div>
  </div>
</div>

<!-- Toasts -->
<div class="toast-container" id="toastContainer"></div>

<!-- ✅ API Script (No Supabase needed) -->
<script>
const TEACHER_ID = <?php echo json_encode($teacher_id); ?>;
const TEACHER_NAME = "<?php echo addslashes($teacher_name); ?>";
const API_URL = 'api.php';

// Global state
let activeFolderId = null;
let courses = [];

// --- Toast ---
function showToast(message, type='success', timeout=3000) {
  const container=document.getElementById('toastContainer');
  const div=document.createElement('div');
  div.className=`toast ${type}`;
  div.textContent=message;
  container.appendChild(div);
  setTimeout(()=>{div.style.opacity='0';div.style.transform='translateY(12px)';setTimeout(()=>container.removeChild(div),300);},timeout);
}

// --- Sidebar + View Switching ---
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');}
function switchView(v,el){
  document.querySelectorAll('.nav-link').forEach(l=>l.classList.remove('active'));
  if(el) el.classList.add('active');
  document.getElementById('lessonManagerView').style.display=v==='lessons'?'grid':'none';
  document.getElementById('courseView').style.display=v==='courses'?'block':'none';
  document.getElementById('mainTitle').textContent=v==='lessons'?'Lesson Manager':'My Courses';
  if(v==='courses') loadCourses(); else renderFolderList();
}

// --- Course Management ---
async function loadCourses(){
  const c=document.getElementById('courseList');
  c.innerHTML='Loading...';
  
  try {
    const response = await fetch(`${API_URL}?action=get_courses`, { credentials: 'include' });
    const data = await response.json();
    
    if (!data.success) {
      showToast('Error loading courses', 'error');
      c.innerHTML = '<p>Error loading courses.</p>';
      return;
    }
    
    courses = data.courses || [];
    
    if (!courses.length) {
      c.innerHTML='<p>No courses yet. Create your first course!</p>';
      return;
    }
    
    c.innerHTML='';
    courses.forEach(course=>{
      const d=document.createElement('div');
      d.className='card';
      d.innerHTML=`
        <h3>📚 ${course.title}</h3>
        <p>${course.description || 'No description provided.'}</p>
        <small>Access Code: <b style="color: var(--kidemy-green);">${course.access_code}</b></small>
        <div style="margin-top: 10px;">
          <button class="btn btn-secondary copy-code-btn" data-code="${course.access_code}" style="padding: 5px 10px; font-size: 0.8rem;">📋 Copy Code</button>
        </div>
      `;
      
      // Add event listener for copy code button
      d.querySelector('.copy-code-btn').addEventListener('click', function() {
        copyAccessCode(this.getAttribute('data-code'));
      });
      
      c.appendChild(d);
    });
    
    // Update course dropdown
    updateCourseDropdown();
    
  } catch (error) {
    console.error('Error loading courses:', error);
    showToast('Error loading courses', 'error');
    c.innerHTML = '<p>Error loading courses.</p>';
  }
}

function updateCourseDropdown() {
  const select = document.getElementById('folderCourseSelect');
  select.innerHTML = '<option value="">Select course (optional)</option>';
  if (courses && courses.length > 0) {
    courses.forEach(course => {
      const option = document.createElement('option');
      option.value = course.id;
      option.textContent = course.title;
      select.appendChild(option);
    });
  }
}

function copyAccessCode(code) {
  navigator.clipboard.writeText(code).then(() => {
    showToast(`Access code ${code} copied to clipboard!`, 'success');
  }).catch(() => {
    showToast('Failed to copy access code', 'error');
  });
}

// --- Course Modal ---
function openCourseModal() {
  document.getElementById('courseModal').style.display = 'flex';
  document.getElementById('courseTitle').value = '';
  document.getElementById('courseDescription').value = '';
  document.getElementById('courseMessage').style.display = 'none';
}

function closeCourseModal() {
  document.getElementById('courseModal').style.display = 'none';
}

async function createCourse() {
  const title = document.getElementById('courseTitle').value.trim();
  const description = document.getElementById('courseDescription').value.trim();
  
  if (!title) {
    showModalMessage('courseMessage', 'Please enter a course title.', 'error');
    return;
  }
  
  const createBtn = document.querySelector('#courseModal .btn');
  createBtn.disabled = true;
  createBtn.textContent = 'Creating...';
  
  try {
    const formData = new FormData();
    formData.append('action', 'create_course');
    formData.append('title', title);
    formData.append('description', description);
    
    const response = await fetch(API_URL, { 
      method: 'POST', 
      body: formData, 
      credentials: 'include' 
    });
    const data = await response.json();
    
    if (data.success) {
      showModalMessage('courseMessage', `Course "${data.course.title}" created successfully! Access code: ${data.course.access_code}`, 'success');
      setTimeout(() => {
        closeCourseModal();
        loadCourses();
      }, 2000);
    } else {
      showModalMessage('courseMessage', data.message || 'Failed to create course.', 'error');
    }
  } catch (error) {
    showModalMessage('courseMessage', 'Network error. Could not create course.', 'error');
    console.error('Create course error:', error);
  } finally {
    createBtn.disabled = false;
    createBtn.textContent = 'Create Course';
  }
}

function showModalMessage(elementId, message, type) {
  const el = document.getElementById(elementId);
  el.textContent = message;
  el.className = `modal-message ${type}`;
  el.style.display = 'block';
  setTimeout(() => {
    el.style.display = 'none';
  }, 4000);
}

// --- Folder Management ---
async function renderFolderList(){
  const fl=document.getElementById('folderList');
  fl.innerHTML = '<p style="color:#666;margin:0;">Loading folders...</p>';
  
  try {
    const response = await fetch(`${API_URL}?action=get_folders`, { credentials: 'include' });
    const data = await response.json();
    
    if (!data.success) {
      showToast('Error loading folders', 'error');
      fl.innerHTML = '<p>Error loading folders.</p>';
      return;
    }
    
    const folders = data.folders || [];
    
    if (!folders.length) {
      fl.innerHTML='<p>No folders yet. Create your first folder!</p>';
      return;
    }
    
    fl.innerHTML='';
    folders.forEach(f=>{
      const d=document.createElement('div');
      d.className='folder-item';
      d.innerHTML=`<h4>📁 ${f.name}</h4>`;
      d.onclick=()=>selectFolder(f.id,f.name);
      fl.appendChild(d);
    });
    
  } catch (error) {
    console.error('Error loading folders:', error);
    showToast('Error loading folders', 'error');
    fl.innerHTML = '<p>Error loading folders.</p>';
  }
}

async function createFolder(){
  const name=document.getElementById('folderName').value.trim();
  const course=document.getElementById('folderCourseSelect').value||null;
  
  if(!name){
    showToast('Enter folder name','error');
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('action', 'create_folder');
    formData.append('name', name);
    if (course) formData.append('course_id', course);
    
    const response = await fetch(API_URL, { 
      method: 'POST', 
      body: formData, 
      credentials: 'include' 
    });
    const data = await response.json();
    
    if (data.success) {
      document.getElementById('folderName').value='';
      showToast('Folder created','success');
      renderFolderList();
    } else {
      showToast(data.message || 'Error creating folder','error');
    }
  } catch (error) {
    console.error('Error creating folder:', error);
    showToast('Error creating folder','error');
  }
}

function selectFolder(id,name){
  activeFolderId=id;
  
  // Highlight active folder
  document.querySelectorAll('.folder-item').forEach(el => el.classList.remove('active'));
  event.target.closest('.folder-item').classList.add('active');
  
  renderFolderContents(id,name);
}

async function renderFolderContents(id,name){
  const c=document.getElementById('folderContents');
  c.innerHTML=`
    <h4>📁 ${name}</h4>
    <div style="margin-bottom: 15px;">
      <button class="btn" id="uploadBtn" data-folder-id="${id}" data-folder-name="${name}">📤 Upload Files</button>
    </div>
    <div id="fileList">Loading files...</div>
  `;
  
  // Add event listener to upload button
  document.getElementById('uploadBtn').addEventListener('click', function() {
    const folderId = this.getAttribute('data-folder-id');
    const folderName = this.getAttribute('data-folder-name');
    openFileUploadModal(folderId, folderName);
  });
  
  loadFiles(id);
}

async function loadFiles(id){
  const list=document.getElementById('fileList');
  list.innerHTML = '<p>Loading files...</p>';
  
  try {
    const response = await fetch(`${API_URL}?action=get_files&folder_id=${id}`, { credentials: 'include' });
    const data = await response.json();
    
    if (!data.success) {
      showToast('Error loading files','error');
      list.innerHTML = '<p>Error loading files.</p>';
      return;
    }
    
    const files = data.files || [];
    
    if (!files.length) {
      list.innerHTML='<p>No files yet. Upload some files!</p>';
      return;
    }
    
    list.innerHTML = files.map(f=>`
      <div class="file-item">
        <span>📄 <a href="${f.file_path}" target="_blank">${f.file_name}</a></span>
        <button class="btn btn-secondary delete-file-btn" data-file-id="${f.id}" data-file-name="${f.file_name}" style="padding: 5px 10px; font-size: 0.8rem;">🗑️ Delete</button>
      </div>
    `).join('');
    
    // Add event listeners for delete buttons
    list.querySelectorAll('.delete-file-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const fileId = this.getAttribute('data-file-id');
        const fileName = this.getAttribute('data-file-name');
        deleteFile(fileId, fileName);
      });
    });
    
  } catch (error) {
    console.error('Error loading files:', error);
    showToast('Error loading files','error');
    list.innerHTML = '<p>Error loading files.</p>';
  }
}

// --- File Upload Modal ---
function openFileUploadModal(folderId, folderName) {
  document.getElementById('fileUploadModal').style.display = 'flex';
  document.getElementById('fileUploadTitle').textContent = `Upload Files to: ${folderName}`;
  document.getElementById('uploadFolderId').value = folderId;
  document.getElementById('fileUploadInput').value = '';
  document.getElementById('fileUploadMessage').style.display = 'none';
}

function closeFileUploadModal() {
  document.getElementById('fileUploadModal').style.display = 'none';
}

async function uploadFiles() {
  const input = document.getElementById('fileUploadInput');
  const folderId = document.getElementById('uploadFolderId').value;
  
  if (!input.files.length) {
    showModalMessage('fileUploadMessage', 'Please select files to upload.', 'error');
    return;
  }
  
  const uploadBtn = document.querySelector('#fileUploadModal .btn');
  uploadBtn.disabled = true;
  uploadBtn.textContent = 'Uploading...';
  
  let successCount = 0;
  let errorCount = 0;
  
  try {
    for (const file of input.files) {
      const formData = new FormData();
      formData.append('action', 'upload_file');
      formData.append('folder_id', folderId);
      formData.append('file', file);
      
      const response = await fetch(API_URL, { 
        method: 'POST', 
        body: formData, 
        credentials: 'include' 
      });
      const data = await response.json();
      
      if (data.success) {
        successCount++;
      } else {
        errorCount++;
        console.error('Upload error for', file.name, ':', data.message);
      }
    }
    
    if (successCount > 0) {
      showModalMessage('fileUploadMessage', `${successCount} file(s) uploaded successfully!`, 'success');
      setTimeout(() => {
        closeFileUploadModal();
        loadFiles(folderId);
      }, 1500);
    }
    
    if (errorCount > 0) {
      showModalMessage('fileUploadMessage', `${errorCount} file(s) failed to upload.`, 'error');
    }
    
  } catch (error) {
    showModalMessage('fileUploadMessage', 'Network error during upload.', 'error');
    console.error('Upload error:', error);
  } finally {
    uploadBtn.disabled = false;
    uploadBtn.textContent = 'Upload Files';
  }
}

async function deleteFile(fileId, fileName) {
  if (!confirm(`Are you sure you want to delete "${fileName}"?`)) {
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('action', 'delete_file');
    formData.append('file_id', fileId);
    
    const response = await fetch(API_URL, { 
      method: 'POST', 
      body: formData, 
      credentials: 'include' 
    });
    const data = await response.json();
    
    if (data.success) {
      showToast('File deleted successfully!', 'success');
      loadFiles(activeFolderId);
    } else {
      showToast(data.message || 'Failed to delete file.', 'error');
    }
  } catch (error) {
    showToast('Error deleting file.', 'error');
    console.error('Delete file error:', error);
  }
}

// Initial load
document.addEventListener('DOMContentLoaded',()=>{
  renderFolderList();
  loadCourses();
});
</script>
</body>
</html>