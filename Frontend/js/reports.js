/**
 * Hotel Management System
 * frontend/js/reports.js
 *
 * Responsibilities:
 * - Generate hotel reports
 * - Revenue reports
 * - Occupancy reports
 * - Booking reports
 * - Room performance reports
 * - Payment reports
 * - Staff reports
 * - Date-range filtering
 * - Report summary cards
 * - Tables/charts rendering
 * - CSV export
 * - Print reports
 */

"use strict";


/* =========================================================
   REPORT CONFIGURATION
========================================================= */

const REPORT_CONFIG = {

    SELECTORS: {

        reportContainer:
            "#reportContainer",

        reportTable:
            "#reportTable",

        reportTableHead:
            "#reportTableHead",

        reportTableBody:
            "#reportTableBody",

        reportTitle:
            "#reportTitle",

        reportDescription:
            "#reportDescription",

        reportType:
            "#reportType",

        startDate:
            "#startDate",

        endDate:
            "#endDate",

        dateRange:
            "#dateRange",

        generateButton:
            "#generateReportBtn",

        exportButton:
            "#exportReportBtn",

        printButton:
            "#printReportBtn",

        resetButton:
            "#resetReportBtn",

        summaryContainer:
            "#reportSummary",

        revenue:
            "#reportRevenue",

        bookings:
            "#reportBookings",

        occupancy:
            "#reportOccupancy",

        payments:
            "#reportPayments",

        rooms:
            "#reportRooms",

        guests:
            "#reportGuests",

        chart:
            "#reportChart",

        loading:
            "#reportLoading",

        error:
            "#reportError"

    },


    REPORT_TYPES: {

        REVENUE:
            "revenue",

        OCCUPANCY:
            "occupancy",

        BOOKINGS:
            "bookings",

        ROOMS:
            "rooms",

        PAYMENTS:
            "payments",

        GUESTS:
            "guests",

        STAFF:
            "staff",

        DASHBOARD:
            "dashboard"

    }

};


/* =========================================================
   REPORT STATE
========================================================= */

const reportState = {

    currentReport:
        null,

    currentType:
        "dashboard",

    startDate:
        null,

    endDate:
        null,

    loading:
        false,

    data:
        [],

    summary:
        {},

    filters:
        {}

};


/* =========================================================
   INITIALIZATION
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        initializeReports();

    }
);


async function initializeReports() {

    if (
        window.HotelAuth &&
        typeof HotelAuth.requireAuth ===
        "function"
    ) {

        if (
            !HotelAuth.requireAuth()
        ) {

            return;

        }

    }


    initializeReportEvents();

    initializeDateRange();

    initializeReportType();

    /*
     * Generate the report automatically when
     * report elements exist on the current page.
     */
    if (
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportContainer
        ) ||
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportTable
        )
    ) {

        await generateReport();

    }

}


/* =========================================================
   EVENT HANDLERS
========================================================= */

function initializeReportEvents() {

    const generateButton =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.generateButton
        );


    const exportButton =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.exportButton
        );


    const printButton =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.printButton
        );


    const resetButton =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.resetButton
        );


    const reportType =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportType
        );


    const dateRange =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.dateRange
        );


    if (generateButton) {

        generateButton.addEventListener(
            "click",
            generateReport
        );

    }


    if (exportButton) {

        exportButton.addEventListener(
            "click",
            exportReportCSV
        );

    }


    if (printButton) {

        printButton.addEventListener(
            "click",
            printReport
        );

    }


    if (resetButton) {

        resetButton.addEventListener(
            "click",
            resetReportFilters
        );

    }


    if (reportType) {

        reportType.addEventListener(
            "change",
            () => {

                updateReportLabels();

            }
        );

    }


    if (dateRange) {

        dateRange.addEventListener(
            "change",
            handleDateRangeChange
        );

    }

}


/* =========================================================
   DATE RANGE
========================================================= */

function initializeDateRange() {

    const startInput =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.startDate
        );


    const endInput =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.endDate
        );


    const today =
        new Date();


    const firstDay =
        new Date(
            today.getFullYear(),
            today.getMonth(),
            1
        );


    if (
        startInput &&
        !startInput.value
    ) {

        startInput.value =
            toInputDate(
                firstDay
            );

    }


    if (
        endInput &&
        !endInput.value
    ) {

        endInput.value =
            toInputDate(
                today
            );

    }


    reportState.startDate =
        startInput?.value ||
        toInputDate(firstDay);


    reportState.endDate =
        endInput?.value ||
        toInputDate(today);

}


function initializeReportType() {

    const select =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportType
        );


    if (!select) {
        return;
    }


    if (!select.value) {

        select.value =
            REPORT_CONFIG.REPORT_TYPES.DASHBOARD;

    }


    reportState.currentType =
        select.value;

}


function handleDateRangeChange(
    event
) {

    const range =
        event.target.value;


    const today =
        new Date();


    let start =
        new Date(
            today
        );


    let end =
        new Date(
            today
        );


    switch (range) {

        case "today":

            start =
                new Date(
                    today
                );

            end =
                new Date(
                    today
                );

            break;


        case "yesterday":

            start.setDate(
                start.getDate() - 1
            );

            end =
                new Date(
                    start
                );

            break;


        case "7":

            start.setDate(
                start.getDate() - 6
            );

            break;


        case "30":

            start.setDate(
                start.getDate() - 29
            );

            break;


        case "90":

            start.setDate(
                start.getDate() - 89
            );

            break;


        case "year":

            start =
                new Date(
                    today.getFullYear(),
                    0,
                    1
                );

            break;


        default:

            return;

    }


    setInputValue(
        REPORT_CONFIG.SELECTORS.startDate,
        toInputDate(start)
    );


    setInputValue(
        REPORT_CONFIG.SELECTORS.endDate,
        toInputDate(end)
    );


    reportState.startDate =
        toInputDate(start);


    reportState.endDate =
        toInputDate(end);

}


