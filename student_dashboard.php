<?php
session_start();

// ✅ Check if user is logged in and is a student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <link rel="icon" href="/favicon.ico" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Student Dashboard</title>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://kit.fontawesome.com/c36cb32178.js" crossorigin="anonymous"></script>
  <style>
    :root {
      --primary-bg: #e8f9ed;
      --sidebar-dark: #1f3f37;
      --sidebar-light: #2c564a;
      --accent-green: #2ecc71;
      --accent-green-hover: #27ae60;
      --text-dark: #1f3f37;
      --text-muted: #6c757d;
      --card-light: #ffffff;
      --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      --border-radius: 12px;
    }

    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Inter', 'Segoe UI', Arial, sans-serif; background: var(--primary-bg); color: var(--text-dark); }
    .app { display: flex; min-height: 100vh; }

    .sidebar {
      width: 260px; background: var(--sidebar-dark); color: #fff;
      padding: 20px; display: flex; flex-direction: column; gap: 18px;
    }

    .greet { font-weight: 700; font-size: 18px; }
    .role { font-size: 13px; color: rgba(255,255,255,0.7); }
    .user-id-display { font-size: 11px; color: rgba(255,255,255,0.5); word-break: break-all; margin-top: 5px; }

    .nav { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }
    .btn {
      background: transparent; border: none; color: #fff; padding: 10px 12px;
      text-align: left; border-radius: 8px; cursor: pointer; font-weight: 600;
      transition: background 0.2s; display: flex; align-items: center; gap: 10px;
    }

    .btn:hover { background: var(--sidebar-light); }
    .btn.active { background: var(--accent-green); color: var(--text-dark); box-shadow: 0 4px 6px rgba(46, 204, 113, 0.3); }

    .main { flex: 1; padding: 28px; }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }

    .card { background: var(--card-light); padding: 18px; border-radius: var(--border-radius); box-shadow: var(--shadow); }

    .overview-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
    .metric-card { padding: 20px; border-radius: 12px; color: white; font-weight: 700; }
    #metric1 { background: #3498db; } #metric2 { background: #9b59b6; } #metric3 { background: #2ecc71; } #metric4 { background: #f1c40f; }
    .metric-value { font-size: 24px; margin-bottom: 5px; }
    .metric-label { font-size: 14px; opacity: 0.9; }

    .list-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-radius: 8px; margin-bottom: 8px; background: var(--primary-bg); border-left: 4px solid var(--accent-green); }
    small.muted { color: var(--text-muted); }

    /* ============================= */
    /*  File Grid Layout           */
    /* ============================= */
    .file-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 16px;
      margin-top: 10px;
    }

    .file-grid .file-card {
      background: #fff;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      padding: 12px;
      text-align: center;
      cursor: pointer;
      transition: 0.2s ease;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    .file-grid .file-card:hover {
      background: #e6ffe6;
      transform: translateY(-3px);
    }

    .file-card i {
      font-size: 30px;
      color: #16a34a;
    }

    /* ============================= */
    /* 🪟 File Preview Modal         */
    /* ============================= */
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      padding-top: 80px;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.7);
    }

    .modal-content {
      background: #fff;
      margin: auto;
      padding: 20px;
      border-radius: 16px;
      width: 80%;
      max-width: 800px;
      position: relative;
    }

    .close-btn {
      position: absolute;
      top: 12px;
      right: 16px;
      font-size: 24px;
      color: #555;
      cursor: pointer;
    }

    .close-btn:hover { color: #000; }

    .file-viewer {
      margin-top: 15px;
      width: 100%;
      height: 500px;
      background: #f8f9fa;
      border-radius: 8px;
      overflow: hidden;
    }

    .download-btn {
      display: inline-block;
      margin-top: 10px;
      background: #16a34a;
      color: #fff;
      padding: 10px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
    }

    .download-btn:hover { background: #128138; }
  </style>
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div style="font-size:20px; font-weight:bold; margin-bottom:10px;">
      <span style="color:var(--accent-green)">KID</span>EMY
    </div>
    <div>
      <div class="greet" id="greeting">Loading...</div>
      <div class="role" id="role">Authenticating...</div>
      <div class="user-id-display" id="userIdDisplay"></div>
    </div>
    <nav class="nav">
      <button class="btn active" data-view="dashboard">📚 My Dashboard</button>
      <button class="btn" data-view="courses">📖 My Courses</button>
      <button class="btn" data-view="files">📂 Course Files</button>
      <button class="btn" style="margin-top:15px;background:#c0392b !important;" id="logout-btn">⏻ Sign Out</button>
    </nav>
    <div style="margin-top:auto;font-size:12px;opacity:0.7">Supabase Connected</div>
  </aside>

  <main class="main">
    <div class="header">
      <h2 id="pageTitle">Student Dashboard</h2>
      <div class="card" style="padding:10px 14px; font-weight:600;">Welcome, <span id="userNameDisplay">Student</span>! 👋</div>
    </div>

    <!-- Metrics -->
    <div class="overview-grid">
      <div class="metric-card" id="metric1"><div class="metric-value" id="courses-enrolled">0</div><div class="metric-label">Courses Enrolled</div></div>
      <div class="metric-card" id="metric2"><div class="metric-value" id="lessons-completed">0</div><div class="metric-label">Lessons Completed</div></div>
      <div class="metric-card" id="metric3"><div class="metric-value" id="overall-progress">0%</div><div class="metric-label">Overall Progress</div></div>
      <div class="metric-card" id="metric4"><div class="metric-value" id="assignments-due">0</div><div class="metric-label">Assignments Due</div></div>
    </div>

    <section id="dashboardView">
      <div class="card">
        <h3>My Overview</h3>
        <p>This dashboard summarizes your course activity and files uploaded by teachers.</p>
      </div>
    </section>

    <section id="coursesView" style="display:none;">
      <div class="card">
        <h3>Enrolled Courses</h3>
        <div id="courseList"><small class="muted">Loading your enrolled courses...</small></div>
      </div>
    </section>

    <section id="filesView" style="display:none;">
      <div class="card">
        <div class="files-section">
          <h2>📁 My Files</h2>
          <div id="file-list" class="file-grid"></div>
        </div>

        <!-- 🪟 File Preview Modal -->
        <div id="fileModal" class="modal">
          <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 id="fileTitle"></h3>
            <div id="fileViewer" class="file-viewer"></div>
            <a id="downloadLink" href="#" target="_blank" class="download-btn">⬇ Download File</a>
          </div>
        </div>
      </div>
    </section>
  </main>
</div>

<script>
const user = <?= json_encode($user) ?>;
const greetingEl = document.getElementById('greeting');
const roleEl = document.getElementById('role');
const userIdDisplay = document.getElementById('userIdDisplay');
const userNameDisplay = document.getElementById('userNameDisplay');
const courseListEl = document.getElementById('courseList');

greetingEl.textContent = `Hello, ${user.name}!`;
roleEl.textContent = `Role: ${user.role}`;
userNameDisplay.textContent = user.name;
userIdDisplay.textContent = `User ID: ${user.id}`;

// ===============================
// 🔹 Sidebar Navigation
// ===============================
document.querySelectorAll('.btn[data-view]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.btn[data-view]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const view = btn.dataset.view;
    document.getElementById('dashboardView').style.display = view === 'dashboard' ? 'block' : 'none';
    document.getElementById('coursesView').style.display = view === 'courses' ? 'block' : 'none';
    document.getElementById('filesView').style.display = view === 'files' ? 'block' : 'none';
    if (view === 'courses') loadCourses();
    if (view === 'files') loadFiles();
  });
});

