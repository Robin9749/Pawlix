<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include("../config/config.php");

$admin_id = intval($_SESSION['admin_id']);

/* SAFE HELPER TO ADD COLUMNS WITHOUT DUPLICATE COLUMN EXCEPTION IN PHP 8.1+ */
function safeAddColumn($conn, $table, $column, $definition) {
    try {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check && mysqli_num_rows($check) == 0) {
            mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    } catch (Throwable $e) {
        // Column already exists or error ignored safely
    }
}

safeAddColumn($conn, 'adoption_application', 'housing_type', "VARCHAR(100) DEFAULT ''");
safeAddColumn($conn, 'adoption_application', 'current_pets', "VARCHAR(100) DEFAULT ''");
safeAddColumn($conn, 'adoption_application', 'adoption_reason', "VARCHAR(255) DEFAULT ''");
safeAddColumn($conn, 'adoption_application', 'why_this_dog', "TEXT");

/* ================= UPDATE APPLICATION STATUS ================= */
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id = intval($_POST['application_id'] ?? 0);
    $new_status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Pending');
    $admin_note = trim($_POST['admin_note'] ?? '');

    if ($app_id > 0) {
        $update_sql = "UPDATE adoption_application SET status = '$new_status' WHERE application_id = $app_id";
        
        if (mysqli_query($conn, $update_sql)) {
            // Fetch applicant user_id and dog details to send notification
            $app_info = mysqli_query($conn, "
                SELECT a.user_id, d.name AS dog_name 
                FROM adoption_application a
                LEFT JOIN dog d ON a.dog_id = d.dog_id
                WHERE a.application_id = $app_id 
                LIMIT 1
            ");
            
            if ($app_info && $info = mysqli_fetch_assoc($app_info)) {
                $target_user_id = intval($info['user_id']);
                $dog_name = htmlspecialchars($info['dog_name'] ?? 'Dog');
                
                // Send direct message notification if admin provided feedback note
                if (!empty($admin_note)) {
                    $note_db = mysqli_real_escape_string($conn, $admin_note);
                    $subject_db = mysqli_real_escape_string($conn, "Adoption Update: $dog_name");
                    $msg_text = mysqli_real_escape_string($conn, "Your adoption application status for $dog_name has been updated to '$new_status'.\n\nShelter Note: $admin_note");
                    
                    try {
                        mysqli_query($conn, "
                            INSERT INTO messages (user_id, admin_id, sender_type, subject, message, is_read)
                            VALUES ($target_user_id, $admin_id, 'admin', '$subject_db', '$msg_text', 0)
                        ");
                    } catch (Throwable $t) {}
                }
            }
            
            header("Location: adoption_requests.php?msg=updated");
            exit();
        } else {
            $error_msg = "Failed to update status: " . mysqli_error($conn);
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $success_msg = "Adoption request updated successfully! User notification sent.";
}

/* ================= FILTER & FETCH REQUESTS ================= */
$filter = isset($_GET['status']) ? $_GET['status'] : 'All';

$where_clause = "";
if ($filter !== 'All') {
    $safe_filter = mysqli_real_escape_string($conn, $filter);
    $where_clause = "WHERE adoption_application.status = '$safe_filter'";
}

$query = "
    SELECT
        adoption_application.*,
        user.first_name,
        user.last_name,
        user.email,
        user.phone AS user_phone,
        user.address AS user_address,
        dog.name AS dog_name,
        dog.breed,
        dog.age AS dog_age,
        dog.gender AS dog_gender,
        dog.image AS dog_image
    FROM adoption_application
    JOIN user ON adoption_application.user_id = user.user_id
    JOIN dog ON adoption_application.dog_id = dog.dog_id
    $where_clause
    ORDER BY application_date DESC, application_id DESC
";

$requests = mysqli_query($conn, $query);

/* ================= COUNTS ================= */
$result = mysqli_query($conn, "SELECT status, COUNT(*) total FROM adoption_application GROUP BY status");
$counts = array("Pending" => 0, "Approved" => 0, "Rejected" => 0);
while ($row = mysqli_fetch_assoc($result)) {
    $counts[$row['status']] = $row['total'];
}

$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM adoption_application");
$total = mysqli_fetch_assoc($totalResult)['total'];

/* HELPER FUNCTION TO FORMAT ADOPTION REASON CODE */
function formatReasonLabel($code) {
    switch(strtolower(trim($code))) {
        case 'companion': return 'Family Companion / Pet';
        case 'active-partner': return 'Active Outdoor Partner';
        case 'emotional-support': return 'Emotional Support & Companionship';
        case 'second-pet': return 'Companion for Current Pet';
        case 'watchdog': return 'Guard / Watch Dog';
        default: return !empty($code) ? ucfirst(str_replace('-', ' ', $code)) : 'Not specified';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Adoption Requests | PawLix Admin</title>

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
  --radius-lg: 16px;
  --radius-pill: 30px;
  --font-family: 'Poppins', Arial, sans-serif;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  min-height: 100vh;
  font-family: var(--font-family);
  background-color: var(--bg-body);
  color: var(--text-dark);
}

body { display: flex; flex-direction: column; }

/* TOPBAR */
.topbar {
  background: var(--bg-topbar);
  padding: 20px 45px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #ffffff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
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

.topbar a.logout { color: var(--primary-blue); font-weight: 600; text-decoration: none; font-size: 14px; }

.layout { display: flex; flex: 1; }

.sidebar {
  width: 240px;
  background: var(--bg-sidebar);
  padding: 20px 14px;
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

.sidebar a:hover { background: var(--bg-sidebar-hover); }
.sidebar a.active { background: var(--primary-orange); color: #ffffff; box-shadow: 0 4px 12px rgba(242, 147, 43, 0.35); }
.sidebar .icon { font-size: 16px; width: 20px; text-align: center; }

.main { flex: 1; padding: 28px 36px; }

.main-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.main h1 { font-size: 24px; font-weight: 700; color: #1a1a1a; margin: 0; }

.filter-pills { display: flex; gap: 8px; }

.filter-pill {
  padding: 7px 16px;
  border-radius: 20px;
  background: #dfcfb0;
  color: var(--text-dark);
  text-decoration: none;
  font-size: 12.5px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.filter-pill:hover, .filter-pill.active {
  background: var(--primary-orange);
  color: white;
}

.alert-success {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
  padding: 12px 18px;
  border-radius: 10px;
  font-size: 13.5px;
  font-weight: 600;
  margin-bottom: 18px;
}

.table-card {
  background: var(--bg-card);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: 0 3px 10px rgba(0,0,0,0.05);
  border: 1px solid var(--border-color);
}

table { width: 100%; border-collapse: collapse; table-layout: fixed; }
col.col-sn { width: 55px; }
col.col-applicant { width: 26%; }
col.col-dog { width: 20%; }
col.col-date { width: 18%; }
col.col-status { width: 16%; }
col.col-action { width: 100px; }

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

tbody tr { border-bottom: 1px solid var(--border-color); transition: background 0.15s ease; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #e6d8bb; }
tbody td { padding: 13px 10px; font-size: 13px; vertical-align: middle; }

.person-name { font-weight: 700; color: #1a1a1a; font-size: 13.5px; }
.person-email { font-size: 11.5px; color: #665444; }

.status {
  padding: 5px 14px;
  border-radius: var(--radius-pill);
  font-weight: 700;
  font-size: 11.5px;
  display: inline-block;
}

.status-approved { background: #bfe3c4; color: #1e6e2e; }
.status-rejected { background: #f9cace; color: #ed1c31; }
.status-pending { background: #f6cba3; color: #a15c00; }

.btn-view {
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
}

.btn-view:hover { background: #1656aa; transform: translateY(-1px); }

/* ================= PERFECT SCROLLABLE MODAL OVERLAY ================= */
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
  max-width: 680px;
  max-height: 86vh;
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
  padding: 18px 26px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
  flex-shrink: 0;
}

.modal-header h2 { margin: 0; font-size: 18.5px; font-weight: 700; color: var(--text-dark); }

.close-btn {
  background: transparent;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #665444;
  line-height: 1;
}

.close-btn:hover { color: #000; }

/* MODAL SCROLLABLE BODY */
.modal-body {
  flex: 1;
  padding: 24px 28px;
  overflow-y: auto;
  overscroll-behavior: contain;
  box-sizing: border-box;
}

.modal-body::-webkit-scrollbar { width: 6px; }
.modal-body::-webkit-scrollbar-thumb { background: #cbb997; border-radius: 10px; }

.section-head {
  font-size: 12.5px;
  font-weight: 700;
  color: var(--primary-orange);
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin: 18px 0 10px;
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
  padding: 14px 18px;
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

.info-item label { display: block; font-size: 11px; font-weight: 700; color: #7a6350; text-transform: uppercase; }
.info-item span { font-size: 13.5px; font-weight: 600; color: #222; word-break: break-word; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px; }

.form-select, .form-textarea {
  width: 100%;
  padding: 11px 14px;
  border: 1px solid #d8c6a5;
  border-radius: 10px;
  background: #fffaf0;
  font-family: inherit;
  font-size: 13.5px;
  outline: none;
  box-sizing: border-box;
  transition: border-color 0.2s;
}

.form-select:focus, .form-textarea:focus { border-color: var(--primary-orange); background: #ffffff; }

.modal-footer {
  padding: 16px 26px;
  background: var(--bg-topbar);
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  flex-shrink: 0;
}

.btn-cancel {
  background: white;
  border: 1px solid var(--border-color);
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 13.5px;
  cursor: pointer;
}

.btn-save {
  background: var(--primary-orange);
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 13.5px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(242,147,43,0.3);
  transition: background 0.2s;
}

.btn-save:hover { background: #e06600; }
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
    <a href="adoption_requests.php" class="active"><span class="icon">📋</span> Adoption Request</a>
    <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    <a href="users.php"><span class="icon">👤</span> Users</a>
    <a href="settings.php"><span class="icon">⚙️</span> Settings</a>
  </div>

  <div class="main">

    <div class="main-header">
      <h1>Adoption Requests (<?php echo $total; ?>)</h1>

      <div class="filter-pills">
        <a href="adoption_requests.php?status=All" class="filter-pill <?php echo $filter === 'All' ? 'active' : ''; ?>">All (<?php echo $total; ?>)</a>
        <a href="adoption_requests.php?status=Pending" class="filter-pill <?php echo $filter === 'Pending' ? 'active' : ''; ?>">Pending (<?php echo $counts['Pending']; ?>)</a>
        <a href="adoption_requests.php?status=Approved" class="filter-pill <?php echo $filter === 'Approved' ? 'active' : ''; ?>">Approved (<?php echo $counts['Approved']; ?>)</a>
        <a href="adoption_requests.php?status=Rejected" class="filter-pill <?php echo $filter === 'Rejected' ? 'active' : ''; ?>">Rejected (<?php echo $counts['Rejected']; ?>)</a>
      </div>
    </div>

    <?php if ($success_msg): ?>
      <div class="alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <div class="table-card">
      <table>
        <colgroup>
          <col class="col-sn">
          <col class="col-applicant">
          <col class="col-dog">
          <col class="col-date">
          <col class="col-status">
          <col class="col-action">
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">S.N.</th>
            <th class="text-left">APPLICANT</th>
            <th class="text-left">DOG</th>
            <th class="text-center">REQUEST DATE</th>
            <th class="text-center">STATUS</th>
            <th class="text-center">ACTION</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$requests || mysqli_num_rows($requests) === 0): ?>
            <tr>
              <td colspan="6" class="text-center" style="padding:30px; color:#665444;">No adoption requests found for this filter.</td>
            </tr>
          <?php else: ?>
            <?php $i = 1; while($r = mysqli_fetch_assoc($requests)) { ?>
            <?php 
              $app_id = intval($r['application_id'] ?? $r['request_id']);
              $applicant_name = htmlspecialchars($r['first_name'] . " " . $r['last_name']);
              $applicant_email = htmlspecialchars($r['email']);
              $applicant_phone = htmlspecialchars(!empty($r['user_phone']) ? $r['user_phone'] : ($r['phone'] ?? 'N/A'));
              $applicant_address = htmlspecialchars(!empty($r['user_address']) ? $r['user_address'] : ($r['address'] ?? 'N/A'));
              
              $dog_name = htmlspecialchars($r['dog_name'] ?? $r['name']);
              $dog_breed = htmlspecialchars($r['breed'] ?? 'Unknown');
              $dog_age = intval($r['dog_age'] ?? 0);
              $dog_age_txt = $dog_age > 0 ? $dog_age . ' Year' . ($dog_age > 1 ? 's' : '') : 'N/A';
              $dog_gender = htmlspecialchars($r['dog_gender'] ?? 'N/A');

              $housing_type = htmlspecialchars(!empty($r['housing_type']) ? ucfirst($r['housing_type']) : 'Not specified');
              $current_pets = htmlspecialchars(!empty($r['current_pets']) ? ucfirst($r['current_pets']) : 'None');
              $adoption_reason = htmlspecialchars(formatReasonLabel($r['adoption_reason'] ?? ''));
              
              $why_raw = trim($r['why_this_dog'] ?? '');
              $why_this_dog = !empty($why_raw) ? htmlspecialchars($why_raw) : 'Not specified in application.';

              $req_date = date("M d, Y", strtotime($r['application_date'] ?? $r['created_at'] ?? 'now'));
              $curr_status = htmlspecialchars($r['status'] ?? 'Pending');
            ?>
            <tr>
              <td class="text-center"><?php echo $i++; ?></td>
              <td class="text-left">
                <div class="person-name"><?php echo $applicant_name; ?></div>
                <div class="person-email"><?php echo $applicant_email; ?></div>
              </td>
              <td class="text-left">
                <strong><?php echo $dog_name; ?></strong>
                <div style="font-size:11.5px; color:#665444;"><?php echo $dog_breed; ?></div>
              </td>
              <td class="text-center"><?php echo $req_date; ?></td>
              <td class="text-center">
                <?php
                  $cls = "status-pending";
                  if ($curr_status == "Approved") $cls = "status-approved";
                  if ($curr_status == "Rejected") $cls = "status-rejected";
                ?>
                <span class="status <?php echo $cls; ?>"><?php echo $curr_status; ?></span>
              </td>
              <td class="text-center">
                <button 
                  type="button" 
                  class="btn-view"
                  onclick="openDetailModal(
                    <?php echo $app_id; ?>, 
                    '<?php echo addslashes($applicant_name); ?>', 
                    '<?php echo addslashes($applicant_email); ?>', 
                    '<?php echo addslashes($applicant_phone); ?>', 
                    '<?php echo addslashes($applicant_address); ?>', 
                    '<?php echo addslashes($dog_name); ?>', 
                    '<?php echo addslashes($dog_breed); ?>', 
                    '<?php echo addslashes($dog_age_txt); ?>', 
                    '<?php echo addslashes($dog_gender); ?>', 
                    '<?php echo addslashes($housing_type); ?>', 
                    '<?php echo addslashes($current_pets); ?>', 
                    '<?php echo addslashes($adoption_reason); ?>', 
                    '<?php echo addslashes($why_this_dog); ?>', 
                    '<?php echo addslashes($req_date); ?>', 
                    '<?php echo addslashes($curr_status); ?>'
                  )"
                >
                  View Details
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

<!-- SCROLLABLE DETAIL & EDIT MODAL POPUP -->
<div class="modal-overlay" id="detailModal">
  <div class="modal-card">
    <div class="modal-header">
      <h2>📋 Adoption Application Review</h2>
      <button type="button" class="close-btn" onclick="closeDetailModal()">&times;</button>
    </div>

    <form method="POST" action="adoption_requests.php" style="display:flex; flex-direction:column; flex:1; min-height:0;">
      <div class="modal-body">
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="application_id" id="modalAppId" value="">

        <!-- Step 1: Applicant Info -->
        <div class="section-head">👤 Applicant Information</div>
        <div class="info-grid">
          <div class="info-item"><label>Full Name</label><span id="mApplicantName">-</span></div>
          <div class="info-item"><label>Email Address</label><span id="mApplicantEmail">-</span></div>
          <div class="info-item"><label>Phone Number</label><span id="mApplicantPhone">-</span></div>
          <div class="info-item"><label>Residential Address</label><span id="mApplicantAddress">-</span></div>
        </div>

        <!-- Step 2: Selected Dog -->
        <div class="section-head">🐾 Selected Dog</div>
        <div class="info-grid">
          <div class="info-item"><label>Dog Name</label><span id="mDogName">-</span></div>
          <div class="info-item"><label>Breed</label><span id="mDogBreed">-</span></div>
          <div class="info-item"><label>Age</label><span id="mDogAge">-</span></div>
          <div class="info-item"><label>Gender</label><span id="mDogGender">-</span></div>
        </div>

        <!-- Step 3 & 4: Household & Adoption Info -->
        <div class="section-head">🏠 Household & Adoption Details</div>
        <div class="info-grid">
          <div class="info-item"><label>Housing Type</label><span id="mHousingType">-</span></div>
          <div class="info-item"><label>Current Pets</label><span id="mCurrentPets">-</span></div>
          <div class="info-item"><label>Primary Reason</label><span id="mAdoptionReason">-</span></div>
          <div class="info-item"><label>Request Date</label><span id="mReqDate">-</span></div>
        </div>

        <div class="form-group">
          <label>Why do you want to adopt this dog?</label>
          <div id="mWhyThisDog" style="background:#f5ecd7; padding:14px; border-radius:10px; font-size:13.5px; color:#4a3c31; border:1px solid #dfcfb0; white-space:pre-wrap; line-height:1.5;">-</div>
        </div>

        <!-- Step 5: Admin Action & Feedback -->
        <div class="section-head">✍️ Admin Decision & Response</div>
        <div class="form-group">
          <label for="modalStatusSelect">Update Status</label>
          <select name="status" id="modalStatusSelect" class="form-select" required>
            <option value="Pending">Pending (Under Review)</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>

        <div class="form-group">
          <label for="modalAdminNote">Admin Note / Feedback Message to User</label>
          <textarea 
            name="admin_note" 
            id="modalAdminNote" 
            class="form-textarea" 
            rows="3" 
            placeholder="Type feedback or note to send with this status update..."
          ></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeDetailModal()">Cancel</button>
        <button type="submit" class="btn-save">Save & Send Update ➔</button>
      </div>
    </form>
  </div>
</div>

<script>
function openDetailModal(id, name, email, phone, address, dogName, dogBreed, dogAge, dogGender, housingType, currentPets, adoptionReason, whyThisDog, reqDate, status) {
    document.getElementById("modalAppId").value = id;
    
    document.getElementById("mApplicantName").innerText = name;
    document.getElementById("mApplicantEmail").innerText = email;
    document.getElementById("mApplicantPhone").innerText = phone;
    document.getElementById("mApplicantAddress").innerText = address;
    
    document.getElementById("mDogName").innerText = dogName;
    document.getElementById("mDogBreed").innerText = dogBreed;
    document.getElementById("mDogAge").innerText = dogAge;
    document.getElementById("mDogGender").innerText = dogGender;
    
    document.getElementById("mHousingType").innerText = housingType;
    document.getElementById("mCurrentPets").innerText = currentPets;
    document.getElementById("mAdoptionReason").innerText = adoptionReason;
    document.getElementById("mReqDate").innerText = reqDate;
    document.getElementById("mWhyThisDog").innerText = whyThisDog || "Not specified.";
    
    document.getElementById("modalStatusSelect").value = status;
    document.getElementById("modalAdminNote").value = "";
    
    const modal = document.getElementById("detailModal");
    modal.classList.add("show");
    
    // Auto scroll modal body to top when opened
    const modalBody = modal.querySelector(".modal-body");
    if (modalBody) { modalBody.scrollTop = 0; }
}

function closeDetailModal() {
    document.getElementById("detailModal").classList.remove("show");
}

window.addEventListener("click", function(e) {
    const modal = document.getElementById("detailModal");
    if (e.target === modal) {
        closeDetailModal();
    }
});

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        closeDetailModal();
    }
});
</script>

</body>
</html>