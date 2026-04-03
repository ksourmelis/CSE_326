<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'politician') {
    header('Location: ../dashboard.php');
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

$userId = (int) $_SESSION['user_id'];
$username = trim((string) ($_SESSION['username'] ?? 'Unknown User'));

$cssHref = 'style.css';
if (basename((string) ($_SERVER['PHP_SELF'] ?? '')) === 'dashboard.php') {
    $cssHref = 'submit/style.css';
}

$section = 'dashboard';
if (isset($_GET['section'])) {
    $section = $_GET['section'];
    if (!in_array($section, ['dashboard', 'my-profile', 'my-submissions'])) {
        $section = 'dashboard';
    }
}

$profileMsg = '';
$submissionMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newUsername = trim($_POST['username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newUsername === '') {
        $profileMsg = 'error';
    } else {
        if ($newPassword !== '') {
            if ($newPassword !== $confirmPassword) {
                $profileMsg = 'error';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET username = :u, password_hash = :p WHERE id = :id');
                $stmt->execute([':u' => $newUsername, ':p' => $hash, ':id' => $userId]);
                $_SESSION['username'] = $newUsername;
                $username = $newUsername;
                $profileMsg = 'success';
            }
        } else {
            $stmt = $pdo->prepare('UPDATE users SET username = :u WHERE id = :id');
            $stmt->execute([':u' => $newUsername, ':id' => $userId]);
            $_SESSION['username'] = $newUsername;
            $username = $newUsername;
            $profileMsg = 'success';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_declaration'])) {
    $year = (int) ($_POST['declaration_year'] ?? 0);
    $party = trim($_POST['party'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $properties = trim($_POST['properties'] ?? '');
    $vehicles = trim($_POST['vehicles'] ?? '');
    $shares = trim($_POST['shares'] ?? '');
    $debts = trim($_POST['debts'] ?? '');
    $income = trim($_POST['income'] ?? '');

    $isValid = $year > 0 && $party !== '' && $position !== '' && $province !== '' && $income !== '';

    if ($isValid) {
        $stmt = $pdo->prepare(
            'INSERT INTO declarations
            (user_id, declaration_year, party, position, province, properties, vehicles, shares, debts, income)
            VALUES
            (:uid, :yr, :party, :pos, :prov, :prop, :veh, :shares, :debts, :income)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':yr' => $year,
            ':party' => $party,
            ':pos' => $position,
            ':prov' => $province,
            ':prop' => $properties,
            ':veh' => $vehicles,
            ':shares' => $shares,
            ':debts' => $debts,
            ':income' => (float) $income,
        ]);
        $submissionMsg = 'success';
    } else {
        $submissionMsg = 'error';
    }
    $section = 'my-submissions';
}

$userInfoStmt = $pdo->prepare('SELECT username, email, role, created_at FROM users WHERE id = :id');
$userInfoStmt->execute([':id' => $userId]);
$userInfo = $userInfoStmt->fetch();

$mySubmissionsStmt = $pdo->prepare(
    'SELECT id, user_id, declaration_year, party, position, province, properties, vehicles, shares, debts, income, created_at
     FROM declarations
     WHERE user_id = :id
     ORDER BY created_at DESC, id DESC'
);
$mySubmissionsStmt->execute([':id' => $userId]);
$mySubmissions = $mySubmissionsStmt->fetchAll();

$myCount = count($mySubmissions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Module</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssHref, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="side-header">
            <h2>Submit Module</h2>
            <p><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <nav class="side-nav">
            <a href="?section=dashboard" class="side-link <?= $section === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="?section=my-profile" class="side-link <?= $section === 'my-profile' ? 'active' : '' ?>">My Profile</a>
            <a href="?section=my-submissions" class="side-link <?= $section === 'my-submissions' ? 'active' : '' ?>">My Submissions</a>
        </nav>

        <a class="logout-link" href="../../auth/logout.php">Logout</a>
    </aside>

    <main class="content">
        <?php if ($section === 'dashboard'): ?>
            <section class="panel">
                <h1>Politician Dashboard</h1>
                <p>You have access to two main actions in this module.</p>

                <div class="cards">
                    <a class="card-link" href="?section=my-profile">
                        <h3>My Profile</h3>
                        <p>View your account details and update your username or password.</p>
                    </a>

                    <a class="card-link" href="?section=my-submissions">
                        <h3>My Submissions</h3>
                        <p>Submit your Pothen Esches declaration and track your submission status.</p>
                    </a>
                </div>
            </section>
        <?php elseif ($section === 'my-profile'): ?>
            <section class="panel">
                <h1>My Profile</h1>
                <p>View your basic information. Email is read-only.</p>

                <?php if ($profileMsg === 'success'): ?>
                    <div class="alert success">Profile updated successfully.</div>
                <?php elseif ($profileMsg === 'error'): ?>
                    <div class="alert error">Please fill username and make sure passwords match.</div>
                <?php endif; ?>

                <form class="form-grid" method="POST" action="?section=my-profile">
                    <div class="field">
                        <label for="username">Username</label>
                        <input id="username" type="text" name="username" value="<?= htmlspecialchars((string) ($userInfo['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="field">
                        <label for="email">Email (cannot be changed)</label>
                        <input id="email" type="email" value="<?= htmlspecialchars((string) ($userInfo['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" readonly>
                    </div>

                    <div class="field">
                        <label for="role">Level</label>
                        <input id="role" type="text" value="Politician" readonly>
                    </div>

                    <div class="field">
                        <label for="new_password">New Password</label>
                        <input id="new_password" type="password" name="new_password" placeholder="Leave empty if unchanged">
                    </div>

                    <div class="field">
                        <label for="confirm_password">Confirm Password</label>
                        <input id="confirm_password" type="password" name="confirm_password" placeholder="Repeat new password">
                    </div>

                    <button class="btn" type="submit" name="update_profile">Save Profile</button>
                </form>
            </section>
        <?php elseif ($section === 'my-submissions'): ?>
            <section class="panel">
                <h1>My Submissions</h1>
                <p>Fill and submit your declaration. You can also review past submissions.</p>

                <?php if ($submissionMsg === 'success'): ?>
                    <div class="alert success">Declaration submitted successfully.</div>
                <?php elseif ($submissionMsg === 'error'): ?>
                    <div class="alert error">Please complete all required fields.</div>
                <?php endif; ?>

                <form class="form-grid" method="POST" action="?section=my-submissions">
                    <div class="field">
                        <label for="declaration_year">Declaration Year</label>
                        <input id="declaration_year" type="number" name="declaration_year" min="2000" max="2100" required>
                    </div>

                    <div class="field">
                        <label for="party">Party</label>
                        <input id="party" type="text" name="party" required>
                    </div>

                    <div class="field">
                        <label for="position">Position</label>
                        <input id="position" type="text" name="position" required>
                    </div>

                    <div class="field">
                        <label for="province">Province</label>
                        <input id="province" type="text" name="province" required>
                    </div>

                    <div class="field full">
                        <label for="properties">Properties</label>
                        <textarea id="properties" name="properties" rows="2"></textarea>
                    </div>

                    <div class="field full">
                        <label for="vehicles">Vehicles</label>
                        <textarea id="vehicles" name="vehicles" rows="2"></textarea>
                    </div>

                    <div class="field full">
                        <label for="shares">Shares</label>
                        <textarea id="shares" name="shares" rows="2"></textarea>
                    </div>

                    <div class="field full">
                        <label for="debts">Debts</label>
                        <textarea id="debts" name="debts" rows="2"></textarea>
                    </div>

                    <div class="field">
                        <label for="income">Income (€)</label>
                        <input id="income" type="number" step="0.01" min="0" name="income" required>
                    </div>

                    <button class="btn" type="submit" name="submit_declaration">Submit Declaration</button>
                </form>
            </section>

            <section class="panel">
                <h2>Submission Status (<?= $myCount ?>)</h2>
                <div class="table-wrap">
                    <table class="submission-status-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Year</th>
                            <th>Party</th>
                            <th>Position</th>
                            <th>Province</th>
                            <th>Properties</th>
                            <th>Vehicles</th>
                            <th>Shares</th>
                            <th>Debts</th>
                            <th>Income</th>
                            <th>Created At</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($mySubmissions as $row): ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>
                                <td><?= (int) $row['user_id'] ?></td>
                                <td><?= htmlspecialchars((string) $row['declaration_year'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['party'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['position'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['province'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-cell"><?= htmlspecialchars((string) ($row['properties'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-cell"><?= htmlspecialchars((string) ($row['vehicles'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-cell"><?= htmlspecialchars((string) ($row['shares'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-cell"><?= htmlspecialchars((string) ($row['debts'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format((float) $row['income'], 2) ?></td>
                                <td><?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="status ok">Submitted</span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mySubmissions)): ?>
                            <tr>
                                <td colspan="13" class="empty">No submissions yet.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>