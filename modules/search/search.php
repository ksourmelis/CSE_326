<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') !== 'citizen')) {
    header('Location: ../dashboard.php');
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

$isLoggedInCitizen = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'citizen');
$username = trim((string) ($_SESSION['username'] ?? 'Guest'));

$cssHref = 'style.css';
$loginPath = '../../auth/login.php';
$listPath = '../list.php';
if (basename((string) ($_SERVER['PHP_SELF'] ?? '')) === 'dashboard.php') {
    $cssHref = 'search/style.css';
    $loginPath = '../auth/login.php';
    $listPath = 'list.php';
}

$section = 'search';
if (isset($_GET['section'])) {
    $section = (string) $_GET['section'];
    if (!in_array($section, ['dashboard', 'search', 'login', 'statistics', 'change-password'])) {
        $section = 'search';
    }
}

if (!$isLoggedInCitizen && $section === 'dashboard') {
    $section = 'search';
}

if (!$isLoggedInCitizen && $section === 'change-password') {
    $section = 'search';
}

if ($section === 'login') {
    header('Location: ' . $loginPath);
    exit;
}

$passwordErrors = [];
$passwordSuccess = '';

if ($isLoggedInCitizen && $section === 'change-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $passwordErrors[] = 'All password fields are required.';
    }

    if (strlen($newPassword) < 6) {
        $passwordErrors[] = 'New password must be at least 6 characters.';
    }

    if ($newPassword !== $confirmPassword) {
        $passwordErrors[] = 'New password and confirmation do not match.';
    }

    $userStmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => (int) $_SESSION['user_id']]);
    $storedHash = (string) $userStmt->fetchColumn();

    if ($storedHash === '' || !password_verify($currentPassword, $storedHash)) {
        $passwordErrors[] = 'Current password is incorrect.';
    }

    if (empty($passwordErrors)) {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $updateStmt->execute([
            ':hash' => $newHash,
            ':id' => (int) $_SESSION['user_id'],
        ]);
        $passwordSuccess = 'Password updated successfully.';
    }
}

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$searchResults = [];

if ($section === 'search') {
    if ($keyword !== '') {
        $kw = '%' . $keyword . '%';
        $stmt = $pdo->prepare(
              "SELECT d.id, u.username, d.declaration_year, d.party, d.position, d.province, d.debts, d.income
             FROM declarations d
             JOIN users u ON u.id = d.user_id
               WHERE u.role = 'politician'
               AND (u.username LIKE :kw
                OR d.party LIKE :kw
                OR d.debts LIKE :kw)
               ORDER BY d.declaration_year DESC, d.id DESC"
        );
        $stmt->execute([':kw' => $kw]);
        $searchResults = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare(
            "SELECT d.id, u.username, d.declaration_year, d.party, d.position, d.province, d.debts, d.income
             FROM declarations d
             JOIN users u ON u.id = d.user_id
               WHERE u.role = 'politician'
             ORDER BY d.declaration_year DESC, d.id DESC
             LIMIT 25"
        );
        $stmt->execute();
        $searchResults = $stmt->fetchAll();
    }
}

$stats = [
    'declarations_total' => 0,
    'politicians_total' => 0,
    'parties_total' => 0,
    'income_sum' => 0.0,
    'entries_with_debts' => 0,
];
$partyStats = [];
$politicianStats = [];

