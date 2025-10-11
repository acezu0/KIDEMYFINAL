<?php
session_start();

// Check if the user is logged in and is a student
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

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const __user_name = '<?= htmlspecialchars($user['name']) ?>';
        const __user_role = '<?= htmlspecialchars($user['role']) ?>';
    </script>

    <style>
        /* Green Color Palette */
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
        }
        .greet { font-weight: 700; font-size: 18px; }
        .role { font-size: 13px; color: rgba(255,255,255,0.7); }
        .user-id-display { font-size: 11px; color: rgba(255,255,255,0.5); word-break: break-all; margin-top: 5px; }
        .nav { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }
        .btn {
            background: transparent; border: none; color: #fff;
            padding: 10px 12px; text-align: left; border-radius: 8px;
            cursor: pointer; font-weight: 600;
            transition: background 0.2s;
            display: flex; align-items: center; gap: 10px;
        }
        .btn:hover { background: var(--sidebar-light); }
        .btn.active {
            background: var(--accent-green); color: var(--text-dark);
            box-shadow: 0 4px 6px rgba(46, 204, 113, 0.3);
        }

        /* Main content */
        .main { flex: 1; padding: 28px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }

        .card { background: var(--card-light); padding: 18px; border-radius: var(--border-radius); box-shadow: var(--shadow); }

        /* Metrics */
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
        #metric1 { background: #3498db; }
        #metric2 { background: #9b59b6; }
        #metric3 { background: #2ecc71; }
        #metric4 { background: #f1c40f; }
        .metric-value { font-size: 24px; margin-bottom: 5px; }
        .metric-label { font-size: 14px; opacity: 0.9; }

        /* Content Area */
        .content-area { display: flex; gap: 20px; margin-top: 20px; }
        .content-area > .left { flex: 2; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .content-area > .right { flex: 1; }

        /* Fixed Chart Container */
        #progressChart {
            width: 100%;
            max-width: 320px;
            height: 320px !important;
            margin: 0 auto;
            display: block;
        }

        /* List Items */
        .list-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px; border-radius: 8px; margin-bottom: 8px;
            background: var(--primary-bg);
            border-left: 4px solid var(--accent-green);
        }

        small.muted { color: var(--text-muted); }

        @media (max-width: 1024px) {
            .overview-grid { grid-template-columns: repeat(2, 1fr); }
            .content-area { flex-direction: column; }
            #progressChart { max-width: 250px; height: 250px !important; }
        }
        @media (max-width: 600px) {
            .overview-grid { grid-template-columns: 1fr; }
            #progressChart { max-width: 220px; height: 220px !important; }
        }
    </style>
