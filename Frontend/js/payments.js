/**
 * Hotel Management System
 * frontend/js/payments.js
 *
 * Payment / Billing Operations
 *
 * Responsibilities:
 * - Load payments
 * - Search and filter payments
 * - Create payments
 * - Update payment records
 * - Record refunds
 * - Update payment status
 * - Calculate booking balance
 * - Generate invoice data
 * - Render invoices
 * - Print invoices
 * - Handle payment forms
 */

"use strict";


/* =========================================================
   PAYMENT CONFIGURATION
========================================================= */

const PAYMENT_CONFIG = {

    SELECTORS: {

        paymentContainer:
            "#paymentsContainer",

        paymentTableBody:
            "#paymentsTableBody",

        searchInput:
            "#paymentSearch",

        statusFilter:
            "#paymentStatusFilter",

        methodFilter:
            "#paymentMethodFilter",

        paymentForm:
            "#paymentForm",

        paymentId:
            "#paymentId",

        bookingId:
            "#paymentBookingId",

        guestId:
            "#paymentGuestId",

        amount:
            "#paymentAmount",

        paymentMethod:
            "#paymentMethod",

        paymentStatus:
            "#paymentStatus",

        transactionId:
            "#transactionId",

        paymentDate:
            "#paymentDate",

        notes:
            "#paymentNotes",

        refundAmount:
            "#refundAmount",

        saveButton:
            "#savePaymentBtn",

        totalPayments:
            "#totalPayments",

        totalRevenue:
            "#totalRevenue",

        pendingPayments:
            "#pendingPayments",

        paidPayments:
            "#paidPayments",

        refundedPayments:
            "#refundedPayments",

        invoiceContainer:
            "#invoiceContainer",

        invoiceNumber:
            "#invoiceNumber",

        invoiceDate:
            "#invoiceDate",

        invoiceDueDate:
            "#invoiceDueDate",

        invoiceGuest:
            "#invoiceGuest",

        invoiceBooking:
            "#invoiceBooking",

        invoiceRoom:
            "#invoiceRoom",

        invoiceCheckIn:
            "#invoiceCheckIn",

        invoiceCheckOut:
            "#invoiceCheckOut",

        invoiceItems:
            "#invoiceItems",

        invoiceSubtotal:
            "#invoiceSubtotal",

        invoiceDiscount:
            "#invoiceDiscount",

        invoiceTax:
            "#invoiceTax",

        invoiceTotal:
            "#invoiceTotal",

        invoicePaid:
            "#invoicePaid",

        invoiceBalance:
            "#invoiceBalance"

    },


    STATUSES: [
        "pending",
        "partial",
        "paid",
        "failed",
        "refunded",
        "cancelled"
    ],


    METHODS: [
        "cash",
        "card",
        "bank_transfer",
        "online",
        "mobile_money",
        "check",
        "other"
    ]

};


/* =========================================================
   PAYMENT STATE
========================================================= */

const paymentState = {

    payments: [],

    filteredPayments: [],

    selectedPayment: null,

    selectedBooking: null,

    loading: false,

    editing: false

};


/* =========================================================
   INITIALIZATION
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        initializePayments();

    }
);


async function initializePayments() {

    if (
        window.HotelAuth &&
        !HotelAuth.requireAuth()
    ) {

        return;

    }


    initializePaymentEvents();

    await loadPayments();


    /*
     * If this is the invoice page,
     * automatically load invoice data.
     */
    const params =
        new URLSearchParams(
            window.location.search
        );


    const bookingId =
        params.get(
            "booking_id"
        );


    const paymentId =
        params.get(
            "payment_id"
        );


    if (bookingId) {

        await loadInvoiceByBooking(
            bookingId
        );

    } else if (paymentId) {

        await loadInvoiceByPayment(
            paymentId
        );

    }

}


/* =========================================================
   EVENT INITIALIZATION
========================================================= */

function initializePaymentEvents() {

    const searchInput =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.searchInput
        );


    const statusFilter =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.statusFilter
        );


    const methodFilter =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.methodFilter
        );


    const paymentForm =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.paymentForm
        );


    if (searchInput) {

        searchInput.addEventListener(
            "input",
            debouncePaymentSearch()
        );

    }


    if (statusFilter) {

        statusFilter.addEventListener(
            "change",
            filterPayments
        );

    }


    if (methodFilter) {

        methodFilter.addEventListener(
            "change",
            filterPayments
        );

    }


    if (paymentForm) {

        paymentForm.addEventListener(
            "submit",
            handlePaymentFormSubmit
        );

    }


    document.addEventListener(
        "click",
        handlePaymentActions
    );

}


/* =========================================================
   LOAD PAYMENTS
========================================================= */

async function loadPayments(
    filters = {}
) {

    if (paymentState.loading) {
        return;
    }


    paymentState.loading =
        true;


    showPaymentsLoading();


    try {

        const response =
            await HotelAPI.getPayments(
                filters
            );


        paymentState.payments =
            extractPaymentList(
                response
            );


        paymentState.filteredPayments =
            [
                ...paymentState.payments
            ];


        updatePaymentStatistics();

        renderPayments();


    } catch (error) {

        console.error(
            "Failed to load payments:",
            error
        );


        showPaymentError(
            error
        );


    } finally {

        paymentState.loading =
            false;


        hidePaymentsLoading();

    }

}


/* =========================================================
   EXTRACT PAYMENT LIST
========================================================= */

function extractPaymentList(
    response
) {

    if (!response) {
        return [];
    }


    if (Array.isArray(response)) {
        return response;
    }


    if (Array.isArray(response.data)) {
        return response.data;
    }


    if (
        Array.isArray(
            response.data?.items
        )
    ) {

        return response.data.items;

    }


    if (
        Array.isArray(
            response.items
        )
    ) {

        return response.items;

    }


    return [];

}


/* =========================================================
   RENDER PAYMENTS
========================================================= */

function renderPayments() {

    renderPaymentCards();

    renderPaymentTable();

}


/* =========================================================
   PAYMENT CARDS
========================================================= */