/* =========================================================
   GENERATE REPORT
========================================================= */

async function generateReport(
    event = null
) {

    if (event) {

        event.preventDefault();

    }


    if (reportState.loading) {
        return;
    }


    const type =
        getSelectedReportType();


    const dates =
        getSelectedDates();


    if (!dates.valid) {

        showReportError(
            dates.message
        );


        return;

    }


    reportState.currentType =
        type;


    reportState.startDate =
        dates.startDate;


    reportState.endDate =
        dates.endDate;


    reportState.filters = {

        start_date:
            dates.startDate,

        end_date:
            dates.endDate

    };


    reportState.loading =
        true;


    showReportLoading();

    disableReportActions(
        true
    );


    try {

        const response =
            await fetchReport(
                type,
                reportState.filters
            );


        const normalized =
            normalizeReportResponse(
                response,
                type
            );


        reportState.currentReport =
            normalized;


        reportState.data =
            normalized.data;


        reportState.summary =
            normalized.summary;


        updateReportHeader();

        renderReportSummary();

        renderReportTable();

        renderReportChart();

        showReportContent();


    } catch (error) {

        console.error(
            "Report generation failed:",
            error
        );


        showReportError(
            getErrorMessage(
                error,
                "Unable to generate report."
            )
        );


    } finally {

        reportState.loading =
            false;


        hideReportLoading();

        disableReportActions(
            false
        );

    }

}


/* =========================================================
   FETCH REPORT
========================================================= */

async function fetchReport(
    type,
    filters
) {

    /*
     * Prefer dedicated HotelAPI report methods.
     */

    switch (type) {

        case "revenue":

            if (
                typeof HotelAPI.getRevenueReport ===
                "function"
            ) {

                return HotelAPI.getRevenueReport(
                    filters
                );

            }

            break;


        case "occupancy":

            if (
                typeof HotelAPI.getOccupancyReport ===
                "function"
            ) {

                return HotelAPI.getOccupancyReport(
                    filters
                );

            }

            break;


        case "bookings":

            if (
                typeof HotelAPI.getBookingReport ===
                "function"
            ) {

                return HotelAPI.getBookingReport(
                    filters
                );

            }

            break;


        case "rooms":

            if (
                typeof HotelAPI.getRoomReport ===
                "function"
            ) {

                return HotelAPI.getRoomReport(
                    filters
                );

            }

            break;


        case "payments":

            if (
                typeof HotelAPI.getPaymentReport ===
                "function"
            ) {

                return HotelAPI.getPaymentReport(
                    filters
                );

            }

            break;


        case "guests":

            if (
                typeof HotelAPI.getGuestReport ===
                "function"
            ) {

                return HotelAPI.getGuestReport(
                    filters
                );

            }

            break;


        case "staff":

            if (
                typeof HotelAPI.getStaffReport ===
                "function"
            ) {

                return HotelAPI.getStaffReport(
                    filters
                );

            }

            break;


        case "dashboard":

            if (
                typeof HotelAPI.getDashboardReport ===
                "function"
            ) {

                return HotelAPI.getDashboardReport(
                    filters
                );

            }

            break;

    }


    /*
     * Generic fallback.
     */
    if (
        typeof HotelAPI.getReport ===
        "function"
    ) {

        return HotelAPI.getReport(
            type,
            filters
        );

    }


    /*
     * Last-resort direct API fallback.
     *
     * This only runs if api.js exposes a
     * base request function.
     */
    if (
        typeof HotelAPI.request ===
        "function"
    ) {

        return HotelAPI.request(
            `/reports/${encodeURIComponent(type)}`,
            {
                method: "GET",
                params: filters
            }
        );

    }


    throw new Error(
        "No report API method is available in HotelAPI."
    );

}


/* =========================================================
   NORMALIZE REPORT RESPONSE
========================================================= */

function normalizeReportResponse(
    response,
    type
) {

    const source =
        response?.data ??
        response ??
        {};


    let data = [];


    if (
        Array.isArray(
            source
        )
    ) {

        data =
            source;

    } else if (
        Array.isArray(
            source.data
        )
    ) {

        data =
            source.data;

    } else if (
        Array.isArray(
            source.items
        )
    ) {

        data =
            source.items;

    } else if (
        Array.isArray(
            source.records
        )
    ) {

        data =
            source.records;

    } else if (
        Array.isArray(
            source.results
        )
    ) {

        data =
            source.results;

    }


    const summary =
        source.summary ||
        response?.summary ||
        buildSummaryFromData(
            data,
            type
        );


    return {

        type,

        data,

        summary,

        meta:
            source.meta ||
            response?.meta ||
            {}

    };

}


/* =========================================================
   BUILD SUMMARY
========================================================= */

function buildSummaryFromData(
    data,
    type
) {

    if (!Array.isArray(data)) {

        return {};

    }


    switch (type) {

        case "revenue":

            return buildRevenueSummary(
                data
            );


        case "occupancy":

            return buildOccupancySummary(
                data
            );


        case "bookings":

            return buildBookingSummary(
                data
            );


        case "payments":

            return buildPaymentSummary(
                data
            );


        case "rooms":

            return buildRoomSummary(
                data
            );


        case "guests":

            return buildGuestSummary(
                data
            );


        case "staff":

            return buildStaffSummary(
                data
            );


        default:

            return {};

    }

}


/* =========================================================
   REVENUE SUMMARY
========================================================= */

function buildRevenueSummary(
    data
) {

    const revenue =
        data.reduce(
            (
                total,
                row
            ) =>
                total +
                Number(
                    row.revenue ??
                    row.total ??
                    row.amount ??
                    0
                ),
            0
        );


    return {

        revenue,

        totalRevenue:
            revenue,

        records:
            data.length

    };

}


