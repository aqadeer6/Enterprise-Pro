<nav>
    <ul>
        <a href="https://www.bradford.gov.uk/" target="_blank"><img id="logo" src="/Software/img/logo.png" alt="Bradford Council Logo"></a>
        <li><a href="/Software/index.php">Home</a></li>
        <li class="user-link csv-link" style="display: none;"><a href="/Software/pages/csv-upload.php">CSV Upload</a></li>
        <li class="user-link gis-link" style="display: none;"><a href="/Software/pages/gis-mapping.php">GIS Mapping</a></li>
        <li class="admin-link" style="display: none;"><a href="/Software/pages/admin.php">Admin</a></li>
        <li class="user-profile" id="userProfile" style="display: none;">
            <a href="#">Account</a>
            <div class="dropdown" id="profileDropdown" style="display: none;">
                <a href="/Software/pages/edit-profile.php">Edit Profile</a>
                <a href="#" onclick="logout()">Logout</a>
            </div>
        </li>
        <li><button id="contrast-toggle">High Contrast</button></li>
        <li><button id="font-size-toggle">Font Size</button></li>
        <li><div id="google-translate-element"></div></li>
    </ul>
</nav>

<!-- Google Translate Script -->
<script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,es,fr,de,zh-CN,ar', // English, Spanish, French, German, Chinese (Simplified), Arabic
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE
        }, 'google-translate-element');
        console.log('Google Translate initialized'); // Debugging to confirm execution
    }
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>