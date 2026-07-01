<?php
// footer.php — bottom bar component
?>
<footer class="site-footer">
    <span>&copy; <?= date('Y') ?> Cabin Putih F&amp;B Sdn Bhd — All rights reserved.</span>
    <span style="color:var(--border-light);">Powered by Oracle APEX &amp; ORDS</span>
</footer>
<div class="toast-container" id="toastContainer"></div>
<script>
function showToast(msg, type = 'info', duration = 3000) {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(20px)'; t.style.transition = '0.3s'; setTimeout(() => t.remove(), 320); }, duration);
}
</script>
