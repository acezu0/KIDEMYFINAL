<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kidemy Login and Account Creation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom Styles for a clean, educational green theme */
        :root {
            --kidemy-green: #4CAF50; /* A vibrant, but friendly green */
            --kidemy-light-green: #81C784;
            --kidemy-background-light: #F7FBF7;
            --kidemy-text-dark: #2C3E50;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--kidemy-background-light);
            /* Centering classes are applied in the body tag below */
        }

        .split-card {
            background-color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            max-width: 900px;
            width: 90%;
            min-height: 550px;
            margin: 20px;
        }

        .form-side {
            padding: 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .illustration-side {
            flex: 1;
            background-color: var(--kidemy-light-green); /* Light green background */
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .form-container {
            display: none;
        }
        .form-container.active {
            display: block;
        }

        /* Green Button Styles */
        .kidemy-button {
            background-color: var(--kidemy-green);
            color: white;
            font-weight: 600;
            transition: background-color 0.2s, transform 0.2s;
        }
        .kidemy-button:hover {
            background-color: #388E3C; /* Darker green on hover */
            transform: translateY(-1px);
        }

        /* Input Styles */
        .kidemy-input {
            border: 1px solid #D1D5DB;
            background-color: #F9FAFB;
            color: var(--kidemy-text-dark);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .kidemy-input:focus {
            border-color: var(--kidemy-green);
            box-shadow: 0 0 0 1px var(--kidemy-green);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .split-card {
                flex-direction: column;
                min-height: auto;
            }
            .illustration-side {
                order: -1; /* Move illustration side to the top on mobile */
                height: 200px;
                border-radius: 20px 20px 0 0;
            }
            .form-side {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="split-card">
        
        <!-- Form Side (Left) -->
        <div class="form-side">
            <!-- App Logo/Title -->
            <div class="mb-8">
                <div class="text-3xl font-extrabold text-gray-800">
                    <span style="color: var(--kidemy-green);">KIDEMY</span>
                </div>
            </div>

            <!-- Login Form -->
            <div id="login-view" class="form-container active">
                <h2 class="text-4xl font-bold mb-2 text-gray-900">Welcome Back!</h2>
                <p class="text-gray-500 mb-6">Log in to continue your learning journey.</p>
                <form id="login-form">
                    <div class="mb-4">
                        <label for="login-email" class="block text-gray-700 text-sm font-medium mb-1">Email Address</label>
                        <input type="email" id="login-email" class="kidemy-input w-full px-4 py-3 rounded-lg focus:outline-none" placeholder="student@kidemy.edu" required>
                    </div>
                    <div class="mb-6">
                        <label for="login-password" class="block text-gray-700 text-sm font-medium mb-1">Password</label>
                        <input type="password" id="login-password" class="kidemy-input w-full px-4 py-3 rounded-lg focus:outline-none" placeholder="••••••••" required>
                        <a href="#" class="text-xs text-gray-500 hover:text-gray-700 float-right mt-1">Forgot Password?</a>
                    </div>
                    <button type="submit" class="kidemy-button w-full py-3 rounded-lg">
                        Login
                    </button>
                    <p class="text-center mt-6 text-gray-500 text-sm">
                        Don't have an account yet? <a href="#" id="show-register" class="text-green-600 hover:text-green-700 font-medium">Create one now</a>
                    </p>
                </form>
            </div>

            <!-- Registration Form -->
            <div id="register-view" class="form-container">
                <h2 class="text-4xl font-bold mb-2 text-gray-900">Join Kidemy!</h2>
                <p class="text-gray-500 mb-6">Start learning with our world-class platform.</p>
                <form id="register-form">
                    <div class="mb-4">
                        <label for="register-email" class="block text-gray-700 text-sm font-medium mb-1">Email</label>
                        <input type="email" id="register-email" class="kidemy-input w-full px-4 py-3 rounded-lg focus:outline-none" placeholder="your.name@email.com" required>
                    </div>
                    <div class="mb-4">
                        <label for="name" class="block text-gray-700 text-sm font-medium mb-1">Full Name</label>
                        <input type="text" id="name" class="kidemy-input w-full px-4 py-3 rounded-lg focus:outline-none" required>
                    </div>
                    <!-- NEW ROLE FIELD -->
                    <div class="mb-4">
                        <label for="role" class="block text-gray-700 text-sm font-medium mb-1">I am a...</label>
                        <select id="role" class="kidemy-input w-full px-4 py-3 rounded-lg focus:outline-none" required>
                            <option value="" disabled selected>Select your role</option>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>
                    <!-- END NEW ROLE FIELD -->
                    <div class="mb-6">
                        <label for="register-password" class="block text-gray-700 text-sm font-medium mb-1">Password</label>
                        <input type="password" id="register-password" class="kidemy-input w-full px-4 py-3 rounded-lg focus:outline-none" placeholder="••••••••" required>
                        <p class="text-xs text-gray-500 mt-1">8+ chars, upper & lower case, and a number required.</p>
                    </div>
                    <button type="submit" class="kidemy-button w-full py-3 rounded-lg">
                        Create Account
                    </button>
                    <p class="text-center mt-6 text-gray-500 text-sm">
                        Already have an account? <a href="#" id="show-login" class="text-green-600 hover:text-green-700 font-medium">Sign In</a>
                    </p>
                </form>
            </div>

            <!-- OTP Verification View -->
            <div id="otp-view" class="form-container">
                <h2 class="text-4xl font-bold mb-2 text-gray-900">Verify Email</h2>
                <p class="text-gray-500 mb-6">We sent a 6-digit code to your email. Please check your inbox.</p>
                <form id="otp-form">
                    <div class="mb-6">
                        <label for="otp" class="block text-gray-700 text-sm font-medium mb-1">One-Time Pin</label>
                        <input type="text" id="otp" maxlength="6" class="kidemy-input w-full px-4 py-3 rounded-lg text-center tracking-widest text-xl font-mono focus:outline-none" placeholder="Enter 6 digits" required>
                    </div>
                    <button type="submit" class="kidemy-button w-full py-3 rounded-lg">
                        Confirm Verification
                    </button>
                </form>
            </div>

            <!-- Message Display -->
            <div id="message" class="mt-8 text-center text-sm font-medium transition-colors duration-300 rounded-lg p-3"></div>
        </div>
        
        <!-- Illustration Side (Right) -->
        <div class="illustration-side">
             <!-- Simple SVG for a book/graduation cap icon as a placeholder for a logo -->
            <svg class="w-24 h-24 text-white mb-4" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 3L1 9l11 6 11-6-11-6zm0 14.28L4.05 13l-1.07.57L12 21l9.02-4.15-1.07-.57L12 17.28zM12 15L2.9 9.87 12 4.74l9.1 5.13L12 15z"/>
                <path d="M12 17.28L12 21l9.02-4.15-1.07-.57L12 17.28z"/>
            </svg>
            <p class="text-white text-xl font-bold mb-2">Easy Navigation & Interactive Learning</p>
            <p class="text-white text-opacity-80 text-center px-4">Explore educational content with simple controls and engaging activities designed for kids.</p>
        </div>
    </div>

    <script>
        const loginView = document.getElementById('login-view');
        const registerView = document.getElementById('register-view');
        const otpView = document.getElementById('otp-view');

        const showRegisterLink = document.getElementById('show-register');
        const showLoginLink = document.getElementById('show-login');

        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const otpForm = document.getElementById('otp-form');

        const messageDiv = document.getElementById('message');

        // Utility to show messages
        function showMessage(text, isError = false) {
            messageDiv.textContent = text;
            if (isError) {
                messageDiv.className = 'mt-8 text-center text-sm font-medium transition-colors duration-300 bg-red-100 text-red-700 rounded-lg p-3 border border-red-300';
            } else {
                messageDiv.className = 'mt-8 text-center text-sm font-medium transition-colors duration-300 bg-green-100 text-green-700 rounded-lg p-3 border border-green-300';
            }
        }

        function showView(view) {
            loginView.classList.remove('active');
            registerView.classList.remove('active');
            otpView.classList.remove('active');
            view.classList.add('active');
            messageDiv.textContent = '';
            messageDiv.className = 'mt-8 text-center text-sm font-medium transition-colors duration-300';
        }

        showRegisterLink.addEventListener('click', (e) => {
            e.preventDefault();
            showView(registerView);
        });

        showLoginLink.addEventListener('click', (e) => {
            e.preventDefault();
            showView(loginView);
        });

        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            // Simulated successful login
            showMessage(`Success! Welcome back, ${email.split('@')[0]}.`, false);
        });

        registerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            // Removed birthday/age logic
            const role = document.getElementById('role').value;
            const password = document.getElementById('register-password').value;

            // 1. Role Check
            if (!role) {
                showMessage('Please select your role (Student or Teacher).', true);
                return;
            }
            
            // 2. Password Check: 8+ characters, one lowercase, one uppercase, one digit.
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
            if (!passwordRegex.test(password)) {
                showMessage('Password must be 8+ characters, including upper case, lower case, and a number.', true);
                return;
            }

            showMessage('Account details validated. Sending OTP for email verification...', false);
            
            // Simulate delay for OTP and transition to OTP view
            setTimeout(() => {
                showView(otpView);
            }, 1000);
        });

        otpForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const otp = document.getElementById('otp').value;
            
            if (otp === '543210') { // Mock correct OTP
                showMessage('Verification successful! Your Kidemy account is ready. Please log in.', false);
                // Clear form fields for security
                registerForm.reset();
                setTimeout(() => {
                     showView(loginView);
                }, 1500);
               
            } else {
                showMessage('Invalid verification code. Please try again.', true);
            }
        });
    </script>
</body>
</html>
