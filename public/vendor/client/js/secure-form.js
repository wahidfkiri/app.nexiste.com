if (!window.__CRM_SECURE_FORM_LOADED__) {
  window.__CRM_SECURE_FORM_LOADED__ = true;

  (function () {
    function csrfToken() {
      return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function loginUrl() {
      return window.CrmAuth?.loginUrl?.() || window.CRM_AUTH_ROUTES?.login || '/login';
    }

    function redirectToLogin(message) {
      if (window.CrmAuth?.redirectToLogin) {
        window.CrmAuth.redirectToLogin(message);
        return;
      }

      if (window.Toast) {
        window.Toast.warning('Session expiree', message || 'Votre session a expire. Redirection vers la connexion.', 1600);
      }

      window.setTimeout(() => {
        window.location.href = loginUrl();
      }, 180);
    }

    function isLoginRedirectResponse(response) {
      if (window.CrmAuth?.isLoginRedirectResponse) {
        return window.CrmAuth.isLoginRedirectResponse(response);
      }

      if (!response || !response.redirected || !response.url) {
        return false;
      }

      try {
        const redirectedUrl = new URL(response.url, window.location.origin);
        const loginPath = new URL(loginUrl(), window.location.origin).pathname.replace(/\/+$/, '');

        return redirectedUrl.pathname.replace(/\/+$/, '') === loginPath;
      } catch (e) {
        return false;
      }
    }

    function randomId() {
      if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return window.crypto.randomUUID();
      }
      const now = Date.now().toString(36);
      const rnd = Math.random().toString(36).slice(2, 10);
      return `${now}-${rnd}`;
    }

    function ensureRequestId(form) {
      let input = form.querySelector('input[name="_request_id"]');
      if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_request_id';
        form.appendChild(input);
      }
      if (!input.value) {
        input.value = randomId();
      }
      return input.value;
    }

    function clearErrors(form) {
      if (window.CrmForm?.clearErrors) {
        window.CrmForm.clearErrors(form);
        return;
      }
      form.querySelectorAll('.form-error').forEach((el) => el.remove());
      form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    }

    function showErrors(form, errors) {
      if (window.CrmForm?.showErrors) {
        window.CrmForm.showErrors(form, errors);
        return;
      }
      clearErrors(form);
      Object.entries(errors || {}).forEach(([name, messages]) => {
        const input = form.querySelector(`[name="${name}"]`);
        if (!input) return;
        input.classList.add('is-invalid');
        const error = document.createElement('span');
        error.className = 'form-error';
        error.textContent = Array.isArray(messages) ? String(messages[0]) : String(messages);
        input.parentNode?.appendChild(error);
      });
    }

    async function submit(form, options = {}) {
      if (!(form instanceof HTMLFormElement)) {
        throw new Error('Form invalide');
      }
      if (form.dataset.submitting === '1') {
        return { ok: false, status: 409, data: { success: false, message: 'Soumission en cours.' } };
      }

      const requestId = ensureRequestId(form);
      const url = options.url || form.action || window.location.href;
      const method = (options.method || form.method || 'POST').toUpperCase();
      const submitBtn = options.submitButton || form.querySelector('[type="submit"]');

      clearErrors(form);
      form.dataset.submitting = '1';
      if (submitBtn && window.CrmForm?.setLoading) {
        window.CrmForm.setLoading(submitBtn, true);
      } else if (submitBtn) {
        submitBtn.disabled = true;
      }

      const formData = new FormData(form);
      formData.set('_request_id', requestId);

      let body = formData;
      const headers = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        'Idempotency-Key': requestId,
      };

      if (options.json === true) {
        const payload = {};
        formData.forEach((value, key) => {
          if (payload[key] !== undefined) {
            if (!Array.isArray(payload[key])) payload[key] = [payload[key]];
            payload[key].push(value);
          } else {
            payload[key] = value;
          }
        });
        body = JSON.stringify(payload);
        headers['Content-Type'] = 'application/json';
      }

      const response = await fetch(url, { method, headers, body });

      if (isLoginRedirectResponse(response)) {
        if (submitBtn && window.CrmForm?.setLoading) {
          window.CrmForm.setLoading(submitBtn, false);
        } else if (submitBtn) {
          submitBtn.disabled = false;
        }

        form.dataset.submitting = '0';
        redirectToLogin('Votre session a expire. Redirection vers la connexion.');

        return {
          ok: false,
          status: 401,
          data: {
            success: false,
            message: 'Votre session a expire. Redirection vers la connexion.',
            redirect: loginUrl(),
          },
        };
      }

      const data = await response.json().catch(() => ({}));

      if (submitBtn && window.CrmForm?.setLoading) {
        window.CrmForm.setLoading(submitBtn, false);
      } else if (submitBtn) {
        submitBtn.disabled = false;
      }
      form.dataset.submitting = '0';

      if (response.status === 422) {
        showErrors(form, data.errors || {});
      }

      if (response.status === 401 || response.status === 419) {
        redirectToLogin(data?.message || 'Votre session a expire. Redirection vers la connexion.');
      }

      return {
        ok: response.ok,
        status: response.status,
        data,
      };
    }

    function autoBind() {
      document.querySelectorAll('form[data-secure-form="1"]').forEach((form) => {
        if (form.dataset.secureBound === '1') return;
        form.dataset.secureBound = '1';
        form.addEventListener('submit', () => {
          ensureRequestId(form);
        });
      });
    }

    document.addEventListener('DOMContentLoaded', autoBind);

    window.SecureForm = {
      ensureRequestId,
      submit,
      clearErrors,
      showErrors,
    };
  })();
}
