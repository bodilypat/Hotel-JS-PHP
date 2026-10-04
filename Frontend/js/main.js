/**
 * Hotel Management System
 * frontend/js/main.js
 *
 * Common application functionality:
 * - Application initialization
 * - Navigation
 * - Sidebar
 * - Logout
 * - Alerts
 * - Modals
 * - Utility functions
 */

"use strict";

/* 
   APPLICATION INITIALIZATION */

document.addEventListener("DOMContentLoaded", () => {
    initializeApp();
});

function initializeApp() {
    initializeSidebar();
    initializeNavigation();
    initializeLogout();
    initializeAlerts();
    initializeModals();
    setActiveNavigation();
}


/* SIDEBAR */

function initializeSidebar() {
    const toggleButton = document.querySelector("#sidebarToggle");
    const sidebar = document.querySelector("#sidebar");
    const overlay = document.querySelector("#sidebarOverlay");

    if (!toggleButton || !sidebar) {
        return;
    }

    toggleButton.addEventListener("click", () => {
        sidebar.classList.toggle("open");

        if (overlay) {
            overlay.classList.toggle("active");
        }
    });

    if (overlay) {
        overlay.addEventListener("click", () => {
            sidebar.classList.remove("open");
            overlay.classList.remove("active");
        });
    }
}


/* NAVIGATION */

function initializeNavigation() {
    const navigationLinks = document.querySelectorAll("[data-page]");

    navigationLinks.forEach(link => {
        link.addEventListener("click", event => {
            const page = link.dataset.page;

            if (!page) {
                return;
            }

            event.preventDefault();

            window.location.href = page;
        });
    });
}


/* ACTIVE NAVIGATION */

function setActiveNavigation() {
    const currentPath = window.location.pathname;

    const navigationLinks = document.querySelectorAll(
        ".sidebar a, .navbar a, [data-page]"
    );

    navigationLinks.forEach(link => {
        const href = link.getAttribute("href");

        if (!href || href === "#") {
            return;
        }

        const linkPath = new URL(href, window.location.href).pathname;

        if (currentPath === linkPath) {
            link.classList.add("active");
        } else {
            link.classList.remove("active");
        }
    });
}


/* LOGOUT */

function initializeLogout() {
    const logoutButtons = document.querySelectorAll(
        "#logoutBtn, .logout-btn, [data-action='logout']"
    );

    logoutButtons.forEach(button => {
        button.addEventListener("click", event => {
            event.preventDefault();

            logoutUser();
        });
    });
}

function logoutUser() {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user");

    sessionStorage.clear();

    window.location.href = "../pages/auth/login.html";
}


/* ALERT SYSTEM */

function initializeAlerts() {
    const alerts = document.querySelectorAll(".alert");

    alerts.forEach(alert => {
        const closeButton = alert.querySelector(".alert-close");

        if (closeButton) {
            closeButton.addEventListener("click", () => {
                closeAlert(alert);
            });
        }
    });
}

function showAlert(message, type = "info", duration = 4000) {
    let container = document.querySelector("#alertContainer");

    if (!container) {
        container = document.createElement("div");
        container.id = "alertContainer";
        container.className = "alert-container";

        document.body.appendChild(container);
    }

    const alert = document.createElement("div");

    alert.className = `alert alert-${type}`;

    alert.innerHTML = `
        <span class="alert-message"></span>
        <button type="button" class="alert-close" aria-label="Close">
            &times;
        </button>
    `;

    alert.querySelector(".alert-message").textContent = message;

    container.appendChild(alert);

    alert.querySelector(".alert-close").addEventListener("click", () => {
        closeAlert(alert);
    });

    if (duration > 0) {
        setTimeout(() => {
            closeAlert(alert);
        }, duration);
    }

    return alert;
}

function closeAlert(alert) {
    if (!alert) {
        return;
    }

    alert.classList.add("closing");

    setTimeout(() => {
        alert.remove();
    }, 200);
}


/* MODAL SYSTEM */