/* =========================================================
   OCCUPANCY SUMMARY
========================================================= */

function buildOccupancySummary(
    data
) {

    let occupied =
        0;


    let available =
        0;


    let total =
        0;


    data.forEach(
        row => {

            occupied +=
                Number(
                    row.occupied ??
                    row.occupied_rooms ??
                    0
                );


            available +=
                Number(
                    row.available ??
                    row.available_rooms ??
                    0
                );


            total +=
                Number(
                    row.total ??
                    row.total_rooms ??
                    0
                );

        }
    );


    if (!total) {

        total =
            occupied +
            available;

    }


    const occupancy =
        total
            ? (
                occupied /
                total
            ) * 100
            : 0;


    return {

        occupied,

        available,

        total,

        occupancy

    };

}


/* =========================================================
   BOOKING SUMMARY
========================================================= */

function buildBookingSummary(
    data
) {

    const total =
        data.length;


    const confirmed =
        data.filter(
            row =>
                normalizeStatus(
                    row.status
                ) === "confirmed"
        ).length;


    const cancelled =
        data.filter(
            row =>
                normalizeStatus(
                    row.status
                ) === "cancelled"
        ).length;


    const completed =
        data.filter(
            row =>
                [
                    "completed",
                    "checked_out",
                    "checkout"
                ].includes(
                    normalizeStatus(
                        row.status
                    )
                )
        ).length;


    return {

        totalBookings:
            total,

        confirmedBookings:
            confirmed,

        cancelledBookings:
            cancelled,

        completedBookings:
            completed

    };

}


/* =========================================================
   PAYMENT SUMMARY
========================================================= */

function buildPaymentSummary(
    data
) {

    const amount =
        data.reduce(
            (
                total,
                row
            ) =>
                total +
                Number(
                    row.amount ??
                    row.total ??
                    row.paid_amount ??
                    0
                ),
            0
        );


    const paid =
        data.filter(
            row =>
                [
                    "paid",
                    "completed",
                    "success",
                    "successful"
                ].includes(
                    normalizeStatus(
                        row.status
                    )
                )
        ).length;


    const pending =
        data.filter(
            row =>
                normalizeStatus(
                    row.status
                ) === "pending"
        ).length;


    return {

        totalPayments:
            data.length,

        totalAmount:
            amount,

        paidPayments:
            paid,

        pendingPayments:
            pending

    };

}


/* =========================================================
   ROOM SUMMARY
========================================================= */

function buildRoomSummary(
    data
) {

    const total =
        data.length;


    const available =
        data.filter(
            row =>
                normalizeStatus(
                    row.status
                ) === "available"
        ).length;


    const occupied =
        data.filter(
            row =>
                normalizeStatus(
                    row.status
                ) === "occupied"
        ).length;


    const maintenance =
        data.filter(
            row =>
                normalizeStatus(
                    row.status
                ) === "maintenance"
        ).length;


    return {

        totalRooms:
            total,

        availableRooms:
            available,

        occupiedRooms:
            occupied,

        maintenanceRooms:
            maintenance

    };

}


/* =========================================================
   GUEST SUMMARY
========================================================= */

function buildGuestSummary(
    data
) {

    return {

        totalGuests:
            data.length

    };

}


/* =========================================================
   STAFF SUMMARY
========================================================= */

function buildStaffSummary(
    data
) {

    const active =
        data.filter(
            row =>
                normalizeStatus(
                    row.status
                ) === "active"
        ).length;


    return {

        totalStaff:
            data.length,

        activeStaff:
            active,

        inactiveStaff:
            data.length -
            active

    };

}


/* =========================================================
   REPORT HEADER
========================================================= */

function updateReportHeader() {

    const title =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportTitle
        );


    const description =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportDescription
        );


    const type =
        reportState.currentType;


    const labels = {

        revenue: {

            title:
                "Revenue Report",

            description:
                "Revenue and income generated during the selected period."

        },


        occupancy: {

            title:
                "Occupancy Report",

            description:
                "Room occupancy and availability during the selected period."

        },


        bookings: {

            title:
                "Booking Report",

            description:
                "Reservation activity during the selected period."

        },


        rooms: {

            title:
                "Room Report",

            description:
                "Room inventory and room status information."

        },


        payments: {

            title:
                "Payment Report",

            description:
                "Payment transactions and billing information."

        },


        guests: {

            title:
                "Guest Report",

            description:
                "Guest activity during the selected period."

        },


        staff: {

            title:
                "Staff Report",

            description:
                "Staff information and employment status."

        },


        dashboard: {

            title:
                "Hotel Management Report",

            description:
                "Overview of hotel operations for the selected period."

        }

    };


    const label =
        labels[type] ||
        labels.dashboard;


    if (title) {

        title.textContent =
            label.title;

    }


    if (description) {

        description.textContent =
            label.description;

    }

}


/* =========================================================
   REPORT SUMMARY CARDS
========================================================= */

function renderReportSummary() {

    const container =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.summaryContainer
        );


    const summary =
        reportState.summary ||
        {};


    if (!container) {

        /*
         * Still update known individual
         * summary elements.
         */
        updateIndividualSummaryElements(
            summary
        );


        return;

    }


    const cards =
        getSummaryCards(
            reportState.currentType,
            summary
        );


    container.innerHTML =
        cards
            .map(
                card =>
                    createSummaryCard(
                        card
                    )
            )
            .join("");


    updateIndividualSummaryElements(
        summary
    );

}


