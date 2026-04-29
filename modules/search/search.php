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
              "SELECT d.id, u.username, d.declaration_year, d.party, d.position, d.province, d.properties, d.vehicles, d.shares, d.debts, d.income, d.created_at
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
            "SELECT d.id, u.username, d.declaration_year, d.party, d.position, d.province, d.properties, d.vehicles, d.shares, d.debts, d.income, d.created_at
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
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ΠΟΘΕΝ ΕΣΧΕΣ — <?php
        $titles = ['dashboard' => 'Ταμπλό', 'search' => 'Αναζήτηση', 'statistics' => 'Στατιστικά', 'change-password' => 'Αλλαγή Κωδικού'];
        echo htmlspecialchars($titles[$section] ?? 'Αναζήτηση', ENT_QUOTES, 'UTF-8');
    ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>


<header class="site-header">
    <div>
        <div class="logo-title">ΠΟΘΕΝ ΕΣΧΕΣ</div>
        <div class="logo-sub">Δηλώσεις Περιουσιακής &amp; Επαγγελματικής Κατάστασης</div>
    </div>
</header>

<nav class="main-nav">
    <div class="nav-inner">
        <a href="<?= htmlspecialchars($listPath, ENT_QUOTES, 'UTF-8') ?>">Αρχική</a>
        <a href="?section=search" class="<?= $section === 'search' ? 'active' : '' ?>">Αναζήτηση</a>
        <a href="?section=statistics" class="<?= $section === 'statistics' ? 'active' : '' ?>">Στατιστικά</a>
        <?php if ($isLoggedInCitizen): ?>
            <a href="?section=dashboard" class="<?= $section === 'dashboard' ? 'active' : '' ?>">Ταμπλό</a>
            <a href="?section=change-password" class="<?= $section === 'change-password' ? 'active' : '' ?>">Αλλαγή Κωδικού</a>
        <?php endif; ?>
        <div class="nav-right">
            <?php if ($isLoggedInCitizen): ?>
                <a href="<?= htmlspecialchars($loginPath === '../auth/login.php' ? '../auth/logout.php' : '../../auth/logout.php', ENT_QUOTES, 'UTF-8') ?>" class="btn-logout">Αποσύνδεση (<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>)</a>
            <?php else: ?>
                <a href="<?= htmlspecialchars(str_replace('login.php', 'register.php', $loginPath), ENT_QUOTES, 'UTF-8') ?>" class="btn-register">Εγγραφή</a>
                <a href="<?= htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8') ?>" class="btn-login">Σύνδεση</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="page-title-bar">
    <div class="ptb-inner"><?php
        $ptbTitles = ['dashboard' => 'Ταμπλό Πολίτη', 'search' => 'Αναζήτηση Δηλώσεων', 'statistics' => 'Στατιστικά', 'change-password' => 'Αλλαγή Κωδικού'];
        echo htmlspecialchars($ptbTitles[$section] ?? 'Αναζήτηση', ENT_QUOTES, 'UTF-8');
    ?></div>
</div>

