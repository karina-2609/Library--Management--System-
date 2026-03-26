<?php
require_once __DIR__ . '/db.php';
include __DIR__ . '/includes/header.php';

// Mock Student Dashboard - In a real app this would use $_SESSION['student_id']
$studentIdToView = $_GET['id'] ?? '1';

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ? OR student_id = ?");
$stmt->bind_param('is', $studentIdToView, $studentIdToView);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    echo "<div class='page-wrapper'><div class='empty-state'><h4>Student Not Found</h4><p>Ensure the ID is valid.</p></div></div>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

$stuDbId = $student['id'];

// Get Issued Books
$issued = $conn->query("
    SELECT ib.id, b.title, ib.issue_date, ib.due_date, ib.status 
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    WHERE ib.student_id = $stuDbId AND ib.status = 'issued'
    ORDER BY ib.due_date ASC
");

// Get Fines
$fines = $conn->query("
    SELECT f.*, b.title 
    FROM fines f
    JOIN issued_books ib ON f.issue_id = ib.id
    JOIN books b ON ib.book_id = b.id
    WHERE f.student_id = $stuDbId
    ORDER BY f.created_at DESC
");
?>

<div class="page-header">
    <div class="breadcrumb"><a href="index.php">Home</a><span>›</span><span>Student Portal</span></div>
    <h1>🎓 Student Dashboard</h1>
    <p>Manage your profile, view current borrows, and check fine statuses.</p>
</div>

<section class="section">
    <div class="dashboard-grid">
        
        <!-- PROFILE COLUMN -->
        <div class="card profile-card animate-in">
            <div class="avatar"><?= strtoupper(substr($student['name'], 0, 1)) ?></div>
            <h2 style="margin-bottom:0.25rem;"><?= h($student['name']) ?></h2>
            <p style="color:var(--text-muted);font-weight:600;margin-bottom:1.5rem;">ID: <?= h($student['student_id']) ?></p>

            <div class="profile-details text-left">
                <p><strong>Course:</strong> <?= h($student['course'] ?: 'Not specified') ?></p>
                <p><strong>Email:</strong> <?= h($student['email'] ?: 'Not specified') ?></p>
                <p><strong>Phone:</strong> <?= h($student['phone'] ?: 'Not specified') ?></p>
                <p><strong>Joined:</strong> <?= formatDate($student['joined_on']) ?></p>
            </div>
            
            <button class="btn btn-primary btn-full mt-3" onclick="openModal('editProfileModal')">✏️ Edit Profile</button>
        </div>

        <!-- ACTIVITY COLUMN -->
        <div class="activity-col">
            
            <!-- CURRENT BORROWS -->
            <div class="card animate-in delay-1 mb-4">
                <div class="flex-between mb-3">
                    <h3 style="margin:0;">📚 Currently Borrowed</h3>
                    <span class="badge badge-blue"><?= $issued->num_rows ?> Books</span>
                </div>
                
                <?php if ($issued && $issued->num_rows > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr><th>Book</th><th>Issued</th><th>Due Date</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($ib = $issued->fetch_assoc()): 
                                    $is_late = strtotime($ib['due_date']) < strtotime('today');
                                ?>
                                    <tr>
                                        <td style="font-weight:600;font-size:0.9rem;"><?= h($ib['title']) ?></td>
                                        <td style="font-size:0.85rem;color:var(--text-muted);"><?= formatDate($ib['issue_date']) ?></td>
                                        <td style="font-size:0.85rem;"><?= formatDate($ib['due_date']) ?></td>
                                        <td>
                                            <span class="badge <?= $is_late ? 'badge-red' : 'badge-green' ?>">
                                                <?= $is_late ? 'Overdue' : 'Active' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted);font-size:0.95rem;">You have no active borrows at this time.</p>
                <?php endif; ?>
            </div>

            <!-- FINE HISTORY -->
            <div class="card animate-in delay-2">
                <div class="flex-between mb-3">
                    <h3 style="margin:0;">💸 Fines & Penalties</h3>
                    <span class="badge badge-amber"><?= $fines->num_rows ?> Records</span>
                </div>

                <?php if ($fines && $fines->num_rows > 0): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr><th>Book</th><th>Days Late</th><th>Amount</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($f = $fines->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-size:0.85rem;"><?= h($f['title']) ?></td>
                                        <td style="font-size:0.85rem;"><?= h($f['days_late']) ?> days</td>
                                        <td style="font-weight:600;">$<?= number_format($f['fine_amount'], 2) ?></td>
                                        <td>
                                            <?php if ($f['status'] === 'paid'): ?>
                                                <span class="badge badge-green">Paid</span>
                                            <?php else: ?>
                                                <a href="fine.php?fine_id=<?= $f['id'] ?>" class="badge badge-red" style="text-decoration:none;">Unpaid (Pay Now)</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color:var(--text-muted);font-size:0.95rem;">No fine history. Great job returning books on time!</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<!-- EDIT PROFILE MODAL -->
<div class="modal-overlay" id="editProfileModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Update Student Profile</h3>
            <button class="modal-close" onclick="closeModal('editProfileModal')">✕</button>
        </div>
        <div class="modal-body">
            <form id="editProfileForm" onsubmit="handleProfileUpdate(event)">
                <input type="hidden" name="id" value="<?= $stuDbId ?>">
                
                <div class="form-group">
                    <label for="pName">Full Name *</label>
                    <input type="text" id="pName" name="name" value="<?= h($student['name']) ?>" required>
                    <div class="form-error" id="errPName"></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="pEmail">Email Address</label>
                        <input type="email" id="pEmail" name="email" value="<?= h($student['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="pPhone">Phone Number</label>
                        <input type="tel" id="pPhone" name="phone" value="<?= h($student['phone']) ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="pCourse">Course / Major</label>
                    <input type="text" id="pCourse" name="course" value="<?= h($student['course']) ?>">
                </div>
                
                <div class="modal-footer" style="padding:1rem 0 0 0;margin-top:1rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editProfileModal')">Cancel</button>
                    <button type="submit" id="btnUpdateProfile" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function handleProfileUpdate(e) {
    e.preventDefault();
    const btn = document.getElementById('btnUpdateProfile');
    btn.disabled = true;
    btn.innerText = 'Saving...';

    const pName = document.getElementById('pName');
    clearErrors([pName]);
    
    if(!pName.value.trim()){
        showError(pName, 'Name is required');
        btn.disabled = false;
        btn.innerText = 'Save Changes';
        return;
    }

    try {
        const payload = new URLSearchParams(new FormData(e.target));
        const res = await fetch('api/update_student.php', { method: 'POST', body: payload });
        const json = await res.json();
        
        if (json.success) {
            showToast(json.message, 'success');
            closeModal('editProfileModal');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(json.message, 'error');
        }
    } catch (err) {
        showToast("Network Error", "error");
    } finally {
        btn.disabled = false;
        btn.innerText = 'Save Changes';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
