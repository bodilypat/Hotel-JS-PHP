/**
 * Hotel Management System
 * frontend/js/staff.js
 *
 * Staff Management Operations
 *
 * Responsibilities:
 * - Load staff members
 * - Search and filter staff
 * - View staff details
 * - Create staff members
 * - Update staff members
 * - Delete/deactivate staff
 * - Change staff status
 * - Assign staff roles/departments
 * - Handle staff forms
 * - Display staff statistics
 */

"use strict";


/* =========================================================
   STAFF CONFIGURATION
========================================================= */

const STAFF_CONFIG = {

    SELECTORS: {

        staffContainer:
            "#staffContainer",

        staffTableBody:
            "#staffTableBody",

        searchInput:
            "#staffSearch",

        statusFilter:
            "#staffStatusFilter",

        roleFilter:
            "#staffRoleFilter",

        departmentFilter:
            "#staffDepartmentFilter",

        staffForm:
            "#staffForm",

        staffId:
            "#staffId",

        firstName:
            "#staffFirstName",

        lastName:
            "#staffLastName",

        email:
            "#staffEmail",

        phone:
            "#staffPhone",

        role:
            "#staffRole",

        department:
            "#staffDepartment",

        status:
            "#staffStatus",

        hireDate:
            "#staffHireDate",

        address:
            "#staffAddress",

        salary:
            "#staffSalary",

        username:
            "#staffUsername",

        password:
            "#staffPassword",

        notes:
            "#staffNotes",

        saveButton:
            "#saveStaffBtn",

        totalStaff:
            "#totalStaff",

        activeStaff:
            "#activeStaff",

        inactiveStaff:
            "#inactiveStaff",

        managers:
            "#managerCount",

        staffDetailsModal:
            "#staffDetailsModal"

    },


    STATUSES: [
        "active",
        "inactive",
        "suspended",
        "terminated"
    ],


    ROLES: [
        "admin",
        "manager",
        "receptionist",
        "housekeeping",
        "maintenance",
        "accountant",
        "security",
        "staff"
    ],


    DEPARTMENTS: [
        "management",
        "front_desk",
        "housekeeping",
        "maintenance",
        "finance",
        "security",
        "food_beverage",
        "human_resources",
        "other"
    ]

};


/* =========================================================
   STAFF STATE
========================================================= */

const staffState = {

    staff: [],

    filteredStaff: [],

    selectedStaff: null,

    editing: false,

    loading: false

};


/* =========================================================
   INITIALIZATION
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        initializeStaff();

    }
);


async function initializeStaff() {

    if (
        window.HotelAuth &&
        !HotelAuth.requireAuth()
    ) {

        return;

    }


    initializeStaffEvents();

    await loadStaff();

}


/* =========================================================
   EVENT HANDLERS
========================================================= */

function initializeStaffEvents() {

    const searchInput =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.searchInput
        );


    const statusFilter =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.statusFilter
        );


    const roleFilter =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.roleFilter
        );


    const departmentFilter =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.departmentFilter
        );


    const staffForm =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.staffForm
        );


    if (searchInput) {

        searchInput.addEventListener(
            "input",
            debounceStaffSearch()
        );

    }


    if (statusFilter) {

        statusFilter.addEventListener(
            "change",
            filterStaff
        );

    }


    if (roleFilter) {

        roleFilter.addEventListener(
            "change",
            filterStaff
        );

    }


    if (departmentFilter) {

        departmentFilter.addEventListener(
            "change",
            filterStaff
        );

    }


    if (staffForm) {

        staffForm.addEventListener(
            "submit",
            handleStaffFormSubmit
        );

    }


    document.addEventListener(
        "click",
        handleStaffActions
    );

}


/* =========================================================
   LOAD STAFF
========================================================= */

async function loadStaff(
    filters = {}
) {

    if (staffState.loading) {
        return;
    }


    staffState.loading =
        true;


    showStaffLoading();


    try {

        if (
            typeof HotelAPI.getStaff !==
            "function"
        ) {

            throw new Error(
                "HotelAPI.getStaff() is not available."
            );

        }


        const response =
            await HotelAPI.getStaff(
                filters
            );


        staffState.staff =
            extractStaffList(
                response
            );


        staffState.filteredStaff =
            [
                ...staffState.staff
            ];


        updateStaffStatistics();

        renderStaff();


    } catch (error) {

        console.error(
            "Failed to load staff:",
            error
        );


        showStaffError(
            error
        );


    } finally {

        staffState.loading =
            false;


        hideStaffLoading();

    }

}


