// ─── API BASE ─────────────────────────────────────────────
const BASE = '../api';

async function api(endpoint, action, method = 'GET', body = null, params = {}) {
  const url = new URL(`${BASE}/${endpoint}.php`, window.location.href);
  url.searchParams.set('action', action);
  Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));

  const opts = {
    method,
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' }
  };
  if (body && method !== 'GET') opts.body = JSON.stringify(body);

  try {
    const res  = await fetch(url, opts);
    const json = await res.json();
    if (!json.success) throw new Error(json.message || 'API error');
    return json.data ?? json;
  } catch (err) {
    showToast(err.message, 'error');
    throw err;
  }
}

// ─── SHORTHAND HELPERS ────────────────────────────────────
const API = {
  // Auth
  login:         (email, password) => api('auth', 'login', 'POST', { email, password }),
  logout:        ()                => api('auth', 'logout', 'POST'),
  me:            ()                => api('auth', 'me'),
  updateProfile: (name, email)     => api('auth', 'update_profile', 'PUT', { name, email }),
  resetPassword: (current, np)     => api('auth', 'reset_password', 'PUT', { current_password: current, new_password: np }),

  // Users
  listUsers:   (role='') => api('users','list','GET',null,role?{role}:{}),
  createUser:  (data)    => api('users','create','POST',data),
  updateUser:  (id,data) => api('users','update','PUT',data,{id}),
  deleteUser:  (id)      => api('users','delete','DELETE',null,{id}),

  // Subjects
  listSubjects:  ()       => api('subjects','list'),
  createSubject: (d)      => api('subjects','create','POST',d),
  updateSubject: (id,d)   => api('subjects','update','PUT',d,{id}),
  deleteSubject: (id)     => api('subjects','delete','DELETE',null,{id}),

  // Batches
  listBatches:  ()       => api('batches','list'),
  createBatch:  (d)      => api('batches','create','POST',d),
  updateBatch:  (id,d)   => api('batches','update','PUT',d,{id}),
  deleteBatch:  (id)     => api('batches','delete','DELETE',null,{id}),

  // Enrollments
  listEnrollments: ()     => api('enrollments','list'),
  enroll:          (d)    => api('enrollments','enroll','POST',d),
  unenroll:        (id)   => api('enrollments','unenroll','PUT',null,{id}),
  reenroll:        (id)   => api('enrollments','reenroll','PUT',null,{id}),

  // Sessions
  listSessions:     (p={}) => api('sessions','list','GET',null,p),
  createSession:    (d)    => api('sessions','create','POST',d),
  uploadLink:       (id,d) => api('sessions','upload_link','PUT',d,{id}),
  uploadRecording:  (id,d) => api('sessions','upload_recording','PUT',d,{id}),
  deleteSession:    (id)   => api('sessions','delete','DELETE',null,{id}),

  // Attendance
  listAttendance:  (p={}) => api('attendance','list','GET',null,p),
  markAttendance:  (recs) => api('attendance','mark','POST',{records:recs}),
  editAttendance:  (id,d) => api('attendance','edit','PUT',d,{id}),
  deleteAttendance:(id)   => api('attendance','delete','DELETE',null,{id}),

  // Results
  listResults:   (p={}) => api('results','list','GET',null,p),
  uploadResult:  (d)    => api('results','upload','POST',d),
  editResult:    (id,d) => api('results','edit','PUT',d,{id}),
  deleteResult:  (id)   => api('results','delete','DELETE',null,{id}),

  // Payments
  listPayments:   (p={}) => api('payments','list','GET',null,p),
  recordPayment:  (d)    => api('payments','record','POST',d),
  approvePayment: (id)   => api('payments','approve','PUT',null,{id}),
  editPayment:    (id,d) => api('payments','edit','PUT',d,{id}),
  deletePayment:  (id)   => api('payments','delete','DELETE',null,{id}),

  // Receipts
  listReceipts:   (p={}) => api('receipts','list','GET',null,p),
  genReceipt:     (d)    => api('receipts','generate','POST',d),
  deleteReceipt:  (id)   => api('receipts','delete','DELETE',null,{id}),

  // Feedback
  listFeedback:   (p={}) => api('feedback','list','GET',null,p),
  provideFeedback:(d)    => api('feedback','provide','POST',d),
  editFeedback:   (id,d) => api('feedback','edit','PUT',d,{id}),
  deleteFeedback: (id)   => api('feedback','delete','DELETE',null,{id}),

  // Announcements
  listAnnouncements: (p={}) => api('announcements','list','GET',null,p),
  postAnnouncement:  (d)    => api('announcements','post','POST',d),
  editAnnouncement:  (id,d) => api('announcements','edit','PUT',d,{id}),
  deleteAnnouncement:(id)   => api('announcements','delete','DELETE',null,{id}),

  // Notifications
  listNotifications: (p={}) => api('notifications','list','GET',null,p),
  markRead:          (id)   => api('notifications','mark_read','PUT',null,{id}),
  markAllRead:       ()     => api('notifications','mark_all_read','PUT'),
  deleteNotification:(id)   => api('notifications','delete','DELETE',null,{id}),

  // Materials
  listMaterials:   (p={}) => api('materials','list','GET',null,p),
  uploadMaterial:  (d)    => api('materials','upload','POST',d),
  editMaterial:    (id,d) => api('materials','edit','PUT',d,{id}),
  deleteMaterial:  (id)   => api('materials','delete','DELETE',null,{id}),

  // Performance
  listPoints:       (p={}) => api('performance','list','GET',null,p),
  getTotal:         (sID)  => api('performance','total','GET',null,{studentID:sID}),
  addPoints:        (d)    => api('performance','add','POST',d),
  editPoints:       (id,d) => api('performance','edit','PUT',d,{id}),
  deletePoints:     (id)   => api('performance','delete','DELETE',null,{id}),
  leaderboard:      ()     => api('performance','leaderboard'),
  rebuildLeaderboard:()    => api('performance','rebuild_leaderboard','POST'),
};

// ─── TABLE RENDERER ───────────────────────────────────────
function renderTable(tbodyId, rows, columns) {
  const tbody = document.getElementById(tbodyId);
  if (!tbody) return;
  if (!rows || rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="${columns.length}" style="text-align:center;color:var(--grey);padding:28px;">No records found.</td></tr>`;
    return;
  }
  tbody.innerHTML = rows.map(row =>
    `<tr>${columns.map(col => `<td>${col(row)}</td>`).join('')}</tr>`
  ).join('');
}

// ─── AUTH GUARD ───────────────────────────────────────────
async function authGuard() {
  try {
    const user = await API.me();
    sessionStorage.setItem('userID', user.userID);
    sessionStorage.setItem('name',   user.name);
    sessionStorage.setItem('email',  user.email);
    sessionStorage.setItem('role',   user.role);
    return user;
  } catch {
    window.location.href = 'login.html';
  }
}

// ─── STATUS BADGE HELPER ──────────────────────────────────
function badge(text, type) {
  const map = {
    active:'badge-active', inactive:'badge-inactive', pending:'badge-pending',
    approved:'badge-active', online:'badge-online', physical:'badge-pending',
    present:'badge-present', absent:'badge-absent', published:'badge-active', draft:'badge-pending'
  };
  const cls = map[(text||'').toLowerCase()] || 'badge-pending';
  return `<span class="badge ${cls}">${text}</span>`;
}

function actionBtns(editFn, deleteFn) {
  return `<button class="btn btn-outline btn-sm" onclick="${editFn}">Edit</button> <button class="btn btn-danger btn-sm" onclick="${deleteFn}">Delete</button>`;
}
