(function () {
  const form = document.getElementById('loginForm');
  const feedback = document.getElementById('loginFeedback');
  const feedbackText = document.getElementById('loginFeedbackText');
  const feedbackIcon = document.getElementById('loginFeedbackIcon');
  const loginBtn = document.getElementById('loginBtn');
  const passwordToggle = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const emailInput = document.getElementById('email');

  if (passwordToggle && passwordInput) {
    passwordToggle.addEventListener('click', () => {
      const nextType = passwordInput.type === 'password' ? 'text' : 'password';
      passwordInput.type = nextType;
      const icon = passwordToggle.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-eye', nextType === 'password');
        icon.classList.toggle('fa-eye-slash', nextType !== 'password');
      }
    });
  }

  function setLoading(isLoading) {
    if (!loginBtn) return;
    loginBtn.disabled = isLoading;
    loginBtn.classList.toggle('is-loading', isLoading);
    const label = loginBtn.querySelector('.btn-login-label');
    if (label) {
      label.textContent = isLoading ? 'Connexion en cours...' : 'Se connecter';
    }
  }

  function clearFieldErrors() {
    if (!form) return;
    form.querySelectorAll('.form-error[data-dynamic="1"]').forEach((node) => node.remove());
    form.querySelectorAll('.is-invalid').forEach((node) => node.classList.remove('is-invalid'));
  }

  function showFieldErrors(errors) {
    if (!form || !errors || typeof errors !== 'object') return;

    Object.entries(errors).forEach(([name, messages]) => {
      const input = form.querySelector(`[name="${name}"]`);
      if (!input) return;

      input.classList.add('is-invalid');
      const error = document.createElement('span');
      error.className = 'form-error';
      error.dataset.dynamic = '1';
      error.textContent = Array.isArray(messages) ? String(messages[0] || '') : String(messages || '');

      const field = input.closest('.login-field');
      field?.appendChild(error);
    });
  }

  function showFeedback(type, message) {
    if (!feedback || !feedbackText || !feedbackIcon) return;

    feedback.classList.remove('is-error', 'is-success');
    feedback.classList.add('is-visible', type === 'success' ? 'is-success' : 'is-error');
    feedbackText.textContent = message || '';
    feedbackIcon.innerHTML = type === 'success'
      ? '<i class="fas fa-circle-check"></i>'
      : '<i class="fas fa-circle-exclamation"></i>';
  }

  function hideFeedback() {
    if (!feedback) return;
    feedback.classList.remove('is-visible', 'is-error', 'is-success');
    if (feedbackText) {
      feedbackText.textContent = '';
    }
  }

  async function submitLogin(event) {
    event.preventDefault();
    if (!form || form.dataset.submitting === '1') {
      return;
    }

    clearFieldErrors();
    hideFeedback();
    setLoading(true);

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: new FormData(form),
        credentials: 'same-origin'
      });

      const data = await response.json().catch(() => ({}));

      if (response.ok && data?.success) {
        showFeedback('success', data.message || 'Connexion reussie.');
        window.setTimeout(() => {
          window.location.href = data.redirect || window.LoginPage?.defaultRedirect || '/dashboard';
        }, 120);
        return;
      }

      if (data?.errors) {
        showFieldErrors(data.errors);
      }

      showFeedback('error', data?.message || window.LoginPage?.loginErrorMessage || 'Connexion impossible pour le moment.');
    } catch (error) {
      showFeedback('error', 'La connexion a echoue. Verifiez votre reseau puis reessayez.');
    } finally {
      setLoading(false);
    }
  }

  if (form) {
    form.addEventListener('submit', submitLogin);
  }

  [emailInput, passwordInput].forEach((input) => {
    input?.addEventListener('input', () => {
      input.classList.remove('is-invalid');
      if (feedback?.dataset.initialType !== 'error') {
        hideFeedback();
      }
    });
  });
})();