function renderPaymentCards() {

    const container =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.paymentContainer
        );


    if (!container) {
        return;
    }


    const payments =
        paymentState.filteredPayments;


    if (!payments.length) {

        container.innerHTML = `
            <div class="empty-state">

                <h3>
                    No payments found
                </h3>

                <p>
                    There are no payment records
                    matching your filters.
                </p>

            </div>
        `;

        return;

    }


    container.innerHTML =
        payments
            .map(
                payment =>
                    createPaymentCard(
                        payment
                    )
            )
            .join("");

}


/* =========================================================
   PAYMENT CARD
========================================================= */

function createPaymentCard(
    payment
) {

    const id =
        getPaymentId(
            payment
        );


    const reference =
        getPaymentReference(
            payment
        );


    const guest =
        getPaymentGuestName(
            payment
        );


    const amount =
        getPaymentAmount(
            payment
        );


    const status =
        getPaymentStatus(
            payment
        );


    return `
        <article
            class="payment-card"
            data-payment-id="${escapeHtml(id)}"
        >

            <div class="payment-card-header">

                <div>

                    <h3>
                        ${escapeHtml(reference)}
                    </h3>

                    <p>
                        ${escapeHtml(guest)}
                    </p>

                </div>


                <span
                    class="status status-${escapeHtml(
                        status
                    )}"
                >
                    ${escapeHtml(
                        formatPaymentStatus(
                            status
                        )
                    )}
                </span>

            </div>


            <div class="payment-card-body">

                <p>
                    <strong>Amount:</strong>
                    ${escapeHtml(
                        formatCurrency(
                            amount
                        )
                    )}
                </p>

                <p>
                    <strong>Method:</strong>
                    ${escapeHtml(
                        formatPaymentMethod(
                            getPaymentMethod(
                                payment
                            )
                        )
                    )}
                </p>

                <p>
                    <strong>Date:</strong>
                    ${escapeHtml(
                        formatDate(
                            getPaymentDate(
                                payment
                            )
                        )
                    )}
                </p>


                ${
                    getTransactionId(
                        payment
                    )
                        ? `
                            <p>
                                <strong>Transaction:</strong>
                                ${escapeHtml(
                                    getTransactionId(
                                        payment
                                    )
                                )}
                            </p>
                        `
                        : ""
                }

            </div>


            <div class="payment-card-actions">

                <button
                    type="button"
                    class="btn btn-primary"
                    data-action="view-payment"
                    data-payment-id="${escapeHtml(id)}"
                >
                    View
                </button>


                <button
                    type="button"
                    class="btn btn-secondary"
                    data-action="edit-payment"
                    data-payment-id="${escapeHtml(id)}"
                >
                    Edit
                </button>


                <button
                    type="button"
                    class="btn btn-secondary"
                    data-action="invoice-payment"
                    data-payment-id="${escapeHtml(id)}"
                >
                    Invoice
                </button>


                ${
                    status === "paid"
                        ? `
                            <button
                                type="button"
                                class="btn btn-danger"
                                data-action="refund-payment"
                                data-payment-id="${escapeHtml(id)}"
                            >
                                Refund
                            </button>
                        `
                        : ""
                }

            </div>

        </article>
    `;

}


/* =========================================================
   PAYMENT TABLE
========================================================= */

function renderPaymentTable() {

    const tbody =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.paymentTableBody
        );


    if (!tbody) {
        return;
    }


    const payments =
        paymentState.filteredPayments;


    if (!payments.length) {

        tbody.innerHTML = `
            <tr>

                <td
                    colspan="9"
                    class="empty-state"
                >
                    No payments found.
                </td>

            </tr>
        `;

        return;

    }


    tbody.innerHTML =
        payments
            .map(
                payment =>
                    createPaymentTableRow(
                        payment
                    )
            )
            .join("");

}


/* =========================================================
   PAYMENT TABLE ROW
========================================================= */

function createPaymentTableRow(
    payment
) {

    const id =
        getPaymentId(
            payment
        );


    const status =
        getPaymentStatus(
            payment
        );


    const method =
        getPaymentMethod(
            payment
        );


    return `
        <tr
            data-payment-id="${escapeHtml(id)}"
        >

            <td>
                ${escapeHtml(
                    getPaymentReference(
                        payment
                    )
                )}
            </td>


            <td>
                ${escapeHtml(
                    getPaymentGuestName(
                        payment
                    )
                )}
            </td>


            <td>
                ${escapeHtml(
                    getBookingReferenceFromPayment(
                        payment
                    )
                )}
            </td>


            <td>
                ${escapeHtml(
                    formatCurrency(
                        getPaymentAmount(
                            payment
                        )
                    )
                )}
            </td>


            <td>
                ${escapeHtml(
                    formatPaymentMethod(
                        method
                    )
                )}
            </td>


            <td>
                ${escapeHtml(
                    formatDate(
                        getPaymentDate(
                            payment
                        )
                    )
                )}
            </td>


            <td>

                <span
                    class="status status-${escapeHtml(
                        status
                    )}"
                >
                    ${escapeHtml(
                        formatPaymentStatus(
                            status
                        )
                    )}
                </span>

            </td>


            <td>
                ${escapeHtml(
                    getTransactionId(
                        payment
                    ) || "-"
                )}
            </td>


            <td>

                <button
                    type="button"
                    class="btn btn-sm"
                    data-action="view-payment"
                    data-payment-id="${escapeHtml(id)}"
                >
                    View
                </button>


                <button
                    type="button"
                    class="btn btn-sm"
                    data-action="edit-payment"
                    data-payment-id="${escapeHtml(id)}"
                >
                    Edit
                </button>


                <button
                    type="button"
                    class="btn btn-sm"
                    data-action="invoice-payment"
                    data-payment-id="${escapeHtml(id)}"
                >
                    Invoice
                </button>


                ${
                    status === "paid"
                        ? `
                            <button
                                type="button"
                                class="btn btn-sm btn-danger"
                                data-action="refund-payment"
                                data-payment-id="${escapeHtml(id)}"
                            >
                                Refund
                            </button>
                        `
                        : ""
                }

            </td>

        </tr>
    `;

}


/* =========================================================
   SEARCH / FILTER
========================================================= */

function debouncePaymentSearch() {

    let timeout;


    return () => {

        clearTimeout(
            timeout
        );


        timeout =
            setTimeout(
                filterPayments,
                300
            );

    };

}


