<?php
require_once __DIR__ . '/db.php';
include __DIR__ . '/includes/header.php';

$fineId = (int)($_GET['fine_id'] ?? 0);
if (!$fineId) {
    echo "<div class='page-wrapper'><div class='empty-state'><h4>Invalid Request</h4><p>Missing Fine ID.</p></div></div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch Fine Details
$stmt = $conn->prepare("
    SELECT f.*, b.title, s.name, s.student_id, f.fine_amount, f.status 
    FROM fines f
    JOIN issued_books ib ON f.issue_id = ib.id
    JOIN books b ON ib.book_id = b.id
    JOIN students s ON f.student_id = s.id
    WHERE f.id = ?
");
$stmt->bind_param('i', $fineId);
$stmt->execute();
$fine = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fine) {
    echo "<div class='page-wrapper'><div class='empty-state'><h4>Fine Not Found</h4><p>Record does not exist.</p></div></div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="page-header">
    <div class="breadcrumb"><a href="index.php">Home</a><span>›</span><a href="return.php">Returns</a><span>›</span><span>Overview</span></div>
    <h1>💸 Fine Overview</h1>
    <p>Please review and settle your overdue library fees.</p>
</div>

<section class="section" style="max-width:800px;margin:0 auto;">
    <div class="card fine-card animate-in">
        
        <?php if ($fine['status'] === 'paid'): ?>
            <div class="fine-status paid">✅ Fine Paid Successfully</div>
            <h2 style="color:var(--text-primary);margin-bottom:1rem;">Thank you, <?= h($fine['name']) ?>!</h2>
            <p style="color:var(--text-secondary);margin-bottom:2rem;">Your account is clear. The fine for <strong>"<?= h($fine['title']) ?>"</strong> has been settled.</p>
            <a href="books.php" class="btn btn-primary">📖 Browse Catalog</a>
        
        <?php else: ?>
            <div class="fine-status unpaid">⚠️ Overdue Fine Required</div>
            <p style="color:var(--text-secondary);font-size:0.95rem;">Student: <strong><?= h($fine['name']) ?> (<?= h($fine['student_id']) ?>)</strong></p>
            <p style="color:var(--text-secondary);font-size:0.95rem;">Book: <strong><?= h($fine['title']) ?></strong></p>
            <p style="color:var(--text-secondary);font-size:0.95rem;margin-top:0.5rem;">
                Days Late: <strong><?= (int)$fine['days_late'] ?> days</strong> <br>
                Rate: $<?= number_format(FINE_PER_DAY, 2) ?> / day
            </p>

            <div class="fine-amount">$<?= number_format($fine['fine_amount'], 2) ?></div>
            
            <hr style="border:none;border-top:1px dashed var(--border);margin:2rem 0;">

            <h3>💳 Secure Payment Portal</h3>
            <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1.5rem;">For this project mockup, click "Pay Securely" to simulate clearing this fine.</p>
            
            <form id="payFineForm" onsubmit="handlePayment(event)">
                <input type="hidden" name="fine_id" value="<?= $fineId ?>">
                <button type="submit" id="btnPay" class="btn btn-success" style="font-size:1.1rem;padding:0.75rem 2rem;">💳 Pay Securely ($<?= number_format($fine['fine_amount'], 2) ?>)</button>
            </form>

        <?php endif; ?>
    </div>
</section>

<script>
async function handlePayment(e) {
    e.preventDefault();
    const btn = document.getElementById('btnPay');
    btn.disabled = true;
    btn.innerText = 'Processing...';

    try {
        const payload = new URLSearchParams(new FormData(e.target));
        const res = await fetch('api/pay_fine.php', { method: 'POST', body: payload });
        const json = await res.json();
        
        if (json.success) {
            showToast(json.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(json.message, 'error');
            btn.disabled = false;
            btn.innerText = '💳 Pay Securely';
        }
    } catch (err) {
        showToast("Error connecting to payment server.", "error");
        btn.disabled = false;
        btn.innerText = '💳 Pay Securely';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
