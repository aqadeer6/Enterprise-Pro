// Global variables
let isLoggedIn = false;
let isAdminLoggedIn = false;
let logs = [];
let pendingUsers = [];
let isHighContrast = false;
let fontSizeLevel = 1; // 0 = Small (12px), 1 = Normal (16px, default), 2 = Large (20px)

// Function to control individual asset upload visibility
function updateIndividualAssetVisibility() {
    const individualUpload = document.getElementById('individual-upload');
    if (individualUpload) {
        const shouldShow = typeof isLoggedIn !== 'undefined' && isLoggedIn && !isAdminLoggedIn;
        const currentDisplay = window.getComputedStyle(individualUpload).display;
        individualUpload.style.display = shouldShow ? 'block' : 'none';
        console.log(`Updating individual asset visibility - isLoggedIn: ${isLoggedIn}, isAdminLoggedIn: ${isAdminLoggedIn}, ShouldShow: ${shouldShow}, CurrentDisplay: ${currentDisplay}, NewDisplay: ${shouldShow ? 'block' : 'none'}`);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM fully loaded, initializing script');

    // Initialize from localStorage
    isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
    isAdminLoggedIn = localStorage.getItem('isAdminLoggedIn') === 'true';
    isHighContrast = localStorage.getItem('isHighContrast') === 'true';
    fontSizeLevel = parseInt(localStorage.getItem('fontSizeLevel')) || 1;
    console.log(`Initial state - isLoggedIn: ${isLoggedIn}, isAdminLoggedIn: ${isAdminLoggedIn}, isHighContrast: ${isHighContrast}, fontSizeLevel: ${fontSizeLevel}`);

    updateNavMenu();
    updateIndividualAssetVisibility();
    applyFontSize();

    const currentPage = window.location.pathname.split('/').pop().replace('.php', '').replace('.html', '') || 'index';
    if (!isLoggedIn && !isAdminLoggedIn && currentPage !== 'index' && currentPage !== 'privacy-policy') {
        alert("Please log in to access this section.");
        window.location.href = '../index.php';
        return;
    }

    if (currentPage === 'index' && document.getElementById('home')) {
        showSection('home');
        const loginBox = document.getElementById('login-box');
        if (loginBox) loginBox.style.display = (isLoggedIn || isAdminLoggedIn) ? 'none' : 'block';

        const loginCredentials = document.getElementById('login-credentials');
        const loginPassword = document.getElementById('login-password');
        if (loginCredentials && loginPassword) {
            [loginCredentials, loginPassword].forEach(input => {
                input.addEventListener('keypress', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        login();
                    }
                });
            });
        }
    } else if (document.getElementById(currentPage)) {
        if (currentPage === 'admin' && !isAdminLoggedIn) {
            alert("Access denied. Admin login required.");
            window.location.href = '../index.php';
            return;
        }
        showSection(currentPage);
        if (currentPage === 'admin') fetchPendingUsers();
    }

    const navLinks = document.querySelectorAll('nav ul li a');
    navLinks.forEach(link => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const href = link.getAttribute('href');
            if (href && href !== '#') window.location.href = href;
            else {
                const action = link.getAttribute('onclick');
                if (action) eval(action);
            }
        });
    });

    const dropdownItems = document.querySelectorAll('#profileDropdown a');
    dropdownItems.forEach(item => {
        item.addEventListener('click', (event) => {
            event.preventDefault();
            const action = item.getAttribute('onclick');
            if (action) eval(action);
        });
    });

    const accountLink = document.querySelector('#userProfile a');
    if (accountLink) accountLink.addEventListener('click', toggleDropdown);

    const contrastToggle = document.getElementById('contrast-toggle');
    if (contrastToggle) {
        contrastToggle.addEventListener('click', toggleContrast);
        if (isHighContrast) {
            document.body.classList.add('high-contrast');
            contrastToggle.textContent = 'Normal Contrast';
            applyHighContrast();
        }
    }

    const fontSizeToggle = document.getElementById('font-size-toggle');
    if (fontSizeToggle) {
        fontSizeToggle.addEventListener('click', toggleFontSize);
        updateFontSizeButtonText();
    }

    document.addEventListener('click', (event) => {
        const dropdown = document.getElementById('profileDropdown');
        const accountLink = document.querySelector('#userProfile a');
        if (dropdown && accountLink) {
            const isClickInsideDropdown = dropdown.contains(event.target);
            const isClickOnAccountLink = accountLink.contains(event.target);
            if (!isClickInsideDropdown && !isClickOnAccountLink && dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
                console.log('Dropdown closed due to outside click');
            }
        }
    });
});

