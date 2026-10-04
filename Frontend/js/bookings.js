/**
 * Hotel Management System
 * frontend/js/bookings.js
 *
 * Reservation / Booking Operations
 *
 * Responsibilities:
 * - Load bookings
 * - Search and filter bookings
 * - Check room availability
 * - Create reservations
 * - Update reservations
 * - View booking details
 * - Cancel reservations
 * - Confirm reservations
 * - Check-in guests
 * - Check-out guests
 * - Calculate booking totals
 * - Handle booking forms
 */

"use strict";


/* =========================================================
   BOOKING CONFIGURATION
========================================================= */

const BOOKING_CONFIG = {

    SELECTORS: {

        bookingContainer:
            "#bookingsContainer",

        bookingTableBody:
            "#bookingsTableBody",

        searchInput:
            "#bookingSearch",

        statusFilter:
            "#bookingStatusFilter",

        paymentStatusFilter:
            "#bookingPaymentStatusFilter",

        roomFilter:
            "#bookingRoomFilter",

        bookingForm:
            "#bookingForm",

        bookingId:
            "#bookingId",

        guestId:
            "#bookingGuestId",

        roomId:
            "#bookingRoomId",

        checkIn:
            "#checkInDate",

        checkOut:
            "#checkOutDate",

        adults:
            "#bookingAdults",

        children:
            "#bookingChildren",

        bookingStatus:
            "#bookingStatus",

        specialRequests:
            "#specialRequests",

        discount:
            "#bookingDiscount",

        tax:
            "#bookingTax",

        totalAmount:
            "#bookingTotal",

        paymentMethod:
            "#bookingPaymentMethod",

        paymentStatus:
            "#bookingPaymentStatus",

        saveButton:
            "#saveBookingBtn",

        totalBookings:
            "#totalBookings",

        confirmedBookings:
            "#confirmedBookings",

        pendingBookings:
            "#pendingBookings",

        checkedInBookings:
            "#checkedInBookings",

        availableRooms:
            "#availableRooms"

    },

    STATUSES: [
        "pending",
        "confirmed",
        "checked_in",
        "checked_out",
        "cancelled",
        "no_show"
    ],

    PAYMENT_STATUSES: [
        "pending",
        "partial",
        "paid",
        "refunded",
        "failed"
    ]

};


/* =========================================================
   BOOKING STATE
========================================================= */

const bookingState = {

    bookings: [],

    filteredBookings: [],

    selectedBooking: null,

    availableRooms: [],

    loading: false,

    checkingAvailability: false,

    editing: false

};


/* =========================================================
   INITIALIZATION
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        initializeBookings();

    }
);


async function initializeBookings() {

    /*
     * Require authentication when HotelAuth
     * is available.
     */
    if (
        window.HotelAuth &&
        !HotelAuth.requireAuth()
    ) {
        return;
    }


    initializeBookingEvents();

    await loadBookings();

}


/* =========================================================
   EVENT INITIALIZATION
========================================================= */

function initializeBookingEvents() {

    const searchInput =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.searchInput
        );


    const statusFilter =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.statusFilter
        );


    const paymentFilter =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.paymentStatusFilter
        );


    const roomFilter =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.roomFilter
        );


    const bookingForm =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.bookingForm
        );


    if (searchInput) {

        searchInput.addEventListener(
            "input",
            debounceBookingSearch()
        );

    }


    if (statusFilter) {

        statusFilter.addEventListener(
            "change",
            filterBookings
        );

    }


    if (paymentFilter) {

        paymentFilter.addEventListener(
            "change",
            filterBookings
        );

    }


    if (roomFilter) {

        roomFilter.addEventListener(
            "change",
            filterBookings
        );

    }


    if (bookingForm) {

        bookingForm.addEventListener(
            "submit",
            handleBookingFormSubmit
        );


        bookingForm.addEventListener(
            "input",
            handleBookingFormInput
        );


        bookingForm.addEventListener(
            "change",
            handleBookingFormInput
        );

    }


    /*
     * Event delegation for booking actions.
     */
    document.addEventListener(
        "click",
        handleBookingActions
    );

}


/* =========================================================
   LOAD BOOKINGS
========================================================= */

async function loadBookings(
    filters = {}
) {

    if (bookingState.loading) {
        return;
    }


    bookingState.loading =
        true;


    showBookingsLoading();


    try {

        const response =
            await HotelAPI.getBookings(
                filters
            );


        bookingState.bookings =
            extractBookingList(
                response
            );


        bookingState.filteredBookings =
            [
                ...bookingState.bookings
            ];


        updateBookingStatistics();

        populateRoomFilter();

        renderBookings();


    } catch (error) {

        console.error(
            "Failed to load bookings:",
            error
        );


        showBookingError(
            error
        );


    } finally {

        bookingState.loading =
            false;


        hideBookingsLoading();

    }

}


/* =========================================================
   EXTRACT BOOKING LIST
========================================================= */

