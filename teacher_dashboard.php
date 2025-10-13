<?php
session_start();

// ✅ Check if the user is logged in and is a teacher
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
/* 🌿 Kidemy Theme */
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
body { margin: 0; font-family: 'Inter', 'Segoe UI', system-ui, Arial, sans-serif; background: var(--primary-bg); color: var(--text-dark); }
.app { display: flex; min-height: 100vh; }
.sidebar { width: 260px; background: var(--sidebar-dark); color: #fff; padding: 20px; display: flex; flex-direction: column; gap: 18px; }
.greet { font-weight: 700; font-size: 18px; }
.role { font-size: 13px; color: rgba(255,255,255,0.7); }
.nav { display: flex; flex-direction: column; gap: 8px; }
.btn { background: transparent; border: none; color: #fff; padding: 10px 12px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 10px; }
.btn:hover { background: var(--sidebar-light); }
.btn.active { background: var(--accent-green); color: var(--text-dark); }
.main { flex: 1; padding: 28px; }
.card { background: var(--card-light); padding: 18px; border-radius: var(--border-radius); box-shadow: var(--shadow); }
.content-area { display: flex; flex-direction: column; gap: 20px; margin-top: 20px; }
.sub-section { display: flex; gap: 20px; flex-wrap: wrap; }
.sub-section>.left { width: 360px; }
.sub-section>.right { flex: 1; }
.list-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-radius: 8px; margin-bottom: 8px; background: var(--primary-bg); border-left: 4px solid var(--sidebar-light); transition: background 0.2s; }
.list-item:hover { background: #d7f5df; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; }
.form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
small.muted { color: var(--text-muted); }
button:not(.btn) { background: var(--accent-green); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.2s; }
button:hover:not(.btn) { background: var(--accent-green-hover); }
.message-box { padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; font-weight: 500; border-left: 5px solid; display: none; }
.msg-success { background: #D1FAE5; color: #059669; border-color: #10B981; }
.msg-error { background: #FEE2E2; color: #DC2626; border-color: #F87171; }
.student-list { margin-top: 15px; max-height: 200px; overflow-y: auto; }
.hidden { display: none; }
</style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div style="font-size: 20px; font-weight: bold; margin-bottom: 10px;">
            <span style="color: var(--accent-green);">KID</span>EMY
        </div>
        <div>
            <div class="greet">Hello, <?= htmlspecialchars($user['name']) ?>!</div>
            <div class="role">Role: <?= htmlspecialchars($user['role']) ?></div>
        </div>
        <nav class="nav">
            <button id="btn-lessons" class="btn active">📂 Lesson Manager</button>
            <button id="btn-courses" class="btn">📘 Courses</button>
            <button class="btn" onclick="window.location.href='logout.php'">⏻ Sign Out</button>
        </nav>
    </aside>

    <main class="main">
        <!-- ==================== LESSON MANAGER SECTION ==================== -->
        <section id="lesson-section">
            <h2>Lesson Manager</h2>
            <div class="card" style="padding:10px 14px;">Welcome, <?= htmlspecialchars($user['name']) ?>! 👋</div>

            <div id="main-message" class="message-box"></div>

            <div class="sub-section">
                <div class="left card">
                    <h3>Create New Lesson Folder</h3>
                    <form id="new-folder-form">
                        <div class="form-group">
                            <label for="folder-name">Folder/Lesson Name</label>
                            <input type="text" id="folder-name" placeholder="e.g., 'Algebra Unit 1'" required>
                        </div>
                        <button type="submit">Create Folder</button>
                    </form>

                    <h3 style="margin-top:20px;">Lesson Folders</h3>
                    <div id="lesson-folder-list">
                        <small class="muted">Loading lessons...</small>
                    </div>
                </div>

                <div class="right card">
                    <h3>Folder Contents</h3>
                    <div id="folder-file-list">
                        <small class="muted">Select a folder to see its contents.</small>
                    </div>

                    <form id="upload-form" enctype="multipart/form-data" method="POST" style="margin-top:20px;">
                        <h4>Upload File to Folder</h4>
                        <input type="hidden" name="folder_id" id="selected-folder-id">
                        <div class="form-group">
                            <label>Select File (PDF, PPT, Image)</label>
                            <input type="file" name="file" accept=".pdf,.ppt,.pptx,.jpg,.jpeg,.png" required>
                        </div>
                        <button type="submit">Upload</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- ==================== COURSES SECTION ==================== -->
        <section id="courses-section" class="hidden">
            <h2>📘 Course Manager</h2>

            <div class="sub-section">
                <div class="left card">
                    <h3>Create New Course</h3>
                    <form id="new-course-form">
                        <div class="form-group">
                            <label for="course-title">Course Title</label>
                            <input type="text" id="course-title" placeholder="e.g., 'Mathematics 101'" required>
                        </div>
                        <div class="form-group">
                            <label for="course-description">Description</label>
                            <input type="text" id="course-description" placeholder="Short description...">
                        </div>
                        <button type="submit">Create Course</button>
                    </form>

                    <h3 style="margin-top:20px;">Your Courses</h3>
                    <div id="course-list">
                        <small class="muted">Loading courses...</small>
                    </div>
                </div>

                <div class="right card">
                    <h3>👩‍🎓 Enroll Students</h3>
                    <form id="enrollForm">
                        <div class="form-group">
                            <label>Student Email</label>
                            <input type="email" id="student_email" placeholder="student@email.com" required>
                        </div>
                        <div class="form-group">
                            <label>Select Course</label>
                            <select id="course_id" required>
                                <option>Loading courses...</option>
                            </select>
                        </div>
                        <button type="submit">Enroll Student</button>
                        <div id="enroll-msg" class="message-box" style="margin-top:10px;"></div>
                    </form>

                    <h4 style="margin-top:20px;">Currently Enrolled Students</h4>
                    <div id="student-list" class="student-list">
                        <small class="muted">Select a course to view enrolled students.</small>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const folderListDiv = document.getElementById('lesson-folder-list');
    const mainMsg = document.getElementById('main-message');
    const uploadForm = document.getElementById('upload-form');

    const lessonSection = document.getElementById('lesson-section');
    const coursesSection = document.getElementById('courses-section');
    const btnLessons = document.getElementById('btn-lessons');
    const btnCourses = document.getElementById('btn-courses');

    // 🔄 Toggle Sections
    btnLessons.onclick = () => {
        lessonSection.classList.remove('hidden');
        coursesSection.classList.add('hidden');
        btnLessons.classList.add('active');
        btnCourses.classList.remove('active');
    };
    btnCourses.onclick = () => {
        lessonSection.classList.add('hidden');
        coursesSection.classList.remove('hidden');
        btnCourses.classList.add('active');
        btnLessons.classList.remove('active');
        loadCourses(); // refresh courses each time
    };

    function showMessage(msg, type='success'){
        mainMsg.textContent = msg;
        mainMsg.className = `message-box msg-${type}`;
        mainMsg.style.display = 'block';
        setTimeout(()=>mainMsg.style.display='none', 3000);
    }

    // =============== FOLDERS ==================
    async function loadFolders(){
        const res = await fetch('api.php?action=get_folders');
        const data = await res.json();
        folderListDiv.innerHTML = '';
        if(!data.success) return folderListDiv.innerHTML = `<small>${data.message}</small>`;
        data.folders.forEach(f=>{
            const div=document.createElement('div');
            div.className='list-item';
            div.innerHTML=`<strong>📂 ${f.name}</strong>`;
            div.onclick=()=>{ document.getElementById('selected-folder-id').value=f.id; loadFiles(f.id); };
            folderListDiv.appendChild(div);
        });
    }

    async function loadFiles(fid){
        const res = await fetch(`api.php?action=get_files&folder_id=${fid}`);
        const data = await res.json();
        const container=document.getElementById('folder-file-list');
        if(!data.success || data.files.length===0) return container.innerHTML='<small>No files yet.</small>';
        container.innerHTML=data.files.map(x=>`
            <div class='list-item'>
                <div><strong>📄 ${x.file_name}</strong><br><small>${new Date(x.uploaded_at).toLocaleString()}</small></div>
                <a href='${x.file_path}' target='_blank'>View</a>
            </div>`).join('');
    }

    document.getElementById('new-folder-form').onsubmit=async e=>{
        e.preventDefault();
        const name=document.getElementById('folder-name').value.trim();
        if(!name) return showMessage('Enter a folder name','error');
        const fd=new FormData();
        fd.append('action','create_folder');
        fd.append('folder_name',name);
        const res=await fetch('api.php',{method:'POST',body:fd});
        const data=await res.json();
        if(data.success){ showMessage('Folder created!'); loadFolders(); }
        else showMessage(data.message,'error');
    };

    uploadForm.onsubmit=async e=>{
        e.preventDefault();
        const fid=document.getElementById('selected-folder-id').value;
        if(!fid) return showMessage('Select a folder first','error');
        const fd=new FormData(uploadForm);
        const res=await fetch('upload_file.php',{method:'POST',body:fd});
        const data=await res.json();
        if(data.success){ showMessage('File uploaded!'); loadFiles(fid); uploadForm.reset(); }
        else showMessage(data.message,'error');
    };

    // =============== COURSES ==================
    const enrollMsg = document.getElementById('enroll-msg');
    const courseSelect = document.getElementById('course_id');
    const studentList = document.getElementById('student-list');
    const courseList = document.getElementById('course-list');

    function showEnrollMsg(msg, type='success'){
        enrollMsg.textContent = msg;
        enrollMsg.className = `message-box msg-${type}`;
        enrollMsg.style.display = 'block';
        setTimeout(()=>enrollMsg.style.display='none', 3000);
    }

    async function loadCourses(){
        const res = await fetch('api.php?action=get_courses');
        const data = await res.json();
        courseSelect.innerHTML = '';
        courseList.innerHTML = '';
        if(data.success && data.courses.length > 0){
            data.courses.forEach(c=>{
                const opt=document.createElement('option');
                opt.value=c.id;
                opt.textContent=c.title;
                courseSelect.appendChild(opt);

                const div=document.createElement('div');
                div.className='list-item';
                div.innerHTML=`<strong>📘 ${c.title}</strong>`;
                div.onclick=()=> loadStudents(c.id);
                courseList.appendChild(div);
            });
            loadStudents(courseSelect.value);
        } else {
            courseSelect.innerHTML = '<option>No courses found</option>';
            courseList.innerHTML = '<small>No courses yet.</small>';
        }
    }

    async function loadStudents(courseId){
        const res = await fetch(`api.php?action=get_enrolled_students&course_id=${courseId}`);
        const data = await res.json();
        if(!data.success || data.students.length===0){
            studentList.innerHTML='<small>No students enrolled yet.</small>';
            return;
        }
        studentList.innerHTML=data.students.map(s=>`
            <div class='list-item'>
                <div><strong>👩‍🎓 ${s.name}</strong><br><small>${s.email}</small></div>
            </div>`).join('');
    }

    document.getElementById('new-course-form').onsubmit=async e=>{
        e.preventDefault();
        const fd=new FormData();
        fd.append('action','create_course');
        fd.append('title',document.getElementById('course-title').value);
        fd.append('description',document.getElementById('course-description').value);
        const res=await fetch('api.php',{method:'POST',body:fd});
        const data=await res.json();
        if(data.success){ showEnrollMsg('Course created!'); loadCourses(); e.target.reset(); }
        else showEnrollMsg(data.message,'error');
    };

    courseSelect.addEventListener('change', ()=> loadStudents(courseSelect.value));

    document.getElementById('enrollForm').onsubmit=async e=>{
        e.preventDefault();
        const fd=new FormData();
        fd.append('action','enroll_student');
        fd.append('student_email',document.getElementById('student_email').value);
        fd.append('course_id',courseSelect.value);
        const res=await fetch('api.php',{method:'POST',body:fd});
        const data=await res.json();
        showEnrollMsg(data.message, data.success?'success':'error');
        if(data.success){ document.getElementById('student_email').value=''; loadStudents(courseSelect.value); }
    };

    loadFolders();
    loadCourses();
});
</script>
</body>
</html>