function filterPayments() {

    const search =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.searchInput
        )?.value
            ?.trim()
            .toLowerCase() || "";


    const status =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.statusFilter
        )?.value
            ?.trim()
            .toLowerCase() || "";


    const method =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.methodFilter
        )?.value
            ?.trim()
            .toLowerCase() || "";


    paymentState.filteredPayments =
        paymentState.payments.filter(
            payment => {

                const reference =
                    getPaymentReference(
                        payment
                    ).toLowerCase();


                const guest =
                    getPaymentGuestName(
                        payment
                    ).toLowerCase();


                const transaction =
                    getTransactionId(
                        payment
                    ).toLowerCase();


                const booking =
                    getBookingReferenceFromPayment(
                        payment
                    ).toLowerCase();


                const paymentStatus =
                    getPaymentStatus(
                        payment
                    ).toLowerCase();


                const paymentMethod =
                    getPaymentMethod(
                        payment
                    ).toLowerCase();


                const matchesSearch =
                    !search ||
                    reference.includes(search) ||
                    guest.includes(search) ||
                    transaction.includes(search) ||
                    booking.includes(search);


                const matchesStatus =
                    !status ||
                    paymentStatus === status;


                const matchesMethod =
                    !method ||
                    paymentMethod === method;


                return (
                    matchesSearch &&
                    matchesStatus &&
                    matchesMethod
                );

            }
        );


    renderPayments();

}


/* =========================================================
   GET PAYMENT
========================================================= */

async function getPayment(
    paymentId
) {

    if (!paymentId) {

        throw new Error(
            "Payment ID is required."
        );

    }


    try {

        const response =
            await HotelAPI.getPayment(
                paymentId
            );


        const payment =
            response?.data ??
            response;


        paymentState.selectedPayment =
            payment;


        return payment;

    } catch (error) {

        console.error(
            "Failed to get payment:",
            error
        );


        throw error;

    }

}


/* =========================================================
   CREATE PAYMENT
========================================================= */

async function createPayment(
    data
) {

    const validation =
        validatePaymentData(
            data
        );


    if (!validation.valid) {

        throw new Error(
            validation.message
        );

    }


    try {

        const response =
            await HotelAPI.createPayment(
                data
            );


        if (window.HotelApp) {

            HotelApp.showAlert(
                "Payment recorded successfully.",
                "success"
            );

        }


        await loadPayments();


        return response;

    } catch (error) {

        console.error(
            "Failed to create payment:",
            error
        );


        showPaymentError(
            error
        );


        throw error;

    }

}


/* =========================================================
   UPDATE PAYMENT
========================================================= */

async function updatePayment(
    paymentId,
    data
) {

    if (!paymentId) {

        throw new Error(
            "Payment ID is required."
        );

    }


    const validation =
        validatePaymentData(
            data,
            true
        );


    if (!validation.valid) {

        throw new Error(
            validation.message
        );

    }


    try {

        const response =
            await HotelAPI.updatePayment(
                paymentId,
                data
            );


        if (window.HotelApp) {

            HotelApp.showAlert(
                "Payment updated successfully.",
                "success"
            );

        }


        await loadPayments();


        return response;

    } catch (error) {

        console.error(
            "Failed to update payment:",
            error
        );


        showPaymentError(
            error
        );


        throw error;

    }

}


/* =========================================================
   PAYMENT FORM
========================================================= */

function openPaymentForm(
    payment = null
) {

    const form =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.paymentForm
        );


    if (!form) {
        return;
    }


    paymentState.selectedPayment =
        payment;


    paymentState.editing =
        Boolean(payment);


    form.reset();


    setField(
        PAYMENT_CONFIG.SELECTORS.paymentId,
        getPaymentId(
            payment
        )
    );


    setField(
        PAYMENT_CONFIG.SELECTORS.bookingId,
        payment?.booking_id ||
        payment?.bookingId ||
        payment?.booking?.id ||
        ""
    );


    setField(
        PAYMENT_CONFIG.SELECTORS.guestId,
        payment?.guest_id ||
        payment?.guestId ||
        payment?.guest?.id ||
        ""
    );


    setField(
        PAYMENT_CONFIG.SELECTORS.amount,
        getPaymentAmount(
            payment
        )
    );


    setField(
        PAYMENT_CONFIG.SELECTORS.paymentMethod,
        getPaymentMethod(
            payment
        )
    );


    setField(
        PAYMENT_CONFIG.SELECTORS.paymentStatus,
        getPaymentStatus(
            payment
        )
    );


    setField(
        PAYMENT_CONFIG.SELECTORS.transactionId,
        getTransactionId(
            payment
        )
    );


    setField(
        PAYMENT_CONFIG.SELECTORS.paymentDate,
        getPaymentDate(
            payment
        ) || getLocalDateTime()
    );


    setField(
        PAYMENT_CONFIG.SELECTORS.notes,
        payment?.notes ||
        ""
    );


    updatePaymentFormTitle();


    if (window.HotelApp) {

        HotelApp.openModal(
            "paymentModal"
        );

    }

}


async function editPayment(
    paymentId
) {

    try {

        const payment =
            await getPayment(
                paymentId
            );


        openPaymentForm(
            payment
        );

    } catch (error) {

        showPaymentError(
            error
        );

    }

}


/* =========================================================
   PAYMENT FORM DATA
========================================================= */

function getPaymentFormData(
    form
) {

    const formData =
        new FormData(
            form
        );


    return {

        id:
            formData.get("id") ||
            formData.get("payment_id") ||
            "",


        booking_id:
            String(
                formData.get(
                    "booking_id"
                ) || ""
            ).trim(),


        guest_id:
            String(
                formData.get(
                    "guest_id"
                ) || ""
            ).trim(),


        amount:
            Number(
                formData.get(
                    "amount"
                ) || 0
            ),


        payment_method:
            String(
                formData.get(
                    "payment_method"
                ) || ""
            ).trim(),


        payment_status:
            String(
                formData.get(
                    "payment_status"
                ) || "pending"
            ).trim(),


        transaction_id:
            String(
                formData.get(
                    "transaction_id"
                ) || ""
            ).trim(),


        payment_date:
            String(
                formData.get(
                    "payment_date"
                ) || ""
            ).trim(),


        notes:
            String(
                formData.get(
                    "notes"
                ) || ""
            ).trim()

    };

}


