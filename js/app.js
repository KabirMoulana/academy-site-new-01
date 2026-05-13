// ─── SIDEBAR NAVIGATION ───
function initSidebar() {
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    item.addEventListener('click', function () {
      navItems.forEach(n => n.classList.remove('active'));
      this.classList.add('active');
    });
  });
}

// ─── TABS ───
function initTabs() {
  document.querySelectorAll('.tabs').forEach(tabGroup => {
    const buttons = tabGroup.querySelectorAll('.tab-btn');
    buttons.forEach(btn => {
      btn.addEventListener('click', function () {
        const target = this.dataset.tab;
        buttons.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const parent = this.closest('.tab-section') || document;
        parent.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        const targetEl = parent.querySelector(`#${target}`);
        if (targetEl) targetEl.classList.add('active');
      });
    });
  });
}

// ─── MODALS ───
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}
function initModals() {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
      if (e.target === this) this.classList.remove('open');
    });
  });
}

// ─── TOAST ───
function showToast(msg, type = 'success') {
  const existing = document.querySelector('.toast');
  if (existing) existing.remove();
  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.style.cssText = `
    position:fixed; bottom:28px; right:28px; z-index:999;
    background:${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--gold)'};
    color:${type === 'gold' ? 'var(--navy)' : '#fff'};
    padding:12px 22px; border-radius:10px;
    font-family:'DM Sans',sans-serif; font-size:13.5px; font-weight:500;
    box-shadow:0 6px 24px rgba(0,0,0,0.3);
    animation:slideUp 0.3s ease;
  `;
  toast.textContent = msg;
  const style = document.createElement('style');
  style.textContent = `@keyframes slideUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}`;
  document.head.appendChild(style);
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

// ─── TABLE SEARCH ───
function filterTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  input.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ─── CONFIRM DELETE ───
function confirmDelete(itemName, callback) {
  if (confirm(`Delete "${itemName}"? This cannot be undone.`)) callback();
}

// ─── INIT ───
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initTabs();
  initModals();
});
