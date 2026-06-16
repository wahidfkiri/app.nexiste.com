(function () {
  const pageConfig = window.PasswordRecoveryPage || {};
  const mode = pageConfig.mode || 'request';
  const feedback = document.getElementById('recoveryFeedback');
  const feedbackText = document.getElementById('recoveryFeedbackText');
  const feedbackIcon = document.getElementById('recoveryFeedbackIcon');
  const defaultRedirect = pageConfig.defaultRedirect || '/login';
  const genericErrorMessage = pageConfig.genericErrorMessage || 'Operation impossible pour le moment. Reessayez dans un instant.';

  const requestForm = document.getElementById('forgotPasswordForm');
  const requestButton = document.getElementById('forgotPasswordBtn');
  const resetForm = document.getElementById('resetPasswordForm');
  const resetButton = document.getElementById('resetPasswordBtn');
  const strengthProgress = document.getElementById('strengthProgress');
  const strengthText = document.getElementById('strengthText');
  const passwordInput = document.getElementById('password');
  const emailInput = document.getElementById('email');

  function getActiveForm() {
    return mode === 'reset' ? resetForm : requestForm;
  }

  function getActiveButton() {
    return mode === 'reset' ? resetButton : requestButton;
  }

  function setLoading(isLoading) {
    const button = getActiveButton();
    if (!button) {
      return;
    }

    button.disabled = isLoading;
    button.classList.toggle('is-loading', isLoading);

    const label = button.querySelector('.btn-recovery-label');
    if (!label) {
      return;
    }

    if (mode === 'reset') {
      label.textContent = isLoading ? 'Mise a jour en cours...' : 'Mettre a jour mon mot de passe';
      return;
    }

    label.textContent = isLoading ? 'Envoi en cours...' : 'Envoyer le lien de reinitialisation';
  }

  function clearFieldErrors() {
    document.querySelectorAll('.form-control-recovery').forEach((input) => {
      input.classList.remove('is-invalid');
    });

    document.querySelectorAll('.form-error[data-runtime-error="1"]').forEach((node) => {
      node.remove();
    });
  }

  function attachFieldError(input, message) {
    if (!input || !message) {
      return;
    }

    input.classList.add('is-invalid');

    const field = input.closest('.recovery-field');
    if (!field) {
      return;
    }

    const existing = field.querySelector('.form-error[data-runtime-error="1"]');
    if (existing) {
      existing.textContent = message;
      return;
    }

    const error = document.createElement('span');
    error.className = 'form-error';
    error.dataset.runtimeError = '1';
    error.textContent = message;
    field.appendChild(error);
  }

  function showFieldErrors(errors) {
    if (!errors || typeof errors !== 'object') {
      return;
    }

    Object.entries(errors).forEach(([field, messages]) => {
      const input = document.querySelector(`[name="${field}"]`);
      if (!input || !Array.isArray(messages) || messages.length === 0) {
        return;
      }

      attachFieldError(input, String(messages[0] || ''));
    });
  }

  function showFeedback(type, message) {
    if (!feedback || !feedbackText || !feedbackIcon) {
      return;
    }

    feedback.className = `recovery-feedback is-visible is-${type}`;
    feedbackText.textContent = message || '';
    feedbackIcon.innerHTML = type === 'success'
      ? '<i class="fas fa-circle-check"></i>'
      : '<i class="fas fa-circle-exclamation"></i>';
  }

  function hideFeedback(force) {
    if (!feedback) {
      return;
    }

    const initialType = feedback.dataset.initialType || '';
    if (!force && initialType === 'error') {
      return;
    }

    feedback.className = 'recovery-feedback';
    if (feedbackText) {
      feedbackText.textContent = '';
    }
  }

  function togglePasswordVisibility(button) {
    const targetId = button.getAttribute('data-target');
    const input = targetId ? document.getElementById(targetId) : null;
    if (!input) {
      return;
    }

    const nextType = input.type === 'password' ? 'text' : 'password';
    input.type = nextType;

    const icon = button.querySelector('i');
    if (!icon) {
      return;
    }

    icon.classList.toggle('fa-eye', nextType === 'password');
    icon.classList.toggle('fa-eye-slash', nextType !== 'password');
  }

  function evaluatePasswordStrength(password) {
    if (!strengthProgress || !strengthText) {
      return;
    }

    if (!password) {
      strengthProgress.style.width = '0%';
      strengthProgress.style.backgroundColor = '#e2e8f0';
      strengthText.textContent = '';
      strengthText.style.color = '#5f728a';
      return;
    }

    let score = 0;
    if (password.length >= 8) score += 1;
    if (password.length >= 12) score += 1;
    if (/[a-z]/.test(password)) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/\d/.test(password)) score += 1;
    if (/[^A-Za-z\d]/.test(password)) score += 1;

    const levels = [
      { max: 2, label: 'Tres faible', width: '20%', color: '#dc2626' },
      { max: 3, label: 'Faible', width: '38%', color: '#f59e0b' },
      { max: 4, label: 'Correct', width: '58%', color: '#d97706' },
      { max: 5, label: 'Fort', width: '82%', color: '#0f766e' },
      { max: 6, label: 'Tres fort', width: '100%', color: '#15803d' },
    ];

    const level = levels.find((entry) => score <= entry.max) || levels[levels.length - 1];
    strengthProgress.style.width = level.width;
    strengthProgress.style.backgroundColor = level.color;
    strengthText.textContent = `Force du mot de passe : ${level.label}`;
    strengthText.style.color = level.color;
  }

  function validateRequestForm() {
    clearFieldErrors();
    hideFeedback(true);

    const email = String(emailInput?.value || '').trim();
    let isValid = true;

    if (!email) {
      attachFieldError(emailInput, 'L email est obligatoire.');
      isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      attachFieldError(emailInput, 'Le format de l email est invalide.');
      isValid = false;
    }

    return isValid;
  }

  function validateResetForm() {
    clearFieldErrors();
    hideFeedback(true);

    let isValid = true;
    const form = getActiveForm();
    const email = String(form?.querySelector('[name="email"]')?.value || '').trim();
    const password = String(form?.querySelector('[name="password"]')?.value || '');
    const confirmation = String(form?.querySelector('[name="password_confirmation"]')?.value || '');

    if (!email) {
      attachFieldError(form?.querySelector('[name="email"]'), 'L email est obligatoire.');
      isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      attachFieldError(form?.querySelector('[name="email"]'), 'Le format de l email est invalide.');
      isValid = false;
    }

    if (!password) {
      attachFieldError(form?.querySelector('[name="password"]'), 'Le mot de passe est obligatoire.');
      isValid = false;
    } else {
      if (password.length < 8) {
        attachFieldError(form?.querySelector('[name="password"]'), 'Le mot de passe doit contenir au moins 8 caracteres.');
        isValid = false;
      }

      if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/.test(password)) {
        attachFieldError(form?.querySelector('[name="password"]'), 'Ajoutez une minuscule, une majuscule, un chiffre et un caractere special.');
        isValid = false;
      }
    }

    if (!confirmation) {
      attachFieldError(form?.querySelector('[name="password_confirmation"]'), 'La confirmation du mot de passe est obligatoire.');
      isValid = false;
    } else if (password !== confirmation) {
      attachFieldError(form?.querySelector('[name="password_confirmation"]'), 'La confirmation du mot de passe ne correspond pas.');
      isValid = false;
    }

    return isValid;
  }

  async function submitRequestForm(event) {
    event.preventDefault();
    const form = getActiveForm();
    if (!form || form.dataset.submitting === '1') {
      return;
    }

    if (!validateRequestForm()) {
      return;
    }

    form.dataset.submitting = '1';
    setLoading(true);

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new FormData(form),
        credentials: 'same-origin',
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        if (payload?.errors) {
          showFieldErrors(payload.errors);
        }

        showFeedback('error', payload?.message || genericErrorMessage);
        return;
      }

      form.reset();
      clearFieldErrors();
      showFeedback('success', payload?.message || 'Si un compte existe avec cette adresse email, un lien de reinitialisation vient d etre envoye.');
    } catch (error) {
      showFeedback('error', genericErrorMessage);
    } finally {
      delete form.dataset.submitting;
      setLoading(false);
    }
  }

  async function submitResetForm(event) {
    event.preventDefault();
    const form = getActiveForm();
    if (!form || form.dataset.submitting === '1') {
      return;
    }

    if (!validateResetForm()) {
      return;
    }

    form.dataset.submitting = '1';
    setLoading(true);

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new FormData(form),
        credentials: 'same-origin',
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        if (payload?.errors) {
          showFieldErrors(payload.errors);
        }

        showFeedback('error', payload?.message || genericErrorMessage);
        return;
      }

      showFeedback('success', payload?.message || 'Votre mot de passe a ete reinitialise. Vous pouvez maintenant vous connecter.');

      window.setTimeout(() => {
        window.location.href = payload?.redirect || defaultRedirect;
      }, 1400);
    } catch (error) {
      showFeedback('error', genericErrorMessage);
    } finally {
      delete form.dataset.submitting;
      setLoading(false);
    }
  }

  document.querySelectorAll('.toggle-password').forEach((button) => {
    button.addEventListener('click', () => togglePasswordVisibility(button));
  });

  document.querySelectorAll('.form-control-recovery').forEach((input) => {
    input.addEventListener('input', () => {
      input.classList.remove('is-invalid');

      const field = input.closest('.recovery-field');
      field?.querySelectorAll('.form-error[data-runtime-error="1"]').forEach((node) => node.remove());

      hideFeedback(false);
    });
  });

  if (passwordInput) {
    evaluatePasswordStrength(passwordInput.value || '');
    passwordInput.addEventListener('input', (event) => {
      evaluatePasswordStrength(event.target.value || '');
    });
  }

  if (requestForm) {
    requestForm.addEventListener('submit', submitRequestForm);
  }

  if (resetForm) {
    resetForm.addEventListener('submit', submitResetForm);
  }
})();
