const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('.site-nav');
if (toggle && nav) {
  toggle.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!open));
    nav.classList.toggle('open', !open);
  });
}

document.querySelectorAll('[data-amount]').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelectorAll('[data-amount]').forEach((item) => item.classList.remove('selected'));
    button.classList.add('selected');
    const amount = document.querySelector('#amount');
    if (amount) amount.value = button.dataset.amount;
  });
});

document.querySelectorAll('input[name="frequency"]').forEach((radio) => {
  radio.addEventListener('change', () => {
    document.querySelectorAll('[data-period]').forEach((label) => {
      label.textContent = radio.value === 'monthly' ? '/month' : 'one time';
    });
  });
});

// Video Playlist Switcher
const mainVideo = document.querySelector('#main-showcase-video');
const playlistButtons = document.querySelectorAll('.playlist-btn');
const metaTitle = document.querySelector('#video-meta-title');
const metaDesc = document.querySelector('#video-meta-desc');

if (mainVideo && playlistButtons.length > 0) {
  playlistButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      playlistButtons.forEach((b) => {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
      });
      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');
      const src = btn.dataset.src;
      const poster = btn.dataset.poster;
      const title = btn.dataset.title;
      const desc = btn.dataset.desc;

      if (poster) mainVideo.poster = poster;
      if (metaTitle && title) metaTitle.textContent = title;
      if (metaDesc && desc) metaDesc.textContent = desc;

      if (src) {
        mainVideo.src = src;
        mainVideo.load();
        mainVideo.play().catch(() => {});
      }
    });
  });
}

// Gallery Lightbox Modal
const lightbox = document.querySelector('#gallery-lightbox');
const lightboxImg = document.querySelector('#lightbox-img');
const lightboxTitle = document.querySelector('#lightbox-title');
const lightboxDesc = document.querySelector('#lightbox-desc');
const galleryItems = document.querySelectorAll('[data-lightbox-src]');

if (lightbox && lightboxImg) {
  galleryItems.forEach((item) => {
    item.style.cursor = 'pointer';
    item.setAttribute('tabindex', '0');
    item.setAttribute('role', 'button');
    item.setAttribute('aria-label', 'View larger photo: ' + (item.dataset.lightboxTitle || 'Classroom'));
    const openLightbox = () => {
      lightboxImg.src = item.dataset.lightboxSrc;
      if (lightboxTitle) lightboxTitle.textContent = item.dataset.lightboxTitle || '';
      if (lightboxDesc) lightboxDesc.textContent = item.dataset.lightboxDesc || '';
      lightbox.classList.add('active');
      lightbox.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    };
    item.addEventListener('click', openLightbox);
    item.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openLightbox();
      }
    });
  });

  const closeLightbox = () => {
    lightbox.classList.remove('active');
    lightbox.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  document.querySelectorAll('[data-lightbox-close]').forEach((el) => {
    el.addEventListener('click', closeLightbox);
  });

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && lightbox.classList.contains('active')) {
      closeLightbox();
    }
  });
}

// Live Accra GMT Clock
const clockEl = document.querySelector('#accra-clock');
if (clockEl) {
  const updateClock = () => {
    const now = new Date();
    // Accra is UTC+0 (GMT) year-round
    const h = String(now.getUTCHours()).padStart(2, '0');
    const m = String(now.getUTCMinutes()).padStart(2, '0');
    const s = String(now.getUTCSeconds()).padStart(2, '0');
    clockEl.textContent = `${h}:${m}:${s} GMT (Accra)`;
  };
  updateClock();
  setInterval(updateClock, 1000);
}

// Copy EIN for Corporate Matching
document.querySelectorAll('.copy-ein-btn').forEach((btn) => {
  btn.addEventListener('click', () => {
    const ein = btn.dataset.ein || '47-1468217';
    navigator.clipboard.writeText(ein).then(() => {
      const originalText = btn.innerHTML;
      btn.innerHTML = '✓ Copied EIN: ' + ein;
      btn.classList.add('copied');
      setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.remove('copied');
      }, 3000);
    }).catch(() => {
      prompt('Copy Federal EIN for employer match:', ein);
    });
  });
});

// Interactive Code Sandbox on impact.php
const codeDisplay = document.querySelector('#code-display');
const terminalOutput = document.querySelector('#terminal-output');
const runCodeBtn = document.querySelector('#run-code-btn');
const sandboxTabs = document.querySelectorAll('.sandbox-tab-btn');