function extractBookingList(
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
   RENDER BOOKINGS
========================================================= */

function renderBookings() {

    renderBookingCards();

    renderBookingTable();

}


/* =========================================================
   BOOKING CARDS
========================================================= */

function renderBookingCards() {

    const container =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.bookingContainer
        );


    if (!container) {
        return;
    }


    const bookings =
        bookingState.filteredBookings;


    if (!bookings.length) {

        container.innerHTML = `
            <div class="empty-state">

                <h3>
                    No bookings found
                </h3>

                <p>
                    No reservations match
                    your current filters.
                </p>

            </div>
        `;

        return;

    }


    container.innerHTML =
        bookings
            .map(
                booking =>
                    createBookingCard(
                        booking
                    )
            )
            .join("");

}


/* =========================================================
   CREATE BOOKING CARD
========================================================= */

function createBookingCard(
    booking
) {

    const id =
        getBookingId(
            booking
        );


    const guest =
        getBookingGuestName(
            booking
        );


    const room =
        getBookingRoomName(
            booking
        );


    const status =
        booking.status ||
        "pending";


    const total =
        getBookingTotal(
            booking
        );


    return `
        <article
            class="booking-card"
            data-booking-id="${escapeHtml(id)}"
        >

            <div class="booking-card-header">

                <div>

                    <h3>
                        ${escapeHtml(
                            getBookingReference(
                                booking
                            )
                        )}
                    </h3>

                    <p>
                        ${escapeHtml(guest)}
                    </p>

                </div>


                <span
                    class="status status-${escapeHtml(
                        String(status)
                            .toLowerCase()
                    )}"
                >
                    ${escapeHtml(
                        formatBookingStatus(
                            status
                        )
                    )}
                </span>

            </div>


            <div class="booking-card-body">

                <p>
                    <strong>Room:</strong>
                    ${escapeHtml(room)}
                </p>

                <p>
                    <strong>Check-in:</strong>
                    ${escapeHtml(
                        formatDate(
                            booking.check_in ||
                            booking.checkIn
                        )
                    )}
                </p>

                <p>
                    <strong>Check-out:</strong>
                    ${escapeHtml(
                        formatDate(
                            booking.check_out ||
                            booking.checkOut
                        )
                    )}
                </p>

                <p>
                    <strong>Total:</strong>
                    ${escapeHtml(
                        formatCurrency(
                            total
                        )
                    )}
                </p>

            </div>


            <div class="booking-card-actions">

                <button
                    type="button"
                    class="btn btn-primary"
                    data-action="view-booking"
                    data-booking-id="${escapeHtml(id)}"
                >
                    View
                </button>


                <button
                    type="button"
                    class="btn btn-secondary"
                    data-action="edit-booking"
                    data-booking-id="${escapeHtml(id)}"
                >
                    Edit
                </button>


                ${createBookingActionButtons(
                    booking
                )}

            </div>

        </article>
    `;

}


/* =========================================================
   BOOKING ACTION BUTTONS
========================================================= */

function createBookingActionButtons(
    booking
) {

    const id =
        getBookingId(
            booking
        );


    const status =
        String(
            booking.status ||
            "pending"
        ).toLowerCase();


    let html = "";


    if (
        status === "pending"
    ) {

        html += `
            <button
                type="button"
                class="btn btn-success"
                data-action="confirm-booking"
                data-booking-id="${escapeHtml(id)}"
            >
                Confirm
            </button>
        `;

    }


    if (
        status === "confirmed"
    ) {

        html += `
            <button
                type="button"
                class="btn btn-success"
                data-action="check-in"
                data-booking-id="${escapeHtml(id)}"
            >
                Check In
            </button>
        `;

    }


    if (
        status === "checked_in"
    ) {

        html += `
            <button
                type="button"
                class="btn btn-primary"
                data-action="check-out"
                data-booking-id="${escapeHtml(id)}"
            >
                Check Out
            </button>
        `;

    }


    if (
        [
            "pending",
            "confirmed"
        ].includes(status)
    ) {

        html += `
            <button
                type="button"
                class="btn btn-danger"
                data-action="cancel-booking"
                data-booking-id="${escapeHtml(id)}"
            >
                Cancel
            </button>
        `;

    }


    return html;

}


/* =========================================================
   BOOKING TABLE
========================================================= */

function renderBookingTable() {

    const tbody =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.bookingTableBody
        );


    if (!tbody) {
        return;
    }


    const bookings =
        bookingState.filteredBookings;


    if (!bookings.length) {

        tbody.innerHTML = `
            <tr>

                <td
                    colspan="9"
                    class="empty-state"
                >
                    No bookings found.
                </td>

            </tr>
        `;

        return;

    }


    tbody.innerHTML =
        bookings
            .map(
                booking =>
                    createBookingTableRow(
                        booking
                    )
            )
            .join("");

}


/* =========================================================
   BOOKING TABLE ROW
========================================================= */

