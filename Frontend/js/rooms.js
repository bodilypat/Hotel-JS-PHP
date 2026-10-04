/**
 * Hotel Management System
 * frontend/js/dashboard.js
 *
 * Dashboard Data Management
 *
 * Responsibilities:
 * - Load dashboard statistics
 * - Load recent bookings
 * - Load recent payments
 * - Load occupancy information
 * - Load revenue information
 * - Update dashboard UI
 * - Refresh dashboard data
 */

"use strict";


/* =========================================================
   DASHBOARD CONFIGURATION
========================================================= */

const DASHBOARD_CONFIG = {
    REFRESH_INTERVAL: 5 * 60 * 1000,

    SELECTORS: {
        totalRooms: "#totalRooms",
        availableRooms: "#availableRooms",
        occupiedRooms: "#occupiedRooms",
        maintenanceRooms: "#maintenanceRooms",

        totalGuests: "#totalGuests",
        todayCheckIns: "#todayCheckIns",
        todayCheckOuts: "#todayCheckOuts",
        todayBookings: "#todayBookings",

        todayRevenue: "#todayRevenue",
        monthlyRevenue: "#monthlyRevenue",
        occupancyRate: "#occupancyRate",

        recentBookings: "#recentBookings",
        recentPayments: "#recentPayments",

        occupancyChart: "#occupancyChart",
        revenueChart: "#revenueChart"
    }
};


/* =========================================================
   DASHBOARD STATE
========================================================= */

const dashboardState = {
    stats: null,
    bookings: [],
    payments: [],
    occupancy: null,
    revenue: null,

    loading: false,
    lastUpdated: null
};


/* =========================================================
   INITIALIZATION
========================================================= */

document.addEventListener("DOMContentLoaded", () => {

    initializeDashboard();

});


async function initializeDashboard() {

    /*
     * Protect dashboard page.
     */
    if (
        window.HotelAuth &&
        !HotelAuth.requireAuth()
    ) {
        return;
    }

    initializeDashboardEvents();

    await loadDashboard();

    startDashboardAutoRefresh();
}


/* =========================================================
   DASHBOARD EVENTS
========================================================= */

function initializeDashboardEvents() {

    const refreshButton =
        document.querySelector(
            "#refreshDashboard, [data-action='refresh-dashboard']"
        );

    if (refreshButton) {

        refreshButton.addEventListener(
            "click",
            async () => {

                await refreshDashboard();
            }
        );
    }
}


/* =========================================================
   LOAD COMPLETE DASHBOARD
========================================================= */

async function loadDashboard() {

    if (dashboardState.loading) {
        return;
    }

    dashboardState.loading = true;

    showDashboardLoading();

    try {

        /*
         * Load independent dashboard resources
         * in parallel for better performance.
         */
        const [
            stats,
            bookings,
            payments,
            occupancy,
            revenue
        ] = await Promise.all([
            HotelAPI.getDashboardStats(),
            HotelAPI.getBookings({
                limit: 10,
                sort: "latest"
            }),
            HotelAPI.getPayments({
                limit: 10,
                sort: "latest"
            }),
            HotelAPI.getOccupancyReport({
                period: "month"
            }),
            HotelAPI.getRevenueReport({
                period: "month"
            })
        ]);

        dashboardState.stats =
            extractData(stats);

        dashboardState.bookings =
            extractList(bookings);

        dashboardState.payments =
            extractList(payments);

        dashboardState.occupancy =
            extractData(occupancy);

        dashboardState.revenue =
            extractData(revenue);

        dashboardState.lastUpdated =
            new Date();

        updateDashboard();

    } catch (error) {

        console.error(
            "Unable to load dashboard:",
            error
        );

        showDashboardError(error);

    } finally {

        dashboardState.loading = false;

        hideDashboardLoading();
    }
}


/* =========================================================
   REFRESH DASHBOARD
========================================================= */

async function refreshDashboard() {

    const refreshButton =
        document.querySelector(
            "#refreshDashboard, [data-action='refresh-dashboard']"
        );

    if (refreshButton) {
        refreshButton.disabled = true;
    }

    try {

        await loadDashboard();

        if (window.HotelApp) {

            HotelApp.showAlert(
                "Dashboard refreshed successfully.",
                "success",
                2500
            );
        }

    } finally {

        if (refreshButton) {
            refreshButton.disabled = false;
        }
    }
}


