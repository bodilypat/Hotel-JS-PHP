/**
 * Hotel Management System
 * frontend/js/validation.js
 *
 * Responsibilities:
 * - Form validation
 * - Authentication validation
 * - Guest validation
 * - Room validation
 * - Booking validation
 * - Payment validation
 * - Staff validation
 * - Inline validation messages
 * - Password validation
 * - Date validation
 * - Number validation
 * - Form submission protection
 */

"use strict";


/* VALIDATION CONFIGURATION */

const VALIDATION_CONFIG = {
    classes: {
        error:
            "field-error",
        success:
            "field-success",
        invalid:
            "is-invalid",
        valid:
            "is-valid"
    },
    selectors: {
        forms:
            "form",
        error:
            ".field-error-message"
    },
    limits: {
        nameMin:
            2,
        nameMax:
            100,
        passwordMin:
            8,
        phoneMin:
            7,
        phoneMax:
            20,
        addressMax:
            255,
        roomNumberMax:
            20,
        notesMax:
            1000
    }
};

/* VALIDATION STATE */
const validationState = {
    forms:
        new WeakMap(),
    submitting:
        new WeakSet()
};

/* INITIALIZATION */
document.addEventListener(
    "DOMContentLoaded",
    () => {
        initializeValidation();
    }
);

function initializeValidation() {

    const forms =
        document.querySelectorAll(
            VALIDATION_CONFIG.selectors.forms
        );

    forms.forEach(
        form => {
            registerForm(
                form
            );
        }
    );

    /*
     * Global delegated validation.
     */
    document.addEventListener(
        "blur",
        handleFieldBlur,
        true
    );
    document.addEventListener(
        "input",
        handleFieldInput,
        true
    );

}

/* FORM REGISTRATION */

function registerForm(
    form
) {

    if (
        !form ||
        validationState.forms.has(form)
    ) {
        return;
    }

    const type =
        detectFormType(
            form
        );

    validationState.forms.set(
        form,
        {
            type,
            validated:
                false
        }
    );

    form.addEventListener(
        "submit",
        event =>
            handleFormSubmit(
                event,
                form
            )
    );

    /*
     * Validate fields when the user leaves them.
     */
    form.querySelectorAll(
        "input, select, textarea"
    )
        .forEach(
            field => {

                field.addEventListener(
                    "blur",
                    () => {

                        validateField(
                            field,
                            form
                        );

                    }
                );

            }
        );

}

/* FORM TYPE DETECTION */

function detectFormType(
    form
) {
    const explicitType =
        form.dataset.validation ||
        form.dataset.formType;

    if (explicitType) {
        return explicitType
            .toLowerCase();
    }

    const id =
        (
            form.id ||
            ""
        )
            .toLowerCase();
    const action =
        (
            form.action ||
            ""
        )
            .toLowerCase();
    const className =
        (
            form.className ||
            ""
        )
            .toLowerCase();
    const source =
        `${id} ${action} ${className}`;

    if (
        source.includes("login")
    ) {
        return "login";
    }

    if (
        source.includes("register") ||
        source.includes("signup")
    ) {
        return "register";
    }

    if (
        source.includes("forgot") ||
        source.includes("reset")
    ) {
        return "forgot-password";

    }

    if (
        source.includes("booking") ||
        source.includes("reservation")
    ) {
        return "booking";
    }

    if (
        source.includes("guest")
    ) {
        return "guest";
    }

    if (
        source.includes("room")
    ) {
        return "room";
    }

    if (
        source.includes("payment") ||
        source.includes("billing")
    ) {
        return "payment";
    }

    if (
        source.includes("staff") ||
        source.includes("employee")
    ) {
        return "staff";
    }
    return "generic";

}


/* FORM SUBMISSION */

function handleFormSubmit(
    event,
    form
) {
    const result =
        validateForm(
            form
        );

    if (!result.valid) {

        event.preventDefault();

        focusFirstInvalidField(
            form
        );
        showFormErrorSummary(
            form,
            result.errors
        );
        return false;

    }

    /*
     * Prevent accidental double submissions.
     */

    if (
        validationState.submitting.has(
            form
        )
    ) {
        event.preventDefault();
        return false;
    }
    validationState.submitting.add(
        form
    );

    /*
     * Allow API-based forms to manage their own
     * loading state.
     */
    form.dispatchEvent(
        new CustomEvent(
            "validation:passed",
            {
                bubbles:
                    true,

                detail: {
                    form,
                    type:
                        validationState.forms.get(
                            form
                        )?.type
                }
            }
        )
    );

    /*
     * Remove the lock after a short delay.
     * This prevents accidental double-clicks while
     * allowing API code to submit again if necessary.
     */
    setTimeout(
        () => {

            validationState.submitting.delete(
                form
            );

        },
        1500
    );
    return true;

}

