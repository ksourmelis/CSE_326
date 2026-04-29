<?php
session_start();

if (isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') !== 'citizen')) {
    header('Location: ../dashboard.php');
    exit;
}

require_once '../includes/db.php';

$isLoggedInCitizen = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'citizen');
$username = trim((string) ($_SESSION['username'] ?? 'Guest'));

$keyword = trim($_GET['keyword'] ?? '');
$results = [];

if ($keyword !== '') {
    $kw = '%' . $keyword . '%';
    $stmt = $pdo->prepare(
        'SELECT d.*, u.username
         FROM declarations d
         JOIN users u ON d.user_id = u.id
         WHERE u.username LIKE :kw
            OR CAST(d.id AS CHAR) LIKE :kw
            OR CAST(d.user_id AS CHAR) LIKE :kw
            OR CAST(d.declaration_year AS CHAR) LIKE :kw
                OR d.party LIKE :kw
                OR d.position LIKE :kw
                OR d.province LIKE :kw
            OR d.properties LIKE :kw
            OR d.vehicles LIKE :kw
            OR d.shares LIKE :kw
            OR d.debts LIKE :kw
                OR CAST(d.created_at AS CHAR) LIKE :kw
            OR CAST(d.income AS CHAR) LIKE :kw
         ORDER BY d.declaration_year DESC, u.username ASC'
    );
    $stmt->execute([':kw' => $kw]);
    $results = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare(
        'SELECT d.*, u.username
         FROM declarations d
         JOIN users u ON d.user_id = u.id
         ORDER BY d.declaration_year DESC, u.username ASC'
    );
    $stmt->execute();
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ΠΟΘΕΝ ΕΣΧΕΣ — Δηλώσεις Περιουσιακής Κατάστασης</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body { font-family: Arial, sans-serif; background: #f1f5f9; color: #1f2937; }

        /* ── Site header ── */
        .site-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 1.1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .site-header .logo-title {
            font-size: 1.6rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.08em;
        }
        .site-header .logo-sub {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 0.2rem;
            letter-spacing: 0.04em;
        }

        /* ── Main navigation ── */
        .main-nav {
            background: #f3bf3a;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: stretch;
        }
        .main-nav a {
            display: flex;
            align-items: center;
            padding: 0.85rem 1.3rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.15s;
        }
        .main-nav a:hover, .main-nav a.active { background: rgba(0,0,0,0.12); }
        .nav-right { margin-left: auto; display: flex; }
        .nav-right a { border-left: 1px solid rgba(0,0,0,0.12); }
        .nav-right a.btn-login {
            background: #0f172a;
            color: #f3bf3a;
        }
        .nav-right a.btn-login:hover { background: #1e293b; }
        .nav-right a.btn-register {
            background: #334155;
            color: #f8fafc;
        }
        .nav-right a.btn-register:hover { background: #475569; }
        .nav-right a.btn-logout { background: #7f1d1d; color: #fef2f2; }
        .nav-right a.btn-logout:hover { background: #991b1b; }
        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 45%, #1a4a6b 70%, #0f172a 100%);
            min-height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 40px,
                rgba(243,191,58,0.04) 40px,
                rgba(243,191,58,0.04) 80px
            );
        }
        .hero-content {
            position: relative;
            text-align: center;
            padding: 2.5rem 1.5rem;
        }
        .hero-content h2 {
            font-size: 2.4rem;
            font-weight: 900;
            color: #f3bf3a;
            letter-spacing: 0.12em;
            text-shadow: 0 2px 12px rgba(0,0,0,0.4);
        }
        .hero-content p {
            color: #cbd5e1;
            margin-top: 0.7rem;
            font-size: 0.95rem;
            max-width: 600px;
        }

        /* ── Info banner ── */
        .info-banner {
            max-width: 860px;
            margin: 2rem auto 0;
            background: #fff;
            border-left: 4px solid #f3bf3a;
            border-radius: 6px;
            box-shadow: 0 3px 12px rgba(15,23,42,0.08);
            padding: 1.1rem 1.4rem;
            color: #1e40af;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* ── Page wrapper ── */
        .page-wrap { max-width: 1200px; margin: 2rem auto; padding: 0 1.25rem 3rem; }

        /* ── Search panel ── */
        .panel {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 14px rgba(15,23,42,0.08);
            padding: 1.6rem;
            margin-bottom: 1.5rem;
        }
        .panel h1 { font-size: 1.3rem; color: #0f172a; margin-bottom: 0.3rem; }
        .panel > p { color: #64748b; font-size: 0.9rem; margin-bottom: 1rem; }

        .search-form { display: flex; gap: 0.6rem; }
        .search-form input[type="text"] {
            flex: 1;
            padding: 0.65rem 0.85rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
        }
        .search-form input[type="text"]:focus { border-color: #f3bf3a; box-shadow: 0 0 0 3px rgba(243,191,58,0.2); }
        .search-form button {
            padding: 0.65rem 1.4rem;
            background: #f3bf3a;
            color: #0f172a;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
        }
        .search-form button:hover { background: #e0aa20; }

        .result-count { margin-top: 0.8rem; font-size: 0.85rem; color: #64748b; }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; margin-top: 1rem; }
        .declarations-full-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            
        }
        .declarations-full-table thead tr { background: #0f172a; color: #f3bf3a; }
        .declarations-full-table th {
            padding: 0.7rem 0.75rem;
            text-align: left;
            white-space: nowrap;
            font-weight: 700;
        }
        .declarations-full-table td {
            padding: 0.6rem 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            color: #374151;
        }
        .declarations-full-table tbody tr:hover { background: #f8fafc; }
        .declarations-full-table .text-cell { max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .declarations-full-table .empty { text-align: center; color: #94a3b8; padding: 2rem; }

        /* ── Breadcrumb ── */
        .breadcrumb {
            font-size: 0.82rem;
            color: #94a3b8;
            margin-bottom: 1.2rem;
        }
        .breadcrumb a { color: #64748b; text-decoration: none; }
        .breadcrumb a:hover { color: #f3bf3a; }
        .breadcrumb span { margin: 0 0.4rem; }

        /* ── Footer ── */
        .site-footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 2.5rem 2rem 1.5rem;
            margin-top: 3rem;
        }
        .footer-inner { max-width: 1200px; margin: 0 auto; }
        .footer-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #f3bf3a;
            margin-bottom: 1rem;
        }
        .footer-links {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.5rem 2rem;
            margin-bottom: 2rem;
        }
        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .footer-links a:hover { color: #f3bf3a; }
        .footer-bottom {
            border-top: 1px solid #1e293b;
            padding-top: 1rem;
            font-size: 0.78rem;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .footer-bottom a { color: #94a3b8; text-decoration: none; }
        .footer-bottom a:hover { color: #f3bf3a; }

        /* ── Logged-in citizen nav extras ── */
        .nav-citizen a { border-left: 1px solid rgba(0,0,0,0.12); }
    </style>
</head>
<body>


<!-- Site header -->
<header class="site-header">
    <div>
        <div class="logo-title">ΠΟΘΕΝ ΕΣΧΕΣ</div>
        <div class="logo-sub">Δηλώσεις Περιουσιακής &amp; Επαγγελματικής Κατάστασης</div>
    </div>
</header>

<!-- Main navigation -->
<nav class="main-nav">
    <div class="nav-inner">
        <a href="list.php" class="active">Αρχική</a>
        <a href="/modules/search/search.php?section=search" class="<?= $section === 'search' ? 'active' : '' ?>">Αναζήτηση</a>
        <a href="search/search.php?section=statistics">Στατιστικά</a>

        <?php if ($isLoggedInCitizen): ?>
            <a href="search/search.php?section=dashboard">Ταμπλό</a>
            <a href="search/search.php?section=change-password">Αλλαγή Κωδικού</a>
        <?php endif; ?>

        <div class="nav-right">
            <?php if ($isLoggedInCitizen): ?>
                <a href="../auth/logout.php" class="btn-logout">Αποσύνδεση (<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>)</a>
            <?php else: ?>
                <a href="../auth/register.php" class="btn-register">Εγγραφή</a>
                <a href="../auth/login.php" class="btn-login">Σύνδεση</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero section -->
<div class="hero">
    <div class="hero-content">
        <h2>ΠΟΘΕΝ ΕΣΧΕΣ</h2>
        <p>Ο περί Δημοσίων Υπαλλήλων (Υποβολή και Έλεγχος Καταστάσεων Προσωπικής και Επαγγελματικής Περιουσίας) Νόμος</p>
    </div>
</div>

<!-- Info banner -->
<div class="info-banner">
    Ο περί του Προέδρου, των Υπουργών και των Βουλευτών της Κυπριακής Δημοκρατίας (Υποβολή και Έλεγχος
    Καταστάσεων Προσωπικής και Επαγγελματικής Περιουσίας) Νόμος του 2024 [Ν. 112(Ι)/2024]
</div>

<!-- Page content -->
<div class="page-wrap">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="list.php">Αρχική</a>
        <span>›</span>
        <strong>Αναζήτηση Δηλώσεων</strong>
    </div>

    <section class="panel">
        <h1>Αναζήτηση Δηλώσεων</h1>
        <p>Αναζητήστε δηλώσεις με οποιοδήποτε διαθέσιμο πεδίο.</p>

        <form class="search-form" method="GET" action="list.php">
            <input
                type="text"
                name="keyword"
                placeholder="Όνομα, κόμμα, θέση, χρέη, επαρχία, εισόδημα..."
                value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
            >
            <button type="submit">Αναζήτηση</button>
        </form>

        <p class="result-count">
            <?php if ($keyword !== ''): ?>
                <?= count($results) ?> αποτέλεσμα<?= count($results) !== 1 ? 'τα' : '' ?> για "<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
            <?php else: ?>
                Εμφάνιση όλων των <?= count($results) ?> δηλώσεων
            <?php endif; ?>
        </p>

        <div class="table-wrap">
            <table class="declarations-full-table">
                <thead>
                    <tr>
                        <th>Χρήστης</th>
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
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['username'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row['declaration_year'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row['party'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row['position'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row['province'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-cell"><?= htmlspecialchars((string) ($row['properties'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-cell"><?= htmlspecialchars((string) ($row['vehicles'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-cell"><?= htmlspecialchars((string) ($row['shares'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-cell"><?= htmlspecialchars((string) ($row['debts'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float) $row['income'], 2) ?></td>
                        <td><?= htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($results)): ?>
                    <tr><td colspan="13" class="empty">Δεν βρέθηκαν εγγραφές.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<!-- Footer -->
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-title">Σύνδεσμοι</div>
        <div class="footer-links">
            <a href="list.php">Αρχική</a>
            <a href="list.php">Δηλώσεις</a>
            <a href="search/search.php?section=statistics">Στατιστικά</a>
            <a href="../auth/login.php">Σύνδεση</a>
            <a href="../auth/register.php">Εγγραφή</a>
        </div>
        <div class="footer-bottom">
            <span>Σύστημα ΠΟΘΕΝ ΕΣΧΕΣ &copy; <?= date('Y') ?></span>
        </div>
    </div>
</footer>

</body>
</html>
