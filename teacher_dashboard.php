<?php
session_start();

// Check if the user is logged in and is a teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Teacher Dashboard</title>
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
            height: 100vh;
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

        /* Layout for Lesson Management */
        .content-area { display: flex; gap: 20px; margin-top: 20px; }
        .content-area > .left { width: 360px; }
        .content-area > .right { flex: 1; }
        
        /* List Styles for Folders */
        .list-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 10px; 
            border-radius: 8px; 
            margin-bottom: 8px; 
            background: var(--primary-bg);
            border-left: 4px solid var(--sidebar-light);
            transition: background 0.2s, border-left 0.2s;
        }
        .list-item:hover { background: #d7f5df; } 

        /* Form Styles */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; color: var(--text-dark); }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: #fcfcfc; }

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

        /* Message Box Styling */
        .message-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            border-left: 5px solid;
            display: none;
            width: 100%;
        }
        .msg-success { background-color: #D1FAE5; color: #059669; border-color: #10B981; }
        .msg-error { background-color: #FEE2E2; color: #DC2626; border-color: #F87171; }

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
                <div class="greet">Hello, <?= htmlspecialchars($user['name']) ?>!</div>
                <div class="role">Role: <?= htmlspecialchars($user['role']) ?></div>
            </div>
            <nav class="nav">
                <button class="btn active">📂 Lesson Manager</button>
                <button class="btn" id="logoutBtn" onclick="window.location.href = 'logout.php';">⏻ Sign Out</button>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main">
            <div class="header">
                <h2 id="pageTitle">Lesson Manager</h2>
                <div class="card" style="padding:10px 14px; font-weight: 600;">Welcome, <?= htmlspecialchars($user['name']) ?>! 👋</div>
            </div>

            <div id="main-message" class="message-box"></div>

            <div class="content-area">
                <div class="left">
                    <div class="card" style="margin-bottom: 20px;">
                        <h3>Create New Lesson Folder</h3>
                        <form id="new-folder-form">
                            <div class="form-group">
                                <label for="folder-name">Folder/Lesson Name</label>
                                <input type="text" id="folder-name" placeholder="e.g., 'Algebra Unit 1'" required>
                            </div>
                            <button type="submit">Create Folder</button>
                        </form>
                    </div>
                    <div class="card">
                        <h3>Lesson Folders</h3>
                        <div id="lesson-folder-list">
                            <small class="muted">Loading lessons...</small>
                        </div>
                    </div>
                </div>

                <div class="right card">
                    <h3>Folder Contents</h3>
                    <div id="folder-file-list">
                        <small class="muted">Select a folder to see its contents.</small>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const newFolderForm = document.getElementById('new-folder-form');
        const folderNameInput = document.getElementById('folder-name');
        const folderListDiv = document.getElementById('lesson-folder-list');
        const mainMessageDiv = document.getElementById('main-message');

        // --- Function to display messages ---
        function showMessage(message, type = 'success') {
            mainMessageDiv.textContent = message;
            mainMessageDiv.className = `message-box msg-${type}`;
            mainMessageDiv.style.display = 'block';
            setTimeout(() => {
                mainMessageDiv.style.display = 'none';
            }, 3000);
        }

        // --- Function to fetch and render folders ---
        async function loadFolders() {
            try {
                const response = await fetch('api.php?action=get_folders');
                const result = await response.json();

                if (result.success) {
                    renderFolderList(result.data);
                } else {
                    folderListDiv.innerHTML = `<small class="muted">Error: ${result.message}</small>`;
                }
            } catch (error) {
                folderListDiv.innerHTML = '<small class="muted">Error loading folders.</small>';
                console.error('Fetch error:', error);
            }
        }

        // --- Function to render the list of folders ---
        function renderFolderList(folders) {
            folderListDiv.innerHTML = '';
            if (folders.length === 0) {
                folderListDiv.innerHTML = '<small class="muted">No lesson folders created yet.</small>';
                return;
            }

            folders.forEach(folder => {
                const el = document.createElement('div');
                el.className = 'list-item';
                el.innerHTML = `
                    <div>
                        <strong>📂 ${escapeHtml(folder.name)}</strong>
                        <div><small class="muted">Created: ${new Date(folder.created_at).toLocaleDateString()}</small></div>
                    </div>
                `;
                folderListDiv.appendChild(el);
            });
        }

        // --- Event Listener for creating a new folder ---
        newFolderForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const folderName = folderNameInput.value.trim();
            if (!folderName) {
                showMessage('Please enter a folder name.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'create_folder');
            formData.append('folder_name', folderName);

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    showMessage('Folder created successfully!');
                    folderNameInput.value = ''; // Clear input
                    loadFolders(); // Refresh the list
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                showMessage('An unexpected error occurred.', 'error');
                console.error('Submit error:', error);
            }
        });

        function escapeHtml(s) {
            if (!s) return '';
            return String(s).replace(/[&<>"]|(?<!\d)'/g, i => `&#${i.charCodeAt(0)};`);
        }

        // --- Initial Load ---
        loadFolders();
    });
    </script>
</body>
</html>