/* =========================================================
   EXTRACT STAFF LIST
========================================================= */

function extractStaffList(
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
   RENDER STAFF
========================================================= */

function renderStaff() {

    renderStaffCards();

    renderStaffTable();

}


/* =========================================================
   STAFF CARDS
========================================================= */

function renderStaffCards() {

    const container =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.staffContainer
        );


    if (!container) {
        return;
    }


    const staff =
        staffState.filteredStaff;


    if (!staff.length) {

        container.innerHTML = `
            <div class="empty-state">

                <h3>
                    No staff members found
                </h3>

                <p>
                    There are no staff records
                    matching your filters.
                </p>

            </div>
        `;

        return;

    }


    container.innerHTML =
        staff
            .map(
                member =>
                    createStaffCard(
                        member
                    )
            )
            .join("");

}


/* =========================================================
   STAFF CARD
========================================================= */

function createStaffCard(
    staff
) {

    const id =
        getStaffId(
            staff
        );


    const name =
        getStaffName(
            staff
        );


    const role =
        getStaffRole(
            staff
        );


    const status =
        getStaffStatus(
            staff
        );


    const department =
        getStaffDepartment(
            staff
        );


    const avatar =
        getStaffAvatar(
            staff
        );


    return `
        <article
            class="staff-card"
            data-staff-id="${escapeHtml(id)}"
        >

            <div class="staff-card-header">

                <div class="staff-avatar">

                    ${
                        avatar
                            ? `
                                <img
                                    src="${escapeHtml(avatar)}"
                                    alt="${escapeHtml(name)}"
                                >
                            `
                            : `
                                <span>
                                    ${escapeHtml(
                                        getInitials(
                                            name
                                        )
                                    )}
                                </span>
                            `
                    }

                </div>


                <div class="staff-card-title">

                    <h3>
                        ${escapeHtml(name)}
                    </h3>

                    <p>
                        ${escapeHtml(
                            formatRole(
                                role
                            )
                        )}
                    </p>

                </div>


                <span
                    class="status status-${escapeHtml(status)}"
                >
                    ${escapeHtml(
                        formatStatus(
                            status
                        )
                    )}
                </span>

            </div>


            <div class="staff-card-body">

                <p>
                    <strong>
                        Department:
                    </strong>

                    ${escapeHtml(
                        formatDepartment(
                            department
                        )
                    )}
                </p>


                <p>
                    <strong>
                        Email:
                    </strong>

                    ${escapeHtml(
                        getStaffEmail(
                            staff
                        ) || "-"
                    )}
                </p>


                <p>
                    <strong>
                        Phone:
                    </strong>

                    ${escapeHtml(
                        getStaffPhone(
                            staff
                        ) || "-"
                    )}
                </p>


                ${
                    getStaffHireDate(staff)
                        ? `
                            <p>
                                <strong>
                                    Hire Date:
                                </strong>

                                ${escapeHtml(
                                    formatDate(
                                        getStaffHireDate(
                                            staff
                                        )
                                    )
                                )}
                            </p>
                        `
                        : ""
                }

            </div>


            <div class="staff-card-actions">

                <button
                    type="button"
                    class="btn btn-primary"
                    data-action="view-staff"
                    data-staff-id="${escapeHtml(id)}"
                >
                    View
                </button>


                <button
                    type="button"
                    class="btn btn-secondary"
                    data-action="edit-staff"
                    data-staff-id="${escapeHtml(id)}"
                >
                    Edit
                </button>


                ${
                    status === "active"
                        ? `
                            <button
                                type="button"
                                class="btn btn-warning"
                                data-action="deactivate-staff"
                                data-staff-id="${escapeHtml(id)}"
                            >
                                Deactivate
                            </button>
                        `
                        : `
                            <button
                                type="button"
                                class="btn btn-success"
                                data-action="activate-staff"
                                data-staff-id="${escapeHtml(id)}"
                            >
                                Activate
                            </button>
                        `
                }

            </div>

        </article>
    `;

}


/* =========================================================
   STAFF TABLE
========================================================= */

function renderStaffTable() {

    const tbody =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.staffTableBody
        );


    if (!tbody) {
        return;
    }


    const staff =
        staffState.filteredStaff;


    if (!staff.length) {

        tbody.innerHTML = `
            <tr>

                <td
                    colspan="9"
                    class="empty-state"
                >
                    No staff members found.
                </td>

            </tr>
        `;

        return;

    }


    tbody.innerHTML =
        staff
            .map(
                member =>
                    createStaffTableRow(
                        member
                    )
            )
            .join("");

}


