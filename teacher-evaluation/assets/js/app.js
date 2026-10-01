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
    };
    form.addEventListener('input', updateProgress);
    form.addEventListener('change', updateProgress);
    updateProgress();
  }
});