/* VALIDATE FORM */

function validateForm(
    form
) {
    if (!form) {
        return {
            valid:
                false,

            errors: {

                form:
                    "Form not found."
            }
        };
    }

    const state =
        validationState.forms.get(
            form
        );

    const type =
        state?.type ||
        detectFormType(
            form
        );

    const errors = {};

    const fields =
        form.querySelectorAll(
            "input, select, textarea"
        );

    fields.forEach(
        field => {

            /*
             * Ignore disabled fields and submit buttons.
             */
            if (
                field.disabled ||
                [
                    "submit",
                    "button",
                    "reset"
                ].includes(
                    field.type
                )
            ) {
                return;
            }
            const result =
                validateField(
                    field,
                    form,
                    false
                );

            if (!result.valid) {
                errors[
                    getFieldName(
                        field
                    )
                ] =
                    result.message;
            }
        }
    );

    /*
     * Form-specific cross-field validation.
     */
    const crossFieldErrors =
        validateFormRules(
            form,
            type
        );
    Object.assign(
        errors,
        crossFieldErrors
    );

    if (state) {
        state.validated =
            true;
    }
    return {
        valid:
            Object.keys(
                errors
            ).length === 0,
        errors
    };

}

/* FIELD VALIDATION */

function validateField(
    field,
    form = null,
    showMessage = true
) {
    if (!field) {

        return {
            valid:
                true
        };
    }

    const activeForm =
        form ||
        field.form ||
        null;

    if (
        field.disabled ||
        [
            "submit",
            "button",
            "reset"
        ].includes(
            field.type
        )
    ) {

        return {
            valid:
                true

        };
    }

    const value =
        getFieldValue(
            field
        );

    if (
        activeForm &&
        !activeForm.contains(field)
    ) {
        return {
            valid:
                false,
            message:
                "Field is not part of the active form."
        };
    }

    let result = {
        valid:
            true,
        message:
            ""
    };

    /*
     * Required validation.
     */
    if (
        field.required &&
        isEmpty(
            value
        )
    ) {
        result = {
            valid:
                false,
            message:
                getRequiredMessage(
                    field
                )
        };
    }


    /*
     * Don't run format validation on an
     * optional empty field.
     */
    if (
        result.valid &&
        !isEmpty(value)
    ) {

        result =
            validateFieldType(
                field,
                value
            );

    }


    if (showMessage) {

        setFieldValidationState(
            field,
            result
        );

    }


    return result;

}


/* =========================================================
   FIELD TYPE VALIDATION
========================================================= */

function validateFieldType(
    field,
    value
) {

    const name =
        getFieldName(
            field
        )
            .toLowerCase();


    const type =
        (
            field.type ||
            ""
        )
            .toLowerCase();


    /*
     * Email.
     */
    if (
        type === "email" ||
        name.includes("email")
    ) {

        return validateEmail(
            value
        );

    }


    /*
     * Password.
     */
    if (
        type === "password" ||
        name.includes("password")
    ) {

        return validatePassword(
            value,
            field
        );

    }


    /*
     * Phone.
     */
    if (
        name.includes("phone") ||
        name.includes("mobile") ||
        name.includes("contact")
    ) {

        return validatePhone(
            value
        );

    }


    /*
     * Name.
     */
    if (
        name === "name" ||
        name.includes("full_name") ||
        name.includes("first_name") ||
        name.includes("last_name") ||
        name.includes("guest_name")
    ) {

        return validateName(
            value
        );

    }


    /*
     * Date.
     */
    if (
        type === "date" ||
        name.includes("date")
    ) {

        return validateDate(
            value
        );

    }


    /*
     * URL.
     */
    if (
        type === "url" ||
        name.includes("website") ||
        name.includes("url")
    ) {

        return validateURL(
            value
        );

    }


    /*
     * Numeric fields.
     */
    if (
        type === "number" ||
        name.includes("price") ||
        name.includes("rate") ||
        name.includes("amount") ||
        name.includes("quantity") ||
        name.includes("guests") ||
        name.includes("adults") ||
        name.includes("children")
    ) {

        return validateNumber(
            field,
            value
        );

    }


    /*
     * Room number.
     */
    if (
        name.includes("room_number") ||
        name === "room"
    ) {

        return validateRoomNumber(
            value
        );

    }


    /*
     * Card number.
     */
    if (
        name.includes("card_number") ||
        name === "card"
    ) {

        return validateCardNumber(
            value
        );

    }


    /*
     * CVV.
     */
    if (
        name === "cvv" ||
        name.includes("security_code")
    ) {

        return validateCVV(
            value
        );

    }


    /*
     * Generic length rules.
     */
    return validateGenericField(
        field,
        value
    );

}