function createBookingTableRow(
    booking
) {

    const id =
        getBookingId(
            booking
        );


    const reference =
        getBookingReference(
            booking
        );


    const guest =
        getBookingGuestName(
            booking
        );


    const room =
        getBookingRoomName(
            booking
        );


    const status =
        booking.status ||
        "pending";


    const paymentStatus =
        booking.payment_status ||
        booking.paymentStatus ||
        "pending";


    return `
        <tr
            data-booking-id="${escapeHtml(id)}"
        >

            <td>
                ${escapeHtml(reference)}
            </td>


            <td>
                ${escapeHtml(guest)}
            </td>


            <td>
                ${escapeHtml(room)}
            </td>


            <td>
                ${escapeHtml(
                    formatDate(
                        booking.check_in ||
                        booking.checkIn
                    )
                )}
            </td>


            <td>
                ${escapeHtml(
                    formatDate(
                        booking.check_out ||
                        booking.checkOut
                    )
                )}
            </td>


            <td>
                ${escapeHtml(
                    formatCurrency(
                        getBookingTotal(
                            booking
                        )
                    )
                )}
            </td>


            <td>

                <span
                    class="status status-${escapeHtml(
                        String(status)
                            .toLowerCase()
                    )}"
                >
                    ${escapeHtml(
                        formatBookingStatus(
                            status
                        )
                    )}
                </span>

            </td>


            <td>

                <span
                    class="status status-${escapeHtml(
                        String(paymentStatus)
                            .toLowerCase()
                    )}"
                >
                    ${escapeHtml(
                        formatBookingStatus(
                            paymentStatus
                        )
                    )}
                </span>

            </td>


            <td>

                <button
                    type="button"
                    class="btn btn-sm"
                    data-action="view-booking"
                    data-booking-id="${escapeHtml(id)}"
                >
                    View
                </button>


                <button
                    type="button"
                    class="btn btn-sm"
                    data-action="edit-booking"
                    data-booking-id="${escapeHtml(id)}"
                >
                    Edit
                </button>


                ${createBookingActionButtons(
                    booking
                )}

            </td>

        </tr>
    `;

}


/* =========================================================
   SEARCH
========================================================= */

function debounceBookingSearch() {

    let timeout;


    return () => {

        clearTimeout(
            timeout
        );


        timeout =
            setTimeout(
                filterBookings,
                300
            );

    };

}


/* =========================================================
   FILTER BOOKINGS
========================================================= */

function filterBookings() {

    const searchInput =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.searchInput
        );


    const statusFilter =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.statusFilter
        );


    const paymentFilter =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.paymentStatusFilter
        );


    const roomFilter =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.roomFilter
        );


    const search =
        searchInput?.value
            ?.trim()
            .toLowerCase() || "";


    const status =
        statusFilter?.value
            ?.trim()
            .toLowerCase() || "";


    const paymentStatus =
        paymentFilter?.value
            ?.trim()
            .toLowerCase() || "";


    const room =
        roomFilter?.value
            ?.trim()
            .toLowerCase() || "";


    bookingState.filteredBookings =
        bookingState.bookings.filter(
            booking => {

                const reference =
                    getBookingReference(
                        booking
                    ).toLowerCase();


                const guest =
                    getBookingGuestName(
                        booking
                    ).toLowerCase();


                const email =
                    String(
                        booking.guest?.email ||
                        booking.guest_email ||
                        booking.email ||
                        ""
                    ).toLowerCase();


                const roomName =
                    getBookingRoomName(
                        booking
                    ).toLowerCase();


                const bookingStatus =
                    String(
                        booking.status ||
                        ""
                    ).toLowerCase();


                const bookingPaymentStatus =
                    String(
                        booking.payment_status ||
                        booking.paymentStatus ||
                        ""
                    ).toLowerCase();


                const matchesSearch =
                    !search ||
                    reference.includes(search) ||
                    guest.includes(search) ||
                    email.includes(search) ||
                    roomName.includes(search);


                const matchesStatus =
                    !status ||
                    bookingStatus === status;


                const matchesPayment =
                    !paymentStatus ||
                    bookingPaymentStatus ===
                        paymentStatus;


                const matchesRoom =
                    !room ||
                    roomName === room;


                return (
                    matchesSearch &&
                    matchesStatus &&
                    matchesPayment &&
                    matchesRoom
                );

            }
        );


    renderBookings();

}


/* =========================================================
   ROOM FILTER
========================================================= */

function populateRoomFilter() {

    const select =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.roomFilter
        );


    if (!select) {
        return;
    }


    const currentValue =
        select.value;


    const rooms = [];


    bookingState.bookings
        .forEach(
            booking => {

                const room =
                    getBookingRoomName(
                        booking
                    );


                if (
                    room &&
                    !rooms.includes(room)
                ) {

                    rooms.push(
                        room
                    );

                }

            }
        );


    rooms.sort(
        (a, b) =>
            a.localeCompare(b)
    );


    select.innerHTML = `
        <option value="">
            All Rooms
        </option>
    `;


    rooms.forEach(
        room => {

            const option =
                document.createElement(
                    "option"
                );


            option.value =
                room;


            option.textContent =
                room;


            select.appendChild(
                option
            );

        }
    );


    select.value =
        currentValue;

}


/* =========================================================
   GET BOOKING
========================================================= */

async function getBooking(
    bookingId
) {

    if (!bookingId) {

        throw new Error(
            "Booking ID is required."
        );

    }


    try {

        const response =
            await HotelAPI.getBooking(
                bookingId
            );


        const booking =
            response?.data ??
            response;


        bookingState.selectedBooking =
            booking;


        return booking;

    } catch (error) {

        console.error(
            "Failed to get booking:",
            error
        );


        throw error;

    }

}


