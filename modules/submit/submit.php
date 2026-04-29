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
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ΠΟΘΕΝ ΕΣΧΕΣ — <?php
        $sTitles = ['dashboard' => 'Ταμπλό', 'my-profile' => 'Προφίλ', 'my-submissions' => 'Οι Δηλώσεις Μου'];
        echo htmlspecialchars($sTitles[$section] ?? 'Ταμπλό', ENT_QUOTES, 'UTF-8');
    ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssHref, ENT_QUOTES, 'UTF-8') ?>">
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
        <a href="?section=dashboard" class="<?= $section === 'dashboard' ? 'active' : '' ?>">Ταμπλό</a>
        <a href="?section=my-profile" class="<?= $section === 'my-profile' ? 'active' : '' ?>">Προφίλ</a>
        <a href="?section=my-submissions" class="<?= $section === 'my-submissions' ? 'active' : '' ?>">Οι Δηλώσεις Μου</a>
        <div class="nav-right">
            <a href="../../auth/logout.php" class="btn-logout">Αποσύνδεση (<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>)</a>
        </div>
    </div>
</nav>

<div class="page-title-bar">
    <div class="ptb-inner"><?= htmlspecialchars($sTitles[$section] ?? 'Ταμπλό', ENT_QUOTES, 'UTF-8') ?></div>
</div>

<div class="page-wrap">

        <?php if ($section === 'dashboard'): ?>
            <section class="panel">
                <h1>Ταμπλό Πολιτικού</h1>
                <p>Έχετε πρόσβαση σε δύο κύριες ενέργειες σε αυτή την ενότητα.</p>

                <div class="cards">
                    <a class="card-link" href="?section=my-profile">
                        <h3>Προφίλ</h3>
                        <p>Δείτε τα στοιχεία λογαριασμού σας και ενημερώστε το όνομα ή τον κωδικό σας.</p>
                    </a>

                    <a class="card-link" href="?section=my-submissions">
                        <h3>Οι Δηλώσεις Μου</h3>
                        <p>Υποβάλετε τη δήλωσή σας ΠΟΘΕΝ ΕΣΧΕΣ και παρακολουθήστε τις υποβολές σας.</p>
                    </a>
                </div>
            </section>
        <?php elseif ($section === 'my-profile'): ?>
            <section class="panel">
                <h1>Προφίλ</h1>
                <p>Δείτε τα βασικά στοιχεία σας. Το email δεν μπορεί να αλλαχθεί.</p>

                <?php if ($profileMsg === 'success'): ?>
                    <div class="alert success">Το προφίλ ενημερώθηκε επιτυχώς.</div>
                <?php elseif ($profileMsg === 'error'): ?>
                    <div class="alert error">Συμπληρώστε το όνομα χρήστη και βεβαιωθείτε ότι οι κωδικοί ταιριάζουν.</div>
                <?php endif; ?>

                <form class="form-grid" method="POST" action="?section=my-profile">
                    <div class="field">
                        <label for="username">Όνομα χρήστη</label>
                        <input id="username" type="text" name="username" value="<?= htmlspecialchars((string) ($userInfo['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="field">
                        <label for="email">Email (δεν μπορεί να αλλαχθεί)</label>
                        <input id="email" type="email" value="<?= htmlspecialchars((string) ($userInfo['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" readonly>
                    </div>

                    <div class="field">
                        <label for="role">Επίπεδο</label>
                        <input id="role" type="text" value="Πολιτικός" readonly>
                    </div>

                    <div class="field">
                        <label for="new_password">Νέος κωδικός</label>
                        <input id="new_password" type="password" name="new_password" placeholder="Αφήστε κενό αν δεν αλλάζει">
                    </div>

                    <div class="field">
                        <label for="confirm_password">Επιβεβαίωση κωδικού</label>
                        <input id="confirm_password" type="password" name="confirm_password" placeholder="Επαναλάβετε τον νέο κωδικό">
                    </div>

                    <button class="btn" type="submit" name="update_profile">Αποθήκευση</button>
                </form>
            </section>
        <?php elseif ($section === 'my-submissions'): ?>
            <section class="panel">
                <h1>Οι Δηλώσεις Μου</h1>
                <p>Συμπληρώστε και υποβάλετε τη δήλωσή σας. Μπορείτε επίσης να δείτε προηγούμενες υποβολές.</p>

                <?php if ($submissionMsg === 'success'): ?>
                    <div class="alert success">Η δήλωση υποβλήθηκε επιτυχώς.</div>
                <?php elseif ($submissionMsg === 'error'): ?>
                    <div class="alert error">Παρακαλώ συμπληρώστε όλα τα υποχρεωτικά πεδία.</div>
                <?php endif; ?>

                <form class="form-grid" method="POST" action="?section=my-submissions">
                    <div class="field">
                        <label for="declaration_year">Έτος Δήλωσης</label>
                        <input id="declaration_year" type="number" name="declaration_year" min="2000" max="2100" required>
                    </div>

                    <div class="field">
                        <label for="party">Κόμμα</label>
                        <input id="party" type="text" name="party" required>
                    </div>

                    <div class="field">
                        <label for="position">Θέση</label>
                        <input id="position" type="text" name="position" required>
                    </div>

                    <div class="field">
                        <label for="province">Επαρχία</label>
                        <input id="province" type="text" name="province" required>
                    </div>

                    <div class="field full">
                        <label for="properties">Ακίνητα</label>
                        <textarea id="properties" name="properties" rows="2"></textarea>
                    </div>

                    <div class="field full">
                        <label for="vehicles">Οχήματα</label>
                        <textarea id="vehicles" name="vehicles" rows="2"></textarea>
                    </div>

                    <div class="field full">
                        <label for="shares">Μετοχές</label>
                        <textarea id="shares" name="shares" rows="2"></textarea>
                    </div>

                    <div class="field full">
                        <label for="debts">Χρέη</label>
                        <textarea id="debts" name="debts" rows="2"></textarea>
                    </div>

                    <div class="field">
                        <label for="income">Εισόδημα (€)</label>
                        <input id="income" type="number" step="0.01" min="0" name="income" required>
                    </div>

                    <button class="btn" type="submit" name="submit_declaration">Υποβολή Δήλωσης</button>
                </form>
            </section>

            <section class="panel">
                <h2>Κατάσταση Υποβολών (<?= $myCount ?>)</h2>
                <div class="table-wrap">
                    <table class="submission-status-table">
                        <thead>
                        <tr>
                            <th>Έτος</th>
                            <th>Κόμμα</th>
                            <th>Θέση</th>
                            <th>Επαρχία</th>
                            <th>Ακίνητα</th>
                            <th>Οχήματα</th>
                            <th>Μετοχές</th>
                            <th>Χρέη</th>
                            <th>Εισόδημα</th>
                            <th>Δημιουργήθηκε</th>
                            <th>Κατάσταση</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($mySubmissions as $row): ?>
                            <tr>
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
                                <td><span class="status ok">Υποβλήθηκε</span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mySubmissions)): ?>
                            <tr>
                                <td colspan="13" class="empty">Δεν υπάρχουν υποβολές ακόμα.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

</div>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-bottom">
            <span>Σύστημα ΠΟΘΕΝ ΕΣΧΕΣ &copy; <?= date('Y') ?></span>
        </div>
    </div>
</footer>

</body>
</html>