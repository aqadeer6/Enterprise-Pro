let isLoggedIn = false;
let isAdminLoggedIn = false;
let logs = [];
let layers = {};
let pendingUsers = [
    { username: 'hello.user', email: 'hello@gmail.com', role: 'user', password: 'Bradford123' },
    { username: 'admin', email: 'admin@bradford.gov.uk', role: 'admin', password: 'admin123' },
    { username: 'test', email: 'test@bradford.gov.uk', role: 'user', password: 'Test123' }
];
let assets = [];
let map;
let isHighContrast = false;

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM fully loaded, initializing script');

    // Restore login state from localStorage
    isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
    isAdminLoggedIn = localStorage.getItem('isAdminLoggedIn') === 'true';

    // Immediately update navigation to reflect login state
    updateNavMenu();

    const currentPage = window.location.pathname.split('/').pop().replace('.html', '') || 'index';
    if (currentPage === 'index' && document.getElementById('home')) {
        showSection('home');
    } else if (document.getElementById(currentPage)) {
        if ((currentPage === 'csv-upload' || currentPage === 'gis-mapping' || currentPage === 'edit-profile' || currentPage === 'admin') && !isLoggedIn && !isAdminLoggedIn) {
            alert(currentPage === 'admin' ? "Access denied. Admin login required." : "Please log in to access this section.");
            window.location.href = '../index.html'; // Redirect to login page
        } else {
            showSection(currentPage);
        }
    } else {
        showSection('home');
    }

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('keydown', trapTabKey);
    });

    const contrastToggle = document.getElementById('contrast-toggle');
    if (contrastToggle) {
        contrastToggle.addEventListener('click', toggleContrast);
    }

    const navLinks = document.querySelectorAll('nav ul li a');
    navLinks.forEach(link => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const action = link.getAttribute('onclick');
            if (action) {
                console.log(`Nav link clicked: ${action}`);
                eval(action);
            }
        });
    });

    const dropdownItems = document.querySelectorAll('#profileDropdown a');
    dropdownItems.forEach(item => {
        item.addEventListener('click', (event) => {
            event.preventDefault();
            const action = item.getAttribute('onclick');
            if (action) {
                console.log(`Dropdown item clicked: ${action}`);
                eval(action);
            }
            closeDropdown(event);
        });
    });

    const accountLink = document.querySelector('#userProfile a');
    if (accountLink) {
        accountLink.addEventListener('click', (event) => {
            event.preventDefault();
            console.log('Account link clicked, attempting to toggle dropdown');
            toggleDropdown(event);
        });
    }

    const savedContrast = localStorage.getItem('isHighContrast');
    if (savedContrast === 'true') {
        isHighContrast = true;
        document.body.classList.add('high-contrast');
        if (contrastToggle) {
            contrastToggle.textContent = 'Normal Contrast';
        }
        applyHighContrast();
    }
});

