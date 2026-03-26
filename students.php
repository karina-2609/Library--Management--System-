<?php
require_once __DIR__ . '/db.php';
include __DIR__ . '/includes/header.php';

// Fetch all students
$students = $conn->query("SELECT * FROM students ORDER BY joined_on DESC");
?>

<div class="page-header">
    <div class="breadcrumb"><a href="index.php">Home</a><span>›</span><span>Students</span></div>
    <h1>🎓 Registered Students</h1>
    <p>View the directory of registered library members and register new students.</p>
</div>

<section class="section">
    <div class="dashboard-grid">
        
        <!-- Registration Column -->
        <div class="card animate-in">
            <h3 class="mb-3">➕ Register New Student</h3>
            <p style="color:var(--text-secondary);font-size:0.95rem;margin-bottom:1.5rem;">Add a new student to the library system so they can begin borrowing books.</p>
            
            <form id="addStudentForm" onsubmit="handleAddStudent(event)">
                <div class="form-group mb-3">
                    <label for="sId">Student ID Roll Number *</label>
                    <input type="text" id="sId" name="student_id" placeholder="e.g. STU1025" required>
                    <div class="form-error" id="errSId"></div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="sName">Full Name *</label>
                    <input type="text" id="sName" name="name" placeholder="Alice Johnson" required>
                    <div class="form-error" id="errSName"></div>
                </div>

                <div class="form-group mb-3">
                    <label for="sEmail">Email Address</label>
                    <input type="email" id="sEmail" name="email" placeholder="alice@example.com">
                </div>
                
                <div class="form-row mb-4">
                    <div class="form-group mb-0">
                        <label for="sPhone">Phone</label>
                        <input type="tel" id="sPhone" name="phone" placeholder="555-0100">
                    </div>
                    <div class="form-group mb-0">
                        <label for="sCourse">Course / Major</label>
                        <input type="text" id="sCourse" name="course" placeholder="Computer Science">
                    </div>
                </div>

                <button type="submit" id="btnSubmitStudent" class="btn btn-primary btn-full" style="font-size:1.05rem;padding:0.75rem;">📋 Register Student</button>
            </form>
        </div>

        <!-- Student Directory Column -->
        <div class="card animate-in delay-1">
            <div class="flex-between mb-3">
                <h3 style="margin:0;">📋 Student Directory</h3>
                <span class="badge badge-blue"><?= $students->num_rows ?> Total</span>
            </div>
            
            <?php if ($students && $students->num_rows > 0): ?>
                <div class="table-container" style="max-height: 500px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($s = $students->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight:600;font-size:0.85rem;"><?= h($s['student_id']) ?></td>
                                    <td>
                                        <div style="font-weight:600;font-size:0.9rem;"><?= h($s['name']) ?></div>
                                        <div style="font-size:0.75rem;color:var(--text-muted);"><?= h($s['email']) ?></div>
                                    </td>
                                    <td style="font-size:0.85rem;color:var(--text-secondary);"><?= h($s['course'] ?: 'N/A') ?></td>
                                    <td>
                                        <a href="student.php?id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">View Profile</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding:2rem;">
                    <div class="empty-icon">🎓</div>
                    <p>No students registered yet!</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<script>
async function handleAddStudent(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitStudent');
    btn.disabled = true;
    btn.innerText = 'Registering...';
    
    const idEl   = document.getElementById('sId');
    const nameEl = document.getElementById('sName');
    clearErrors([idEl, nameEl]);

    if (!idEl.value.trim() || !nameEl.value.trim()) {
        showToast("Student ID and Name are required.", "error");
        btn.disabled = false;
        btn.innerText = '📋 Register Student';
        return;
    }

    try {
        const payload = new URLSearchParams(new FormData(e.target));
        const res = await fetch('api/add_student.php', { method: 'POST', body: payload });
        const json = await res.json();
        
        if (json.success) {
            showToast(json.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(json.message, 'error');
            if (json.message.includes('already exists')) {
                showError(idEl, "This ID is already registered.");
            }
        }
    } catch (err) {
        showToast("Error processing registration.", "error");
    } finally {
        btn.disabled = false;
        btn.innerText = '📋 Register Student';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
