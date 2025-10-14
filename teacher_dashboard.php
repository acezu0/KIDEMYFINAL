<?php
session_start();
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
/* 🟢 Kidemy Theme */
:root {
    --primary-bg:#e8f9ed; --sidebar-dark:#1f3f37; --sidebar-light:#2c564a;
    --accent-green:#2ecc71; --accent-green-hover:#27ae60;
    --text-dark:#1f3f37; --text-muted:#6c757d; --card-light:#fff;
    --shadow:0 4px 12px rgba(0,0,0,0.08); --border-radius:12px;
}
body{margin:0;font-family:'Inter',system-ui,Arial;background:var(--primary-bg);color:var(--text-dark);}
.app{display:flex;min-height:100vh;}
.sidebar{width:260px;background:var(--sidebar-dark);color:#fff;padding:20px;display:flex;flex-direction:column;gap:18px;}
.nav{display:flex;flex-direction:column;gap:8px;}
.btn{background:transparent;border:none;color:#fff;padding:10px 12px;border-radius:8px;cursor:pointer;font-weight:600;display:flex;align-items:center;gap:10px;}
.btn:hover{background:var(--sidebar-light);}
.btn.active{background:var(--accent-green);color:var(--text-dark);}
.main{flex:1;padding:28px;}
.card{background:var(--card-light);padding:18px;border-radius:var(--border-radius);box-shadow:var(--shadow);}
.message-box{padding:10px;border-radius:8px;margin:10px 0;font-size:14px;display:none;}
.msg-success{background:#D1FAE5;color:#059669;}
.msg-error{background:#FEE2E2;color:#DC2626;}
.list-item{padding:10px;margin:6px 0;background:var(--primary-bg);border-left:4px solid var(--sidebar-light);border-radius:8px;cursor:pointer;}
.list-item:hover{background:#d7f5df;}
.hidden{display:none;}
input, select, button {margin:5px 0;padding:8px;border:1px solid #ccc;border-radius:6px;}
button {background:var(--accent-green);color:#fff;cursor:pointer;}
button:hover{background:var(--accent-green-hover);}
.preview-frame{margin-top:10px;border:1px solid #ccc;border-radius:6px;padding:5px;background:#fff;}
</style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div style="font-size:20px;font-weight:bold;margin-bottom:10px;">
            <span style="color:var(--accent-green);">KID</span>EMY
        </div>
        <div><div style="font-weight:700;font-size:18px;">Hello, <?= htmlspecialchars($user['name']) ?>!</div>
        <div style="font-size:13px;color:rgba(255,255,255,0.7);">Role: <?= htmlspecialchars($user['role']) ?></div></div>
        <nav class="nav">
            <button id="btn-courses" class="btn active">📘 Courses</button>
            <button class="btn" onclick="window.location.href='logout.php'">⏻ Sign Out</button>
        </nav>
    </aside>

    <main class="main">
        <h2>📘 Course Manager</h2>

        <!-- 🔹 Create New Course -->
        <div class="card">
            <form id="new-course-form">
                <label>Course Title</label><br>
                <input type="text" id="course-title" required><br>
                <label>Description</label><br>
                <input type="text" id="course-description">
                <button type="submit">Create Course</button>
            </form>
        </div>

        <!-- 🔹 Course List -->
        <div id="course-section" class="card" style="margin-top:20px;">
            <h3>Your Courses</h3>
            <div id="course-list"><small>Loading...</small></div>
        </div>

        <!-- 🔹 Course Content -->
        <div id="course-content" class="card hidden" style="margin-top:20px;">
            <h3 id="course-title-display">Course Content</h3>

            <!-- Folder Creation -->
            <form id="new-folder-form">
                <label>New Folder Name</label><br>
                <input type="text" id="folder-name" required>
                <button type="submit">Create Folder</button>
            </form>

            <!-- Folder List -->
            <div id="folder-list" style="margin-top:10px;"></div>

            <!-- File Upload -->
            <form id="upload-form" enctype="multipart/form-data" style="margin-top:20px;">
                <input type="hidden" name="folder_id" id="selected-folder-id">
                <label>Select File</label><br>
                <input type="file" name="file" accept=".pdf,.ppt,.pptx,.jpg,.jpeg,.png" required>
                <button type="submit">Upload File</button>
            </form>

            <!-- File List -->
            <div id="file-list" style="margin-top:15px;"></div>

            <!-- File Preview -->
            <div id="file-preview" class="preview-frame hidden">
                <h4>Preview:</h4>
                <iframe id="preview-frame" width="100%" height="400px"></iframe>
            </div>

            <!-- Enroll Student (by Gmail) -->
            <h3 style="margin-top:25px;">🎓 Enroll Student</h3>
            <form id="enroll-form">
                <label>Enter Student Gmail</label><br>
                <input type="email" id="student-email" placeholder="student@gmail.com" required>
                <button type="submit">Enroll Student</button>
            </form>
            <div id="enrolled-list" style="margin-top:10px;"></div>

            <div id="main-message" class="message-box"></div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    const courseList=document.getElementById('course-list');
    const folderList=document.getElementById('folder-list');
    const fileList=document.getElementById('file-list');
    const courseContent=document.getElementById('course-content');
    const msg=document.getElementById('main-message');
    const preview=document.getElementById('file-preview');
    const previewFrame=document.getElementById('preview-frame');
    const enrolledList=document.getElementById('enrolled-list');
    let currentCourseId=null;

    function showMsg(t,type='success'){msg.textContent=t;msg.className='message-box msg-'+type;msg.style.display='block';setTimeout(()=>msg.style.display='none',2500);}

    async function loadCourses(){
        const r=await fetch('api.php?action=get_courses');
        const d=await r.json();
        courseList.innerHTML='';
        if(!d.success||d.courses.length===0)return courseList.innerHTML='<small>No courses yet.</small>';
        d.courses.forEach(c=>{
            const div=document.createElement('div');
            div.className='list-item';
            div.textContent='📘 '+c.title;
            div.onclick=()=>openCourse(c.id,c.title);
            courseList.appendChild(div);
        });
    }

    async function openCourse(id,title){
        currentCourseId=id;
        courseContent.classList.remove('hidden');
        document.getElementById('course-title-display').textContent='📂 '+title;
        loadFolders();
        loadEnrolled();
    }

    async function loadFolders(){
        const r=await fetch(`api.php?action=get_folders&course_id=${currentCourseId}`);
        const d=await r.json();
        folderList.innerHTML='';
        if(!d.success||d.folders.length===0)return folderList.innerHTML='<small>No folders yet.</small>';
        d.folders.forEach(f=>{
            const div=document.createElement('div');
            div.className='list-item';
            div.textContent='📂 '+f.name;
            div.onclick=()=>{document.getElementById('selected-folder-id').value=f.id;loadFiles(f.id);};
            folderList.appendChild(div);
        });
    }

    async function loadFiles(fid){
        const r=await fetch(`api.php?action=get_files&folder_id=${fid}`);
        const d=await r.json();
        fileList.innerHTML='';
        if(!d.success||d.files.length===0)return fileList.innerHTML='<small>No files yet.</small>';
        d.files.forEach(f=>{
            const div=document.createElement('div');
            div.className='list-item';
            div.innerHTML=`📄 ${f.file_name}`;
            div.onclick=()=>{
                preview.classList.remove('hidden');
                previewFrame.src=f.file_path;
            };
            fileList.appendChild(div);
        });
    }


    async function loadEnrolled(){
        const r=await fetch(`api.php?action=get_enrolled_students&course_id=${currentCourseId}`);
        const d=await r.json();
        enrolledList.innerHTML='';
        if(!d.success||d.students.length===0)return enrolledList.innerHTML='<small>No students enrolled yet.</small>';
        d.students.forEach(s=>{
            const div=document.createElement('div');
            div.className='list-item';
            div.textContent='🎓 '+s.name+' ('+s.email+')';
            enrolledList.appendChild(div);
        });
    }

    document.getElementById('new-course-form').onsubmit=async e=>{
        e.preventDefault();
        const fd=new FormData();
        fd.append('action','create_course');
        fd.append('title',document.getElementById('course-title').value);
        fd.append('description',document.getElementById('course-description').value);
        const r=await fetch('api.php',{method:'POST',body:fd});
        const d=await r.json();
        if(d.success){showMsg('Course created!');loadCourses();}
        else showMsg(d.message,'error');
    };

    document.getElementById('new-folder-form').onsubmit=async e=>{
        e.preventDefault();
        const fd=new FormData();
        fd.append('action','create_folder');
        fd.append('folder_name',document.getElementById('folder-name').value);
        fd.append('course_id',currentCourseId);
        const r=await fetch('api.php',{method:'POST',body:fd});
        const d=await r.json();
        if(d.success){showMsg('Folder created!');loadFolders();}
        else showMsg(d.message,'error');
    };

    document.getElementById('upload-form').onsubmit=async e=>{
        e.preventDefault();
        const fid=document.getElementById('selected-folder-id').value;
        if(!fid)return showMsg('Select a folder first','error');
        const fd=new FormData(e.target);
        fd.append('action','upload_file');
        fd.append('course_id',currentCourseId);
        const r=await fetch('api.php',{method:'POST',body:fd});
        const d=await r.json();
        if(d.success){showMsg('File uploaded!');loadFiles(fid);}
        else showMsg(d.message,'error');
    };

    document.getElementById('enroll-form').onsubmit = async e => {
        e.preventDefault();
        const fd = new FormData();
        fd.append('action', 'enroll_student_by_email');
        fd.append('course_id', currentCourseId);
        fd.append('email', document.getElementById('student-email').value.trim());
        
        const r = await fetch('api.php', { method: 'POST', body: fd });
        const d = await r.json();
        
        if (d.success) {
            showMsg('Student enrolled successfully!');
            document.getElementById('student-email').value = '';
            loadEnrolled();
        } else {
            showMsg(d.message, 'error');
        }
    };

    loadCourses();
});
</script>
</body>
</html>
