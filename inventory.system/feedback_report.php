<?php
session_start();
include("connection.php");
if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != 'Manager') {
    header("Location: dashboard.php"); exit();
}
include("header.php");

$responses = $conn->query("
    SELECT f.*, u.full_name, u.role, f.submitted_at
    FROM user_feedback f
    JOIN users u ON f.user_id = u.user_id
    ORDER BY f.submitted_at DESC
");

$totals = $conn->query("
    SELECT 
        COUNT(*) as total_responses,
        ROUND(AVG(ease_of_use),2) as avg_ease,
        ROUND(AVG(usefulness),2) as avg_useful,
        ROUND(AVG(speed),2) as avg_speed,
        ROUND(AVG(accuracy),2) as avg_accuracy,
        ROUND(AVG(overall),2) as avg_overall,
        ROUND(AVG((ease_of_use+usefulness+speed+accuracy+overall)/5),2) as avg_total
    FROM user_feedback
")->fetch_assoc();
?>

<div class="topbar">
    <div><h2>User Testing Report</h2><div class="sub">Acceptance testing results — Infinity Inventory System</div></div>
    <a href="feedback.php" class="btn btn-secondary">My Feedback</a>
</div>

<div class="page-content">

<?php if($totals['total_responses'] == 0): ?>
    <div class="card" style="text-align:center">
        <div class="card-body" style="padding:40px">
            <p style="color:var(--muted)">No feedback submitted yet. Ask users to visit the Feedback page.</p>
        </div>
    </div>
<?php else: ?>

<!-- SUMMARY STATS -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="icon icon-blue">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div class="label">Total Responses</div>
        <div class="value"><?php echo $totals['total_responses']; ?></div>
        <div class="change">Users tested the system</div>
    </div>
    <div class="stat-card">
        <div class="icon icon-green">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <div class="label">Overall Average</div>
        <div class="value"><?php echo $totals['avg_total']; ?> <span style="font-size:14px;color:var(--muted)">/5</span></div>
        <div class="change">Across all criteria</div>
    </div>
    <div class="stat-card">
        <div class="icon icon-green">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3H14z"/><path d="M7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"/></svg>
        </div>
        <div class="label">Ease of Use</div>
        <div class="value"><?php echo $totals['avg_ease']; ?> <span style="font-size:14px;color:var(--muted)">/5</span></div>
        <div class="change">Avg usability rating</div>
    </div>
    <div class="stat-card">
        <div class="icon icon-amber">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div class="label">Usefulness</div>
        <div class="value"><?php echo $totals['avg_useful']; ?> <span style="font-size:14px;color:var(--muted)">/5</span></div>
        <div class="change">Avg usefulness rating</div>
    </div>
</div>

<!-- RADAR CHART -->
<div class="two-col" style="margin-bottom:24px">
    <div class="card">
        <div class="card-header"><h3>Performance Across All Criteria</h3></div>
        <div class="card-body"><canvas id="radarChart" height="220"></canvas></div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Criteria Breakdown</h3></div>
        <div class="card-body">
            <?php
            $criteria_labels = [
                'Ease of Use'  => $totals['avg_ease'],
                'Usefulness'   => $totals['avg_useful'],
                'Speed'        => $totals['avg_speed'],
                'Accuracy'     => $totals['avg_accuracy'],
                'Overall'      => $totals['avg_overall'],
            ];
            foreach($criteria_labels as $label => $val):
                $pct = ($val / 5) * 100;
                $color = $pct >= 80 ? 'var(--green)' : ($pct >= 60 ? 'var(--amber)' : 'var(--red)');
            ?>
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
                    <span style="font-size:13px;font-weight:500"><?php echo $label; ?></span>
                    <span style="font-size:13px;font-weight:700;font-family:'DM Mono',monospace;color:<?php echo $color; ?>"><?php echo $val; ?>/5</span>
                </div>
                <div style="background:#f3f4f6;border-radius:20px;height:8px">
                    <div style="background:<?php echo $color; ?>;width:<?php echo $pct; ?>%;height:8px;border-radius:20px;transition:width 0.8s"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- INDIVIDUAL RESPONSES TABLE -->
<div class="card">
    <div class="card-header">
        <h3>Individual Responses (<?php echo $totals['total_responses']; ?>)</h3>
    </div>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>User</th><th>Role</th><th>Ease</th><th>Useful</th>
                <th>Speed</th><th>Accuracy</th><th>Overall</th>
                <th>Avg</th><th>Comments</th><th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php while($r = $responses->fetch_assoc()):
            $avg = round(($r['ease_of_use']+$r['usefulness']+$r['speed']+$r['accuracy']+$r['overall'])/5, 1);
            $avg_color = $avg >= 4 ? 'badge-green' : ($avg >= 3 ? 'badge-amber' : 'badge-red');
        ?>
            <tr>
                <td style="font-weight:600"><?php echo htmlspecialchars($r['full_name']); ?></td>
                <td><span class="badge badge-green" style="font-size:10px"><?php echo $r['role']; ?></span></td>
                <td style="text-align:center"><?php echo $r['ease_of_use']; ?>/5</td>
                <td style="text-align:center"><?php echo $r['usefulness']; ?>/5</td>
                <td style="text-align:center"><?php echo $r['speed']; ?>/5</td>
                <td style="text-align:center"><?php echo $r['accuracy']; ?>/5</td>
                <td style="text-align:center"><?php echo $r['overall']; ?>/5</td>
                <td style="text-align:center"><span class="badge <?php echo $avg_color; ?>"><?php echo $avg; ?></span></td>
                <td style="font-size:12px;color:var(--muted);max-width:200px">
                    <?php echo $r['comments'] ? htmlspecialchars(substr($r['comments'],0,80)).(strlen($r['comments'])>80?'...':'') : '—'; ?>
                </td>
                <td style="font-family:'DM Mono',monospace;font-size:11px;white-space:nowrap"><?php echo date('d M Y H:i', strtotime($r['submitted_at'])); ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<?php endif; ?>
</div>

<script>
<?php if($totals['total_responses'] > 0): ?>
const radarCtx = document.getElementById('radarChart').getContext('2d');
new Chart(radarCtx, {
    type: 'radar',
    data: {
        labels: ['Ease of Use', 'Usefulness', 'Speed', 'Accuracy', 'Overall'],
        datasets: [{
            label: 'Average Rating',
            data: [
                <?php echo $totals['avg_ease']; ?>,
                <?php echo $totals['avg_useful']; ?>,
                <?php echo $totals['avg_speed']; ?>,
                <?php echo $totals['avg_accuracy']; ?>,
                <?php echo $totals['avg_overall']; ?>
            ],
            backgroundColor: 'rgba(26,122,62,0.15)',
            borderColor: '#1a7a3e',
            borderWidth: 2,
            pointBackgroundColor: '#1a7a3e',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        scales: {
            r: {
                min: 0, max: 5,
                ticks: { stepSize: 1, font: { family: 'DM Mono', size: 10 } },
                pointLabels: { font: { family: 'DM Sans', size: 12 } },
                grid: { color: '#e5e7eb' }
            }
        },
        plugins: { legend: { display: false } }
    }
});
<?php endif; ?>
</script>

<?php include("footer.php"); ?>
