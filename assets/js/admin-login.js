(() => {
  'use strict';
  const password = document.getElementById('adminPassword');
  const toggle = document.getElementById('adminPasswordToggle');
  if (password && toggle) {
    toggle.hidden = false;
    toggle.addEventListener('click', () => {
      const visible = password.type === 'password';
      password.type = visible ? 'text' : 'password';
      toggle.textContent = visible ? 'Hide' : 'Show';
      toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
      toggle.setAttribute('aria-pressed', String(visible));
    });
  }
  const error = document.getElementById('adminLoginError');
  if (error) error.focus();
})();
