<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"])) { header("Location: login.php"); exit(); }
if($_SESSION["role"] !== "Manager") { header("Location: dashboard.php"); exit(); }
include("header.php");

// "Online" = active within the last 5 minutes
$ONLINE_THRESHOLD_MINUTES = 5;

$users = $conn->query("
    SELECT user_id, full_name, username, email, role, created_at, last_active,
           TIMESTAMPDIFF(MINUTE, last_active, NOW()) as mins_since_active
    FROM users
    ORDER BY (last_active IS NULL) ASC, last_active DESC
");

$total_users = 0;
$online_count = 0;
$rows = [];
while($r = $users->fetch_assoc()) {
    $rows[] = $r;
    $total_users++;
    if($r['last_active'] !== null && $r['mins_since_active'] !== null && $r['mins_since_active'] <= $ONLINE_THRESHOLD_MINUTES) {
        $online_count++;
    }
}

function time_ago($datetime) {
    if(!$datetime) return 'Never logged in';
    $diff = time() - strtotime($datetime);
    if($diff < 60) return 'Just now';
    if($diff < 3600) return floor($diff / 60) . ' min ago';
    if($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if($diff < 604800) return floor($diff / 86400) . ' day(s) ago';
    return date('d M Y', strtotime($datetime));
}
?>

<div class="topbar">
    <div class="topbar-left">
        <h2>User Activity Report</h2>
        <div class="breadcrumb">Login activity, online status, and account tenure</div>
    </div>
    <div class="topbar-right">
        <span style="font-size:12px;color:var(--gray-500)">Generated: <?php echo date('d M Y H:i'); ?></span>
    </div>
</div>

<div class="page-content">

<!-- SUMMARY STATS -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
    <div class="stat-card gold">
        <div class="stat-card-top">
            <div class="stat-label">Total Users</div>
            <div class="stat-icon icon-gold">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo $total_users; ?></div>
        <div class="stat-sub">All registered accounts</div>
    </div>
    <div class="stat-card green">
        <div class="stat-card-top">
            <div class="stat-label">Currently Online</div>
            <div class="stat-icon icon-green">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
            </div>
        </div>
        <div class="stat-value" style="color:var(--green-ok)"><?php echo $online_count; ?></div>
        <div class="stat-sub">Active in the last <?php echo $ONLINE_THRESHOLD_MINUTES; ?> minutes</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-card-top">
            <div class="stat-label">Offline</div>
            <div class="stat-icon icon-blue">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            </div>
        </div>
        <div class="stat-value"><?php echo $total_users - $online_count; ?></div>
        <div class="stat-sub">Not active recently</div>
    </div>
</div>

<!-- USERS TABLE -->
<div class="card">
    <div class="card-header">
        <h3>All Users (<?php echo $total_users; ?>)</h3>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Status</th><th>Full Name</th><th>Username</th><th>Email</th><th>Role</th>
                <th>Date Joined</th>
            </tr>
        </thead>
        <tbody>
        <?php if(empty($rows)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--gray-500);padding:30px">No users found.</td></tr>
        <?php else: foreach($rows as $r):
            $is_online = ($r['last_active'] !== null && $r['mins_since_active'] !== null && $r['mins_since_active'] <= $ONLINE_THRESHOLD_MINUTES);
        ?>
            <tr>
                <td>
                    <?php if($is_online): ?>
                        <span class="badge badge-green">● Online</span>
                    <?php else: ?>
                        <span class="badge badge-red" style="opacity:0.7">○ Offline</span>
                    <?php endif; ?>
                </td>
                <td style="font-weight:600"><?php echo htmlspecialchars($r['full_name']); ?></td>
                <td style="color:var(--gray-500)"><?php echo htmlspecialchars($r['username']); ?></td>
                <td style="color:var(--gray-500);font-size:12px"><?php echo htmlspecialchars($r['email']); ?></td>
                <td><span class="badge badge-blue"><?php echo htmlspecialchars($r['role']); ?></span></td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:11px">
                    <?php echo $r['created_at'] ? date('d M Y', strtotime($r['created_at'])) : '—'; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

</div>
<?php include("footer.php"); ?>
