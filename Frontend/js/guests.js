/**
 * Hotel Management System
 * frontend/js/guests.js
 *
 * Guest Operations
 *
 * Responsibilities:
 * - Load guests
 * - Search guests
 * - Filter guests
 * - View guest details
 * - Create guests
 * - Update guests
 * - Delete guests
 * - Handle guest forms
 * - Display guest statistics
 */

"use strict";


/* =========================================================
   GUEST CONFIGURATION
========================================================= */

const GUEST_CONFIG = {

    SELECTORS: {

        guestContainer:
            "#guestsContainer",

        guestTableBody:
            "#guestsTableBody",

        searchInput:
            "#guestSearch",

        statusFilter:
            "#guestStatusFilter",

        countryFilter:
            "#guestCountryFilter",

        guestForm:
            "#guestForm",

        guestId:
            "#guestId",

        firstName:
            "#firstName",

        lastName:
            "#lastName",

        email:
            "#guestEmail",

        phone:
            "#guestPhone",

        gender:
            "#guestGender",

        dateOfBirth:
            "#guestDateOfBirth",

        nationality:
            "#guestNationality",

        address:
            "#guestAddress",

        city:
            "#guestCity",

        country:
            "#guestCountry",

        idType:
            "#guestIdType",

        idNumber:
            "#guestIdNumber",

        notes:
            "#guestNotes",

        addGuestButton:
            "#addGuestBtn",

        saveGuestButton:
            "#saveGuestBtn",

        totalGuests:
            "#totalGuests",

        activeGuests:
            "#activeGuests"
    },

    STATUSES: [
        "active",
        "inactive",
        "blacklisted"
    ]
};


/* =========================================================
   GUEST STATE
========================================================= */

const guestState = {

    guests: [],

    filteredGuests: [],

    selectedGuest: null,

    loading: false,

    editing: false

};


/* =========================================================
   INITIALIZATION
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        initializeGuests();

    }
);


async function initializeGuests() {

    /*
     * Protect guest management page.
     */
    if (
        window.HotelAuth &&
        !HotelAuth.requireAuth()
    ) {
        return;
    }


    initializeGuestEvents();

    await loadGuests();

}


/* =========================================================
   EVENT INITIALIZATION
========================================================= */

function initializeGuestEvents() {

    const searchInput =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.searchInput
        );

    const statusFilter =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.statusFilter
        );

    const countryFilter =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.countryFilter
        );

    const guestForm =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.guestForm
        );

    const addButton =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.addGuestButton
        );


    /* Search */

    if (searchInput) {

        searchInput.addEventListener(
            "input",
            debounceGuestSearch()
        );

    }


    /* Status filter */

    if (statusFilter) {

        statusFilter.addEventListener(
            "change",
            filterGuests
        );

    }


    /* Country filter */

    if (countryFilter) {

        countryFilter.addEventListener(
            "change",
            filterGuests
        );

    }


    /* Guest form */

    if (guestForm) {

        guestForm.addEventListener(
            "submit",
            handleGuestFormSubmit
        );

    }


    /* Add guest */

    if (addButton) {

        addButton.addEventListener(
            "click",
            () => openGuestForm()
        );

    }


    /*
     * Event delegation for guest actions.
     */
    document.addEventListener(
        "click",
        handleGuestActions
    );

}


/* =========================================================
   LOAD GUESTS
========================================================= */

async function loadGuests(filters = {}) {

    if (guestState.loading) {
        return;
    }


    guestState.loading = true;

    showGuestsLoading();


    try {

        const response =
            await HotelAPI.getGuests(filters);


        guestState.guests =
            extractGuestList(response);


        guestState.filteredGuests =
            [...guestState.guests];


        updateGuestStatistics();

        renderGuests();

        populateCountryFilter();


    } catch (error) {

        console.error(
            "Failed to load guests:",
            error
        );

        showGuestError(error);


    } finally {

        guestState.loading = false;

        hideGuestsLoading();

    }

}


/* =========================================================
   EXTRACT GUEST LIST
========================================================= */

