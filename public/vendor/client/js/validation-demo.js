if (!window.__CRM_VALIDATION_DEMO_JS__) {
  window.__CRM_VALIDATION_DEMO_JS__ = true;

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('validationDemoForm');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!window.SecureForm) {
        form.submit();
        return;
      }

      const submitButton = form.querySelector('button[type="submit"]');
      const result = await window.SecureForm.submit(form, {
        method: 'POST',
        submitButton,
      });

      if (result.ok) {
        if (window.Toast) {
          window.Toast.success('Succès', result.data?.message || 'Validation effectuée.');
        }
        return;
      }

      if (result.status === 422) {
        if (window.Toast) {
          window.Toast.error('Validation', 'Veuillez corriger les champs indiqués.');
        }
        return;
      }

      if (window.Toast) {
        window.Toast.error('Erreur', result.data?.message || 'Erreur technique.');
      }
    });
  });
}