function showSection(sectionId) {
    console.log(`Showing section: ${sectionId}, isAdminLoggedIn: ${isAdminLoggedIn}, isLoggedIn: ${isLoggedIn}`);
    const sections = document.querySelectorAll(".section");
    sections.forEach(section => section.style.display = "none");

    if (sectionId === "privacy-policy") {
        document.getElementById(sectionId).style.display = "block";
        updateNavMenu();
    } else if (sectionId === "admin" && !isAdminLoggedIn) {
        alert("Access denied. Admin login required.");
        if (window.location.pathname.split('/').pop() !== 'index.html') {
            window.location.href = '../index.html'; // Redirect to index.html
        }
        return;
    } else if ((sectionId === "csv-upload" || sectionId === "gis-mapping" || sectionId === "edit-profile") && !isLoggedIn && !isAdminLoggedIn) {
        alert("Please log in to access this section.");
        if (window.location.pathname.split('/').pop() !== 'index.html') {
            window.location.href = '../index.html';
        }
        return;
    } else {
        document.getElementById(sectionId).style.display = "block";
        if (sectionId === 'home' && document.getElementById('login-box')) {
            document.getElementById('login-box').style.display = (isLoggedIn || isAdminLoggedIn) ? 'none' : 'block';
        }
    }

    if (sectionId === "gis-mapping" && !map) {
        initMap();
        updateMapMarkers();
    }

    if (sectionId === "csv-upload") {
        document.getElementById("csv-status").innerText = "";
        document.getElementById("csv-preview").style.display = "none";
        if (isAdminLoggedIn) {
            document.getElementById("individual-upload").style.display = "block";
        }
    }

    if (sectionId === "admin") {
        updateApprovalTable();
        document.getElementById('admin-logout').style.display = isAdminLoggedIn ? 'block' : 'none';
    }

    if (sectionId === "edit-profile") {
        document.getElementById('edit-first-name').value = 'John';
        document.getElementById('edit-last-name').value = 'Doe';
        document.getElementById('edit-username').value = 'hello.user';
        document.getElementById('edit-email').value = 'hello@gmail.com';
        document.getElementById('edit-current-password').value = '';
        document.getElementById('edit-new-password').value = '';
        document.getElementById('edit-confirm-password').value = '';
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(msg => msg.style.display = 'none');
    }

    updateNavMenu();
    if (isHighContrast) applyHighContrast();
}

function login() {
    const credentials = document.getElementById('login-credentials').value;
    const password = document.getElementById('login-password').value;
    const emailError = document.getElementById('login-error');
    const passwordError = document.getElementById('password-error');

    let user = null;
    if (isValidEmail(credentials)) {
        user = pendingUsers.find(u => u.email === credentials && u.password === password);
    } else {
        user = pendingUsers.find(u => u.username === credentials && u.password === password);
    }

    if (!user) {
        if (!isValidEmail(credentials) && !pendingUsers.some(u => u.username === credentials)) {
            showErrorMessage(emailError, 'Enter a valid email or username');
            return;
        }
        emailError.style.display = 'none';
        if (!isValidPassword(password)) {
            showErrorMessage(passwordError, 'Password must be at least 8 characters with an uppercase letter and a number');
            return;
        }
        passwordError.style.display = 'none';
        showErrorMessage(emailError, 'Invalid email, username, or password');
        return;
    }

    emailError.style.display = 'none';
    passwordError.style.display = 'none';

    isLoggedIn = true;
    isAdminLoggedIn = user.role === 'admin';
    localStorage.setItem('isLoggedIn', 'true');
    localStorage.setItem('isAdminLoggedIn', user.role === 'admin' ? 'true' : 'false');

    document.getElementById('login-box').style.display = 'none';
    document.getElementById('userProfile').style.display = 'block';
    document.getElementById('notificationBell').style.display = 'block';
    updateNavMenu();
    logAction(`User ${user.email || user.username} logged in at ${new Date().toISOString()}`);
    showSection('home');
    if (isHighContrast) applyHighContrast();
}

function updateNavMenu() {
    const csvLink = document.querySelector('.csv-link');
    const gisLink = document.querySelector('.gis-link');
    const adminLink = document.querySelector('.admin-link');
    const userProfile = document.getElementById('userProfile');
    const notificationBell = document.getElementById('notificationBell');
    const profileDropdown = document.getElementById('profileDropdown');

    if (csvLink) csvLink.style.display = (isLoggedIn || isAdminLoggedIn) ? 'block' : 'none';
    if (gisLink) gisLink.style.display = (isLoggedIn || isAdminLoggedIn) ? 'block' : 'none';
    if (adminLink) adminLink.style.display = isAdminLoggedIn ? 'block' : 'none';
    if (userProfile) userProfile.style.display = (isLoggedIn || isAdminLoggedIn) ? 'block' : 'none';
    if (notificationBell) notificationBell.style.display = (isLoggedIn || isAdminLoggedIn) ? 'block' : 'none';
    if (profileDropdown) profileDropdown.style.display = 'none';

    console.log(`Nav menu updated: isAdminLoggedIn = ${isAdminLoggedIn}, isLoggedIn = ${isLoggedIn}`);
    if (isHighContrast) applyHighContrast();
}