if (codeDisplay && runCodeBtn && sandboxTabs.length > 0) {
  const scripts = {
    kwame: {
      code: `# Project: Accra Lab Solar & Workstation Power Calculator
# Author: Kwame (Age 14, Early Secondary)

def lab_power_audit(battery_ah, solar_panels, num_workstations):
    total_watt_hours = battery_ah * 12 * 0.85
    watts_per_laptop = 45
    total_load = num_workstations * watts_per_laptop
    runtime_hours = total_watt_hours / total_load
    return runtime_hours

hours = lab_power_audit(battery_ah=200, solar_panels=4, num_workstations=12)

print("=" * 45)
print("  MILL CREEK-AR LAB SOLAR AUDIT · ACCRA")
print("=" * 45)
print(f"Workstations Active: 12 Laptops")
print(f"Calculated Battery Runtime: {hours:.1f} hours of coding")
print("Status: 🟢 Sustained Clean Power Available")`,
      output: `=============================================
  MILL CREEK-AR LAB SOLAR AUDIT · ACCRA
=============================================
Workstations Active: 12 Laptops
Calculated Battery Runtime: 3.8 hours of coding
Status: 🟢 Sustained Clean Power Available`
    },
    kofi: {
      code: `# Project: Memory & Hardware Diagnostic Tool
# Author: Kofi (Age 15, Middle Secondary)

workstations = [
    {"id": "WS-01", "ram_gb": 8, "status": "Ready", "assigned_to": "Kwame"},
    {"id": "WS-02", "ram_gb": 8, "status": "Ready", "assigned_to": "Kofi"},
    {"id": "WS-03", "ram_gb": 16, "status": "Ready", "assigned_to": "Ama"},
    {"id": "WS-04", "ram_gb": 8, "status": "Ready", "assigned_to": "Esi"}
]

print("DIAGNOSTIC REPORT: ACCRA COMPUTER LAB")
print("-" * 40)
total_ram = sum(ws["ram_gb"] for ws in workstations)
print(f"Total Configured Workstations: {len(workstations)}")
print(f"Total Lab Memory: {total_ram} GB DDR4")
for ws in workstations:
    print(f"  • {ws['id']}: {ws['ram_gb']}GB RAM | {ws['assigned_to']} ({ws['status']})")
print("-" * 40)
print("System Check Complete: All systems operational.")`,
      output: `DIAGNOSTIC REPORT: ACCRA COMPUTER LAB
----------------------------------------
Total Configured Workstations: 4
Total Lab Memory: 40 GB DDR4
  • WS-01: 8GB RAM | Kwame (Ready)
  • WS-02: 8GB RAM | Kofi (Ready)
  • WS-03: 16GB RAM | Ama (Ready)
  • WS-04: 8GB RAM | Esi (Ready)
----------------------------------------
System Check Complete: All systems operational.`
    },
    ama: {
      code: `# Project: STEM Academic Growth Tracker
# Author: Ama (Age 16, Upper Secondary)

subjects = {"Python 3": 94, "Math": 91, "ICT Hardware": 88, "English": 90}
average = sum(subjects.values()) / len(subjects)

print("STUDENT STEM PERFORMANCE PROFILE")
print("=" * 40)
for subject, score in subjects.items():
    stars = "★" * (score // 20)
    print(f"{subject:15} : {score}% {stars}")
print("-" * 40)
print(f"Cohort Average: {average:.1f}% (Honors Level)")
print("Recommendation: Advanced Web & AI Engineering")`,
      output: `STUDENT STEM PERFORMANCE PROFILE
========================================
Python 3        : 94% ★★★★
Math            : 91% ★★★★
ICT Hardware    : 88% ★★★★
English         : 90% ★★★★
----------------------------------------
Cohort Average: 90.8% (Honors Level)
Recommendation: Advanced Web & AI Engineering`
    }
  };

  let activeStudent = 'kwame';
  const loadStudentScript = (studentKey) => {
    activeStudent = studentKey;
    codeDisplay.textContent = scripts[studentKey].code;
    terminalOutput.textContent = 'Click "Run Python Script" above to execute ' + studentKey.charAt(0).toUpperCase() + studentKey.slice(1) + "'s code...";
  };

  loadStudentScript('kwame');

  sandboxTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      sandboxTabs.forEach((t) => {
        t.style.background = 'rgba(255,255,255,0.1)';
        t.style.color = '#cbdad5';
      });
      tab.style.background = 'var(--forest)';
      tab.style.color = '#fff';
      loadStudentScript(tab.dataset.student);
    });
  });

  runCodeBtn.addEventListener('click', () => {
    terminalOutput.innerHTML = '<span style="color:var(--gold)">$ python3 student_script.py [compiling...]</span>';
    setTimeout(() => {
      terminalOutput.textContent = scripts[activeStudent].output;
    }, 380);
  });
}