/* =========================================================
   UPDATE DASHBOARD
========================================================= */

function updateDashboard() {

    updateStatistics();

    updateRecentBookings();

    updateRecentPayments();

    updateOccupancy();

    updateRevenue();

    updateCharts();

    updateLastUpdated();
}


/* =========================================================
   DATA HELPERS
========================================================= */

function extractData(response) {

    if (!response) {
        return null;
    }

    if (response.data !== undefined) {
        return response.data;
    }

    return response;
}


function extractList(response) {

    if (!response) {
        return [];
    }

    if (Array.isArray(response)) {
        return response;
    }

    if (Array.isArray(response.data)) {
        return response.data;
    }

    if (Array.isArray(response.data?.items)) {
        return response.data.items;
    }

    if (Array.isArray(response.items)) {
        return response.items;
    }

    return [];
}


/* =========================================================
   STATISTICS
========================================================= */

function updateStatistics() {

    const stats =
        dashboardState.stats;

    if (!stats) {
        return;
    }

    setText(
        DASHBOARD_CONFIG.SELECTORS.totalRooms,
        stats.total_rooms ??
        stats.totalRooms ??
        0
    );

    setText(
        DASHBOARD_CONFIG.SELECTORS.availableRooms,
        stats.available_rooms ??
        stats.availableRooms ??
        0
    );

    setText(
        DASHBOARD_CONFIG.SELECTORS.occupiedRooms,
        stats.occupied_rooms ??
        stats.occupiedRooms ??
        0
    );

    setText(
        DASHBOARD_CONFIG.SELECTORS.maintenanceRooms,
        stats.maintenance_rooms ??
        stats.maintenanceRooms ??
        0
    );

    setText(
        DASHBOARD_CONFIG.SELECTORS.totalGuests,
        stats.total_guests ??
        stats.totalGuests ??
        0
    );

    setText(
        DASHBOARD_CONFIG.SELECTORS.todayCheckIns,
        stats.today_check_ins ??
        stats.todayCheckIns ??
        0
    );

    setText(
        DASHBOARD_CONFIG.SELECTORS.todayCheckOuts,
        stats.today_check_outs ??
        stats.todayCheckOuts ??
        0
    );

    setText(
        DASHBOARD_CONFIG.SELECTORS.todayBookings,
        stats.today_bookings ??
        stats.todayBookings ??
        0
    );

    setCurrency(
        DASHBOARD_CONFIG.SELECTORS.todayRevenue,
        stats.today_revenue ??
        stats.todayRevenue ??
        0
    );

    setCurrency(
        DASHBOARD_CONFIG.SELECTORS.monthlyRevenue,
        stats.monthly_revenue ??
        stats.monthlyRevenue ??
        0
    );

    setPercentage(
        DASHBOARD_CONFIG.SELECTORS.occupancyRate,
        stats.occupancy_rate ??
        stats.occupancyRate ??
        0
    );
}


/* =========================================================
   RECENT BOOKINGS
========================================================= */

function updateRecentBookings() {

    const container =
        document.querySelector(
            DASHBOARD_CONFIG.SELECTORS.recentBookings
        );

    if (!container) {
        return;
    }

    const bookings =
        dashboardState.bookings;

    if (!bookings.length) {

        container.innerHTML = `
            <div class="empty-state">
                <p>No recent bookings found.</p>
            </div>
        `;

        return;
    }

    container.innerHTML =
        bookings
            .map(booking =>
                createBookingRow(booking)
            )
            .join("");
}


function createBookingRow(booking) {

    const id =
        booking.id ??
        booking.booking_id ??
        "";

    const guestName =
        booking.guest_name ??
        booking.guest?.name ??
        booking.name ??
        "Unknown Guest";

    const roomNumber =
        booking.room_number ??
        booking.room?.room_number ??
        "-";

    const checkIn =
        booking.check_in ??
        booking.checkIn ??
        "";

    const checkOut =
        booking.check_out ??
        booking.checkOut ??
        "";

    const status =
        booking.status ??
        "pending";

    const amount =
        booking.total_amount ??
        booking.totalAmount ??
        0;

    return `
        <div class="booking-row" data-booking-id="${escapeHtml(id)}">

            <div class="booking-guest">
                <strong>
                    ${escapeHtml(guestName)}
                </strong>

                <small>
                    Booking #${escapeHtml(id)}
                </small>
            </div>

            <div class="booking-room">
                Room ${escapeHtml(roomNumber)}
            </div>

            <div class="booking-date">
                ${formatDashboardDate(checkIn)}
            </div>

            <div class="booking-date">
                ${formatDashboardDate(checkOut)}
            </div>

            <div class="booking-amount">
                ${formatDashboardCurrency(amount)}
            </div>

            <div class="booking-status">
                <span class="status status-${escapeHtml(
                    String(status).toLowerCase()
                )}">
                    ${escapeHtml(formatStatus(status))}
                </span>
            </div>

        </div>
    `;
}


