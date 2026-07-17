<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Water Guardian</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --text: #2d3748;
            --text-light: #718096;
            --border: #e2e8f0;
            --success: #48bb78;
            --error: #fc8181;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .register-container::-webkit-scrollbar {
            width: 5px;
        }

        .register-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .register-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header .icon {
            font-size: 50px;
            color: var(--primary);
        }

        .register-header h2 {
            color: var(--text);
            margin-top: 10px;
            font-weight: 700;
        }

        .register-header p {
            color: var(--text-light);
            font-size: 14px;
        }

        .form-step {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .form-step.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-indicators {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .step-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--border);
            transition: all 0.3s;
        }

        .step-indicator.active {
            background: var(--primary);
            transform: scale(1.2);
        }

        .step-indicator.completed {
            background: var(--success);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text);
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }

        .form-group .required {
            color: #e53e3e;
            margin-left: 2px;
        }

        .form-group .input-group {
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid var(--border);
            transition: all 0.3s;
        }

        .form-group .input-group:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .form-group .input-group.error {
            border-color: var(--error);
        }

        .form-group .input-group.success {
            border-color: var(--success);
        }

        .form-group .input-group-text {
            background: transparent;
            border: none;
            color: #a0aec0;
            padding: 0 15px;
        }

        .form-group .form-control {
            border: none;
            padding: 12px 15px;
            font-size: 15px;
        }

        .form-group .form-control:focus {
            box-shadow: none;
        }

        .form-group .form-control.is-invalid {
            border-color: var(--error);
        }

        .form-group .form-control.is-valid {
            border-color: var(--success);
        }

        .form-group .invalid-feedback {
            font-size: 12px;
            margin-top: 5px;
            color: var(--error);
        }

        .form-group .help-text {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
        }

        .password-strength {
            height: 4px;
            margin-top: 8px;
            border-radius: 2px;
            background: var(--border);
            overflow: hidden;
        }

        .password-strength .bar {
            height: 100%;
            border-radius: 2px;
            transition: all 0.3s;
            width: 0%;
        }

        .password-strength .bar.weak { width: 25%; background: #fc8181; }
        .password-strength .bar.medium { width: 50%; background: #f6ad55; }
        .password-strength .bar.strong { width: 75%; background: #68d391; }
        .password-strength .bar.very-strong { width: 100%; background: #48bb78; }

        .password-strength-text {
            font-size: 12px;
            margin-top: 5px;
            color: var(--text-light);
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-register:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-register:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-step {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-step.btn-next {
            background: var(--primary);
            color: white;
        }

        .btn-step.btn-next:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-step.btn-prev {
            background: var(--border);
            color: var(--text);
        }

        .btn-step.btn-prev:hover:not(:disabled) {
            background: #cbd5e0;
        }

        .btn-step:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            gap: 15px;
        }

        .alert-container {
            margin-top: 15px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-light);
        }

        .login-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .terms {
            font-size: 13px;
            color: var(--text-light);
            text-align: center;
            margin-top: 15px;
        }

        .terms a {
            color: var(--primary);
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        /* Phone input customization */
        .iti {
            width: 100%;
        }

        .iti__flag-container {
            padding-left: 10px;
        }

        .iti__selected-flag {
            padding: 0 10px;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .register-container {
                padding: 25px 20px;
            }

            .register-header .icon {
                font-size: 35px;
            }

            .register-header h2 {
                font-size: 22px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn-step {
                width: 100%;
            }
        }

        /* Loading spinner */
        .spinner-border-sm {
            width: 1.2rem;
            height: 1.2rem;
            border-width: 0.15em;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Header -->
        <div class="register-header">
            <div class="icon">💧</div>
            <h2>Create Account</h2>
            <p>Join Smart Water Guardian and start monitoring your water usage</p>
        </div>

        <!-- Step Indicators -->
        <div class="step-indicators">
            <span class="step-indicator active" data-step="1"></span>
            <span class="step-indicator" data-step="2"></span>
            <span class="step-indicator" data-step="3"></span>
        </div>

        <!-- Alert Container -->
        <div class="alert-container" id="alertContainer"></div>

        <!-- Registration Form -->
        <form id="registerForm" onsubmit="handleRegister(event)">
            
            <!-- Step 1: Personal Information -->
            <div class="form-step active" data-step="1">
                <h5 style="color: var(--text); font-weight: 600; margin-bottom: 20px;">
                    <i class="fas fa-user" style="color: var(--primary);"></i> Personal Information
                </h5>

                <div class="form-group">
                    <label>Name <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="name" 
                               placeholder="Enter your first name" required
                               minlength="2" maxlength="100">
                    </div>
                    <div class="invalid-feedback" id="nameFeedback"></div>
                </div>

                <div class="form-group">
                    <label>Surname <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                        <input type="text" class="form-control" id="surname" 
                               placeholder="Enter your surname" required
                               minlength="2" maxlength="100">
                    </div>
                    <div class="invalid-feedback" id="surnameFeedback"></div>
                </div>

                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" 
                               placeholder="Enter your email address" required>
                    </div>
                    <div class="invalid-feedback" id="emailFeedback"></div>
                </div>

                <div class="form-group">
                    <label>Phone Number <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="tel" class="form-control" id="phone" 
                               placeholder="Enter your phone number" required>
                    </div>
                    <div class="invalid-feedback" id="phoneFeedback"></div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-step btn-next" onclick="nextStep()">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2: Address & Details -->
            <div class="form-step" data-step="2">
                <h5 style="color: var(--text); font-weight: 600; margin-bottom: 20px;">
                    <i class="fas fa-home" style="color: var(--primary);"></i> Address & Details
                </h5>

                <div class="form-group">
                    <label>Physical Address <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                        <textarea class="form-control" id="address" 
                                  placeholder="Enter your full physical address" 
                                  required rows="3"></textarea>
                    </div>
                    <div class="invalid-feedback" id="addressFeedback"></div>
                </div>

                <div class="form-group">
                    <label>Property ID (Optional)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                        <input type="text" class="form-control" id="property_id" 
                               placeholder="e.g., PROP_001 (if you have one)">
                    </div>
                    <div class="help-text">If you already have a property registered, enter its ID here.</div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-step btn-prev" onclick="prevStep()">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn-step btn-next" onclick="nextStep()">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: Security -->
            <div class="form-step" data-step="3">
                <h5 style="color: var(--text); font-weight: 600; margin-bottom: 20px;">
                    <i class="fas fa-lock" style="color: var(--primary);"></i> Security
                </h5>

                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" 
                               placeholder="Create a strong password" required>
                        <span class="input-group-text password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </span>
                    </div>
                    <div class="help-text">
                        Must be at least 8 characters, with uppercase, lowercase, number, and special character.
                    </div>
                    <div class="password-strength">
                        <div class="bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="password-strength-text" id="passwordStrengthText"></div>
                    <div class="invalid-feedback" id="passwordFeedback"></div>
                </div>

                <div class="form-group">
                    <label>Confirm Password <span class="required">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                        <input type="password" class="form-control" id="confirm_password" 
                               placeholder="Confirm your password" required>
                    </div>
                    <div class="invalid-feedback" id="confirmFeedback"></div>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required>
                        <label class="form-check-label" for="termsCheck" style="font-weight:400; font-size:14px;">
                            I agree to the 
                            <a href="#" target="_blank">Terms & Conditions</a> 
                            and 
                            <a href="#" target="_blank">Privacy Policy</a>
                            <span class="required">*</span>
                        </label>
                    </div>
                    <div class="invalid-feedback" id="termsFeedback"></div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-step btn-prev" onclick="prevStep()">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn-register" id="registerBtn">
                        <span class="loading-spinner" id="loadingSpinner" style="display:none;">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                        <span id="registerText">Create Account</span>
                    </button>
                </div>
            </div>
        </form>

        <div class="login-link">
            Already have an account? <a href="?page=login">Sign in</a>
        </div>

        <div class="terms">
            By creating an account, you agree to our 
            <a href="#">Terms of Service</a> and 
            <a href="#">Privacy Policy</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    
    <script>
        // ============================================
        // STATE MANAGEMENT
        // ============================================
        let currentStep = 1;
        const totalSteps = 3;
        const formData = {};

        // ============================================
        // DOM ELEMENTS
        // ============================================
        const steps = document.querySelectorAll('.form-step');
        const indicators = document.querySelectorAll('.step-indicator');
        const alertContainer = document.getElementById('alertContainer');

        // ============================================
        // PHONE INPUT INITIALIZATION
        // ============================================
        const phoneInput = document.querySelector("#phone");
        const iti = window.intlTelInput(phoneInput, {
            initialCountry: "za",
            preferredCountries: ["za", "us", "gb"],
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
        });

        // ============================================
        // STEP NAVIGATION
        // ============================================
        function goToStep(step) {
            // Validate current step before proceeding
            if (step > currentStep) {
                if (!validateStep(currentStep)) {
                    return;
                }
                // Save current step data
                saveStepData(currentStep);
            }

            currentStep = step;
            
            // Update steps
            steps.forEach((s, index) => {
                s.classList.toggle('active', index + 1 === currentStep);
            });
            
            // Update indicators
            indicators.forEach((ind, index) => {
                ind.classList.remove('active', 'completed');
                if (index + 1 === currentStep) {
                    ind.classList.add('active');
                } else if (index + 1 < currentStep) {
                    ind.classList.add('completed');
                }
            });

            // Clear alerts
            clearAlerts();
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                goToStep(currentStep + 1);
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                goToStep(currentStep - 1);
            }
        }

        // ============================================
        // STEP VALIDATION
        // ============================================
        function validateStep(step) {
            let isValid = true;

            switch(step) {
                case 1: // Personal Information
                    isValid = validatePersonalInfo();
                    break;
                case 2: // Address & Details
                    isValid = validateAddress();
                    break;
                case 3: // Security
                    isValid = validateSecurity();
                    break;
            }

            return isValid;
        }

        function validatePersonalInfo() {
            let isValid = true;

            // Name
            const name = document.getElementById('name');
            const nameFeedback = document.getElementById('nameFeedback');
            if (!name.value.trim() || name.value.trim().length < 2) {
                name.classList.add('is-invalid');
                nameFeedback.textContent = 'Please enter your full name (minimum 2 characters)';
                isValid = false;
            } else {
                name.classList.remove('is-invalid');
                name.classList.add('is-valid');
                nameFeedback.textContent = '';
            }

            // Surname
            const surname = document.getElementById('surname');
            const surnameFeedback = document.getElementById('surnameFeedback');
            if (!surname.value.trim() || surname.value.trim().length < 2) {
                surname.classList.add('is-invalid');
                surnameFeedback.textContent = 'Please enter your surname (minimum 2 characters)';
                isValid = false;
            } else {
                surname.classList.remove('is-invalid');
                surname.classList.add('is-valid');
                surnameFeedback.textContent = '';
            }

            // Email
            const email = document.getElementById('email');
            const emailFeedback = document.getElementById('emailFeedback');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email.value.trim() || !emailRegex.test(email.value.trim())) {
                email.classList.add('is-invalid');
                emailFeedback.textContent = 'Please enter a valid email address';
                isValid = false;
            } else {
                email.classList.remove('is-invalid');
                email.classList.add('is-valid');
                emailFeedback.textContent = '';
            }

            // Phone
            const phone = document.getElementById('phone');
            const phoneFeedback = document.getElementById('phoneFeedback');
            const phoneNumber = iti.getNumber();
            if (!phoneNumber || phoneNumber.length < 10) {
                phone.classList.add('is-invalid');
                phoneFeedback.textContent = 'Please enter a valid phone number';
                isValid = false;
            } else {
                phone.classList.remove('is-invalid');
                phone.classList.add('is-valid');
                phoneFeedback.textContent = '';
            }

            return isValid;
        }

        function validateAddress() {
            let isValid = true;

            // Address
            const address = document.getElementById('address');
            const addressFeedback = document.getElementById('addressFeedback');
            if (!address.value.trim() || address.value.trim().length < 5) {
                address.classList.add('is-invalid');
                addressFeedback.textContent = 'Please enter your full physical address (minimum 5 characters)';
                isValid = false;
            } else {
                address.classList.remove('is-invalid');
                address.classList.add('is-valid');
                addressFeedback.textContent = '';
            }

            return isValid;
        }

        function validateSecurity() {
            let isValid = true;

            // Password
            const password = document.getElementById('password');
            const passwordFeedback = document.getElementById('passwordFeedback');
            const passwordValue = password.value;
            
            if (passwordValue.length < 8) {
                password.classList.add('is-invalid');
                passwordFeedback.textContent = 'Password must be at least 8 characters';
                isValid = false;
            } else if (!/[A-Z]/.test(passwordValue)) {
                password.classList.add('is-invalid');
                passwordFeedback.textContent = 'Password must contain at least one uppercase letter';
                isValid = false;
            } else if (!/[a-z]/.test(passwordValue)) {
                password.classList.add('is-invalid');
                passwordFeedback.textContent = 'Password must contain at least one lowercase letter';
                isValid = false;
            } else if (!/[0-9]/.test(passwordValue)) {
                password.classList.add('is-invalid');
                passwordFeedback.textContent = 'Password must contain at least one number';
                isValid = false;
            } else if (!/[!@#$%^&*]/.test(passwordValue)) {
                password.classList.add('is-invalid');
                passwordFeedback.textContent = 'Password must contain at least one special character (!@#$%^&*)';
                isValid = false;
            } else {
                password.classList.remove('is-invalid');
                password.classList.add('is-valid');
                passwordFeedback.textContent = '';
            }

            // Confirm Password
            const confirm = document.getElementById('confirm_password');
            const confirmFeedback = document.getElementById('confirmFeedback');
            if (confirm.value !== password.value) {
                confirm.classList.add('is-invalid');
                confirmFeedback.textContent = 'Passwords do not match';
                isValid = false;
            } else {
                confirm.classList.remove('is-invalid');
                confirm.classList.add('is-valid');
                confirmFeedback.textContent = '';
            }

            // Terms
            const terms = document.getElementById('termsCheck');
            const termsFeedback = document.getElementById('termsFeedback');
            if (!terms.checked) {
                termsFeedback.textContent = 'You must agree to the Terms & Conditions';
                isValid = false;
            } else {
                termsFeedback.textContent = '';
            }

            return isValid;
        }

        // ============================================
        // SAVE STEP DATA
        // ============================================
        function saveStepData(step) {
            switch(step) {
                case 1:
                    formData.name = document.getElementById('name').value.trim();
                    formData.surname = document.getElementById('surname').value.trim();
                    formData.email = document.getElementById('email').value.trim();
                    formData.phone = iti.getNumber();
                    break;
                case 2:
                    formData.address = document.getElementById('address').value.trim();
                    formData.property_id = document.getElementById('property_id').value.trim() || null;
                    break;
                case 3:
                    formData.password = document.getElementById('password').value;
                    break;
            }
        }

        // ============================================
        // PASSWORD STRENGTH CHECKER
        // ============================================
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const bar = document.getElementById('passwordStrengthBar');
            const text = document.getElementById('passwordStrengthText');
            
            let strength = 0;
            let label = '';
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[!@#$%^&*]/.test(password)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                    label = 'Weak';
                    bar.className = 'bar weak';
                    break;
                case 2:
                case 3:
                    label = 'Medium';
                    bar.className = 'bar medium';
                    break;
                case 4:
                    label = 'Strong';
                    bar.className = 'bar strong';
                    break;
                case 5:
                    label = 'Very Strong';
                    bar.className = 'bar very-strong';
                    break;
            }
            
            if (password.length === 0) {
                text.textContent = '';
                bar.style.width = '0%';
            } else {
                text.textContent = `Password Strength: ${label}`;
            }
        });

        // ============================================
        // TOGGLE PASSWORD VISIBILITY
        // ============================================
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('passwordIcon');
            if (password.type === 'password') {
                password.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // ============================================
        // FORM SUBMISSION
        // ============================================
        async function handleRegister(e) {
            e.preventDefault();
            
            // Validate final step
            if (!validateStep(3)) {
                return;
            }

            // Get all form data
            const registerData = {
                name: formData.name || document.getElementById('name').value.trim(),
                surname: formData.surname || document.getElementById('surname').value.trim(),
                email: formData.email || document.getElementById('email').value.trim(),
                phone: formData.phone || iti.getNumber(),
                address: formData.address || document.getElementById('address').value.trim(),
                password: formData.password || document.getElementById('password').value,
                property_id: formData.property_id || document.getElementById('property_id').value.trim() || null
            };

            // Show loading state
            const btn = document.getElementById('registerBtn');
            const text = document.getElementById('registerText');
            const spinner = document.getElementById('loadingSpinner');
            btn.disabled = true;
            text.textContent = ' Creating Account...';
            spinner.style.display = 'inline';

            try {
                const response = await fetch('/api/modules/auth/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(registerData)
                });

                const data = await response.json();

                if (data.success) {
                    // Store token and user data
                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));
                    
                    showAlert(data.message || 'Registration successful!', 'success');
                    
                    // Redirect to dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    if (data.errors) {
                        // Show field-specific errors
                        showFieldErrors(data.errors);
                    }
                    showAlert(data.message || 'Registration failed', 'danger');
                }
            } catch (error) {
                showAlert('Network error. Please check your connection and try again.', 'danger');
                console.error('Registration error:', error);
            } finally {
                // Reset button state
                btn.disabled = false;
                text.textContent = 'Create Account';
                spinner.style.display = 'none';
            }
        }

        // ============================================
        // SHOW FIELD ERRORS
        // ============================================
        function showFieldErrors(errors) {
            for (const [field, messages] of Object.entries(errors)) {
                const fieldMap = {
                    'name': 'name',
                    'surname': 'surname',
                    'email': 'email',
                    'phone': 'phone',
                    'address': 'address',
                    'password': 'password'
                };
                
                const elementId = fieldMap[field];
                if (elementId) {
                    const element = document.getElementById(elementId);
                    const feedback = document.getElementById(elementId + 'Feedback');
                    if (element && feedback) {
                        element.classList.add('is-invalid');
                        feedback.textContent = messages[0] || messages;
                    }
                }
            }
        }

        // ============================================
        // SHOW ALERT
        // ============================================
        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const alertClass = `alert-${type}`;
            const icon = type === 'success' ? '✅' : type === 'danger' ? '❌' : 'ℹ️';
            
            container.innerHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 20px;">${icon}</span>
                        <span>${message}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }
            }, 5000);
        }

        function clearAlerts() {
            const container = document.getElementById('alertContainer');
            container.innerHTML = '';
        }

        // ============================================
        // REAL-TIME VALIDATION
        // ============================================
        // Name validation on input
        document.getElementById('name').addEventListener('input', function() {
            const feedback = document.getElementById('nameFeedback');
            if (this.value.trim().length >= 2) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                feedback.textContent = '';
            } else {
                this.classList.remove('is-valid');
                if (this.value.trim().length > 0) {
                    this.classList.add('is-invalid');
                    feedback.textContent = 'Minimum 2 characters required';
                }
            }
        });

        // Email validation on input
        document.getElementById('email').addEventListener('input', function() {
            const feedback = document.getElementById('emailFeedback');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(this.value.trim())) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                feedback.textContent = '';
            } else {
                this.classList.remove('is-valid');
                if (this.value.trim().length > 0) {
                    this.classList.add('is-invalid');
                    feedback.textContent = 'Please enter a valid email address';
                }
            }
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password');
            const feedback = document.getElementById('confirmFeedback');
            if (this.value === password.value && this.value.length > 0) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                feedback.textContent = '';
            } else {
                this.classList.remove('is-valid');
                if (this.value.length > 0) {
                    this.classList.add('is-invalid');
                    feedback.textContent = 'Passwords do not match';
                }
            }
        });

        // ============================================
        // KEYBOARD SHORTCUTS
        // ============================================
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter to submit
            if (e.ctrlKey && e.key === 'Enter') {
                if (currentStep === totalSteps) {
                    document.getElementById('registerForm').dispatchEvent(new Event('submit'));
                } else {
                    nextStep();
                }
            }
            // Right arrow to go next
            if (e.key === 'ArrowRight' && !e.ctrlKey && !e.altKey) {
                if (currentStep < totalSteps) {
                    e.preventDefault();
                    nextStep();
                }
            }
            // Left arrow to go back
            if (e.key === 'ArrowLeft' && !e.ctrlKey && !e.altKey) {
                if (currentStep > 1) {
                    e.preventDefault();
                    prevStep();
                }
            }
        });

        // ============================================
        // CHECK IF ALREADY LOGGED IN
        // ============================================
        if (localStorage.getItem('token')) {
            window.location.href = 'index.php';
        }

        console.log('Smart Water Guardian Registration Page Loaded');
    </script>
</body>
</html>