function showSection(sectionId) {
    console.log(`Showing section: ${sectionId}, isAdminLoggedIn: ${isAdminLoggedIn}, isLoggedIn: ${isLoggedIn}`);
    const sections = document.querySelectorAll(".section");
    sections.forEach(section => section.style.display = "none");

    const currentPage = window.location.pathname.split('/').pop().replace('.php', '').replace('.html', '') || 'index';
    if (sectionId !== currentPage && currentPage === 'index') {
        document.getElementById(sectionId).style.display = "block";
    } else {
        document.getElementById(sectionId).style.display = "block";
    }

    if (sectionId === 'home') {
        const loginBox = document.getElementById('login-box');
        const welcomeMessage = document.getElementById('welcome-message');
        const welcomeText = document.getElementById('welcome-text');
        const signupBox = document.getElementById('signup-box');
        const forgotBox = document.getElementById('forgot-box');

        if (loginBox && welcomeMessage && welcomeText && signupBox && forgotBox) {
            // Hide all forms when logged in, show welcome message
            if (isLoggedIn || isAdminLoggedIn) {
                loginBox.style.display = 'none';
                signupBox.style.display = 'none';
                forgotBox.style.display = 'none';
                welcomeMessage.style.display = 'block';
                if (isAdminLoggedIn) {
                    welcomeText.textContent = 'Hello Admin';
                } else {
                    const username = localStorage.getItem('username') || 'User';
                    welcomeText.textContent = `Welcome ${username}`;
                }
            } else {
                // Show login form by default when not logged in, hide welcome message
                loginBox.style.display = 'block';
                signupBox.style.display = 'none';
                forgotBox.style.display = 'none';
                welcomeMessage.style.display = 'none';
            }
        }
    }

    updateNavMenu();
    if (isHighContrast) applyHighContrast();
    applyFontSize();
    if (sectionId === 'csv-upload') updateIndividualAssetVisibility();
}

function login() {
    const credentials = document.getElementById('login-credentials').value;
    const password = document.getElementById('login-password').value;
    const emailError = document.getElementById('login-error');
    const passwordError = document.getElementById('password-error');

    if (!credentials) {
        showErrorMessage(emailError, 'Enter a valid email or username');
        return;
    }
    emailError.style.display = 'none';

    if (!isValidPassword(password)) {
        showErrorMessage(passwordError, 'Password must be at least 8 characters with an uppercase letter, a number, and a special character');
        return;
    }
    passwordError.style.display = 'none';

    fetch('backend/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=login&credentials=${encodeURIComponent(credentials)}&password=${encodeURIComponent(password)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            isLoggedIn = true;
            isAdminLoggedIn = data.role === 'admin';
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('isAdminLoggedIn', data.role === 'admin' ? 'true' : 'false');
            localStorage.setItem('username', data.username); // Store username
            console.log(`Login - isLoggedIn: ${isLoggedIn}, isAdminLoggedIn: ${isAdminLoggedIn}, User: ${credentials}`);

            document.getElementById('login-box').style.display = 'none';
            updateNavMenu();
            logAction(`User ${credentials} logged in at ${new Date().toISOString()}`);
            showSection('home');
            applyFontSize();
            updateIndividualAssetVisibility();
        } else {
            showErrorMessage(emailError, data.message || 'Invalid email, username, or password');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage(emailError, 'Login failed');
    });
}