function extractGuestList(response) {

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


    if (Array.isArray(response.items)) {
        return response.items;
    }


    return [];

}


/* =========================================================
   RENDER GUESTS
========================================================= */

function renderGuests() {

    renderGuestCards();

    renderGuestTable();

}


/* =========================================================
   GUEST CARDS
========================================================= */

function renderGuestCards() {

    const container =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.guestContainer
        );


    if (!container) {
        return;
    }


    const guests =
        guestState.filteredGuests;


    if (!guests.length) {

        container.innerHTML = `
            <div class="empty-state">
                <h3>No guests found</h3>

                <p>
                    No guests match your current
                    search or filters.
                </p>
            </div>
        `;

        return;
    }


    container.innerHTML =
        guests
            .map(guest =>
                createGuestCard(guest)
            )
            .join("");

}


/* =========================================================
   CREATE GUEST CARD
========================================================= */

function createGuestCard(guest) {

    const id =
        getGuestId(guest);


    const name =
        getGuestName(guest);


    const email =
        guest.email ||
        "-";


    const phone =
        guest.phone ||
        guest.phone_number ||
        "-";


    const country =
        guest.country ||
        guest.nationality ||
        "-";


    const status =
        guest.status ||
        "active";


    return `
        <article
            class="guest-card"
            data-guest-id="${escapeHtml(id)}"
        >

            <div class="guest-card-header">

                <div class="guest-avatar">
                    ${escapeHtml(
                        getGuestInitials(guest)
                    )}
                </div>

                <div>

                    <h3>
                        ${escapeHtml(name)}
                    </h3>

                    <span
                        class="status status-${escapeHtml(
                            String(status).toLowerCase()
                        )}"
                    >
                        ${escapeHtml(
                            formatGuestStatus(status)
                        )}
                    </span>

                </div>

            </div>


            <div class="guest-card-body">

                <p>
                    <strong>Email:</strong>
                    ${escapeHtml(email)}
                </p>

                <p>
                    <strong>Phone:</strong>
                    ${escapeHtml(phone)}
                </p>

                <p>
                    <strong>Country:</strong>
                    ${escapeHtml(country)}
                </p>

            </div>


            <div class="guest-card-actions">

                <button
                    type="button"
                    class="btn btn-primary"
                    data-action="view-guest"
                    data-guest-id="${escapeHtml(id)}"
                >
                    View
                </button>


                <button
                    type="button"
                    class="btn btn-secondary"
                    data-action="edit-guest"
                    data-guest-id="${escapeHtml(id)}"
                >
                    Edit
                </button>


                <button
                    type="button"
                    class="btn btn-danger"
                    data-action="delete-guest"
                    data-guest-id="${escapeHtml(id)}"
                >
                    Delete
                </button>

            </div>

        </article>
    `;

}


/* =========================================================
   GUEST TABLE
========================================================= */