function adminLogout() {
    isAdminLoggedIn = false;
    isLoggedIn = false;
    localStorage.setItem('isAdminLoggedIn', 'false');
    localStorage.setItem('isLoggedIn', 'false');
    document.getElementById('admin-logout').style.display = 'none';
    document.getElementById('userProfile').style.display = 'none';
    document.getElementById('notificationBell').style.display = 'none';
    document.getElementById('login-credentials').value = '';
    document.getElementById('login-password').value = '';
    showSection('home');
    updateNavMenu();
    if (map) {
        map.remove();
        map = null;
    }
    logAction(`Admin logged out at ${new Date().toISOString()}`);
    if (isHighContrast) applyHighContrast();
}

function logout() {
    isLoggedIn = false;
    isAdminLoggedIn = false;
    localStorage.setItem('isLoggedIn', 'false');
    localStorage.setItem('isAdminLoggedIn', 'false');
    document.getElementById('userProfile').style.display = 'none';
    document.getElementById('notificationBell').style.display = 'none';
    document.getElementById('profileDropdown').style.display = 'none';
    document.getElementById('login-credentials').value = '';
    document.getElementById('login-password').value = '';
    showSection('home');
    updateNavMenu();
    if (map) {
        map.remove();
        map = null;
    }
    logAction(`User logged out at ${new Date().toISOString()}`);
    console.log(`User logged out: isAdminLoggedIn = ${isAdminLoggedIn}, isLoggedIn = ${isLoggedIn}`);
    if (isHighContrast) applyHighContrast();
}

function signup() {
    const username = document.getElementById('signup-username').value;
    const email = document.getElementById('signup-email').value;
    const password = document.getElementById('signup-password').value;
    const confirmPassword = document.getElementById('signup-confirm-password').value;
    const emailError = document.getElementById('signup-error');
    const passwordError = document.getElementById('signup-password-error');

    if (!isValidEmail(email)) {
        showErrorMessage(emailError, 'Enter a valid email address (e.g., must include @)');
        return;
    }
    emailError.style.display = 'none';

    if (!isValidPassword(password)) {
        showErrorMessage(passwordError, 'Password must be at least 8 characters with an uppercase letter and a number');
        return;
    }
    passwordError.style.display = 'none';

    if (password !== confirmPassword) {
        alert('Passwords do not match.');
        return;
    }

    if (pendingUsers.some(u => u.email === email || u.username === username)) {
        showErrorMessage(emailError, 'Email or username already registered');
        return;
    }

    pendingUsers.push({ username, email, role: 'user', password });
    document.getElementById('verify-modal').style.display = 'flex';
    logAction(`User ${username} signed up with email ${email} at ${new Date().toISOString()}`);
    if (isHighContrast) applyHighContrast();
}

function resetPassword() {
    const email = document.getElementById('forgot-email').value;
    if (!email) {
        alert('Please enter an email address.');
        return;
    }
    if (!isValidEmail(email)) {
        alert('Please enter a valid email address (e.g., must include @).');
        return;
    }
    const user = pendingUsers.find(u => u.email === email);
    if (!user) {
        alert('Email not found.');
        return;
    }
    logAction(`Password reset requested for ${email} at ${new Date().toISOString()}`);
    alert('Password reset link sent! Check your email.');
    showLogin();
    if (isHighContrast) applyHighContrast();
}

function showSignup() {
    document.getElementById('login-box').style.display = 'none';
    document.getElementById('signup-box').style.display = 'block';
    document.getElementById('forgot-box').style.display = 'none';
    if (isHighContrast) applyHighContrast();
}

