// ========== INVOICE SETTINGS PAGE - SCRIPT COMPLET ==========

// Signature Pad
let signaturePad = null;

// Load saved settings
function loadSettings() {
    const settings = JSON.parse(localStorage.getItem('invoiceSettings')) || {
        // Header
        invoiceTitle: 'FACTURE',
        invoiceSubtitle: 'Document officiel',
        primaryColor: '#3b82f6',
        companyName: 'NexusCRM',
        companyAddress: '123 Avenue des Affaires, 75001 Paris',
        companySiret: '123 456 789 00012',
        companyPhone: '+33 1 23 45 67 89',
        companyEmail: 'contact@nexuscrm.com',
        footerMessage: 'Merci de votre confiance !',
        legalNotice: '© 2024 NexusCRM - Tous droits réservés',
        companyWebsite: 'www.nexuscrm.com',
        signatureText: 'Signature électronique - Fait à Paris, le [date]',
        // Rules
        autoGenerateNumber: true,
        dueDateRule: true,
        dueDateDays: '30',
        taxRule: true,
        defaultTaxRate: '20',
        discountRule: true,
        lateFeeRule: false,
        lateFeeRate: '10',
        lateFeeMessage: 'Pénalité de retard applicable après la date d\'échéance',
        defaultCurrency: '€',
        numberFormat: 'fr',
        emailSubject: 'Votre facture {invoice_number}',
        emailMessage: 'Bonjour {client_name},\n\nVeuillez trouver ci-joint votre facture {invoice_number} d\'un montant de {amount}.\n\nCordialement,\n{company_name}'
    };
    
    // Apply settings to form
    document.getElementById('invoiceTitle').value = settings.invoiceTitle;
    document.getElementById('invoiceSubtitle').value = settings.invoiceSubtitle;
    document.getElementById('primaryColor').value = settings.primaryColor;
    document.getElementById('colorValue').textContent = settings.primaryColor;
    document.getElementById('companyName').value = settings.companyName;
    document.getElementById('companyAddress').value = settings.companyAddress;
    document.getElementById('companySiret').value = settings.companySiret;
    document.getElementById('companyPhone').value = settings.companyPhone;
    document.getElementById('companyEmail').value = settings.companyEmail;
    document.getElementById('footerMessage').value = settings.footerMessage;
    document.getElementById('legalNotice').value = settings.legalNotice;
    document.getElementById('companyWebsite').value = settings.companyWebsite;
    document.getElementById('signatureText').value = settings.signatureText;
    
    document.getElementById('autoGenerateNumber').checked = settings.autoGenerateNumber;
    document.getElementById('dueDateRule').checked = settings.dueDateRule;
    document.getElementById('dueDateDays').value = settings.dueDateDays;
    document.getElementById('taxRule').checked = settings.taxRule;
    document.getElementById('defaultTaxRate').value = settings.defaultTaxRate;
    document.getElementById('discountRule').checked = settings.discountRule;
    document.getElementById('lateFeeRule').checked = settings.lateFeeRule;
    document.getElementById('lateFeeRate').value = settings.lateFeeRate;
    document.getElementById('lateFeeMessage').value = settings.lateFeeMessage;
    document.getElementById('defaultCurrency').value = settings.defaultCurrency;
    document.getElementById('numberFormat').value = settings.numberFormat;
    document.getElementById('emailSubject').value = settings.emailSubject;
    document.getElementById('emailMessage').value = settings.emailMessage;
    
    // Show/hide rule options
    document.getElementById('dueDateOption').style.display = settings.dueDateRule ? 'block' : 'none';
    document.getElementById('taxOption').style.display = settings.taxRule ? 'block' : 'none';
    document.getElementById('lateFeeOption').style.display = settings.lateFeeRule ? 'block' : 'none';
    
    // Load logo
    const savedLogo = localStorage.getItem('invoiceLogo');
    if(savedLogo) {
        const logoPreview = document.getElementById('logoPreview');
        logoPreview.innerHTML = `<img src="${savedLogo}" style="width:100%; height:100%; object-fit:cover;">`;
    }
    
    // Load signature
    const savedSignature = localStorage.getItem('invoiceSignature');
    if(savedSignature && signaturePad) {
        signaturePad.fromDataURL(savedSignature);
    }
}