/* =========================================================
   VIEW BOOKING
========================================================= */

async function viewBooking(
    bookingId
) {

    try {

        await getBooking(
            bookingId
        );


        window.location.href =
            `booking-details.html?id=${encodeURIComponent(
                bookingId
            )}`;

    } catch (error) {

        showBookingError(
            error
        );

    }

}


/* =========================================================
   OPEN BOOKING FORM
========================================================= */

function openBookingForm(
    booking = null
) {

    const form =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.bookingForm
        );


    if (!form) {
        return;
    }


    bookingState.editing =
        Boolean(booking);


    bookingState.selectedBooking =
        booking;


    form.reset();


    setField(
        BOOKING_CONFIG.SELECTORS.bookingId,
        getBookingId(
            booking
        )
    );


    setField(
        BOOKING_CONFIG.SELECTORS.guestId,
        booking?.guest_id ||
        booking?.guestId ||
        booking?.guest?.id ||
        ""
    );


    setField(
        BOOKING_CONFIG.SELECTORS.roomId,
        booking?.room_id ||
        booking?.roomId ||
        booking?.room?.id ||
        ""
    );


    setField(
        BOOKING_CONFIG.SELECTORS.checkIn,
        booking?.check_in ||
        booking?.checkIn ||
        ""
    );


    setField(
        BOOKING_CONFIG.SELECTORS.checkOut,
        booking?.check_out ||
        booking?.checkOut ||
        ""
    );


    setField(
        BOOKING_CONFIG.SELECTORS.adults,
        booking?.adults ||
        1
    );


    setField(
        BOOKING_CONFIG.SELECTORS.children,
        booking?.children ||
        0
    );


    setField(
        BOOKING_CONFIG.SELECTORS.bookingStatus,
        booking?.status ||
        "pending"
    );


    setField(
        BOOKING_CONFIG.SELECTORS.specialRequests,
        booking?.special_requests ||
        booking?.specialRequests ||
        ""
    );


    setField(
        BOOKING_CONFIG.SELECTORS.discount,
        booking?.discount ||
        0
    );


    setField(
        BOOKING_CONFIG.SELECTORS.tax,
        booking?.tax ||
        0
    );


    setField(
        BOOKING_CONFIG.SELECTORS.paymentMethod,
        booking?.payment_method ||
        booking?.paymentMethod ||
        ""
    );


    setField(
        BOOKING_CONFIG.SELECTORS.paymentStatus,
        booking?.payment_status ||
        booking?.paymentStatus ||
        "pending"
    );


    updateBookingFormTitle();


    calculateBookingTotal();


    if (window.HotelApp) {

        HotelApp.openModal(
            "bookingModal"
        );

    }

}


/* =========================================================
   EDIT BOOKING
========================================================= */

async function editBooking(
    bookingId
) {

    try {

        const booking =
            await getBooking(
                bookingId
            );


        openBookingForm(
            booking
        );

    } catch (error) {

        showBookingError(
            error
        );

    }

}


/* =========================================================
   BOOKING FORM DATA
========================================================= */