function showLogin() {
    document.getElementById('signup-box').style.display = 'none';
    document.getElementById('forgot-box').style.display = 'none';
    document.getElementById('login-box').style.display = 'block';
    if (isHighContrast) applyHighContrast();
}

function showForgotPassword() {
    document.getElementById('login-box').style.display = 'none';
    document.getElementById('signup-box').style.display = 'none';
    document.getElementById('forgot-box').style.display = 'block';
    if (isHighContrast) applyHighContrast();
}

function toggleDropdown(event) {
    event.preventDefault();
    const dropdown = document.getElementById('profileDropdown');
    if (!dropdown) return;
    const isExpanded = dropdown.style.display === 'block';
    dropdown.style.display = isExpanded ? 'none' : 'block';
    dropdown.setAttribute('aria-expanded', (!isExpanded).toString());
    if (isHighContrast) applyHighContrast();
}

function closeDropdown(event) {
    event.preventDefault();
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown) {
        dropdown.style.display = 'none';
        dropdown.setAttribute('aria-expanded', 'false');
    }
    if (isHighContrast) applyHighContrast();
}

function uploadCSV() {
    let fileInput = document.getElementById("csvFile");
    if (fileInput.files.length > 0) {
        Papa.parse(fileInput.files[0], {
            header: true,
            complete: function(results) {
                assets = results.data;
                document.getElementById("csv-status").innerText = "CSV Uploaded! (Processing needed)";
                updateMapMarkers();
                logAction(`Uploaded CSV with ${results.data.length} assets at ${new Date().toISOString()}`);
            }
        });
    } else {
        alert("Please select a CSV file.");
    }
    if (isHighContrast) applyHighContrast();
}

function showCSVPreview() {
    const fileInput = document.getElementById("csvFile");
    if (fileInput.files.length > 0) {
        Papa.parse(fileInput.files[0], {
            header: true,
            complete: function(results) {
                displayPreview(results.data);
            }
        });
    } else {
        alert("Please select a CSV file.");
    }
    if (isHighContrast) applyHighContrast();
}

function displayPreview(data) {
    const table = document.getElementById("preview-table");
    table.innerHTML = '<tr>' + Object.keys(data[0]).map(key => `<th>${key}</th>`).join('') + '</tr>';
    data.forEach(row => {
        table.innerHTML += '<tr>' + Object.values(row).map(value => `<td>${value}</td>`).join('') + '</tr>';
    });
    document.getElementById("csv-preview").style.display = "block";
    if (isHighContrast) applyHighContrast();
}

function submitCSV() {
    document.getElementById("csv-status").innerText = "CSV Submitted! (Processing needed)";
    document.getElementById("csv-preview").style.display = "none";
    logAction(`Submitted CSV at ${new Date().toISOString()}`);
    if (isHighContrast) applyHighContrast();
}

function cancelPreview() {
    document.getElementById("csv-preview").style.display = "none";
    if (isHighContrast) applyHighContrast();
}

function modifyData() {
    const table = document.getElementById("preview-table");
    table.contentEditable = true;
    logAction(`Started modifying data at ${new Date().toISOString()}`);
    setTimeout(() => {
        table.contentEditable = false;
        logAction(`Finished modifying data at ${new Date().toISOString()}`);
        alert("Data modifications saved (simulated).");
    }, 5000);
    if (isHighContrast) applyHighContrast();
}

function uploadIndividualAsset() {
    const name = document.getElementById("asset-name").value;
    const lat = parseFloat(document.getElementById("asset-lat").value);
    const lon = parseFloat(document.getElementById("asset-lon").value);
    if (name && !isNaN(lat) && !isNaN(lon)) {
        assets.push({ name, lat, lon });
        document.getElementById("individual-upload").style.display = "none";
        updateMapMarkers();
        logAction(`Uploaded individual asset ${name} at ${new Date().toISOString()}`);
        alert("Asset uploaded (simulated).");
    } else {
        alert("Please enter valid name, latitude, and longitude.");
    }
    if (isHighContrast) applyHighContrast();
}