/* =========================================================
   STAFF TABLE ROW
========================================================= */

function createStaffTableRow(
    staff
) {

    const id =
        getStaffId(
            staff
        );


    const status =
        getStaffStatus(
            staff
        );


    return `
        <tr
            data-staff-id="${escapeHtml(id)}"
        >

            <td>

                <div class="staff-table-user">

                    <div class="staff-avatar small">

                        <span>
                            ${escapeHtml(
                                getInitials(
                                    getStaffName(
                                        staff
                                    )
                                )
                            )}
                        </span>

                    </div>


                    <div>

                        <strong>
                            ${escapeHtml(
                                getStaffName(
                                    staff
                                )
                            )}
                        </strong>

                        ${
                            getStaffUsername(
                                staff
                            )
                                ? `
                                    <small>
                                        @${escapeHtml(
                                            getStaffUsername(
                                                staff
                                            )
                                        )}
                                    </small>
                                `
                                : ""
                        }

                    </div>

                </div>

            </td>


            <td>
                ${escapeHtml(
                    getStaffEmail(
                        staff
                    ) || "-"
                )}
            </td>


            <td>
                ${escapeHtml(
                    getStaffPhone(
                        staff
                    ) || "-"
                )}
            </td>


            <td>
                ${escapeHtml(
                    formatRole(
                        getStaffRole(
                            staff
                        )
                    )
                )}
            </td>


            <td>
                ${escapeHtml(
                    formatDepartment(
                        getStaffDepartment(
                            staff
                        )
                    )
                )}
            </td>


            <td>
                ${escapeHtml(
                    formatDate(
                        getStaffHireDate(
                            staff
                        )
                    )
                )}
            </td>


            <td>

                <span
                    class="status status-${escapeHtml(status)}"
                >
                    ${escapeHtml(
                        formatStatus(
                            status
                        )
                    )}
                </span>

            </td>


            <td>

                <button
                    type="button"
                    class="btn btn-sm"
                    data-action="view-staff"
                    data-staff-id="${escapeHtml(id)}"
                >
                    View
                </button>


                <button
                    type="button"
                    class="btn btn-sm"
                    data-action="edit-staff"
                    data-staff-id="${escapeHtml(id)}"
                >
                    Edit
                </button>


                ${
                    status === "active"
                        ? `
                            <button
                                type="button"
                                class="btn btn-sm btn-warning"
                                data-action="deactivate-staff"
                                data-staff-id="${escapeHtml(id)}"
                            >
                                Deactivate
                            </button>
                        `
                        : `
                            <button
                                type="button"
                                class="btn btn-sm btn-success"
                                data-action="activate-staff"
                                data-staff-id="${escapeHtml(id)}"
                            >
                                Activate
                            </button>
                        `
                }


                <button
                    type="button"
                    class="btn btn-sm btn-danger"
                    data-action="delete-staff"
                    data-staff-id="${escapeHtml(id)}"
                >
                    Delete
                </button>

            </td>

        </tr>
    `;

}


/* =========================================================
   SEARCH AND FILTER
========================================================= */

function debounceStaffSearch() {

    let timeout;


    return () => {

        clearTimeout(
            timeout
        );


        timeout =
            setTimeout(
                filterStaff,
                300
            );

    };

}


function filterStaff() {

    const search =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.searchInput
        )?.value
            ?.trim()
            .toLowerCase() || "";


    const status =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.statusFilter
        )?.value
            ?.trim()
            .toLowerCase() || "";


    const role =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.roleFilter
        )?.value
            ?.trim()
            .toLowerCase() || "";


    const department =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.departmentFilter
        )?.value
            ?.trim()
            .toLowerCase() || "";


    staffState.filteredStaff =
        staffState.staff.filter(
            member => {

                const name =
                    getStaffName(
                        member
                    ).toLowerCase();


                const email =
                    getStaffEmail(
                        member
                    ).toLowerCase();


                const phone =
                    getStaffPhone(
                        member
                    ).toLowerCase();


                const username =
                    getStaffUsername(
                        member
                    ).toLowerCase();


                const memberRole =
                    getStaffRole(
                        member
                    ).toLowerCase();


                const memberDepartment =
                    getStaffDepartment(
                        member
                    ).toLowerCase();


                const memberStatus =
                    getStaffStatus(
                        member
                    ).toLowerCase();


                const matchesSearch =
                    !search ||
                    name.includes(search) ||
                    email.includes(search) ||
                    phone.includes(search) ||
                    username.includes(search);


                const matchesStatus =
                    !status ||
                    memberStatus === status;


                const matchesRole =
                    !role ||
                    memberRole === role;


                const matchesDepartment =
                    !department ||
                    memberDepartment === department;


                return (
                    matchesSearch &&
                    matchesStatus &&
                    matchesRole &&
                    matchesDepartment
                );

            }
        );


    renderStaff();

}


