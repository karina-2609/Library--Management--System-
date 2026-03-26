<?php
require_once __DIR__ . '/db.php';
include __DIR__ . '/includes/header.php';

$catEmojis = [
    'Computer Science' => '💻', 'Fiction' => '📖', 'Mathematics' => '📐',
    'Science' => '🔬', 'History' => '🏛️', 'Self-Help' => '🌟', 'Philosophy' => '🧠', 'General' => '📚'
];

$books      = $conn->query("SELECT * FROM books ORDER BY title ASC");
$categories = $conn->query("SELECT DISTINCT category FROM books ORDER BY category ASC");
?>

<div class="page-header">
    <div class="breadcrumb"><a href="index.php">Home</a><span>›</span><span>Books Catalog</span></div>
    <h1>📖 Central Book Catalog</h1>
    <p>Find, review editions and publishers, and issue books directly to your student ID.</p>
</div>

<section class="section">
    <!-- Search & Filter Bar -->
    <div class="search-bar" style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1rem;display:flex;gap:1rem;margin-bottom:2rem;flex-wrap:wrap;">
        <div class="search-input-wrap" style="flex:1;min-width:250px;position:relative;display:flex;align-items:center;">
            <span class="search-icon" style="position:absolute;left:1rem;color:var(--text-muted);">🔍</span>
            <input type="text" id="bookSearch" placeholder="Search by title, author, publisher, or edition…" style="padding-left:2.5rem;width:100%;border-radius:var(--radius-md);">
        </div>
        
        <select class="filter-select" id="categoryFilter" style="width:200px;border-radius:var(--radius-md);">
            <option value="">All Topics</option>
            <?php if ($categories && $categories->num_rows > 0): ?>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= strtolower(h($cat['category'])) ?>"><?= h($cat['category']) ?></option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>
        
        <select class="filter-select" id="availFilter" style="width:180px;border-radius:var(--radius-md);">
            <option value="">All Availability</option>
            <option value="1">Available to Issue</option>
            <option value="0">Currently Borrowed</option>
        </select>
    </div>

    <!-- Books Grid -->
    <div class="books-grid" id="booksGrid">
        <?php if ($books && $books->num_rows > 0): ?>
            <?php while ($b = $books->fetch_assoc()): 
                $avail = (int)$b['available'] > 0;
                $emoji = $catEmojis[$b['category']] ?? '📚';
            ?>
            <div class="book-card animate-in"
                 data-title="<?= strtolower(h($b['title'])) ?>"
                 data-author="<?= strtolower(h($b['author'])) ?>"
                 data-publisher="<?= strtolower(h($b['publisher'])) ?>"
                 data-edition="<?= strtolower(h($b['edition'])) ?>"
                 data-category="<?= strtolower(h($b['category'])) ?>"
                 data-available="<?= $avail ? '1' : '0' ?>">
                
                <div class="book-cover">
                    <?= $emoji ?>
                    <span class="category-badge"><?= h($b['category']) ?></span>
                </div>
                
                <div class="book-body">
                    <div class="book-title"><?= h($b['title']) ?></div>
                    <div class="book-author">By: <?= h($b['author']) ?></div>
                    
                    <div class="book-meta">
                        <span>Edition: <strong><?= h($b['edition']) ?></strong></span>
                        <span>Publisher: <strong><?= h($b['publisher']) ?></strong></span>
                    </div>

                    <?php if (!empty($b['isbn'])): ?>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:1rem;border-top:1px dashed var(--border);padding-top:0.5rem;">ISBN: <?= h($b['isbn']) ?></div>
                    <?php endif; ?>
                    
                    <span class="availability-badge <?= $avail ? 'available-yes' : 'available-no' ?>" style="margin-bottom:1.5rem;display:inline-block;">
                        <?= $avail ? "Status: Available ({$b['available']}/{$b['total_copies']})" : 'Status: Waitlisted' ?>
                    </span>
                    
                    <div class="book-footer">
                        <?php if ($avail): ?>
                            <button class="btn btn-primary btn-sm btn-full" onclick="openIssueModal(<?= (int)$b['id'] ?>, '<?= addslashes(h($b['title'])) ?>')">📤 Issue Book</button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm btn-full" disabled>⏳ Unavailable</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📚</div>
                <h4>Catalog Empty</h4>
                <p>Run the SQL file to populate the database.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ISSUE BOOK MODAL -->