// Save settings
function saveSettings() {
    const settings = {
        invoiceTitle: document.getElementById('invoiceTitle').value,
        invoiceSubtitle: document.getElementById('invoiceSubtitle').value,
        primaryColor: document.getElementById('primaryColor').value,
        companyName: document.getElementById('companyName').value,
        companyAddress: document.getElementById('companyAddress').value,
        companySiret: document.getElementById('companySiret').value,
        companyPhone: document.getElementById('companyPhone').value,
        companyEmail: document.getElementById('companyEmail').value,
        footerMessage: document.getElementById('footerMessage').value,
        legalNotice: document.getElementById('legalNotice').value,
        companyWebsite: document.getElementById('companyWebsite').value,
        signatureText: document.getElementById('signatureText').value,
        autoGenerateNumber: document.getElementById('autoGenerateNumber').checked,
        dueDateRule: document.getElementById('dueDateRule').checked,
        dueDateDays: document.getElementById('dueDateDays').value,
        taxRule: document.getElementById('taxRule').checked,
        defaultTaxRate: document.getElementById('defaultTaxRate').value,
        discountRule: document.getElementById('discountRule').checked,
        lateFeeRule: document.getElementById('lateFeeRule').checked,
        lateFeeRate: document.getElementById('lateFeeRate').value,
        lateFeeMessage: document.getElementById('lateFeeMessage').value,
        defaultCurrency: document.getElementById('defaultCurrency').value,
        numberFormat: document.getElementById('numberFormat').value,
        emailSubject: document.getElementById('emailSubject').value,
        emailMessage: document.getElementById('emailMessage').value
    };
    
    // Save signature
    if(signaturePad && !signaturePad.isEmpty()) {
        localStorage.setItem('invoiceSignature', signaturePad.toDataURL());
    }
    
    localStorage.setItem('invoiceSettings', JSON.stringify(settings));
    showToast('Paramètres enregistrés avec succès', 'success');
}

// Show preview
function showPreview() {
    const settings = JSON.parse(localStorage.getItem('invoiceSettings')) || {};
    const primaryColor = settings.primaryColor || '#3b82f6';
    const companyName = settings.companyName || 'NexusCRM';
    const companyAddress = settings.companyAddress || '123 Avenue des Affaires, 75001 Paris';
    const companySiret = settings.companySiret || '123 456 789 00012';
    const companyPhone = settings.companyPhone || '+33 1 23 45 67 89';
    const companyEmail = settings.companyEmail || 'contact@nexuscrm.com';
    const footerMessage = settings.footerMessage || 'Merci de votre confiance !';
    const legalNotice = settings.legalNotice || '© 2024 NexusCRM - Tous droits réservés';
    const signatureText = settings.signatureText || 'Signature électronique - Fait à Paris, le [date]';
    const defaultCurrency = settings.defaultCurrency || '€';
    
    const savedLogo = localStorage.getItem('invoiceLogo');
    const savedSignature = localStorage.getItem('invoiceSignature');
    
    const previewContent = document.getElementById('previewContent');
    previewContent.innerHTML = `
        <div class="preview-invoice" style="border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.5rem;">
            <div class="preview-header-invoice" style="border-bottom-color: ${primaryColor};">
                ${savedLogo ? `<img src="${savedLogo}" style="max-height: 60px; margin-bottom: 0.5rem;">` : ''}
                <h2 style="color: ${primaryColor};">${settings.invoiceTitle || 'FACTURE'}</h2>
                <p>${settings.invoiceSubtitle || 'Document officiel'}</p>
            </div>
            
            <div class="preview-company-info">
                <h4>${companyName}</h4>
                <p>${companyAddress.replace(/\n/g, '<br>')}</p>
                <p>${companySiret} | ${companyPhone} | ${companyEmail}</p>
            </div>
            
            <div class="preview-client-info">
                <h4>Facturé à :</h4>
                <p>Client exemple<br>client@email.com<br>Adresse du client</p>
            </div>
            
            <table class="preview-items-table">
                <thead>
                    <tr><th>Description</th><th>Qté</th><th>Prix unit.</th><th>Total</th></tr>
                </thead>
                <tbody>
                    <tr><td>Produit exemple</td><td>2</td><td>${defaultCurrency}50.00</td><td>${defaultCurrency}100.00</td></tr>
                </tbody>
            </table>
            
            <div class="preview-totals">
                <div>Sous-total : ${defaultCurrency}100.00</div>
                <div>TVA (20%) : ${defaultCurrency}20.00</div>
                <div class="grand-total">Total : ${defaultCurrency}120.00</div>
            </div>
            
            ${footerMessage ? `<div class="preview-footer">${footerMessage}<br>${legalNotice}</div>` : ''}
            
            ${savedSignature ? `<div class="signature-line"><img src="${savedSignature}" style="max-height: 60px;"><br><small>${signatureText}</small></div>` : ''}
        </div>
    `;
    
    openModal(document.getElementById('previewModal'));
}

