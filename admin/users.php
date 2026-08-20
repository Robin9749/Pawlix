<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");


$users_query = "
SELECT 
    u.*,
    (SELECT COUNT(*) FROM adoption_application a WHERE a.user_id = u.user_id) AS total_adoptions,
    (SELECT COUNT(*) FROM report_dogs r WHERE r.user_id = u.user_id) AS total_reports,
    (SELECT COUNT(*) FROM messages m WHERE m.user_id = u.user_id) AS total_messages
FROM user u
ORDER BY u.user_id DESC
";

$users = mysqli_query($conn, $users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users | PawLix Admin</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');

:root {
  --bg-body: #e8dcc0;
  --bg-topbar: #f2e6c9;
  --bg-card: #ede1c6;
  --bg-sidebar: #e8dcc0;
  --bg-sidebar-hover: #ddceac;
  --primary-orange: #f2932b;
  --primary-blue: #1f6fd6;
  --text-dark: #2b2b2b;
  --text-muted: #5c5c5c;
  --border-color: #ddccae;
  --radius-sm: 8px;
  --radius-md: 10px;
  --radius-lg: 14px;
  --radius-pill: 30px;
  --font-family: 'Poppins', Arial, sans-serif;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

html, body {
  height: 100%;
  margin: 0;
  padding: 0;
  font-family: var(--font-family);
  background-color: var(--bg-body);
  color: var(--text-dark);
  overflow-x: hidden;
  overflow-y: auto;
}

body {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.topbar{
  background: #f2e6c9;
  height: 75px;
  padding: 0 55px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  box-sizing: border-box;
}

.topbar .logo{
  display: flex;
  align-items: center;
  justify-content: flex-start;
  height: 100%;
}

.topbar .logo img{
  width: 110px;
  display: block;
  position: static;
  padding-top: 20px;
}

.topbar a.logout {
  color: var(--primary-blue);
  font-weight: 600;
  text-decoration: none;
  font-size: 14px;
  transition: opacity 0.2s ease;
}

.topbar a.logout:hover {
  opacity: 0.8;
  text-decoration: underline;
}

.layout {
  display: flex;
  align-items: stretch;
  flex: 1;
}

.sidebar {
  width: 240px;
  background: var(--bg-sidebar);
  padding: 20px 14px;
  min-height: calc(100vh - 65px);
  flex-shrink: 0;
  border-right: 1px solid rgba(255, 255, 255, 0.4);
}

.sidebar a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 15px;
  margin-bottom: 6px;
  border-radius: var(--radius-md);
  text-decoration: none;
  color: var(--text-dark);
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s ease;
}

.sidebar a:hover {
  background: var(--bg-sidebar-hover);
}

.sidebar a.active {
  background: var(--primary-orange);
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(242, 147, 43, 0.35);
}

.sidebar .icon {
  font-size: 16px;
  width: 20px;
  text-align: center;
}

.main {
  flex: 1;
  padding: 28px 36px;
}

.main h1 {
  margin: 0 0 22px;
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
}

.table-card {
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

col.col-sn { width: 60px; }
col.col-name { width: 25%; }
col.col-email { width: 30%; }
col.col-role { width: 12%; }
col.col-joined { width: 18%; }
col.col-action { width: 90px; }

thead th {
  background: #d8c9a3;
  font-weight: 700;
  font-size: 12px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: #3d3326;
  padding: 14px 10px;
}

.text-center { text-align: center; }
.text-left { text-align: left; }

tbody tr {
  border-bottom: 1px solid var(--border-color);
  transition: background 0.15s ease-in-out;
}

tbody tr:last-child {
  border-bottom: none;
}

tbody tr:hover {
  background: #e6d8bb;
}

tbody td {
  padding: 12px 10px;
  font-size: 13px;
  vertical-align: middle;
}

.person {
  display: flex;
  align-items: center;
  gap: 12px;
}

.name {
  font-weight: 600;
  font-size: 14px;
  color: #1a1a1a;
}

.email, .role, .joined {
  color: #555555;
  font-size: 13px;
}

.view-btn {
  background: var(--primary-blue);
  color: white;
  border: none;
  padding: 6px 14px;
  border-radius: 16px;
  font-family: inherit;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 6px rgba(31,111,214,0.25);
  display: inline-block;
  text-decoration: none;
}

.view-btn:hover { background: #1656aa; transform: translateY(-1px); }


.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  z-index: 99999;
  justify-content: center;
  align-items: center;
  padding: 20px;
  box-sizing: border-box;
}

.modal-overlay.show { display: flex; }

.modal-card {
  width: 100%;
  max-width: 580px;
  max-height: 85vh;
  background: #ede1c6;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0,0,0,0.3);
  border: 1px solid rgba(255,255,255,0.7);
  animation: modalSlide 0.25s ease-out;
  display: flex;
  flex-direction: column;
}

@keyframes modalSlide {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-header {
  background: var(--bg-topbar);
  padding: 18px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
  flex-shrink: 0;
}

.modal-header h2 { margin: 0; font-size: 18px; font-weight: 700; color: var(--text-dark); }

.close-btn {
  background: transparent;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #665444;
  line-height: 1;
}

.close-btn:hover { color: #000; }

.modal-body {
  flex: 1;
  padding: 22px 26px;
  overflow-y: auto;
  box-sizing: border-box;
}

.modal-body::-webkit-scrollbar { width: 6px; }
.modal-body::-webkit-scrollbar-thumb { background: #cbb997; border-radius: 10px; }

.user-profile-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  background: #f5ecd7;
  padding: 16px;
  border-radius: 14px;
  border: 1px solid var(--border-color);
}

.avatar-large {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--primary-orange);
  color: white;
  font-size: 24px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(242,147,43,0.3);
}

.user-profile-title h3 { font-size: 18px; font-weight: 700; color: #1a1a1a; margin-bottom: 2px; }
.user-profile-title p { font-size: 13px; color: #665444; }

.section-head {
  font-size: 12px;
  font-weight: 700;
  color: var(--primary-orange);
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin: 16px 0 10px;
  border-bottom: 1.5px dashed #dfcfb0;
  padding-bottom: 4px;
}

.section-head:first-of-type { margin-top: 0; }

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px 16px;
  margin-bottom: 16px;
  background: #f5ecd7;
  padding: 14px 16px;
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.info-item label { display: block; font-size: 11px; font-weight: 700; color: #7a6350; text-transform: uppercase; }
.info-item span { font-size: 13.5px; font-weight: 600; color: #222; word-break: break-word; }

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 16px;
}

.stat-box {
  background: #fff8eb;
  padding: 12px;
  border-radius: 12px;
  text-align: center;
  border: 1px solid #e2d2b5;
}

.stat-number { font-size: 20px; font-weight: 800; color: var(--primary-orange); }
.stat-label { font-size: 11px; font-weight: 600; color: #665444; }

.modal-footer {
  padding: 14px 24px;
  background: var(--bg-topbar);
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  flex-shrink: 0;
}

.btn-chat {
  background: var(--primary-orange);
  color: white;
  border: none;
  padding: 9px 20px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 4px 12px rgba(242,147,43,0.3);
}

.btn-chat:hover { background: #e06600; }

.btn-close {
  background: white;
  border: 1px solid var(--border-color);
  padding: 9px 18px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
}

@media(max-width: 850px) {
  .layout {
    flex-direction: column;
  }
  .sidebar {
    width: 100%;
    min-height: auto;
    border-right: none;
    border-bottom: 1px solid var(--border-color);
  }
  .main { padding: 20px; }
  table { table-layout: auto; }
}
</style>
</head>

<body>

<div class="topbar">
   <div class="logo">
      <img src="../assets/images/logo.png" alt="PawLix logo">
    </div>
  <a class="logout" href="logout.php">Logout</a>
</div>

<div class="layout">

  <div class="sidebar">
    <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
    <a href="dogs.php"><span class="icon">🐾</span> Dogs</a>
    <a href="reported_dogs.php"><span class="icon">🚨</span> Report Dogs</a>
    <a href="adoption_requests.php"><span class="icon">📋</span> Adoption Request</a>
    <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    <a href="users.php" class="active"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">

    <h1>Registered Users</h1>

    <div class="table-card">
      <table>
        <colgroup>
          <col class="col-sn">
          <col class="col-name">
          <col class="col-email">
          <col class="col-role">
          <col class="col-joined">
          <col class="col-action">
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">S.N.</th>
            <th class="text-left">NAME</th>
            <th class="text-left">EMAIL</th>
            <th class="text-center">ROLE</th>
            <th class="text-center">JOINED DATE</th>
            <th class="text-center">ACTION</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$users || mysqli_num_rows($users) === 0): ?>
            <tr>
              <td colspan="6" class="text-center" style="padding:30px; color:#665444;">No users found.</td>
            </tr>
          <?php else: ?>
            <?php $i = 1; while($u = mysqli_fetch_assoc($users)) { ?>
            <?php 
              $user_id = intval($u['user_id']);
              $first_name = htmlspecialchars($u['first_name'] ?? 'User');
              $last_name = htmlspecialchars($u['last_name'] ?? '');
              $full_name = trim($first_name . ' ' . $last_name);
              $email = htmlspecialchars($u['email']);
              $phone = htmlspecialchars(!empty($u['phone']) ? $u['phone'] : 'Not provided');
              $address = htmlspecialchars(!empty($u['address']) ? $u['address'] : 'Not provided');
              $role = htmlspecialchars($u['role'] ?? 'User');
              $joined = date("M d, Y", strtotime($u['created_at'] ?? $u['joined_date'] ?? 'now'));
              $adoptions = intval($u['total_adoptions'] ?? 0);
              $reports = intval($u['total_reports'] ?? 0);
              $messages_cnt = intval($u['total_messages'] ?? 0);
              $initial = strtoupper(substr($first_name, 0, 1));
            ?>
            <tr>
              <td class="text-center"><?php echo $i++; ?></td>
              <td class="text-left">
                <div class="person">
                  <div class="name"><?php echo $full_name; ?></div>
                </div>
              </td>
              <td class="text-left email"><?php echo $email; ?></td>
              <td class="text-center role"><?php echo $role; ?></td>
              <td class="text-center joined"><?php echo $joined; ?></td>
              <td class="text-center">
                <button 
                  type="button" 
                  class="view-btn"
                  onclick="openUserModal(
                    <?php echo $user_id; ?>,
                    '<?php echo addslashes($full_name); ?>',
                    '<?php echo addslashes($email); ?>',
                    '<?php echo addslashes($phone); ?>',
                    '<?php echo addslashes($address); ?>',
                    '<?php echo addslashes($role); ?>',
                    '<?php echo addslashes($joined); ?>',
                    <?php echo $adoptions; ?>,
                    <?php echo $reports; ?>,
                    <?php echo $messages_cnt; ?>,
                    '<?php echo $initial; ?>'
                  )"
                >
                  View 
                </button>
              </td>
            </tr>
            <?php } ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</div>


<div class="modal-overlay" id="viewUserModal">
  <div class="modal-card">
    <div class="modal-header">
      <h2>👤 User Details</h2>
      <button type="button" class="close-btn" onclick="closeUserModal()">&times;</button>
    </div>

    <div class="modal-body">

      <div class="user-profile-header">
        <div class="avatar-large" id="mUserInitial">U</div>
        <div class="user-profile-title">
          <h3 id="mUserName">User Name</h3>
          <p id="mUserEmail">user@example.com</p>
        </div>
      </div>

      <div class="section-head">Contact & Account Information</div>
      <div class="info-grid">
        <div class="info-item"><label>Phone Number</label><span id="mUserPhone">-</span></div>
        <div class="info-item"><label>Role</label><span id="mUserRole">User</span></div>
        <div class="info-item"><label>Joined Date</label><span id="mUserJoined">-</span></div>
        <div class="info-item"><label>Residential Address</label><span id="mUserAddress">-</span></div>
      </div>

      <div class="section-head">Activity Overview</div>
      <div class="stats-grid">
        <div class="stat-box">
          <div class="stat-number" id="mUserAdoptions">0</div>
          <div class="stat-label">Adoption Requests</div>
        </div>
        <div class="stat-box">
          <div class="stat-number" id="mUserReports">0</div>
          <div class="stat-label">Dog Reports</div>
        </div>
        <div class="stat-box">
          <div class="stat-number" id="mUserMessages">0</div>
          <div class="stat-label">Messages</div>
        </div>
      </div>

    </div>

    <div class="modal-footer">
      <button type="button" class="btn-close" onclick="closeUserModal()">Close</button>
      <a href="#" id="mUserChatBtn" class="btn-chat">✉️ Direct Message User</a>
    </div>
  </div>
</div>

<script>
function openUserModal(id, name, email, phone, address, role, joined, adoptions, reports, messages, initial) {
    document.getElementById("mUserInitial").innerText = initial || "U";
    document.getElementById("mUserName").innerText = name;
    document.getElementById("mUserEmail").innerText = email;
    document.getElementById("mUserPhone").innerText = phone;
    document.getElementById("mUserAddress").innerText = address;
    document.getElementById("mUserRole").innerText = role;
    document.getElementById("mUserJoined").innerText = joined;
    
    document.getElementById("mUserAdoptions").innerText = adoptions;
    document.getElementById("mUserReports").innerText = reports;
    document.getElementById("mUserMessages").innerText = messages;
    
    document.getElementById("mUserChatBtn").href = "messages.php?user_id=" + id;
    
    document.getElementById("viewUserModal").classList.add("show");
}

function closeUserModal() {
    document.getElementById("viewUserModal").classList.remove("show");
}

window.addEventListener("click", function(e) {
    const modal = document.getElementById("viewUserModal");
    if (e.target === modal) {
        closeUserModal();
    }
});

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        closeUserModal();
    }
});
</script>

</body>
</html>