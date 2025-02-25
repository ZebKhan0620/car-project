document.addEventListener('DOMContentLoaded', function() {
    const setupContainer = document.getElementById('setupContainer');
    const backupCodesContainer = document.getElementById('backupCodesContainer');
    const csrf_token = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!csrf_token) {
        showAlert('CSRF token not found', 'error');
        return;
    }

    // Initialize 2FA setup
    async function initSetup() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            console.log('Using CSRF token:', csrfToken); // Debug log
            
            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            const response = await fetch('/car-project/public/api/2fa/setup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,  // Match exact case with API
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.success) {
                displayQRCode(data.secret);
                storeBackupCodes(data.backup_codes);
            } else {
                showAlert(data.error || 'Failed to initialize 2FA setup', 'error');
            }
        } catch (error) {
            console.error('2FA Setup Error:', error);
            showAlert('Failed to initialize 2FA setup: ' + error.message, 'error');
        }
    }

    // Display QR code and verification form
    function displayQRCode(secret) {
        const appName = 'CarManagement';
        const qrUrl = `otpauth://totp/${encodeURIComponent(window.userEmail)}?secret=${secret}&issuer=${encodeURIComponent(appName)}`;
        
        setupContainer.innerHTML = `
            <div class="text-center mb-6">
                <div id="qrcode" class="mx-auto mb-4 p-4 bg-white rounded-lg inline-block"></div>
                <p class="mb-2">${window.translations.auth['2fa'].setup.scan_qr}</p>
                <p class="text-sm mb-4">
                    ${window.translations.auth['2fa'].setup.manual_code}
                    <code class="bg-base-200 px-2 py-1 rounded select-all">${secret}</code>
                </p>
                <p class="text-xs text-gray-500 mb-4">${window.translations.auth['2fa'].setup.manual_hint}</p>
                <p class="mb-2">${window.translations.auth['2fa'].setup.enter_code}</p>
            </div>
            <form id="verifySetupForm">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">${window.translations.auth['2fa'].setup.verification_code}</span>
                    </label>
                    <input type="text" id="verificationCode" class="input input-bordered" 
                           pattern="[0-9]{6}" inputmode="numeric" maxlength="6" required>
                </div>
                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">${window.translations.auth['2fa'].setup.verify_button}</button>
                </div>
            </form>
        `;

        // Create QR code with optimal settings for scanning
        new QRCode(document.getElementById("qrcode"), {
            text: qrUrl,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        document.getElementById('verifySetupForm').addEventListener('submit', verifySetup);
    }

    // Store backup codes for later display
    function storeBackupCodes(codes) {
        const codesList = document.getElementById('backupCodesList');
        codesList.innerHTML = codes.map(code => `
            <div class="bg-base-200 p-2 rounded font-mono text-center">${code}</div>
        `).join('');

        // Update backup codes container text
        const backupCodesContainer = document.getElementById('backupCodesContainer');
        backupCodesContainer.innerHTML = `
            <div class="alert alert-success">
                ${window.translations.auth['2fa'].setup.success}
            </div>

            <div class="text-center">
                <h3 class="font-bold text-xl mb-4">${window.translations.auth['2fa'].setup.backup_codes}</h3>
                <p class="mb-4 text-sm">${window.translations.auth['2fa'].setup.backup_instruction}</p>
                
                <div id="backupCodesList" class="grid grid-cols-2 gap-2 mb-6">
                    ${codes.map(code => `
                        <div class="bg-base-200 p-2 rounded font-mono text-center">${code}</div>
                    `).join('')}
                </div>

                <button id="downloadCodes" class="btn btn-outline btn-sm mb-6">
                    ${window.translations.auth['2fa'].setup.download_codes}
                </button>

                <div class="divider">${window.translations.auth['2fa'].setup.after_backup}</div>

                <a href="/car-project/public/index.php" class="btn btn-primary btn-block">
                    ${window.translations.auth['2fa'].setup.continue}
                </a>
            </div>
        `;

        document.getElementById('downloadCodes').addEventListener('click', () => {
            const codesText = codes.join('\n');
            const blob = new Blob([codesText], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'backup-codes.txt';
            a.click();
            window.URL.revokeObjectURL(url);
        });
    }

    // Verify setup and enable 2FA
    async function verifySetup(e) {
        e.preventDefault();
        const code = document.getElementById('verificationCode').value;
        const csrf_token = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!csrf_token) {
            showAlert('CSRF token not found', 'error');
            return;
        }

        try {
            const response = await fetch('/car-project/public/api/2fa/verify-setup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf_token
                },
                body: JSON.stringify({ code })
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Invalid response format from server');
            }

            const data = await response.json();
            if (data.success) {
                setupContainer.style.display = 'none';
                backupCodesContainer.classList.remove('hidden');
                backupCodesContainer.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            } else {
                throw new Error(data.error || 'Verification failed');
            }
        } catch (error) {
            console.error('2FA Verification Error:', error);
            showAlert(error.message, 'error');
        }
    }

    // Show alert message
    function showAlert(message, type = 'error') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} mb-4`;
        alertDiv.textContent = message;
        setupContainer.insertBefore(alertDiv, setupContainer.firstChild);
        setTimeout(() => alertDiv.remove(), 5000);
    }

    // Start setup process
    initSetup();
});