/**
 * Hotel Management System
 * frontend/js/auth.js
 *
 * Authentication & Session Management
 *
 * Responsibilities:
 * - Login
 * - Logout
 * - Session persistence
 * - Session validation
 * - Protected pages
 * - Role-based access
 * - Authentication state
 */

"use strict";


/* AUTH CONFIGURATION */

const AUTH_CONFIG = {
    LOGIN_PAGE: "../pages/auth/login.html",
    DASHBOARD_PAGE: "../dashboard/dashboard.html",
    SESSION_KEY: "hotel_session",
    TOKEN_KEY: "auth_token",
    USER_KEY: "user",
    SESSION_CHECK_INTERVAL: 5 * 60 * 1000
};


/*SESSION STORAGE */

/**
 * Save authenticated session.
 *
 * Expected session:
 * {
 *     token: "...",
 *     user: {
 *         id: 1,
 *         name: "Admin",
 *         email: "admin@example.com",
 *         role: "admin"
 *     }
 * }
 */
function saveSession(session) {

    if (!session) {
        return false;
    }

    try {

        const token =
            session.token ||
            session.access_token ||
            session.accessToken;

        const user =
            session.user ||
            session.data?.user;

        if (token) {
            localStorage.setItem(
                AUTH_CONFIG.TOKEN_KEY,
                token
            );
        }

        if (user) {
            localStorage.setItem(
                AUTH_CONFIG.USER_KEY,
                JSON.stringify(user)
            );
        }

        const sessionData = {
            authenticated: true,
            user: user || null,
            loginTime: Date.now()
        };

        localStorage.setItem(
            AUTH_CONFIG.SESSION_KEY,
            JSON.stringify(sessionData)
        );

        return true;

    } catch (error) {

        console.error(
            "Unable to save session:",
            error
        );

        return false;
    }
}


/**
 * Get stored session.
 */
function getSession() {

    const session =
        localStorage.getItem(
            AUTH_CONFIG.SESSION_KEY
        );

    if (!session) {
        return null;
    }

    try {
        return JSON.parse(session);
    } catch (error) {

        console.error(
            "Invalid session data:",
            error
        );

        clearSession();

        return null;
    }
}


/**
 * Get authenticated user.
 */
function getUser() {

    const user =
        localStorage.getItem(
            AUTH_CONFIG.USER_KEY
        );

    if (!user) {
        return null;
    }

    try {
        return JSON.parse(user);
    } catch (error) {

        console.error(
            "Invalid user data:",
            error
        );

        localStorage.removeItem(
            AUTH_CONFIG.USER_KEY
        );

        return null;
    }
}


/**
 * Get authentication token.
 */
function getToken() {

    return localStorage.getItem(
        AUTH_CONFIG.TOKEN_KEY
    );
}


/* SESSION STATE */

/**
 * Check whether a user is authenticated.
 */
function isLoggedIn() {

    const token = getToken();
    const session = getSession();

    return Boolean(
        token &&
        session &&
        session.authenticated === true
    );
}


/**
 * Check whether session data exists.
 */
function hasSession() {

    return Boolean(
        getSession() &&
        getToken()
    );
}


/**
 * Clear local authentication data.
 */
function clearSession() {

    localStorage.removeItem(
        AUTH_CONFIG.TOKEN_KEY
    );

    localStorage.removeItem(
        AUTH_CONFIG.USER_KEY
    );

    localStorage.removeItem(
        AUTH_CONFIG.SESSION_KEY
    );
}


/* LOGIN */

/**
 * Login user.
 *
 * @param {string} email
 * @param {string} password
 */
async function login(email, password) {

    if (!email || !password) {

        throw new Error(
            "Email and password are required."
        );
    }

    try {

        const response =
            await HotelAPI.login(
                email.trim(),
                password
            );

        /*
         * Support common backend response formats.
         */
        const token =
            response?.token ||
            response?.access_token ||
            response?.data?.token ||
            response?.data?.access_token;

        const user =
            response?.user ||
            response?.data?.user;

        if (!token) {

            throw new Error(
                "Authentication token was not returned by the server."
            );
        }

        saveSession({
            token,
            user
        });

        /*
         * Notify application.
         */
        window.dispatchEvent(
            new CustomEvent("auth:login", {
                detail: {
                    user
                }
            })
        );

        return {
            success: true,
            token,
            user,
            data: response
        };

    } catch (error) {

        console.error(
            "Login failed:",
            error
        );

        throw error;
    }
}


/* LOGOUT */

/**
 * Logout from server and clear local session.
 */
async function logout(options = {}) {

    const {
        redirect = true
    } = options;

    try {

        /*
         * Inform backend that the session is ending.
         */
        if (isLoggedIn()) {

            try {
                await HotelAPI.logout();
            } catch (error) {

                /*
                 * Local session must still be cleared
                 * even if the server request fails.
                 */
                console.warn(
                    "Server logout failed:",
                    error
                );
            }
        }

    } finally {

        clearSession();

        window.dispatchEvent(
            new CustomEvent("auth:logout")
        );

        if (redirect) {
            redirectToLogin();
        }
    }
}


/* LOGIN REDIRECTION */

function redirectToLogin() {

    window.location.href =
        AUTH_CONFIG.LOGIN_PAGE;
}


function redirectToDashboard() {

    window.location.href =
        AUTH_CONFIG.DASHBOARD_PAGE;
}


/* PROTECTED PAGE */

/**
 * Require authentication on protected pages.
 *
 * Usage:
 *
 * document.addEventListener("DOMContentLoaded", () => {
 *     requireAuth();
 * });
 */
