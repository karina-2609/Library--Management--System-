<?php
require_once __DIR__ . '/db.php';
include __DIR__ . '/includes/header.php';

// Mock Librarian Authentication - In a real app this would use $_SESSION['librarian_id']
$empIdToView = $_GET['emp'] ?? 'LIB001';

$stmt = $conn->prepare("SELECT * FROM librarians WHERE emp_id = ?");
$stmt->bind_param('s', $empIdToView);
$stmt->execute();
$librarian = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$librarian) {
    echo "<div class='page-wrapper'><div class='empty-state'><h4>Access Denied</h4><p>Invalid Librarian Credentials.</p></div></div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch general dashboard metrics
$booksOut = $conn->query("SELECT COUNT(*) as c FROM issued_books WHERE status='issued'")->fetch_assoc()['c'] ?? 0;
$finesDue = $conn->query("SELECT SUM(fine_amount) as c FROM fines WHERE status='unpaid'")->fetch_assoc()['c'] ?? 0;
$finesDue = $finesDue ?: 0; // null safe

// Fetch recent unpaid fines
$fines = $conn->query("
    SELECT f.*, s.name as student_name, s.student_id, b.title 
    FROM fines f
    JOIN students s ON f.student_id = s.id
    JOIN issued_books ib ON f.issue_id = ib.id
    JOIN books b ON ib.book_id = b.id
    WHERE f.status = 'unpaid'
    ORDER BY f.created_at DESC LIMIT 10
");
?>

<div class="page-header">
    <div class="breadcrumb"><a href="index.php">Home</a><span>›</span><span>Admin Desk</span></div>
    <h1>💼 Librarian Dashboard</h1>
    <p>Manage the library catalog, track overdue fines, and update your staff profile.</p>
</div>

<section class="section">
    <div class="dashboard-grid">
        
        <!-- PROFILE COLUMN (Left) -->
        <div class="card profile-card animate-in">
            <div class="avatar" style="background:var(--text-primary);"><?= strtoupper(substr($librarian['name'], 0, 1)) ?></div>
            <h2 style="margin-bottom:0.25rem;"><?= h($librarian['name']) ?></h2>
            <p style="color:var(--text-muted);font-weight:600;margin-bottom:1.5rem;">Emp ID: <?= h($librarian['emp_id']) ?></p>

            <div class="profile-details text-left">
                <p><strong>Qualif:</strong> <?= h($librarian['qualification']) ?></p>
                <p><strong>Exp:</strong> <?= h($librarian['experience']) ?> Years</p>
                <p><strong>Email:</strong> <?= h($librarian['email']) ?></p>
                <p><strong>Phone:</strong> <?= h($librarian['phone']) ?></p>
            </div>
            
            <hr style="border:none;border-top:1px dashed var(--border);margin:1.5rem 0;">
            
            <h4 style="margin-bottom:1rem;color:var(--text-secondary);text-align:left;">Quick Staff Actions</h4>
            <button class="btn btn-primary btn-full mb-2 text-left" onclick="openModal('addBookModal')" style="justify-content:flex-start;">📘 Register New Book</button>
            <button class="btn btn-outline btn-full text-left" onclick="openModal('addAnnounceModal')" style="justify-content:flex-start;">📣 Post Announcement</button>
        </div>

        <!-- DASHBOARD DATA COLUMN (Right) -->
        <div class="activity-col">
            
            <!-- Admin Metrics Row -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
                <div class="stat-card" style="padding:1.25rem;">
                    <div class="stat-icon amber" style="width:40px;height:40px;font-size:1.2rem;">📤</div>
                    <div>
                        <div class="stat-value" style="font-size:1.5rem;"><?= h($booksOut) ?></div>
                        <div class="stat-label">Books Checked Out</div>
                    </div>
                </div>
                <div class="stat-card" style="padding:1.25rem;">
                    <div class="stat-icon red" style="background:rgba(239,68,68,0.1);color:var(--danger);width:40px;height:40px;font-size:1.2rem;">💸</div>
                    <div>
                        <div class="stat-value" style="font-size:1.5rem;">$<?= number_format($finesDue, 2) ?></div>
                        <div class="stat-label">Total Unpaid Fines</div>
                    </div>
                </div>
            </div>

            <!-- UNPAID FINES TABLE -->
            <div class="card animate-in delay-1">
                <div class="flex-between mb-3">
                    <h3 style="margin:0;">🚨 Active Fine Accounts</h3>
                </div>

                <?php if ($fines && $fines->num_rows > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr><th>Student</th><th>Book</th><th>Late</th><th>Fine</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($f = $fines->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;font-size:0.9rem;"><?= h($f['student_name']) ?></div>
                                            <div style="font-size:0.75rem;color:var(--text-muted);"><?= h($f['student_id']) ?></div>
                                        </td>
                                        <td style="font-size:0.85rem;"><?= h($f['title']) ?></td>
                                        <td><span class="badge badge-amber"><?= h($f['days_late']) ?> days</span></td>
                                        <td style="font-weight:600;color:var(--danger);">$<?= number_format($f['fine_amount'], 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted);font-size:0.95rem;">No active fines outstanding. All accounts are clean!</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<!-- ADD BOOK MODAL -->
<div class="modal-overlay" id="addBookModal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <h3>📘 Register New Library Book</h3>
            <button class="modal-close" onclick="closeModal('addBookModal')">✕</button>
        </div>
        <div class="modal-body">
            <form id="addBookForm" onsubmit="handleAddBook(event)">
                <div class="form-group mb-3">
                    <label for="bTitle">Book Title *</label>
                    <input type="text" id="bTitle" name="title" required>
                    <div class="form-error" id="errBTitle"></div>
                </div>
                
                <div class="form-row mb-3">
                    <div class="form-group mb-0">
                        <label for="bAuthor">Author *</label>
                        <input type="text" id="bAuthor" name="author" required>
                        <div class="form-error" id="errBAuthor"></div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="bIsbn">ISBN</label>
                        <input type="text" id="bIsbn" name="isbn" placeholder="Optional">
                    </div>
                </div>
                
                <div class="form-row mb-3">
                    <div class="form-group mb-0">
                        <label for="bEdition">Edition</label>
                        <input type="text" id="bEdition" name="edition" placeholder="e.g. 5th Edition">
                    </div>
                    <div class="form-group mb-0">
                        <label for="bPublisher">Publisher</label>
                        <input type="text" id="bPublisher" name="publisher" placeholder="e.g. Pearson">
                    </div>
                </div>

                <div class="form-row mb-3">
                    <div class="form-group mb-0">
                        <label for="bCategory">Topic / Category *</label>
                        <select id="bCategory" name="category" required>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Fiction">Fiction</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Science">Science</option>
                            <option value="History">History</option>
                            <option value="Self-Help">Self-Help</option>
                            <option value="Philosophy">Philosophy</option>
                            <option value="General">General Reference</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label for="bCopies">Total Copies Received *</label>
                        <input type="number" id="bCopies" name="copies" value="1" min="1" required>
                    </div>
                </div>
                
                <div class="modal-footer" style="padding:1rem 0 0 0;margin-top:1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addBookModal')">Cancel</button>
                    <button type="submit" id="btnSaveBook" class="btn btn-primary">📖 Add to Catalog</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function handleAddBook(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveBook');
    btn.disabled = true;
    btn.innerText = 'Saving...';
    
    try {
        const payload = new URLSearchParams(new FormData(e.target));
        const res = await fetch('api/add_book.php', { method: 'POST', body: payload });
        const json = await res.json();
        
        if(json.success) {
            showToast(json.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(json.message, 'error');
        }
    } catch (err) {
        showToast("Network Error", "error");
    } finally {
        btn.disabled = false;
        btn.innerHTML = '📖 Add to Catalog';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