/* =========================================================
   GET STAFF
========================================================= */

async function getStaff(
    staffId
) {

    if (!staffId) {

        throw new Error(
            "Staff ID is required."
        );

    }


    try {

        if (
            typeof HotelAPI.getStaffMember ===
            "function"
        ) {

            const response =
                await HotelAPI.getStaffMember(
                    staffId
                );


            const member =
                response?.data ??
                response;


            staffState.selectedStaff =
                member;


            return member;

        }


        /*
         * Fallback to the locally loaded staff list.
         */
        const member =
            staffState.staff.find(
                item =>
                    String(
                        getStaffId(
                            item
                        )
                    ) ===
                    String(staffId)
            );


        if (!member) {

            throw new Error(
                "Staff member not found."
            );

        }


        staffState.selectedStaff =
            member;


        return member;

    } catch (error) {

        console.error(
            "Failed to get staff member:",
            error
        );


        throw error;

    }

}


/* =========================================================
   CREATE STAFF
========================================================= */

async function createStaff(
    data
) {

    const validation =
        validateStaffData(
            data
        );


    if (!validation.valid) {

        throw new Error(
            validation.message
        );

    }


    try {

        if (
            typeof HotelAPI.createStaff !==
            "function"
        ) {

            throw new Error(
                "HotelAPI.createStaff() is not available."
            );

        }


        const response =
            await HotelAPI.createStaff(
                sanitizeStaffData(
                    data
                )
            );


        showStaffSuccess(
            "Staff member created successfully."
        );


        await loadStaff();


        return response;

    } catch (error) {

        console.error(
            "Failed to create staff:",
            error
        );


        showStaffError(
            error
        );


        throw error;

    }

}


/* =========================================================
   UPDATE STAFF
========================================================= */

async function updateStaff(
    staffId,
    data
) {

    if (!staffId) {

        throw new Error(
            "Staff ID is required."
        );

    }


    const validation =
        validateStaffData(
            data,
            true
        );


    if (!validation.valid) {

        throw new Error(
            validation.message
        );

    }


    try {

        if (
            typeof HotelAPI.updateStaff !==
            "function"
        ) {

            throw new Error(
                "HotelAPI.updateStaff() is not available."
            );

        }


        const response =
            await HotelAPI.updateStaff(
                staffId,
                sanitizeStaffData(
                    data,
                    true
                )
            );


        showStaffSuccess(
            "Staff member updated successfully."
        );


        await loadStaff();


        return response;

    } catch (error) {

        console.error(
            "Failed to update staff:",
            error
        );


        showStaffError(
            error
        );


        throw error;

    }

}


/* =========================================================
   DELETE STAFF
========================================================= */

async function deleteStaff(
    staffId
) {

    if (!staffId) {

        throw new Error(
            "Staff ID is required."
        );

    }


    const member =
        await getStaff(
            staffId
        );


    const confirmed =
        window.confirm(
            `Are you sure you want to delete ${getStaffName(
                member
            )}? This action cannot be easily undone.`
        );


    if (!confirmed) {

        return null;

    }


    try {

        if (
            typeof HotelAPI.deleteStaff !==
            "function"
        ) {

            /*
             * Safer fallback: deactivate the
             * staff member instead of deleting.
             */
            return deactivateStaff(
                staffId
            );

        }


        const response =
            await HotelAPI.deleteStaff(
                staffId
            );


        showStaffSuccess(
            "Staff member deleted successfully."
        );


        await loadStaff();


        return response;

    } catch (error) {

        console.error(
            "Failed to delete staff:",
            error
        );


        showStaffError(
            error
        );


        throw error;

    }

}


/* =========================================================
   ACTIVATE STAFF
========================================================= */

async function activateStaff(
    staffId
) {

    return updateStaffStatus(
        staffId,
        "active"
    );

}


/* =========================================================
   DEACTIVATE STAFF
========================================================= */

async function deactivateStaff(
    staffId
) {

    const member =
        await getStaff(
            staffId
        );


    const confirmed =
        window.confirm(
            `Deactivate ${getStaffName(
                member
            )}?`
        );


    if (!confirmed) {

        return null;

    }


    return updateStaffStatus(
        staffId,
        "inactive"
    );

}


/* =========================================================
   SUSPEND STAFF
========================================================= */