/* =========================================================
   SUBMIT PAYMENT FORM
========================================================= */

async function handlePaymentFormSubmit(
    event
) {

    event.preventDefault();


    const form =
        event.currentTarget;


    const data =
        getPaymentFormData(
            form
        );


    const validation =
        validatePaymentData(
            data,
            Boolean(data.id)
        );


    if (!validation.valid) {

        showPaymentError(
            new Error(
                validation.message
            )
        );


        return;

    }


    try {

        setPaymentFormLoading(
            true
        );


        let response;


        if (data.id) {

            response =
                await updatePayment(
                    data.id,
                    data
                );

        } else {

            response =
                await createPayment(
                    data
                );

        }


        closePaymentForm();


        return response;

    } catch (error) {

        console.error(
            "Payment form submission failed:",
            error
        );

        /*
         * Error already displayed by the
         * create/update functions.
         */

    } finally {

        setPaymentFormLoading(
            false
        );

    }

}


/* =========================================================
   PAYMENT VALIDATION
========================================================= */

function validatePaymentData(
    data,
    isUpdate = false
) {

    if (
        !isUpdate &&
        !data.booking_id
    ) {

        return {
            valid: false,
            message:
                "Booking ID is required."
        };

    }


    if (
        !data.amount ||
        Number(data.amount) <= 0
    ) {

        return {
            valid: false,
            message:
                "Payment amount must be greater than zero."
        };

    }


    if (
        !data.payment_method
    ) {

        return {
            valid: false,
            message:
                "Please select a payment method."
        };

    }


    if (
        !PAYMENT_CONFIG.METHODS.includes(
            data.payment_method
        )
    ) {

        return {
            valid: false,
            message:
                "Invalid payment method."
        };

    }


    if (
        !PAYMENT_CONFIG.STATUSES.includes(
            data.payment_status
        )
    ) {

        return {
            valid: false,
            message:
                "Invalid payment status."
        };

    }


    if (
        data.payment_date
    ) {

        const date =
            new Date(
                data.payment_date
            );


        if (
            Number.isNaN(
                date.getTime()
            )
        ) {

            return {
                valid: false,
                message:
                    "Invalid payment date."
            };

        }

    }


    return {
        valid: true,
        message: ""
    };

}


/* =========================================================
   PAYMENT STATUS
========================================================= */

async function updatePaymentStatus(
    paymentId,
    status
) {

    if (
        !paymentId ||
        !status
    ) {

        throw new Error(
            "Payment ID and status are required."
        );

    }


    if (
        !PAYMENT_CONFIG.STATUSES.includes(
            status
        )
    ) {

        throw new Error(
            "Invalid payment status."
        );

    }


    try {

        let response;


        if (
            typeof HotelAPI.updatePaymentStatus ===
            "function"
        ) {

            response =
                await HotelAPI.updatePaymentStatus(
                    paymentId,
                    status
                );

        } else {

            response =
                await HotelAPI.updatePayment(
                    paymentId,
                    {
                        payment_status:
                            status
                    }
                );

        }


        if (window.HotelApp) {

            HotelApp.showAlert(
                "Payment status updated successfully.",
                "success"
            );

        }


        await loadPayments();


        return response;

    } catch (error) {

        console.error(
            "Failed to update payment status:",
            error
        );


        showPaymentError(
            error
        );


        throw error;

    }

}


/* =========================================================
   REFUND
========================================================= */

async function refundPayment(
    paymentId,
    amount = null,
    reason = ""
) {

    const payment =
        await getPayment(
            paymentId
        );


    const originalAmount =
        getPaymentAmount(
            payment
        );


    const refundAmount =
        amount === null
            ? originalAmount
            : Number(amount);


    if (
        refundAmount <= 0
    ) {

        throw new Error(
            "Refund amount must be greater than zero."
        );

    }


    if (
        refundAmount >
        originalAmount
    ) {

        throw new Error(
            "Refund amount cannot exceed the payment amount."
        );

    }


    let refundReason =
        reason;


    if (!refundReason) {

        refundReason =
            window.prompt(
                "Enter refund reason:",
                ""
            ) || "";

    }


    const confirmed =
        window.confirm(
            `Refund ${formatCurrency(
                refundAmount
            )} for payment ${getPaymentReference(
                payment
            )}?`
        );


    if (!confirmed) {
        return null;
    }


    try {

        let response;


        if (
            typeof HotelAPI.refundPayment ===
            "function"
        ) {

            response =
                await HotelAPI.refundPayment(
                    paymentId,
                    {
                        amount:
                            refundAmount,

                        reason:
                            refundReason
                    }
                );

        } else {

            /*
             * Fallback for APIs without a dedicated
             * refund endpoint.
             */
            response =
                await HotelAPI.updatePayment(
                    paymentId,
                    {
                        payment_status:
                            "refunded",

                        refund_amount:
                            refundAmount,

                        refund_reason:
                            refundReason
                    }
                );

        }


        if (window.HotelApp) {

            HotelApp.showAlert(
                "Refund processed successfully.",
                "success"
            );

        }


        await loadPayments();


        return response;

    } catch (error) {

        console.error(
            "Refund failed:",
            error
        );


        showPaymentError(
            error
        );


        throw error;

    }

}


/* =========================================================
   PAYMENT ACTIONS
========================================================= */

function handlePaymentActions(
    event
) {

    const button =
        event.target.closest(
            "[data-action]"
        );


    if (!button) {
        return;
    }


    const action =
        button.dataset.action;


    const paymentId =
        button.dataset.paymentId;


    switch (action) {

        case "view-payment":

            viewPayment(
                paymentId
            );

            break;


        case "edit-payment":

            editPayment(
                paymentId
            );

            break;


        case "invoice-payment":

            openInvoiceByPayment(
                paymentId
            );

            break;


        case "refund-payment":

            refundPayment(
                paymentId
            );

            break;


        case "mark-payment-paid":

            updatePaymentStatus(
                paymentId,
                "paid"
            );

            break;


        default:

            break;

    }

}