function renderGuestTable() {

    const tbody =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.guestTableBody
        );


    if (!tbody) {
        return;
    }


    const guests =
        guestState.filteredGuests;


    if (!guests.length) {

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="8"
                    class="empty-state"
                >
                    No guests found.
                </td>
            </tr>
        `;

        return;
    }


    tbody.innerHTML =
        guests
            .map(guest =>
                createGuestTableRow(guest)
            )
            .join("");

}


/* =========================================================
   CREATE GUEST TABLE ROW
========================================================= */

function createGuestTableRow(guest) {

    const id =
        getGuestId(guest);


    const name =
        getGuestName(guest);


    const email =
        guest.email ||
        "-";


    const phone =
        guest.phone ||
        guest.phone_number ||
        "-";


    const country =
        guest.country ||
        "-";


    const nationality =
        guest.nationality ||
        "-";


    const status =
        guest.status ||
        "active";


    return `
        <tr
            data-guest-id="${escapeHtml(id)}"
        >

            <td>
                ${escapeHtml(id)}
            </td>


            <td>
                <strong>
                    ${escapeHtml(name)}
                </strong>
            </td>


            <td>
                ${escapeHtml(email)}
            </td>


            <td>
                ${escapeHtml(phone)}
            </td>


            <td>
                ${escapeHtml(nationality)}
            </td>


            <td>
                ${escapeHtml(country)}
            </td>


            <td>

                <span
                    class="status status-${escapeHtml(
                        String(status).toLowerCase()
                    )}"
                >
                    ${escapeHtml(
                        formatGuestStatus(status)
                    )}
                </span>

            </td>


            <td>

                <button
                    type="button"
                    class="btn btn-sm"
                    data-action="view-guest"
                    data-guest-id="${escapeHtml(id)}"
                >
                    View
                </button>


                <button
                    type="button"
                    class="btn btn-sm"
                    data-action="edit-guest"
                    data-guest-id="${escapeHtml(id)}"
                >
                    Edit
                </button>


                <button
                    type="button"
                    class="btn btn-sm btn-danger"
                    data-action="delete-guest"
                    data-guest-id="${escapeHtml(id)}"
                >
                    Delete
                </button>

            </td>

        </tr>
    `;

}


/* =========================================================
   SEARCH
========================================================= */

function debounceGuestSearch() {

    let timeout;


    return () => {

        clearTimeout(timeout);


        timeout = setTimeout(
            filterGuests,
            300
        );

    };

}


/* =========================================================
   FILTER GUESTS
========================================================= */

function filterGuests() {

    const searchInput =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.searchInput
        );


    const statusFilter =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.statusFilter
        );


    const countryFilter =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.countryFilter
        );


    const search =
        searchInput?.value
            ?.trim()
            .toLowerCase() || "";


    const status =
        statusFilter?.value
            ?.trim()
            .toLowerCase() || "";


    const country =
        countryFilter?.value
            ?.trim()
            .toLowerCase() || "";


    guestState.filteredGuests =
        guestState.guests.filter(
            guest => {

                const name =
                    getGuestName(
                        guest
                    ).toLowerCase();


                const email =
                    String(
                        guest.email || ""
                    ).toLowerCase();


                const phone =
                    String(
                        guest.phone ||
                        guest.phone_number ||
                        ""
                    ).toLowerCase();


                const idNumber =
                    String(
                        guest.id_number ||
                        guest.idNumber ||
                        ""
                    ).toLowerCase();


                const guestCountry =
                    String(
                        guest.country ||
                        ""
                    ).toLowerCase();


                const guestStatus =
                    String(
                        guest.status ||
                        ""
                    ).toLowerCase();


                const matchesSearch =
                    !search ||
                    name.includes(search) ||
                    email.includes(search) ||
                    phone.includes(search) ||
                    idNumber.includes(search);


                const matchesStatus =
                    !status ||
                    guestStatus === status;


                const matchesCountry =
                    !country ||
                    guestCountry === country;


                return (
                    matchesSearch &&
                    matchesStatus &&
                    matchesCountry
                );

            }
        );


    renderGuests();

}


/* =========================================================
   COUNTRY FILTER
========================================================= */

function populateCountryFilter() {

    const select =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.countryFilter
        );


    if (!select) {
        return;
    }


    const currentValue =
        select.value;


    const countries =
        [
            ...new Set(
                guestState.guests
                    .map(
                        guest =>
                            guest.country
                    )
                    .filter(Boolean)
            )
        ]
        .sort(
            (a, b) =>
                String(a).localeCompare(
                    String(b)
                )
        );


    /*
     * Keep the first option.
     */
    select.innerHTML = `
        <option value="">
            All Countries
        </option>
    `;


    countries.forEach(country => {

        const option =
            document.createElement(
                "option"
            );


        option.value =
            country;


        option.textContent =
            country;


        select.appendChild(
            option
        );

    });


    select.value =
        currentValue;

}


/* =========================================================
   GET SINGLE GUEST
========================================================= */

async function getGuest(guestId) {

    if (!guestId) {

        throw new Error(
            "Guest ID is required."
        );

    }


    try {

        const response =
            await HotelAPI.getGuest(
                guestId
            );


        const guest =
            response?.data ??
            response;


        guestState.selectedGuest =
            guest;


        return guest;

    } catch (error) {

        console.error(
            "Failed to get guest:",
            error
        );

        throw error;

    }

}


/* =========================================================
   VIEW GUEST
========================================================= */

async function viewGuest(guestId) {

    try {

        await getGuest(
            guestId
        );


        const detailsPage =
            "../pages/guests/guest-details.html";


        const url =
            `${detailsPage}?id=${encodeURIComponent(
                guestId
            )}`;


        window.location.href =
            url;


    } catch (error) {

        showGuestError(error);

    }

}


/* =========================================================
   OPEN GUEST FORM
========================================================= */

function openGuestForm(
    guest = null
) {

    const form =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.guestForm
        );


    if (!form) {
        return;
    }


    guestState.editing =
        Boolean(guest);


    guestState.selectedGuest =
        guest;


    form.reset();


    setField(
        GUEST_CONFIG.SELECTORS.guestId,
        getGuestId(guest)
    );


    setField(
        GUEST_CONFIG.SELECTORS.firstName,
        guest?.first_name ||
        guest?.firstName ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.lastName,
        guest?.last_name ||
        guest?.lastName ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.email,
        guest?.email ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.phone,
        guest?.phone ||
        guest?.phone_number ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.gender,
        guest?.gender ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.dateOfBirth,
        guest?.date_of_birth ||
        guest?.dateOfBirth ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.nationality,
        guest?.nationality ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.address,
        guest?.address ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.city,
        guest?.city ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.country,
        guest?.country ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.idType,
        guest?.id_type ||
        guest?.idType ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.idNumber,
        guest?.id_number ||
        guest?.idNumber ||
        ""
    );


    setField(
        GUEST_CONFIG.SELECTORS.notes,
        guest?.notes ||
        ""
    );


    updateGuestFormTitle();


    if (window.HotelApp) {

        HotelApp.openModal(
            "guestModal"
        );

    }

}


/* =========================================================
   EDIT GUEST
========================================================= */

async function editGuest(
    guestId
) {

    try {

        const guest =
            await getGuest(
                guestId
            );


        openGuestForm(
            guest
        );

    } catch (error) {

        showGuestError(error);

    }

}


/* =========================================================
   SUBMIT GUEST FORM
========================================================= */

async function handleGuestFormSubmit(
    event
) {

    event.preventDefault();


    const form =
        event.currentTarget;


    const data =
        getGuestFormData(
            form
        );


    const validation =
        validateGuestData(
            data
        );


    if (!validation.valid) {

        showGuestError(
            new Error(
                validation.message
            )
        );

        return;
    }


    const guestId =
        data.id;


    try {

        setGuestFormLoading(
            true
        );


        let response;


        if (guestId) {

            response =
                await HotelAPI.updateGuest(
                    guestId,
                    data
                );

        } else {

            response =
                await HotelAPI.createGuest(
                    data
                );

        }


        if (window.HotelApp) {

            HotelApp.showAlert(
                guestId
                    ? "Guest updated successfully."
                    : "Guest created successfully.",
                "success"
            );

        }


        closeGuestForm();


        await loadGuests();


        return response;

    } catch (error) {

        console.error(
            "Failed to save guest:",
            error
        );


        showGuestError(
            error
        );


        throw error;

    } finally {

        setGuestFormLoading(
            false
        );

    }

}


/* =========================================================
   GET FORM DATA
========================================================= */

function getGuestFormData(
    form
) {

    const formData =
        new FormData(form);


    return {

        id:
            formData.get("id") ||
            formData.get("guest_id") ||
            "",


        first_name:
            String(
                formData.get(
                    "first_name"
                ) || ""
            ).trim(),


        last_name:
            String(
                formData.get(
                    "last_name"
                ) || ""
            ).trim(),


        email:
            String(
                formData.get(
                    "email"
                ) || ""
            ).trim(),


        phone:
            String(
                formData.get(
                    "phone"
                ) || ""
            ).trim(),


        gender:
            String(
                formData.get(
                    "gender"
                ) || ""
            ).trim(),


        date_of_birth:
            String(
                formData.get(
                    "date_of_birth"
                ) || ""
            ).trim(),


        nationality:
            String(
                formData.get(
                    "nationality"
                ) || ""
            ).trim(),


        address:
            String(
                formData.get(
                    "address"
                ) || ""
            ).trim(),


        city:
            String(
                formData.get(
                    "city"
                ) || ""
            ).trim(),


        country:
            String(
                formData.get(
                    "country"
                ) || ""
            ).trim(),


        id_type:
            String(
                formData.get(
                    "id_type"
                ) || ""
            ).trim(),


        id_number:
            String(
                formData.get(
                    "id_number"
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
   VALIDATE GUEST
========================================================= */

function validateGuestData(
    data
) {

    if (!data.first_name) {

        return {
            valid: false,
            message:
                "First name is required."
        };

    }


    if (!data.last_name) {

        return {
            valid: false,
            message:
                "Last name is required."
        };

    }


    if (!data.email) {

        return {
            valid: false,
            message:
                "Email address is required."
        };

    }


    if (
        !isValidEmail(
            data.email
        )
    ) {

        return {
            valid: false,
            message:
                "Please enter a valid email address."
        };

    }


    if (
        data.phone &&
        data.phone.length < 7
    ) {

        return {
            valid: false,
            message:
                "Please enter a valid phone number."
        };

    }


    if (
        data.id_type &&
        !data.id_number
    ) {

        return {
            valid: false,
            message:
                "Identification number is required."
        };

    }


    return {
        valid: true,
        message: ""
    };

}


/* =========================================================
   DELETE GUEST
========================================================= */

async function deleteGuest(
    guestId
) {

    if (!guestId) {
        return;
    }


    const guest =
        guestState.guests.find(
            item =>
                String(
                    getGuestId(item)
                ) === String(guestId)
        );


    const guestName =
        guest
            ? getGuestName(guest)
            : "this guest";


    const confirmed =
        window.confirm(
            `Are you sure you want to delete ${guestName}?`
        );


    if (!confirmed) {
        return;
    }


    try {

        await HotelAPI.deleteGuest(
            guestId
        );


        if (window.HotelApp) {

            HotelApp.showAlert(
                "Guest deleted successfully.",
                "success"
            );

        }


        await loadGuests();

    } catch (error) {

        console.error(
            "Failed to delete guest:",
            error
        );


        showGuestError(
            error
        );

    }

}


/* =========================================================
   UPDATE GUEST STATUS
========================================================= */

async function changeGuestStatus(
    guestId,
    status
) {

    if (!guestId || !status) {
        return;
    }


    const normalizedStatus =
        String(status)
            .toLowerCase();


    if (
        !GUEST_CONFIG.STATUSES.includes(
            normalizedStatus
        )
    ) {

        showGuestError(
            new Error(
                "Invalid guest status."
            )
        );

        return;
    }


    try {

        await HotelAPI.updateGuest(
            guestId,
            {
                status:
                    normalizedStatus
            }
        );


        if (window.HotelApp) {

            HotelApp.showAlert(
                "Guest status updated successfully.",
                "success"
            );

        }


        await loadGuests();

    } catch (error) {

        console.error(
            "Failed to update guest status:",
            error
        );


        showGuestError(
            error
        );

    }

}


/* =========================================================
   GUEST ACTION DELEGATION
========================================================= */

function handleGuestActions(
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


    const guestId =
        button.dataset.guestId;


    switch (action) {

        case "view-guest":

            viewGuest(
                guestId
            );

            break;


        case "edit-guest":

            editGuest(
                guestId
            );

            break;


        case "delete-guest":

            deleteGuest(
                guestId
            );

            break;


        default:

            break;

    }

}


/* =========================================================
   CLOSE GUEST FORM
========================================================= */

function closeGuestForm() {

    const form =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.guestForm
        );


    if (form) {
        form.reset();
    }


    guestState.selectedGuest =
        null;


    guestState.editing =
        false;


    if (window.HotelApp) {

        HotelApp.closeModal(
            "guestModal"
        );

    }

}


/* =========================================================
   FORM UI
========================================================= */

function updateGuestFormTitle() {

    const title =
        document.querySelector(
            "#guestModalTitle"
        );


    if (!title) {
        return;
    }


    title.textContent =
        guestState.editing
            ? "Edit Guest"
            : "Add Guest";

}


function setGuestFormLoading(
    loading
) {

    const button =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.saveGuestButton
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
            guestState.editing
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


/* =========================================================
   GUEST STATISTICS
========================================================= */

function updateGuestStatistics() {

    const total =
        guestState.guests.length;


    const active =
        guestState.guests.filter(
            guest =>
                String(
                    guest.status ||
                    "active"
                ).toLowerCase() ===
                "active"
        ).length;


    setText(
        GUEST_CONFIG.SELECTORS.totalGuests,
        total
    );


    setText(
        GUEST_CONFIG.SELECTORS.activeGuests,
        active
    );

}


/* =========================================================
   LOADING STATE
========================================================= */

function showGuestsLoading() {

    const container =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.guestContainer
        );


    const tableBody =
        document.querySelector(
            GUEST_CONFIG.SELECTORS.guestTableBody
        );


    if (container) {

        container.innerHTML = `
            <div class="loading-state">

                <div class="spinner"></div>

                <p>
                    Loading guests...
                </p>

            </div>
        `;

    }


    if (tableBody) {

        tableBody.innerHTML = `
            <tr>
                <td
                    colspan="8"
                    class="loading-state"
                >
                    Loading guests...
                </td>
            </tr>
        `;

    }

}


function hideGuestsLoading() {

    document.body.classList.remove(
        "guests-loading"
    );

}


/* =========================================================
   ERROR HANDLING
========================================================= */

function showGuestError(
    error
) {

    const message =
        error?.message ||
        "Unable to complete guest operation.";


    console.error(
        "Guest operation error:",
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
   GUEST HELPERS
========================================================= */

function getGuestId(
    guest
) {

    if (!guest) {
        return "";
    }


    return (
        guest.id ??
        guest.guest_id ??
        ""
    );

}


function getGuestName(
    guest
) {

    if (!guest) {
        return "Unknown Guest";
    }


    if (guest.name) {
        return guest.name;
    }


    const firstName =
        guest.first_name ||
        guest.firstName ||
        "";


    const lastName =
        guest.last_name ||
        guest.lastName ||
        "";


    const fullName =
        `${firstName} ${lastName}`
            .trim();


    return fullName ||
        "Unknown Guest";

}


function getGuestInitials(
    guest
) {

    const name =
        getGuestName(
            guest
        );


    if (
        !name ||
        name === "Unknown Guest"
    ) {
        return "?";
    }


    const parts =
        name
            .trim()
            .split(/\s+/);


    if (parts.length === 1) {

        return parts[0]
            .substring(0, 2)
            .toUpperCase();

    }


    return (
        parts[0][0] +
        parts[parts.length - 1][0]
    ).toUpperCase();

}


function formatGuestStatus(
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


function isValidEmail(
    email
) {

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        .test(email);

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
        value ?? 0;

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
   PUBLIC GUEST API
========================================================= */

window.HotelGuests = {

    load:
        loadGuests,


    refresh:
        () =>
            loadGuests(),


    get:
        getGuest,


    view:
        viewGuest,


    create:
        async data =>
            HotelAPI.createGuest(
                data
            ),


    update:
        async (
            id,
            data
        ) =>
            HotelAPI.updateGuest(
                id,
                data
            ),


    remove:
        deleteGuest,


    changeStatus:
        changeGuestStatus,


    search:
        filterGuests,


    openForm:
        openGuestForm,


    closeForm:
        closeGuestForm,


    getState:
        () => ({

            ...guestState,

            guests:
                [
                    ...guestState.guests
                ],

            filteredGuests:
                [
                    ...guestState.filteredGuests
                ]

        })

};
