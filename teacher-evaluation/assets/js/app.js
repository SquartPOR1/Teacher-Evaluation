document.addEventListener('DOMContentLoaded', () => {
  const roleSelect = document.querySelector('#user-role');
  if (roleSelect) {
    const departmentFields = [...document.querySelectorAll('[data-department-kind]')];
    const studentProfileFields = document.querySelector('[data-student-profile-fields]');
    const profileNumberLabel = document.querySelector('[data-profile-number-label]');
    const staffDepartmentHelp = document.querySelector('[data-staff-department-help]');
    const createAccountButton = document.querySelector('[data-create-account-button]');
    const updateDepartmentOptions = () => {
      const studentSelected = roleSelect.value === 'student';
      const teacherSelected = roleSelect.value === 'teacher';
      departmentFields.forEach((field) => {
        const studentField = field.dataset.departmentKind === 'student';
        const visible = studentSelected === studentField;
        const select = field.querySelector('select');
        field.hidden = !visible;
        field.style.display = visible ? 'flex' : 'none';
        select.disabled = !visible;
        select.required = visible && studentSelected;
      });
      const teacherNeedsDepartmentSetup = teacherSelected && createAccountButton?.dataset.noStaffDepartments === 'true';
      if (staffDepartmentHelp) {
        staffDepartmentHelp.hidden = !teacherNeedsDepartmentSetup;
        staffDepartmentHelp.style.display = teacherNeedsDepartmentSetup ? 'block' : 'none';
      }
      if (createAccountButton) createAccountButton.disabled = false;
      if (studentProfileFields) {
        studentProfileFields.hidden = !studentSelected;
        studentProfileFields.style.display = studentSelected ? 'grid' : 'none';
        studentProfileFields.querySelectorAll('input').forEach((input) => {
          input.disabled = !studentSelected;
        });
      }
      if (profileNumberLabel) {
        profileNumberLabel.textContent = studentSelected ? 'Student number' : teacherSelected ? 'Employee number' : 'Employee / student number';
      }
    };
    roleSelect.addEventListener('change', updateDepartmentOptions);
    updateDepartmentOptions();
  }

  const toggle = document.querySelector('.menu-toggle');
  const sidebar = document.querySelector('.sidebar');
  toggle?.addEventListener('click', () => {
    const open = sidebar?.classList.toggle('is-open') ?? false;
    toggle.setAttribute('aria-expanded', String(open));
  });

  const form = document.querySelector('#evaluation-form');
  const progress = document.querySelector('#progress-label');
  if (form && progress) {
    const progressCount = document.querySelector('#progress-count');
    const progressBar = document.querySelector('.evaluation-progress [role="progressbar"]');
    const progressFill = document.querySelector('#evaluation-progress-bar');
    const updateProgress = () => {
      const required = [...form.querySelectorAll('[required]')];
      const groups = new Map();
      required.forEach((field) => {
        const key = field.type === 'radio' ? field.name : field.id;
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(field);
      });
      const complete = [...groups.values()].filter((fields) =>
        fields.some((field) => field.type === 'radio' ? field.checked : field.value.trim() !== '')
      ).length;
      const percent = groups.size ? Math.round((complete / groups.size) * 100) : 100;
      progress.textContent = `${percent}% complete`;
      if (progressCount) progressCount.textContent = groups.size ? `${complete} of ${groups.size} answered` : 'No required questions';
      progressBar?.setAttribute('aria-valuenow', String(percent));
      if (progressFill) progressFill.style.width = `${percent}%`;
    };
    form.addEventListener('input', updateProgress);
    form.addEventListener('change', updateProgress);
    updateProgress();
  }

  const motionTargets = document.querySelectorAll(
    '.app-user .overview-hero-copy, .app-user .overview-hero-art, .app-user .overview-hero-status, .app-user .overview-metrics, .app-user .overview-lower-grid, .app-user .page-heading, .app-user .stats-grid, .app-user .dashboard-grid, .app-user .two-column, .app-user .evaluation-progress, .app-user .panel, .app-guest .login-card, .app-guest .account-help-card, .app-guest .demo-intro, .app-guest .demo-preview-copy, .app-guest .demo-sample-card'
  );
  if (motionTargets.length && 'IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.body.classList.add('motion-ready');
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' });
    motionTargets.forEach((target, index) => {
      target.classList.add('motion-reveal');
      target.style.setProperty('--reveal-order', String(Math.min(index, 5)));
      revealObserver.observe(target);
    });
  }
});