/* =========================================================
   VIEW PAYMENT
========================================================= */

async function viewPayment(
    paymentId
) {

    try {

        const payment =
            await getPayment(
                paymentId
            );


        paymentState.selectedPayment =
            payment;


        /*
         * If a payment details page exists,
         * navigate to it.
         */
        if (
            window.location.pathname
                .includes(
                    "/payments/"
                )
        ) {

            window.location.href =
                `payment-details.html?id=${encodeURIComponent(
                    paymentId
                )}`;

            return;

        }


        showPaymentDetails(
            payment
        );

    } catch (error) {

        showPaymentError(
            error
        );

    }

}


/* =========================================================
   PAYMENT DETAILS
========================================================= */

function showPaymentDetails(
    payment
) {

    const modal =
        document.querySelector(
            "#paymentDetailsModal"
        );


    if (!modal) {

        return;

    }


    const amount =
        getPaymentAmount(
            payment
        );


    const status =
        getPaymentStatus(
            payment
        );


    const values = {

        "#detailsPaymentReference":
            getPaymentReference(
                payment
            ),

        "#detailsPaymentAmount":
            formatCurrency(
                amount
            ),

        "#detailsPaymentMethod":
            formatPaymentMethod(
                getPaymentMethod(
                    payment
                )
            ),

        "#detailsPaymentStatus":
            formatPaymentStatus(
                status
            ),

        "#detailsTransactionId":
            getTransactionId(
                payment
            ) || "-",

        "#detailsPaymentDate":
            formatDate(
                getPaymentDate(
                    payment
                )
            ),

        "#detailsGuest":
            getPaymentGuestName(
                payment
            ),

        "#detailsBooking":
            getBookingReferenceFromPayment(
                payment
            )

    };


    Object.entries(
        values
    ).forEach(
        ([selector, value]) => {

            setText(
                selector,
                value
            );

        }
    );


    if (window.HotelApp) {

        HotelApp.openModal(
            "paymentDetailsModal"
        );

    }

}


/* =========================================================
   INVOICE
========================================================= */

async function loadInvoiceByPayment(
    paymentId
) {

    const payment =
        await getPayment(
            paymentId
        );


    const bookingId =
        payment?.booking_id ||
        payment?.bookingId ||
        payment?.booking?.id;


    if (bookingId) {

        return loadInvoiceByBooking(
            bookingId
        );

    }


    renderInvoice(
        {
            payment
        }
    );

}


async function loadInvoiceByBooking(
    bookingId
) {

    if (!bookingId) {

        throw new Error(
            "Booking ID is required to generate an invoice."
        );

    }


    try {

        let booking;


        /*
         * Use the booking API already used by
         * bookings.js.
         */
        if (
            typeof HotelAPI.getBooking ===
            "function"
        ) {

            const response =
                await HotelAPI.getBooking(
                    bookingId
                );


            booking =
                response?.data ??
                response;

        }


        /*
         * Retrieve booking payments if supported.
         */
        let payments = [];


        if (
            typeof HotelAPI.getPaymentsByBooking ===
            "function"
        ) {

            const response =
                await HotelAPI.getPaymentsByBooking(
                    bookingId
                );


            payments =
                extractPaymentList(
                    response
                );

        } else {

            payments =
                paymentState.payments.filter(
                    payment =>
                        String(
                            payment.booking_id ||
                            payment.bookingId
                        ) ===
                        String(bookingId)
                );

        }


        const invoice =
            buildInvoice(
                booking,
                payments
            );


        paymentState.selectedBooking =
            booking;


        renderInvoice(
            invoice
        );


        return invoice;

    } catch (error) {

        console.error(
            "Failed to load invoice:",
            error
        );


        showPaymentError(
            error
        );


        throw error;

    }

}


/* =========================================================
   BUILD INVOICE
========================================================= */

function buildInvoice(
    booking,
    payments = []
) {

    if (!booking) {

        throw new Error(
            "Booking information is required."
        );

    }


    const bookingId =
        booking.id ||
        booking.booking_id;


    const checkIn =
        booking.check_in ||
        booking.checkIn;


    const checkOut =
        booking.check_out ||
        booking.checkOut;


    const nights =
        calculateNights(
            checkIn,
            checkOut
        );


    const roomPrice =
        Number(
            booking.room?.price ||
            booking.room_price ||
            booking.price_per_night ||
            booking.rate ||
            0
        );


    const bookingSubtotal =
        Number(
            booking.subtotal ??
            (
                roomPrice *
                nights
            )
        );


    const discount =
        Number(
            booking.discount ||
            0
        );


    const tax =
        Number(
            booking.tax ||
            booking.tax_amount ||
            0
        );


    const total =
        Number(
            booking.total_amount ??
            booking.total ??
            (
                bookingSubtotal -
                discount +
                tax
            )
        );


    const paid =
        payments.reduce(
            (
                sum,
                payment
            ) => {

                const status =
                    getPaymentStatus(
                        payment
                    );


                if (
                    [
                        "paid",
                        "partial"
                    ].includes(
                        status
                    )
                ) {

                    return (
                        sum +
                        getPaymentAmount(
                            payment
                        )
                    );

                }


                return sum;

            },
            0
        );


    const balance =
        Math.max(
            0,
            total - paid
        );


    return {

        invoice_number:
            booking.invoice_number ||
            `INV-${bookingId}`,

        invoice_date:
            booking.invoice_date ||
            getLocalDateTime(),


        due_date:
            booking.due_date ||
            checkOut,


        booking_id:
            bookingId,


        booking_reference:
            booking.booking_reference ||
            booking.booking_number ||
            `BK-${bookingId}`,


        guest:
            booking.guest ||
            {},


        room:
            booking.room ||
            {},


        room_number:
            booking.room_number ||
            booking.room?.room_number ||
            booking.room?.roomNumber ||
            "",


        check_in:
            checkIn,


        check_out:
            checkOut,


        nights,


        room_price:
            roomPrice,


        subtotal:
            bookingSubtotal,


        discount,


        tax,


        total,


        paid,


        balance,


        payments

    };

}


/* =========================================================
   RENDER INVOICE
========================================================= */