function requireAuth() {

    if (!isLoggedIn()) {

        redirectToLogin();

        return false;
    }

    return true;
}


/* GUEST-ONLY PAGE */

/**
 * Prevent authenticated users from visiting
 * login/register pages.
 */
function requireGuest() {

    if (isLoggedIn()) {

        redirectToDashboard();

        return false;
    }

    return true;
}


/* ROLE MANAGEMENT */

/**
 * Get current user's role.
 */
function getUserRole() {

    const user = getUser();

    if (!user) {
        return null;
    }

    return String(
        user.role ||
        user.user_role ||
        ""
    ).toLowerCase();
}


/**
 * Check one role.
 */
function hasRole(role) {

    if (!role) {
        return false;
    }

    const currentRole =
        getUserRole();

    return (
        currentRole ===
        String(role).toLowerCase()
    );
}


/**
 * Check multiple roles.
 */
function hasAnyRole(roles = []) {

    if (!Array.isArray(roles)) {
        return false;
    }

    const currentRole =
        getUserRole();

    return roles
        .map(role =>
            String(role).toLowerCase()
        )
        .includes(currentRole);
}


/**
 * Require a specific role.
 */
function requireRole(role) {

    if (!requireAuth()) {
        return false;
    }

    if (!hasRole(role)) {

        showAccessDenied();

        return false;
    }

    return true;
}


/**
 * Require one of several roles.
 */
function requireAnyRole(roles) {

    if (!requireAuth()) {
        return false;
    }

    if (!hasAnyRole(roles)) {

        showAccessDenied();

        return false;
    }

    return true;
}


/* ACCESS DENIED */

function showAccessDenied() {

    if (window.HotelApp) {

        HotelApp.showAlert(
            "You do not have permission to access this page.",
            "error"
        );

        setTimeout(() => {
            redirectToDashboard();
        }, 1200);

    } else {

        redirectToDashboard();
    }
}


/* SESSION VALIDATION */

/**
 * Validate current token with backend.
 *
 * Your PHP API should provide:
 *
 * GET /auth/me
 *
 * or an equivalent authenticated endpoint.
 */
async function validateSession() {

    if (!hasSession()) {
        return false;
    }

    try {

        const response =
            await HotelAPI.get("auth/me");

        const user =
            response?.user ||
            response?.data?.user;

        if (user) {

            localStorage.setItem(
                AUTH_CONFIG.USER_KEY,
                JSON.stringify(user)
            );

            const session =
                getSession();

            if (session) {
                session.user = user;

                localStorage.setItem(
                    AUTH_CONFIG.SESSION_KEY,
                    JSON.stringify(session)
                );
            }
        }

        return true;

    } catch (error) {

        /*
         * 401 means the server rejected the session.
         */
        if (error.status === 401) {

            clearSession();

            window.dispatchEvent(
                new CustomEvent(
                    "auth:session-expired"
                )
            );

            return false;
        }

        /*
         * Do not immediately log the user out for
         * temporary network/server failures.
         */
        console.warn(
            "Session validation failed:",
            error
        );

        return isLoggedIn();
    }
}


/* SESSION EXPIRATION */

function handleSessionExpired() {

    clearSession();

    if (window.HotelApp) {

        HotelApp.showAlert(
            "Your session has expired. Please log in again.",
            "warning"
        );

        setTimeout(() => {
            redirectToLogin();
        }, 1000);

    } else {

        redirectToLogin();
    }
}


/* AUTOMATIC SESSION CHECK */

function startSessionMonitor() {

    if (!isLoggedIn()) {
        return;
    }

    setInterval(async () => {

        const valid =
            await validateSession();

        if (!valid) {
            handleSessionExpired();
        }

    }, AUTH_CONFIG.SESSION_CHECK_INTERVAL);
}


/* AUTH EVENT LISTENERS */

window.addEventListener(
    "auth:unauthorized",
    () => {
        handleSessionExpired();
    }
);


window.addEventListener(
    "auth:session-expired",
    () => {
        handleSessionExpired();
    }
);


/* LOGOUT BUTTONS */

function initializeLogoutButtons() {

    const buttons =
        document.querySelectorAll(
            "#logoutBtn, .logout-btn, [data-action='logout']"
        );

    buttons.forEach(button => {

        button.addEventListener(
            "click",
            async event => {

                event.preventDefault();

                const confirmed =
                    window.confirm(
                        "Are you sure you want to logout?"
                    );

                if (!confirmed) {
                    return;
                }

                try {

                    button.disabled = true;

                    await logout();

                } finally {

                    button.disabled = false;
                }
            }
        );
    });
}


/* AUTH PAGE INITIALIZATION */

function initializeAuth() {

    initializeLogoutButtons();

    /*
     * Start session monitoring only for
     * authenticated users.
     */
    if (isLoggedIn()) {
        startSessionMonitor();
    }
}


/* DOM INITIALIZATION */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        initializeAuth();

    }
);


/* GLOBAL AUTH OBJECT */

window.HotelAuth = {

    // Login / Logout
    login,
    logout,

    // Session
    getSession,
    getUser,
    getToken,
    saveSession,
    clearSession,

    // State
    isLoggedIn,
    hasSession,

    // Navigation
    requireAuth,
    requireGuest,
    redirectToLogin,
    redirectToDashboard,

    // Roles
    getUserRole,
    hasRole,
    hasAnyRole,
    requireRole,
    requireAnyRole,

    // Validation
    validateSession,

    // Session monitor
    startSessionMonitor,

    // Configuration
    config: AUTH_CONFIG
};
