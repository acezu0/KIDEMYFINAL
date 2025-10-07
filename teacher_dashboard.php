<?php
session_start();
// Use the user data provided by the user (simulated login)
$_SESSION['user_id'] = 2; 
$_SESSION['user_name'] = 'Test Teacher';
$_SESSION['user_email'] = 'teacher@example.com';
$_SESSION['user_role'] = 'teacher';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Teacher Dashboard</title>
    <!-- Chart.js CDN for Analytics View -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Supabase JS CDN (v2) -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js/dist/umd/supabase.min.js"></script>
    <style>
        /* Green Color Palette - Matching Student Dashboard */
        :root {
            --primary-bg: #e8f9ed; /* Very light green/white background */
            --sidebar-dark: #1f3f37; /* Dark forest green for sidebar */
            --sidebar-light: #2c564a; /* Slightly lighter shade for hover/active */
            --accent-green: #2ecc71; /* Bright primary green */
            --accent-green-hover: #27ae60;
            --text-dark: #1f3f37;
            --text-muted: #6c757d;
            --card-light: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --border-radius: 12px;
        }

        /* Base Styles */
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', 'Segoe UI', system-ui, Arial, sans-serif; background: var(--primary-bg); color: var(--text-dark); }
        .app { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-dark); 
            color: #fff; 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            gap: 18px; 
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1); 
            position: sticky;
            top: 0;
            left: 0;
        }
        .greet { font-weight: 700; font-size: 18px; }
        .role { font-size: 13px; color: rgba(255, 255, 255, 0.7); }
        .nav { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }
        
        .btn {
            background: transparent;
            border: none;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn:hover { background: var(--sidebar-light); }
        .btn.active { 
            background: var(--accent-green); 
            color: var(--text-dark);
            box-shadow: 0 4px 6px rgba(46, 204, 113, 0.3);
            font-weight: bold;
        }

        /* Main Content */
        .main { flex: 1; padding: 28px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        
        /* Card styling */
        .card { 
            background: var(--card-light); 
            padding: 18px; 
            border-radius: var(--border-radius); 
            box-shadow: var(--shadow);
        }

        /* Dashboard Overview Grid (Metric Cards) */
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .metric-card {
            padding: 20px;
            border-radius: 12px;
            color: white;
            font-weight: 700;
        }
        /* Specific Card Styles (Matching Student Colors) */
        #metric1 { background-color: #f77085; } /* Pink - Total Students */
        #metric2 { background-color: #aa85f7; } /* Lavender - Total Lessons */
        #metric3 { background-color: var(--accent-green); } /* Primary Green - Avg Completion */
        #metric4 { background-color: #f9d55f; } /* Yellow - Files Pending Review */
        .metric-value { font-size: 24px; margin-bottom: 5px; }
        .metric-label { font-size: 14px; opacity: 0.9; }

        /* Layout for Analytics/Reports View */
        .content-area { display: flex; gap: 20px; margin-top: 20px; }
        .content-area > .left { width: 360px; }
        .content-area > .right { flex: 1; }
        
        /* List Styles */
        .list-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 10px; 
            border-radius: 8px; 
            margin-bottom: 8px; 
            background: var(--primary-bg);
            cursor: pointer;
            border-left: 4px solid var(--accent-green);
            transition: background 0.2s;
        }
        .list-item:hover { background: #d7f5df; } 

        /* Form Styles */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; color: var(--text-dark); }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: #fcfcfc;
        }
        .form-row { display: flex; gap: 10px; }
        .form-row input[type="file"] { border: none; padding: 0; background: none; }

        /* Helpers */
        small.muted { color: var(--text-muted); }
        button:not(.btn) { 
            background: var(--accent-green); 
            color: white; 
            border: none; 
            padding: 8px 15px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: 600;
            transition: background 0.2s, transform 0.1s;
        }
        button:hover:not(.btn) { background: var(--accent-green-hover); }
        button:active:not(.btn) { transform: scale(0.98); }

        /* Media Queries for Responsiveness */
        @media (max-width: 1024px) {
            .overview-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 880px) {
            .sidebar { display: none; }
            .app { flex-direction: column; }
            .content-area { flex-direction: column; }
            .content-area > .left { width: 100%; }
        }
        @media (max-width: 600px) {
            .overview-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Sidebar - Redesigned to match student aesthetic -->
        <aside class="sidebar">
            <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">
                <span style="color: var(--accent-green);">KID</span>EMY
            </div>
            <div>
                <div class="greet" id="greeting">Hello, Teacher</div>
                <div class="role" id="role">Role: teacher</div>
            </div>

            <nav class="nav">
                <button class="btn active" data-view="analytics">📊 Analytics</button>
                <button class="btn" data-view="reports">📂 Lesson Manager</button>
                <button class="btn" data-view="settings">⚙️ Settings</button>
                <button class="btn" id="logoutBtn" style="margin-top: 15px; background: #c0392b !important;" onclick="confirmLogout()">⏻ Sign Out</button>
            </nav>

            <div style="margin-top:auto;font-size:12px;opacity:0.7">
                Supabase Connected
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main">
            <div class="header">
                <h2 id="pageTitle" style="color: var(--text-dark);">Teacher Dashboard</h2>
                <div class="card" style="padding:10px 14px; font-weight: 600;">Welcome, Test Teacher! 👋</div>
            </div>

            <!-- Dashboard Metric Cards - Matching Student Design -->
            <div class="overview-grid">
                <div class="metric-card" id="metric1">
                    <div class="metric-value" id="total-students">0</div>
                    <div class="metric-label">Total Students</div>
                </div>
                <div class="metric-card" id="metric2">
                    <div class="metric-value" id="total-lessons">0</div>
                    <div class="metric-label">Total Lessons/Folders</div>
                </div>
                <div class="metric-card" id="metric3">
                    <div class="metric-value" id="avg-completion">0%</div>
                    <div class="metric-label">Class Avg. Completion</div>
                </div>
                <div class="metric-card" id="metric4">
                    <div class="metric-value" id="pending-reviews">0</div>
                    <div class="metric-label">Files Pending Review</div>
                </div>
            </div>

            <!-- Analytics View (Default) -->
            <section id="analyticsView">
                <div class="content-area">
                    <div class="left card">
                        <h3>Class Progress Chart</h3>
                        <canvas id="progressChart" style="max-width:320px; margin: 0 auto;"></canvas>
                        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                        <small class="muted">Overall class performance across all lessons.</small>
                    </div>

                    <div class="right card">
                        <h3>Student Performance List</h3>
                        <div id="studentList">
                            <small class="muted">Loading student data...</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Reports View (Lesson Management) -->
            <section id="reportsView" style="display:none;margin-top:18px">
                <div class="content-area">
                    <div class="left card">
                        <h3>Create New Lesson Folder</h3>
                        <form id="newFolderForm">
                            <div class="form-group">
                                <label for="folderName">Folder/Lesson Name</label>
                                <input type="text" id="folderName" placeholder="e.g., 'Algebra Unit 1'" required>
                            </div>
                            <button type="submit">Create Folder</button>
                            <p id="folderMessage" style="margin-top:10px; font-size:14px;"></p>
                        </form>
                    </div>

                    <div class="right card">
                        <h3>Manage Files in Lessons</h3>
                        <div id="lessonManager">
                            <small class="muted">Select a folder to upload a new task file.</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Settings View -->
            <section id="settingsView" style="display:none;margin-top:18px">
                <div class="card">
                    <h3>Profile & Account Settings</h3>
                    <div id="profileInfo"></div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // ==============================
        // 🔗 Supabase Connection & Setup
        // ==============================
        // NOTE: Replace with your actual Supabase configuration.
        // We are using placeholders since real keys are not available here.
        const SUPABASE_URL = 'https://your-supabase-url.supabase.co';
        const SUPABASE_ANON_KEY = 'your-anon-key';

        const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

        const greetingEl = document.getElementById('greeting');
        const roleEl = document.getElementById('role');
        const pageTitle = document.getElementById('pageTitle');
        const analyticsView = document.getElementById('analyticsView');
        const reportsView = document.getElementById('reportsView');
        const settingsView = document.getElementById('settingsView');
        const studentListEl = document.getElementById('studentList');
        const lessonManagerEl = document.getElementById('lessonManager');
        const progressChartCtx = document.getElementById('progressChart');
        const profileInfo = document.getElementById('profileInfo');
        
        // Metric elements
        const totalStudentsEl = document.getElementById('total-students');
        const totalLessonsEl = document.getElementById('total-lessons');
        const avgCompletionEl = document.getElementById('avg-completion');
        const pendingReviewsEl = document.getElementById('pending-reviews');

        let user = null;
        let chart = null;

        // ==============================
        // 🧠 UI & Navigation
        // ==============================

        // Get PHP session data (simulated login)
        const demoUserId = '<?php echo isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null; ?>';
        const demoUserName = '<?php echo isset($_SESSION["user_name"]) ? $_SESSION["user_name"] : "Guest"; ?>';
        const demoUserRole = '<?php echo isset($_SESSION["user_role"]) ? $_SESSION["user_role"] : "guest"; ?>';

        function showView(v) {
            analyticsView.style.display = v === 'analytics' ? 'block' : 'none';
            reportsView.style.display = v === 'reports' ? 'block' : 'none';
            settingsView.style.display = v === 'settings' ? 'block' : 'none';
            pageTitle.textContent = v === 'analytics' ? 'Teacher Dashboard' : v === 'reports' ? 'Lesson Manager' : 'Settings';
            if (v === 'analytics') loadAnalytics();
            if (v === 'reports') loadReports();
            if (v === 'settings') loadProfile();
        }

        document.querySelectorAll('.btn[data-view]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                showView(btn.dataset.view);
            });
        });

        function confirmLogout() {
            // This is simulated logout for the simple PHP session setup
            alert('Signed out! (Functionality disabled for simple PHP session demo)');
            // In a real Supabase app, you would use:
            // supabase.auth.signOut(); 
            // window.location.reload();
        }

        async function init() {
            // Set up mock user based on PHP session
            if (demoUserId) {
                user = { id: demoUserId, user_metadata: { full_name: demoUserName }, email: '<?php echo isset($_SESSION["user_email"]) ? $_SESSION["user_email"] : ""; ?>', role: demoUserRole };
                greetingEl.textContent = `Hello, ${demoUserName}`;
                roleEl.textContent = `Role: ${demoUserRole}`;
            }

            // Start on the Analytics view
            loadAnalytics();
        }

        // ==============================
        // 📊 Analytics (Student Progress)
        // ==============================

        async function loadAnalytics() {
            studentListEl.innerHTML = '<small class="muted">Fetching student progress...</small>';
            
            // --- DEMO DATA SIMULATION (REPLACE WITH REAL SUPABASE QUERIES) ---
            const studentData = [
                { id: 101, name: 'Alice Smith', completed: 8, total: 10 },
                { id: 102, name: 'Bob Johnson', completed: 5, total: 10 },
                { id: 103, name: 'Charlie Brown', completed: 9, total: 10 },
                { id: 104, name: 'Diana Prince', completed: 6, total: 10 },
            ];
            
            // Simulate fetching lesson count (replace with actual query on a 'lessons' or 'folders' table)
            const { data: folders } = await supabase.from('folders').select('id', { count: 'exact' }).limit(10);
            const lessonCount = folders?.length || 0;

            // Metrics calculation
            const totalStudents = studentData.length;
            const totalTasks = studentData.reduce((sum, s) => sum + s.total, 0);
            const totalCompleted = studentData.reduce((sum, s) => sum + s.completed, 0);
            const avgPct = totalTasks === 0 ? 0 : Math.round((totalCompleted / totalTasks) * 100);
            const pendingReviewsCount = 3; // Simulated
            
            // 3. Update Metrics
            totalStudentsEl.textContent = totalStudents;
            totalLessonsEl.textContent = lessonCount;
            avgCompletionEl.textContent = `${avgPct}%`;
            pendingReviewsEl.textContent = pendingReviewsCount;

            // 4. Render Student List
            studentListEl.innerHTML = '';
            const labels = [];
            const values = [];

            studentData.forEach(s => {
                const pct = s.total === 0 ? 0 : Math.round((s.completed / s.total) * 100);
                labels.push(s.name);
                values.push(pct);

                const el = document.createElement('div');
                el.className = 'list-item';
                el.innerHTML = `
                    <div>
                        <strong>${escapeHtml(s.name)}</strong>
                        <div><small class="muted">${s.completed}/${s.total} tasks completed</small></div>
                    </div>
                    <div style="font-weight: bold; color: ${pct >= 70 ? 'var(--accent-green)' : '#f77085'};">${pct}%</div>
                `;
                studentListEl.appendChild(el);
            });
            
            // 5. Render Chart
            renderChart(avgPct);
        }

        function renderChart(avgPct) {
            if (chart) chart.destroy();
            const data = {
                labels: ['Completed', 'Incomplete'],
                datasets: [{
                    data: [avgPct, 100 - avgPct],
                    // FIX: Using literal hex colors for Chart.js config to avoid SyntaxError
                    backgroundColor: ['#2ecc71', '#e0e6ed'], 
                    hoverOffset: 4
                }]
            };

            chart = new Chart(progressChartCtx.getContext('2d'), {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { callbacks: { label: (context) => `${context.label}: ${context.raw}%` } },
                        title: { 
                            display: true, 
                            text: `Class Average: ${avgPct}%`, 
                            // FIX: Using literal hex color for Chart.js config to avoid SyntaxError
                            color: '#1f3f37' 
                        } 
                    }
                }
            });
        }

        // ==============================
        // 📂 Reports (Lesson Management)
        // ==============================
        async function loadReports() {
            lessonManagerEl.innerHTML = '<small class="muted">Loading lessons...</small>';

            // 1. New Folder Submission Handler
            document.getElementById('newFolderForm').onsubmit = async (e) => {
                e.preventDefault();
                const folderName = document.getElementById('folderName').value.trim();
                const msgEl = document.getElementById('folderMessage');
                msgEl.textContent = 'Creating...';
                
                // Supabase Insertion Example
                const { error } = await supabase.from('folders').insert([
                    { name: folderName, created_by: user.id }
                ]);

                if (error) {
                    msgEl.textContent = `Error: ${error.message}`;
                    msgEl.style.color = '#e74c3c';
                } else {
                    msgEl.textContent = `Folder "${folderName}" created successfully!`;
                    msgEl.style.color = 'var(--accent-green)';
                    document.getElementById('folderName').value = '';
                    await loadFoldersForManagement(); // Refresh the list
                }
            };
            
            // 2. Load Folders
            await loadFoldersForManagement();
        }

        async function loadFoldersForManagement() {
            // Supabase Query Example
            const { data: folders, error } = await supabase.from('folders').select('*').order('created_at', { ascending: false });

            if (error) {
                lessonManagerEl.innerHTML = `<p style="color:#e74c3c;">Error loading folders: ${error.message}</p>`;
                return;
            }

            if (!folders || folders.length === 0) {
                lessonManagerEl.innerHTML = '<small class="muted">No lessons created yet. Use the form on the left to start.</small>';
                return;
            }

            let html = '<ul style="list-style: none; padding: 0;">';
            folders.forEach(f => {
                html += `
                    <li class="card" style="margin-bottom: 12px; padding: 15px;">
                        <div style="font-weight: 700; color: var(--text-dark);">${escapeHtml(f.name)}</div>
                        <small class="muted">ID: ${f.id}</small>
                        <form class="file-upload-form" data-folder-id="${f.id}" style="margin-top: 10px;">
                            <div class="form-row">
                                <input type="text" placeholder="File Name (e.g., Q1_Worksheet)" required style="flex: 2;">
                                <input type="file" required style="flex: 3;">
                            </div>
                            <button type="submit" style="margin-top: 8px;">Upload Task File</button>
                            <p class="file-message" data-folder-id="${f.id}" style="margin-top:5px; font-size:12px;"></p>
                        </form>
                    </li>
                `;
            });
            html += '</ul>';
            lessonManagerEl.innerHTML = html;
            
            attachFileUploadListeners();
        }

        function attachFileUploadListeners() {
            document.querySelectorAll('.file-upload-form').forEach(form => {
                form.onsubmit = async (e) => {
                    e.preventDefault();
                    const folderId = e.target.dataset.folderId;
                    const fileNameInput = e.target.querySelector('input[type="text"]');
                    const fileInput = e.target.querySelector('input[type="file"]');
                    const fileMessageEl = document.querySelector(`.file-message[data-folder-id="${folderId}"]`);
                    
                    const fileName = fileNameInput.value.trim();
                    const file = fileInput.files[0];

                    if (!file) return;

                    fileMessageEl.textContent = 'Uploading file...';
                    fileMessageEl.style.color = 'var(--text-muted)';
                    
                    // Supabase Storage Upload Example (Skipping actual upload for demo)
                    const uploadPath = `files/${folderId}/${fileName}_${Date.now()}`;
                    const mockFilePath = `https://storage.mock/path/${uploadPath}`;

                    // Supabase Metadata Insertion Example
                    const { error: insertError } = await supabase.from('files').insert([
                        { 
                            folder_id: folderId, 
                            file_name: fileName, 
                            file_path: mockFilePath, 
                            uploaded_by: user.id 
                        }
                    ]);

                    if (insertError) {
                        fileMessageEl.textContent = `Error saving metadata: ${insertError.message}`;
                        fileMessageEl.style.color = '#e74c3c';
                    } else {
                        fileMessageEl.textContent = `File "${fileName}" added to lesson successfully!`;
                        fileMessageEl.style.color = 'var(--accent-green)';
                        fileNameInput.value = '';
                        fileInput.value = '';
                        loadAnalytics(); // Refresh dashboard metrics
                    }
                };
            });
        }
        
        // ==============================
        // ⚙️ Profile
        // ==============================
        async function loadProfile() {
            profileInfo.innerHTML = '<div><small class="muted">Loading...</small></div>';
            if (!user) { profileInfo.innerHTML = '<div>Please sign in to view profile.</div>'; return; }
            profileInfo.innerHTML = `
                <form id="settingsForm">
                    <div class="form-group">
                        <label>Display Name</label>
                        <input type="text" value="${escapeHtml(user.user_metadata?.full_name || 'N/A')}" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="${escapeHtml(user.email)}" required disabled>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="${escapeHtml(user.role)}" disabled>
                    </div>
                    <button type="submit">Save Changes (Demo)</button>
                </form>
            `;
            // Add a mock save handler
            document.getElementById('settingsForm').onsubmit = (e) => {
                e.preventDefault();
                alert('Settings saved! (Demo functionality)');
            };
        }

        // ==============================
        // Helpers
        // ==============================
        function escapeHtml(s) { if (!s) return ''; return s.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;'); }

        init();
    </script>
</body>
</html>