function cancelIndividualUpload() {
    document.getElementById("individual-upload").style.display = "none";
    if (isHighContrast) applyHighContrast();
}

function saveProfile() {
    const firstName = document.getElementById('edit-first-name').value;
    const lastName = document.getElementById('edit-last-name').value;
    const username = document.getElementById('edit-username').value;
    const email = document.getElementById('edit-email').value;
    const currentPassword = document.getElementById('edit-current-password').value;
    const newPassword = document.getElementById('edit-new-password').value;
    const confirmPassword = document.getElementById('edit-confirm-password').value;
    const firstNameError = document.getElementById('edit-first-name-error');
    const lastNameError = document.getElementById('edit-last-name-error');
    const usernameError = document.getElementById('edit-username-error');
    const emailError = document.getElementById('edit-email-error');
    const currentPasswordError = document.getElementById('edit-current-password-error');
    const newPasswordError = document.getElementById('edit-new-password-error');
    const confirmPasswordError = document.getElementById('edit-confirm-password-error');

    firstNameError.style.display = 'none';
    lastNameError.style.display = 'none';
    usernameError.style.display = 'none';
    emailError.style.display = 'none';
    currentPasswordError.style.display = 'none';
    newPasswordError.style.display = 'none';
    confirmPasswordError.style.display = 'none';

    let hasErrors = false;

    if (!firstName) {
        showErrorMessage(firstNameError, 'Enter a valid first name');
        hasErrors = true;
    }

    if (!lastName) {
        showErrorMessage(lastNameError, 'Enter a valid last name');
        hasErrors = true;
    }

    if (!username) {
        showErrorMessage(usernameError, 'Enter a valid username');
        hasErrors = true;
    }

    if (!isValidEmail(email)) {
        showErrorMessage(emailError, 'Enter a valid email address (e.g., must include @)');
        hasErrors = true;
    }

    if (!currentPassword) {
        showErrorMessage(currentPasswordError, 'Enter your current password');
        hasErrors = true;
    }

    if (newPassword && !isValidPassword(newPassword)) {
        showErrorMessage(newPasswordError, 'New password must be at least 8 characters with an uppercase letter and a number');
        hasErrors = true;
    }

    if (newPassword !== confirmPassword) {
        showErrorMessage(confirmPasswordError, 'Passwords must match');
        hasErrors = true;
    }

    if (!hasErrors) {
        alert('Profile updated successfully (simulated).');
        logAction(`Profile updated for ${email} at ${new Date().toISOString()}`);
        cancelEditProfile();
    }
    if (isHighContrast) applyHighContrast();
}

function cancelEditProfile() {
    showSection('home');
    if (isLoggedIn || isAdminLoggedIn) {
        document.getElementById('userProfile').style.display = 'block';
    }
    if (isHighContrast) applyHighContrast();
}