function renderInvoice(
    invoice
) {

    const container =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.invoiceContainer
        );


    if (!container) {

        /*
         * The invoice page may use individual
         * fields instead of one container.
         */
        renderInvoiceFields(
            invoice
        );

        return;

    }


    const guestName =
        getInvoiceGuestName(
            invoice
        );


    container.innerHTML = `
        <div class="invoice">

            <div class="invoice-header">

                <div>

                    <h1>
                        Hotel Invoice
                    </h1>

                    <p>
                        Invoice #
                        ${escapeHtml(
                            invoice.invoice_number
                        )}
                    </p>

                </div>


                <div class="invoice-meta">

                    <p>
                        <strong>Invoice Date:</strong>
                        ${escapeHtml(
                            formatDate(
                                invoice.invoice_date
                            )
                        )}
                    </p>

                    <p>
                        <strong>Due Date:</strong>
                        ${escapeHtml(
                            formatDate(
                                invoice.due_date
                            )
                        )}
                    </p>

                </div>

            </div>


            <div class="invoice-parties">

                <div>

                    <h3>
                        Bill To
                    </h3>

                    <p>
                        ${escapeHtml(
                            guestName
                        )}
                    </p>

                    ${
                        invoice.guest?.email
                            ? `
                                <p>
                                    ${escapeHtml(
                                        invoice.guest.email
                                    )}
                                </p>
                            `
                            : ""
                    }

                    ${
                        invoice.guest?.phone
                            ? `
                                <p>
                                    ${escapeHtml(
                                        invoice.guest.phone
                                    )}
                                </p>
                            `
                            : ""
                    }

                </div>


                <div>

                    <h3>
                        Reservation
                    </h3>

                    <p>
                        <strong>Booking:</strong>
                        ${escapeHtml(
                            invoice.booking_reference
                        )}
                    </p>

                    <p>
                        <strong>Room:</strong>
                        ${escapeHtml(
                            invoice.room_number ||
                            "-"
                        )}
                    </p>

                    <p>
                        <strong>Check-in:</strong>
                        ${escapeHtml(
                            formatDate(
                                invoice.check_in
                            )
                        )}
                    </p>

                    <p>
                        <strong>Check-out:</strong>
                        ${escapeHtml(
                            formatDate(
                                invoice.check_out
                            )
                        )}
                    </p>

                </div>

            </div>


            <div class="invoice-items">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Description
                            </th>

                            <th>
                                Nights
                            </th>

                            <th>
                                Rate
                            </th>

                            <th>
                                Amount
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr>

                            <td>
                                Room accommodation
                            </td>

                            <td>
                                ${escapeHtml(
                                    invoice.nights
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    formatCurrency(
                                        invoice.room_price
                                    )
                                )}
                            </td>

                            <td>
                                ${escapeHtml(
                                    formatCurrency(
                                        invoice.subtotal
                                    )
                                )}
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <div class="invoice-summary">

                <p>
                    <span>
                        Subtotal
                    </span>

                    <strong>
                        ${escapeHtml(
                            formatCurrency(
                                invoice.subtotal
                            )
                        )}
                    </strong>
                </p>


                <p>
                    <span>
                        Discount
                    </span>

                    <strong>
                        -${escapeHtml(
                            formatCurrency(
                                invoice.discount
                            )
                        )}
                    </strong>
                </p>


                <p>
                    <span>
                        Tax
                    </span>

                    <strong>
                        ${escapeHtml(
                            formatCurrency(
                                invoice.tax
                            )
                        )}
                    </strong>
                </p>


                <p class="invoice-total">

                    <span>
                        Total
                    </span>

                    <strong>
                        ${escapeHtml(
                            formatCurrency(
                                invoice.total
                            )
                        )}
                    </strong>

                </p>


                <p>
                    <span>
                        Paid
                    </span>

                    <strong>
                        ${escapeHtml(
                            formatCurrency(
                                invoice.paid
                            )
                        )}
                    </strong>

                </p>


                <p class="invoice-balance">

                    <span>
                        Balance Due
                    </span>

                    <strong>
                        ${escapeHtml(
                            formatCurrency(
                                invoice.balance
                            )
                        )}
                    </strong>

                </p>

            </div>


            <div class="invoice-footer">

                <p>
                    Thank you for staying with us.
                </p>

            </div>

        </div>
    `;


    renderInvoiceFields(
        invoice
    );

}


/* =========================================================
   RENDER INDIVIDUAL INVOICE FIELDS
========================================================= */

function renderInvoiceFields(
    invoice
) {

    if (!invoice) {
        return;
    }


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceNumber,
        invoice.invoice_number
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceDate,
        formatDate(
            invoice.invoice_date
        )
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceDueDate,
        formatDate(
            invoice.due_date
        )
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceGuest,
        getInvoiceGuestName(
            invoice
        )
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceBooking,
        invoice.booking_reference
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceRoom,
        invoice.room_number ||
        "-"
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceCheckIn,
        formatDate(
            invoice.check_in
        )
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceCheckOut,
        formatDate(
            invoice.check_out
        )
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceSubtotal,
        formatCurrency(
            invoice.subtotal
        )
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceDiscount,
        formatCurrency(
            invoice.discount
        )
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceTax,
        formatCurrency(
            invoice.tax
        )
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceTotal,
        formatCurrency(
            invoice.total
        )
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoicePaid,
        formatCurrency(
            invoice.paid
        )
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.invoiceBalance,
        formatCurrency(
            invoice.balance
        )
    );


    const items =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.invoiceItems
        );


    if (
        items &&
        invoice.nights
    ) {

        items.innerHTML = `
            <tr>

                <td>
                    Room accommodation
                </td>

                <td>
                    ${escapeHtml(
                        invoice.nights
                    )}
                </td>

                <td>
                    ${escapeHtml(
                        formatCurrency(
                            invoice.room_price
                        )
                    )}
                </td>

                <td>
                    ${escapeHtml(
                        formatCurrency(
                            invoice.subtotal
                        )
                    )}
                </td>

            </tr>
        `;

    }

}


/* =========================================================
   OPEN INVOICE
========================================================= */

