<?php

class SecurityEvents {
    // Authentication events
    const LOGIN_ATTEMPT = 'login_attempt';
    const LOGIN_SUCCESS = 'login_success';
    const LOGIN_FAILURE = 'login_failure';
    const LOGOUT = 'logout';
    
    // 2FA events
    const TWO_FA_SETUP = 'two_fa_setup';
    const TWO_FA_VERIFY = 'two_fa_verify';
    const TWO_FA_DISABLE = 'two_fa_disable';
    
    // Password events
    const PASSWORD_RESET_REQUEST = 'password_reset_request';
    const PASSWORD_RESET_SUCCESS = 'password_reset_success';
    const PASSWORD_CHANGE = 'password_change';
    
    // Email verification events
    const EMAIL_VERIFY_REQUEST = 'email_verify_request';
    const EMAIL_VERIFY_SUCCESS = 'email_verify_success';
    
    // Session events
    const SESSION_START = 'session_start';
    const SESSION_EXPIRE = 'session_expire';
    const SESSION_INVALIDATE = 'session_invalidate';
    
    // Account events
    const ACCOUNT_CREATE = 'account_create';
    const ACCOUNT_UPDATE = 'account_update';
    const ACCOUNT_DELETE = 'account_delete';
} 