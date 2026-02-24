<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passwordless Login Language Lines
    |--------------------------------------------------------------------------
    */

    // Email / Notification
    'email_subject' => 'Your Login Link',
    'email_greeting' => 'Hello!',
    'email_intro' => 'You are receiving this email because we received a login request for your account.',
    'email_action' => 'Log In',
    'email_expiry_notice' => 'This login link will expire in :minutes minutes.',
    'email_outro' => 'If you did not request a login link, no further action is required.',
    'email_salutation' => 'Regards',

    // Confirmation Page (Bot Detection)
    'confirmation_title' => 'Confirm Login',
    'confirmation_heading' => 'Almost There!',
    'confirmation_message' => 'Click the button below to complete your login.',
    'confirmation_button' => 'Continue to Login',
    'confirmation_redirecting' => 'Redirecting you automatically...',
    'confirmation_noscript' => 'Please click the button above to continue.',

    // Error Messages
    'error_expired' => 'This login link has expired. Please request a new one.',
    'error_invalid' => 'This login link is invalid.',
    'error_used' => 'This login link has already been used.',
    'error_ip_mismatch' => 'This login link cannot be used from this network.',
    'error_user_agent_mismatch' => 'This login link cannot be used from this browser.',
    'error_condition_failed' => 'You are not authorized to log in at this time.',
    'error_user_not_found' => 'No account found for this login link.',
    'error_throttled' => 'Too many login link requests. Please try again in :minutes minutes.',
    'error_generic' => 'Something went wrong. Please request a new login link.',

    // Flash Messages
    'link_sent' => 'A login link has been sent to your email address.',
    'link_sent_if_exists' => 'If an account exists with that email, a login link has been sent.',
    'login_success' => 'You have been logged in successfully.',

];