async function openInvoiceByPayment(
    paymentId
) {

    try {

        const payment =
            await getPayment(
                paymentId
            );


        const bookingId =
            payment.booking_id ||
            payment.bookingId ||
            payment.booking?.id;


        if (!bookingId) {

            throw new Error(
                "This payment is not associated with a booking."
            );

        }


        window.location.href =
            `invoice.html?booking_id=${encodeURIComponent(
                bookingId
            )}`;

    } catch (error) {

        showPaymentError(
            error
        );

    }

}


/* =========================================================
   PRINT INVOICE
========================================================= */

function printInvoice() {

    window.print();

}


/* =========================================================
   DOWNLOAD / EXPORT INVOICE
========================================================= */

async function downloadInvoice(
    invoice = null
) {

    if (!invoice) {

        invoice =
            buildInvoice(
                paymentState.selectedBooking,
                paymentState.payments.filter(
                    payment =>
                        String(
                            payment.booking_id ||
                            payment.bookingId
                        ) ===
                        String(
                            paymentState.selectedBooking?.id ||
                            paymentState.selectedBooking?.booking_id
                        )
                )
            );

    }


    /*
     * If jsPDF is installed, generate a PDF.
     * Otherwise open the browser print dialog.
     */
    if (
        window.jspdf &&
        window.jspdf.jsPDF
    ) {

        const {
            jsPDF
        } =
            window.jspdf;


        const pdf =
            new jsPDF();


        pdf.setFontSize(
            20
        );


        pdf.text(
            "Hotel Invoice",
            20,
            20
        );


        pdf.setFontSize(
            11
        );


        pdf.text(
            `Invoice: ${invoice.invoice_number}`,
            20,
            32
        );


        pdf.text(
            `Guest: ${getInvoiceGuestName(invoice)}`,
            20,
            42
        );


        pdf.text(
            `Booking: ${invoice.booking_reference}`,
            20,
            52
        );


        pdf.text(
            `Room: ${invoice.room_number || "-"}`,
            20,
            62
        );


        pdf.text(
            `Check-in: ${formatDate(invoice.check_in)}`,
            20,
            72
        );


        pdf.text(
            `Check-out: ${formatDate(invoice.check_out)}`,
            20,
            82
        );


        pdf.text(
            `Subtotal: ${formatCurrency(invoice.subtotal)}`,
            20,
            100
        );


        pdf.text(
            `Discount: ${formatCurrency(invoice.discount)}`,
            20,
            110
        );


        pdf.text(
            `Tax: ${formatCurrency(invoice.tax)}`,
            20,
            120
        );


        pdf.text(
            `Total: ${formatCurrency(invoice.total)}`,
            20,
            132
        );


        pdf.text(
            `Paid: ${formatCurrency(invoice.paid)}`,
            20,
            142
        );


        pdf.text(
            `Balance Due: ${formatCurrency(invoice.balance)}`,
            20,
            152
        );


        pdf.save(
            `${invoice.invoice_number}.pdf`
        );


        return;

    }


    printInvoice();

}


/* =========================================================
   PAYMENT STATISTICS
========================================================= */

function updatePaymentStatistics() {

    const payments =
        paymentState.payments;


    const totalPayments =
        payments.length;


    const totalRevenue =
        payments.reduce(
            (
                total,
                payment
            ) => {

                const status =
                    getPaymentStatus(
                        payment
                    );


                if (
                    [
                        "paid",
                        "partial"
                    ].includes(
                        status
                    )
                ) {

                    return (
                        total +
                        getPaymentAmount(
                            payment
                        )
                    );

                }


                return total;

            },
            0
        );


    const pendingPayments =
        payments.filter(
            payment =>
                getPaymentStatus(
                    payment
                ) === "pending"
        ).length;


    const paidPayments =
        payments.filter(
            payment =>
                getPaymentStatus(
                    payment
                ) === "paid"
        ).length;


    const refundedPayments =
        payments.filter(
            payment =>
                getPaymentStatus(
                    payment
                ) === "refunded"
        ).length;


    setText(
        PAYMENT_CONFIG.SELECTORS.totalPayments,
        totalPayments
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.totalRevenue,
        formatCurrency(
            totalRevenue
        )
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.pendingPayments,
        pendingPayments
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.paidPayments,
        paidPayments
    );


    setText(
        PAYMENT_CONFIG.SELECTORS.refundedPayments,
        refundedPayments
    );

}


/* =========================================================
   FORM UI
========================================================= */

function updatePaymentFormTitle() {

    const title =
        document.querySelector(
            "#paymentModalTitle"
        );


    if (!title) {
        return;
    }


    title.textContent =
        paymentState.editing
            ? "Edit Payment"
            : "Record Payment";

}


function setPaymentFormLoading(
    loading
) {

    const button =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.saveButton
        );


    if (!button) {
        return;
    }


    if (loading) {

        button.disabled =
            true;


        button.dataset.originalText =
            button.textContent;


        button.textContent =
            paymentState.editing
                ? "Updating..."
                : "Saving...";

    } else {

        button.disabled =
            false;


        if (
            button.dataset.originalText
        ) {

            button.textContent =
                button.dataset.originalText;


            delete button.dataset.originalText;

        }

    }

}


function closePaymentForm() {

    const form =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.paymentForm
        );


    if (form) {

        form.reset();

    }


    paymentState.selectedPayment =
        null;


    paymentState.editing =
        false;


    if (window.HotelApp) {

        HotelApp.closeModal(
            "paymentModal"
        );

    }

}


/* =========================================================
   PAYMENT HELPERS
========================================================= */

function getPaymentId(
    payment
) {

    return (
        payment?.id ??
        payment?.payment_id ??
        ""
    );

}


function getPaymentReference(
    payment
) {

    return (
        payment?.payment_reference ||
        payment?.paymentReference ||
        payment?.reference ||
        payment?.receipt_number ||
        `PAY-${getPaymentId(payment)}`
    );

}


function getPaymentAmount(
    payment
) {

    return Number(
        payment?.amount ??
        payment?.payment_amount ??
        payment?.total_amount ??
        0
    );

}


function getPaymentMethod(
    payment
) {

    return String(
        payment?.payment_method ||
        payment?.paymentMethod ||
        payment?.method ||
        ""
    ).toLowerCase();

}


