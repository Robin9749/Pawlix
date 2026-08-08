<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

$success = "";
$error = "";

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if (mysqli_query($conn, "DELETE FROM report_dogs WHERE id = $id")) {
        header("Location: reported_dogs.php?msg=deleted");
        exit();
    } else {
        $error = "Failed to delete report.";
    }
}

if (isset($_POST['update_status'])) {
    $report_id = intval($_POST['report_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    if (mysqli_query($conn, "UPDATE report_dogs SET status = '$status' WHERE id = $report_id")) {
        $success = "Report status updated successfully!";
    } else {
        $error = "Database Error: " . mysqli_error($conn);
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $success = "Report deleted successfully!";
}

$reports = mysqli_query($conn, "SELECT * FROM report_dogs ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reported Dogs | PawLix Admin</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

:root {
  --bg-body: #e8dcc0;
  --bg-topbar: #f2e6c9;
  --bg-card: #ede1c6;
  --bg-sidebar: #e8dcc0;
  --bg-sidebar-hover: #ddceac;
  --primary-orange: #f2932b;
  --primary-orange-hover: #e08420;
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

.topbar {
  background: var(--bg-topbar);
  padding: 20px 45px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
  z-index: 10;
}

.topbar .logo {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-dark);
  display: flex;
  align-items: center;
  gap: 8px;
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

.main-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.main-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
}

.alert-success {
  background: #d4edda;
  color: #155724;
  padding: 12px 18px;
  border-radius: var(--radius-sm);
  margin-bottom: 20px;
  font-weight: 600;
  font-size: 14px;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  padding: 12px 18px;
  border-radius: var(--radius-sm);
  margin-bottom: 20px;
  font-weight: 600;
  font-size: 14px;
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

col.col-sn { width: 50px; }
col.col-photo { width: 15%; }
col.col-reporter { width: 18%; }
col.col-details { width: 22%; }
col.col-location { width: 22%; }
col.col-status { width: 14%; }
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

.dog-photo {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  object-fit: cover;
  background: #d8cba9;
  border: 1px solid rgba(0,0,0,0.06);
}

.reporter-name { font-weight: 600; color: #1a1a1a; }
.reporter-phone { color: var(--primary-blue); font-size: 12px; font-weight: 500; }
.reporter-email { color: var(--text-muted); font-size: 11px; }

.location-area { font-weight: 600; color: #1a1a1a; }
.landmark-info { color: var(--text-muted); font-size: 12px; }

.status {
  padding: 5px 12px;
  border-radius: var(--radius-pill);
  font-weight: 600;
  font-size: 12px;
  display: inline-block;
}

.status-pending { background: #f6cba3; color: #a15c00; }
.status-dispatched { background: #ffe29a; color: #8a5700; }
.status-rescued { background: #b9d3ee; color: #1958ab; }
.status-adoption { background: #bfe3c4; color: #1e6e2e; }
.status-closed { background: #f5bcbc; color: #b3261e; }

.action-links {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.edit-btn {
  background: #ede8f8;
  color: #5b3fd6;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  cursor: pointer;
  border: none;
  transition: all 0.2s ease;
}

.edit-btn:hover {
  background: #5b3fd6;
  color: #ffffff;
}

.delete-btn {
  background: #fde8e8;
  color: #b3261e;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s ease;
}

.delete-btn:hover {
  background: #b3261e;
  color: #ffffff;
}

/* Modal Overlay & Card */
.modal-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(4px);
  z-index: 1000;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.modal-content {
  background: var(--bg-card);
  width: 100%;
  max-width: 550px;
  border-radius: 14px;
  padding: 26px;
  box-shadow: 0 15px 30px rgba(0,0,0,0.25);
  position: relative;
  max-height: 90vh;
  overflow-y: auto;
  animation: popup 0.3s ease-out;
}

@keyframes popup {
  from { transform: scale(0.85); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  border-bottom: 2px solid var(--border-color);
  padding-bottom: 10px;
}

.modal-header h2 {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-dark);
}

.close-modal {
  background: none;
  border: none;
  font-size: 26px;
  font-weight: bold;
  cursor: pointer;
  color: #666;
}

.close-modal:hover { color: var(--primary-orange); }

.modal-body form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.modal-body label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 600;
}

.modal-body select {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  outline: none;
  background: #ffffff;
  font-size: 13px;
  font-family: inherit;
}

.modal-body select:focus {
  border-color: var(--primary-orange);
  box-shadow: 0 0 5px rgba(242,147,43,.3);
}

.submit-btn {
  background: var(--primary-orange);
  color: white;
  border: none;
  padding: 13px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 15px;
  font-weight: 600;
  transition: background 0.3s;
}

.submit-btn:hover { background: var(--primary-orange-hover); }

@media (max-width: 850px) {
  .layout { flex-direction: column; }
  .sidebar { width: 100%; min-height: auto; border-right: none; border-bottom: 1px solid var(--border-color); }
  .main { padding: 20px; }
  table { table-layout: auto; }
}
</style>
</head>

<body>

<div class="topbar">
  <div class="logo">PawLix</div>
  <a class="logout" href="logout.php">Logout</a>
</div>

<div class="layout">

  <!-- Sidebar -->
  <div class="sidebar">
    <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
    <a href="dogs.php"><span class="icon">🐾</span> Dogs</a>
    <a href="reported_dogs.php" class="active"><span class="icon">🚨</span> Reported Dogs</a>
    <a href="adoption_requests.php"><span class="icon">📋</span> Adoption Request</a>
    <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">

    <?php if ($success != "") { ?>
      <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php } ?>

    <?php if ($error != "") { ?>
      <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <div class="main-header">
      <h1>Reported Dogs</h1>
    </div>

    <div class="table-card">
      <table>
        <colgroup>
          <col class="col-sn">
          <col class="col-photo">
          <col class="col-reporter">
          <col class="col-details">
          <col class="col-location">
          <col class="col-status">
          <col class="col-action">
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">S.N.</th>
            <th class="text-center">Photo</th>
            <th class="text-left">Reporter</th>
            <th class="text-left">Report Details</th>
            <th class="text-left">Location & Landmark</th>
            <th class="text-center">Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $i = 1; 
          if (mysqli_num_rows($reports) > 0) {
            while($r = mysqli_fetch_assoc($reports)) { 
          ?>
          <tr>
            <td class="text-center"><?php echo $i++; ?></td>
            <td class="text-center">
              <img class="dog-photo" src="../uploads/<?php echo htmlspecialchars($r['dog_photo']); ?>" onerror="this.src='../assets/img/default.jpg';" alt="Dog Photo">
            </td>
            <td class="text-left">
              <div class="reporter-name"><?php echo htmlspecialchars($r['name']); ?></div>
              <div class="reporter-phone">📞 <?php echo htmlspecialchars($r['phone_number']); ?></div>
              <div class="reporter-email"><?php echo htmlspecialchars($r['email']); ?></div>
            </td>
            <td class="text-left">
              <strong><?php echo htmlspecialchars($r['report_type']); ?> Dog</strong>
              <div style="font-size: 12px; color: var(--text-muted);">
                Color: <?php echo htmlspecialchars($r['dog_color']); ?> • Condition: <strong><?php echo htmlspecialchars($r['dog_condition']); ?></strong>
              </div>
            </td>
            <td class="text-left">
              <div class="location-area">📍 <?php echo htmlspecialchars($r['location_area'] . ", " . $r['city_municipality']); ?></div>
              <div class="landmark-info">Landmark: <?php echo htmlspecialchars($r['landmark']); ?></div>
            </td>
            <td class="text-center">
              <?php
                $st = $r['status'];
                $cls = "status-pending";
                if ($st == 'Rescue Dispatched') $cls = "status-dispatched";
                if ($st == 'Rescued' || $st == 'Medical Care') $cls = "status-rescued";
                if ($st == 'Ready for Adoption') $cls = "status-adoption";
                if ($st == 'Closed') $cls = "status-closed";
              ?>
              <span class="status <?php echo $cls; ?>"><?php echo htmlspecialchars($st); ?></span>
            </td>
            <td class="text-center">
              <div class="action-links">
                <!-- Edit Status Modal Trigger Button -->
                <button type="button" class="edit-btn" onclick="openStatusModal(<?php echo $r['id']; ?>, '<?php echo $r['status']; ?>')" title="Update Status">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                </button>
                <!-- Delete Button -->
                <a class="delete-btn" href="reported_dogs.php?delete=<?php echo $r['id']; ?>" onclick="return confirm('Delete this report?');" title="Delete">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </a>
              </div>
            </td>
          </tr>
          <?php 
            } 
          } else { 
          ?>
          <tr>
            <td colspan="7" class="text-center" style="padding: 20px; color: var(--text-muted);">No dog reports found.</td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

  </div>

</div>

<!-- Update Status Modal -->
<div class="modal-overlay" id="statusModal">
  <div class="modal-content">
    
    <div class="modal-header">
      <h2>Update Report Status</h2>
      <button type="button" class="close-modal" onclick="closeStatusModal()">&times;</button>
    </div>

    <div class="modal-body">
      <form method="POST" action="reported_dogs.php">
        <input type="hidden" name="report_id" id="modal_report_id">

        <div>
          <label>Select Rescue Status</label>
          <select name="status" id="modal_status_select" required>
            <option value="Pending">🚨 Pending</option>
            <option value="Rescue Dispatched">🚑 Rescue Dispatched</option>
            <option value="Rescued">🏥 Rescued</option>
            <option value="Medical Care">💉 Medical Care</option>
            <option value="Ready for Adoption">🐾 Ready for Adoption</option>
            <option value="Closed">✅ Closed</option>
          </select>
        </div>

        <button type="submit" name="update_status" class="submit-btn">Update Status</button>
      </form>
    </div>

  </div>
</div>

<script>
function openStatusModal(id, currentStatus) {
  document.getElementById('modal_report_id').value = id;
  document.getElementById('modal_status_select').value = currentStatus;
  document.getElementById('statusModal').style.display = 'flex';
}

function closeStatusModal() {
  document.getElementById('statusModal').style.display = 'none';
}

window.onclick = function(event) {
  var modal = document.getElementById('statusModal');
  if (event.target == modal) {
    closeStatusModal();
  }
}
</script>

</body>
</html>