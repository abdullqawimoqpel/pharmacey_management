<?php
// includes/language.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
    // Redirect to remove the lang parameter from URL
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect_url");
    exit;
}

// Set default language
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = DEFAULT_LANGUAGE ?? 'ar';
}

// Get current language
$current_lang = $_SESSION['language'];

// Load language file
$lang_file = dirname(__FILE__) . '/languages/' . $current_lang . '.php';
if (file_exists($lang_file)) {
    $translations = require $lang_file;
} else {
    $translations = require dirname(__FILE__) . '/languages/ar.php';
}

// Translation function
function __($key, $default = '') {
    global $translations;
    return $translations[$key] ?? ($default ?: $key);
}

// Get current language code
function getCurrentLang() {
    return $_SESSION['language'] ?? 'ar';
}

// Check if RTL
function isRTL() {
    return getCurrentLang() === 'ar';
}

// Get direction
function getDirection() {
    return isRTL() ? 'rtl' : 'ltr';
}

// Get language name
function getLangName($code = null) {
    $code = $code ?? getCurrentLang();
    return $code === 'ar' ? 'العربية' : 'English';
}

// Format currency
function formatMoney($amount) {
    $currency = __('currency');
    if (getCurrentLang() === 'ar') {
        return number_format($amount, 2) . ' ' . $currency;
    }
    return $currency . ' ' . number_format($amount, 2);
}

// Get Arabic date
function getArabicDate() {
    $months = [__('january'), __('february'), __('march'), __('april'), __('may'), __('june'), 
               __('july'), __('august'), __('september'), __('october'), __('november'), __('december')];
    $days = [__('sunday'), __('monday'), __('tuesday'), __('wednesday'), __('thursday'), __('friday'), __('saturday')];
    
    $day_name = $days[(int)date('w')];
    $day = date('j');
    $month = $months[(int)date('n') - 1];
    $year = date('Y');
    
    if (getCurrentLang() === 'ar') {
        return "$day_name $day $month $year";
    }
    return "$day_name, $month $day, $year";
}

// Get greeting based on time
function getGreeting() {
    $hour = (int)date('H');
    if ($hour >= 5 && $hour < 12) {
        return __('good_morning');
    } elseif ($hour >= 12 && $hour < 17) {
        return __('good_afternoon');
    } else {
        return __('good_evening');
    }
}
