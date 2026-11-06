<?php
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
$conn->query("UPDATE users SET last_active = NOW() WHERE user_id = " . (int)$_SESSION["user_id"]);
$role = $_SESSION["role"];
$full_name = $_SESSION["full_name"];
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Infinity Sportswear Hub</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
/* ═══════════════════════════════════════
   60-30-10: Black(60) · Gold(30) · White(10)
   ═══════════════════════════════════════ */
:root {
    /* 60% — Dominant dark */
    --black:       #111214;
    --black-2:     #1a1c21;
    --black-3:     #23262d;
    /* 30% — Gold secondary */
    --gold:        #f0b429;
    --gold-light:  #f5c842;
    --gold-pale:   #fef9e7;
    --gold-dim:    rgba(240,180,41,0.12);
    --gold-border: rgba(240,180,41,0.25);
    /* 10% — White accent */
    --white:       #ffffff;
    --off-white:   #f8f9fa;
    --gray-100:    #f1f3f5;
    --gray-200:    #e9ecef;
    --gray-400:    #ced4da;
    --gray-500:    #868e96;
    --gray-700:    #495057;
    --gray-900:    #212529;
    /* Status */
    --red:         #e03131;
    --red-pale:    #fff5f5;
    --green-ok:    #2f9e44;
    --green-pale:  #ebfbee;
    --amber:       #e67700;
    --amber-pale:  #fff8dc;
    --blue:        #1971c2;
    /* Shadows */
    --shadow-sm:   0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
    --shadow-md:   0 4px 12px rgba(0,0,0,0.10);
    --shadow-gold: 0 0 0 3px rgba(240,180,41,0.15);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Outfit', sans-serif;
    background: var(--gray-100);
    color: var(--gray-900);
    display: flex;
    min-height: 100vh;
    font-size: 14px;
}

/* ═══ SIDEBAR ═══ */
.sidebar {
    width: 240px;
    height: 100vh;
    background: var(--black);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0;
    z-index: 100;
    border-right: 1px solid var(--black-3);
}