// ===============================
// 📁 Load Files
// ===============================
async function loadFiles() {
  const res = await fetch(`student_api.php?action=get_all_files_for_student`);
  const data = await res.json();
  const container = document.getElementById('file-list');
  container.innerHTML = '';

  if (!data.success || data.files.length === 0) {
    container.innerHTML = '<p>No files available in this folder.</p>';
    return;
  }

  data.files.forEach(f => {
    const card = document.createElement('div');
    card.className = 'file-card';
    card.innerHTML = `<i class="fa-solid fa-file"></i><p>${f.file_name}</p>`;
    card.onclick = () => openFileModal(f);
    container.appendChild(card);
  });
}

// ===============================
// 🪟 File Modal
// ===============================
const modal = document.getElementById('fileModal');
const viewer = document.getElementById('fileViewer');
const fileTitle = document.getElementById('fileTitle');
const downloadLink = document.getElementById('downloadLink');
const closeBtn = document.querySelector('.close-btn');

function openFileModal(file) {
  modal.style.display = 'block';
  fileTitle.textContent = file.file_name;
  downloadLink.href = file.file_path;
  const ext = file.file_name.split('.').pop().toLowerCase();
  viewer.innerHTML = '';

  if (['pdf'].includes(ext)) {
    viewer.innerHTML = `<iframe src="${file.file_path}" width="100%" height="100%" frameborder="0"></iframe>`;
  } else if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
    viewer.innerHTML = `<img src="${file.file_path}" style="width:100%;height:100%;object-fit:contain;">`;
  } else if (['mp4','webm'].includes(ext)) {
    viewer.innerHTML = `<video src="${file.file_path}" controls style="width:100%;height:100%;"></video>`;
  } else {
    viewer.innerHTML = `<p>Preview not available for this file type.</p>`;
  }
}

closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };

// ===============================
// 📖 Load Enrolled Courses
// ===============================
async function loadCourses() {
  courseListEl.innerHTML = '<small class="muted">Loading...</small>';
  try {
    const res = await fetch(`student_api.php?action=get_enrolled_courses`);
    const data = await res.json();
    if (!data.success || !data.courses.length) {
      courseListEl.innerHTML = '<small class="muted">You are not enrolled in any courses yet.</small>';
      return;
    }
    courseListEl.innerHTML = '';
    data.courses.forEach(c => {
      const item = document.createElement('div');
      item.className = 'list-item';
      item.innerHTML = `<strong>${escapeHtml(c.title)}</strong>`;
      courseListEl.appendChild(item);
    });
  } catch (err) {
    console.error(err);
    courseListEl.innerHTML = '<small class="muted">Error loading courses.</small>';
  }
}

// Escape HTML
function escapeHtml(text) {
  return text.replace(/[\"&'\/<>]/g, function (a) {
    return {
      '"': '&quot;',
      '&': '&amp;',
      "'": '&#39;',
      '/': '&#47;',
      '<': '&lt;',
      '>': '&gt;'
    }[a];
  });
}

// ===============================
// 🔸 Logout
// ===============================
function confirmLogout() {
  if (confirm('Are you sure you want to log out?')) {
    window.location.href = 'logout.php';
  }
}
document.getElementById('logout-btn').addEventListener('click', confirmLogout);
</script>
</body>
</html>
