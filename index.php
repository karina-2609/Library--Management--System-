<?php
require_once __DIR__ . '/db.php';
include __DIR__ . '/includes/header.php';

// Fetch Statistics
$totalBooks    = $conn->query("SELECT COUNT(*) AS c FROM books")->fetch_assoc()['c'] ?? 0;
$availBooks    = $conn->query("SELECT SUM(available) AS c FROM books")->fetch_assoc()['c'] ?? 0;
$totalStudents = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'] ?? 0;
$issuedBooks   = $conn->query("SELECT COUNT(*) AS c FROM issued_books WHERE status='issued'")->fetch_assoc()['c'] ?? 0;

// Fetch active announcements
$announcements = $conn->query("SELECT * FROM announcements WHERE active=1 ORDER BY posted_on DESC LIMIT 3");

// Fetch newly added featured books
$featuredBooks = $conn->query("SELECT * FROM books ORDER BY added_on DESC LIMIT 4");

$catEmojis = [
    'Computer Science' => '💻', 'Fiction' => '📖', 'Mathematics' => '📐',
    'Science' => '🔬', 'History' => '🏛️', 'Self-Help' => '🌟', 'Philosophy' => '🧠', 'General' => '📚'
];
?>

<!-- ── HERO SECTION ── -->
<section class="hero">
    <div class="hero-badge">🎓 Advance Your Studies Today</div>
    <h1 class="animate-in">Welcome to <br><span>EduLib Global Library</span></h1>
    <p class="animate-in delay-1">Explore our advanced catalog, easily manage your borrowing, and access thousands of resources through our state-of-the-art portal.</p>
    <div class="hero-cta animate-in delay-2">
        <a href="books.php" class="btn btn-primary">📖 Explore Catalog</a>
        <a href="student.php" class="btn btn-outline">🎓 Student Login</a>
    </div>
</section>

<!-- ── STATS SECTION ── -->
<section class="stats-section">
    <div class="stats-grid">
        <div class="stat-card animate-in delay-1">
            <div class="stat-icon blue">📚</div>
            <div class="stat-content">
                <div class="stat-value" data-counter="<?= h($totalBooks) ?>">0</div>
                <div class="stat-label">Total Books Cataloged</div>
            </div>
        </div>
        <div class="stat-card animate-in delay-2">
            <div class="stat-icon green">✅</div>
            <div class="stat-content">
                <div class="stat-value" data-counter="<?= h($availBooks) ?>">0</div>
                <div class="stat-label">Copies Available</div>
            </div>
        </div>
        <div class="stat-card animate-in delay-3">
            <div class="stat-icon amber">📤</div>
            <div class="stat-content">
                <div class="stat-value" data-counter="<?= h($issuedBooks) ?>">0</div>
                <div class="stat-label">Actively Borrowed</div>
            </div>
        </div>
        <div class="stat-card animate-in delay-4">
            <div class="stat-icon cyan">🎓</div>
            <div class="stat-content">
                <div class="stat-value" data-counter="<?= h($totalStudents) ?>">0</div>
                <div class="stat-label">Registered Members</div>
            </div>
        </div>
    </div>
</section>

<!-- ── DASHBOARD GRID: NOTICES & QUICK LINkS ── -->
<section class="section notices-section" id="announcements">
    <div class="dashboard-grid">
        
        <!-- Announcements Column -->
        <div class="card animate-in">
            <h3 class="mb-3">🔔 Official Announcements</h3>
            <?php if ($announcements && $announcements->num_rows > 0): ?>
                <?php while ($notice = $announcements->fetch_assoc()): ?>
                    <div class="notice-card">
                        <h4><?= h($notice['title']) ?></h4>
                        <p><?= h($notice['content']) ?></p>
                        <small style="color:var(--text-muted);display:block;margin-top:0.5rem;font-size:0.75rem;">
                            Posted: <?= formatDate($notice['posted_on']) ?>
                        </small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No active announcements to display right now.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Links Column -->
        <div class="card animate-in delay-1">
            <h3 class="mb-3">⚡ Quick Portal Access</h3>
            <p style="color:var(--text-secondary);margin-bottom:1.5rem;font-size:0.95rem;">Select your dashboard to manage library interactions quickly.</p>
            
            <div style="display:flex;flex-direction:column;gap:1rem;">
                <!-- Link 1 -->
                <a href="books.php" style="display:flex;align-items:center;padding:1.25rem;border:1px solid var(--border);border-radius:var(--radius-md);transition:0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                    <div class="stat-icon blue" style="margin-right:1rem;">📖</div>
                    <div>
                        <h4 style="margin:0;color:var(--text-primary);">Search Catalog</h4>
                        <p style="margin:0;font-size:0.85rem;color:var(--text-muted);">Find books by Edition, Publisher, or Topic.</p>
                    </div>
                </a>
                
                <!-- Link 2 -->
                <a href="return.php" style="display:flex;align-items:center;padding:1.25rem;border:1px solid var(--border);border-radius:var(--radius-md);transition:0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                    <div class="stat-icon amber" style="margin-right:1rem;">↩️</div>
                    <div>
                        <h4 style="margin:0;color:var(--text-primary);">Return & Fines</h4>
                        <p style="margin:0;font-size:0.85rem;color:var(--text-muted);">Check in late materials and clear your fines securely.</p>
                    </div>
                </a>

                <!-- Link 3 -->
                <a href="student.php" style="display:flex;align-items:center;padding:1.25rem;border:1px solid var(--border);border-radius:var(--radius-md);transition:0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                    <div class="stat-icon green" style="margin-right:1rem;">🎓</div>
                    <div>
                        <h4 style="margin:0;color:var(--text-primary);">Student Profile</h4>
                        <p style="margin:0;font-size:0.85rem;color:var(--text-muted);">View current borrows, upcoming due dates, and update profile.</p>
                    </div>
                </a>
            </div>
        </div>

    </div>
</section>

<!-- ── FEATURED ACQUISITIONS ── -->
<section class="section mb-4">
    <div class="text-center mb-4">
        <h2>✨ New Arrivals</h2>
        <p style="color:var(--text-secondary);">Browse the latest catalog additions to our library shelves.</p>
    </div>
    
    <div class="books-grid">
        <?php if ($featuredBooks && $featuredBooks->num_rows > 0): ?>
            <?php while ($b = $featuredBooks->fetch_assoc()): 
                $avail = (int)$b['available'] > 0;
                $emoji = $catEmojis[$b['category']] ?? '📚';
            ?>
                <div class="book-card animate-in">
                    <div class="book-cover">
                        <?= $emoji ?>
                        <span class="category-badge"><?= h($b['category']) ?></span>
                    </div>
                    <div class="book-body">
                        <div class="book-title"><?= h($b['title']) ?></div>
                        <div class="book-author">by <?= h($b['author']) ?></div>
                        
                        <div class="book-meta" style="margin-bottom:0.75rem;">
                            <span>Ed: <strong><?= h($b['edition']) ?></strong></span>
                            <span>Pub: <strong><?= h($b['publisher']) ?></strong></span>
                        </div>
                        
                        <span class="availability-badge <?= $avail ? 'available-yes' : 'available-no' ?>" style="margin-bottom:1rem;display:inline-block;">
                            <?= $avail ? "Available ({$b['available']})" : "Currently Unavailable" ?>
                        </span>
                        
                        <div class="book-footer text-center">
                            <a href="books.php" class="btn btn-outline btn-sm btn-full">View in Catalog</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <p>No featured books available. Run the setup SQL.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