/* =========================================================
   AUTHENTICATION VALIDATION
========================================================= */

function validateLoginForm(
    form
) {

    const errors = {};


    const email =
        getField(
            form,
            [
                "email",
                "username"
            ]
        );


    const password =
        getField(
            form,
            [
                "password"
            ]
        );


    if (
        email &&
        isEmpty(
            getFieldValue(email)
        )
    ) {

        errors.email =
            "Email or username is required.";

    }


    if (
        password &&
        isEmpty(
            getFieldValue(password)
        )
    ) {

        errors.password =
            "Password is required.";

    }


    return errors;

}


function validateRegisterForm(
    form
) {

    const errors = {};


    const password =
        getField(
            form,
            [
                "password"
            ]
        );


    const confirmPassword =
        getField(
            form,
            [
                "confirm_password",
                "password_confirmation"
            ]
        );


    if (
        password &&
        confirmPassword &&
        getFieldValue(password) !==
        getFieldValue(confirmPassword)
    ) {

        errors.confirm_password =
            "Passwords do not match.";

        setFieldValidationState(
            confirmPassword,
            {
                valid:
                    false,

                message:
                    errors.confirm_password
            }
        );

    }


    const terms =
        getField(
            form,
            [
                "terms",
                "agree",
                "agreement"
            ]
        );


    if (
        terms &&
        terms.type === "checkbox" &&
        !terms.checked
    ) {

        errors.terms =
            "You must accept the terms and conditions.";

        setFieldValidationState(
            terms,
            {
                valid:
                    false,

                message:
                    errors.terms
            }
        );

    }


    return errors;

}


function validateForgotPasswordForm(
    form
) {

    const errors = {};


    const email =
        getField(
            form,
            [
                "email"
            ]
        );


    if (
        email &&
        !isEmpty(
            getFieldValue(email)
        )
    ) {

        const result =
            validateEmail(
                getFieldValue(
                    email
                )
            );


        if (!result.valid) {

            errors.email =
                result.message;

        }

    }


    return errors;

}


/* =========================================================
   GUEST VALIDATION
========================================================= */

function validateGuestForm(
    form
) {

    const errors = {};


    const email =
        getField(
            form,
            [
                "email"
            ]
        );


    const phone =
        getField(
            form,
            [
                "phone",
                "mobile"
            ]
        );


    const idNumber =
        getField(
            form,
            [
                "id_number",
                "identity_number",
                "passport"
            ]
        );


    if (
        email &&
        !isEmpty(
            getFieldValue(email)
        )
    ) {

        const result =
            validateEmail(
                getFieldValue(email)
            );


        if (!result.valid) {

            errors.email =
                result.message;

        }

    }


    if (
        phone &&
        !isEmpty(
            getFieldValue(phone)
        )
    ) {

        const result =
            validatePhone(
                getFieldValue(phone)
            );


        if (!result.valid) {

            errors.phone =
                result.message;

        }

    }


    if (
        idNumber &&
        !isEmpty(
            getFieldValue(idNumber)
        )
    ) {

        if (
            getFieldValue(idNumber)
                .length < 4
        ) {

            errors.id_number =
                "Identification number is too short.";

        }

    }


    return errors;

}


/* =========================================================
   ROOM VALIDATION
========================================================= */

function validateRoomForm(
    form
) {

    const errors = {};


    const roomNumber =
        getField(
            form,
            [
                "room_number",
                "room"
            ]
        );


    const roomType =
        getField(
            form,
            [
                "room_type",
                "type"
            ]
        );


    const rate =
        getField(
            form,
            [
                "rate",
                "price",
                "room_rate"
            ]
        );


    if (
        roomNumber &&
        !isEmpty(
            getFieldValue(roomNumber)
        )
    ) {

        const result =
            validateRoomNumber(
                getFieldValue(
                    roomNumber
                )
            );


        if (!result.valid) {

            errors.room_number =
                result.message;

        }

    }


    if (
        roomType &&
        isEmpty(
            getFieldValue(roomType)
        )
    ) {

        errors.room_type =
            "Please select a room type.";

    }


    if (
        rate &&
        !isEmpty(
            getFieldValue(rate)
        )
    ) {

        const result =
            validateNumber(
                rate,
                getFieldValue(rate)
            );


        if (!result.valid) {

            errors.rate =
                result.message;

        }

    }


    return errors;

}


/* =========================================================
   BOOKING VALIDATION
========================================================= */