<div class="modal-overlay" id="issueModal">
    <div class="modal">
        <div class="modal-header">
            <h3>📤 Secure Book Issuance</h3>
            <button class="modal-close" onclick="closeModal('issueModal')" aria-label="Close">✕</button>
        </div>
        <div class="modal-body">
            <div style="background:var(--primary-light);border:1px solid rgba(79,70,229,0.2);border-radius:8px;padding:0.75rem;margin-bottom:1.5rem;color:var(--primary);font-weight:500;">
                Selected Text: <strong id="issueBookTitle" style="color:var(--text-primary);"></strong>
            </div>

            <form id="issueForm" novalidate>
                <input type="hidden" id="issueBookId" name="book_id">
                
                <div class="form-group">
                    <label for="issueStudentId">Student ID Card Number *</label>
                    <input type="text" id="issueStudentId" name="student_id" placeholder="e.g. STU001" required>
                    <div class="form-error" id="errStudentId"></div>
                    <small style="color:var(--text-muted);">Must be a registered student in the system.</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="issueDate">Date of Issue (Today)</label>
                        <input type="date" id="issueDate" name="issue_date" value="<?= date('Y-m-d') ?>" readonly style="background:var(--bg);cursor:not-allowed;">
                    </div>
                    <div class="form-group">
                        <label for="issueDueDate">Return Due Date</label>
                        <input type="date" id="issueDueDate" name="due_date" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" readonly style="background:var(--bg);cursor:not-allowed;">
                        <small style="color:var(--text-muted);">Standard 14-day lending period.</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('issueModal')">Cancel</button>
            <button class="btn btn-primary" id="btnSubmitIssue" type="submit" form="issueForm">Confirm Issuance</button>
        </div>
    </div>
</div>

<script>
// Filter & Search Logic
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('bookSearch');
    const catFilter   = document.getElementById('categoryFilter');
    const availFilter = document.getElementById('availFilter');
    const books       = document.querySelectorAll('.book-card');

    function filterBooks() {
        const query = searchInput.value.toLowerCase();
        const cat   = catFilter.value;
        const avail = availFilter.value;

        books.forEach(b => {
            const title = b.getAttribute('data-title');
            const auth  = b.getAttribute('data-author');
            const pub   = b.getAttribute('data-publisher');
            const ed    = b.getAttribute('data-edition');
            const bCat  = b.getAttribute('data-category');
            const bAvail= b.getAttribute('data-available');

            const matchSearch = String(title+auth+pub+ed).includes(query);
            const matchCat    = cat === "" || bCat === cat;
            const matchAvail  = avail === "" || bAvail === avail;

            if (matchSearch && matchCat && matchAvail) {
                b.style.display = 'flex';
                // Trigger reflow for animation if needed
                b.classList.remove('animate-in', 'visible');
                void b.offsetWidth;
                b.classList.add('visible');
            } else {
                b.style.display = 'none';
            }
        });
    }

    searchInput?.addEventListener('input', filterBooks);
    catFilter?.addEventListener('change', filterBooks);
    availFilter?.addEventListener('change', filterBooks);
});

// Issue Modal Logic
function openIssueModal(bookId, title) {
    document.getElementById('issueBookId').value = bookId;
    document.getElementById('issueBookTitle').innerText = title;
    document.getElementById('issueStudentId').value = '';
    clearErrors([document.getElementById('issueStudentId')]);
    openModal('issueModal');
}

document.getElementById('issueForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitIssue');
    const stuEl = document.getElementById('issueStudentId');
    const bId = document.getElementById('issueBookId').value;
    
    clearErrors([stuEl]);
    if (!stuEl.value.trim()) {
        showError(stuEl, "Student ID is required.");
        return;
    }

    btn.disabled = true;
    btn.innerHTML = 'Processing...';

    try {
        const payload = new URLSearchParams(new FormData(e.target));
        const res = await fetch('api/issue.php', { method: 'POST', body: payload });
        const json = await res.json();
        
        if (json.success) {
            showToast(json.message, 'success');
            closeModal('issueModal');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showError(stuEl, json.message);
        }
    } catch (err) {
        showToast("Network error. Try again.", 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Confirm Issuance';
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