function getSummaryCards(
    type,
    summary
) {

    switch (type) {

        case "revenue":

            return [

                {
                    label:
                        "Total Revenue",

                    value:
                        formatCurrency(
                            summary.totalRevenue ??
                            summary.revenue ??
                            0
                        ),

                    className:
                        "revenue"

                },

                {
                    label:
                        "Transactions",

                    value:
                        summary.records ??
                        summary.totalPayments ??
                        0,

                    className:
                        "transactions"

                }

            ];


        case "occupancy":

            return [

                {
                    label:
                        "Occupancy Rate",

                    value:
                        formatPercent(
                            summary.occupancy ??
                            0
                        ),

                    className:
                        "occupancy"

                },

                {
                    label:
                        "Occupied Rooms",

                    value:
                        summary.occupied ??
                        summary.occupiedRooms ??
                        0,

                    className:
                        "occupied"

                },

                {
                    label:
                        "Available Rooms",

                    value:
                        summary.available ??
                        summary.availableRooms ??
                        0,

                    className:
                        "available"

                },

                {
                    label:
                        "Total Rooms",

                    value:
                        summary.total ??
                        summary.totalRooms ??
                        0,

                    className:
                        "total"

                }

            ];


        case "bookings":

            return [

                {
                    label:
                        "Total Bookings",

                    value:
                        summary.totalBookings ??
                        0,

                    className:
                        "bookings"

                },

                {
                    label:
                        "Confirmed",

                    value:
                        summary.confirmedBookings ??
                        0,

                    className:
                        "confirmed"

                },

                {
                    label:
                        "Cancelled",

                    value:
                        summary.cancelledBookings ??
                        0,

                    className:
                        "cancelled"

                },

                {
                    label:
                        "Completed",

                    value:
                        summary.completedBookings ??
                        0,

                    className:
                        "completed"

                }

            ];


        case "payments":

            return [

                {
                    label:
                        "Total Amount",

                    value:
                        formatCurrency(
                            summary.totalAmount ??
                            0
                        ),

                    className:
                        "amount"

                },

                {
                    label:
                        "Payments",

                    value:
                        summary.totalPayments ??
                        0,

                    className:
                        "payments"

                },

                {
                    label:
                        "Paid",

                    value:
                        summary.paidPayments ??
                        0,

                    className:
                        "paid"

                },

                {
                    label:
                        "Pending",

                    value:
                        summary.pendingPayments ??
                        0,

                    className:
                        "pending"

                }

            ];


        case "rooms":

            return [

                {
                    label:
                        "Total Rooms",

                    value:
                        summary.totalRooms ??
                        0,

                    className:
                        "rooms"

                },

                {
                    label:
                        "Available",

                    value:
                        summary.availableRooms ??
                        0,

                    className:
                        "available"

                },

                {
                    label:
                        "Occupied",

                    value:
                        summary.occupiedRooms ??
                        0,

                    className:
                        "occupied"

                },

                {
                    label:
                        "Maintenance",

                    value:
                        summary.maintenanceRooms ??
                        0,

                    className:
                        "maintenance"

                }

            ];


        case "guests":

            return [

                {
                    label:
                        "Total Guests",

                    value:
                        summary.totalGuests ??
                        0,

                    className:
                        "guests"

                }

            ];


        case "staff":

            return [

                {
                    label:
                        "Total Staff",

                    value:
                        summary.totalStaff ??
                        0,

                    className:
                        "staff"

                },

                {
                    label:
                        "Active Staff",

                    value:
                        summary.activeStaff ??
                        0,

                    className:
                        "active"

                },

                {
                    label:
                        "Inactive Staff",

                    value:
                        summary.inactiveStaff ??
                        0,

                    className:
                        "inactive"

                }

            ];


        default:

            return [

                {
                    label:
                        "Revenue",

                    value:
                        formatCurrency(
                            summary.revenue ??
                            summary.totalRevenue ??
                            0
                        ),

                    className:
                        "revenue"

                },

                {
                    label:
                        "Bookings",

                    value:
                        summary.totalBookings ??
                        0,

                    className:
                        "bookings"

                },

                {
                    label:
                        "Occupancy",

                    value:
                        formatPercent(
                            summary.occupancy ??
                            0
                        ),

                    className:
                        "occupancy"

                }

            ];

    }

}


function createSummaryCard(
    card
) {

    return `
        <div class="report-summary-card ${escapeHtml(
            card.className || ""
        )}">

            <div class="report-summary-label">
                ${escapeHtml(
                    card.label
                )}
            </div>

            <div class="report-summary-value">
                ${escapeHtml(
                    card.value
                )}
            </div>

        </div>
    `;

}


function updateIndividualSummaryElements(
    summary
) {

    setText(
        REPORT_CONFIG.SELECTORS.revenue,
        formatCurrency(
            summary.revenue ??
            summary.totalRevenue ??
            summary.totalAmount ??
            0
        )
    );


    setText(
        REPORT_CONFIG.SELECTORS.bookings,
        summary.totalBookings ??
        0
    );


    setText(
        REPORT_CONFIG.SELECTORS.occupancy,
        formatPercent(
            summary.occupancy ??
            0
        )
    );


    setText(
        REPORT_CONFIG.SELECTORS.payments,
        summary.totalPayments ??
        0
    );


    setText(
        REPORT_CONFIG.SELECTORS.rooms,
        summary.totalRooms ??
        0
    );


    setText(
        REPORT_CONFIG.SELECTORS.guests,
        summary.totalGuests ??
        0
    );

}


/* =========================================================
   REPORT TABLE
========================================================= */