async function suspendStaff(
    staffId
) {

    return updateStaffStatus(
        staffId,
        "suspended"
    );

}


/* =========================================================
   UPDATE STAFF STATUS
========================================================= */

async function updateStaffStatus(
    staffId,
    status
) {

    if (!staffId) {

        throw new Error(
            "Staff ID is required."
        );

    }


    if (
        !STAFF_CONFIG.STATUSES.includes(
            status
        )
    ) {

        throw new Error(
            "Invalid staff status."
        );

    }


    try {

        let response;


        if (
            typeof HotelAPI.updateStaffStatus ===
            "function"
        ) {

            response =
                await HotelAPI.updateStaffStatus(
                    staffId,
                    status
                );

        } else {

            response =
                await HotelAPI.updateStaff(
                    staffId,
                    {
                        status
                    }
                );

        }


        showStaffSuccess(
            `Staff member ${formatStatus(status).toLowerCase()}.`
        );


        await loadStaff();


        return response;

    } catch (error) {

        console.error(
            "Failed to update staff status:",
            error
        );


        showStaffError(
            error
        );


        throw error;

    }

}


/* =========================================================
   CHANGE ROLE
========================================================= */

async function changeStaffRole(
    staffId,
    role
) {

    if (!staffId) {

        throw new Error(
            "Staff ID is required."
        );

    }


    if (
        !STAFF_CONFIG.ROLES.includes(
            role
        )
    ) {

        throw new Error(
            "Invalid staff role."
        );

    }


    try {

        const response =
            await HotelAPI.updateStaff(
                staffId,
                {
                    role
                }
            );


        showStaffSuccess(
            "Staff role updated successfully."
        );


        await loadStaff();


        return response;

    } catch (error) {

        console.error(
            "Failed to change staff role:",
            error
        );


        showStaffError(
            error
        );


        throw error;

    }

}


/* =========================================================
   CHANGE DEPARTMENT
========================================================= */

async function changeStaffDepartment(
    staffId,
    department
) {

    if (!staffId) {

        throw new Error(
            "Staff ID is required."
        );

    }


    if (
        !STAFF_CONFIG.DEPARTMENTS.includes(
            department
        )
    ) {

        throw new Error(
            "Invalid department."
        );

    }


    try {

        const response =
            await HotelAPI.updateStaff(
                staffId,
                {
                    department
                }
            );


        showStaffSuccess(
            "Staff department updated successfully."
        );


        await loadStaff();


        return response;

    } catch (error) {

        console.error(
            "Failed to change department:",
            error
        );


        showStaffError(
            error
        );


        throw error;

    }

}


/* =========================================================
   STAFF FORM
========================================================= */

function openStaffForm(
    member = null
) {

    const form =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.staffForm
        );


    if (!form) {
        return;
    }


    staffState.selectedStaff =
        member;


    staffState.editing =
        Boolean(member);


    form.reset();


    setField(
        STAFF_CONFIG.SELECTORS.staffId,
        getStaffId(
            member
        )
    );


    setField(
        STAFF_CONFIG.SELECTORS.firstName,
        member?.first_name ||
        member?.firstName ||
        ""
    );


    setField(
        STAFF_CONFIG.SELECTORS.lastName,
        member?.last_name ||
        member?.lastName ||
        ""
    );


    setField(
        STAFF_CONFIG.SELECTORS.email,
        getStaffEmail(
            member
        )
    );


    setField(
        STAFF_CONFIG.SELECTORS.phone,
        getStaffPhone(
            member
        )
    );


    setField(
        STAFF_CONFIG.SELECTORS.role,
        getStaffRole(
            member
        ) || "staff"
    );


    setField(
        STAFF_CONFIG.SELECTORS.department,
        getStaffDepartment(
            member
        )
    );


    setField(
        STAFF_CONFIG.SELECTORS.status,
        getStaffStatus(
            member
        ) || "active"
    );


    setField(
        STAFF_CONFIG.SELECTORS.hireDate,
        getStaffHireDate(
            member
        )
    );


    setField(
        STAFF_CONFIG.SELECTORS.address,
        member?.address ||
        ""
    );


    setField(
        STAFF_CONFIG.SELECTORS.salary,
        member?.salary ||
        ""
    );


    setField(
        STAFF_CONFIG.SELECTORS.username,
        getStaffUsername(
            member
        )
    );


    setField(
        STAFF_CONFIG.SELECTORS.password,
        ""
    );


    setField(
        STAFF_CONFIG.SELECTORS.notes,
        member?.notes ||
        ""
    );


    updateStaffFormTitle();


    if (window.HotelApp) {

        HotelApp.openModal(
            "staffModal"
        );

    }

}