function getPaymentStatus(
    payment
) {

    return String(
        payment?.payment_status ||
        payment?.paymentStatus ||
        payment?.status ||
        "pending"
    ).toLowerCase();

}


function getPaymentDate(
    payment
) {

    return (
        payment?.payment_date ||
        payment?.paymentDate ||
        payment?.created_at ||
        payment?.createdAt ||
        ""
    );

}


function getTransactionId(
    payment
) {

    return String(
        payment?.transaction_id ||
        payment?.transactionId ||
        payment?.transaction_reference ||
        ""
    );

}


function getPaymentGuestName(
    payment
) {

    if (
        payment?.guest?.name
    ) {

        return payment.guest.name;

    }


    if (
        payment?.guest_name
    ) {

        return payment.guest_name;

    }


    const firstName =
        payment?.guest?.first_name ||
        payment?.guest?.firstName ||
        payment?.first_name ||
        "";


    const lastName =
        payment?.guest?.last_name ||
        payment?.guest?.lastName ||
        payment?.last_name ||
        "";


    const name =
        `${firstName} ${lastName}`
            .trim();


    return name ||
        "Unknown Guest";

}


function getBookingReferenceFromPayment(
    payment
) {

    return (
        payment?.booking?.booking_reference ||
        payment?.booking?.booking_number ||
        payment?.booking_reference ||
        payment?.booking_number ||
        payment?.booking_id ||
        "-"
    ).toString();

}


function getInvoiceGuestName(
    invoice
) {

    const guest =
        invoice?.guest ||
        {};


    if (guest.name) {

        return guest.name;

    }


    const name =
        `${guest.first_name || guest.firstName || ""} ${
            guest.last_name || guest.lastName || ""
        }`.trim();


    return (
        name ||
        invoice?.guest_name ||
        "Guest"
    );

}


function formatPaymentMethod(
    method
) {

    if (!method) {
        return "-";
    }


    return String(method)
        .replace(
            /[_-]/g,
            " "
        )
        .replace(
            /\b\w/g,
            character =>
                character.toUpperCase()
        );

}


function formatPaymentStatus(
    status
) {

    if (!status) {
        return "Unknown";
    }


    return String(status)
        .replace(
            /[_-]/g,
            " "
        )
        .replace(
            /\b\w/g,
            character =>
                character.toUpperCase()
        );

}


/* =========================================================
   GENERAL HELPERS
========================================================= */

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
            year: "numeric",
            month: "short",
            day: "numeric"
        }
    ).format(date);

}


function formatCurrency(
    value
) {

    const amount =
        Number(value) || 0;


    /*
     * Change USD to your hotel's currency
     * if necessary.
     */
    return new Intl.NumberFormat(
        undefined,
        {
            style: "currency",
            currency: "USD"
        }
    ).format(amount);

}


function getLocalDateTime() {

    const now =
        new Date();


    const year =
        now.getFullYear();


    const month =
        String(
            now.getMonth() + 1
        ).padStart(
            2,
            "0"
        );


    const day =
        String(
            now.getDate()
        ).padStart(
            2,
            "0"
        );


    const hours =
        String(
            now.getHours()
        ).padStart(
            2,
            "0"
        );


    const minutes =
        String(
            now.getMinutes()
        ).padStart(
            2,
            "0"
        );


    return `${year}-${month}-${day}T${hours}:${minutes}`;

}


function calculateNights(
    checkIn,
    checkOut
) {

    if (
        !checkIn ||
        !checkOut
    ) {

        return 0;

    }


    const start =
        new Date(
            checkIn
        );


    const end =
        new Date(
            checkOut
        );


    const difference =
        end.getTime() -
        start.getTime();


    if (
        difference <= 0
    ) {

        return 0;

    }


    return Math.ceil(
        difference /
        (
            1000 *
            60 *
            60 *
            24
        )
    );

}


function setField(
    selector,
    value
) {

    const element =
        document.querySelector(
            selector
        );


    if (!element) {
        return;
    }


    element.value =
        value ?? "";

}


function setText(
    selector,
    value
) {

    const element =
        document.querySelector(
            selector
        );


    if (!element) {
        return;
    }


    element.textContent =
        value ?? "";

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


/* =========================================================
   LOADING / ERROR UI
========================================================= */

function showPaymentsLoading() {

    const container =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.paymentContainer
        );


    const tbody =
        document.querySelector(
            PAYMENT_CONFIG.SELECTORS.paymentTableBody
        );


    if (container) {

        container.innerHTML = `
            <div class="loading-state">

                <div class="spinner"></div>

                <p>
                    Loading payments...
                </p>

            </div>
        `;

    }


    if (tbody) {

        tbody.innerHTML = `
            <tr>

                <td
                    colspan="9"
                    class="loading-state"
                >
                    Loading payments...
                </td>

            </tr>
        `;

    }

}


function hidePaymentsLoading() {

    document.body.classList.remove(
        "payments-loading"
    );

}


function showPaymentError(
    error
) {

    const message =
        error?.message ||
        "Unable to complete payment operation.";


    console.error(
        "Payment operation error:",
        error
    );


    if (window.HotelApp) {

        HotelApp.showAlert(
            message,
            "error"
        );

    } else {

        alert(message);

    }

}


/* =========================================================
   PUBLIC PAYMENT API
========================================================= */

window.HotelPayments = {

    load:
        loadPayments,


    refresh:
        () =>
            loadPayments(),


    get:
        getPayment,


    create:
        createPayment,


    update:
        updatePayment,


    updateStatus:
        updatePaymentStatus,


    refund:
        refundPayment,


    openForm:
        openPaymentForm,


    closeForm:
        closePaymentForm,


    filter:
        filterPayments,


    invoice:
        loadInvoiceByBooking,


    invoiceByPayment:
        loadInvoiceByPayment,


    buildInvoice:
        buildInvoice,


    renderInvoice:
        renderInvoice,


    printInvoice:
        printInvoice,


    downloadInvoice:
        downloadInvoice,


    getState:
        () => ({

            ...paymentState,

            payments:
                [
                    ...paymentState.payments
                ],

            filteredPayments:
                [
                    ...paymentState.filteredPayments
                ]

        })

};
