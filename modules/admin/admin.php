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
	if (!in_array($section, ['dashboard', 'manage-users', 'manage-submissions', 'configure-system', 'reports', 'profile'])) {
		$section = 'dashboard';
	}
}

$sectionTitles = [
	'dashboard'           => 'Dashboard',
	'manage-users'        => 'Manage Users',
	'manage-submissions'  => 'Manage Submissions',
	'configure-system'    => 'Configure System',
	'reports'             => 'Reports',
	'profile'             => 'Profile',
];

$profileMsg = '';
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
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin Dashboard</title>
	<link rel="stylesheet" href="<?= htmlspecialchars($cssHref, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
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
				<a href="?section=configure-system" class="nav-item <?= $section === 'configure-system' ? 'active' : '' ?>">
					<span class="nav-icon">C</span> Configure System
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
			<div class="content-header">
				<h2><?= htmlspecialchars($sectionTitles[$section], ENT_QUOTES, 'UTF-8') ?></h2>
			</div>

			<div class="content-body">
				<?php if ($section === 'dashboard'): ?>
				<div class="stats-row">
					<div class="stat-card">
						<span class="stat-label">Registered Users</span>
						<span class="stat-value"><?= $stats['users'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Submissions</span>
						<span class="stat-value"><?= $stats['submissions'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Citizens</span>
						<span class="stat-value"><?= $stats['citizens'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Politicians</span>
						<span class="stat-value"><?= $stats['politicians'] ?></span>
					</div>
				</div>

				<?php elseif ($section === 'manage-users'): ?>
				<div class="section-card">
					<h3>All Registered Users</h3>
					<p>View, add, remove and update the registered users in the application.</p>
					<table>
						<thead>
							<tr><th>#</th><th>Username</th><th>Email</th><th>Role</th><th>Registered</th></tr>
						</thead>
						<tbody>
							<?php foreach ($users_list as $u): ?>
							<tr>
								<td><?= (int) $u['id'] ?></td>
								<td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
								<td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
								<td><span class="badge badge-<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
								<td><?= htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
							</tr>
							<?php endforeach; ?>
							<?php if (empty($users_list)): ?>
							<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:1.5rem;">No users found.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<?php elseif ($section === 'manage-submissions'): ?>
				<div class="section-card">
					<h3>All Submitted Declarations</h3>
					<p>Supervise declaration activity and monitor submission flow across the system.</p>
					<div class="table-wrap">
					<table class="declarations-table">
						<thead>
							<tr><th>#</th><th>User ID</th><th>User</th><th>Year</th><th>Party</th><th>Position</th><th>Province</th><th>Properties</th><th>Vehicles</th><th>Shares</th><th>Debts</th><th>Income</th><th>Created At</th></tr>
						</thead>
						<tbody>
							<?php foreach ($declarations_list as $d): ?>
							<tr>
								<td><?= (int) $d['id'] ?></td>
								<td><?= (int) $d['user_id'] ?></td>
								<td><?= htmlspecialchars($d['username'], ENT_QUOTES, 'UTF-8') ?></td>
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
							</tr>
							<?php endforeach; ?>
							<?php if (empty($declarations_list)): ?>
							<tr><td colspan="13" style="text-align:center;color:#94a3b8;padding:1.5rem;">No submissions found.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
					</div>
				</div>

				<?php elseif ($section === 'configure-system'): ?>
				<div class="section-card">
					<h3>Configure System</h3>
					<p>The admin sets the parties, people, positions and provinces used across the platform.</p>
				</div>

				<?php elseif ($section === 'reports'): ?>
				<div class="stats-row">
					<div class="stat-card">
						<span class="stat-label">Total Users</span>
						<span class="stat-value"><?= $stats['users'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Total Submissions</span>
						<span class="stat-value"><?= $stats['submissions'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Citizens</span>
						<span class="stat-value"><?= $stats['citizens'] ?></span>
					</div>
					<div class="stat-card">
						<span class="stat-label">Politicians</span>
						<span class="stat-value"><?= $stats['politicians'] ?></span>
					</div>
				</div>
				<div class="section-card">
					<h3>Summary Report</h3>
					<p>Statistical overview of the Pothen Esches platform.</p>
					<ul>
						<li>Total registered users: <strong><?= $stats['users'] ?></strong></li>						<li>Citizens: <strong><?= $stats['citizens'] ?></strong></li>
						<li>Politicians: <strong><?= $stats['politicians'] ?></strong></li>
						<li>Total submitted declarations: <strong><?= $stats['submissions'] ?></strong></li>
					</ul>
				</div>

				<?php elseif ($section === 'profile'): ?>
				<?php if ($profileMsg === 'success'): ?>
				<div class="alert alert-success">Profile updated successfully.</div>
				<?php elseif ($profileMsg === 'error'): ?>
				<div class="alert alert-error">Fill in all fields and make sure the passwords match.</div>
				<?php endif; ?>
				<div class="section-card">
					<h3>Update Profile</h3>
					<p>Change your username and password.</p>
					<form class="profile-form" method="POST" action="?section=profile">
						<div class="form-group">
							<label for="new_username">New Username</label>
							<input type="text" id="new_username" name="new_username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
						</div>
						<div class="form-group">
							<label for="new_password">New Password</label>
							<input type="password" id="new_password" name="new_password">
						</div>
						<div class="form-group">
							<label for="confirm_password">Confirm Password</label>
							<input type="password" id="confirm_password" name="confirm_password">
						</div>
						<button class="btn-save" type="submit" name="update_profile">Save Changes</button>
					</form>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</body>
</html>