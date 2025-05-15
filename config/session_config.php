<?php
// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
ini_set('session.gc_maxlifetime', 3600); // 1 hour session expiry
ini_set('session.cookie_lifetime', 0); // Session cookie expires when browser is closed

// Set session name to avoid conflicts with other applications
session_name('BRACULA_SESSION');
?> 