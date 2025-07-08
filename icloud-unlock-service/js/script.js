// Global variables
let selectedPaymentMethod = null;
let currentPrice = 0;
let stripe = null;
let elements = null;
let card = null;

// Pricing configuration
const pricing = {
    iphone: 89,
    ipad: 79,
    mac: 149
};

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize application
function initializeApp() {
    loadPricingFromAPI();
    setupEventListeners();
    initializeStripe();
    loadWalletAddresses();
}

// Load pricing from admin panel
async function loadPricingFromAPI() {
    try {
        const response = await fetch('php/get_pricing.php');
        const data = await response.json();
        
        if (data.success) {
            // Update pricing in memory and DOM
            Object.keys(data.pricing).forEach(device => {
                pricing[device] = parseFloat(data.pricing[device]);
                const priceElement = document.getElementById(`${device}-price`);
                if (priceElement) {
                    priceElement.textContent = pricing[device];
                }
            });
        }
    } catch (error) {
        console.log('Using default pricing values');
    }
}

// Load wallet addresses from admin panel
async function loadWalletAddresses() {
    try {
        const response = await fetch('php/get_wallets.php');
        const data = await response.json();
        
        if (data.success) {
            const trc20Element = document.getElementById('trc20Address');
            const erc20Element = document.getElementById('erc20Address');
            
            if (trc20Element && data.wallets.trc20) {
                trc20Element.textContent = data.wallets.trc20;
            }
            if (erc20Element && data.wallets.erc20) {
                erc20Element.textContent = data.wallets.erc20;
            }
        }
    } catch (error) {
        console.log('Using default wallet addresses');
    }
}

// Setup event listeners
function setupEventListeners() {
    // Mobile menu toggle
    const navMobile = document.querySelector('.nav-mobile');
    const navMenu = document.querySelector('.nav-menu');
    
    if (navMobile) {
        navMobile.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }

    // Device type selection
    const deviceTypeSelect = document.getElementById('deviceType');
    if (deviceTypeSelect) {
        deviceTypeSelect.addEventListener('change', updatePricing);
    }

    // Pricing card selection
    const pricingCards = document.querySelectorAll('.pricing-card');
    pricingCards.forEach(card => {
        card.addEventListener('click', function() {
            const deviceType = this.getAttribute('data-device');
            if (deviceType) {
                deviceTypeSelect.value = deviceType;
                updatePricing();
                
                // Scroll to form
                document.getElementById('unlock-form').scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Payment method selection
    const paymentOptions = document.querySelectorAll('.payment-option');
    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            selectPaymentMethod(this.getAttribute('data-method'));
        });
    });

    // Form submission
    const unlockForm = document.getElementById('unlockForm');
    if (unlockForm) {
        unlockForm.addEventListener('submit', handleFormSubmission);
    }

    // Smooth scrolling for navigation links
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Update pricing based on selected device
function updatePricing() {
    const deviceType = document.getElementById('deviceType').value;
    const selectedServiceElement = document.getElementById('selectedService');
    const totalPriceElement = document.getElementById('totalPrice');

    if (deviceType && pricing[deviceType]) {
        currentPrice = pricing[deviceType];
        selectedServiceElement.textContent = `${deviceType.charAt(0).toUpperCase() + deviceType.slice(1)} Unlock`;
        totalPriceElement.textContent = `$${currentPrice}`;
        
        // Update PayPal amount if PayPal is selected
        if (selectedPaymentMethod === 'paypal') {
            initializePayPal();
        }
    } else {
        selectedServiceElement.textContent = 'Please select a device type';
        totalPriceElement.textContent = '$0';
        currentPrice = 0;
    }
}

// Select payment method
function selectPaymentMethod(method) {
    // Remove active class from all options
    document.querySelectorAll('.payment-option').forEach(option => {
        option.classList.remove('active');
    });

    // Add active class to selected option
    const selectedOption = document.querySelector(`[data-method="${method}"]`);
    if (selectedOption) {
        selectedOption.classList.add('active');
    }

    // Hide all payment forms
    document.querySelectorAll('.payment-form').forEach(form => {
        form.style.display = 'none';
    });

    // Show selected payment form
    const paymentForm = document.getElementById(`${method}Payment`);
    if (paymentForm) {
        paymentForm.style.display = 'block';
    }

    selectedPaymentMethod = method;

    // Initialize payment method specific functionality
    switch (method) {
        case 'card':
            initializeStripeCard();
            break;
        case 'paypal':
            initializePayPal();
            break;
        case 'usdt':
            // USDT payment form is already displayed
            break;
    }
}

// Initialize Stripe
function initializeStripe() {
    if (typeof Stripe !== 'undefined') {
        stripe = Stripe('pk_test_YOUR_STRIPE_PUBLISHABLE_KEY'); // Replace with your actual publishable key
        elements = stripe.elements();
    }
}

// Initialize Stripe card element
function initializeStripeCard() {
    if (!stripe || !elements) {
        console.error('Stripe not initialized');
        return;
    }

    if (card) {
        card.destroy();
    }

    card = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#424770',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
        },
    });

    card.mount('#card-element');

    card.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
}