function validateBookingForm(
    form
) {

    const errors = {};


    const checkIn =
        getField(
            form,
            [
                "check_in",
                "checkin",
                "check_in_date"
            ]
        );


    const checkOut =
        getField(
            form,
            [
                "check_out",
                "checkout",
                "check_out_date"
            ]
        );


    const guests =
        getField(
            form,
            [
                "guests",
                "guest_count",
                "number_of_guests"
            ]
        );


    if (
        checkIn &&
        checkOut &&
        !isEmpty(
            getFieldValue(checkIn)
        ) &&
        !isEmpty(
            getFieldValue(checkOut)
        )
    ) {

        const start =
            new Date(
                getFieldValue(checkIn)
            );


        const end =
            new Date(
                getFieldValue(checkOut)
            );


        if (
            Number.isNaN(
                start.getTime()
            ) ||
            Number.isNaN(
                end.getTime()
            )
        ) {

            errors.check_in =
                "Please enter valid booking dates.";

        } else if (
            end <= start
        ) {

            errors.check_out =
                "Check-out date must be after check-in date.";

        }

    }


    /*
     * Check-in cannot be in the past unless
     * the form explicitly allows it.
     */
    if (
        checkIn &&
        !isEmpty(
            getFieldValue(checkIn)
        ) &&
        form.dataset.allowPastDates !==
        "true"
    ) {

        const today =
            startOfDay(
                new Date()
            );


        const checkInDate =
            startOfDay(
                new Date(
                    getFieldValue(checkIn)
                )
            );


        if (
            checkInDate < today
        ) {

            errors.check_in =
                "Check-in date cannot be in the past.";

        }

    }


    if (
        guests &&
        !isEmpty(
            getFieldValue(guests)
        )
    ) {

        const result =
            validateNumber(
                guests,
                getFieldValue(guests),
                {
                    min:
                        1,

                    max:
                        50
                }
            );


        if (!result.valid) {

            errors.guests =
                "Number of guests must be between 1 and 50.";

        }

    }


    return errors;

}


/* =========================================================
   PAYMENT VALIDATION
========================================================= */

function validatePaymentForm(
    form
) {

    const errors = {};


    const amount =
        getField(
            form,
            [
                "amount",
                "payment_amount",
                "total"
            ]
        );


    const method =
        getField(
            form,
            [
                "payment_method",
                "method"
            ]
        );


    if (
        amount &&
        !isEmpty(
            getFieldValue(amount)
        )
    ) {

        const result =
            validateNumber(
                amount,
                getFieldValue(amount),
                {
                    min:
                        0.01
                }
            );


        if (!result.valid) {

            errors.amount =
                "Payment amount must be greater than zero.";

        }

    }


    if (
        method &&
        isEmpty(
            getFieldValue(method)
        )
    ) {

        errors.payment_method =
            "Please select a payment method.";

    }


    /*
     * Validate card fields only when card payment
     * is selected.
     */
    const methodValue =
        method
            ? normalize(
                getFieldValue(method)
            )
            : "";


    if (
        methodValue.includes("card") ||
        methodValue.includes("credit") ||
        methodValue.includes("debit")
    ) {

        const card =
            getField(
                form,
                [
                    "card_number"
                ]
            );


        const cvv =
            getField(
                form,
                [
                    "cvv",
                    "security_code"
                ]
            );


        const expiry =
            getField(
                form,
                [
                    "expiry",
                    "expiry_date"
                ]
            );


        if (
            card &&
            !isEmpty(
                getFieldValue(card)
            )
        ) {

            const result =
                validateCardNumber(
                    getFieldValue(card)
                );


            if (!result.valid) {

                errors.card_number =
                    result.message;

            }

        }


        if (
            cvv &&
            !isEmpty(
                getFieldValue(cvv)
            )
        ) {

            const result =
                validateCVV(
                    getFieldValue(cvv)
                );


            if (!result.valid) {

                errors.cvv =
                    result.message;

            }

        }


        if (
            expiry &&
            !isEmpty(
                getFieldValue(expiry)
            )
        ) {

            const result =
                validateCardExpiry(
                    getFieldValue(expiry)
                );


            if (!result.valid) {

                errors.expiry =
                    result.message;

            }

        }

    }


    return errors;

}


/* =========================================================
   STAFF VALIDATION
========================================================= */

function validateStaffForm(
    form
) {

    const errors = {};


    const email =
        getField(
            form,
            [
                "email"
            ]
        );


    const phone =
        getField(
            form,
            [
                "phone",
                "mobile"
            ]
        );


    const salary =
        getField(
            form,
            [
                "salary",
                "wage"
            ]
        );


    if (
        email &&
        !isEmpty(
            getFieldValue(email)
        )
    ) {

        const result =
            validateEmail(
                getFieldValue(email)
            );


        if (!result.valid) {

            errors.email =
                result.message;

        }

    }


    if (
        phone &&
        !isEmpty(
            getFieldValue(phone)
        )
    ) {

        const result =
            validatePhone(
                getFieldValue(phone)
            );


        if (!result.valid) {

            errors.phone =
                result.message;

        }

    }


    if (
        salary &&
        !isEmpty(
            getFieldValue(salary)
        )
    ) {

        const result =
            validateNumber(
                salary,
                getFieldValue(salary),
                {
                    min:
                        0
                }
            );


        if (!result.valid) {

            errors.salary =
                result.message;

        }

    }


    return errors;

}


