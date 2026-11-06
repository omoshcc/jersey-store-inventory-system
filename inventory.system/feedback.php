<?php
session_start();
include("connection.php");

$user_id = (int)$_SESSION["user_id"];
$msg = $_GET['msg'] ?? '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ease       = max(1, min(5, (int)$_POST['ease_of_use']));
    $useful     = max(1, min(5, (int)$_POST['usefulness']));
    $speed      = max(1, min(5, (int)$_POST['speed']));
    $accuracy   = max(1, min(5, (int)$_POST['accuracy']));
    $overall    = max(1, min(5, (int)$_POST['overall']));
    $comments   = trim($_POST['comments']);

    $stmt = $conn->prepare("INSERT INTO user_feedback (user_id, ease_of_use, usefulness, speed, accuracy, overall, comments) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("iiiiiis", $user_id, $ease, $useful, $speed, $accuracy, $overall, $comments);
    if($stmt->execute()) {
        header("Location: feedback.php?msg=submitted"); exit();
    } else {
        header("Location: feedback.php?msg=error"); exit();
    }
}

include("header.php");
?>

<div class="topbar">
    <div><h2>System Feedback</h2><div class="sub">Rate your experience using the Infinity Inventory System</div></div>
</div>

<div class="page-content">

<?php if($msg == 'submitted'): ?>
    <div class="alert alert-success">✓ Thank you! Your feedback has been recorded successfully.</div>
<?php endif; ?>
<?php if($msg == 'error'): ?>
    <div class="alert alert-danger">Something went wrong. Please try again.</div>
<?php endif; ?>


<div class="card" style="max-width:620px">
    <div class="card-header"><h3>User Acceptance Testing — Feedback Form</h3></div>
    <div class="card-body">
        <p style="font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.6">
            This form is part of the system acceptance testing for the Infinity Sportswear Hub Inventory Management System. 
            Please rate each aspect of the system honestly based on your experience using it.
        </p>

        <form method="POST">
            <?php
            $criteria = [
                'ease_of_use' => 'Ease of Use — How easy was the system to navigate and operate?',
                'usefulness'  => 'Usefulness — Does the system help you do your job more effectively?',
                'speed'       => 'Speed — How fast does the system respond and process tasks?',
                'accuracy'    => 'Accuracy — Does the system correctly record and display stock and sales data?',
                'overall'     => 'Overall Satisfaction — Your overall rating of the system',
            ];
            foreach($criteria as $name => $label):
            ?>
            <div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--border)">
                <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:10px"><?php echo $label; ?></div>
                <div style="display:flex;gap:10px" class="star-group" data-name="<?php echo $name; ?>">
                    <?php for($i=1;$i<=5;$i++): ?>
                    <label style="cursor:pointer;text-align:center">
                        <input type="radio" name="<?php echo $name; ?>" value="<?php echo $i; ?>" required style="display:none">
                        <div class="star-btn" data-val="<?php echo $i; ?>" style="width:44px;height:44px;border-radius:8px;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--muted);transition:all 0.15s;background:#fafafa">
                            <?php echo $i; ?>
                        </div>
                        <div style="font-size:10px;color:var(--muted);margin-top:4px;text-align:center">
                            <?php echo ['','Poor','Fair','Good','Great','Excel.'][$i]; ?>
                        </div>
                    </label>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="form-group" style="margin-bottom:20px">
                <label>Additional Comments / Suggestions</label>
                <textarea name="comments" rows="4" placeholder="Share any specific feedback, features you liked, things that could be improved, or issues you encountered..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">
                Submit Feedback
            </button>
        </form>
    </div>
</div>

</div>

<style>
.star-btn:hover, .star-btn.selected {
    background: var(--green) !important;
    color: #fff !important;
    border-color: var(--green) !important;
}
</style>

<script>
document.querySelectorAll('.star-group').forEach(group => {
    group.querySelectorAll('label').forEach(label => {
        const btn = label.querySelector('.star-btn');
        const input = label.querySelector('input');
        label.addEventListener('click', () => {
            const val = parseInt(btn.getAttribute('data-val'));
            group.querySelectorAll('.star-btn').forEach((b,i) => {
                b.classList.toggle('selected', i < val);
            });
            input.checked = true;
        });
    });
});
</script>

<?php include("footer.php"); ?>