function getBookingFormData(
    form
) {

    const formData =
        new FormData(
            form
        );


    return {

        id:
            formData.get("id") ||
            formData.get("booking_id") ||
            "",


        guest_id:
            String(
                formData.get(
                    "guest_id"
                ) || ""
            ).trim(),


        room_id:
            String(
                formData.get(
                    "room_id"
                ) || ""
            ).trim(),


        check_in:
            String(
                formData.get(
                    "check_in"
                ) || ""
            ).trim(),


        check_out:
            String(
                formData.get(
                    "check_out"
                ) || ""
            ).trim(),


        adults:
            Number(
                formData.get(
                    "adults"
                ) || 1
            ),


        children:
            Number(
                formData.get(
                    "children"
                ) || 0
            ),


        status:
            String(
                formData.get(
                    "status"
                ) || "pending"
            ).trim(),


        special_requests:
            String(
                formData.get(
                    "special_requests"
                ) || ""
            ).trim(),


        discount:
            Number(
                formData.get(
                    "discount"
                ) || 0
            ),


        tax:
            Number(
                formData.get(
                    "tax"
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
            ).trim()

    };

}


/* =========================================================
   SUBMIT BOOKING FORM
========================================================= */

async function handleBookingFormSubmit(
    event
) {

    event.preventDefault();


    const form =
        event.currentTarget;


    const data =
        getBookingFormData(
            form
        );


    const validation =
        validateBookingData(
            data
        );


    if (!validation.valid) {

        showBookingError(
            new Error(
                validation.message
            )
        );


        return;

    }


    try {

        setBookingFormLoading(
            true
        );


        /*
         * Verify room availability before
         * creating a new reservation.
         */
        if (!data.id) {

            const available =
                await checkRoomAvailability(
                    data.room_id,
                    data.check_in,
                    data.check_out
                );


            if (!available) {

                throw new Error(
                    "The selected room is not available for the requested dates."
                );

            }

        }


        let response;


        if (data.id) {

            response =
                await HotelAPI.updateBooking(
                    data.id,
                    data
                );

        } else {

            response =
                await HotelAPI.createBooking(
                    data
                );

        }


        if (window.HotelApp) {

            HotelApp.showAlert(
                data.id
                    ? "Reservation updated successfully."
                    : "Reservation created successfully.",
                "success"
            );

        }


        closeBookingForm();


        await loadBookings();


        /*
         * Optional redirect after successful
         * new reservation.
         */
        if (
            !data.id &&
            response
        ) {

            const createdBooking =
                response?.data ??
                response;


            const createdId =
                getBookingId(
                    createdBooking
                );


            if (
                createdId &&
                window.location.pathname
                    .includes("booking.html")
            ) {

                /*
                 * Keep the user on the page.
                 * booking-success.html can be used
                 * by the application if desired.
                 */
            }

        }


        return response;

    } catch (error) {

        console.error(
            "Failed to save booking:",
            error
        );


        showBookingError(
            error
        );


        throw error;

    } finally {

        setBookingFormLoading(
            false
        );

    }

}


/* =========================================================
   VALIDATE BOOKING
========================================================= */

function validateBookingData(
    data
) {

    if (!data.guest_id) {

        return {
            valid: false,
            message:
                "Please select a guest."
        };

    }


    if (!data.room_id) {

        return {
            valid: false,
            message:
                "Please select a room."
        };

    }


    if (!data.check_in) {

        return {
            valid: false,
            message:
                "Check-in date is required."
        };

    }


    if (!data.check_out) {

        return {
            valid: false,
            message:
                "Check-out date is required."
        };

    }


    const checkIn =
        new Date(
            data.check_in
        );


    const checkOut =
        new Date(
            data.check_out
        );


    if (
        Number.isNaN(
            checkIn.getTime()
        )
    ) {

        return {
            valid: false,
            message:
                "Invalid check-in date."
        };

    }


    if (
        Number.isNaN(
            checkOut.getTime()
        )
    ) {

        return {
            valid: false,
            message:
                "Invalid check-out date."
        };

    }


    if (
        checkOut <= checkIn
    ) {

        return {
            valid: false,
            message:
                "Check-out must be after check-in."
        };

    }


    if (
        data.adults < 1
    ) {

        return {
            valid: false,
            message:
                "At least one adult is required."
        };

    }


    if (
        data.children < 0
    ) {

        return {
            valid: false,
            message:
                "Number of children cannot be negative."
        };

    }


    if (
        data.discount < 0 ||
        data.tax < 0
    ) {

        return {
            valid: false,
            message:
                "Discount and tax cannot be negative."
        };

    }


    if (
        !BOOKING_CONFIG.STATUSES.includes(
            data.status
        )
    ) {

        return {
            valid: false,
            message:
                "Invalid booking status."
        };

    }


    if (
        !BOOKING_CONFIG.PAYMENT_STATUSES.includes(
            data.payment_status
        )
    ) {

        return {
            valid: false,
            message:
                "Invalid payment status."
        };

    }


    return {
        valid: true,
        message: ""
    };

}


/* =========================================================
   ROOM AVAILABILITY
========================================================= */

async function checkRoomAvailability(
    roomId,
    checkIn,
    checkOut
) {

    if (
        !roomId ||
        !checkIn ||
        !checkOut
    ) {

        return false;

    }


    if (
        bookingState.checkingAvailability
    ) {

        return false;

    }


    bookingState.checkingAvailability =
        true;


    try {

        /*
         * This method should be implemented
         * by api.js.
         */
        const response =
            await HotelAPI.checkRoomAvailability(
                roomId,
                checkIn,
                checkOut
            );


        if (
            typeof response ===
            "boolean"
        ) {

            return response;

        }


        if (
            typeof response?.available ===
            "boolean"
        ) {

            return response.available;

        }


        if (
            typeof response?.data?.available ===
            "boolean"
        ) {

            return response.data.available;

        }


        /*
         * Some APIs return:
         *
         * { data: { available_rooms: [...] } }
         */
        const availableRooms =
            response?.data?.available_rooms ||
            response?.available_rooms ||
            response?.data?.rooms ||
            response?.rooms;


        if (
            Array.isArray(
                availableRooms
            )
        ) {

            return availableRooms.some(
                room =>
                    String(
                        room.id ??
                        room.room_id
                    ) === String(roomId)
            );

        }


        return false;

    } catch (error) {

        console.error(
            "Availability check failed:",
            error
        );


        throw new Error(
            "Unable to verify room availability."
        );

    } finally {

        bookingState.checkingAvailability =
            false;

    }

}


/* =========================================================
   LOAD AVAILABLE ROOMS
========================================================= */

async function loadAvailableRooms(
    checkIn,
    checkOut
) {

    if (
        !checkIn ||
        !checkOut
    ) {

        bookingState.availableRooms =
            [];


        return [];

    }


    try {

        const response =
            await HotelAPI.getAvailableRooms(
                checkIn,
                checkOut
            );


        bookingState.availableRooms =
            extractRoomList(
                response
            );


        renderAvailableRooms();


        return bookingState.availableRooms;

    } catch (error) {

        console.error(
            "Failed to load available rooms:",
            error
        );


        showBookingError(
            error
        );


        return [];

    }

}


/* =========================================================
   EXTRACT ROOMS
========================================================= */

function extractRoomList(
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
            response.data?.rooms
        )
    ) {

        return response.data.rooms;

    }


    if (
        Array.isArray(
            response.rooms
        )
    ) {

        return response.rooms;

    }


    return [];

}