/* =========================================================
   CROSS-FIELD FORM RULES
========================================================= */

function validateFormRules(
    form,
    type
) {

    switch (type) {

        case "login":

            return validateLoginForm(
                form
            );


        case "register":

            return validateRegisterForm(
                form
            );


        case "forgot-password":

            return validateForgotPasswordForm(
                form
            );


        case "guest":

            return validateGuestForm(
                form
            );


        case "room":

            return validateRoomForm(
                form
            );


        case "booking":

            return validateBookingForm(
                form
            );


        case "payment":

            return validatePaymentForm(
                form
            );


        case "staff":

            return validateStaffForm(
                form
            );


        default:

            return {};

    }

}


/* =========================================================
   EMAIL
========================================================= */

function validateEmail(
    email
) {

    const value =
        String(
            email ||
            ""
        )
            .trim();


    /*
     * Practical email validation.
     */
    const pattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;


    if (!pattern.test(value)) {

        return {

            valid:
                false,

            message:
                "Please enter a valid email address."

        };

    }


    return {

        valid:
            true,

        message:
            ""

    };

}


/* =========================================================
   PASSWORD
========================================================= */

function validatePassword(
    password,
    field = null
) {

    const value =
        String(
            password ||
            ""
        );


    const min =
        Number(
            field?.dataset?.minLength ||
            VALIDATION_CONFIG.limits.passwordMin
        );


    if (
        value.length <
        min
    ) {

        return {

            valid:
                false,

            message:
                `Password must be at least ${min} characters.`

        };

    }


    /*
     * If the field explicitly requires strong passwords,
     * enforce uppercase, lowercase, number and symbol.
     */
    if (
        field?.dataset?.strongPassword ===
        "true"
    ) {

        if (
            !/[A-Z]/.test(value)
        ) {

            return {

                valid:
                    false,

                message:
                    "Password must contain an uppercase letter."

            };

        }


        if (
            !/[a-z]/.test(value)
        ) {

            return {

                valid:
                    false,

                message:
                    "Password must contain a lowercase letter."

            };

        }


        if (
            !/[0-9]/.test(value)
        ) {

            return {

                valid:
                    false,

                message:
                    "Password must contain a number."

            };

        }


        if (
            !/[^A-Za-z0-9]/.test(value)
        ) {

            return {

                valid:
                    false,

                message:
                    "Password must contain a special character."

            };

        }

    }


    return {

        valid:
            true,

        message:
            ""

    };

}


/* =========================================================
   NAME
========================================================= */

function validateName(
    name
) {

    const value =
        String(
            name ||
            ""
        )
            .trim();


    const min =
        VALIDATION_CONFIG.limits.nameMin;


    const max =
        VALIDATION_CONFIG.limits.nameMax;


    if (
        value.length <
        min
    ) {

        return {

            valid:
                false,

            message:
                `Name must contain at least ${min} characters.`

        };

    }


    if (
        value.length >
        max
    ) {

        return {

            valid:
                false,

            message:
                `Name cannot exceed ${max} characters.`

        };

    }


    /*
     * Allow letters, spaces, apostrophes,
     * periods and hyphens.
     */
    if (
        !/^[A-Za-zÀ-ÖØ-öø-ÿ\s.'-]+$/.test(
            value
        )
    ) {

        return {

            valid:
                false,

            message:
                "Name contains invalid characters."

        };

    }


    return {

        valid:
            true,

        message:
            ""

    };

}


/* =========================================================
   PHONE
========================================================= */

function validatePhone(
    phone
) {

    const value =
        String(
            phone ||
            ""
        )
            .trim();


    const digits =
        value.replace(
            /\D/g,
            ""
        );


    if (
        digits.length <
        VALIDATION_CONFIG.limits.phoneMin
    ) {

        return {

            valid:
                false,

            message:
                "Please enter a valid phone number."

        };

    }


    if (
        digits.length >
        VALIDATION_CONFIG.limits.phoneMax
    ) {

        return {

            valid:
                false,

            message:
                "Phone number is too long."

        };

    }


    if (
        !/^[+\d\s().-]+$/.test(
            value
        )
    ) {

        return {

            valid:
                false,

            message:
                "Phone number contains invalid characters."

        };

    }


    return {

        valid:
            true,

        message:
            ""

    };

}


/* =========================================================
   DATE
========================================================= */

