<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
	header('Location: ../dashboard.php');
	exit;
}

require_once __DIR__ . '/../../includes/db.php';

$username = trim((string) ($_SESSION['username'] ?? 'Unknown Admin'));

$cssHref = 'style.css';
$logoutHref = '../../auth/logout.php';
if (basename((string) ($_SERVER['PHP_SELF'] ?? '')) === 'dashboard.php') {
	$cssHref = 'admin/style.css';
	$logoutHref = '../auth/logout.php';
}

$section = 'dashboard';
if (isset($_GET['section'])) {
	$section = $_GET['section'];
	if (!in_array($section, ['dashboard', 'manage-users', 'manage-submissions', 'reports', 'profile'])) {
		$section = 'dashboard';
	}
}

$sectionTitles = [
	'dashboard'           => 'Dashboard',
	'manage-users'        => 'Διαχείριση Χρηστών',
	'manage-submissions'  => 'Διαχείριση Δηλώσεων',
	'reports'             => 'Αναφορές',
	'profile'             => 'Προφίλ',
];

$profileMsg = '';
$userMsg = '';
$submissionMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_submission_id'])) {
	$deleteId = (int) $_POST['delete_submission_id'];
	$pdo->prepare('DELETE FROM declarations WHERE id = :id')
		->execute([':id' => $deleteId]);
	$submissionMsg = 'deleted';
	$section = 'manage-submissions';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
	$deleteId = (int) $_POST['delete_user_id'];
	if ($deleteId !== (int) $_SESSION['user_id']) {
		$pdo->prepare('DELETE FROM users WHERE id = :id AND role != :role')
			->execute([':id' => $deleteId, ':role' => 'admin']);
		$userMsg = 'deleted';
	}
	$section = 'manage-users';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
	$newU = trim($_POST['new_username'] ?? '');
	$newE = trim($_POST['new_email'] ?? '');
	$newP = $_POST['new_password'] ?? '';
	$newR = in_array($_POST['new_role'] ?? '', ['citizen', 'politician']) ? $_POST['new_role'] : 'citizen';
	if ($newU !== '' && $newE !== '' && $newP !== '') {
		$hash = password_hash($newP, PASSWORD_DEFAULT);
		$pdo->prepare('INSERT INTO users (username, email, password_hash, role, created_at) VALUES (:u, :e, :p, :r, NOW())')
			->execute([':u' => $newU, ':e' => $newE, ':p' => $hash, ':r' => $newR]);
		$userMsg = 'created';
	} else {
		$userMsg = 'create_error';
	}
	$section = 'manage-users';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
	$newUsername     = trim($_POST['new_username'] ?? '');
	$newPassword     = $_POST['new_password'] ?? '';
	$confirmPassword = $_POST['confirm_password'] ?? '';
	if ($newUsername !== '' && $newPassword !== '' && $newPassword === $confirmPassword) {
		$hash = password_hash($newPassword, PASSWORD_DEFAULT);
		$stmt = $pdo->prepare('UPDATE users SET username = :u, password_hash = :p WHERE id = :id');
		$stmt->execute([':u' => $newUsername, ':p' => $hash, ':id' => $_SESSION['user_id']]);
		$_SESSION['username'] = $newUsername;
		$username = $newUsername;
		$profileMsg = 'success';
	} else {
		$profileMsg = 'error';
	}
}

$stats = [
	'users' => 0,
	'citizens' => 0,
	'politicians' => 0,
	'submissions' => 0,
];

$users_list        = [];
$declarations_list = [];