/* =========================================================
   EDIT STAFF
========================================================= */

async function editStaff(
    staffId
) {

    try {

        const member =
            await getStaff(
                staffId
            );


        openStaffForm(
            member
        );

    } catch (error) {

        showStaffError(
            error
        );

    }

}


/* =========================================================
   STAFF FORM DATA
========================================================= */

function getStaffFormData(
    form
) {

    const formData =
        new FormData(
            form
        );


    const data = {

        id:
            formData.get("id") ||
            formData.get("staff_id") ||
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


        role:
            String(
                formData.get(
                    "role"
                ) || "staff"
            ).trim(),


        department:
            String(
                formData.get(
                    "department"
                ) || ""
            ).trim(),


        status:
            String(
                formData.get(
                    "status"
                ) || "active"
            ).trim(),


        hire_date:
            String(
                formData.get(
                    "hire_date"
                ) || ""
            ).trim(),


        address:
            String(
                formData.get(
                    "address"
                ) || ""
            ).trim(),


        salary:
            formData.get(
                "salary"
            )
                ? Number(
                    formData.get(
                        "salary"
                    )
                )
                : null,


        username:
            String(
                formData.get(
                    "username"
                ) || ""
            ).trim(),


        password:
            String(
                formData.get(
                    "password"
                ) || ""
            ),


        notes:
            String(
                formData.get(
                    "notes"
                ) || ""
            ).trim()

    };


    return data;

}


/* =========================================================
   FORM SUBMIT
========================================================= */

async function handleStaffFormSubmit(
    event
) {

    event.preventDefault();


    const form =
        event.currentTarget;


    const data =
        getStaffFormData(
            form
        );


    const validation =
        validateStaffData(
            data,
            Boolean(data.id)
        );


    if (!validation.valid) {

        showStaffError(
            new Error(
                validation.message
            )
        );


        return;

    }


    try {

        setStaffFormLoading(
            true
        );


        let response;


        if (data.id) {

            response =
                await updateStaff(
                    data.id,
                    data
                );

        } else {

            response =
                await createStaff(
                    data
                );

        }


        closeStaffForm();


        return response;

    } catch (error) {

        /*
         * createStaff/updateStaff already
         * display the error.
         */
        console.error(
            "Staff form submission failed:",
            error
        );

    } finally {

        setStaffFormLoading(
            false
        );

    }

}


/* =========================================================
   STAFF VALIDATION
========================================================= */

function validateStaffData(
    data,
    isUpdate = false
) {

    if (
        !data.first_name
    ) {

        return {
            valid: false,
            message:
                "First name is required."
        };

    }


    if (
        !data.last_name
    ) {

        return {
            valid: false,
            message:
                "Last name is required."
        };

    }


    if (
        !data.email
    ) {

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
        !isValidPhone(
            data.phone
        )
    ) {

        return {
            valid: false,
            message:
                "Please enter a valid phone number."
        };

    }


    if (
        !STAFF_CONFIG.ROLES.includes(
            data.role
        )
    ) {

        return {
            valid: false,
            message:
                "Please select a valid staff role."
        };

    }


    if (
        data.department &&
        !STAFF_CONFIG.DEPARTMENTS.includes(
            data.department
        )
    ) {

        return {
            valid: false,
            message:
                "Please select a valid department."
        };

    }


    if (
        !STAFF_CONFIG.STATUSES.includes(
            data.status
        )
    ) {

        return {
            valid: false,
            message:
                "Please select a valid staff status."
        };

    }


    if (
        data.salary !== null &&
        data.salary !== undefined &&
        (
            Number.isNaN(
                Number(data.salary)
            ) ||
            Number(data.salary) < 0
        )
    ) {

        return {
            valid: false,
            message:
                "Salary must be a valid non-negative number."
        };

    }


    /*
     * Password is required when creating a
     * new staff account, if your staff records
     * also create login accounts.
     */
    if (
        !isUpdate &&
        data.password &&
        data.password.length < 6
    ) {

        return {
            valid: false,
            message:
                "Password must contain at least 6 characters."
        };

    }


    return {
        valid: true,
        message: ""
    };

}


/* =========================================================
   SANITIZE STAFF DATA
========================================================= */