function validateDate(
    value
) {

    const date =
        new Date(
            value
        );


    if (
        !value ||
        Number.isNaN(
            date.getTime()
        )
    ) {

        return {

            valid:
                false,

            message:
                "Please enter a valid date."

        };

    }


    return {

        valid:
            true,

        message:
            ""

    };

}


/* =========================================================
   NUMBER
========================================================= */

function validateNumber(
    field,
    value,
    options = {}
) {

    const number =
        Number(
            value
        );


    if (
        !Number.isFinite(
            number
        )
    ) {

        return {

            valid:
                false,

            message:
                "Please enter a valid number."

        };

    }


    const min =
        options.min ??
        (
            field?.min !== ""
                ? Number(field.min)
                : null
        );


    const max =
        options.max ??
        (
            field?.max !== ""
                ? Number(field.max)
                : null
        );


    if (
        min !== null &&
        Number.isFinite(min) &&
        number < min
    ) {

        return {

            valid:
                false,

            message:
                `Value must be at least ${min}.`

        };

    }


    if (
        max !== null &&
        Number.isFinite(max) &&
        number > max
    ) {

        return {

            valid:
                false,

            message:
                `Value cannot exceed ${max}.`

        };

    }


    return {

        valid:
            true,

        message:
            ""

    };

}


/* =========================================================
   ROOM NUMBER
========================================================= */

function validateRoomNumber(
    value
) {

    const room =
        String(
            value ||
            ""
        )
            .trim();


    if (!room) {

        return {

            valid:
                false,

            message:
                "Room number is required."

        };

    }


    if (
        room.length >
        VALIDATION_CONFIG.limits.roomNumberMax
    ) {

        return {

            valid:
                false,

            message:
                "Room number is too long."

        };

    }


    /*
     * Allows values such as:
     * 101
     * 205A
     * A-12
     * DELUXE-01
     */
    if (
        !/^[A-Za-z0-9][A-Za-z0-9 -]*$/.test(
            room
        )
    ) {

        return {

            valid:
                false,

            message:
                "Room number contains invalid characters."

        };

    }


    return {

        valid:
            true,

        message:
            ""

    };

}


/* =========================================================
   URL
========================================================= */

function validateURL(
    value
) {

    try {

        const url =
            new URL(
                value
            );


        if (
            ![
                "http:",
                "https:"
            ].includes(
                url.protocol
            )
        ) {

            throw new Error(
                "Invalid protocol"
            );

        }


        return {

            valid:
                true,

            message:
                ""

        };

    } catch (
        error
    ) {

        return {

            valid:
                false,

            message:
                "Please enter a valid URL."

        };

    }

}


/* =========================================================
   CREDIT/DEBIT CARD
========================================================= */

function validateCardNumber(
    value
) {

    const digits =
        String(
            value ||
            ""
        )
            .replace(
                /\D/g,
                ""
            );


    if (
        digits.length <
        13 ||
        digits.length >
        19
    ) {

        return {

            valid:
                false,

            message:
                "Please enter a valid card number."

        };

    }


    /*
     * Luhn algorithm.
     */
    let sum =
        0;


    let shouldDouble =
        false;


    for (
        let i =
            digits.length - 1;
        i >= 0;
        i--
    ) {

        let digit =
            Number(
                digits[i]
            );


        if (
            shouldDouble
        ) {

            digit *= 2;


            if (
                digit > 9
            ) {

                digit -= 9;

            }

        }


        sum +=
            digit;


        shouldDouble =
            !shouldDouble;

    }


    if (
        sum % 10 !==
        0
    ) {

        return {

            valid:
                false,

            message:
                "Card number is invalid."

        };

    }


    return {

        valid:
            true,

        message:
            ""

    };

}


/* =========================================================
   CVV
========================================================= */

function validateCVV(
    value
) {

    const cvv =
        String(
            value ||
            ""
        )
            .trim();


    if (
        !/^\d{3,4}$/.test(
            cvv
        )
    ) {

        return {

            valid:
                false,

            message:
                "CVV must contain 3 or 4 digits."

        };

    }


    return {

        valid:
            true,

        message:
            ""

    };

}


/* =========================================================
   CARD EXPIRY
========================================================= */

function validateCardExpiry(
    value
) {

    const input =
        String(
            value ||
            ""
        )
            .trim();


    let month;
    let year;


    /*
     * Supports:
     * MM/YY
     * MM/YYYY
     * YYYY-MM
     */
    let match =
        input.match(
            /^(\d{1,2})\/(\d{2}|\d{4})$/
        );


    if (match) {

        month =
            Number(
                match[1]
            );


        year =
            Number(
                match[2]
            );


        if (
            year < 100
        ) {

            year +=
                2000;

        }

    } else {

        match =
            input.match(
                /^(\d{4})-(\d{1,2})$/
            );


        if (match) {

            year =
                Number(
                    match[1]
                );


            month =
                Number(
                    match[2]
                );

        }

    }


    if (
        !month ||
        !year ||
        month < 1 ||
        month > 12
    ) {

        return {

            valid:
                false,

            message:
                "Please enter a valid expiry date."

        };

    }


    const now =
        new Date();


    const expiry =
        new Date(
            year,
            month,
            0,
            23,
            59,
            59
        );


    if (
        expiry <
        now
    ) {

        return {

            valid:
                false,

            message:
                "Card has expired."

        };

    }


    return {

        valid:
            true,

        message:
            ""

    };

}