function renderReportTable() {

    const head =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportTableHead
        );


    const body =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportTableBody
        );


    if (!head && !body) {
        return;
    }


    const data =
        reportState.data;


    if (!data.length) {

        if (head) {

            head.innerHTML =
                "";

        }


        if (body) {

            body.innerHTML = `
                <tr>

                    <td
                        colspan="20"
                        class="empty-state"
                    >
                        No report data found for
                        the selected period.
                    </td>

                </tr>
            `;

        }


        return;

    }


    const columns =
        getReportColumns(
            reportState.currentType,
            data
        );


    if (head) {

        head.innerHTML = `
            <tr>
                ${columns
                    .map(
                        column =>
                            `<th>${escapeHtml(
                                column.label
                            )}</th>`
                    )
                    .join("")
                }
            </tr>
        `;

    }


    if (body) {

        body.innerHTML =
            data
                .map(
                    row =>
                        createReportRow(
                            row,
                            columns
                        )
                )
                .join("");

    }

}


/* =========================================================
   REPORT COLUMNS
========================================================= */

function getReportColumns(
    type,
    data
) {

    switch (type) {

        case "revenue":

            return [

                {
                    key:
                        "date",

                    label:
                        "Date",

                    formatter:
                        formatDate
                },

                {
                    key:
                        "revenue",

                    label:
                        "Revenue",

                    formatter:
                        formatCurrency
                },

                {
                    key:
                        "bookings",

                    label:
                        "Bookings",

                    formatter:
                        value =>
                            value ?? 0
                },

                {
                    key:
                        "payments",

                    label:
                        "Payments",

                    formatter:
                        value =>
                            value ?? 0
                }

            ];


        case "occupancy":

            return [

                {
                    key:
                        "date",

                    label:
                        "Date",

                    formatter:
                        formatDate
                },

                {
                    key:
                        "occupied",

                    label:
                        "Occupied",

                    formatter:
                        value =>
                            value ?? 0
                },

                {
                    key:
                        "available",

                    label:
                        "Available",

                    formatter:
                        value =>
                            value ?? 0
                },

                {
                    key:
                        "total",

                    label:
                        "Total",

                    formatter:
                        value =>
                            value ?? 0
                },

                {
                    key:
                        "occupancy",

                    label:
                        "Occupancy",

                    formatter:
                        formatPercent
                }

            ];


        case "bookings":

            return [

                {
                    key:
                        "booking_id",

                    label:
                        "Booking ID",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "guest_name",

                    label:
                        "Guest",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "room_number",

                    label:
                        "Room",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "check_in",

                    label:
                        "Check In",

                    formatter:
                        formatDate
                },

                {
                    key:
                        "check_out",

                    label:
                        "Check Out",

                    formatter:
                        formatDate
                },

                {
                    key:
                        "status",

                    label:
                        "Status",

                    formatter:
                        formatStatus
                },

                {
                    key:
                        "total",

                    label:
                        "Total",

                    formatter:
                        formatCurrency
                }

            ];


        case "rooms":

            return [

                {
                    key:
                        "room_number",

                    label:
                        "Room",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "room_type",

                    label:
                        "Room Type",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "status",

                    label:
                        "Status",

                    formatter:
                        formatStatus
                },

                {
                    key:
                        "rate",

                    label:
                        "Rate",

                    formatter:
                        formatCurrency
                },

                {
                    key:
                        "revenue",

                    label:
                        "Revenue",

                    formatter:
                        formatCurrency
                }

            ];


        case "payments":

            return [

                {
                    key:
                        "payment_id",

                    label:
                        "Payment ID",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "date",

                    label:
                        "Date",

                    formatter:
                        formatDate
                },

                {
                    key:
                        "guest_name",

                    label:
                        "Guest",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "amount",

                    label:
                        "Amount",

                    formatter:
                        formatCurrency
                },

                {
                    key:
                        "method",

                    label:
                        "Method",

                    formatter:
                        formatStatus
                },

                {
                    key:
                        "status",

                    label:
                        "Status",

                    formatter:
                        formatStatus
                }

            ];


        case "guests":

            return [

                {
                    key:
                        "guest_id",

                    label:
                        "Guest ID",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "name",

                    label:
                        "Guest",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "email",

                    label:
                        "Email",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "phone",

                    label:
                        "Phone",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "bookings",

                    label:
                        "Bookings",

                    formatter:
                        value =>
                            value ?? 0
                }

            ];


        case "staff":

            return [

                {
                    key:
                        "staff_id",

                    label:
                        "Staff ID",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "name",

                    label:
                        "Name",

                    formatter:
                        value =>
                            value ?? "-"
                },

                {
                    key:
                        "role",

                    label:
                        "Role",

                    formatter:
                        formatStatus
                },

                {
                    key:
                        "department",

                    label:
                        "Department",

                    formatter:
                        formatStatus
                },

                {
                    key:
                        "status",

                    label:
                        "Status",

                    formatter:
                        formatStatus
                },

                {
                    key:
                        "hire_date",

                    label:
                        "Hire Date",

                    formatter:
                        formatDate
                }

            ];


        default:

            return inferColumns(
                data
            );

    }

}


/* =========================================================
   CREATE TABLE ROW
========================================================= */

function createReportRow(
    row,
    columns
) {

    return `
        <tr>

            ${
                columns
                    .map(
                        column => {

                            const raw =
                                getRowValue(
                                    row,
                                    column.key
                                );


                            let value =
                                raw;


                            if (
                                typeof column.formatter ===
                                "function"
                            ) {

                                try {

                                    value =
                                        column.formatter(
                                            raw,
                                            row
                                        );

                                } catch (
                                    error
                                ) {

                                    value =
                                        raw ??
                                        "-";

                                }

                            }


                            return `
                                <td>
                                    ${escapeHtml(
                                        value ??
                                        "-"
                                    )}
                                </td>
                            `;

                        }
                    )
                    .join("")
            }

        </tr>
    `;

}


/* =========================================================
   INFER TABLE COLUMNS
========================================================= */

