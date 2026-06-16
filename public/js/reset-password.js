// ======================== PAGE RESET PASSWORD - SCRIPT COMPLET ========================

// Loader
const loaderOverlay = document.getElementById('loaderOverlay');

function showLoader() {
    if(loaderOverlay) loaderOverlay.classList.add('active');
}

function hideLoader() {
    if(loaderOverlay) loaderOverlay.classList.remove('active');
}

// Variables
let userEmail = '';
let generatedOTP = '';
let otpTimer = null;
let timeLeft = 60;

// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if(input) {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            button.querySelector('i').classList.toggle('fa-eye');
            button.querySelector('i').classList.toggle('fa-eye-slash');
        }
    });
});

// Password strength checker
const newPasswordInput = document.getElementById('newPassword');
const strengthProgress = document.getElementById('strengthProgress');
const strengthText = document.getElementById('strengthText');

function checkPasswordStrength(password) {
    let strength = 0;
    let message = '';
    let color = '';
    
    if(password.length === 0) {
        strengthProgress.style.width = '0%';
        strengthText.textContent = '';
        return;
    }
    
    if(password.length >= 8) strength++;
    if(password.length >= 12) strength++;
    if(/[a-z]/.test(password)) strength++;
    if(/[A-Z]/.test(password)) strength++;
    if(/[0-9]/.test(password)) strength++;
    if(/[^a-zA-Z0-9]/.test(password)) strength++;
    
    if(strength <= 2) {
        message = 'Très faible';
        color = '#ef4444';
        strengthProgress.style.width = '20%';
    } else if(strength <= 4) {
        message = 'Faible';
        color = '#f59e0b';
        strengthProgress.style.width = '40%';
    } else if(strength <= 6) {
        message = 'Moyen';
        color = '#eab308';
        strengthProgress.style.width = '60%';
    } else if(strength <= 8) {
        message = 'Fort';
        color = '#10b981';
        strengthProgress.style.width = '80%';
    } else {
        message = 'Très fort';
        color = '#059669';
        strengthProgress.style.width = '100%';
    }
    
    strengthProgress.style.backgroundColor = color;
    strengthText.textContent = `Force du mot de passe : ${message}`;
    strengthText.style.color = color;
}

if(newPasswordInput) {
    newPasswordInput.addEventListener('input', (e) => checkPasswordStrength(e.target.value));
}

// Password requirements checker
function checkPasswordRequirements(password) {
    const reqLength = document.getElementById('reqLength');
    const reqLower = document.getElementById('reqLower');
    const reqUpper = document.getElementById('reqUpper');
    const reqNumber = document.getElementById('reqNumber');
    
    if(password.length >= 8) {
        reqLength.classList.add('valid');
        reqLength.querySelector('i').className = 'fas fa-check-circle';
    } else {
        reqLength.classList.remove('valid');
        reqLength.querySelector('i').className = 'far fa-circle';
    }
    
    if(/[a-z]/.test(password)) {
        reqLower.classList.add('valid');
        reqLower.querySelector('i').className = 'fas fa-check-circle';
    } else {
        reqLower.classList.remove('valid');
        reqLower.querySelector('i').className = 'far fa-circle';
    }
    
    if(/[A-Z]/.test(password)) {
        reqUpper.classList.add('valid');
        reqUpper.querySelector('i').className = 'fas fa-check-circle';
    } else {
        reqUpper.classList.remove('valid');
        reqUpper.querySelector('i').className = 'far fa-circle';
    }
    
    if(/[0-9]/.test(password)) {
        reqNumber.classList.add('valid');
        reqNumber.querySelector('i').className = 'fas fa-check-circle';
    } else {
        reqNumber.classList.remove('valid');
        reqNumber.querySelector('i').className = 'far fa-circle';
    }
}

if(newPasswordInput) {
    newPasswordInput.addEventListener('input', (e) => checkPasswordRequirements(e.target.value));
}

// Affichage des messages
function showError(message, step = 1) {
    let errorDiv = document.querySelector(`#step${step} .error-message`);
    if(!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        const form = document.querySelector(`#step${step} form`);
        if(form) form.insertBefore(errorDiv, form.firstChild);
    }
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    errorDiv.classList.add('show');
    
    setTimeout(() => {
        errorDiv.classList.remove('show');
    }, 4000);
}

function showSuccess(message, step = 1) {
    let successDiv = document.querySelector(`#step${step} .success-message`);
    if(!successDiv) {
        successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        const form = document.querySelector(`#step${step} form`);
        if(form) form.insertBefore(successDiv, form.firstChild);
    }
    successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    successDiv.classList.add('show');
    
    setTimeout(() => {
        successDiv.classList.remove('show');
    }, 4000);
}

// Générer OTP
function generateOTP() {
    const otp = Math.floor(100000 + Math.random() * 900000).toString();
    console.log(`OTP généré: ${otp}`); // Pour debug, à retirer en production
    return otp;
}

// Simulation d'envoi d'email
async function sendOTPEmail(email, otp) {
    showLoader();
    
    // Simulation d'appel API
    return new Promise((resolve) => {
        setTimeout(() => {
            console.log(`Email envoyé à ${email} avec le code: ${otp}`);
            hideLoader();
            resolve(true);
        }, 1500);
    });
}

