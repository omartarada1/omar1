// Service Request Page JavaScript
let selectedDevice = null;
let selectedVersion = null;
let currentPrice = 0;
let usdtWalletAddress = '';

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializePage();
});

function initializePage() {
    setupEventListeners();
    loadWalletAddress();
    setupMobileNavigation();
}

function setupEventListeners() {
    // Device type selection
    const deviceTypeSelect = document.getElementById('deviceType');
    if (deviceTypeSelect) {
        deviceTypeSelect.addEventListener('change', handleDeviceTypeChange);
    }

    // Form submission
    const serviceForm = document.getElementById('serviceRequestForm');
    if (serviceForm) {
        serviceForm.addEventListener('submit', handleFormSubmission);
    }
}

function setupMobileNavigation() {
    const navMobile = document.querySelector('.nav-mobile');
    const navMenu = document.querySelector('.nav-menu');
    
    if (navMobile) {
        navMobile.addEventListener('click', function() {
            navMenu.classList.toggle('mobile-active');
        });
    }
}

// Handle device type selection
async function handleDeviceTypeChange() {
    const deviceType = document.getElementById('deviceType').value;
    const deviceVersionsDiv = document.getElementById('deviceVersions');
    const versionsList = document.getElementById('versionsList');
    
    // Reset previous selections
    selectedDevice = deviceType;
    selectedVersion = null;
    currentPrice = 0;
    
    if (!deviceType) {
        deviceVersionsDiv.style.display = 'none';
        hideOrderSummary();
        hidePaymentSection();
        return;
    }

    // Show device versions section
    deviceVersionsDiv.style.display = 'block';
    
    // Load device versions
    try {
        const response = await fetch(`php/get_device_versions.php?device_type=${deviceType}`);
        const data = await response.json();
        
        if (data.success && data.versions.length > 0) {
            renderDeviceVersions(data.versions);
        } else {
            versionsList.innerHTML = '<p>No versions available for this device type.</p>';
        }
    } catch (error) {
        console.error('Error loading device versions:', error);
        // Fallback to default versions
        renderDefaultVersions(deviceType);
    }
}

// Render device versions
function renderDeviceVersions(versions) {
    const versionsList = document.getElementById('versionsList');
    
    let html = '';
    versions.forEach(version => {
        html += `
            <div class="version-item" data-version="${version.name}" data-price="${version.price}" onclick="selectVersion('${version.name}', ${version.price})">
                <input type="radio" name="deviceVersion" value="${version.name}" id="version_${version.id}">
                <div class="version-info">
                    <div class="version-name">${version.name}</div>
                    <div class="version-price">$${parseFloat(version.price).toFixed(2)}</div>
                </div>
            </div>
        `;
    });
    
    versionsList.innerHTML = html;
}

// Fallback default versions if API fails
function renderDefaultVersions(deviceType) {
    const defaultVersions = {
        iphone: [
            { name: 'iPhone 15 Pro Max', price: 149 },
            { name: 'iPhone 15 Pro', price: 139 },
            { name: 'iPhone 15 Plus', price: 129 },
            { name: 'iPhone 15', price: 119 },
            { name: 'iPhone 14 Pro Max', price: 109 },
            { name: 'iPhone 14 Pro', price: 99 },
            { name: 'iPhone 14 Plus', price: 89 },
            { name: 'iPhone 14', price: 89 },
            { name: 'iPhone 13 Pro Max', price: 79 },
            { name: 'iPhone 13 Pro', price: 79 },
            { name: 'iPhone 13', price: 69 },
            { name: 'iPhone 12 Pro Max', price: 69 },
            { name: 'iPhone 12 Pro', price: 59 },
            { name: 'iPhone 12', price: 59 }
        ],
        ipad: [
            { name: 'iPad Pro 12.9" (6th gen)', price: 99 },
            { name: 'iPad Pro 11" (4th gen)', price: 89 },
            { name: 'iPad Air (5th gen)', price: 79 },
            { name: 'iPad (10th gen)', price: 69 },
            { name: 'iPad (9th gen)', price: 59 },
            { name: 'iPad mini (6th gen)', price: 69 },
            { name: 'iPad Pro 12.9" (5th gen)', price: 89 },
            { name: 'iPad Pro 11" (3rd gen)', price: 79 }
        ],
        mac: [
            { name: 'MacBook Pro 16" (M3)', price: 199 },
            { name: 'MacBook Pro 14" (M3)', price: 189 },
            { name: 'MacBook Air 15" (M3)', price: 169 },
            { name: 'MacBook Air 13" (M3)', price: 159 },
            { name: 'MacBook Pro 16" (M2)', price: 179 },
            { name: 'MacBook Pro 14" (M2)', price: 169 },
            { name: 'MacBook Air 13" (M2)', price: 149 },
            { name: 'iMac 24" (M3)', price: 189 },
            { name: 'Mac Studio (M2)', price: 219 },
            { name: 'Mac Pro (M2)', price: 299 }
        ]
    };
    
    const versions = defaultVersions[deviceType] || [];
    renderDeviceVersions(versions);
}

// Select device version
function selectVersion(versionName, price) {
    // Remove previous selection
    document.querySelectorAll('.version-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Add selection to clicked item
    const selectedItem = document.querySelector(`[data-version="${versionName}"]`);
    if (selectedItem) {
        selectedItem.classList.add('selected');
        // Check the radio button
        const radio = selectedItem.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
        }
    }
    
    selectedVersion = versionName;
    currentPrice = parseFloat(price);
    
    updateOrderSummary();
    showPaymentSection();
}