/* =========================================================
   RENDER AVAILABLE ROOMS
========================================================= */

function renderAvailableRooms() {

    const select =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.roomId
        );


    if (!select) {
        return;
    }


    /*
     * If the room selector is already populated
     * by rooms.js, don't destroy its options.
     */
    if (
        !bookingState.availableRooms.length
    ) {

        return;

    }


    const currentValue =
        select.value;


    select.innerHTML = `
        <option value="">
            Select Available Room
        </option>
    `;


    bookingState.availableRooms
        .forEach(
            room => {

                const option =
                    document.createElement(
                        "option"
                    );


                const id =
                    room.id ??
                    room.room_id;


                const roomNumber =
                    room.room_number ||
                    room.roomNumber ||
                    room.number ||
                    id;


                const type =
                    room.type ||
                    room.room_type ||
                    "";


                const price =
                    room.price ||
                    room.price_per_night ||
                    "";


                option.value =
                    id;


                option.textContent =
                    `${roomNumber}` +
                    (
                        type
                            ? ` - ${type}`
                            : ""
                    ) +
                    (
                        price
                            ? ` - ${formatCurrency(price)}/night`
                            : ""
                    );


                select.appendChild(
                    option
                );

            }
        );


    if (currentValue) {

        select.value =
            currentValue;

    }

}


/* =========================================================
   FORM INPUT HANDLER
========================================================= */

function handleBookingFormInput() {

    calculateBookingTotal();


    const checkIn =
        getFieldValue(
            BOOKING_CONFIG.SELECTORS.checkIn
        );


    const checkOut =
        getFieldValue(
            BOOKING_CONFIG.SELECTORS.checkOut
        );


    if (
        checkIn &&
        checkOut &&
        checkOut > checkIn
    ) {

        loadAvailableRooms(
            checkIn,
            checkOut
        );

    }

}


/* =========================================================
   CALCULATE BOOKING TOTAL
========================================================= */

function calculateBookingTotal() {

    const roomId =
        getFieldValue(
            BOOKING_CONFIG.SELECTORS.roomId
        );


    const discount =
        Number(
            getFieldValue(
                BOOKING_CONFIG.SELECTORS.discount
            ) || 0
        );


    const tax =
        Number(
            getFieldValue(
                BOOKING_CONFIG.SELECTORS.tax
            ) || 0
        );


    /*
     * Find selected room from available rooms
     * or use the room price supplied in DOM.
     */
    let roomPrice =
        0;


    const selectedRoom =
        bookingState.availableRooms.find(
            room =>
                String(
                    room.id ??
                    room.room_id
                ) === String(roomId)
        );


    if (selectedRoom) {

        roomPrice =
            Number(
                selectedRoom.price ||
                selectedRoom.price_per_night ||
                selectedRoom.rate ||
                0
            );

    }


    /*
     * Fall back to data-room-price.
     */
    if (!roomPrice) {

        const roomElement =
            document.querySelector(
                BOOKING_CONFIG.SELECTORS.roomId
            );


        const selectedOption =
            roomElement?.selectedOptions?.[0];


        roomPrice =
            Number(
                selectedOption?.dataset.price ||
                0
            );

    }


    const checkIn =
        getFieldValue(
            BOOKING_CONFIG.SELECTORS.checkIn
        );


    const checkOut =
        getFieldValue(
            BOOKING_CONFIG.SELECTORS.checkOut
        );


    const nights =
        calculateNights(
            checkIn,
            checkOut
        );


    const subtotal =
        roomPrice *
        nights;


    const discounted =
        Math.max(
            0,
            subtotal - discount
        );


    const total =
        Math.max(
            0,
            discounted + tax
        );


    setField(
        BOOKING_CONFIG.SELECTORS.totalAmount,
        total.toFixed(2)
    );


    /*
     * Optional breakdown elements.
     */
    setText(
        "#bookingNights",
        nights
    );


    setText(
        "#bookingSubtotal",
        formatCurrency(
            subtotal
        )
    );


    return {

        nights,

        roomPrice,

        subtotal,

        discount,

        tax,

        total

    };

}


/* =========================================================
   CALCULATE NIGHTS
========================================================= */

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


/* =========================================================
   CONFIRM BOOKING
========================================================= */

async function confirmBooking(
    bookingId
) {

    return updateBookingStatus(
        bookingId,
        "confirmed",
        "Reservation confirmed successfully."
    );

}


/* =========================================================
   CHECK IN
========================================================= */

async function checkInBooking(
    bookingId
) {

    const booking =
        await getBooking(
            bookingId
        );


    const status =
        String(
            booking?.status ||
            ""
        ).toLowerCase();


    if (
        status !== "confirmed"
    ) {

        showBookingError(
            new Error(
                "Only confirmed reservations can be checked in."
            )
        );


        return;

    }


    return updateBookingStatus(
        bookingId,
        "checked_in",
        "Guest checked in successfully."
    );

}