function trapTabKey(event) {
    const modal = event.target.closest('.modal');
    if (!modal) return;

    const focusableElements = modal.querySelectorAll('button, input, select, a');
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (event.key === 'Tab') {
        if (event.shiftKey) {
            if (document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            }
        } else {
            if (document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        }
    }
    if (isHighContrast) applyHighContrast();
}

function initMap() {
    if (!map) {
        map = L.map('map').setView([53.7939, -1.7521], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        layers['schools'] = L.layerGroup().addTo(map);
        layers['hospitals'] = L.layerGroup().addTo(map);
        updateMapMarkers();
    }
    if (isHighContrast) applyHighContrast();
}

function updateMapMarkers() {
    if (map) {
        Object.values(layers).forEach(layer => layer.clearLayers());
        assets.forEach(asset => {
            if (asset.lat && asset.lon) {
                const layer = asset.name.includes('School') ? layers['schools'] : layers['hospitals'];
                L.marker([asset.lat, asset.lon], { title: asset.name }).addTo(layer).bindPopup(asset.name);
            }
        });
        console.log(`Map markers updated: ${assets.length} assets displayed`);
    }
    if (isHighContrast) applyHighContrast();
}

function toggleLayer(layerName) {
    if (map) {
        if (document.querySelector(`#layer-controls input[onchange="toggleLayer('${layerName}')"]`).checked) {
            map.addLayer(layers[layerName]);
        } else {
            map.removeLayer(layers[layerName]);
        }
    }
    if (isHighContrast) applyHighContrast();
}

function fetchLiveUpdates() {
    console.log('Fetching live updates (simulated)...');
    const newAsset = { name: `New Asset ${Math.floor(Math.random() * 100)}`, lat: 53.7940 + Math.random() * 0.01, lon: -1.7520 + Math.random() * 0.01 };
    assets.push(newAsset);
    updateMapMarkers();
    logAction(`Live update added ${newAsset.name} at ${new Date().toISOString()}`);
    if (isHighContrast) applyHighContrast();
}
setInterval(fetchLiveUpdates, 10000);

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.(com|org|net|gov|co\.uk|gmail\.com|outlook\.com)$/i;
    return emailRegex.test(email);
}

function isValidPassword(password) {
    return password.length >= 8 && /[A-Z]/.test(password) && /[0-9]/.test(password);
}

function showErrorMessage(errorElement, message) {
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    setTimeout(() => {
        errorElement.style.display = 'none';
    }, 3000);
    if (isHighContrast) applyHighContrast();
}

function verifyEmail() {
    const code = document.getElementById('verify-code').value;
    if (code === '654321') {
        closeModal();
        alert('Email verified!');
        showLogin();
        logAction(`Email verified at ${new Date().toISOString()}`);
    } else {
        alert('Invalid verification code');
    }
    if (isHighContrast) applyHighContrast();
}

function logAction(action) {
    logs.push(action);
    if (document.getElementById('logs-modal') && document.getElementById('logs-modal').style.display === 'flex') {
        document.getElementById('log-list').innerHTML = logs.map(log => `<li>${log}</li>`).join('');
    }
    if (isAdminLoggedIn) {
        updateEnhancedTracking(action);
    }
    if (isHighContrast) applyHighContrast();
}

function updateEnhancedTracking(action) {
    const sessionData = {
        action: action,
        timestamp: new Date().toISOString(),
        page: window.location.pathname,
        sessionDuration: Math.floor(Math.random() * 3600)
    };
    console.log('Enhanced Tracking:', sessionData);
}

function showLogs() {
    document.getElementById('logs-modal').style.display = 'flex';
    document.getElementById('log-list').innerHTML = logs.map(log => `<li>${log}</li>`).join('');
    if (isHighContrast) applyHighContrast();
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
    if (isHighContrast) applyHighContrast();
}

function showShareModal() {
    document.getElementById('share-modal').style.display = 'flex';
    if (isHighContrast) applyHighContrast();
}

function shareDataset() {
    const department = document.getElementById('department-select').value;
    alert(`Dataset shared with ${department} (simulated).`);
    logAction(`Shared dataset with ${department} at ${new Date().toISOString()}`);
    closeModal();
    if (isHighContrast) applyHighContrast();
}

function updateApprovalTable() {
    const table = document.getElementById('admin-approval-table');
    table.innerHTML = '<tr><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr>';
    pendingUsers.forEach(user => {
        table.innerHTML += `
            <tr>
                <td>${user.username}</td>
                <td>${user.email}</td>
                <td>${user.role}</td>
                <td>
                    <button onclick="approveUser('${user.username}')" tabindex="0" aria-label="Approve User ${user.username}">Approve</button>
                    <button onclick="declineUser('${user.username}')" tabindex="0" aria-label="Decline User ${user.username}">Decline</button>
                </td>
            </tr>
        `;
    });
    console.log(`Admin approval table updated with ${pendingUsers.length} users`);
    if (isHighContrast) applyHighContrast();
}

function approveUser(username) {
    pendingUsers = pendingUsers.filter(user => user.username !== username);
    updateApprovalTable();
    logAction(`Approved user ${username} at ${new Date().toISOString()}`);
    alert(`${username} approved (simulated).`);
    if (isHighContrast) applyHighContrast();
}

function declineUser(username) {
    pendingUsers = pendingUsers.filter(user => user.username !== username);
    updateApprovalTable();
    logAction(`Declined user ${username} at ${new Date().toISOString()}`);
    alert(`${username} declined (simulated).`);
    if (isHighContrast) applyHighContrast();
}

function changeUserRole() {
    const username = document.getElementById('change-role-username').value;
    const newRole = document.getElementById('role-select').value;
    const user = pendingUsers.find(u => u.username === username);
    if (user) {
        user.role = newRole;
        updateApprovalTable();
        logAction(`Changed role of ${username} to ${newRole} at ${new Date().toISOString()}`);
        alert(`Role of ${username} changed to ${newRole} (simulated).`);
        document.getElementById('change-role-username').value = '';
    } else {
        alert('User not found.');
    }
    if (isHighContrast) applyHighContrast();
}

function showCSVData() {
    showSection('csv-upload');
    logAction(`Viewed CSV data at ${new Date().toISOString()}`);
    if (isHighContrast) applyHighContrast();
}

function showGISData() {
    showSection('gis-mapping');
    logAction(`Viewed GIS data at ${new Date().toISOString()}`);
    if (isHighContrast) applyHighContrast();
}

function toggleContrast() {
    isHighContrast = !isHighContrast;
    document.body.classList.toggle('high-contrast', isHighContrast);
    const contrastToggle = document.getElementById('contrast-toggle');
    if (contrastToggle) {
        contrastToggle.textContent = isHighContrast ? 'Normal Contrast' : 'High Contrast';
    }
    localStorage.setItem('isHighContrast', isHighContrast);
    applyHighContrast();
    logAction(`Contrast mode toggled to ${isHighContrast ? 'High' : 'Normal'} at ${new Date().toISOString()}`);
}

function applyHighContrast() {
    const elements = document.querySelectorAll('*');
    elements.forEach(element => {
        if (isHighContrast) {
            element.classList.add('high-contrast');
            if (element.id === 'map' && map) {
                element.style.filter = 'invert(100%) hue-rotate(180deg)';
                Object.values(layers).forEach(layer => {
                    layer.eachLayer(marker => {
                        if (marker._icon) marker._icon.style.filter = 'invert(100%) hue-rotate(180deg)';
                        if (marker._path) marker._path.style.filter = 'invert(100%) hue-rotate(180deg)';
                    });
                });
            }
            if (element.classList.contains('modal')) {
                element.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            }
            if (element.classList.contains('modal-content')) {
                element.style.backgroundColor = '#333';
                element.style.color = '#fff';
            }
            if (element.id === 'profileDropdown' || element.id === 'notifications') {
                element.style.backgroundColor = '#333';
                element.style.color = '#fff';
            }
        } else {
            element.classList.remove('high-contrast');
            if (element.id === 'map' && map) {
                element.style.filter = 'none';
                Object.values(layers).forEach(layer => {
                    layer.eachLayer(marker => {
                        if (marker._icon) marker._icon.style.filter = 'none';
                        if (marker._path) marker._path.style.filter = 'none';
                    });
                });
            }
            if (element.classList.contains('modal')) {
                element.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            }
            if (element.classList.contains('modal-content')) {
                element.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
                element.style.color = 'black';
            }
            if (element.id === 'profileDropdown' || element.id === 'notifications') {
                element.style.backgroundColor = '#fff';
                element.style.color = '#333';
            }
        }
    });
    const logo = document.getElementById('logo');
    if (logo) {
        logo.style.filter = isHighContrast ? 'invert(100%)' : 'none';
    }
}