.sidebar-brand {
    padding: 20px 16px 16px;
    border-bottom: 1px solid var(--black-3);
    display: flex;
    align-items: center;
    gap: 10px;
}
.sidebar-brand img {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid var(--gold-border);
}
.sidebar-brand-text h1 {
    font-size: 13px;
    font-weight: 700;
    color: var(--white);
    line-height: 1.2;
}
.sidebar-brand-text span {
    font-size: 10px;
    color: var(--gold);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 2px;
}
.status-dot {
    width: 6px; height: 6px;
    background: var(--gold);
    border-radius: 50%;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%,100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.sidebar-user {
    padding: 12px 16px;
    border-bottom: 1px solid var(--black-3);
    display: flex;
    align-items: center;
    gap: 10px;
}
.user-avatar {
    width: 34px; height: 34px;
    background: var(--gold-dim);
    border: 1.5px solid var(--gold-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    color: var(--gold);
    flex-shrink: 0;
}
.user-info .name {
    font-size: 12px;
    font-weight: 600;
    color: var(--white);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
}
.role-badge {
    display: inline-block;
    font-size: 9px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    margin-top: 3px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.role-Manager    { background: rgba(240,180,41,0.2); color: var(--gold); border: 1px solid var(--gold-border); }
.role-Storekeeper{ background: rgba(25,113,194,0.2); color: #74c0fc; border: 1px solid rgba(25,113,194,0.3); }
.role-Sales      { background: rgba(47,158,68,0.2);  color: #69db7c; border: 1px solid rgba(47,158,68,0.3); }

.sidebar nav { padding: 10px 0; flex: 1; overflow-y: auto; }

.nav-section {
    font-size: 9px;
    font-weight: 700;
    color: var(--gray-500);
    padding: 12px 16px 4px;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 16px;
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-500);
    text-decoration: none;
    transition: all 0.15s;
    border-left: 3px solid transparent;
    position: relative;
}
.nav-link:hover {
    background: var(--black-2);
    color: var(--white);
    border-left-color: var(--gold-border);
}
.nav-link.active {
    background: var(--gold-dim);
    color: var(--gold);
    border-left-color: var(--gold);
    font-weight: 600;
}
.nav-link svg { flex-shrink: 0; width: 16px; height: 16px; }

.sidebar-footer {
    padding: 12px 16px;
    border-top: 1px solid var(--black-3);
}
.sidebar-footer {
    padding: 12px 16px;
    border-top: 1px solid var(--black-3);
}

.sidebar-logout-top {
    padding: 10px 16px;
    border-bottom: 1px solid var(--black-3);
}
.sidebar-logout-top .logout-btn {
    padding: 4px 0;
}
.logout-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--gray-500);
    text-decoration: none;
    font-weight: 500;
    padding: 8px 0;
    transition: color 0.15s;
    font-family: 'Outfit', sans-serif;
}
.logout-btn:hover { color: #ff6b6b; }

/* ═══ MAIN ═══ */
.main {
    margin-left: 240px;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.topbar {
    background: var(--white);
    border-bottom: 1px solid var(--gray-200);
    padding: 0 28px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 50;
    box-shadow: var(--shadow-sm);
}
.topbar-left { display: flex; flex-direction: column; }
.topbar h2 {
    font-size: 18px;
    font-weight: 700;
    color: var(--black);
    letter-spacing: -0.3px;
}
.topbar .breadcrumb {
    font-size: 11px;
    color: var(--gray-500);
    margin-top: 1px;
}
.topbar-right { display: flex; align-items: center; gap: 10px; }

.page-content { padding: 24px 28px; flex: 1; }

/* ═══ STAT CARDS ═══ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--white);
    border-radius: 10px;
    padding: 20px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
}
.stat-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-1px);
}
.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 4px;
    height: 100%;
    background: var(--gold);
    border-radius: 10px 0 0 10px;
}
.stat-card.red::before   { background: var(--red); }
.stat-card.blue::before  { background: var(--blue); }
.stat-card.green::before { background: var(--green-ok); }
.stat-card.gold::before  { background: var(--gold); }

.stat-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.stat-icon {
    width: 38px; height: 38px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.icon-gold  { background: var(--gold-pale);  color: var(--gold); }
.icon-red   { background: var(--red-pale);   color: var(--red); }
.icon-green { background: var(--green-pale); color: var(--green-ok); }
.icon-blue  { background: #e7f5ff;           color: var(--blue); }
.icon-amber { background: var(--amber-pale); color: var(--amber); }

.stat-label { font-size: 11px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.stat-value { font-size: 26px; font-weight: 800; color: var(--black); font-family: 'JetBrains Mono', monospace; letter-spacing: -1px; line-height: 1; }
.stat-sub   { font-size: 11px; color: var(--gray-500); margin-top: 5px; }

/* ═══ CARDS ═══ */
.card {
    background: var(--white);
    border-radius: 10px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    overflow: hidden;
}
.card-header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--white);
}
.card-header h3 {
    font-size: 14px;
    font-weight: 700;
    color: var(--black);
    letter-spacing: -0.2px;
}
.card-body { padding: 20px; }

/* ═══ TABLES ═══ */
table { width: 100%; border-collapse: collapse; }
thead th {
    padding: 10px 16px;
    text-align: left;
    font-size: 10px;
    font-weight: 700;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    background: var(--off-white);
    border-bottom: 1px solid var(--gray-200);
    white-space: nowrap;
}
tbody tr {
    border-bottom: 1px solid var(--gray-100);
    transition: background 0.1s;
}
tbody tr:hover { background: var(--gold-pale); }
tbody td { padding: 12px 16px; font-size: 13px; color: var(--gray-700); }
tbody tr:last-child { border-bottom: none; }

/* ═══ BADGES ═══ */
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.3px;
}
.badge-A     { background: #fff5f5; color: var(--red); border: 1px solid #ffc9c9; }
.badge-B     { background: var(--amber-pale); color: var(--amber); border: 1px solid #ffe8a1; }
.badge-C     { background: var(--green-pale); color: var(--green-ok); border: 1px solid #b2f2bb; }
.badge-gold  { background: var(--gold-pale); color: #b08000; border: 1px solid var(--gold-border); }
.badge-green { background: var(--green-pale); color: var(--green-ok); border: 1px solid #b2f2bb; }
.badge-red   { background: var(--red-pale); color: var(--red); border: 1px solid #ffc9c9; }
.badge-amber { background: var(--amber-pale); color: var(--amber); border: 1px solid #ffe8a1; }
.badge-blue  { background: #e7f5ff; color: var(--blue); border: 1px solid #a5d8ff; }

/* ═══ BUTTONS ═══ */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all 0.15s;
    font-family: 'Outfit', sans-serif;
    letter-spacing: 0.1px;
}
.btn-primary {
    background: var(--gold);
    color: var(--black);
}
.btn-primary:hover {
    background: var(--gold-light);
    box-shadow: 0 2px 8px rgba(240,180,41,0.35);
}
.btn-dark {
    background: var(--black);
    color: var(--white);
}
.btn-dark:hover { background: var(--black-2); }
.btn-danger {
    background: var(--red-pale);
    color: var(--red);
    border: 1px solid #ffc9c9;
}
.btn-danger:hover { background: #ffe3e3; }
.btn-secondary {
    background: var(--gray-100);
    color: var(--gray-700);
    border: 1px solid var(--gray-200);
}
.btn-secondary:hover { background: var(--gray-200); }
.btn-sm { padding: 5px 11px; font-size: 11px; border-radius: 5px; }

/* ═══ FORMS ═══ */
.form-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
.form-group  { display: flex; flex-direction: column; gap: 5px; }
.form-group label {
    font-size: 11px;
    font-weight: 700;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.form-group input,
.form-group select,
.form-group textarea {
    padding: 9px 12px;
    border: 1.5px solid var(--gray-200);
    border-radius: 7px;
    font-size: 13px;
    font-family: 'Outfit', sans-serif;
    color: var(--gray-900);
    background: var(--white);
    transition: border 0.15s, box-shadow 0.15s;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: var(--shadow-gold);
}

/* ═══ ALERTS ═══ */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid;
}
.alert-success { background: var(--green-pale); color: #1a6b2e; border-color: #b2f2bb; }
.alert-danger  { background: var(--red-pale);   color: #a50000; border-color: #ffc9c9; }
.alert-warning { background: var(--amber-pale); color: #7d4000; border-color: #ffe8a1; }
.alert-gold    { background: var(--gold-pale);  color: #7d5c00; border-color: var(--gold-border); }

/* ═══ LAYOUT HELPERS ═══ */
.two-col   { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
.three-col { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px; }
.low-stock-row { background: #fff5f5 !important; }
.low-stock-row td { color: var(--red) !important; }

/* ═══ HAMBURGER (mobile) ═══ */
.hamburger {
    display: none;
    position: fixed;
    top: 14px; left: 14px;
    z-index: 200;
    background: var(--black);
    border: 1.5px solid var(--gold-border);
    border-radius: 8px;
    padding: 8px 10px;
    cursor: pointer;
    flex-direction: column;
    gap: 5px;
}
.hamburger span {
    display: block;
    width: 20px; height: 2px;
    background: var(--gold);
    border-radius: 2px;
}
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    z-index: 99;
    backdrop-filter: blur(2px);
}

/* ═══ MOBILE ═══ */
@media (max-width: 768px) {
    .hamburger { display: flex; }
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.25s ease;
        z-index: 150;
    }
    .sidebar.open { transform: translateX(0); }
    .sidebar-overlay.open { display: block; }
    .main { margin-left: 0; }
    .topbar { padding: 0 16px 0 54px; height: 56px; }
    .topbar h2 { font-size: 15px; }
    .page-content { padding: 16px; }
    .stats-grid { grid-template-columns: 1fr 1fr !important; gap: 10px; }
    .stat-value { font-size: 20px !important; }
    .two-col, .three-col { grid-template-columns: 1fr; }
    .form-grid, .form-grid-3 { grid-template-columns: 1fr; }
    table { font-size: 12px; }
    thead th { padding: 8px 10px; font-size: 9px; }
    tbody td { padding: 8px 10px; }
}
@media (max-width: 400px) {
    .stats-grid { grid-template-columns: 1fr !important; }
}
</style>
</head>
<body>

<button class="hamburger" id="hamburger" onclick="toggleSidebar()">
    <span></span><span></span><span></span>
</button>
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <img src="logo.jpeg" alt="Logo">
        <div class="sidebar-brand-text">
            <h1>Infinity Sportswear</h1>
            <span><span class="status-dot"></span>System Online</span>
        </div>
    </div>
    <!-- Logout -->
    <div class="sidebar-logout-top">
        <a href="logout.php" class="logout-btn">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            Sign Out
        </a>
    </div>

    <!-- User -->
    <div class="sidebar-user">
        <div class="user-avatar"><?php echo strtoupper(substr($full_name,0,1)); ?></div>
        <div class="user-info">
            <div class="name"><?php echo htmlspecialchars($full_name); ?></div>
            <span class="role-badge role-<?php echo explode(' ',$role)[0]; ?>"><?php echo $role; ?></span>
        </div>
    </div>

    <!-- Nav -->
    <nav>
        <div class="nav-section">Overview</div>
        <a href="dashboard.php" class="nav-link <?php echo $current_page=='dashboard.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a>

        <div class="nav-section">Inventory</div>
        <a href="inventory.php" class="nav-link <?php echo $current_page=='inventory.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
            View Inventory
        </a>
        <?php if($role == 'Manager' || $role == 'Storekeeper'): ?>
        <a href="stock_in.php" class="nav-link <?php echo $current_page=='stock_in.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
            Add Stock
        </a>
        <?php endif; ?>

        <div class="nav-section">Sales</div>
        <a href="sales.php" class="nav-link <?php echo $current_page=='sales.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4H9m0 0a2 2 0 000 4h6a2 2 0 000-4M9 3h6"/></svg>
            Record Sale
        </a>

        <?php if($role == 'Manager'): ?>
        <div class="nav-section">Management</div>
        <a href="suppliers.php" class="nav-link <?php echo $current_page=='suppliers.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            Suppliers
        </a>
        <a href="financials.php" class="nav-link <?php echo $current_page=='financials.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            Financials
        </a>
        <?php endif; ?>
        

        <div class="nav-section">Reports</div>
        <a href="sales_report.php" class="nav-link <?php echo $current_page=='sales_report.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            Sales Report
        </a>
        <a href="inventory_report.php" class="nav-link <?php echo $current_page=='inventory_report.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
            Inventory Report
        </a>
        <a href="low_stock_report.php" class="nav-link <?php echo $current_page=='low_stock_report.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Low Stock Alerts
        </a>
        <?php if($role == 'Manager'): ?>
        <a href="supplier_report.php" class="nav-link <?php echo $current_page=='supplier_report.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            Supplier Report
        </a>
        <a href="financials_report.php" class="nav-link <?php echo $current_page=='financials_report.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            Financials Report
        </a>
        <a href="audit_report.php" class="nav-link <?php echo $current_page=='audit_report.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Audit Log
        </a>
        <a href="user_activity_report.php" class="nav-link <?php echo $current_page=='user_activity_report.php'?'active':''; ?>">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
    User Activity
</a>
        <?php endif; ?>

        <div class="nav-section">Feedback</div>

        <div class="nav-section">Feedback</div>
        <a href="feedback.php" class="nav-link <?php echo $current_page=='feedback.php'?'active':''; ?>">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Rate System
        </a>
    </nav>

    
</aside>

<div class="main">