function updateNavMenu() {
    const csvLink = document.querySelector('.csv-link');
    const gisLink = document.querySelector('.gis-link');
    const adminLink = document.querySelector('.admin-link');
    const userProfile = document.getElementById('userProfile');

    if (csvLink) csvLink.style.display = (isLoggedIn || isAdminLoggedIn) ? 'block' : 'none';
    if (gisLink) gisLink.style.display = (isLoggedIn || isAdminLoggedIn) ? 'block' : 'none';
    if (adminLink) adminLink.style.display = isAdminLoggedIn ? 'block' : 'none';
    if (userProfile) userProfile.style.display = (isLoggedIn || isAdminLoggedIn) ? 'block' : 'none';

    console.log(`Nav menu updated: isAdminLoggedIn = ${isAdminLoggedIn}, isLoggedIn = ${isLoggedIn}`);
    if (isHighContrast) applyHighContrast();
    applyFontSize();
    updateIndividualAssetVisibility();
}

function logout() {
    console.log('Starting logout process...');
    try {
        isLoggedIn = false;
        isAdminLoggedIn = false;
        localStorage.setItem('isLoggedIn', 'false');
        localStorage.setItem('isAdminLoggedIn', 'false');
        localStorage.removeItem('username'); // Clear username
        console.log(`Logout - State set to isLoggedIn: ${isLoggedIn}, isAdminLoggedIn: ${isAdminLoggedIn}`);

        logs = [];
        localStorage.setItem('logs', JSON.stringify(logs));
        console.log('Logs cleared');

        const userProfile = document.getElementById('userProfile');
        if (userProfile) userProfile.style.display = 'none';
        console.log('UserProfile hidden');

        const sections = document.querySelectorAll('.section');
        sections.forEach(section => section.style.display = 'none');
        console.log('All sections hidden');

        // Hide welcome message on logout
        const welcomeMessage = document.getElementById('welcome-message');
        if (welcomeMessage) welcomeMessage.style.display = 'none';

        logAction(`User logged out at ${new Date().toISOString()}`);
        console.log('Logout action logged');

        updateIndividualAssetVisibility();
        console.log('Individual asset visibility updated');

        updateNavMenu();
        console.log('Navbar updated');

        const currentPath = window.location.pathname;
        console.log('Current path:', currentPath);
        let redirectPath = 'index.php';
        if (currentPath.includes('/pages/')) redirectPath = '../index.php';
        console.log('Redirecting to:', redirectPath);

        window.location.href = redirectPath;
    } catch (error) {
        console.error('Error during logout:', error);
    }
}

function signup() {
    const firstName = document.getElementById('signup-first-name').value;
    const lastName = document.getElementById('signup-last-name').value;
    const username = document.getElementById('signup-username').value;
    const department = document.getElementById('signup-department').value;
    const email = document.getElementById('signup-email').value;
    const password = document.getElementById('signup-password').value;
    const confirmPassword = document.getElementById('signup-confirm-password').value;
    const emailError = document.getElementById('signup-error');
    const passwordError = document.getElementById('signup-password-error');

    if (!firstName || !lastName) {
        showErrorMessage(emailError, 'Please enter both first and last names');
        return;
    }

    if (!department) {
        showErrorMessage(emailError, 'Please select a department');
        return;
    }

    if (!isValidEmail(email)) {
        showErrorMessage(emailError, 'Enter a valid email address (e.g., must include @)');
        return;
    }
    emailError.style.display = 'none';

    if (!isValidPassword(password)) {
        showErrorMessage(passwordError, 'Password must be at least 8 characters with an uppercase letter, a number, and a special character');
        return;
    }
    passwordError.style.display = 'none';

    if (password !== confirmPassword) {
        alert('Passwords do not match.');
        return;
    }

    fetch('backend/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=signup&username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&first_name=${encodeURIComponent(firstName)}&last_name=${encodeURIComponent(lastName)}&department=${encodeURIComponent(department)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Your account has been created and is pending admin approval.');
            logAction(`User ${username} signed up with email ${email} and department ${department} at ${new Date().toISOString()}`);
            showLogin();
        } else {
            showErrorMessage(emailError, data.message || 'Signup failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage(emailError, 'Signup failed');
    });
}