// Logo upload
document.getElementById('uploadLogoBtn')?.addEventListener('click', () => {
    document.getElementById('logoFile').click();
});

document.getElementById('logoFile')?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if(file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            const logoPreview = document.getElementById('logoPreview');
            logoPreview.innerHTML = `<img src="${event.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
            localStorage.setItem('invoiceLogo', event.target.result);
            showToast('Logo téléchargé avec succès', 'success');
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('removeLogoBtn')?.addEventListener('click', () => {
    document.getElementById('logoPreview').innerHTML = '<i class="fas fa-building"></i><span>NexusCRM</span>';
    localStorage.removeItem('invoiceLogo');
    showToast('Logo supprimé', 'info');
});

// Signature
function initSignaturePad() {
    const canvas = document.getElementById('signatureCanvas');
    if(canvas) {
        signaturePad = new SignaturePad(canvas);
        canvas.width = canvas.clientWidth;
        canvas.height = canvas.clientHeight;
        
        // Adjust canvas size on resize
        window.addEventListener('resize', () => {
            const data = signaturePad.toData();
            canvas.width = canvas.clientWidth;
            canvas.height = canvas.clientHeight;
            signaturePad.fromData(data);
        });
    }
}

document.getElementById('clearSignatureBtn')?.addEventListener('click', () => {
    if(signaturePad) signaturePad.clear();
});

document.getElementById('uploadSignatureBtn')?.addEventListener('click', () => {
    document.getElementById('signatureFile').click();
});

document.getElementById('signatureFile')?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if(file) {
        const reader = new FileReader();
        reader.onload = (event) => {
            const img = new Image();
            img.onload = () => {
                if(signaturePad) {
                    signaturePad.clear();
                    signaturePad.fromDataURL(event.target.result);
                }
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// Rule toggles
document.getElementById('dueDateRule')?.addEventListener('change', (e) => {
    document.getElementById('dueDateOption').style.display = e.target.checked ? 'block' : 'none';
});

document.getElementById('taxRule')?.addEventListener('change', (e) => {
    document.getElementById('taxOption').style.display = e.target.checked ? 'block' : 'none';
});

document.getElementById('lateFeeRule')?.addEventListener('change', (e) => {
    document.getElementById('lateFeeOption').style.display = e.target.checked ? 'block' : 'none';
});

// Color picker
document.getElementById('primaryColor')?.addEventListener('input', (e) => {
    document.getElementById('colorValue').textContent = e.target.value;
});

// Modal functions
function openModal(modal) { modal?.classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeModal(modal) { modal?.classList.remove('active'); document.body.style.overflow = ''; }

document.getElementById('previewBtn')?.addEventListener('click', showPreview);
document.getElementById('saveSettingsBtn')?.addEventListener('click', saveSettings);
document.getElementById('closePreviewModal')?.addEventListener('click', () => closeModal(document.getElementById('previewModal')));
document.getElementById('closePreviewFooter')?.addEventListener('click', () => closeModal(document.getElementById('previewModal')));

// Toast
function showToast(message, type = 'success') {
    const existingToast = document.querySelector('.toast-notification');
    if(existingToast) existingToast.remove();
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i><span>${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 3000);
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    showLoader();
    initSignaturePad();
    loadSettings();
    setTimeout(() => hideLoader(), 500);
});

// Close modals on outside click
window.addEventListener('click', (e) => {
    const modal = document.getElementById('previewModal');
    if(e.target === modal) closeModal(modal);
});