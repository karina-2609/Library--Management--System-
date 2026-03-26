<?php
require_once __DIR__ . '/db.php';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="background:var(--primary-light);text-align:center;">
    <div class="breadcrumb" style="justify-content:center;"><a href="index.php">Home</a><span>›</span><span>Contact</span></div>
    <h1>✉️ Contact EduLib Help Desk</h1>
    <p>We are here to assist with catalog queries, fine disputes, and general library support.</p>
</div>

<section class="section" style="max-width:900px;margin:0 auto;">
    <div class="dashboard-grid" style="grid-template-columns:1fr 1.5fr;">
        
        <!-- Contact Details Column -->
        <div class="card animate-in">
            <h3 class="mb-3">📍 General Information</h3>
            <p style="color:var(--text-secondary);font-size:0.95rem;margin-bottom:2rem;">Our librarians and support staff are available during standard academic hours.</p>

            <ul class="footer-contact" style="margin-bottom:2rem;">
                <li style="display:flex;gap:0.75rem;"><span style="font-size:1.2rem;">🏢</span> <div><strong>Main Campus</strong><br>Global College, Block A<br>University Ave, CA 90210</div></li>
                <li style="display:flex;gap:0.75rem;"><span style="font-size:1.2rem;">📞</span> <div><strong>Phone Hotline</strong><br>+1 (555) 010-2030</div></li>
                <li style="display:flex;gap:0.75rem;"><span style="font-size:1.2rem;">✉️</span> <div><strong>Support Email</strong><br>library@globalcollege.edu</div></li>
                <li style="display:flex;gap:0.75rem;"><span style="font-size:1.2rem;">🕒</span> <div><strong>Operating Hours</strong><br>Mon-Fri: 8:00 AM - 10:00 PM<br>Weekends: 10:00 AM - 6:00 PM</div></li>
            </ul>
        </div>

        <!-- Contact Form Column -->
        <div class="card animate-in delay-1">
            <h3 class="mb-3">📝 Send a Secure Message</h3>
            
            <form id="contactForm" onsubmit="handleContactSubmit(event)">
                <div class="form-group mb-3">
                    <label for="cName">Your Name *</label>
                    <input type="text" id="cName" name="name" required placeholder="John Doe">
                    <div class="form-error" id="errCName"></div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="cEmail">Email Address *</label>
                    <input type="email" id="cEmail" name="email" required placeholder="john@example.com">
                    <div class="form-error" id="errCEmail"></div>
                </div>

                <div class="form-group mb-3">
                    <label for="cSubject">Subject</label>
                    <input type="text" id="cSubject" name="subject" placeholder="What is this regarding?">
                </div>

                <div class="form-group mb-4">
                    <label for="cMsg">Message Details *</label>
                    <textarea id="cMsg" name="message" rows="5" required placeholder="Describe your query or issue..."></textarea>
                    <div class="form-error" id="errCMsg"></div>
                </div>

                <button type="submit" id="btnSubmitContact" class="btn btn-primary btn-full" style="font-size:1.05rem;padding:0.75rem;">📤 Send Message to Library Desk</button>
            </form>
        </div>

    </div>
</section>

<script>
async function handleContactSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitContact');
    btn.disabled = true;
    btn.innerText = 'Sending...';
    
    // Basic validation
    const nameEl  = document.getElementById('cName');
    const emailEl = document.getElementById('cEmail');
    const msgEl   = document.getElementById('cMsg');
    clearErrors([nameEl, emailEl, msgEl]);

    if (!nameEl.value.trim() || !emailEl.value.trim() || !msgEl.value.trim()) {
        showToast("Please fill in all required fields.", "error");
        btn.disabled = false;
        btn.innerText = '📤 Send Message to Library Desk';
        return;
    }

    try {
        const payload = new URLSearchParams(new FormData(e.target));
        const res = await fetch('api/contact.php', { method: 'POST', body: payload });
        const json = await res.json();
        
        if (json.success) {
            showToast(json.message, 'success');
            e.target.reset(); // clear the form
        } else {
            showToast(json.message, 'error');
        }
    } catch (err) {
        showToast("Error sending message. Try again later.", "error");
    } finally {
        btn.disabled = false;
        btn.innerText = '📤 Send Message to Library Desk';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