function sanitizeStaffData(
    data,
    isUpdate = false
) {

    const clean =
        {
            first_name:
                data.first_name,

            last_name:
                data.last_name,

            email:
                data.email,

            phone:
                data.phone,

            role:
                data.role,

            department:
                data.department,

            status:
                data.status,

            hire_date:
                data.hire_date,

            address:
                data.address,

            salary:
                data.salary,

            username:
                data.username,

            notes:
                data.notes
        };


    /*
     * Never send an empty password during
     * an update. This prevents accidentally
     * overwriting an existing password.
     */
    if (
        data.password &&
        data.password.trim()
    ) {

        clean.password =
            data.password;

    }


    return clean;

}


/* =========================================================
   STAFF DETAILS
========================================================= */

async function viewStaff(
    staffId
) {

    try {

        const member =
            await getStaff(
                staffId
            );


        showStaffDetails(
            member
        );


    } catch (error) {

        showStaffError(
            error
        );

    }

}


function showStaffDetails(
    member
) {

    const modal =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.staffDetailsModal
        );


    if (!modal) {

        /*
         * If no modal exists, navigate to
         * the staff page/details page.
         */
        window.location.href =
            `staff-details.html?id=${encodeURIComponent(
                getStaffId(
                    member
                )
            )}`;

        return;

    }


    const values = {

        "#detailsStaffName":
            getStaffName(
                member
            ),

        "#detailsStaffEmail":
            getStaffEmail(
                member
            ) || "-",

        "#detailsStaffPhone":
            getStaffPhone(
                member
            ) || "-",

        "#detailsStaffRole":
            formatRole(
                getStaffRole(
                    member
                )
            ),

        "#detailsStaffDepartment":
            formatDepartment(
                getStaffDepartment(
                    member
                )
            ),

        "#detailsStaffStatus":
            formatStatus(
                getStaffStatus(
                    member
                )
            ),

        "#detailsStaffHireDate":
            formatDate(
                getStaffHireDate(
                    member
                )
            ),

        "#detailsStaffUsername":
            getStaffUsername(
                member
            ) || "-",

        "#detailsStaffAddress":
            member.address ||
            "-",

        "#detailsStaffSalary":
            member.salary !== undefined &&
            member.salary !== null
                ? formatCurrency(
                    member.salary
                )
                : "-",

        "#detailsStaffNotes":
            member.notes ||
            "-"

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
            "staffDetailsModal"
        );

    }

}


/* =========================================================
   STAFF ACTION HANDLER
========================================================= */

function handleStaffActions(
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


    const staffId =
        button.dataset.staffId;


    if (!staffId) {
        return;
    }


    switch (action) {

        case "view-staff":

            viewStaff(
                staffId
            );

            break;


        case "edit-staff":

            editStaff(
                staffId
            );

            break;


        case "delete-staff":

            deleteStaff(
                staffId
            );

            break;


        case "activate-staff":

            activateStaff(
                staffId
            );

            break;


        case "deactivate-staff":

            deactivateStaff(
                staffId
            );

            break;


        case "suspend-staff":

            suspendStaff(
                staffId
            );

            break;


        default:

            break;

    }

}


/* =========================================================
   STAFF STATISTICS
========================================================= */

function updateStaffStatistics() {

    const staff =
        staffState.staff;


    const total =
        staff.length;


    const active =
        staff.filter(
            member =>
                getStaffStatus(
                    member
                ) === "active"
        ).length;


    const inactive =
        staff.filter(
            member =>
                [
                    "inactive",
                    "suspended",
                    "terminated"
                ].includes(
                    getStaffStatus(
                        member
                    )
                )
        ).length;


    const managers =
        staff.filter(
            member =>
                getStaffRole(
                    member
                ) === "manager"
        ).length;


    setText(
        STAFF_CONFIG.SELECTORS.totalStaff,
        total
    );


    setText(
        STAFF_CONFIG.SELECTORS.activeStaff,
        active
    );


    setText(
        STAFF_CONFIG.SELECTORS.inactiveStaff,
        inactive
    );


    setText(
        STAFF_CONFIG.SELECTORS.managers,
        managers
    );

}


/* =========================================================
   FORM UI
========================================================= */

function updateStaffFormTitle() {

    const title =
        document.querySelector(
            "#staffModalTitle"
        );


    if (!title) {
        return;
    }


    title.textContent =
        staffState.editing
            ? "Edit Staff Member"
            : "Add Staff Member";

}


function setStaffFormLoading(
    loading
) {

    const button =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.saveButton
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
            staffState.editing
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


function closeStaffForm() {

    const form =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.staffForm
        );


    if (form) {

        form.reset();

    }


    staffState.selectedStaff =
        null;


    staffState.editing =
        false;


    if (window.HotelApp) {

        HotelApp.closeModal(
            "staffModal"
        );

    }

}