// Initialize PayPal
function initializePayPal() {
    if (typeof paypal === 'undefined' || currentPrice === 0) {
        return;
    }

    const paypalContainer = document.getElementById('paypal-button-container');
    paypalContainer.innerHTML = ''; // Clear existing buttons

    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: currentPrice.toString(),
                        currency_code: 'USD'
                    },
                    description: `iCloud unlock service for ${document.getElementById('deviceType').value}`
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                // Handle successful PayPal payment
                handlePaymentSuccess('paypal', {
                    paypal_order_id: data.orderID,
                    payer_email: details.payer.email_address,
                    payer_name: details.payer.name.given_name + ' ' + details.payer.name.surname,
                    amount: details.purchase_units[0].amount.value
                });
            });
        },
        onError: function(err) {
            console.error('PayPal Error:', err);
            showAlert('PayPal payment failed. Please try again.', 'error');
        }
    }).render('#paypal-button-container');
}

// Handle form submission
async function handleFormSubmission(e) {
    e.preventDefault();

    if (!validateForm()) {
        return;
    }

    if (!selectedPaymentMethod) {
        showAlert('Please select a payment method.', 'error');
        return;
    }

    showLoading(true);

    try {
        switch (selectedPaymentMethod) {
            case 'card':
                await handleStripePayment();
                break;
            case 'paypal':
                showAlert('Please complete PayPal payment above.', 'error');
                showLoading(false);
                break;
            case 'usdt':
                await handleUSDTPayment();
                break;
        }
    } catch (error) {
        console.error('Payment error:', error);
        showAlert('Payment processing failed. Please try again.', 'error');
        showLoading(false);
    }
}

// Validate form
function validateForm() {
    const deviceType = document.getElementById('deviceType').value;
    const imeiSerial = document.getElementById('imeiSerial').value.trim();
    const email = document.getElementById('email').value.trim();

    if (!deviceType) {
        showAlert('Please select a device type.', 'error');
        return false;
    }

    if (!imeiSerial) {
        showAlert('Please enter IMEI or Serial Number.', 'error');
        return false;
    }

    if (!email || !isValidEmail(email)) {
        showAlert('Please enter a valid email address.', 'error');
        return false;
    }

    if (selectedPaymentMethod === 'usdt') {
        const txHash = document.getElementById('txHash').value.trim();
        if (!txHash) {
            showAlert('Please enter the transaction hash for USDT payment.', 'error');
            return false;
        }
    }

    return true;
}

// Handle Stripe payment
async function handleStripePayment() {
    const { token, error } = await stripe.createToken(card);

    if (error) {
        showAlert(error.message, 'error');
        showLoading(false);
        return;
    }

    const formData = getFormData();
    formData.payment_method = 'card';
    formData.stripe_token = token.id;
    formData.amount = currentPrice;

    await submitForm(formData);
}

// Handle USDT payment
async function handleUSDTPayment() {
    const formData = getFormData();
    formData.payment_method = 'usdt';
    formData.tx_hash = document.getElementById('txHash').value.trim();
    formData.amount = currentPrice;

    await submitForm(formData);
}

// Handle payment success (for PayPal)
async function handlePaymentSuccess(method, paymentData) {
    const formData = getFormData();
    formData.payment_method = method;
    formData.payment_data = JSON.stringify(paymentData);
    formData.amount = currentPrice;

    await submitForm(formData);
}

// Get form data
function getFormData() {
    return {
        device_type: document.getElementById('deviceType').value,
        imei_serial: document.getElementById('imeiSerial').value.trim(),
        email: document.getElementById('email').value.trim(),
        description: document.getElementById('description').value.trim()
    };
}

