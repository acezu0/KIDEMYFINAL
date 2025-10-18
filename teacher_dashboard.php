<?php
// teacher_dashboard.php
// Note: this file expects your session + user to be already set (like your original file).
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
  header('Location: login.php');
  exit;
}

// Provide your Supabase settings somewhere secure, e.g. in connect.php
// Example: define('SUPABASE_URL', 'https://xyz.supabase.co'); define('SUPABASE_ANON_KEY', 'public-anon-key');
require_once 'connect.php'; // should define SUPABASE_URL and SUPABASE_ANON_KEY

// Fallback check to avoid JS errors if not set
$supabase_url = defined('SUPABASE_URL') ? SUPABASE_URL : '';
$supabase_anon = defined('SUPABASE_ANON_KEY') ? SUPABASEeyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imd5aW9zZnJqc2Jya2NzeW54dGt2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTkyOTA0OTcsImV4cCI6MjA3NDg2NjQ5N30.Yc08sv62N1Xi3uKpD5bMjqC6s5LRlgmneDRk8AmqCCo_ANON_KEY : '';

// teacher info from session
$teacher_id = (int)($_SESSION['user']['id'] ?? 0);
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
/* --- keep your previous styles, plus toast & quick-access adjustments --- */
body{margin:0;font-family:"Poppins",sans-serif;background:#f4f6f9;color:#333}
:root{--kidemy-green:#006c4f;--light-green:#eaf8f4;--text-color:#333;--border-radius:12px;--shadow:0 4px 12px rgba(0,0,0,0.08)}
/* ... (paste the full CSS you already had) ... */
/* Additions for Quick Access grid & toasts */
.quick-access-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.qa-card{background:#fff;border-radius:12px;padding:12px;box-shadow:var(--shadow);display:flex;flex-direction:column;gap:8px}
.qa-card .meta{font-size:0.85rem;color:#666}
.recent-list{display:flex;flex-direction:column;gap:8px}
.recent-item{background:#fff;border-radius:10px;padding:10px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 3px rgba(0,0,0,0.04)}
/* Toast container */
.toast-container{position:fixed;right:20px;bottom:20px;z-index:2000;display:flex;flex-direction:column;gap:10px;align-items:flex-end}
.toast{min-width:220px;padding:12px 14px;border-radius:10px;color:#fff;font-weight:600;box-shadow:0 6px 18px rgba(0,0,0,0.12);transform:translateY(0);opacity:1;transition:transform .25s,opacity .25s}
.toast.success{background:linear-gradient(90deg,#28a745,#1c7a3a)}
.toast.error{background:linear-gradient(90deg,#dc3545,#b02a37)}
.toast.info{background:linear-gradient(90deg,#0d6efd,#084ea6)}
/* Keep rest of your CSS... */
</style>
</head>
<body>

<!-- Mobile Menu Toggle -->
<button class="menu-toggle" onclick="toggleSidebar()">☰ Menu</button>

<div class="sidebar" id="sidebar">
  <h2>KIDEMY</h2>
  <div class="user-info">
      Hello, <b><?php echo $teacher_name; ?>!</b><br>
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
        <div class="input-group">
          <label for="folderName">Folder/Lesson Name</label>
          <input type="text" id="folderName" placeholder="e.g., 'Algebra Unit 1'" maxlength="100">
          <select id="folderCourseSelect" style="width:100%;padding:10px;border-radius:6px;border:1px solid #ccc;margin-bottom:10px;">
            <option value="">Select course (optional)</option>
          </select>
          <button class="btn" onclick="createFolder()">Create Folder</button>
        </div>
      </div>

      <div class="card folder-list-card">
        <h3>Lesson Folders</h3>
        <div id="folderList">
          <p style="color:#666;margin:0;">Loading folders...</p>
        </div>
      </div>
    </div>

    <!-- RIGHT PANEL: Folder Contents + Quick Access -->
    <div class="right-panel">
      <div class="card" style="min-height:220px;">
        <h3>Folder Contents</h3>
        <div id="folderContents">
          <p style="color:#666;font-style:italic">Select a folder to see its contents, or create a new folder above.</p>
        </div>
      </div>

      <!-- Quick Access / Recently Edited design panel -->
      <div style="height:18px"></div>
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
          <h3 style="margin:0">Quick Access</h3>
          <small style="color:#666">Your pinned & recent items</small>
        </div>

        <div class="quick-access-grid" id="quickAccessGrid">
          <!-- JS will populate quick access cards here -->
        </div>

        <hr style="margin:12px 0;border:none;border-top:1px solid #eee" />
        <h4 style="margin:8px 0">Recently Edited</h4>
        <div class="recent-list" id="recentList">
          <!-- JS will populate recent files/folders -->
        </div>
      </div>
    </div>
  </div>

  <!-- COURSE VIEW -->
  <div id="courseView" class="dashboard-view" style="display:none">
    <button class="btn add-btn" id="addCourseBtn">➕ Add New Course</button>
    <div id="courseList">Loading courses...</div>
  </div>
</div>

<!-- Toasts -->
<div class="toast-container" id="toastContainer"></div>

<!-- Supabase CDN -->
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.min.js"></script>

<script>
// Injected server-side variables
const SUPABASE_URL = "<?php echo addslashes($supabase_url); ?>";
const SUPABASE_ANON_KEY = "<?php echo addslashes($supabase_anon); ?>";
const TEACHER_ID = <?php echo json_encode($teacher_id); ?>;
const TEACHER_NAME = "<?php echo addslashes($teacher_name); ?>";

// Basic guard
if (!SUPABASE_URL || !SUPABASE_ANON_KEY) {
  console.error('Supabase credentials not set. Please define SUPABASE_URL and SUPABASE_ANON_KEY in connect.php');
  // Optionally show toast
  showToast('Supabase credentials missing (server-side).', 'error');
}

const supabase = supabasejs.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

// --- Toast helper ---
function showToast(message, type = 'success', timeout = 3000) {
  const container = document.getElementById('toastContainer');
  const div = document.createElement('div');
  div.className = `toast ${type}`;
  div.textContent = message;
  container.appendChild(div);
  setTimeout(() => {
    div.style.opacity = '0';
    div.style.transform = 'translateY(12px)';
    setTimeout(() => container.removeChild(div), 300);
  }, timeout);
}

// --- Sidebar toggles & view switching ---
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open')}

function switchView(viewName, element){
  document.querySelectorAll('.nav-link').forEach(l=>l.classList.remove('active'));
  if (element) element.classList.add('active');
  document.getElementById('lessonManagerView').style.display = viewName === 'lessons' ? 'grid' : 'none';
  document.getElementById('courseView').style.display = viewName === 'courses' ? 'block' : 'none';
  document.getElementById('mainTitle').textContent = viewName === 'lessons' ? 'Lesson Manager' : 'My Courses';
  if (viewName === 'courses') loadCourses(); else renderFolderList();
  if (window.innerWidth <= 900) document.getElementById('sidebar').classList.remove('open');
}

// --- Data functions: Courses / Folders / Files ---

async function loadCourses() {
  const container = document.getElementById('courseList');
  container.innerHTML = 'Loading courses...';
  try {
    const { data, error } = await supabase
      .from('courses')
      .select('id,title,description,access_code,created_at')
      .eq('teacher_id', TEACHER_ID)
      .order('created_at', { ascending: false });
    if (error) throw error;
    if (!data || data.length === 0) {
      container.innerHTML = '<p>No courses yet. Click “Add New Course” to start one!</p>';
      populateCourseSelect([]);
      return;
    }
    container.innerHTML = '';
    populateCourseSelect(data);
    data.forEach(course => {
      const div = document.createElement('div');
      div.className = 'card course-card';
      div.innerHTML = `
        <h3 style="font-weight:600">${escapeHtml(course.title)}</h3>
        <p style="margin:0.5rem 0 1rem">${escapeHtml(course.description || 'No description provided.')}</p>
        <small style="color:#666">Access code: <b>${escapeHtml(course.access_code || '')}</b></small>
        <div style="margin-top:10px">
          <button class="btn" onclick="openCourse(${course.id})">Open Course</button>
          <button class="btn" style="background:#ffc107;color:#222;margin-left:8px" onclick="loadFoldersForCourse(${course.id})">View Folders</button>
        </div>
      `;
      container.appendChild(div);
    });
  } catch (err) {
    console.error(err);
    container.innerHTML = `<p style="color:red">Error loading courses. Check console.</p>`;
    showToast('Could not load courses', 'error');
  }
}

function populateCourseSelect(courses) {
  const sel = document.getElementById('folderCourseSelect');
  sel.innerHTML = '<option value="">Select course (optional)</option>';
  courses.forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.id;
    opt.textContent = c.title;
    sel.appendChild(opt);
  });
}

// Create course (prompt modal replaced by simple prompt for now)
document.getElementById('addCourseBtn').addEventListener('click', async () => {
  const title = prompt('Enter course title:');
  if (!title || !title.trim()) return;
  const description = prompt('Enter description (optional):') || '';
  try {
    const { data, error } = await supabase
      .from('courses')
      .insert([{ title: title.trim(), description: description.trim(), teacher_id: TEACHER_ID, access_code: generateAccessCode() }])
      .select()
      .single();
    if (error) throw error;
    showToast('Course created successfully', 'success');
    loadCourses();
    // update Quick Access
    refreshQuickAccess();
  } catch (err) {
    console.error(err);
    showToast('Failed to create course', 'error');
  }
});

function generateAccessCode() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let s = '';
  for (let i=0;i<6;i++) s += chars[Math.floor(Math.random()*chars.length)];
  return s;
}

function openCourse(id) {
  window.location.href = 'teacher_course.php?id=' + id;
}

// --- FOLDERS ---
async function renderFolderList() {
  const container = document.getElementById('folderList');
  container.innerHTML = 'Loading...';
  try {
    // Get all folders for this teacher (optionally show only recent)
    const { data, error } = await supabase
      .from('folders')
      .select('id,name,description,created_at,course_id')
      .eq('teacher_id', TEACHER_ID)
      .order('created_at', { ascending: false })
      .limit(50);
    if (error) throw error;
    if (!data || data.length === 0) {
      container.innerHTML = '<p style="color:#666;margin:0;">No folders yet. Create your first one above!</p>';
      return;
    }
    container.innerHTML = '';
    data.forEach(folder => {
      const div = document.createElement('div');
      div.className = `folder-item ${folder.id === activeFolderId ? 'active' : ''}`;
      div.innerHTML = `
        <h4 style="display:flex;align-items:center;gap:8px"><span style="font-size:1.2rem">&#128193;</span> ${escapeHtml(folder.name)}</h4>
        <small>Created: ${new Date(folder.created_at).toLocaleDateString()}</small>
      `;
      div.onclick = () => selectFolder(folder.id, folder.name);
      container.appendChild(div);
    });
  } catch (err) {
    console.error(err);
    container.innerHTML = '<p style="color:red">Failed to load folders.</p>';
    showToast('Failed to load folders', 'error');
  }
}

async function createFolder() {
  const name = document.getElementById('folderName').value.trim();
  const courseId = document.getElementById('folderCourseSelect').value || null;
  if (!name) { showToast('Please enter a folder name', 'error'); return; }
  try {
    const payload = { name, teacher_id: TEACHER_ID, course_id: courseId };
    const { data, error } = await supabase.from('folders').insert([payload]).select().single();
    if (error) throw error;
    document.getElementById('folderName').value = '';
    renderFolderList();
    showToast('Folder created', 'success');
    // show newly created folder contents
    selectFolder(data.id, data.name);
    refreshQuickAccess();
  } catch (err) {
    console.error(err);
    showToast('Could not create folder', 'error');
  }
}

let activeFolderId = null;
function selectFolder(id, name) {
  activeFolderId = id;
  renderFolderList();
  renderFolderContents(id, name);
}

async function loadFoldersForCourse(courseId) {
  try {
    const { data, error } = await supabase.from('folders').select('id,name,created_at').eq('course_id', courseId).order('created_at', { ascending:false });
    if (error) throw error;
    // show these in folderList temporarily
    const container = document.getElementById('folderList');
    container.innerHTML = '';
    data.forEach(folder => {
      const div = document.createElement('div');
      div.className = 'folder-item';
      div.innerHTML = `<h4>&#128193; ${escapeHtml(folder.name)}</h4><small>Created: ${new Date(folder.created_at).toLocaleDateString()}</small>`;
      div.onclick = () => selectFolder(folder.id, folder.name);
      container.appendChild(div);
    });
    showToast('Folders loaded for course', 'info');
  } catch (err) {
    console.error(err);
    showToast('Failed to load folders for course', 'error');
  }
}

// --- Folder contents & file uploads ---
async function renderFolderContents(folderId, folderName) {
  const contentsDiv = document.getElementById('folderContents');
  contentsDiv.innerHTML = `<h4 style="color:var(--kidemy-green)">${escapeHtml(folderName)} Contents</h4>
    <div style="margin-bottom:12px">Upload files to this folder</div>
    <input type="file" id="fileUpload" multiple style="margin-bottom:8px">
    <button class="btn" onclick="uploadMaterial()">Upload Selected Files</button>
    <div id="fileList" style="margin-top:16px"></div>
  `;
  loadFiles(folderId);
}

async function uploadMaterial() {
  const input = document.getElementById('fileUpload');
  if (!input || input.files.length === 0) { showToast('Please choose files to upload', 'error'); return; }
  if (!activeFolderId) { showToast('Select a folder first', 'error'); return; }

  const files = Array.from(input.files);
  showToast(`Uploading ${files.length} file(s)...`, 'info', 2000);

  try {
    for (const file of files) {
      // create a unique path
      const uniqueName = `${Date.now()}_${sanitizeFilename(file.name)}`;
      const { data:uploadData, error:uploadErr } = await supabase.storage.from('uploads').upload(uniqueName, file);
      if (uploadErr) throw uploadErr;

      // get public url (or you can set up signed urls)
      const { data: publicData } = supabase.storage.from('uploads').getPublicUrl(uniqueName);

      // record metadata in files table
      const { error: insertErr } = await supabase.from('files').insert([{
        folder_id: activeFolderId,
        course_id: null,
        file_name: file.name,
        file_path: publicData.publicUrl,
        uploaded_by: TEACHER_ID
      }]);

      if (insertErr) throw insertErr;
    }

    input.value = '';
    showToast('Upload(s) complete', 'success');
    loadFiles(activeFolderId);
    refreshQuickAccess();
  } catch (err) {
    console.error(err);
    showToast('File upload failed', 'error');
  }
}

async function loadFiles(folderId) {
  const listDiv = document.getElementById('fileList');
  listDiv.innerHTML = 'Loading files...';
  try {
    const { data, error } = await supabase.from('files').select('id,file_name,file_path,uploaded_at').eq('folder_id', folderId).order('uploaded_at', { ascending: false });
    if (error) throw error;
    if (!data || data.length === 0) {
      listDiv.innerHTML = '<p style="color:#666">No uploaded materials yet.</p>';
      return;
    }
    listDiv.innerHTML = '<ul style="list-style:none;padding:0;margin:0">' + data.map(f => `<li style="padding:8px 0">&#128196; <a href="${escapeHtml(f.file_path)}" target="_blank">${escapeHtml(f.file_name)}</a> <small style="color:#666;margin-left:8px">${new Date(f.uploaded_at).toLocaleString()}</small></li>`).join('') + '</ul>';
  } catch (err) {
    console.error(err);
    listDiv.innerHTML = '<p style="color:red">Failed to load files.</p>';
    showToast('Failed to load files', 'error');
  }
}

// --- Quick Access & Recently Edited UI ---
async function refreshQuickAccess() {
  try {
    // get some pinned/frequency items (for demo: top 6 recent folders + files)
    const { data:folders } = await supabase.from('folders').select('id,name,created_at').eq('teacher_id', TEACHER_ID).order('created_at', { ascending:false }).limit(4);
    const { data:files } = await supabase.from('files').select('id,file_name,file_path,uploaded_at').eq('uploaded_by', TEACHER_ID).order('uploaded_at', { ascending:false }).limit(6);

    const qa = document.getElementById('quickAccessGrid');
    qa.innerHTML = '';
    (folders||[]).forEach(f => {
      const card = document.createElement('div');
      card.className = 'qa-card';
      card.innerHTML = `<strong>${escapeHtml(f.name)}</strong><div class="meta">Folder · ${new Date(f.created_at).toLocaleDateString()}</div>`;
      card.onclick = () => selectFolder(f.id, f.name);
      qa.appendChild(card);
    });

    const recent = document.getElementById('recentList');
    recent.innerHTML = '';
    (files||[]).forEach(f => {
      const item = document.createElement('div');
      item.className = 'recent-item';
      item.innerHTML = `<div style="display:flex;gap:8px;align-items:center"><span style="font-size:1.2rem">&#128196;</span><div><div style="font-weight:600">${escapeHtml(f.file_name)}</div><div style="font-size:0.85rem;color:#666">${new Date(f.uploaded_at).toLocaleString()}</div></div></div><a href="${escapeHtml(f.file_path)}" target="_blank" class="btn" style="background:#f1f1f1;color:#222">Open</a>`;
      recent.appendChild(item);
    });
  } catch (err) {
    console.error(err);
  }
}

// --- Utilities ---
function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function sanitizeFilename(n){ return n.replace(/[^A-Za-z0-9_\-\.]/g,'_'); }
function activeOrNull(v){ return v ? v : null; }

// Initial load
document.addEventListener('DOMContentLoaded', () => {
  // initial content
  renderFolderList();
  loadCourses();
  refreshQuickAccess();
  document.querySelector('.nav-link[data-view="lessons"]').classList.add('active');
});
</script>
</body>
</html>