// Timer pour renvoi de code
function startTimer() {
    const timerElement = document.getElementById('timer');
    const resendBtn = document.getElementById('resendCodeBtn');
    
    timeLeft = 60;
    if(otpTimer) clearInterval(otpTimer);
    
    otpTimer = setInterval(() => {
        timeLeft--;
        if(timerElement) timerElement.textContent = `${timeLeft}s`;
        
        if(timeLeft <= 0) {
            clearInterval(otpTimer);
            if(resendBtn) {
                resendBtn.disabled = false;
                if(timerElement) timerElement.style.display = 'none';
            }
        } else {
            if(resendBtn) resendBtn.disabled = true;
            if(timerElement) timerElement.style.display = 'inline';
        }
    }, 1000);
}

// Gestion OTP inputs
function setupOTPInputs() {
    const inputs = document.querySelectorAll('.otp-input');
    
    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            if(e.target.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });
        
        input.addEventListener('keydown', (e) => {
            if(e.key === 'Backspace' && index > 0 && !input.value) {
                inputs[index - 1].focus();
            }
        });
        
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const paste = e.clipboardData.getData('text');
            const pastedCode = paste.replace(/\D/g, '').slice(0, 6);
            
            for(let i = 0; i < pastedCode.length && i < inputs.length; i++) {
                inputs[i].value = pastedCode[i];
            }
            
            if(pastedCode.length === 6) {
                document.getElementById('verifyCodeBtn').click();
            } else if(pastedCode.length > 0) {
                inputs[Math.min(pastedCode.length, inputs.length - 1)].focus();
            }
        });
    });
}

// Step 1: Envoi du code
const emailForm = document.getElementById('emailForm');

if(emailForm) {
    emailForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = document.getElementById('resetEmail').value.trim();
        
        if(!email) {
            showError('Veuillez entrer votre email', 1);
            return;
        }
        
        if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('Veuillez entrer un email valide', 1);
            return;
        }
        
        userEmail = email;
        generatedOTP = generateOTP();
        
        // Simulation d'envoi
        const success = await sendOTPEmail(userEmail, generatedOTP);
        
        if(success) {
            showSuccess('Code envoyé à votre adresse email', 1);
            
            setTimeout(() => {
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                document.getElementById('userEmailDisplay').textContent = userEmail;
                setupOTPInputs();
                startTimer();
                
                // Reset OTP inputs
                document.querySelectorAll('.otp-input').forEach(input => input.value = '');
                document.querySelectorAll('.otp-input')[0].focus();
            }, 1500);
        } else {
            showError('Erreur lors de l\'envoi du code', 1);
        }
    });
}

// Step 2: Vérification OTP
const otpForm = document.getElementById('otpForm');

if(otpForm) {
    otpForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const inputs = document.querySelectorAll('.otp-input');
        const enteredOTP = Array.from(inputs).map(input => input.value).join('');
        
        if(enteredOTP.length !== 6) {
            showError('Veuillez entrer le code à 6 chiffres', 2);
            return;
        }
        
        if(enteredOTP === generatedOTP) {
            showSuccess('Code vérifié avec succès', 2);
            
            setTimeout(() => {
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'block';
            }, 1000);
        } else {
            showError('Code invalide. Veuillez réessayer.', 2);
            inputs.forEach(input => {
                input.classList.add('error');
                setTimeout(() => input.classList.remove('error'), 500);
            });
        }
    });
}

// Renvoyer le code
const resendCodeBtn = document.getElementById('resendCodeBtn');

if(resendCodeBtn) {
    resendCodeBtn.addEventListener('click', async () => {
        if(timeLeft > 0) return;
        
        generatedOTP = generateOTP();
        await sendOTPEmail(userEmail, generatedOTP);
        showSuccess('Nouveau code envoyé', 2);
        startTimer();
    });
}

// Step 3: Nouveau mot de passe
const newPasswordForm = document.getElementById('newPasswordForm');

if(newPasswordForm) {
    newPasswordForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmNewPassword').value;
        
        if(!newPassword || !confirmPassword) {
            showError('Veuillez remplir tous les champs', 3);
            return;
        }
        
        if(newPassword.length < 8) {
            showError('Le mot de passe doit contenir au moins 8 caractères', 3);
            return;
        }
        
        if(!/[a-z]/.test(newPassword)) {
            showError('Le mot de passe doit contenir une minuscule', 3);
            return;
        }
        
        if(!/[A-Z]/.test(newPassword)) {
            showError('Le mot de passe doit contenir une majuscule', 3);
            return;
        }
        
        if(!/[0-9]/.test(newPassword)) {
            showError('Le mot de passe doit contenir un chiffre', 3);
            return;
        }
        
        if(newPassword !== confirmPassword) {
            showError('Les mots de passe ne correspondent pas', 3);
            return;
        }
        
        showLoader();
        
        // Simulation de réinitialisation
        setTimeout(() => {
            hideLoader();
            
            // Afficher modal de succès
            const modal = document.getElementById('successModal');
            if(modal) modal.classList.add('active');
        }, 1500);
    });
}

// Redirection vers login
const goToLoginBtn = document.getElementById('goToLoginBtn');

if(goToLoginBtn) {
    goToLoginBtn.addEventListener('click', () => {
        window.location.href = 'login.html';
    });
}

// Animation des steps
document.querySelectorAll('.reset-step').forEach(step => {
    step.style.animation = 'fadeInUp 0.4s ease';
});