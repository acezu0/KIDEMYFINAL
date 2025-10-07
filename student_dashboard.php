<?php
session_start();
$_SESSION['user_id'] = '1'; // Placeholder ID for a demo student
$_SESSION['user_name'] = 'Test Student';
$_SESSION['user_email'] = 'student@example.com';
$_SESSION['user_role'] = 'student';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Student Dashboard</title>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Supabase JS CDN (v2) -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js/dist/umd/supabase.min.js"></script>
    <style>
        /* Green Color Palette */
        :root {
            --primary-bg: #e8f9ed; /* Very light green/white background */
            --sidebar-dark: #1f3f37; /* Dark forest green for sidebar */
            --sidebar-light: #2c564a; /* Slightly lighter shade for hover/active */
            --accent-green: #2ecc71; /* Bright primary green (used in the image for "Hospital Earning") */
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
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
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

        /* Dashboard Overview Grid (Mimicking the image's top row) */
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
        /* Specific Card Styles based on image */
        #metric1 { background-color: #f77085; } /* Pink */
        #metric2 { background-color: #aa85f7; } /* Lavender */
        #metric3 { background-color: var(--accent-green); } /* Primary Green */
        #metric4 { background-color: #f9d55f; } /* Yellow */
        .metric-value { font-size: 24px; margin-bottom: 5px; }
        .metric-label { font-size: 14px; opacity: 0.9; }


        /* Analytics layout */
        .analytics { display: flex; gap: 20px; margin-top: 20px; }
        .left { width: 360px; }
        .right { flex: 1; }
        .folder-list { margin-top: 12px; }
        .folder-item { 
            display: flex; 
            justify-content: space-between; 
            padding: 10px; 
            border-radius: 8px; 
            margin-bottom: 8px; 
            background: var(--primary-bg); /* Use light background for list items */
            cursor: pointer;
            border-left: 4px solid var(--accent-green);
            transition: background 0.2s;
        }
        .folder-item:hover { background: #d7f5df; } 

        /* Reports */
        .reports-list { margin-top: 12px; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .folder-card { background: var(--card-light); padding: 18px; border-radius: 12px; box-shadow: var(--shadow); }
        .file-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 10px; 
            border-radius: 8px; 
            margin-top: 10px; 
            background: #f8fafc;
            border: 1px solid #e0e6ed;
        }

        .upload-row { display: flex; gap: 8px; margin-top: 10px; }
        input[type=file] { display: block; border: 1px solid #ccc; padding: 6px; border-radius: 6px; background: white; }

        /* Helpers */
        small.muted { color: var(--text-muted); }
        button { 
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
            .analytics { flex-direction: column; }
            .left { width: 100%; }
        }
        @media (max-width: 600px) {
            .overview-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="app">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">
                <span style="color: var(--accent-green);">KID</span>EMY
            </div>
            <div>
                <div class="greet" id="greeting">Hello, Student</div>
                <div class="role" id="role">Role: student</div>
            </div>

            <nav class="nav">
                <button class="btn active" data-view="analytics">📊 Dashboard</button>
                <button class="btn" data-view="reports">📁 Teacher Reports</button>
                <button class="btn" data-view="settings">⚙️ Settings</button>
                <button class="btn" id="logoutBtn" style="margin-top: 15px;">⏻ Sign Out</button>
            </nav>

            <div style="margin-top:auto;font-size:12px;opacity:0.7">
                Connected to Database
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main">
            <div class="header">
                <h2 id="pageTitle" style="color: var(--text-dark);">Student Dashboard</h2>
                <div class="card" style="padding:10px 14px; font-weight: 600;">Welcome back! 👋</div>
            </div>

            <!-- Dashboard Metrics (Mimicking the top cards in the image) -->
            <div class="overview-grid">
                <div class="metric-card" id="metric1">
                    <div class="metric-value" id="tasks-pending">5</div>
                    <div class="metric-label">Pending Tasks</div>
                </div>
                <div class="metric-card" id="metric2">
                    <div class="metric-value" id="files-uploaded">25</div>
                    <div class="metric-label">Files Uploaded</div>
                </div>
                <div class="metric-card" id="metric3">
                    <div class="metric-value" id="folders-available">5</div>
                    <div class="metric-label">Available Folders</div>
                </div>
                <div class="metric-card" id="metric4">
                    <div class="metric-value" id="avg-progress">60%</div>
                    <div class="metric-label">Avg Progress</div>
                </div>
            </div>

            <!-- Analytics View -->
            <section id="analyticsView">
                <div class="analytics">
                    <div class="left card">
                        <h3>Progress Overview</h3>
                        <canvas id="progressChart" style="max-width:320px; margin: 0 auto;"></canvas>
                        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                        <div class="folder-list" id="folderProgressList"></div>
                    </div>

                    <div class="right card">
                        <h3>Task Details</h3>
                        <div id="analyticsDetails"><small class="muted">Select a task folder to view file-specific progress and mark completion.</small></div>
                    </div>
                </div>
            </section>

            <!-- Reports View -->
            <section id="reportsView" style="display:none;margin-top:18px">
                <div class="card">
                    <h3>Teacher Folders & Files</h3>
                    <div class="reports-list" id="foldersContainer"></div>
                </div>
            </section>

            <!-- Settings View -->
            <section id="settingsView" style="display:none;margin-top:18px">
                <div class="card">
                    <h3>Settings</h3>
                    <p><small class="muted">Profile & preferences (demo).</small></p>
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
        const SUPABASE_URL = 'https://your-supabase-url.supabase.co';
        const SUPABASE_ANON_KEY = 'your-anon-key';

        // FIX: The supabase CDN exports the client factory as `window.supabase.createClient`.
        const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

        const greetingEl = document.getElementById('greeting');
        const roleEl = document.getElementById('role');
        const pageTitle = document.getElementById('pageTitle');
        const analyticsView = document.getElementById('analyticsView');
        const reportsView = document.getElementById('reportsView');
        const settingsView = document.getElementById('settingsView');
        const folderProgressList = document.getElementById('folderProgressList');
        const progressChartCtx = document.getElementById('progressChart');
        const foldersContainer = document.getElementById('foldersContainer');
        const analyticsDetails = document.getElementById('analyticsDetails');
        const profileInfo = document.getElementById('profileInfo');
        
        // Metric elements
        const tasksPendingEl = document.getElementById('tasks-pending');
        const filesUploadedEl = document.getElementById('files-uploaded');
        const foldersAvailableEl = document.getElementById('folders-available');
        const avgProgressEl = document.getElementById('avg-progress');

        let user = null;
        let chart = null;

        // ==============================
        // 🧠 UI & Navigation
        // ==============================
        document.querySelectorAll('.btn[data-view]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                showView(btn.dataset.view);
            });
        });

        document.getElementById('logoutBtn').addEventListener('click', async () => {
            // Note: In a real app, this signs out the user. 
            // For this PHP/Session demo, it's just a placeholder.
            // await supabase.auth.signOut(); 
            alert('Signed out! (Functionality disabled for simple PHP session demo)');
            // location.reload();
        });

        function showView(v) {
            analyticsView.style.display = v === 'analytics' ? 'block' : 'none';
            reportsView.style.display = v === 'reports' ? 'block' : 'none';
            settingsView.style.display = v === 'settings' ? 'block' : 'none';
            pageTitle.textContent = v === 'analytics' ? 'Student Dashboard' : v === 'reports' ? 'Teacher Reports' : 'Settings';
            if (v === 'analytics') loadAnalytics();
            if (v === 'reports') loadReports();
            if (v === 'settings') loadProfile();
        }

        async function init() {
            // Simulate user authentication from PHP session for demo
            const demoUserId = '<?php echo isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null; ?>';
            const demoUserName = '<?php echo isset($_SESSION["user_name"]) ? $_SESSION["user_name"] : "Guest"; ?>';
            const demoUserRole = '<?php echo isset($_SESSION["user_role"]) ? $_SESSION["user_role"] : "guest"; ?>';

            // In a real app, we'd use supabase.auth.getSession()
            // Here, we just set a mock user object structure if we have an ID
            if (demoUserId) {
                user = { id: demoUserId, user_metadata: { full_name: demoUserName }, email: '<?php echo isset($_SESSION["user_email"]) ? $_SESSION["user_email"] : ""; ?>' };
                greetingEl.textContent = `Hello, ${demoUserName}`;
                roleEl.textContent = `Role: ${demoUserRole}`;
            } else {
                 // Fallback for real Supabase if session wasn't set (unlikely in this context)
                 const { data: { user: currentUser } } = await supabase.auth.getUser();
                 user = currentUser;
                 // Update UI based on real Supabase user
                 if(user) {
                    greetingEl.textContent = `Hello, ${user.user_metadata?.full_name || user.email.split('@')[0]}`;
                    roleEl.textContent = `Role: student`;
                 }
            }
            
            loadAnalytics();
            loadReports();
        }

        // ==============================
        // 📊 Analytics
        // ==============================
        async function loadAnalytics() {
            if (!user) {
                folderProgressList.innerHTML = '<small class="muted">Please ensure RLS is configured for unauthenticated access or sign in.</small>';
                renderChart(['No Data'], [100]);
                return;
            }
            
            // 1. Fetch Folders
            const { data: folders, error: fErr } = await supabase.from('folders').select('*').order('created_at', { ascending: false });
            if (fErr) { console.error('Error fetching folders', fErr); return; }

            // 2. Aggregate Data
            let totalFiles = 0;
            let totalCompleted = 0;
            const labels = [];
            const values = [];
            folderProgressList.innerHTML = '';

            for (const folder of folders) {
                // Get all files (tasks) in the folder
                const { data: files } = await supabase.from('files').select('id').eq('folder_id', folder.id);
                const total = files?.length || 0;
                totalFiles += total;
                
                // Get this student's completed tasks (submissions marked checked)
                const { data: subs } = await supabase.from('student_files').select('id').eq('folder_id', folder.id).eq('user_id', user.id);
                // We map to the provided schema's 'student_files' table, assuming existence of a file is completion.
                const completed = subs?.length || 0;
                totalCompleted += completed;

                const pct = total === 0 ? 0 : Math.round((completed / total) * 100);
                labels.push(folder.name || 'Untitled');
                values.push(pct);

                const el = document.createElement('div');
                el.className = 'folder-item';
                el.innerHTML = `
                    <div>
                        <strong>${escapeHtml(folder.name)}</strong>
                        <div><small class="muted">${completed}/${total} files completed</small></div>
                    </div>
                    <div style="font-weight: bold; color: var(--accent-green);">${pct}%</div>
                `;
                el.addEventListener('click', () => showFolderDetails(folder));
                folderProgressList.appendChild(el);
            }

            // 3. Update Metrics & Chart
            const avgPct = totalFiles === 0 ? 0 : Math.round((totalCompleted / totalFiles) * 100);
            
            tasksPendingEl.textContent = totalFiles - totalCompleted; // Placeholder for actual task logic
            filesUploadedEl.textContent = totalCompleted; // Total files completed/submitted
            foldersAvailableEl.textContent = folders.length;
            avgProgressEl.textContent = `${avgPct}%`;
            
            renderChart(labels, values);
        }

        function renderChart(labels, values) {
            if (chart) chart.destroy();
            chart = new Chart(progressChartCtx.getContext('2d'), {
                type: 'doughnut',
                data: { 
                    labels, 
                    datasets: [{
                        data: values,
                        backgroundColor: ['#2ecc71', '#3498db', '#f1c40f', '#e74c3c', '#9b59b6', '#34495e'],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'right' },
                        title: { display: false }
                    }
                }
            });
        }

        async function showFolderDetails(folder) {
            analyticsDetails.innerHTML = '<h4>' + escapeHtml(folder.name) + '</h4><div class="muted">Loading file list...</div>';
            
            // 1. Fetch all teacher-uploaded files for this folder
            const { data: files } = await supabase.from('files').select('*').eq('folder_id', folder.id).order('uploaded_at', { ascending: true });
            
            // 2. Fetch the student's submissions for this folder
            const { data: subs } = await supabase.from('student_files').select('file_name, file_path').eq('folder_id', folder.id).eq('user_id', user.id);
            const submittedFileNames = new Set(subs.map(s => s.file_name)); 

            let html = '<ul style="list-style: none; padding: 0;">';
            for (const f of files) {
                // Determine if this specific teacher file has a student submission
                const isCompleted = submittedFileNames.has(f.file_name); 

                html += `
                    <li style="margin-bottom: 15px; padding: 10px; border-radius: 8px; background: ${isCompleted ? '#d7f5df' : '#f8fafc'}; border: 1px solid ${isCompleted ? '#a8e0b9' : '#e0e6ed'};">
                        <div style="font-weight: 600;">${escapeHtml(f.file_name)}</div>
                        <small class="muted">Teacher File Path: ${f.file_path ? f.file_path.substring(0, 30) + '...' : 'N/A'}</small><br>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                            <span style="color: ${isCompleted ? 'var(--accent-green)' : 'var(--text-muted)'}; font-size: 14px;">
                                ${isCompleted ? '✅ Submitted' : '☐ Pending'}
                            </span>
                            <button data-fileid="${f.id}" data-filename="${f.file_name}" data-folderid="${folder.id}" class="complete-btn" ${isCompleted ? 'disabled' : ''}>
                                Mark ${isCompleted ? 'Complete' : 'as Complete'}
                            </button>
                        </div>
                    </li>`;
            }
            html += '</ul>';
            analyticsDetails.innerHTML = html;

            // 3. Add listener for "Mark as Complete" (Simulated completion)
            analyticsDetails.querySelectorAll('.complete-btn').forEach(btn => {
                btn.addEventListener('click', async e => {
                    if (!user) { alert('You must be signed in to save progress.'); return; }
                    
                    const fileId = e.target.dataset.fileid;
                    const fileName = e.target.dataset.filename;
                    const folderId = e.target.dataset.folderid;
                    
                    // Simulate insertion into student_files to mark progress
                    // We use file_name as a unique marker for a student's completion of a teacher file.
                    const payload = {
                        user_id: user.id,
                        folder_id: folderId,
                        file_name: fileName, // The name of the teacher's file being completed
                        file_path: 'SIMULATED_COMPLETION_PATH',
                        uploaded_at: new Date().toISOString()
                    };

                    const { error } = await supabase.from('student_files').insert([payload]);

                    if (error) { 
                        alert('Failed to mark complete: ' + error.message); 
                    } else { 
                        alert(`Task "${fileName}" marked as complete!`);
                        showFolderDetails(folder); // Refresh details
                        loadAnalytics(); // Refresh progress overview
                    }
                });
            });
        }

        // ==============================
        // 📁 Reports (Admin Files)
        // ==============================
        async function loadReports() {
            const { data: folders } = await supabase.from('folders').select('*').order('created_at', { ascending: false });
            foldersContainer.innerHTML = '';

            for (const folder of folders) {
                const card = document.createElement('div');
                card.className = 'folder-card';
                card.innerHTML = `
                    <h4 style="color: var(--accent-green); margin-bottom: 5px;">${escapeHtml(folder.name)}</h4>
                    <div class="muted" style="margin-bottom: 15px;">Official reports & lesson files.</div>
                    <div class="files" id="files-${folder.id}">Loading...</div>
                `;
                foldersContainer.appendChild(card);
                populateFiles(folder);
            }
        }

        async function populateFiles(folder) {
            const el = document.getElementById('files-' + folder.id);
            // Fetch the public/admin files
            const { data: files } = await supabase.from('files').select('*').eq('folder_id', folder.id);
            if (!files || files.length === 0) { el.innerHTML = '<small class="muted">No files yet</small>'; return; }

            el.innerHTML = '';
            for (const f of files) {
                const fileDiv = document.createElement('div');
                fileDiv.className = 'file-item';
                fileDiv.innerHTML = `
                    <div style="flex:1">
                        <strong>📄 ${escapeHtml(f.file_name)}</strong>
                        <div><small class="muted">Uploaded by: ${f.uploaded_by ? f.uploaded_by.substring(0, 8) + '...' : 'Admin'}</small></div>
                    </div>
                    <a href="${f.file_path}" target="_blank" style="text-decoration: none;">
                        <button style="background: #3498db; padding: 6px 10px;">Download</button>
                    </a>
                `;
                el.appendChild(fileDiv);
            }
        }

        // ==============================
        // ⚙️ Profile
        // ==============================
        async function loadProfile() {
            profileInfo.innerHTML = '<div><small class="muted">Loading...</small></div>';
            if (!user) { profileInfo.innerHTML = '<div>Please sign in to view profile.</div>'; return; }
            profileInfo.innerHTML = `
                <div style="padding: 10px 0;">
                    <div style="font-size: 1.1em; font-weight: 600; color: var(--accent-green);">${escapeHtml(user.user_metadata?.full_name || 'N/A')}</div>
                    <div class="muted">${user.email}</div>
                    <div class="muted">User ID: ${user.id}</div>
                    <div class="muted">Role: Student</div>
                </div>
            `;
        }

        // ==============================
        // Helpers
        // ==============================
        function escapeHtml(s) { if (!s) return ''; return s.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;'); }

        init();
    </script>
</body>
</html>
