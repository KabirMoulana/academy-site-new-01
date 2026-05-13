// ─── SHARED LAYOUT BUILDER ───
// Call buildLayout(navItems, pageTitle) from each dashboard page

function getUserInfo() {
  return {
    name:  sessionStorage.getItem('name')  || 'Demo User',
    email: sessionStorage.getItem('email') || 'demo@educore.lk',
    role:  sessionStorage.getItem('role')  || 'user'
  };
}

function roleLabel(role) {
  const map = {
    admin:'Administrator', manager:'Manager', director:'Director',
    lecturer:'Lecturer', student:'Student', parent:'Parent', receptionist:'Receptionist'
  };
  return map[role] || role;
}

function buildLayout(navSections, pageTitle) {
  const u = getUserInfo();
  const initials = u.name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);

  const sidebarHTML = `
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-mark">✦ EduCore</div>
      <div class="logo-sub">Academy System</div>
    </div>
    <div class="sidebar-user">
      <div class="user-avatar">${initials}</div>
      <div>
        <div class="user-name">${u.name}</div>
        <div class="user-role">${roleLabel(u.role)}</div>
      </div>
    </div>
    <nav class="sidebar-nav">
      ${navSections.map(section => `
        <div class="nav-section-label">${section.label}</div>
        ${section.items.map(item => `
          <a href="${item.href || '#'}" class="nav-item${item.active?' active':''}" onclick="setActive(this)">
            <span class="nav-icon">${item.icon}</span>
            ${item.name}
          </a>
        `).join('')}
      `).join('')}
    </nav>
    <div class="sidebar-footer">
      <button class="btn-logout" onclick="doLogout()">
        <span>⬡</span> Sign Out
      </button>
    </div>
  </aside>`;

  const topbarHTML = `
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:16px;">
      <button class="topbar-btn" id="menuToggle" onclick="toggleSidebar()" style="display:none">☰</button>
      <span class="topbar-title">${pageTitle}</span>
    </div>
    <div class="topbar-actions">
      <button class="topbar-btn" title="Notifications" onclick="openModal('notifModal')">
        🔔<span class="notif-badge"></span>
      </button>
      <button class="topbar-btn" title="Profile" onclick="window.location.href='profile.html'">👤</button>
    </div>
  </div>`;

  // Insert sidebar before .main-content
  const shell = document.getElementById('appShell');
  shell.insertAdjacentHTML('afterbegin', sidebarHTML);
  document.getElementById('topbarMount').innerHTML = topbarHTML;

  // Notification modal
  document.body.insertAdjacentHTML('beforeend', `
  <div class="modal-overlay" id="notifModal">
    <div class="modal" style="max-width:420px;">
      <div class="modal-header">
        <h2 class="modal-title">Notifications</h2>
        <button class="modal-close" onclick="closeModal('notifModal')">✕</button>
      </div>
      <div class="table-wrapper" style="border-radius:12px;">
        <div class="notif-item unread">
          <div class="notif-dot"></div>
          <div><div class="notif-text">New batch schedule published for June 2025.</div><div class="notif-time">2 mins ago</div></div>
        </div>
        <div class="notif-item unread">
          <div class="notif-dot"></div>
          <div><div class="notif-text">Exam results for Mathematics uploaded.</div><div class="notif-time">1 hour ago</div></div>
        </div>
        <div class="notif-item">
          <div class="notif-dot read"></div>
          <div><div class="notif-text">Payment approved for student ID #1042.</div><div class="notif-time">Yesterday</div></div>
        </div>
        <div class="notif-item">
          <div class="notif-dot read"></div>
          <div><div class="notif-text">New feedback submitted by Arun Silva.</div><div class="notif-time">2 days ago</div></div>
        </div>
      </div>
      <div style="padding:14px 20px;border-top:1px solid rgba(201,168,76,0.1);text-align:right;">
        <button class="btn btn-outline btn-sm" onclick="closeModal('notifModal')">Mark all read</button>
      </div>
    </div>
  </div>`);
}

function setActive(el) {
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  el.classList.add('active');
}

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}

function doLogout() {
  sessionStorage.clear();
  window.location.href = 'login.html';
}

// Show menu toggle on mobile
window.addEventListener('DOMContentLoaded', () => {
  if (window.innerWidth <= 768) {
    const t = document.getElementById('menuToggle');
    if (t) t.style.display = 'flex';
  }
});