function resetPassword() {
    const email = document.getElementById('forgot-email').value;
    const forgotSuccess = document.getElementById('forgot-success');
    if (!isValidEmail(email)) {
        alert('Please enter a valid email address (e.g., must include @).');
        return;
    }

    fetch('backend/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=reset_password&email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            logAction(`Password reset requested for ${email} at ${new Date().toISOString()}`);
            forgotSuccess.style.display = 'block'; // Show success message
            setTimeout(() => {
                forgotSuccess.style.display = 'none';
                showLogin();
            }, 3000); // Hide after 3 seconds and return to login
        } else {
            alert(data.message || 'Email not found.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Password reset failed.');
    });
}

function showSignup() {
    document.getElementById('login-box').style.display = 'none';
    document.getElementById('signup-box').style.display = 'block';
    document.getElementById('forgot-box').style.display = 'none';
}

function showLogin() {
    document.getElementById('signup-box').style.display = 'none';
    document.getElementById('forgot-box').style.display = 'none';
    document.getElementById('login-box').style.display = 'block';
}

function showForgotPassword() {
    document.getElementById('login-box').style.display = 'none';
    document.getElementById('signup-box').style.display = 'none';
    document.getElementById('forgot-box').style.display = 'block';
}

function toggleDropdown(event) {
    event.preventDefault();
    const dropdown = document.getElementById('profileDropdown');
    if (!dropdown) return;
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function closeDropdown(event) {
    event.preventDefault();
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown) dropdown.style.display = 'none';
}

function toggleContrast() {
    isHighContrast = !isHighContrast;
    document.body.classList.toggle('high-contrast', isHighContrast);
    const contrastToggle = document.getElementById('contrast-toggle');
    if (contrastToggle) contrastToggle.textContent = isHighContrast ? 'Normal Contrast' : 'High Contrast';
    localStorage.setItem('isHighContrast', isHighContrast);
    applyHighContrast();

    fetch('../backend/user_management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_accessibility&high_contrast=${isHighContrast ? 1 : 0}&font_size=${fontSizeLevel === 0 ? 'small' : fontSizeLevel === 1 ? 'normal' : 'large'}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) console.error('Failed to update accessibility settings:', data.message);
    })
    .catch(error => console.error('Error updating accessibility settings:', error));
}

function applyHighContrast() {
    const elements = document.querySelectorAll('*');
    elements.forEach(element => {
        if (isHighContrast) element.classList.add('high-contrast');
        else element.classList.remove('high-contrast');
    });
}

function toggleFontSize() {
    fontSizeLevel = (fontSizeLevel + 1) % 3;
    localStorage.setItem('fontSizeLevel', fontSizeLevel);
    applyFontSize();
    updateFontSizeButtonText();

    fetch('../backend/user_management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_accessibility&high_contrast=${isHighContrast ? 1 : 0}&font_size=${fontSizeLevel === 0 ? 'small' : fontSizeLevel === 1 ? 'normal' : 'large'}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) console.error('Failed to update accessibility settings:', data.message);
    })
    .catch(error => console.error('Error updating accessibility settings:', error));
}

function applyFontSize() {
    const fontSizes = ['12px', '16px', '20px'];
    document.documentElement.style.fontSize = fontSizes[fontSizeLevel];
}

function updateFontSizeButtonText() {
    const fontSizeToggle = document.getElementById('font-size-toggle');
    if (fontSizeToggle) {
        const labels = ['Small Font', 'Normal Font', 'Large Font'];
        fontSizeToggle.textContent = labels[fontSizeLevel];
    }
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/.test(email);
}

