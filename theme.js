/* ============================================================
   SoilSync — theme.js
   Handles: theme toggle, custom cursor, scroll reveal, sidebar
   ============================================================ */

// ── THEME ────────────────────────────────────────────────
const THEME_KEY = 'soilsync_theme';

function getTheme() {
  return localStorage.getItem(THEME_KEY) || 'light';
}

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  const btn = document.getElementById('themeToggle');
  if (btn) {
    btn.innerHTML = theme === 'dark'
      ? '<span>☀️</span> Light Mode'
      : '<span>🌙</span> Dark Mode';
  }
}

function toggleTheme() {
  const next = getTheme() === 'dark' ? 'light' : 'dark';
  localStorage.setItem(THEME_KEY, next);
  applyTheme(next);
}

// Apply immediately (also run inline in <head> to prevent flash)
(function() {
  const t = localStorage.getItem('soilsync_theme') || 'light';
  document.documentElement.setAttribute('data-theme', t);
})();

// ── CUSTOM CURSOR ────────────────────────────────────────
(function initCursor() {
  // Don't show on touch devices
  if ('ontouchstart' in window) return;

  const cursor = document.getElementById('ss-cursor');
  const ring   = document.getElementById('ss-cursor-ring');
  if (!cursor || !ring) return;

  let mx = window.innerWidth / 2;
  let my = window.innerHeight / 2;
  let rx = mx, ry = my;
  let rafId;

  document.addEventListener('mousemove', (e) => {
    mx = e.clientX;
    my = e.clientY;
    cursor.style.left = mx + 'px';
    cursor.style.top  = my + 'px';
  });

  function animRing() {
    rx += (mx - rx) * 0.14;
    ry += (my - ry) * 0.14;
    ring.style.left = rx + 'px';
    ring.style.top  = ry + 'px';
    rafId = requestAnimationFrame(animRing);
  }
  animRing();

  // Hover effect on interactive elements
  const hoverSels = 'a, button, input, select, textarea, label, .btn, .nav-item, .stat-card, .card, .feat-card, .crop-card, [role="button"]';

  document.addEventListener('mouseover', (e) => {
    if (e.target.closest(hoverSels)) {
      document.body.classList.add('cursor-hover');
    }
  });

  document.addEventListener('mouseout', (e) => {
    if (e.target.closest(hoverSels)) {
      document.body.classList.remove('cursor-hover');
    }
  });

  // Hide cursor when leaving window
  document.addEventListener('mouseleave', () => {
    cursor.style.opacity = '0';
    ring.style.opacity = '0';
  });

  document.addEventListener('mouseenter', () => {
    cursor.style.opacity = '1';
    ring.style.opacity = '0.6';
  });
})();

// ── SCROLL REVEAL ────────────────────────────────────────
(function initReveal() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        // Don't unobserve — keep for re-entry if needed
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  function observe() {
    document.querySelectorAll('.reveal, .reveal-stagger').forEach(el => {
      observer.observe(el);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', observe);
  } else {
    observe();
  }
})();

// ── DOM READY ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

  // Apply theme & update button text
  applyTheme(getTheme());

  // ── MOBILE SIDEBAR ──
  const menuBtn  = document.getElementById('mobileMenuBtn');
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebar-overlay');

  function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('show');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('show');
    document.body.style.overflow = '';
  }

  menuBtn?.addEventListener('click', () => {
    if (sidebar?.classList.contains('open')) closeSidebar();
    else openSidebar();
  });

  overlay?.addEventListener('click', closeSidebar);

  // Close sidebar on nav item click (mobile)
  sidebar?.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
      if (window.innerWidth <= 900) closeSidebar();
    });
  });

  // ── AUTO-DISMISS ALERTS ──
  document.querySelectorAll('.alert').forEach(el => {
    // Add close button
    const close = document.createElement('button');
    close.innerHTML = '×';
    close.style.cssText = 'margin-left:auto;background:none;border:none;font-size:18px;line-height:1;opacity:0.6;padding:0 0 0 10px;flex-shrink:0;';
    close.addEventListener('click', () => dismissAlert(el));
    el.appendChild(close);

    // Auto dismiss after 5 seconds
    setTimeout(() => dismissAlert(el), 5000);
  });

  function dismissAlert(el) {
    el.style.transition = 'opacity 0.4s, transform 0.4s, max-height 0.4s, margin 0.4s, padding 0.4s';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-6px)';
    setTimeout(() => el.remove(), 400);
  }

  // ── CONFIRM DIALOGS ──
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
      const msg = el.dataset.confirm || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  // ── ACTIVE NAV LINK HIGHLIGHT ──
  const currentPath = window.location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('.nav-item[href]').forEach(link => {
    const href = link.getAttribute('href').split('?')[0];
    if (href === currentPath) link.classList.add('active');
  });
});
