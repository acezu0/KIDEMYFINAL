<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Dashboard</title>
<style>
  * {
    box-sizing: border-box;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  }

  body {
    margin: 0;
    display: flex;
    height: 100vh;
    background: #f3f6f9;
  }

  /* SIDEBAR */
  .sidebar {
    width: 250px;
    background: #2e8b57;
    color: white;
    display: flex;
    flex-direction: column;
    padding: 20px 0;
    transition: all 0.3s ease;
  }

  .greeting {
    text-align: center;
    margin-bottom: 30px;
  }

  .greeting h2 {
    font-size: 18px;
    margin: 0;
  }

  .role {
    font-size: 14px;
    opacity: 0.8;
  }

  .menu {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 0 20px;
  }

  .menu button {
    background: none;
    border: none;
    color: white;
    padding: 12px 15px;
    text-align: left;
    border-radius: 8px;
    cursor: pointer;
    font-size: 15px;
    transition: background 0.3s ease;
  }

  .menu button:hover,
  .menu button.active {
    background: rgba(255, 255, 255, 0.2);
  }

  /* MAIN CONTENT */
  .main {
    flex: 1;
    padding: 30px;
    overflow-y: auto;
  }

  .main h1 {
    color: #2e8b57;
  }

  /* FORM STYLES */
  form {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    max-width: 600px;
  }

  form input,
  form textarea,
  form select {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
  }

  form button {
    padding: 10px 20px;
    background-color: #2e8b57;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s ease;
  }

  form button:hover {
    background-color: #256d45;
  }

  /* LOGOUT */
  .logout {
    margin-top: auto;
    padding: 0 20px;
  }

  .logout button {
    width: 100%;
    background: #b22222;
  }

  .logout button:hover {
    background: #8b1a1a;
  }

  @media (max-width: 768px) {
    .sidebar {
      width: 200px;
    }
    .main {
      padding: 20px;
    }
  }
</style>
</head>
<body>
  <!-- 🟢 SIDEBAR -->
  <div class="sidebar">
    <div class="greeting">
      <h2>Hello, <span id="username">User</span> 👋</h2>
      <p class="role">Role: <span id="userRole">Admin</span></p>
    </div>

    <div class="menu">
      <button id="btnAnalytics" class="active" onclick="showSection('analytics')">📈 Analytics</button>
      <button id="btnReports" onclick="showSection('reports')">📄 Reports</button>
      <button id="btnSettings" onclick="showSection('settings')">⚙️ Settings</button>
    </div>

    <div class="logout">
      <button id="btnLogout" onclick="showSection('logout')">🚪 Logout</button>
    </div>
  </div>

  <!-- 🧩 MAIN CONTENT -->
  <div class="main" id="mainContent">
    <h1>Welcome to the Dashboard</h1>
    <p>Select an option from the sidebar to get started.</p>
  </div>

<script>
  // Example user (this could come from PHP session or Supabase later)
  const currentUser = {
    name: "Maria Cruz",
    role: "Teacher"
  };

  // Set greeting
  document.getElementById("username").textContent = currentUser.name;
  document.getElementById("userRole").textContent = currentUser.role;

  // Handle sidebar section switching
  function showSection(section) {
    const main = document.getElementById("mainContent");
    const buttons = document.querySelectorAll(".menu button, .logout button");
    buttons.forEach(btn => btn.classList.remove("active"));

    switch (section) {
      case "analytics":
        document.getElementById("btnAnalytics").classList.add("active");
        main.innerHTML = `
          <h1>📊 Analytics</h1>
          <form id="analyticsForm">
            <label>Report Name</label>
            <input type="text" placeholder="Enter analytics title" required>
            <label>Description</label>
            <textarea rows="4" placeholder="Enter description..."></textarea>
            <button type="submit">Submit Analytics</button>
          </form>
        `;
        break;

      case "reports":
        document.getElementById("btnReports").classList.add("active");
        main.innerHTML = `
          <h1>📄 Reports</h1>
          <form id="reportsForm">
            <label>Report Title</label>
            <input type="text" placeholder="Enter report name" required>
            <label>Upload File</label>
            <input type="file" required>
            <button type="submit">Upload Report</button>
          </form>
        `;
        break;

      case "settings":
        document.getElementById("btnSettings").classList.add("active");
        main.innerHTML = `
          <h1>⚙️ Settings</h1>
          <form id="settingsForm">
            <label>Display Name</label>
            <input type="text" value="${currentUser.name}" required>
            <label>Email</label>
            <input type="email" value="user@example.com" required>
            <label>Role</label>
            <select disabled>
              <option>${currentUser.role}</option>
            </select>
            <button type="submit">Save Changes</button>
          </form>
        `;
        break;

      case "logout":
        main.innerHTML = `
          <h1>🚪 Logout</h1>
          <p>Are you sure you want to log out?</p>
          <button onclick="confirmLogout()">Yes, Logout</button>
        `;
        break;
    }
  }

  function confirmLogout() {
    alert("You have been logged out!");
    // You can redirect to login page later
    window.location.href = "login.php";
  }
</script>
</body>
</html>
