if (!window.__CRM_PROFILE_SETTINGS_JS__) {
  window.__CRM_PROFILE_SETTINGS_JS__ = true;

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('profileForm');
    if (!form || form.dataset.secureAjax !== '1') {
      return;
    }

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
          window.Toast.success('Succès', result.data?.message || 'Profil mis à jour.');
        }
        return;
      }

      if (result.status === 422) {
        if (window.Toast) {
          window.Toast.error('Validation', result.data?.message || 'Veuillez corriger les erreurs.');
        }
        return;
      }

      if (window.Toast) {
        window.Toast.error('Erreur', result.data?.message || 'Échec de la mise à jour du profil.');
      }
    });
  });
}