// Update order summary
function updateOrderSummary() {
    const orderSummary = document.getElementById('orderSummary');
    const selectedDeviceSpan = document.getElementById('selectedDevice');
    const selectedModelSpan = document.getElementById('selectedModel');
    const totalAmountSpan = document.getElementById('totalAmount');
    
    if (selectedDevice && selectedVersion) {
        selectedDeviceSpan.textContent = selectedDevice.charAt(0).toUpperCase() + selectedDevice.slice(1);
        selectedModelSpan.textContent = selectedVersion;
        totalAmountSpan.textContent = `$${currentPrice.toFixed(2)}`;
        
        orderSummary.style.display = 'block';
    } else {
        orderSummary.style.display = 'none';
    }
}

// Hide order summary
function hideOrderSummary() {
    document.getElementById('orderSummary').style.display = 'none';
}

// Show payment section
function showPaymentSection() {
    const paymentSection = document.getElementById('paymentSection');
    const usdtAmountSpan = document.getElementById('usdtAmount');
    
    if (currentPrice > 0) {
        usdtAmountSpan.textContent = `${currentPrice.toFixed(2)}`;
        paymentSection.style.display = 'block';
        generateQRCode();
    }
}

// Hide payment section
function hidePaymentSection() {
    document.getElementById('paymentSection').style.display = 'none';
}

// Load wallet address from admin settings
async function loadWalletAddress() {
    try {
        const response = await fetch('php/get_wallets.php');
        const data = await response.json();
        
        if (data.success && data.wallets.trc20) {
            usdtWalletAddress = data.wallets.trc20;
            document.getElementById('usdtWalletAddress').textContent = usdtWalletAddress;
            generateQRCode();
        }
    } catch (error) {
        console.error('Error loading wallet address:', error);
        // Use default address
        usdtWalletAddress = 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE';
    }
}

// Generate QR Code (simple implementation)
function generateQRCode() {
    const qrContainer = document.getElementById('qrCodeDisplay');
    
    if (usdtWalletAddress && currentPrice > 0) {
        // Create QR code URL using QR Server API
        const qrData = usdtWalletAddress;
        const qrSize = '150x150';
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${qrSize}&data=${encodeURIComponent(qrData)}`;
        
        qrContainer.innerHTML = `<img src="${qrUrl}" alt="USDT Wallet QR Code" style="width: 100%; height: 100%; object-fit: contain;">`;
    }
}

// Copy wallet address to clipboard
function copyWalletAddress() {
    const address = document.getElementById('usdtWalletAddress').textContent;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(address).then(() => {
            showNotification('Wallet address copied to clipboard!', 'success');
        }).catch(() => {
            fallbackCopyToClipboard(address);
        });
    } else {
        fallbackCopyToClipboard(address);
    }
}

// Fallback copy method
function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showNotification('Wallet address copied to clipboard!', 'success');
        } else {
            showNotification('Failed to copy address. Please copy manually.', 'error');
        }
    } catch (err) {
        showNotification('Failed to copy address. Please copy manually.', 'error');
    }

    document.body.removeChild(textArea);
}

// Handle form submission
async function handleFormSubmission(e) {
    e.preventDefault();
    
    if (!validateForm()) {
        return;
    }
    
    showLoading(true);
    
    try {
        const formData = collectFormData();
        
        const response = await fetch('php/submit_service_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Redirect to success page with order details
            const params = new URLSearchParams({
                order_id: result.order_id,
                device_type: selectedDevice,
                device_model: selectedVersion,
                amount: currentPrice,
                email: formData.customerEmail
            });
            
            window.location.href = `payment-success.html?${params.toString()}`;
        } else {
            showNotification(result.message || 'An error occurred while submitting your request.', 'error');
        }
    } catch (error) {
        console.error('Submission error:', error);
        showNotification('An error occurred while submitting your request. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}

// Validate form
function validateForm() {
    const customerEmail = document.getElementById('customerEmail').value.trim();
    const imeiSerial = document.getElementById('imeiSerial').value.trim();
    const transactionHash = document.getElementById('transactionHash').value.trim();
    
    if (!customerEmail || !isValidEmail(customerEmail)) {
        showNotification('Please enter a valid email address.', 'error');
        return false;
    }
    
    if (!selectedDevice) {
        showNotification('Please select a device type.', 'error');
        return false;
    }
    
    if (!selectedVersion) {
        showNotification('Please select a device model.', 'error');
        return false;
    }
    
    if (!imeiSerial) {
        showNotification('Please enter IMEI or Serial Number.', 'error');
        return false;
    }
    
    if (!transactionHash) {
        showNotification('Please enter the transaction hash.', 'error');
        return false;
    }
    
    return true;
}

// Collect form data
function collectFormData() {
    return {
        customerEmail: document.getElementById('customerEmail').value.trim(),
        deviceType: selectedDevice,
        deviceVersion: selectedVersion,
        imeiSerial: document.getElementById('imeiSerial').value.trim(),
        description: document.getElementById('description').value.trim(),
        transactionHash: document.getElementById('transactionHash').value.trim(),
        amount: currentPrice,
        walletAddress: usdtWalletAddress
    };
}

// Validate email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Show loading overlay
function showLoading(show) {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = show ? 'flex' : 'none';
    }
}

// Show notification
function showNotification(message, type) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create new notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Add notification styles if not exists
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 100px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                animation: slideIn 0.3s ease;
            }
            
            .notification-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            
            .notification-error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1rem;
            }
            
            .notification-content i:first-child {
                font-size: 1.2rem;
            }
            
            .notification-close {
                background: none;
                border: none;
                color: inherit;
                cursor: pointer;
                padding: 0.25rem;
                margin-left: auto;
            }
            
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @media (max-width: 768px) {
                .notification {
                    right: 10px;
                    left: 10px;
                    max-width: none;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Insert notification
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Export functions for global access
window.copyWalletAddress = copyWalletAddress;
window.selectVersion = selectVersion;