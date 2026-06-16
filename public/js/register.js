const form = document.getElementById('registerForm');
const submitButton = document.getElementById('registerBtn');
const passwordInput = document.getElementById('password');
const strengthProgress = document.getElementById('strengthProgress');
const strengthText = document.getElementById('strengthText');
const feedback = document.getElementById('registerFeedback');
const feedbackText = document.getElementById('registerFeedbackText');
const feedbackIcon = document.getElementById('registerFeedbackIcon');
const defaultRedirect = window.RegisterPage?.defaultRedirect || '/login';
const genericErrorMessage = window.RegisterPage?.registerErrorMessage || 'Inscription impossible pour le moment. Reessayez dans un instant.';

function togglePasswordVisibility(button) {
    const targetId = button.getAttribute('data-target');
    const input = document.getElementById(targetId);

    if (!input) {
        return;
    }

    const isPassword = input.getAttribute('type') === 'password';
    input.setAttribute('type', isPassword ? 'text' : 'password');

    const icon = button.querySelector('i');
    if (icon) {
        icon.classList.toggle('fa-eye', !isPassword);
        icon.classList.toggle('fa-eye-slash', isPassword);
    }
}

function setLoading(isLoading) {
    if (!submitButton) {
        return;
    }

    submitButton.disabled = isLoading;
    submitButton.classList.toggle('is-loading', isLoading);
}

function clearFieldErrors() {
    document.querySelectorAll('.form-control-modern').forEach((input) => {
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

    const existing = input.closest('.register-field')?.querySelector('.form-error[data-runtime-error="1"]');
    if (existing) {
        existing.textContent = message;
        return;
    }

    const error = document.createElement('span');
    error.className = 'form-error';
    error.dataset.runtimeError = '1';
    error.textContent = message;
    input.closest('.register-field')?.appendChild(error);
}

function showFieldErrors(errors) {
    if (!errors || typeof errors !== 'object') {
        return;
    }

    Object.entries(errors).forEach(([field, messages]) => {
        const input = document.querySelector(`[name="${field}"]`);
        if (input && Array.isArray(messages) && messages.length > 0) {
            attachFieldError(input, String(messages[0] || ''));
        }
    });
}

function showFeedback(type, message) {
    if (!feedback || !feedbackText || !feedbackIcon) {
        return;
    }

    feedback.className = `register-feedback is-visible is-${type}`;
    feedbackText.textContent = message;
    feedbackIcon.innerHTML = `<i class="fas ${type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}"></i>`;
}

function hideFeedback() {
    if (!feedback) {
        return;
    }

    feedback.className = 'register-feedback';
    if (feedbackText) {
        feedbackText.textContent = '';
    }
}

function evaluatePasswordStrength(password) {
    if (!strengthProgress || !strengthText) {
        return;
    }

    if (!password) {
        strengthProgress.style.width = '0%';
        strengthProgress.style.backgroundColor = '#e2e8f0';
        strengthText.textContent = '';
        strengthText.style.color = '#64748b';
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
        { max: 2, label: 'Tres faible', width: '22%', color: '#ef4444' },
        { max: 3, label: 'Faible', width: '38%', color: '#f59e0b' },
        { max: 4, label: 'Moyen', width: '58%', color: '#eab308' },
        { max: 5, label: 'Fort', width: '78%', color: '#10b981' },
        { max: 6, label: 'Tres fort', width: '100%', color: '#059669' },
    ];

    const level = levels.find((entry) => score <= entry.max) || levels[levels.length - 1];
    strengthProgress.style.width = level.width;
    strengthProgress.style.backgroundColor = level.color;
    strengthText.textContent = `Force du mot de passe : ${level.label}`;
    strengthText.style.color = level.color;
}

function validateBeforeSubmit() {
    clearFieldErrors();
    hideFeedback();

    let isValid = true;
    const requiredFields = [
        { name: 'first_name', message: 'Le prenom est obligatoire.' },
        { name: 'last_name', message: 'Le nom est obligatoire.' },
        { name: 'email', message: 'L\'email est obligatoire.' },
        { name: 'password', message: 'Le mot de passe est obligatoire.' },
        { name: 'password_confirmation', message: 'La confirmation du mot de passe est obligatoire.' },
    ];

    requiredFields.forEach(({ name, message }) => {
        const input = document.querySelector(`[name="${name}"]`);
        if (input && !String(input.value || '').trim()) {
            attachFieldError(input, message);
            isValid = false;
        }
    });

    const emailInput = document.querySelector('[name="email"]');
    if (emailInput && String(emailInput.value || '').trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(emailInput.value).trim())) {
        attachFieldError(emailInput, 'Le format de l\'email est invalide.');
        isValid = false;
    }

    const password = String(document.querySelector('[name="password"]')?.value || '');
    const passwordConfirmation = String(document.querySelector('[name="password_confirmation"]')?.value || '');

    if (password && password.length < 8) {
        attachFieldError(document.querySelector('[name="password"]'), 'Le mot de passe doit contenir au moins 8 caracteres.');
        isValid = false;
    }

    if (password && !(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/.test(password))) {
        attachFieldError(document.querySelector('[name="password"]'), 'Ajoutez une minuscule, une majuscule, un chiffre et un caractere special.');
        isValid = false;
    }

    if (password && passwordConfirmation && password !== passwordConfirmation) {
        attachFieldError(document.querySelector('[name="password_confirmation"]'), 'La confirmation du mot de passe ne correspond pas.');
        isValid = false;
    }

    const termsCheckbox = document.getElementById('termsCheckbox');
    if (termsCheckbox && !termsCheckbox.checked) {
        showFeedback('error', 'Vous devez accepter les conditions d\'utilisation pour continuer.');
        isValid = false;
    }

    return isValid;
}

async function submitRegister(event) {
    event.preventDefault();

    if (!form) {
        return;
    }

    if (!validateBeforeSubmit()) {
        return;
    }

    setLoading(true);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
            credentials: 'same-origin',
        });

        let payload = null;
        try {
            payload = await response.json();
        } catch (error) {
            payload = null;
        }

        if (!response.ok) {
            if (response.status === 422 && payload?.errors) {
                showFieldErrors(payload.errors);
                showFeedback('error', payload.message || 'Merci de corriger les champs signales.');
                return;
            }

            showFeedback('error', payload?.message || genericErrorMessage);
            return;
        }

        showFeedback('success', payload?.message || 'Compte cree. Verifiez votre email pour activer votre acces.');

        window.setTimeout(() => {
            window.location.href = payload?.redirect || defaultRedirect;
        }, 1200);
    } catch (error) {
        showFeedback('error', genericErrorMessage);
    } finally {
        setLoading(false);
    }
}

document.querySelectorAll('.toggle-password').forEach((button) => {
    button.addEventListener('click', () => togglePasswordVisibility(button));
});

if (passwordInput) {
    passwordInput.addEventListener('input', (event) => {
        evaluatePasswordStrength(event.target.value || '');
    });
}

if (form) {
    form.addEventListener('submit', submitRegister);
}