/* =========================================================
   CHECK OUT
========================================================= */

async function checkOutBooking(
    bookingId
) {

    const booking =
        await getBooking(
            bookingId
        );


    const status =
        String(
            booking?.status ||
            ""
        ).toLowerCase();


    if (
        status !== "checked_in"
    ) {

        showBookingError(
            new Error(
                "Only checked-in reservations can be checked out."
            )
        );


        return;

    }


    return updateBookingStatus(
        bookingId,
        "checked_out",
        "Guest checked out successfully."
    );

}


/* =========================================================
   CANCEL BOOKING
========================================================= */

async function cancelBooking(
    bookingId
) {

    if (!bookingId) {
        return;
    }


    const booking =
        bookingState.bookings.find(
            item =>
                String(
                    getBookingId(item)
                ) ===
                String(bookingId)
        );


    const reference =
        booking
            ? getBookingReference(
                booking
            )
            : bookingId;


    const confirmed =
        window.confirm(
            `Are you sure you want to cancel reservation ${reference}?`
        );


    if (!confirmed) {
        return;
    }


    return updateBookingStatus(
        bookingId,
        "cancelled",
        "Reservation cancelled successfully."
    );

}


/* =========================================================
   UPDATE BOOKING STATUS
========================================================= */

async function updateBookingStatus(
    bookingId,
    status,
    successMessage
) {

    if (
        !bookingId ||
        !status
    ) {

        throw new Error(
            "Booking ID and status are required."
        );

    }


    if (
        !BOOKING_CONFIG.STATUSES.includes(
            status
        )
    ) {

        throw new Error(
            "Invalid booking status."
        );

    }


    try {

        /*
         * Prefer a dedicated API method when
         * available, otherwise use updateBooking.
         */
        let response;


        if (
            typeof HotelAPI.updateBookingStatus ===
            "function"
        ) {

            response =
                await HotelAPI.updateBookingStatus(
                    bookingId,
                    status
                );

        } else {

            response =
                await HotelAPI.updateBooking(
                    bookingId,
                    {
                        status
                    }
                );

        }


        if (window.HotelApp) {

            HotelApp.showAlert(
                successMessage ||
                "Booking status updated.",
                "success"
            );

        }


        await loadBookings();


        return response;

    } catch (error) {

        console.error(
            "Failed to update booking status:",
            error
        );


        showBookingError(
            error
        );


        throw error;

    }

}


/* =========================================================
   UPDATE PAYMENT STATUS
========================================================= */

async function updatePaymentStatus(
    bookingId,
    paymentStatus
) {

    if (
        !bookingId ||
        !paymentStatus
    ) {

        throw new Error(
            "Booking ID and payment status are required."
        );

    }


    if (
        !BOOKING_CONFIG.PAYMENT_STATUSES.includes(
            paymentStatus
        )
    ) {

        throw new Error(
            "Invalid payment status."
        );

    }


    try {

        const response =
            await HotelAPI.updateBooking(
                bookingId,
                {
                    payment_status:
                        paymentStatus
                }
            );


        if (window.HotelApp) {

            HotelApp.showAlert(
                "Payment status updated successfully.",
                "success"
            );

        }


        await loadBookings();


        return response;

    } catch (error) {

        console.error(
            "Failed to update payment status:",
            error
        );


        showBookingError(
            error
        );


        throw error;

    }

}


/* =========================================================
   BOOKING ACTION DELEGATION
========================================================= */

function handleBookingActions(
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


    const bookingId =
        button.dataset.bookingId;


    switch (action) {

        case "view-booking":

            viewBooking(
                bookingId
            );

            break;


        case "edit-booking":

            editBooking(
                bookingId
            );

            break;


        case "confirm-booking":

            confirmBooking(
                bookingId
            );

            break;


        case "check-in":

            checkInBooking(
                bookingId
            );

            break;


        case "check-out":

            checkOutBooking(
                bookingId
            );

            break;


        case "cancel-booking":

            cancelBooking(
                bookingId
            );

            break;


        default:

            break;

    }

}


/* =========================================================
   CLOSE BOOKING FORM
========================================================= */

function closeBookingForm() {

    const form =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.bookingForm
        );


    if (form) {
        form.reset();
    }


    bookingState.selectedBooking =
        null;


    bookingState.editing =
        false;


    bookingState.availableRooms =
        [];


    if (window.HotelApp) {

        HotelApp.closeModal(
            "bookingModal"
        );

    }

}


/* =========================================================
   FORM UI
========================================================= */

function updateBookingFormTitle() {

    const title =
        document.querySelector(
            "#bookingModalTitle"
        );


    if (!title) {
        return;
    }


    title.textContent =
        bookingState.editing
            ? "Edit Reservation"
            : "New Reservation";

}


