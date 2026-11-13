
/**
 * PROJECT MAKEME LIVE
 * Admin User Management
 *
 * File: public/js/makeme-admin-users.js
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    let activeModal = null;
    let previousFocus = null;

    /*
    |--------------------------------------------------------------------------
    | MODAL MANAGEMENT
    |--------------------------------------------------------------------------
    */

    function openModal(modalId) {
        const modal = document.getElementById(modalId);

        if (!modal) {
            console.error('Modal not found:', modalId);
            return;
        }

        if (!activeModal) {
            previousFocus = document.activeElement;
        }

        if (activeModal && activeModal !== modal) {
            activeModal.hidden = true;
        }

        modal.hidden = false;
        activeModal = modal;

        document.body.classList.add('au-modal-open');

        const focusable = modal.querySelector(
            'input:not([type="hidden"]):not([disabled]), ' +
            'select:not([disabled]), ' +
            'textarea:not([disabled]), ' +
            'button:not([disabled])'
        );

        if (focusable) {
            focusable.focus();
        }
    }

    function closeModal() {
        if (!activeModal) {
            return;
        }

        activeModal.hidden = true;
        activeModal = null;

        document.body.classList.remove('au-modal-open');

        if (
            previousFocus &&
            typeof previousFocus.focus === 'function' &&
            previousFocus.isConnected
        ) {
            previousFocus.focus();
        }

        previousFocus = null;
    }

    // Available to Blade scripts.
    window.openAdminModal = openModal;
    window.closeAdminModal = closeModal;

    /*
    |--------------------------------------------------------------------------
    | OPEN MODALS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('[data-open-modal]')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                const modalId = this.dataset.openModal;

                if (modalId === 'createAdminModal') {
                    resetCreateForm();
                }

                openModal(modalId);
            });
        });

    /*
    |--------------------------------------------------------------------------
    | CLOSE MODALS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('[data-close-modal]')
        .forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

    document.querySelectorAll('.au-modal-overlay')
        .forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        });

    /*
    |--------------------------------------------------------------------------
    | KEYBOARD ACCESSIBILITY
    |--------------------------------------------------------------------------
    */

    document.addEventListener('keydown', function (event) {
        if (!activeModal) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusableElements = Array.from(
            activeModal.querySelectorAll(
                'a[href], ' +
                'button:not([disabled]), ' +
                'input:not([disabled]):not([type="hidden"]), ' +
                'select:not([disabled]), ' +
                'textarea:not([disabled]), ' +
                '[tabindex]:not([tabindex="-1"])'
            )
        ).filter(function (element) {
            return element.getClientRects().length > 0;
        });

        if (!focusableElements.length) {
            event.preventDefault();
            return;
        }

        const first = focusableElements[0];
        const last = focusableElements[
            focusableElements.length - 1
        ];

        if (
            event.shiftKey &&
            document.activeElement === first
        ) {
            event.preventDefault();
            last.focus();
        } else if (
            !event.shiftKey &&
            document.activeElement === last
        ) {
            event.preventDefault();
            first.focus();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | CREATE ADMINISTRATOR
    |--------------------------------------------------------------------------
    */

    const createForm = document.querySelector(
        '#createAdminModal form'
    );

    function resetCreateForm() {
        if (!createForm) {
            return;
        }

        createForm.reset();

        const submitButton = createForm.querySelector(
            'button[type="submit"]'
        );

        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = 'Create Administrator';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT ADMINISTRATOR
    |--------------------------------------------------------------------------
    */

    const editForm = document.getElementById('editAdminForm');

    const editAdminId = document.getElementById('editAdminId');
    const editFirstName = document.getElementById('editFirstName');
    const editSurname = document.getElementById('editSurname');
    const editEmail = document.getElementById('editEmail');
    const editRole = document.getElementById('editRole');
    const editPassword = document.getElementById('editPassword');
    const editPasswordConfirmation = document.getElementById(
        'editPasswordConfirmation'
    );

    document.querySelectorAll('[data-edit-user]')
        .forEach(function (button) {
            button.addEventListener('click', function () {

                if (
                    !editForm ||
                    !editAdminId ||
                    !editFirstName ||
                    !editSurname ||
                    !editEmail ||
                    !editRole
                ) {
                    console.error(
                        'Edit administrator form is incomplete.'
                    );
                    return;
                }

                const data = this.dataset;

                // Set the correct administrator update URL.
                editForm.action = data.url;

                // Identify the administrator being edited.
                editAdminId.value = data.id || '';

                // Populate the administrator's information.
                editFirstName.value = data.firstName || '';
                editSurname.value = data.surname || '';
                editEmail.value = data.email || '';
                editRole.value = data.role || '';

                // Never populate existing passwords.
                if (editPassword) {
                    editPassword.value = '';
                }

                if (editPasswordConfirmation) {
                    editPasswordConfirmation.value = '';
                }

                // Reset the submit button.
                const submitButton = editForm.querySelector(
                    'button[type="submit"]'
                );

                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Save Changes';
                }

                openModal('editAdminModal');
            });
        });

    /*
    |--------------------------------------------------------------------------
    | ACTIVATE / DEACTIVATE ADMINISTRATOR
    |--------------------------------------------------------------------------
    */

    const statusForm = document.getElementById(
        'statusAdminForm'
    );

    const statusMessage = document.getElementById(
        'statusAdminMessage'
    );

    const statusSubmit = document.getElementById(
        'statusAdminSubmit'
    );

    document.querySelectorAll('[data-status-user]')
        .forEach(function (button) {
            button.addEventListener('click', function () {

                if (
                    !statusForm ||
                    !statusMessage ||
                    !statusSubmit
                ) {
                    console.error(
                        'Administrator status modal is incomplete.'
                    );
                    return;
                }

                const name = this.dataset.name || 'this administrator';
                const action = this.dataset.action;
                const url = this.dataset.url;

                if (
                    !url ||
                    !['activate', 'deactivate'].includes(action)
                ) {
                    console.error(
                        'Invalid administrator status action.'
                    );
                    return;
                }

                statusForm.action = url;

                statusMessage.textContent =
                    action === 'deactivate'
                        ? `Are you sure you want to deactivate ${name}'s administrative account?`
                        : `Are you sure you want to activate ${name}'s administrative account?`;

                statusSubmit.textContent =
                    action === 'deactivate'
                        ? 'Deactivate Account'
                        : 'Activate Account';

                statusSubmit.disabled = false;

                statusSubmit.classList.toggle(
                    'au-btn-danger',
                    action === 'deactivate'
                );

                statusSubmit.classList.toggle(
                    'au-btn-primary',
                    action === 'activate'
                );

                openModal('statusAdminModal');
            });
        });

    /*
    |--------------------------------------------------------------------------
    | FORM VALIDATION
    |--------------------------------------------------------------------------
    */

    function validatePasswordConfirmation(form) {
        const password = form.querySelector(
            '[name="password"]'
        );

        const confirmation = form.querySelector(
            '[name="password_confirmation"]'
        );

        if (!password || !confirmation) {
            return true;
        }

        confirmation.setCustomValidity('');

        // On edit, both fields may be empty.
        if (
            password.value === '' &&
            confirmation.value === ''
        ) {
            return true;
        }

        if (password.value !== confirmation.value) {
            confirmation.setCustomValidity(
                'Passwords do not match.'
            );

            confirmation.reportValidity();
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | PREVENT DUPLICATE FORM SUBMISSIONS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('[data-admin-form]')
        .forEach(function (form) {

            const password = form.querySelector(
                '[name="password"]'
            );

            const confirmation = form.querySelector(
                '[name="password_confirmation"]'
            );

            if (password && confirmation) {
                [password, confirmation].forEach(function (input) {
                    input.addEventListener('input', function () {
                        confirmation.setCustomValidity('');
                    });
                });
            }

            form.addEventListener('submit', function (event) {

                if (form.dataset.submitting === 'true') {
                    event.preventDefault();
                    return;
                }

                if (!validatePasswordConfirmation(form)) {
                    event.preventDefault();
                    return;
                }

                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                    return;
                }

                form.dataset.submitting = 'true';

                const submitButton = form.querySelector(
                    'button[type="submit"]'
                );

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Saving...';
                }
            });
        });

    /*
    |--------------------------------------------------------------------------
    | PREVENT DUPLICATE STATUS SUBMISSIONS
    |--------------------------------------------------------------------------
    */

    if (statusForm) {
        statusForm.addEventListener('submit', function (event) {

            if (statusForm.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            statusForm.dataset.submitting = 'true';

            if (statusSubmit) {
                statusSubmit.disabled = true;
                statusSubmit.textContent = 'Processing...';
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RESTORE MODAL AFTER LARAVEL VALIDATION ERRORS
    |--------------------------------------------------------------------------
    |
    | Reads validation information exposed by Blade.
    | The same modal will reopen after validation fails.
    |
    */

    const validationData = document.getElementById(
        'adminValidationData'
    );

    if (validationData) {
        const modalType = validationData.dataset.modal;
        const adminId = validationData.dataset.adminId;

        if (modalType === 'create') {

            // Preserve Laravel's old() input values.
            openModal('createAdminModal');

        } else if (modalType === 'edit' && adminId) {

            const editButton = Array.from(
                document.querySelectorAll('[data-edit-user]')
            ).find(function (button) {
                return button.dataset.id === String(adminId);
            });

            if (editButton) {
                editButton.click();

                const oldValues = {
                    firstName: validationData.dataset.firstName,
                    surname: validationData.dataset.surname,
                    email: validationData.dataset.email,
                    role: validationData.dataset.role
                };

                editFirstName.value = oldValues.firstName || '';
                editSurname.value = oldValues.surname || '';
                editEmail.value = oldValues.email || '';
                editRole.value = oldValues.role || '';
            }
        }
    }

    /*
|--------------------------------------------------------------------------
| SHOW / HIDE PASSWORD
|--------------------------------------------------------------------------
*/

const eyeOpenSvg = `
    <svg xmlns="http://www.w3.org/2000/svg"
         width="20" height="20"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="1.8"
         stroke-linecap="round"
         stroke-linejoin="round">
        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
        <circle cx="12" cy="12" r="3"/>
    </svg>
`;

const eyeClosedSvg = `
    <svg xmlns="http://www.w3.org/2000/svg"
         width="20" height="20"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="1.8"
         stroke-linecap="round"
         stroke-linejoin="round">
        <path d="M3 3l18 18"/>
        <path d="M10.6 5.1A11.9 11.9 0 0 1 12 5c6.5 0 10 7 10 7a15.5 15.5 0 0 1-3.2 4.1"/>
        <path d="M6.1 6.1C3.4 8 2 12 2 12s3.5 7 10 7a10.8 10.8 0 0 0 4.1-.8"/>
        <path d="M10 10a3 3 0 0 0 4 4"/>
    </svg>
`;

document.querySelectorAll('[data-toggle-password]')
    .forEach(function (button) {

        const inputId = button.dataset.togglePassword;
        const input = document.getElementById(inputId);
        const icon = button.querySelector('.au-eye-icon');

        if (!input || !icon) {
            return;
        }

        icon.innerHTML = eyeOpenSvg;

        button.addEventListener('click', function () {

            const isCurrentlyHidden = input.type === 'password';

            input.type = isCurrentlyHidden ? 'text' : 'password';

            icon.innerHTML = isCurrentlyHidden
                ? eyeClosedSvg
                : eyeOpenSvg;

            button.setAttribute(
                'aria-label',
                isCurrentlyHidden ? 'Hide password' : 'Show password'
            );

            button.setAttribute(
                'aria-pressed',
                String(isCurrentlyHidden)
            );
        });
    });

});