try {
	$countUsersStmt = $pdo->prepare('SELECT COUNT(*) FROM users');
	$countUsersStmt->execute();
	$stats['users'] = (int) $countUsersStmt->fetchColumn();

	$countCitizensStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'citizen'");
	$countCitizensStmt->execute();
	$stats['citizens'] = (int) $countCitizensStmt->fetchColumn();

	$countPoliticiansStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'politician'");
	$countPoliticiansStmt->execute();
	$stats['politicians'] = (int) $countPoliticiansStmt->fetchColumn();

	$countSubmissionsStmt = $pdo->prepare('SELECT COUNT(*) FROM declarations');
	$countSubmissionsStmt->execute();
	$stats['submissions'] = (int) $countSubmissionsStmt->fetchColumn();

	if ($section === 'manage-users') {
		$usersStmt = $pdo->prepare('SELECT id, username, email, role, created_at FROM users WHERE role != :role ORDER BY created_at');
		$usersStmt->execute([':role' => 'admin']);
		$users_list = $usersStmt->fetchAll();
	}
	if ($section === 'manage-submissions') {
		$declarationsStmt = $pdo->prepare('SELECT d.id, d.user_id, u.username, d.declaration_year, d.party, d.position, d.province, d.properties, d.vehicles, d.shares, d.debts, d.income, d.created_at FROM declarations d JOIN users u ON d.user_id = u.id ORDER BY d.id');
		$declarationsStmt->execute();
		$declarations_list = $declarationsStmt->fetchAll();
	}
} catch (PDOException $exception) {
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>ΠΟΘΕΝ ΕΣΧΕΣ — Admin Panel</title>
	<link rel="stylesheet" href="<?= htmlspecialchars($cssHref, ENT_QUOTES, 'UTF-8') ?>">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="site-header-admin">
	<div>
		<div class="logo-title">ΠΟΘΕΝ ΕΣΧΕΣ</div>
		<div class="logo-sub">Δηλώσεις Περιουσιακής &amp; Επαγγελματικής Κατάστασης</div>
	</div>
	<div class="admin-badge">ADMIN PANEL</div>
</div>

	<div class="admin-layout">
		<aside class="sidebar">
			<div class="sidebar-brand">
				<div class="brand-title">Admin Panel</div>
				<div class="brand-user"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></div>
			</div>
			<nav class="sidebar-nav">
				<a href="?section=dashboard" class="nav-item <?= $section === 'dashboard' ? 'active' : '' ?>">
					<span class="nav-icon">D</span> Dashboard
				</a>
				<a href="?section=manage-users" class="nav-item <?= $section === 'manage-users' ? 'active' : '' ?>">
					<span class="nav-icon">U</span> Manage Users
				</a>
				<a href="?section=manage-submissions" class="nav-item <?= $section === 'manage-submissions' ? 'active' : '' ?>">
					<span class="nav-icon">S</span> Manage Submissions
				</a>
				<a href="?section=reports" class="nav-item <?= $section === 'reports' ? 'active' : '' ?>">
					<span class="nav-icon">R</span> Reports
				</a>
				<a href="?section=profile" class="nav-item <?= $section === 'profile' ? 'active' : '' ?>">
					<span class="nav-icon">P</span> Profile
				</a>
			</nav>
			<div class="sidebar-bottom">
				<a class="sidebar-logout" href="<?= htmlspecialchars($logoutHref, ENT_QUOTES, 'UTF-8') ?>">Logout</a>
			</div>
		</aside>

		<div class="admin-content">
			<div class="<?= $section === 'manage-submissions' ? 'content-header_v2' : 'content-header' ?>">
					<h2><?= htmlspecialchars($sectionTitles[$section] ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></h2>

			<div class="content-body">
				<?php if ($section === 'dashboard'): ?>
				<div class="stats-row">
					<div class="stat-card">
						<span class="stat-label">Εγγεγραμμένοι Χρήστες</span>
						<span class="stat-value"><?= $stats['users'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Υποβολές</span>
						<span class="stat-value"><?= $stats['submissions'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Πολίτες</span>
						<span class="stat-value"><?= $stats['citizens'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Πολιτικοί</span>
						<span class="stat-value"><?= $stats['politicians'] ?></span>
					</div>
				</div>

				<?php elseif ($section === 'manage-users'): ?>
				<?php if ($userMsg === 'created'): ?>
				<div class="alert-success">Ο χρήστης δημιουργήθηκε επιτυχώς.</div>
				<?php elseif ($userMsg === 'deleted'): ?>
				<div class="alert-success">Ο χρήστης διαγράφηκε.</div>
				<?php elseif ($userMsg === 'create_error'): ?>
				<div class="alert-error">Συμπληρώστε όλα τα πεδία.</div>
				<?php endif; ?>
				<div class="section-card">
					<h3>Δημιουργία Νέου Χρήστη</h3>
					<form class="create-user-form" method="POST" action="?section=manage-users">
						<input type="text" name="new_username" placeholder="Όνομα χρήστη" required>
						<input type="email" name="new_email" placeholder="Email" required>
						<input type="password" name="new_password" placeholder="Κωδικός" required>
						<select name="new_role">
							<option value="citizen">Πολίτης</option>
							<option value="politician">Πολιτικός</option>
						</select>
						<button type="submit" name="create_user">Δημιουργία</button>
					</form>
				</div>
				<div class="section-card">
					<h3>Όλοι οι Εγγεγραμμένοι Χρήστες</h3>
					<p>Προβολή, προσθήκη, αφαίρεση και ενημέρωση εγγεγραμμένων χρηστών.</p>
					<table>
						<thead>
							<tr><th>Χρήστης</th><th>Email</th><th>Ρόλος</th><th>Εγγράφηκε</th><th></th></tr>
						</thead>
						<tbody>
							<?php foreach ($users_list as $u): ?>
							<tr>

								<td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
								<td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
								<td><span class="badge badge-<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
								<td><?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
								<td>
								<form method="POST" action="?section=manage-users" class="delete-form">
									<input type="hidden" name="delete_user_id" value="<?= (int) $u['id'] ?>">
									<button type="button" class="btn-delete" onclick="confirmDelete(this)">Διαγραφή</button>
									</form>
								</td>
							</tr>
							<?php endforeach; ?>
							<?php if (empty($users_list)): ?>
					<tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:1.5rem;">Δεν βρέθηκαν χρήστες.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<?php elseif ($section === 'manage-submissions'): ?>
				<?php if ($submissionMsg === 'deleted'): ?>
				<div class="alert-success">Η δήλωση διαγράφηκε.</div>
				<?php endif; ?>
				<div class="section-card">
					<h3>Όλες οι Υποβληθείσες Δηλώσεις</h3>
					<p>Εποπτεία δηλώσεων και παρακολούθηση υποβολών στο σύστημα.</p>
					<div class="table-wrap">
					<table class="declarations-table">
						<thead>
							<tr><th>User</th><th>Year</th><th>Party</th><th>Position</th><th>Province</th><th>Properties</th><th>Vehicles</th><th>Shares</th><th>Debts</th><th>Income</th><th>Created At</th><th></th></tr>
						</thead>
						<tbody>
							<?php foreach ($declarations_list as $d): ?>
							<tr>
								<td><?= htmlspecialchars($d['username'], ENT_QUOTES, 'UTF-8') ?></td></td>
								<td><?= htmlspecialchars($d['declaration_year'], ENT_QUOTES, 'UTF-8') ?></td>
								<td><?= htmlspecialchars($d['party'], ENT_QUOTES, 'UTF-8') ?></td>
								<td><?= htmlspecialchars($d['position'], ENT_QUOTES, 'UTF-8') ?></td>
								<td><?= htmlspecialchars($d['province'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="text-cell"><?= htmlspecialchars((string) ($d['properties'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
								<td class="text-cell"><?= htmlspecialchars((string) ($d['vehicles'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
								<td class="text-cell"><?= htmlspecialchars((string) ($d['shares'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
								<td class="text-cell"><?= htmlspecialchars((string) ($d['debts'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
								<td><?= number_format((float) ($d['income'] ?? 0), 2) ?></td>
								<td><?= htmlspecialchars((string) ($d['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
								<td>
									<form method="POST" action="?section=manage-submissions" class="delete-form">
										<input type="hidden" name="delete_submission_id" value="<?= (int) $d['id'] ?>">
										<button type="button" class="btn-delete" onclick="confirmDeleteSubmission(this)">Διαγραφή</button>
									</form>
								</td>
							</tr>
							<?php endforeach; ?>
							<?php if (empty($declarations_list)): ?>
							<tr><td colspan="14" style="text-align:center;color:#94a3b8;padding:1.5rem;">Δεν βρέθηκαν υποβολές.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
					</div>
				</div>

				<?php elseif ($section === 'reports'): ?>
				<div class="stats-row">
					<div class="stat-card">
						<span class="stat-label">Σύνολο Χρηστών</span>
						<span class="stat-value"><?= $stats['users'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Σύνολο Υποβολών</span>
						<span class="stat-value"><?= $stats['submissions'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Πολίτες</span>
						<span class="stat-value"><?= $stats['citizens'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Πολιτικοί</span>
						<span class="stat-value"><?= $stats['politicians'] ?></span>
					</div>
				</div>
				<div class="section-card">
					<h3>Συνοπτική Αναφορά</h3>
					<p>Στατιστική επισκόπηση της πλατφόρμας ΠΟΘΕΝ ΕΣΧΕΣ.</p>
					<ul>
						<li>Σύνολο εγγεγραμμένων χρηστών: <strong><?= $stats['users'] ?></strong></li>
						<li>Πολίτες: <strong><?= $stats['citizens'] ?></strong></li>
						<li>Πολιτικοί: <strong><?= $stats['politicians'] ?></strong></li>
						<li>Σύνολο υποβληθεισών δηλώσεων: <strong><?= $stats['submissions'] ?></strong></li>
					</ul>
				</div>

				<?php elseif ($section === 'profile'): ?>
				<?php if ($profileMsg === 'success'): ?>
				<div class="alert alert-success">Το προφίλ ενημερώθηκε επιτυχώς.</div>
				<?php elseif ($profileMsg === 'error'): ?>
				<div class="alert alert-error">Συμπληρώστε όλα τα πεδία και βεβαιωθείτε ότι οι κωδικοί ταιριάζουν.</div>
				<?php endif; ?>
				<div class="section-card">
					<h3>Ενημέρωση Προφίλ</h3>
					<p>Αλλάξτε το όνομα χρήστη και τον κωδικό σας.</p>
					<form class="profile-form" method="POST" action="?section=profile">
						<div class="form-group">
						<label for="new_username">Νέο Όνομα Χρήστη</label>
						<input type="text" id="new_username" name="new_username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
					</div>
					<div class="form-group">
						<label for="new_password">Νέος Κωδικός</label>
						<input type="password" id="new_password" name="new_password">
					</div>
					<div class="form-group">
						<label for="confirm_password">Επιβεβαίωση Κωδικού</label>
						<input type="password" id="confirm_password" name="confirm_password">
					</div>
					<button class="btn-save" type="submit" name="update_profile">Αποθήκευση</button>
					</form>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
<script>
function confirmDelete(btn) {
    Swal.fire({
        title: 'Διαγραφή χρήστη;',
        text: 'Αυτή η ενέργεια δεν μπορεί να αναιρεθεί.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7f1d1d',
        cancelButtonColor: '#334155',
        confirmButtonText: 'Διαγραφή',
        cancelButtonText: 'Άκυρο'
    }).then(result => {
        if (result.isConfirmed) {
            btn.closest('form').submit();
        }
    });
}

function confirmDeleteSubmission(btn) {
    Swal.fire({
        title: 'Διαγραφή δήλωσης;',
        text: 'Αυτή η ενέργεια δεν μπορεί να αναιρεθεί.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7f1d1d',
        cancelButtonColor: '#334155',
        confirmButtonText: 'Διαγραφή',
        cancelButtonText: 'Άκυρο'
    }).then(result => {
        if (result.isConfirmed) {
            btn.closest('form').submit();
        }
    });
}
</script>
</body>
</html>