if ($section === 'statistics' || $section === 'dashboard') {
    $declCountStmt = $pdo->prepare('SELECT COUNT(*) FROM declarations');
    $declCountStmt->execute();
    $stats['declarations_total'] = (int) $declCountStmt->fetchColumn();

    $polCountStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'politician'");
    $polCountStmt->execute();
    $stats['politicians_total'] = (int) $polCountStmt->fetchColumn();

    $partyCountStmt = $pdo->prepare('SELECT COUNT(DISTINCT party) FROM declarations');
    $partyCountStmt->execute();
    $stats['parties_total'] = (int) $partyCountStmt->fetchColumn();

    $incomeSumStmt = $pdo->prepare('SELECT COALESCE(SUM(income), 0) FROM declarations');
    $incomeSumStmt->execute();
    $stats['income_sum'] = (float) $incomeSumStmt->fetchColumn();

    $debtsCountStmt = $pdo->prepare("SELECT COUNT(*) FROM declarations WHERE debts IS NOT NULL AND TRIM(debts) <> ''");
    $debtsCountStmt->execute();
    $stats['entries_with_debts'] = (int) $debtsCountStmt->fetchColumn();

    $partyStatsStmt = $pdo->prepare(
        'SELECT party, COUNT(*) AS declarations_count, COALESCE(SUM(income), 0) AS total_income
         FROM declarations
         GROUP BY party
         ORDER BY declarations_count DESC, party ASC'
    );
    $partyStatsStmt->execute();
    $partyStats = $partyStatsStmt->fetchAll();

    $politicianStatsStmt = $pdo->prepare(
        'SELECT u.username, COUNT(*) AS declarations_count, COALESCE(SUM(d.income), 0) AS total_income
         FROM declarations d
         JOIN users u ON u.id = d.user_id
         GROUP BY u.id, u.username
         ORDER BY declarations_count DESC, u.username ASC'
    );
    $politicianStatsStmt->execute();
    $politicianStats = $politicianStatsStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Module</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssHref, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="side-header">
            <h2>Search Module</h2>
            <p><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <nav class="side-nav">
            <?php if ($isLoggedInCitizen): ?>
                <a href="?section=dashboard" class="side-link <?= $section === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($listPath, ENT_QUOTES, 'UTF-8') ?>" class="side-link">Search</a>
            <a href="?section=statistics" class="side-link <?= $section === 'statistics' ? 'active' : '' ?>">Statistics</a>
            <?php if ($isLoggedInCitizen): ?>
                <a href="?section=change-password" class="side-link <?= $section === 'change-password' ? 'active' : '' ?>">Change Password</a>
            <?php endif; ?>
        </nav>

        <?php if ($isLoggedInCitizen): ?>
            <a class="logout-link" href="<?= htmlspecialchars($loginPath === '../auth/login.php' ? '../auth/logout.php' : '../../auth/logout.php', ENT_QUOTES, 'UTF-8') ?>">Logout</a>
        <?php else: ?>
            <a class="logout-link" href="<?= htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8') ?>">Login</a>
        <?php endif; ?>
    </aside>

    <main class="content">
        <?php if ($section === 'dashboard'): ?>
            <section class="panel">
                <h1>Citizen Dashboard</h1>
                <p>This module gives public users access to search, registration and statistics.</p>
            </section>

            <section class="panel stats-mini">
                <div class="mini-card"><span><?= $stats['declarations_total'] ?></span><small>Total declarations</small></div>
                <div class="mini-card"><span><?= $stats['politicians_total'] ?></span><small>Total politicians</small></div>
                <div class="mini-card"><span><?= $stats['parties_total'] ?></span><small>Total parties</small></div>
                <div class="mini-card"><span><?= number_format($stats['income_sum'], 2) ?></span><small>Total declared income</small></div>
            </section>

        <?php elseif ($section === 'search'): ?>
            <section class="panel">
                <h1>Seaaaaaaarch</h1>
                <p>Search by politician name, party or debts text.</p>

                <form class="search-form" method="GET" action="">
                    <input type="hidden" name="section" value="search">
                    <input
                        type="text"
                        name="keyword"
                        placeholder="e.g. nikos, DISY, debt"
                        value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <button type="submit">Search</button>
                </form>

                <p class="result-count">
                    <?= count($searchResults) ?> result<?= count($searchResults) !== 1 ? 's' : '' ?>
                    <?php if ($keyword !== ''): ?>for "<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                </p>

                <div class="table-wrap">
                    <table class="search-results-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Politician</th>
                                <th>Year</th>
                                <th>Party</th>
                                <th>Position</th>
                                <th>Province</th>
                                <th>Income</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($searchResults as $row): ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>
                                <td><?= htmlspecialchars((string) $row['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['declaration_year'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['party'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['position'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['province'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format((float) $row['income'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($searchResults)): ?>
                            <tr><td colspan="7" class="empty">No matching records.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        <?php elseif ($section === 'statistics'): ?>
            <section class="panel stats-mini">
                <div class="mini-card"><span><?= $stats['declarations_total'] ?></span><small>Total declarations</small></div>
                <div class="mini-card"><span><?= $stats['politicians_total'] ?></span><small>Total politicians</small></div>
                <div class="mini-card"><span><?= $stats['entries_with_debts'] ?></span><small>Entries with debts info</small></div>
                <div class="mini-card"><span><?= number_format($stats['income_sum'], 2) ?></span><small>Total declared income</small></div>
            </section>

            <section class="panel">
                <h2>By Party</h2>
                <?php $maxParty = 1; foreach ($partyStats as $p) { if ((int) $p['declarations_count'] > $maxParty) { $maxParty = (int) $p['declarations_count']; } } ?>
                <?php foreach ($partyStats as $p): ?>
                    <?php $w = ((int) $p['declarations_count'] / $maxParty) * 100; ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <span><?= htmlspecialchars((string) $p['party'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= (int) $p['declarations_count'] ?> declarations</span>
                        </div>
                        <div class="bar-track"><div class="bar-fill" style="width: <?= number_format($w, 2) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($partyStats)): ?>
                    <p class="empty-text">No statistics available yet.</p>
                <?php endif; ?>
            </section>

            <section class="panel">
                <h2>By Politician</h2>
                <?php $maxPol = 1; foreach ($politicianStats as $p) { if ((int) $p['declarations_count'] > $maxPol) { $maxPol = (int) $p['declarations_count']; } } ?>
                <?php foreach ($politicianStats as $p): ?>
                    <?php $w = ((int) $p['declarations_count'] / $maxPol) * 100; ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <span><?= htmlspecialchars((string) $p['username'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= (int) $p['declarations_count'] ?> declarations</span>
                        </div>
                        <div class="bar-track"><div class="bar-fill" style="width: <?= number_format($w, 2) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($politicianStats)): ?>
                    <p class="empty-text">No statistics available yet.</p>
                <?php endif; ?>
            </section>

        <?php elseif ($section === 'change-password'): ?>
            <section class="panel">
                <h1>Change Password</h1>
                <p>Update your account password.</p>

                <?php if (!empty($passwordErrors)): ?>
                    <div class="empty-text" style="margin-top: 0.8rem; color: #b91c1c;">
                        <?= htmlspecialchars(implode(' ', $passwordErrors), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if ($passwordSuccess !== ''): ?>
                    <div class="empty-text" style="margin-top: 0.8rem; color: #166534;">
                        <?= htmlspecialchars($passwordSuccess, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form class="search-form" method="POST" action="?section=change-password" style="margin-top: 1rem;">
                    <input type="password" name="current_password" placeholder="Current password" required>
                    <input type="password" name="new_password" placeholder="New password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                    <button type="submit">Update Password</button>
                </form>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>