/* =========================================================
   STAFF GETTERS
========================================================= */

function getStaffId(
    member
) {

    return (
        member?.id ??
        member?.staff_id ??
        ""
    );

}


function getStaffName(
    member
) {

    if (!member) {
        return "Unknown Staff";
    }


    if (member.name) {

        return member.name;

    }


    const firstName =
        member.first_name ||
        member.firstName ||
        "";


    const lastName =
        member.last_name ||
        member.lastName ||
        "";


    return (
        `${firstName} ${lastName}`.trim() ||
        "Unknown Staff"
    );

}


function getStaffEmail(
    member
) {

    return String(
        member?.email ||
        member?.user?.email ||
        ""
    );

}


function getStaffPhone(
    member
) {

    return String(
        member?.phone ||
        member?.phone_number ||
        member?.user?.phone ||
        ""
    );

}


function getStaffRole(
    member
) {

    return String(
        member?.role ||
        member?.position ||
        member?.user?.role ||
        "staff"
    ).toLowerCase();

}


function getStaffDepartment(
    member
) {

    return String(
        member?.department ||
        member?.department_name ||
        ""
    ).toLowerCase();

}


function getStaffStatus(
    member
) {

    return String(
        member?.status ||
        member?.employment_status ||
        "active"
    ).toLowerCase();

}


function getStaffHireDate(
    member
) {

    return (
        member?.hire_date ||
        member?.hireDate ||
        member?.date_hired ||
        ""
    );

}


function getStaffUsername(
    member
) {

    return String(
        member?.username ||
        member?.user?.username ||
        ""
    );

}


function getStaffAvatar(
    member
) {

    return (
        member?.avatar ||
        member?.profile_image ||
        member?.profileImage ||
        member?.user?.avatar ||
        ""
    );

}


/* =========================================================
   FORMATTING
========================================================= */

function formatRole(
    role
) {

    if (!role) {
        return "-";
    }


    return String(role)
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


function formatDepartment(
    department
) {

    if (!department) {
        return "-";
    }


    return String(department)
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


function formatStatus(
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


function getInitials(
    name
) {

    if (!name) {
        return "ST";
    }


    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map(
            word =>
                word
                    .charAt(0)
                    .toUpperCase()
        )
        .join("");

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


    return new Intl.NumberFormat(
        undefined,
        {
            style: "currency",
            currency: "USD"
        }
    ).format(amount);

}


/* =========================================================
   VALIDATION HELPERS
========================================================= */

function isValidEmail(
    email
) {

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        .test(
            String(email)
        );

}


function isValidPhone(
    phone
) {

    /*
     * Allows international numbers,
     * spaces, parentheses, hyphens and
     * a leading plus sign.
     */
    return /^\+?[0-9\s().-]{7,20}$/
        .test(
            String(phone)
        );

}


/* =========================================================
   GENERAL DOM HELPERS
========================================================= */

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

function showStaffLoading() {

    const container =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.staffContainer
        );


    const tbody =
        document.querySelector(
            STAFF_CONFIG.SELECTORS.staffTableBody
        );


    if (container) {

        container.innerHTML = `
            <div class="loading-state">

                <div class="spinner"></div>

                <p>
                    Loading staff...
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
                    Loading staff...
                </td>

            </tr>
        `;

    }

}


function hideStaffLoading() {

    document.body.classList.remove(
        "staff-loading"
    );

}


function showStaffSuccess(
    message
) {

    if (window.HotelApp) {

        HotelApp.showAlert(
            message,
            "success"
        );

    }

}


function showStaffError(
    error
) {

    const message =
        error?.message ||
        "Unable to complete staff operation.";


    console.error(
        "Staff operation error:",
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
   PUBLIC STAFF API
========================================================= */

window.HotelStaff = {

    load:
        loadStaff,


    refresh:
        () =>
            loadStaff(),


    get:
        getStaff,


    create:
        createStaff,


    update:
        updateStaff,


    delete:
        deleteStaff,


    activate:
        activateStaff,


    deactivate:
        deactivateStaff,


    suspend:
        suspendStaff,


    updateStatus:
        updateStaffStatus,


    changeRole:
        changeStaffRole,


    changeDepartment:
        changeStaffDepartment,


    openForm:
        openStaffForm,


    closeForm:
        closeStaffForm,


    edit:
        editStaff,


    view:
        viewStaff,


    filter:
        filterStaff,


    getState:
        () => ({

            ...staffState,

            staff:
                [
                    ...staffState.staff
                ],

            filteredStaff:
                [
                    ...staffState.filteredStaff
                ]

        })

};