function inferColumns(
    data
) {

    if (
        !data.length
    ) {

        return [];

    }


    const first =
        data[0];


    return Object.keys(
        first
    )
        .filter(
            key =>
                typeof first[key] !==
                "object"
        )
        .slice(
            0,
            10
        )
        .map(
            key => ({

                key,

                label:
                    prettifyKey(
                        key
                    ),

                formatter:
                    value =>
                        value ?? "-"

            })
        );

}


/* =========================================================
   CHART RENDERING
========================================================= */

function renderReportChart() {

    const canvas =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.chart
        );


    if (!canvas) {
        return;
    }


    /*
     * Use Chart.js when available.
     */
    if (
        typeof Chart !==
        "undefined"
    ) {

        renderChartJS(
            canvas
        );

        return;

    }


    /*
     * If Chart.js isn't loaded,
     * display a simple textual fallback.
     */
    renderSimpleChart(
        canvas
    );

}


/* =========================================================
   CHART.JS
========================================================= */

function renderChartJS(
    canvas
) {

    if (
        canvas._reportChart
    ) {

        canvas._reportChart.destroy();

    }


    const chartData =
        buildChartData();


    if (!chartData) {
        return;
    }


    canvas._reportChart =
        new Chart(
            canvas,
            {

                type:
                    chartData.type ||
                    "line",

                data: {

                    labels:
                        chartData.labels,

                    datasets:
                        chartData.datasets

                },

                options: {

                    responsive:
                        true,

                    maintainAspectRatio:
                        false,

                    plugins: {

                        legend: {

                            display:
                                true

                        }

                    },

                    scales: {

                        y: {

                            beginAtZero:
                                true

                        }

                    }

                }

            }
        );

}


/* =========================================================
   BUILD CHART DATA
========================================================= */

function buildChartData() {

    const data =
        reportState.data;


    if (!data.length) {

        return null;

    }


    switch (
        reportState.currentType
    ) {

        case "revenue":

            return {

                type:
                    "line",

                labels:
                    data.map(
                        row =>
                            formatDate(
                                row.date ||
                                row.created_at
                            )
                    ),

                datasets: [

                    {

                        label:
                            "Revenue",

                        data:
                            data.map(
                                row =>
                                    Number(
                                        row.revenue ??
                                        row.total ??
                                        row.amount ??
                                        0
                                    )
                            ),

                        borderColor:
                            "#2563eb",

                        backgroundColor:
                            "rgba(37, 99, 235, 0.15)",

                        fill:
                            true,

                        tension:
                            0.3

                    }

                ]

            };


        case "occupancy":

            return {

                type:
                    "line",

                labels:
                    data.map(
                        row =>
                            formatDate(
                                row.date
                            )
                    ),

                datasets: [

                    {

                        label:
                            "Occupancy %",

                        data:
                            data.map(
                                row =>
                                    Number(
                                        row.occupancy ??
                                        calculateOccupancy(
                                            row
                                        )
                                    )
                            ),

                        borderColor:
                            "#16a34a",

                        backgroundColor:
                            "rgba(22, 163, 74, 0.15)",

                        fill:
                            true,

                        tension:
                            0.3

                    }

                ]

            };


        case "bookings":

            return {

                type:
                    "bar",

                labels:
                    data.map(
                        row =>
                            formatDate(
                                row.date ||
                                row.check_in
                            )
                    ),

                datasets: [

                    {

                        label:
                            "Bookings",

                        data:
                            data.map(
                                row =>
                                    Number(
                                        row.bookings ??
                                        row.total ??
                                        1
                                    )
                            ),

                        backgroundColor:
                            "#7c3aed"

                    }

                ]

            };


        case "payments":

            return {

                type:
                    "bar",

                labels:
                    data.map(
                        row =>
                            formatDate(
                                row.date ||
                                row.created_at
                            )
                    ),

                datasets: [

                    {

                        label:
                            "Payments",

                        data:
                            data.map(
                                row =>
                                    Number(
                                        row.amount ??
                                        row.total ??
                                        0
                                    )
                            ),

                        backgroundColor:
                            "#0891b2"

                    }

                ]

            };


        default:

            return {

                type:
                    "bar",

                labels:
                    data.map(
                        (
                            row,
                            index
                        ) =>
                            row.name ||
                            row.label ||
                            row.room_number ||
                            row.id ||
                            `Item ${index + 1}`
                    ),

                datasets: [

                    {

                        label:
                            "Value",

                        data:
                            data.map(
                                row =>
                                    Number(
                                        row.value ??
                                        row.total ??
                                        row.amount ??
                                        row.count ??
                                        0
                                    )
                            ),

                        backgroundColor:
                            "#2563eb"

                    }

                ]

            };

    }

}


/* =========================================================
   SIMPLE CHART FALLBACK
========================================================= */

function renderSimpleChart(
    canvas
) {

    const parent =
        canvas.parentElement;


    if (!parent) {
        return;
    }


    const data =
        buildChartData();


    if (!data) {
        return;
    }


    const values =
        data.datasets?.[0]?.data ||
        [];


    const max =
        Math.max(
            ...values,
            1
        );


    const chart =
        document.createElement(
            "div"
        );


    chart.className =
        "simple-report-chart";


    chart.innerHTML =
        values
            .map(
                (
                    value,
                    index
                ) => {

                    const width =
                        (
                            Number(value) /
                            max
                        ) * 100;


                    return `
                        <div
                            class="simple-chart-row"
                        >

                            <span>
                                ${escapeHtml(
                                    data.labels[index] ||
                                    ""
                                )}
                            </span>

                            <div
                                class="simple-chart-bar"
                            >

                                <div
                                    style="
                                        width:${Math.max(
                                            width,
                                            2
                                        )}%;
                                    "
                                ></div>

                            </div>

                            <strong>
                                ${escapeHtml(
                                    value
                                )}
                            </strong>

                        </div>
                    `;

                }
            )
            .join("");


    canvas.style.display =
        "none";


    parent
        .querySelector(
            ".simple-report-chart"
        )
        ?.remove();


    parent.appendChild(
        chart
    );

}


