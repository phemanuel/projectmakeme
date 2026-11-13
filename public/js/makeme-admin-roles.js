
/**
 * PROJECT MAKEME LIVE
 * Roles & Permissions Management
 *
 * File: public/js/makeme-admin-roles.js
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    let activeModal = null;
    let previousFocus = null;

    const createModal = document.getElementById('rpCreateModal');
    const editModal = document.getElementById('rpEditModal');
    const deleteModal = document.getElementById('rpDeleteModal');

    const createForm = createModal?.querySelector('form');
    const editForm = document.getElementById('rpEditForm');
    const deleteForm = document.getElementById('rpDeleteForm');

    /*
    |--------------------------------------------------------------------------
    | MODAL HANDLING
    |--------------------------------------------------------------------------
    */

    function openModal(id) {
        const modal = document.getElementById(id);

        if (!modal) {
            console.error('Role modal not found:', id);
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

        document.body.classList.add('rp-modal-open');

        const focusTarget = modal.querySelector(
            'input:not([type="hidden"]):not([disabled]), ' +
            'button:not([disabled])'
        );

        focusTarget?.focus();
    }

    function closeModal() {
        if (!activeModal) return;

        activeModal.hidden = true;
        activeModal = null;

        document.body.classList.remove('rp-modal-open');

        if (previousFocus?.isConnected) {
            previousFocus.focus();
        }

        previousFocus = null;
    }

    document.querySelectorAll('[data-rp-open]')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                if (this.dataset.rpOpen === 'rpCreateModal') {
                    resetCreateForm();
                }

                openModal(this.dataset.rpOpen);
            });
        });

    document.querySelectorAll('[data-rp-close]')
        .forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

    document.querySelectorAll('.rp-modal-overlay')
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
        if (!activeModal) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }

        if (event.key !== 'Tab') return;

        const focusable = Array.from(
            activeModal.querySelectorAll(
                'a[href], ' +
                'button:not([disabled]), ' +
                'input:not([disabled]):not([type="hidden"]), ' +
                'select:not([disabled]), ' +
                '[tabindex]:not([tabindex="-1"])'
            )
        ).filter(function (element) {
            return element.getClientRects().length > 0;
        });

        if (!focusable.length) {
            event.preventDefault();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

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
    | PERMISSION HELPERS
    |--------------------------------------------------------------------------
    */

    function getPermissions(form) {
        return Array.from(
            form.querySelectorAll('[data-rp-permission]')
        );
    }

    function syncModuleCheckbox(module) {
        const permissions = Array.from(
            module.querySelectorAll('[data-rp-permission]')
        );

        const toggle = module.querySelector(
            '[data-rp-module-all]'
        );

        if (!toggle || !permissions.length) return;

        const selectedCount = permissions.filter(
            checkbox => checkbox.checked
        ).length;

        toggle.checked = selectedCount === permissions.length;

        toggle.indeterminate =
            selectedCount > 0 &&
            selectedCount < permissions.length;
    }

    function syncAllModules(form) {
        form.querySelectorAll('[data-rp-module]')
            .forEach(syncModuleCheckbox);

        updateSelectAllButton(form);
    }

    function updateSelectAllButton(form) {
        const permissions = getPermissions(form);
        const button = form.querySelector(
            '[data-rp-select-all]'
        );

        if (!button) return;

        const allChecked =
            permissions.length > 0 &&
            permissions.every(checkbox => checkbox.checked);

        button.textContent = allChecked
            ? 'Deselect All'
            : 'Select All';

        button.dataset.allSelected = String(allChecked);
    }

    function applyPermissions(form, selectedPermissions) {
        if (!form) return;

        const selected = new Set(selectedPermissions);

        getPermissions(form).forEach(function (checkbox) {
            checkbox.checked = selected.has(checkbox.value);
        });

        syncAllModules(form);
    }

    /*
    |--------------------------------------------------------------------------
    | MODULE SELECT ALL
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('[data-rp-module-all]')
        .forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                const module = this.closest('[data-rp-module]');
                if (!module) return;

                module.querySelectorAll('[data-rp-permission]')
                    .forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });

                syncModuleCheckbox(module);

                const form = this.closest('form');
                if (form) updateSelectAllButton(form);
            });
        });

    /*
    |--------------------------------------------------------------------------
    | INDIVIDUAL PERMISSION CHANGES
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('[data-rp-permission]')
        .forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                const module = this.closest('[data-rp-module]');
                const form = this.closest('form');

                if (module) syncModuleCheckbox(module);
                if (form) updateSelectAllButton(form);
            });
        });

    /*
    |--------------------------------------------------------------------------
    | GLOBAL SELECT ALL
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('[data-rp-select-all]')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                const form = this.closest('form');

                if (!form) return;

                const permissions = getPermissions(form);

                const allChecked =
                    permissions.length > 0 &&
                    permissions.every(checkbox => checkbox.checked);

                permissions.forEach(function (checkbox) {
                    checkbox.checked = !allChecked;
                });

                syncAllModules(form);
            });
        });

    /*
    |--------------------------------------------------------------------------
    | RESET CREATE FORM
    |--------------------------------------------------------------------------
    */

    function resetCreateForm() {
        if (!createForm) return;

        createForm.reset();

        applyPermissions(createForm, []);

        const submit = createForm.querySelector(
            'button[type="submit"]'
        );

        if (submit) {
            submit.disabled = false;
            submit.textContent = 'Create Role';
        }

        createForm.dataset.submitting = 'false';
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT ROLE
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('[data-rp-edit]')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                if (!editForm) return;

                const roleId = this.dataset.roleId;
                const name = this.dataset.roleName;
                const url = this.dataset.url;

                const permissions = (
                    this.dataset.rolePermissions || ''
                ).split(',').filter(Boolean);

                editForm.action = url;

                document.getElementById(
                    'rpEditRoleId'
                ).value = roleId;

                document.getElementById(
                    'rpEditName'
                ).value = name;

                applyPermissions(editForm, permissions);

                editForm.dataset.submitting = 'false';

                const submit = editForm.querySelector(
                    'button[type="submit"]'
                );

                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Save Changes';
                }

                openModal('rpEditModal');
            });
        });

    /*
    |--------------------------------------------------------------------------
    | DELETE ROLE
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('[data-rp-delete]')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                if (!deleteForm) return;

                const name = this.dataset.roleName;
                const url = this.dataset.url;

                deleteForm.action = url;

                document.getElementById(
                    'rpDeleteMessage'
                ).textContent =
                    `Are you sure you want to delete the "${name}" role? This action cannot be undone.`;

                deleteForm.dataset.submitting = 'false';

                const submit = deleteForm.querySelector(
                    'button[type="submit"]'
                );

                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Delete Role';
                }

                openModal('rpDeleteModal');
            });
        });

    /*
    |--------------------------------------------------------------------------
    | PREVENT DOUBLE SUBMISSIONS
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(
        '[data-rp-form], #rpDeleteForm'
    ).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

            form.dataset.submitting = 'true';

            const submit = form.querySelector(
                'button[type="submit"]'
            );

            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Saving...';
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | RESTORE MODAL AFTER VALIDATION ERRORS
    |--------------------------------------------------------------------------
    */

    const validation = document.getElementById(
        'rpValidationData'
    );

    if (validation) {
        const modalType = validation.dataset.modal;
        const roleId = validation.dataset.roleId;

        const name = validation.dataset.roleName || '';

        const permissions = (
            validation.dataset.permissions || ''
        ).split(',').filter(Boolean);

        if (modalType === 'create' && createForm) {
            createForm.querySelector('[name="name"]').value = name;

            applyPermissions(createForm, permissions);

            openModal('rpCreateModal');
        }

        if (modalType === 'edit' && roleId) {
            const button = Array.from(
                document.querySelectorAll('[data-rp-edit]')
            ).find(function (item) {
                return item.dataset.roleId === String(roleId);
            });

            if (button) {
                button.click();

                document.getElementById(
                    'rpEditName'
                ).value = name;

                applyPermissions(editForm, permissions);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INITIALISE CHECKBOX STATES
    |--------------------------------------------------------------------------
    */

    if (createForm) {
        syncAllModules(createForm);
    }

    if (editForm) {
        syncAllModules(editForm);
    }

});