function setBookingFormLoading(
    loading
) {

    const button =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.saveButton
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
            bookingState.editing
                ? "Updating..."
                : "Creating...";

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


/* =========================================================
   BOOKING STATISTICS
========================================================= */

function updateBookingStatistics() {

    const bookings =
        bookingState.bookings;


    const total =
        bookings.length;


    const confirmed =
        bookings.filter(
            booking =>
                String(
                    booking.status ||
                    ""
                ).toLowerCase() ===
                "confirmed"
        ).length;


    const pending =
        bookings.filter(
            booking =>
                String(
                    booking.status ||
                    ""
                ).toLowerCase() ===
                "pending"
        ).length;


    const checkedIn =
        bookings.filter(
            booking =>
                String(
                    booking.status ||
                    ""
                ).toLowerCase() ===
                "checked_in"
        ).length;


    setText(
        BOOKING_CONFIG.SELECTORS.totalBookings,
        total
    );


    setText(
        BOOKING_CONFIG.SELECTORS.confirmedBookings,
        confirmed
    );


    setText(
        BOOKING_CONFIG.SELECTORS.pendingBookings,
        pending
    );


    setText(
        BOOKING_CONFIG.SELECTORS.checkedInBookings,
        checkedIn
    );

}


/* =========================================================
   LOADING UI
========================================================= */

function showBookingsLoading() {

    const container =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.bookingContainer
        );


    const tbody =
        document.querySelector(
            BOOKING_CONFIG.SELECTORS.bookingTableBody
        );


    if (container) {

        container.innerHTML = `
            <div class="loading-state">

                <div class="spinner"></div>

                <p>
                    Loading reservations...
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
                    Loading reservations...
                </td>

            </tr>
        `;

    }

}


function hideBookingsLoading() {

    document.body.classList.remove(
        "bookings-loading"
    );

}


/* =========================================================
   ERROR HANDLING
========================================================= */

function showBookingError(
    error
) {

    const message =
        error?.message ||
        "Unable to complete reservation operation.";


    console.error(
        "Booking operation error:",
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
   BOOKING HELPERS
========================================================= */

function getBookingId(
    booking
) {

    if (!booking) {
        return "";
    }


    return (
        booking.id ??
        booking.booking_id ??
        ""
    );

}


function getBookingReference(
    booking
) {

    if (!booking) {
        return "";
    }


    return (
        booking.booking_reference ||
        booking.bookingReference ||
        booking.reference ||
        booking.booking_number ||
        `BK-${getBookingId(booking)}`
    );

}


function getBookingGuestName(
    booking
) {

    if (!booking) {
        return "Unknown Guest";
    }


    if (
        booking.guest?.name
    ) {

        return booking.guest.name;

    }


    if (
        booking.guest_name
    ) {

        return booking.guest_name;

    }


    const firstName =
        booking.guest?.first_name ||
        booking.guest?.firstName ||
        booking.first_name ||
        "";


    const lastName =
        booking.guest?.last_name ||
        booking.guest?.lastName ||
        booking.last_name ||
        "";


    const name =
        `${firstName} ${lastName}`
            .trim();


    return name ||
        "Unknown Guest";

}


function getBookingRoomName(
    booking
) {

    if (!booking) {
        return "Unknown Room";
    }


    if (
        booking.room?.room_number
    ) {

        return String(
            booking.room.room_number
        );

    }


    if (
        booking.room?.roomNumber
    ) {

        return String(
            booking.room.roomNumber
        );

    }


    return String(
        booking.room_number ||
        booking.roomNumber ||
        booking.room_name ||
        booking.room?.name ||
        booking.room_id ||
        "Unknown Room"
    );

}


function getBookingTotal(
    booking
) {

    if (!booking) {
        return 0;
    }


    return Number(
        booking.total_amount ??
        booking.totalAmount ??
        booking.total ??
        0
    );

}


function formatBookingStatus(
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
     * Change USD here if your hotel
     * uses another currency.
     */
    return new Intl.NumberFormat(
        undefined,
        {
            style: "currency",
            currency: "USD"
        }
    ).format(amount);

}


function getFieldValue(
    selector
) {

    const element =
        document.querySelector(
            selector
        );


    return element?.value ??
        "";

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
   PUBLIC BOOKING API
========================================================= */

window.HotelBookings = {

    load:
        loadBookings,


    refresh:
        () =>
            loadBookings(),


    get:
        getBooking,


    view:
        viewBooking,


    create:
        async data =>
            HotelAPI.createBooking(
                data
            ),


    update:
        async (
            id,
            data
        ) =>
            HotelAPI.updateBooking(
                id,
                data
            ),


    remove:
        cancelBooking,


    confirm:
        confirmBooking,


    checkIn:
        checkInBooking,


    checkOut:
        checkOutBooking,


    cancel:
        cancelBooking,


    updateStatus:
        updateBookingStatus,


    updatePaymentStatus:
        updatePaymentStatus,


    checkAvailability:
        checkRoomAvailability,


    loadAvailableRooms:
        loadAvailableRooms,


    calculateTotal:
        calculateBookingTotal,


    openForm:
        openBookingForm,


    closeForm:
        closeBookingForm,


    search:
        filterBookings,


    getState:
        () => ({

            ...bookingState,

            bookings:
                [
                    ...bookingState.bookings
                ],

            filteredBookings:
                [
                    ...bookingState.filteredBookings
                ],

            availableRooms:
                [
                    ...bookingState.availableRooms
                ]

        })

};