/* =========================================================
   CSV EXPORT
========================================================= */

function exportReportCSV(
    event = null
) {

    if (event) {

        event.preventDefault();

    }


    const data =
        reportState.data;


    if (!data.length) {

        showReportError(
            "There is no report data to export."
        );


        return;

    }


    const columns =
        getReportColumns(
            reportState.currentType,
            data
        );


    const header =
        columns.map(
            column =>
                csvEscape(
                    column.label
                )
        );


    const rows =
        data.map(
            row =>
                columns.map(
                    column => {

                        const raw =
                            getRowValue(
                                row,
                                column.key
                            );


                        let value =
                            raw;


                        if (
                            typeof column.formatter ===
                            "function"
                        ) {

                            try {

                                value =
                                    column.formatter(
                                        raw,
                                        row
                                    );

                            } catch (
                                error
                            ) {

                                value =
                                    raw ??
                                    "";

                            }

                        }


                        return csvEscape(
                            value
                        );

                    }
                )
        );


    const csv =
        [
            header,
            ...rows
        ]
            .map(
                row =>
                    row.join(",")
            )
            .join("\n");


    const filename =
        `${reportState.currentType}-report-${reportState.startDate || "report"}-${reportState.endDate || ""}.csv`;


    downloadFile(
        csv,
        filename,
        "text/csv;charset=utf-8;"
    );


    showReportSuccess(
        "Report exported successfully."
    );

}


/* =========================================================
   PRINT REPORT
========================================================= */

function printReport(
    event = null
) {

    if (event) {

        event.preventDefault();

    }


    if (!reportState.currentReport) {

        showReportError(
            "Generate a report before printing."
        );


        return;

    }


    window.print();

}


/* =========================================================
   RESET FILTERS
========================================================= */

function resetReportFilters(
    event = null
) {

    if (event) {

        event.preventDefault();

    }


    const today =
        new Date();


    const firstDay =
        new Date(
            today.getFullYear(),
            today.getMonth(),
            1
        );


    setInputValue(
        REPORT_CONFIG.SELECTORS.startDate,
        toInputDate(firstDay)
    );


    setInputValue(
        REPORT_CONFIG.SELECTORS.endDate,
        toInputDate(today)
    );


    const range =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.dateRange
        );


    if (range) {

        range.value =
            "";

    }


    reportState.startDate =
        toInputDate(
            firstDay
        );


    reportState.endDate =
        toInputDate(
            today
        );


    generateReport();

}


/* =========================================================
   REPORT TYPE
========================================================= */

function getSelectedReportType() {

    const select =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportType
        );


    const value =
        select?.value ||
        reportState.currentType ||
        "dashboard";


    return value;

}


function updateReportLabels() {

    reportState.currentType =
        getSelectedReportType();


    updateReportHeader();

}


/* =========================================================
   DATE VALIDATION
========================================================= */

function getSelectedDates() {

    const startInput =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.startDate
        );


    const endInput =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.endDate
        );


    const startDate =
        startInput?.value ||
        reportState.startDate;


    const endDate =
        endInput?.value ||
        reportState.endDate;


    if (!startDate) {

        return {

            valid: false,

            message:
                "Please select a start date."

        };

    }


    if (!endDate) {

        return {

            valid: false,

            message:
                "Please select an end date."

        };

    }


    const start =
        new Date(
            `${startDate}T00:00:00`
        );


    const end =
        new Date(
            `${endDate}T00:00:00`
        );


    if (
        Number.isNaN(
            start.getTime()
        ) ||
        Number.isNaN(
            end.getTime()
        )
    ) {

        return {

            valid: false,

            message:
                "Please enter valid dates."

        };

    }


    if (start > end) {

        return {

            valid: false,

            message:
                "Start date cannot be later than end date."

        };

    }


    return {

        valid: true,

        startDate,

        endDate

    };

}


/* =========================================================
   REPORT UI STATE
========================================================= */

function showReportLoading() {

    const loading =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.loading
        );


    if (loading) {

        loading.hidden =
            false;

    }


    const container =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportContainer
        );


    if (
        container &&
        !reportState.currentReport
    ) {

        container.classList.add(
            "is-loading"
        );

    }

}


function hideReportLoading() {

    const loading =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.loading
        );


    if (loading) {

        loading.hidden =
            true;

    }


    const container =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportContainer
        );


    if (container) {

        container.classList.remove(
            "is-loading"
        );

    }

}


function showReportContent() {

    const container =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.reportContainer
        );


    if (container) {

        container.hidden =
            false;

    }


    const error =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.error
        );


    if (error) {

        error.hidden =
            true;

    }

}


function showReportError(
    message
) {

    const error =
        document.querySelector(
            REPORT_CONFIG.SELECTORS.error
        );


    if (error) {

        error.textContent =
            message;


        error.hidden =
            false;

    } else if (
        window.HotelApp &&
        typeof HotelApp.showAlert ===
        "function"
    ) {

        HotelApp.showAlert(
            message,
            "error"
        );

    } else {

        console.error(
            message
        );

    }

}


function showReportSuccess(
    message
) {

    if (
        window.HotelApp &&
        typeof HotelApp.showAlert ===
        "function"
    ) {

        HotelApp.showAlert(
            message,
            "success"
        );

    }

}


function disableReportActions(
    disabled
) {

    [
        REPORT_CONFIG.SELECTORS.generateButton,
        REPORT_CONFIG.SELECTORS.exportButton,
        REPORT_CONFIG.SELECTORS.printButton,
        REPORT_CONFIG.SELECTORS.resetButton
    ]
        .forEach(
            selector => {

                const button =
                    document.querySelector(
                        selector
                    );


                if (button) {

                    button.disabled =
                        disabled;

                }

            }
        );

}