</head>
<body>
<div class="app">
    <!-- Sidebar -->
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
            <button class="btn" data-view="settings">⚙️ Settings</button>
            <button class="btn" style="margin-top:15px;background:#c0392b !important;" onclick="confirmLogout()">⏻ Sign Out</button>
        </nav>

        <div style="margin-top:auto;font-size:12px;opacity:0.7">Supabase Connected</div>
    </aside>

    <!-- Main -->
    <main class="main">
        <div class="header">
            <h2 id="pageTitle">Student Dashboard</h2>
            <div class="card" style="padding:10px 14px; font-weight:600;">Welcome, <span id="userNameDisplay">Student</span>! 👋</div>
        </div>

        <!-- Metrics -->
        <div class="overview-grid">
            <div class="metric-card" id="metric1">
                <div class="metric-value" id="courses-enrolled">0</div>
                <div class="metric-label">Courses Enrolled</div>
            </div>
            <div class="metric-card" id="metric2">
                <div class="metric-value" id="lessons-completed">0</div>
                <div class="metric-label">Lessons Completed</div>
            </div>
            <div class="metric-card" id="metric3">
                <div class="metric-value" id="overall-progress">0%</div>
                <div class="metric-label">Overall Progress</div>
            </div>
            <div class="metric-card" id="metric4">
                <div class="metric-value" id="assignments-due">0</div>
                <div class="metric-label">Assignments Due</div>
            </div>
        </div>

        <!-- Dashboard View -->
        <section id="dashboardView">
            <div class="content-area">
                <div class="left card">
                    <h3>My Progress</h3>
                    <canvas id="progressChart"></canvas>
                </div>
                <div class="right card">
                    <h3>Upcoming Deadlines</h3>
                    <div id="deadlinesList">
                        <small class="muted">No upcoming deadlines.</small>
                    </div>
                </div>
            </div>
        </section>

        <!-- Courses View -->
        <section id="coursesView" style="display:none;margin-top:18px">
            <div class="card">
                <h3>Enrolled Courses</h3>
                <div id="courseList"><small class="muted">You are not enrolled in any courses yet.</small></div>
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
    const greetingEl = document.getElementById('greeting');
    const roleEl = document.getElementById('role');
    const userIdDisplay = document.getElementById('userIdDisplay');
    const userNameDisplay = document.getElementById('userNameDisplay');
    const pageTitle = document.getElementById('pageTitle');
    const dashboardView = document.getElementById('dashboardView');
    const coursesView = document.getElementById('coursesView');
    const settingsView = document.getElementById('settingsView');
    const courseListEl = document.getElementById('courseList');
    const deadlinesListEl = document.getElementById('deadlinesList');
    const progressChartCtx = document.getElementById('progressChart');

    const coursesEnrolledEl = document.getElementById('courses-enrolled');
    const lessonsCompletedEl = document.getElementById('lessons-completed');
    const overallProgressEl = document.getElementById('overall-progress');
    const assignmentsDueEl = document.getElementById('assignments-due');

    let chart;

    async function initializeDashboard() {
        const user = JSON.parse('<?= json_encode($user) ?>');
        greetingEl.textContent = `Hello, ${escapeHtml(user.name)}!`;
        userNameDisplay.textContent = escapeHtml(user.name);
        roleEl.textContent = `Role: ${escapeHtml(user.role)}`;
        userIdDisplay.textContent = `User ID: ${escapeHtml(user.id)}`;

        try {
            const response = await fetch('student_api.php?action=get_dashboard_data');
            const result = await response.json();
            if (result.success) {
                const data = result.data;
                coursesEnrolledEl.textContent = data.coursesEnrolled;
                lessonsCompletedEl.textContent = data.lessonsCompleted;
                overallProgressEl.textContent = `${data.overallProgress}%`;
                assignmentsDueEl.textContent = data.assignmentsDue;
                renderDeadlines(data.deadlines);
                renderChart(data.overallProgress);
                renderCourseList(data.courses);
                renderProfile(user);
            } else alert(`Error: ${result.message}`);
        } catch (error) {
            console.error('Dashboard load failed:', error);
        }
    }

    function renderChart(progress) {
        if (chart) chart.destroy();
        chart = new Chart(progressChartCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Incomplete'],
                datasets: [{ data: [progress, 100 - progress], backgroundColor: ['#2ecc71', '#e0e6ed'], hoverOffset: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    }

    function renderDeadlines(deadlines) {
        deadlinesListEl.innerHTML = '';
        if (!deadlines.length) return deadlinesListEl.innerHTML = '<small class="muted">No upcoming deadlines.</small>';
        deadlines.forEach(d => {
            const el = document.createElement('div');
            el.className = 'list-item';
            el.innerHTML = `<strong>${escapeHtml(d.name)}</strong><span>${escapeHtml(d.due)}</span>`;
            deadlinesListEl.appendChild(el);
        });
    }

    function renderCourseList(courses) {
        courseListEl.innerHTML = '';
        if (!courses.length) return courseListEl.innerHTML = '<small class="muted">You are not enrolled in any courses yet.</small>';
        courses.forEach(c => {
            const el = document.createElement('div');
            el.className = 'list-item';
            el.innerHTML = `<strong>${escapeHtml(c.title)}</strong><small class="muted">Teacher: ${escapeHtml(c.teacher)}</small>`;
            courseListEl.appendChild(el);
        });
    }

    function renderProfile(user) {
        document.getElementById('profileInfo').innerHTML = `
            <form id="settingsForm">
                <div class="form-group"><label>Display Name</label><input type="text" value="${escapeHtml(user.name)}" required></div>
                <div class="form-group"><label>Email</label><input type="email" value="${escapeHtml(user.email)}" disabled></div>
                <div class="form-group"><label>User ID</label><input type="text" value="${escapeHtml(user.id)}" disabled></div>
                <div class="form-group"><label>Role</label><input type="text" value="${escapeHtml(user.role)}" disabled></div>
                <button type="submit">Save Changes (Not Implemented)</button>
            </form>`;
        document.getElementById('settingsForm').onsubmit = e => { e.preventDefault(); alert('Profile update not yet implemented.'); };
    }

    function showView(viewName) {
        dashboardView.style.display = viewName === 'dashboard' ? 'block' : 'none';
        coursesView.style.display = viewName === 'courses' ? 'block' : 'none';
        settingsView.style.display = viewName === 'settings' ? 'block' : 'none';
        pageTitle.textContent = viewName.charAt(0).toUpperCase() + viewName.slice(1);
    }

    document.querySelectorAll('.btn[data-view]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.btn[data-view]').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            showView(btn.dataset.view);
        });
    });

    function confirmLogout() { window.location.href = 'logout.php'; }

    function escapeHtml(s) { return s ? String(s).replace(/[&<>'"`]/g, i => `&#${i.charCodeAt(0)};`) : ''; }

    document.addEventListener('DOMContentLoaded', initializeDashboard);
</script>
</body>
</html>
