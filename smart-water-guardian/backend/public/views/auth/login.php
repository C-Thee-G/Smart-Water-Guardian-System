<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Water Guardian</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header .icon {
            font-size: 50px;
            color: #667eea;
        }
        .login-header h2 {
            color: #2d3748;
            margin-top: 10px;
            font-weight: 700;
        }
        .login-header p {
            color: #718096;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .form-group .input-group {
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }
        .form-group .input-group:focus-within {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        .form-group .input-group-text {
            background: transparent;
            border: none;
            color: #a0aec0;
        }
        .form-group .form-control {
            border: none;
            padding: 12px 15px;
            font-size: 15px;
        }
        .form-group .form-control:focus {
            box-shadow: none;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .alert-container {
            margin-top: 15px;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #718096;
        }
        .register-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .password-toggle {
            cursor: pointer;
        }
        .loading-spinner {
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon">💧</div>
            <h2>Smart Water Guardian</h2>
            <p>Sign in to monitor your water usage</p>
        </div>
        
        <form id="loginForm" onsubmit="handleLogin(event)">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" 
                           placeholder="Enter your email" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" 
                           placeholder="Enter your password" required>
                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordIcon"></i>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="rememberMe">
                    <label class="form-check-label" for="rememberMe" style="font-weight:400; font-size:14px;">
                        Remember me
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-login" id="loginBtn">
                <span class="loading-spinner" id="loadingSpinner">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                <span id="loginText">Sign In</span>
            </button>
        </form>
        
        <div class="alert-container" id="alertContainer"></div>
        
        <div class="register-link">
            Don't have an account? <a href="?page=register">Create one now</a>
        </div>
    </div>

    <script>
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

        async function handleLogin(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const rememberMe = document.getElementById('rememberMe').checked;
            
            if (!email || !password) {
                showAlert('Please fill in all fields', 'warning');
                return;
            }
            
            // Show loading state
            const btn = document.getElementById('loginBtn');
            const text = document.getElementById('loginText');
            const spinner = document.getElementById('loadingSpinner');
            btn.disabled = true;
            text.textContent = ' Signing in...';
            spinner.style.display = 'inline';
            
            try {
                const response = await fetch('/api/modules/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Store token and user data
                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));
                    if (rememberMe) {
                        localStorage.setItem('rememberMe', 'true');
                    }
                    
                    showAlert('Login successful! Redirecting...', 'success');
                    
                    // Redirect based on role
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    showAlert(data.message || 'Login failed', 'danger');
                }
            } catch (error) {
                showAlert('Network error. Please try again.', 'danger');
                console.error('Login error:', error);
            } finally {
                // Reset button state
                btn.disabled = false;
                text.textContent = 'Sign In';
                spinner.style.display = 'none';
            }
        }

        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const alertClass = `alert-${type}`;
            container.innerHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) alert.remove();
            }, 5000);
        }

        // Check if already logged in
        if (localStorage.getItem('token')) {
            window.location.href = 'index.php';
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