/* =========================================================
   GENERIC FIELD VALIDATION
========================================================= */

function validateGenericField(
    field,
    value
) {

    const text =
        String(
            value ||
            ""
        )
            .trim();


    const minLength =
        field.dataset.minLength
            ? Number(
                field.dataset.minLength
            )
            : null;


    const maxLength =
        field.dataset.maxLength
            ? Number(
                field.dataset.maxLength
            )
            : null;


    if (
        minLength !== null &&
        text.length <
        minLength
    ) {

        return {

            valid:
                false,

            message:
                `This field must contain at least ${minLength} characters.`

        };

    }


    if (
        maxLength !== null &&
        text.length >
        maxLength
    ) {

        return {

            valid:
                false,

            message:
                `This field cannot exceed ${maxLength} characters.`

        };

    }


    return {

        valid:
            true,

        message:
            ""

    };

}


/* =========================================================
   FIELD INPUT EVENTS
========================================================= */

function handleFieldBlur(
    event
) {

    const field =
        event.target;


    if (
        !isFormField(
            field
        )
    ) {

        return;

    }


    const form =
        field.form;


    if (!form) {
        return;
    }


    validateField(
        field,
        form,
        true
    );

}


function handleFieldInput(
    event
) {

    const field =
        event.target;


    if (
        !isFormField(
            field
        )
    ) {

        return;

    }


    /*
     * Remove stale errors while the user is correcting
     * a field. The field is revalidated on blur.
     */
    if (
        field.classList.contains(
            VALIDATION_CONFIG.classes.invalid
        )
    ) {

        validateField(
            field,
            field.form,
            true
        );

    }


    /*
     * Live password confirmation.
     */
    if (
        field.name ===
            "password" ||
        field.id ===
            "password"
    ) {

        const confirm =
            getField(
                field.form,
                [
                    "confirm_password",
                    "password_confirmation"
                ]
            );


        if (confirm) {

            validateField(
                confirm,
                field.form,
                true
            );

        }

    }

}


/* =========================================================
   FIELD UI
========================================================= */

function setFieldValidationState(
    field,
    result
) {

    if (!field) {
        return;
    }


    const wrapper =
        getFieldWrapper(
            field
        );


    const errorElement =
        getErrorElement(
            field,
            wrapper
        );


    field.classList.remove(
        VALIDATION_CONFIG.classes.invalid,
        VALIDATION_CONFIG.classes.valid
    );


    if (
        result.valid
    ) {

        field.classList.add(
            VALIDATION_CONFIG.classes.valid
        );


        field.setAttribute(
            "aria-invalid",
            "false"
        );


        if (
            errorElement
        ) {

            errorElement.textContent =
                "";


            errorElement.hidden =
                true;

        }

        return;

    }


    field.classList.add(
        VALIDATION_CONFIG.classes.invalid
    );


    field.setAttribute(
        "aria-invalid",
        "true"
    );


    if (
        errorElement
    ) {

        errorElement.textContent =
            result.message ||
            "Invalid value.";


        errorElement.hidden =
            false;

    }

}


function getFieldWrapper(
    field
) {

    return (
        field.closest(
            ".form-group"
        ) ||
        field.closest(
            ".form-field"
        ) ||
        field.parentElement
    );

}


function getErrorElement(
    field,
    wrapper
) {

    if (!wrapper) {
        return null;
    }


    let error =
        wrapper.querySelector(
            `.field-error-message[data-for="${CSS.escape(
                field.name ||
                field.id ||
                ""
            )}"]`
        );


    if (!error) {

        error =
            wrapper.querySelector(
                VALIDATION_CONFIG.selectors.error
            );

    }


    /*
     * Automatically create an error container
     * when the form does not provide one.
     */
    if (!error) {

        error =
            document.createElement(
                "small"
            );


        error.className =
            "field-error-message";


        error.hidden =
            true;


        error.setAttribute(
            "role",
            "alert"
        );


        wrapper.appendChild(
            error
        );

    }


    return error;

}


/* =========================================================
   ERROR SUMMARY
========================================================= */