function initializeModals() {
    document.addEventListener("click", event => {

        const openButton = event.target.closest("[data-modal]");

        if (openButton) {
            const modalId = openButton.dataset.modal;
            openModal(modalId);
        }

        const closeButton = event.target.closest("[data-close-modal]");

        if (closeButton) {
            const modal = closeButton.closest(".modal");

            if (modal) {
                closeModal(modal.id);
            }
        }

        if (event.target.classList.contains("modal")) {
            closeModal(event.target.id);
        }
    });

    document.addEventListener("keydown", event => {
        if (event.key !== "Escape") {
            return;
        }

        const activeModals = document.querySelectorAll(".modal.active");
        const topModal = activeModals[activeModals.length - 1];

        if (topModal) {
            closeModal(topModal.id);
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);

    if (!modal) {
        console.warn(`Modal "${modalId}" was not found.`);
        return;
    }

    modal.classList.add("active");
    modal.setAttribute("aria-hidden", "false");

    document.body.classList.add("modal-open");
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);

    if (!modal) {
        return;
    }

    modal.classList.remove("active");
    modal.setAttribute("aria-hidden", "true");

    if (!document.querySelector(".modal.active")) {
        document.body.classList.remove("modal-open");
    }
}

function closeAllModals() {
    const modals = document.querySelectorAll(".modal");

    modals.forEach(modal => {
        modal.classList.remove("active");
        modal.setAttribute("aria-hidden", "true");
    });

    document.body.classList.remove("modal-open");
}


/* CONFIRMATION */

function confirmAction(message) {
    return window.confirm(
        message || "Are you sure you want to continue?"
    );
}

/* LOADING STATE */

function showLoading(element) {
    if (!element) {
        return;
    }

    element.dataset.originalContent = element.innerHTML;

    element.disabled = true;

    element.innerHTML = `
        <span class="spinner"></span>
        Loading...
    `;
}

function hideLoading(element) {
    if (!element) {
        return;
    }

    element.disabled = false;

    if (element.dataset.originalContent) {
        element.innerHTML = element.dataset.originalContent;
        delete element.dataset.originalContent;
    }
}


/* FORM UTILITIES */

function resetForm(form) {
    if (!form) {
        return;
    }

    form.reset();

    const errors = form.querySelectorAll(".field-error");

    errors.forEach(error => {
        error.textContent = "";
        error.classList.remove("visible");
    });

    const invalidFields = form.querySelectorAll(".is-invalid");

    invalidFields.forEach(field => {
        field.classList.remove("is-invalid");
    });
}


/* DATE & CURRENCY UTILITIES */

function formatCurrency(amount, currency = "USD") {
    const value = Number(amount);

    if (Number.isNaN(value)) {
        return "$0.00";
    }

    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: currency
    }).format(value);
}

function formatDate(date) {
    if (!date) {
        return "";
    }

    const parsedDate = new Date(date);

    if (Number.isNaN(parsedDate.getTime())) {
        return "";
    }

    return new Intl.DateTimeFormat("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric"
    }).format(parsedDate);
}


/* DEBOUNCE */

function debounce(callback, delay = 300) {
    let timeout;

    return (...args) => {
        clearTimeout(timeout);

        timeout = setTimeout(() => {
            callback(...args);
        }, delay);
    };
}


/* AUTHENTICATION HELPER */

function isAuthenticated() {
    const token = localStorage.getItem("auth_token");

    return Boolean(token);
}


/* PAGE PROTECTION */

function requireAuthentication() {
    if (!isAuthenticated()) {
        window.location.href = "../pages/auth/login.html";
        return false;
    }

    return true;
}


/* GLOBAL EXPORTS */

window.HotelApp = {
    showAlert,
    closeAlert,

    openModal,
    closeModal,
    closeAllModals,

    confirmAction,

    showLoading,
    hideLoading,

    resetForm,

    formatCurrency,
    formatDate,

    debounce,

    isAuthenticated,
    requireAuthentication,

    logoutUser
};