/* =========================================================
   GENERAL HELPERS
========================================================= */

function getRowValue(
    row,
    key
) {

    if (!row) {
        return undefined;
    }


    if (
        Object.prototype.hasOwnProperty.call(
            row,
            key
        )
    ) {

        return row[key];

    }


    /*
     * Support common nested structures.
     */

    if (
        key === "guest_name" &&
        row.guest
    ) {

        return (
            row.guest.name ||
            `${row.guest.first_name || ""} ${
                row.guest.last_name || ""
            }`.trim()
        );

    }


    if (
        key === "room_number" &&
        row.room
    ) {

        return (
            row.room.room_number ||
            row.room.number
        );

    }


    if (
        key === "booking_id" &&
        row.booking
    ) {

        return row.booking.id;

    }


    if (
        key === "payment_id" &&
        row.payment
    ) {

        return row.payment.id;

    }


    if (
        key === "staff_id" &&
        row.staff
    ) {

        return row.staff.id;

    }


    return undefined;

}


function calculateOccupancy(
    row
) {

    const occupied =
        Number(
            row.occupied ??
            row.occupied_rooms ??
            0
        );


    const total =
        Number(
            row.total ??
            row.total_rooms ??
            0
        );


    if (!total) {

        return 0;

    }


    return (
        occupied /
        total
    ) * 100;

}


function normalizeStatus(
    value
) {

    return String(
        value ||
        ""
    )
        .trim()
        .toLowerCase()
        .replace(
            /[\s-]+/g,
            "_"
        );

}


function formatStatus(
    value
) {

    if (
        value === null ||
        value === undefined ||
        value === ""
    ) {

        return "-";

    }


    return String(value)
        .replace(
            /[_-]+/g,
            " "
        )
        .replace(
            /\b\w/g,
            char =>
                char.toUpperCase()
        );

}


function formatDate(
    value
) {

    if (!value) {

        return "-";

    }


    const date =
        new Date(
            value
        );


    if (
        Number.isNaN(
            date.getTime()
        )
    ) {

        return String(value);

    }


    return new Intl.DateTimeFormat(
        undefined,
        {
            year:
                "numeric",

            month:
                "short",

            day:
                "numeric"
        }
    ).format(date);

}


function formatCurrency(
    value
) {

    const amount =
        Number(
            value
        );


    if (
        Number.isNaN(
            amount
        )
    ) {

        return "$0.00";

    }


    return new Intl.NumberFormat(
        undefined,
        {
            style:
                "currency",

            currency:
                "USD"
        }
    ).format(
        amount
    );

}


function formatPercent(
    value
) {

    const number =
        Number(
            value
        );


    if (
        Number.isNaN(
            number
        )
    ) {

        return "0%";

    }


    return `${number.toFixed(1)}%`;

}


function toInputDate(
    date
) {

    const year =
        date.getFullYear();


    const month =
        String(
            date.getMonth() + 1
        )
            .padStart(
                2,
                "0"
            );


    const day =
        String(
            date.getDate()
        )
            .padStart(
                2,
                "0"
            );


    return `${year}-${month}-${day}`;

}


function setInputValue(
    selector,
    value
) {

    const element =
        document.querySelector(
            selector
        );


    if (element) {

        element.value =
            value;

    }

}


function setText(
    selector,
    value
) {

    const element =
        document.querySelector(
            selector
        );


    if (element) {

        element.textContent =
            value ??
            "";

    }

}


function prettifyKey(
    key
) {

    return String(key)
        .replace(
            /[_-]+/g,
            " "
        )
        .replace(
            /\b\w/g,
            char =>
                char.toUpperCase()
        );

}


function csvEscape(
    value
) {

    if (
        value === null ||
        value === undefined
    ) {

        return '""';

    }


    const stringValue =
        String(value)
            .replace(
                /"/g,
                '""'
            );


    return `"${stringValue}"`;

}


function downloadFile(
    content,
    filename,
    mimeType
) {

    const blob =
        new Blob(
            [
                content
            ],
            {
                type:
                    mimeType
            }
        );


    const url =
        URL.createObjectURL(
            blob
        );


    const link =
        document.createElement(
            "a"
        );


    link.href =
        url;


    link.download =
        filename;


    document.body.appendChild(
        link
    );


    link.click();


    link.remove();


    URL.revokeObjectURL(
        url
    );

}


function escapeHtml(
    value
) {

    if (
        value === null ||
        value === undefined
    ) {

        return "";

    }


    return String(value)
        .replace(
            /&/g,
            "&amp;"
        )
        .replace(
            /</g,
            "&lt;"
        )
        .replace(
            />/g,
            "&gt;"
        )
        .replace(
            /"/g,
            "&quot;"
        )
        .replace(
            /'/g,
            "&#039;"
        );

}


function getErrorMessage(
    error,
    fallback
) {

    return (
        error?.message ||
        error?.response?.message ||
        fallback
    );

}


/* =========================================================
   PUBLIC REPORT API
========================================================= */

window.HotelReports = {

    generate:
        generateReport,


    fetch:
        fetchReport,


    exportCSV:
        exportReportCSV,


    print:
        printReport,


    reset:
        resetReportFilters,


    getCurrentReport:
        () =>
            reportState.currentReport,


    getData:
        () =>
            [
                ...reportState.data
            ],


    getSummary:
        () =>
            ({
                ...reportState.summary
            }),


    getState:
        () =>
            ({
                ...reportState,

                data:
                    [
                        ...reportState.data
                    ],

                summary:
                    {
                        ...reportState.summary
                    }

            })

};