<div class="page-wrap">

        <?php if ($section === 'dashboard'): ?>
            <section class="panel">
                <h1>Ταμπλό Πολίτη</h1>
                <p>Αυτή η ενότητα παρέχει στους χρήστες πρόσβαση σε αναζήτηση και στατιστικά.</p>
            </section>

            <section class="stats-mini">
                <div class="mini-card"><span><?= $stats['declarations_total'] ?></span><small>Συνολικές δηλώσεις</small></div>
                <div class="mini-card"><span><?= $stats['politicians_total'] ?></span><small>Πολιτικοί</small></div>
                <div class="mini-card"><span><?= $stats['parties_total'] ?></span><small>Κόμματα</small></div>
                <div class="mini-card"><span><?= number_format($stats['income_sum'], 2) ?></span><small>Συνολικό δηλωθέν εισόδημα</small></div>
            </section>

        <?php elseif ($section === 'search'): ?>
            <section class="panel">
                <h1>Αναζήτηση</h1>
                <p>Αναζητήστε βάσει ονόματος πολιτικού, κόμματος ή χρεών.</p>

                <form class="search-form" method="GET" action="">
                    <input type="hidden" name="section" value="search">
                    <input
                        type="text"
                        name="keyword"
                        placeholder="π.χ. Νίκος, ΔΗΣΥ, χρέη..."
                        value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <button type="submit">Αναζήτηση</button>
                </form>

                <p class="result-count">
                    <?= count($searchResults) ?> αποτέλεσμα<?= count($searchResults) !== 1 ? 'τα' : '' ?>
                    <?php if ($keyword !== ''): ?>για "<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                </p>

                <div class="table-wrap">
                    <table class="search-results-table">
                        <thead>
                            <tr>
                                <th>Πολιτικός</th>
                                <th>Έτος</th>
                                <th>Κόμμα</th>
                                <th>Θέση</th>
                                <th>Επαρχία</th>
                                <th>Ακίνητα</th>
                                <th>Οχήματα</th>
                                <th>Μετοχές</th>
                                <th>Χρέη</th>
                                <th>Εισόδημα</th>
                                <th>Ημ/νία Υποβολής</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($searchResults as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $row['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['declaration_year'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['party'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['position'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['province'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['properties'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['vehicles'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['shares'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['debts'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format((float) $row['income'], 2) ?></td>
                                <td><?= htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($searchResults)): ?>
                            <tr><td colspan="11" class="empty">Δεν βρέθηκαν εγγραφές.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        <?php elseif ($section === 'statistics'): ?>
            <section class="stats-mini">
                <div class="mini-card"><span><?= $stats['declarations_total'] ?></span><small>Συνολικές δηλώσεις</small></div>
                <div class="mini-card"><span><?= $stats['politicians_total'] ?></span><small>Πολιτικοί</small></div>
                <div class="mini-card"><span><?= $stats['entries_with_debts'] ?></span><small>Εγγραφές με χρέη</small></div>
                <div class="mini-card"><span><?= number_format($stats['income_sum'], 2) ?></span><small>Συνολικό δηλωθέν εισόδημα</small></div>
            </section>

            <section class="panel">
                <h2>Ανά Κόμμα</h2>
                <?php $maxParty = 1; foreach ($partyStats as $p) { if ((int) $p['declarations_count'] > $maxParty) { $maxParty = (int) $p['declarations_count']; } } ?>
                <?php foreach ($partyStats as $p): ?>
                    <?php $w = ((int) $p['declarations_count'] / $maxParty) * 100; ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <span><?= htmlspecialchars((string) $p['party'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= (int) $p['declarations_count'] ?> δηλώσεις</span>
                        </div>
                        <div class="bar-track"><div class="bar-fill" style="width: <?= number_format($w, 2) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($partyStats)): ?>
                    <p class="empty-text">Δεν υπάρχουν διαθέσιμα στατιστικά.</p>
                <?php endif; ?>
            </section>

            <section class="panel">
                <h2>Ανά Πολιτικό</h2>
                <?php $maxPol = 1; foreach ($politicianStats as $p) { if ((int) $p['declarations_count'] > $maxPol) { $maxPol = (int) $p['declarations_count']; } } ?>
                <?php foreach ($politicianStats as $p): ?>
                    <?php $w = ((int) $p['declarations_count'] / $maxPol) * 100; ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <span><?= htmlspecialchars((string) $p['username'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= (int) $p['declarations_count'] ?> δηλώσεις</span>
                        </div>
                        <div class="bar-track"><div class="bar-fill" style="width: <?= number_format($w, 2) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($politicianStats)): ?>
                    <p class="empty-text">Δεν υπάρχουν διαθέσιμα στατιστικά.</p>
                <?php endif; ?>
            </section>

        <?php elseif ($section === 'change-password'): ?>
            <section class="panel">
                <h1>Αλλαγή Κωδικού</h1>
                <p>Ενημερώστε τον κωδικό του λογαριασμού σας.</p>

                <?php if (!empty($passwordErrors)): ?>
                    <div class="msg-err"><?= htmlspecialchars(implode(' ', $passwordErrors), ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($passwordSuccess !== ''): ?>
                    <div class="msg-ok"><?= htmlspecialchars($passwordSuccess, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form class="pw-form" method="POST" action="?section=change-password">
                    <input type="password" name="current_password" placeholder="Τρέχων κωδικός" required>
                    <input type="password" name="new_password" placeholder="Νέος κωδικός" required>
                    <input type="password" name="confirm_password" placeholder="Επιβεβαίωση νέου κωδικού" required>
                    <button type="submit">Ενημέρωση Κωδικού</button>
                </form>
            </section>
        <?php endif; ?>

</div>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-title">Σύνδεσμοι</div>
        <div class="footer-links">
            <a href="<?= htmlspecialchars($listPath, ENT_QUOTES, 'UTF-8') ?>">Αρχική</a>
            <a href="?section=search">Αναζήτηση</a>
            <a href="?section=statistics">Στατιστικά</a>
            <a href="<?= htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8') ?>">Σύνδεση</a>
        </div>
        <div class="footer-bottom">
            <span>Σύστημα ΠΟΘΕΝ ΕΣΧΕΣ &copy; <?= date('Y') ?></span>
        </div>
    </div>
</footer>

</body>
</html>