// Submit form to backend
async function submitForm(formData) {
    try {
        const response = await fetch('php/process_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            showAlert('Your unlock request has been submitted successfully! You will receive a confirmation email shortly.', 'success');
            document.getElementById('unlockForm').reset();
            selectedPaymentMethod = null;
            currentPrice = 0;
            updatePricing();
            
            // Hide payment forms
            document.querySelectorAll('.payment-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Remove active class from payment options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('active');
            });
        } else {
            showAlert(result.message || 'An error occurred while processing your request.', 'error');
        }
    } catch (error) {
        console.error('Submission error:', error);
        showAlert('An error occurred while submitting your request. Please try again.', 'error');
    } finally {
        showLoading(false);
    }
}

// Copy wallet address to clipboard
function copyAddress(type) {
    const addressElement = document.getElementById(`${type}Address`);
    const address = addressElement.textContent;

    if (navigator.clipboard) {
        navigator.clipboard.writeText(address).then(() => {
            showAlert('Address copied to clipboard!', 'success');
        }).catch(() => {
            fallbackCopyToClipboard(address);
        });
    } else {
        fallbackCopyToClipboard(address);
    }
}

// Fallback copy to clipboard
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
            showAlert('Address copied to clipboard!', 'success');
        } else {
            showAlert('Failed to copy address. Please copy manually.', 'error');
        }
    } catch (err) {
        showAlert('Failed to copy address. Please copy manually.', 'error');
    }

    document.body.removeChild(textArea);
}

// Show loading overlay
function showLoading(show) {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = show ? 'flex' : 'none';
    }
}

// Show alert message
function showAlert(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());

    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;

    // Insert alert at the top of the form
    const form = document.querySelector('.form');
    if (form) {
        form.insertBefore(alert, form.firstChild);
    }

    // Auto-remove alert after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);

    // Scroll to alert
    alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Validate email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Smooth scrolling polyfill for older browsers
function smoothScrollTo(element) {
    const targetPosition = element.offsetTop - 100; // Account for fixed header
    const startPosition = window.pageYOffset;
    const distance = targetPosition - startPosition;
    const duration = 1000;
    let start = null;

    function animation(currentTime) {
        if (start === null) start = currentTime;
        const timeElapsed = currentTime - start;
        const run = ease(timeElapsed, startPosition, distance, duration);
        window.scrollTo(0, run);
        if (timeElapsed < duration) requestAnimationFrame(animation);
    }

    function ease(t, b, c, d) {
        t /= d / 2;
        if (t < 1) return c / 2 * t * t + b;
        t--;
        return -c / 2 * (t * (t - 2) - 1) + b;
    }

    requestAnimationFrame(animation);
}

// Mobile menu functionality
function toggleMobileMenu() {
    const navMenu = document.querySelector('.nav-menu');
    navMenu.classList.toggle('mobile-active');
}

// Add mobile menu styles
const style = document.createElement('style');
style.textContent = `
    @media (max-width: 768px) {
        .nav-menu {
            position: fixed;
            top: 80px;
            right: -100%;
            width: 100%;
            height: calc(100vh - 80px);
            background: white;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding-top: 2rem;
            transition: right 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-menu.mobile-active {
            right: 0;
        }
        
        .nav-menu a {
            padding: 1rem 2rem;
            font-size: 1.2rem;
            border-bottom: 1px solid #eee;
            width: 100%;
            text-align: center;
        }
    }
`;
document.head.appendChild(style);

// Initialize mobile menu
document.addEventListener('DOMContentLoaded', function() {
    const navMobile = document.querySelector('.nav-mobile');
    if (navMobile) {
        navMobile.addEventListener('click', toggleMobileMenu);
    }
    
    // Close mobile menu when clicking on a link
    const navLinks = document.querySelectorAll('.nav-menu a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            const navMenu = document.querySelector('.nav-menu');
            navMenu.classList.remove('mobile-active');
        });
    });
});

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Apply animation to elements
document.addEventListener('DOMContentLoaded', function() {
    const animatedElements = document.querySelectorAll('.service-card, .pricing-card, .step, .contact-item');
    
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// Add loading states to buttons
function addButtonLoading(button, loading) {
    if (loading) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    } else {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-lock-open"></i> Process Unlock Request';
    }
}

// Handle button loading state
document.addEventListener('DOMContentLoaded', function() {
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        const originalSubmitForm = submitForm;
        window.submitForm = async function(formData) {
            addButtonLoading(submitBtn, true);
            try {
                await originalSubmitForm(formData);
            } finally {
                addButtonLoading(submitBtn, false);
            }
        };
    }
});

// Export functions for global access
window.copyAddress = copyAddress;
window.selectPaymentMethod = selectPaymentMethod;