/* =========================================================
   RECENT PAYMENTS
========================================================= */

function updateRecentPayments() {

    const container =
        document.querySelector(
            DASHBOARD_CONFIG.SELECTORS.recentPayments
        );

    if (!container) {
        return;
    }

    const payments =
        dashboardState.payments;

    if (!payments.length) {

        container.innerHTML = `
            <div class="empty-state">
                <p>No recent payments found.</p>
            </div>
        `;

        return;
    }

    container.innerHTML =
        payments
            .map(payment =>
                createPaymentRow(payment)
            )
            .join("");
}


function createPaymentRow(payment) {

    const id =
        payment.id ??
        payment.payment_id ??
        "";

    const guestName =
        payment.guest_name ??
        payment.guest?.name ??
        "Unknown Guest";

    const amount =
        payment.amount ??
        0;

    const method =
        payment.payment_method ??
        payment.paymentMethod ??
        "Unknown";

    const status =
        payment.payment_status ??
        payment.status ??
        "pending";

    const paidAt =
        payment.paid_at ??
        payment.paidAt ??
        payment.created_at ??
        "";

    return `
        <div class="payment-row" data-payment-id="${escapeHtml(id)}">

            <div class="payment-guest">
                <strong>
                    ${escapeHtml(guestName)}
                </strong>

                <small>
                    Payment #${escapeHtml(id)}
                </small>
            </div>

            <div class="payment-method">
                ${escapeHtml(formatPaymentMethod(method))}
            </div>

            <div class="payment-date">
                ${formatDashboardDate(paidAt)}
            </div>

            <div class="payment-amount">
                ${formatDashboardCurrency(amount)}
            </div>

            <div class="payment-status">
                <span class="status status-${escapeHtml(
                    String(status).toLowerCase()
                )}">
                    ${escapeHtml(formatStatus(status))}
                </span>
            </div>

        </div>
    `;
}


/* =========================================================
   OCCUPANCY
========================================================= */

function updateOccupancy() {

    const occupancy =
        dashboardState.occupancy;

    if (!occupancy) {
        return;
    }

    const rate =
        occupancy.occupancy_rate ??
        occupancy.occupancyRate ??
        occupancy.rate;

    if (rate !== undefined) {

        setPercentage(
            DASHBOARD_CONFIG.SELECTORS.occupancyRate,
            rate
        );
    }
}


/* =========================================================
   REVENUE
========================================================= */

function updateRevenue() {

    const revenue =
        dashboardState.revenue;

    if (!revenue) {
        return;
    }

    const total =
        revenue.total ??
        revenue.total_revenue ??
        revenue.totalRevenue;

    if (total !== undefined) {

        setCurrency(
            DASHBOARD_CONFIG.SELECTORS.monthlyRevenue,
            total
        );
    }
}


/* =========================================================
   CHARTS
========================================================= */

function updateCharts() {

    /*
     * This function intentionally does not require
     * a chart library.
     *
     * If Chart.js is added later, initialize the charts
     * here.
     */

    updateOccupancyChart();

    updateRevenueChart();
}


function updateOccupancyChart() {

    const canvas =
        document.querySelector(
            DASHBOARD_CONFIG.SELECTORS.occupancyChart
        );

    if (!canvas) {
        return;
    }

    /*
     * Example integration point:
     *
     * new Chart(canvas, {
     *     type: "doughnut",
     *     data: {...}
     * });
     *
     * Keep chart implementation separate if desired.
     */
}


function updateRevenueChart() {

    const canvas =
        document.querySelector(
            DASHBOARD_CONFIG.SELECTORS.revenueChart
        );

    if (!canvas) {
        return;
    }

    /*
     * Example integration point for
     * monthly revenue chart.
     */
}


