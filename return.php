<?php
require_once __DIR__ . '/db.php';
include __DIR__ . '/includes/header.php';

// Fetch recent issues for reference table
$recentIssues = $conn->query("
    SELECT ib.id, b.title, s.name AS student_name, s.student_id, ib.issue_date, ib.due_date 
    FROM issued_books ib
    JOIN books b ON b.id = ib.book_id
    JOIN students s ON s.id = ib.student_id
    WHERE ib.status = 'issued'
    ORDER BY ib.issue_date DESC LIMIT 15
");
?>

<div class="page-header">
    <div class="breadcrumb"><a href="index.php">Home</a><span>›</span><span>Return Books</span></div>
    <h1>↩️ Book Returns & Fines</h1>
    <p>Look up your issue record to return a book. Late returns incur a standard $<?= number_format(FINE_PER_DAY, 2) ?>/day fine.</p>
</div>

<section class="section">
    <div class="dashboard-grid">
        
        <!-- Look Up Column -->
        <div class="card animate-in">
            <h3 class="mb-3">🔎 Look Up Issue Record</h3>
            <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:1.5rem;">Enter your Issue ID to process a return. If the book is past due, you will be redirected to the fine payment portal.</p>
            
            <form id="returnLookupForm" novalidate onsubmit="handleReturnLookup(event)">
                <div class="form-group mb-3">
                    <label for="returnIssueId">Issue Record ID</label>
                    <input type="number" id="returnIssueId" placeholder="e.g. 5" min="1" required>
                    <div id="errReturnId" class="form-error"></div>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Look Up & Process Return</button>
            </form>

            <div id="returnResult" style="margin-top:2rem;"></div>
        </div>

        <!-- Reference Table Column -->
        <div class="card animate-in delay-1">
            <h3 class="mb-3">📋 Currently Issued Books</h3>
            <p style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:1rem;">Recent issues are listed here for quick reference.</p>
            
            <?php if ($recentIssues && $recentIssues->num_rows > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Issue #</th>
                                <th>Student ID</th>
                                <th>Book Title</th>
                                <th style="text-align:right;">Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = $recentIssues->fetch_assoc()): 
                                $isLate = strtotime($r['due_date']) < strtotime('today');
                            ?>
                                <tr>
                                    <td><span class="badge badge-blue">#<?= h($r['id']) ?></span></td>
                                    <td style="font-size:0.85rem;"><?= h($r['student_id']) ?></td>
                                    <td style="font-size:0.85rem;font-weight:600;"><?= h($r['title']) ?></td>
                                    <td style="font-size:0.85rem;text-align:right;">
                                        <?= formatDate($r['due_date']) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $isLate ? 'badge-red' : 'badge-green' ?>">
                                            <?= $isLate ? 'Overdue ⚠️' : 'Active' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding:2rem;">
                    <div class="empty-icon">✅</div>
                    <p>All books have been returned. No active issues!</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<script>
async function handleReturnLookup(e) {
    e.preventDefault();
    const idInput = document.getElementById('returnIssueId');
    const errObj  = document.getElementById('errReturnId');
    const result  = document.getElementById('returnResult');
    
    if(!idInput.value) {
        errObj.innerText = "Please enter an Issue ID.";
        return;
    }
    
    errObj.innerText = "";
    result.innerHTML = `<div style="text-align:center;padding:1rem;color:var(--text-muted);">Looking up record...</div>`;

    try {
        const payload = new URLSearchParams({ issue_id: idInput.value });
        const res = await fetch('api/process_return.php', { method: 'POST', body: payload });
        const json = await res.json();
        
        if (json.success) {
            if (json.has_fine) {
                // Redirect to fine portal
                result.innerHTML = `
                    <div style="background:var(--warning);color:#fff;padding:1rem;border-radius:var(--radius-md);text-align:center;">
                        <strong>⚠️ Overdue Book Returned</strong><br>
                        Redirecting to Fine Portal...
                    </div>
                `;
                setTimeout(() => {
                    window.location.href = `fine.php?fine_id=${json.fine_id}`;
                }, 1500);
            } else {
                // Pure success
                result.innerHTML = `
                    <div style="background:var(--success);color:#fff;padding:1rem;border-radius:var(--radius-md);text-align:center;">
                        <strong>✅ ${json.message}</strong>
                    </div>
                `;
                showToast(json.message, 'success');
                setTimeout(() => window.location.reload(), 2000);
            }
        } else {
            result.innerHTML = `
                <div style="background:#fee2e2;color:var(--danger);padding:1rem;border-radius:var(--radius-md);border:1px solid var(--danger);text-align:center;">
                    <strong>❌ Error:</strong> ${json.message}
                </div>
            `;
            showToast(json.message, 'error');
        }
    } catch(err) {
        result.innerHTML = `<div style="color:var(--danger);">Network error occurred.</div>`;
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