function isValidPassword(password) {
    return password.length >= 8 && /[A-Z]/.test(password) && /[0-9]/.test(password) && /[!@#$%^&*(),.?":{}|<>]/.test(password);
}

function showErrorMessage(errorElement, message) {
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    setTimeout(() => errorElement.style.display = 'none', 3000);
}

function fetchPendingUsers() {
    console.log('Fetching pending users...');
    console.log('Current isAdminLoggedIn state:', isAdminLoggedIn);
    fetch('../backend/user_management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_pending_users'
    })
    .then(response => {
        console.log('Fetch response status:', response.status);
        console.log('Fetch response headers:', response.headers);
        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        console.log('Fetch data:', data);
        if (data.success) {
            pendingUsers = data.users || [];
            console.log('Pending users updated:', pendingUsers);
        } else {
            console.error('Failed to fetch pending users:', data.message);
            pendingUsers = [];
        }
        updateApprovalTable();
    })
    .catch(error => {
        console.error('Error fetching pending users:', error);
        pendingUsers = [];
        updateApprovalTable();
    });
}

function updateApprovalTable() {
    console.log('Updating approval table with pending users:', pendingUsers);
    const table = document.getElementById('admin-approval-table');
    if (!table) {
        console.error('Admin approval table not found.');
        return;
    }
    table.innerHTML = '<tr><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr>';
    if (pendingUsers.length === 0) {
        table.innerHTML += '<tr><td colspan="4">No pending users.</td></tr>';
    } else {
        pendingUsers.forEach(user => {
            console.log('Adding user to table:', user);
            table.innerHTML += `
                <tr>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>${user.role}</td>
                    <td>
                        <button onclick="approveUser('${user.username}')">Approve</button>
                        <button onclick="declineUser('${user.username}')">Decline</button>
                    </td>
                </tr>
            `;
        });
    }
}

function approveUser(username) {
    fetch('../backend/user_management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=approve_user&username=${encodeURIComponent(username)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            pendingUsers = pendingUsers.filter(user => user.username !== username);
            updateApprovalTable();
            logAction(`Approved user ${username} at ${new Date().toISOString()}`);
            alert(`${username} approved successfully.`);
        } else {
            alert(data.message || 'Failed to approve user.');
        }
    })
    .catch(error => {
        console.error('Error approving user:', error);
        alert('Error approving user.');
    });
}

function declineUser(username) {
    fetch('../backend/user_management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=decline_user&username=${encodeURIComponent(username)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            pendingUsers = pendingUsers.filter(user => user.username !== username);
            updateApprovalTable();
            logAction(`Declined user ${username} at ${new Date().toISOString()}`);
            alert(`${username} declined successfully.`);
        } else {
            alert(data.message || 'Failed to decline user.');
        }
    })
    .catch(error => {
        console.error('Error declining user:', error);
        alert('Error declining user.');
    });
}

function fetchLogs() {
    console.log('Fetching logs...');
    return fetch('../backend/user_management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_logs'
    })
    .then(response => {
        console.log('Fetch logs response status:', response.status);
        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        console.log('Fetch logs data:', data);
        if (data.success) {
            logs = data.logs || [];
            console.log('Logs updated:', logs);
            return logs;
        } else {
            console.error('Failed to fetch logs:', data.message);
            logs = [];
            return [];
        }
    })
    .catch(error => {
        console.error('Error fetching logs:', error);
        logs = [];
        return [];
    });
}

function fetchCSVData() {
    console.log('Fetching CSV data...');
    return fetch('../backend/user_management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_assets'
    })
    .then(response => {
        console.log('Fetch CSV response status:', response.status);
        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        console.log('Fetch CSV data:', data);
        if (data.success) {
            return data.assets || [];
        } else {
            console.error('Failed to fetch CSV data:', data.message);
            return [];
        }
    })
    .catch(error => {
        console.error('Error fetching CSV data:', error);
        return [];
    });
}

function logAction(action) {
    logs.push(action);
    localStorage.setItem('logs', JSON.stringify(logs));
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling; // The eye icon is the next sibling
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '👁️'; // You can change this to a different icon if desired
    } else {
        input.type = 'password';
        icon.textContent = '👁️';
    }
}