function showFormErrorSummary(
    form,
    errors
) {

    if (!form) {
        return;
    }


    const existing =
        form.querySelector(
            ".validation-error-summary"
        );


    if (existing) {

        existing.remove();

    }


    const keys =
        Object.keys(
            errors
        );


    if (!keys.length) {
        return;
    }


    const summary =
        document.createElement(
            "div"
        );


    summary.className =
        "validation-error-summary";


    summary.setAttribute(
        "role",
        "alert"
    );


    const title =
        document.createElement(
            "strong"
        );


    title.textContent =
        "Please correct the following:";


    summary.appendChild(
        title
    );


    const list =
        document.createElement(
            "ul"
        );


    keys.forEach(
        key => {

            const item =
                document.createElement(
                    "li"
                );


            item.textContent =
                errors[key];


            list.appendChild(
                item
            );

        }
    );


    summary.appendChild(
        list
    );


    form.prepend(
        summary
    );

}


function focusFirstInvalidField(
    form
) {

    const field =
        form.querySelector(
            ".is-invalid"
        );


    if (!field) {
        return;
    }


    field.focus(
        {
            preventScroll:
                false
        }
    );

}


/* =========================================================
   FIELD HELPERS
========================================================= */

function getFieldName(
    field
) {

    return (
        field.name ||
        field.id ||
        ""
    );

}


function getFieldValue(
    field
) {

    if (
        field.type ===
        "checkbox"
    ) {

        return field.checked
            ? field.value ||
              true
            : "";

    }


    if (
        field.type ===
        "radio"
    ) {

        if (!field.form) {
            return field.checked
                ? field.value
                : "";
        }


        const selected =
            field.form.querySelector(
                `input[name="${CSS.escape(
                    field.name
                )}"]:checked`
            );


        return selected?.value ||
            "";

    }


    return String(
        field.value ??
        ""
    )
        .trim();

}


function getField(
    form,
    names
) {

    if (
        !form ||
        !Array.isArray(names)
    ) {

        return null;

    }


    for (
        const name of names
    ) {

        const field =
            form.querySelector(
                `[name="${CSS.escape(name)}"], #${CSS.escape(name)}`
            );


        if (field) {

            return field;

        }

    }


    return null;

}


function isFormField(
    element
) {

    if (!element) {
        return false;
    }


    return [
        "INPUT",
        "SELECT",
        "TEXTAREA"
    ].includes(
        element.tagName
    );

}


function isEmpty(
    value
) {

    return (
        value === null ||
        value === undefined ||
        String(value)
            .trim()
            .length === 0
    );

}


function normalize(
    value
) {

    return String(
        value ||
        ""
    )
        .trim()
        .toLowerCase()
        .replace(
            /[_-]+/g,
            " "
        );

}


function startOfDay(
    date
) {

    return new Date(
        date.getFullYear(),
        date.getMonth(),
        date.getDate()
    );

}


function getRequiredMessage(
    field
) {

    const label =
        field.dataset.label ||
        getReadableFieldName(
            field
        );


    return `${label} is required.`;

}


function getReadableFieldName(
    field
) {

    const raw =
        field.name ||
        field.id ||
        "This field";


    return raw
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


/* =========================================================
   PASSWORD MATCH VALIDATION
========================================================= */

function passwordsMatch(
    passwordField,
    confirmField
) {

    if (
        !passwordField ||
        !confirmField
    ) {

        return true;

    }


    return (
        getFieldValue(
            passwordField
        ) ===
        getFieldValue(
            confirmField
        )
    );

}


/* =========================================================
   PUBLIC API
========================================================= */

window.HotelValidation = {

    /*
     * Core API.
     */

    initialize:
        initializeValidation,


    registerForm:
        registerForm,


    validateForm:
        validateForm,


    validateField:
        validateField,


    /*
     * Authentication.
     */

    validateLogin:
        validateLoginForm,


    validateRegister:
        validateRegisterForm,


    validateForgotPassword:
        validateForgotPasswordForm,


    /*
     * Hotel modules.
     */

    validateGuest:
        validateGuestForm,


    validateRoom:
        validateRoomForm,


    validateBooking:
        validateBookingForm,


    validatePayment:
        validatePaymentForm,


    validateStaff:
        validateStaffForm,


    /*
     * Individual validators.
     */

    email:
        validateEmail,


    password:
        validatePassword,


    name:
        validateName,


    phone:
        validatePhone,


    date:
        validateDate,


    number:
        validateNumber,


    roomNumber:
        validateRoomNumber,


    cardNumber:
        validateCardNumber,


    cvv:
        validateCVV,


    cardExpiry:
        validateCardExpiry,


    url:
        validateURL,


    passwordsMatch
};


/* =========================================================
   OPTIONAL GLOBAL ALIASES
========================================================= */

window.validateForm =
    validateForm;


window.validateField =
    validateField;