/* =========================================================
   AUTO REFRESH
========================================================= */

function startDashboardAutoRefresh() {

    setInterval(
        async () => {

            if (
                document.visibilityState !==
                "visible"
            ) {
                return;
            }

            if (
                window.HotelAuth &&
                !HotelAuth.isLoggedIn()
            ) {
                return;
            }

            await loadDashboard();

        },
        DASHBOARD_CONFIG.REFRESH_INTERVAL
    );
}


/* =========================================================
   LOADING STATE
========================================================= */

function showDashboardLoading() {

    document.body.classList.add(
        "dashboard-loading"
    );

    const elements =
        document.querySelectorAll(
            ".dashboard-stat-value"
        );

    elements.forEach(element => {

        if (!element.dataset.originalValue) {

            element.dataset.originalValue =
                element.textContent;
        }

        element.classList.add("loading");
    });
}


function hideDashboardLoading() {

    document.body.classList.remove(
        "dashboard-loading"
    );

    const elements =
        document.querySelectorAll(
            ".dashboard-stat-value"
        );

    elements.forEach(element => {

        element.classList.remove("loading");
    });
}


/* =========================================================
   ERROR STATE
========================================================= */

function showDashboardError(error) {

    const message =
        error?.message ||
        "Unable to load dashboard data.";

    console.error(
        "Dashboard error:",
        message
    );

    if (window.HotelApp) {

        HotelApp.showAlert(
            message,
            "error"
        );
    }
}


/* =========================================================
   LAST UPDATED
========================================================= */

function updateLastUpdated() {

    const element =
        document.querySelector(
            "#dashboardLastUpdated"
        );

    if (!element) {
        return;
    }

    if (!dashboardState.lastUpdated) {
        element.textContent = "Never";
        return;
    }

    element.textContent =
        dashboardState.lastUpdated.toLocaleTimeString(
            "en-US",
            {
                hour: "numeric",
                minute: "2-digit"
            }
        );
}


/* =========================================================
   DOM HELPERS
========================================================= */

function setText(selector, value) {

    const element =
        document.querySelector(selector);

    if (!element) {
        return;
    }

    element.textContent =
        value ?? 0;
}


function setCurrency(selector, value) {

    const element =
        document.querySelector(selector);

    if (!element) {
        return;
    }

    element.textContent =
        formatDashboardCurrency(value);
}


function setPercentage(selector, value) {

    const element =
        document.querySelector(selector);

    if (!element) {
        return;
    }

    const number =
        Number(value) || 0;

    element.textContent =
        `${number.toFixed(1)}%`;
}


/* =========================================================
   FORMATTING
========================================================= */

function formatDashboardCurrency(value) {

    const amount =
        Number(value) || 0;

    if (window.HotelApp) {
        return HotelApp.formatCurrency(amount);
    }

    return new Intl.NumberFormat(
        "en-US",
        {
            style: "currency",
            currency: "USD"
        }
    ).format(amount);
}


function formatDashboardDate(value) {

    if (!value) {
        return "-";
    }

    if (window.HotelApp) {
        return HotelApp.formatDate(value);
    }

    const date =
        new Date(value);

    if (Number.isNaN(date.getTime())) {
        return "-";
    }

    return new Intl.DateTimeFormat(
        "en-US",
        {
            year: "numeric",
            month: "short",
            day: "numeric"
        }
    ).format(date);
}


function formatStatus(status) {

    if (!status) {
        return "Unknown";
    }

    return String(status)
        .replace(/[_-]/g, " ")
        .replace(/\b\w/g, char =>
            char.toUpperCase()
        );
}


function formatPaymentMethod(method) {

    if (!method) {
        return "Unknown";
    }

    return String(method)
        .replace(/[_-]/g, " ")
        .replace(/\b\w/g, char =>
            char.toUpperCase()
        );
}


/* =========================================================
   HTML ESCAPING
========================================================= */

function escapeHtml(value) {

    if (value === null || value === undefined) {
        return "";
    }

    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


/* =========================================================
   PUBLIC DASHBOARD API
========================================================= */

window.HotelDashboard = {

    load: loadDashboard,

    refresh: refreshDashboard,

    getState: () => ({
        ...dashboardState
    }),

    getStats: () =>
        dashboardState.stats,

    getBookings: () =>
        [...dashboardState.bookings],

    getPayments: () =>
        [...dashboardState.payments]
};
