
/**
 * Project MakeMe Live
 * Wallet Management
 *
 * File: public/js/makeme-admin-wallets.js
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    let activeModal = null;
    let previousFocus = null;
    let selectedBalance = 0;

    const adjustForm = document.getElementById('wmAdjustForm');
    const adjustSubmit = document.getElementById('wmAdjustSubmit');

    const typeInput = document.getElementById('wmType');
    const amountInput = document.getElementById('wmAmount');
    const reasonInput = document.getElementById('wmReason');

    const currentBalance = document.getElementById(
        'wmCurrentBalance'
    );

    const newBalance = document.getElementById(
        'wmNewBalance'
    );

    const balanceWarning = document.getElementById(
        'wmBalanceWarning'
    );

    const numberFormatter = new Intl.NumberFormat('en-NG');

    function formatCredits(value) {
        return numberFormatter.format(value) + ' credits';
    }

    /*
    |--------------------------------------------------------------------------
    | MODAL MANAGEMENT
    |--------------------------------------------------------------------------
    */

    function openModal(id) {
        const modal = document.getElementById(id);

        if (!modal) return;

        if (!activeModal) {
            previousFocus = document.activeElement;
        }

        if (activeModal && activeModal !== modal) {
            activeModal.hidden = true;
        }

        activeModal = modal;
        modal.hidden = false;

        document.body.classList.add('wm-modal-open');

        const firstElement = modal.querySelector(
            'input:not([type="hidden"]):not([disabled]), ' +
            'select:not([disabled]), ' +
            'textarea:not([disabled]), ' +
            'button:not([disabled])'
        );

        firstElement?.focus();
    }

    function closeModal() {
        if (!activeModal) return;

        activeModal.hidden = true;
        activeModal = null;

        document.body.classList.remove('wm-modal-open');

        if (
            previousFocus &&
            previousFocus.isConnected &&
            typeof previousFocus.focus === 'function'
        ) {
            previousFocus.focus();
        }

        previousFocus = null;
    }

    document.querySelectorAll('[data-wm-close]')
        .forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

    document.querySelectorAll('.wm-overlay')
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
                'textarea:not([disabled])'
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
    | PARTICIPANT DATA
    |--------------------------------------------------------------------------
    */

    function getParticipant(id) {
        const element = document.getElementById(
            'wmParticipant' + id
        );

        if (!element) return null;

        try {
            return JSON.parse(element.textContent);
        } catch (error) {
            console.error(
                'Unable to read participant wallet data.',
                error
            );
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | WALLET BALANCE PREVIEW
    |--------------------------------------------------------------------------
    */

    function updateBalancePreview() {
        if (!typeInput || !amountInput) return;

        const type = typeInput.value;
        const amount = Number(amountInput.value);

        const validAmount =
            amountInput.value !== '' &&
            Number.isSafeInteger(amount) &&
            amount >= 1 &&
            amount <= 999999999;

        const newValue = validAmount
            ? type === 'credit'
                ? selectedBalance + amount
                : selectedBalance - amount
            : selectedBalance;

        const insufficient =
            validAmount &&
            type === 'debit' &&
            newValue < 0;

        const exceedsMaximum =
            validAmount &&
            type === 'credit' &&
            newValue > 999999999;

        if (newBalance) {
            newBalance.textContent = formatCredits(newValue);
            newBalance.classList.toggle(
                'wm-negative',
                insufficient || exceedsMaximum
            );
        }

        if (balanceWarning) {
            balanceWarning.hidden =
                !insufficient && !exceedsMaximum;

            balanceWarning.textContent = insufficient
                ? 'Insufficient credits for this deduction.'
                : 'The maximum wallet balance is 999,999,999 credits.';
        }

        if (adjustSubmit) {
            adjustSubmit.disabled =
                insufficient || exceedsMaximum;
        }
    }

    typeInput?.addEventListener(
        'change',
        updateBalancePreview
    );

    amountInput?.addEventListener(
        'input',
        updateBalancePreview
    );

    /*
    |--------------------------------------------------------------------------
    | ADJUST WALLET CREDITS
    |--------------------------------------------------------------------------
    */

    function openAdjustment(participant) {
        if (!participant || !adjustForm) return;

        adjustForm.reset();
        adjustForm.dataset.submitting = 'false';

        selectedBalance = Number(participant.balance) || 0;

        adjustForm.action = participant.adjust_url;

        document.getElementById(
            'wmAdjustParticipantId'
        ).value = participant.id;

        document.getElementById(
            'wmAdjustSubtitle'
        ).textContent = participant.name;

        currentBalance.textContent = formatCredits(
            selectedBalance
        );

        typeInput.value = 'credit';
        amountInput.value = '';
        reasonInput.value = '';

        adjustSubmit.disabled = false;
        adjustSubmit.textContent = 'Save Adjustment';

        updateBalancePreview();
        openModal('wmAdjustModal');
    }

    document.querySelectorAll('[data-wm-adjust]')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                const participant = getParticipant(
                    this.dataset.wmAdjust
                );

                openAdjustment(participant);
            });
        });

    /*
    |--------------------------------------------------------------------------
    | TRANSACTION HISTORY
    |--------------------------------------------------------------------------
    */

    function createCell(text) {
        const cell = document.createElement('td');
        cell.textContent = text;
        return cell;
    }

    function openHistory(participant) {
        if (!participant) return;

        const historyBody = document.getElementById(
            'wmHistoryBody'
        );

        const subtitle = document.getElementById(
            'wmHistorySubtitle'
        );

        if (!historyBody) return;

        historyBody.replaceChildren();

        if (subtitle) {
            subtitle.textContent =
                participant.name +
                ' — Balance: ' +
                formatCredits(participant.balance);
        }

        const transactions = participant.transactions || [];

        if (!transactions.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');

            cell.colSpan = 6;
            cell.className = 'wm-empty';
            cell.textContent =
                'No wallet transactions recorded yet.';

            row.appendChild(cell);
            historyBody.appendChild(row);
        } else {
            transactions.forEach(function (tx) {
                const row = document.createElement('tr');

                row.appendChild(createCell(tx.date || '—'));

                const typeCell = document.createElement('td');
                const badge = document.createElement('span');

                const isCredit = [
                    'credit',
                    'refund',
                ].includes(tx.type);

                badge.className = isCredit
                    ? 'wm-badge wm-badge-success'
                    : 'wm-badge wm-badge-danger';

                badge.textContent =
                    tx.type
                        ? tx.type.charAt(0).toUpperCase() +
                          tx.type.slice(1)
                        : 'Unknown';

                typeCell.appendChild(badge);
                row.appendChild(typeCell);

                row.appendChild(
                    createCell(
                        (isCredit ? '+' : '-') +
                        numberFormatter.format(tx.amount)
                    )
                );

                row.appendChild(
                    createCell(
                        numberFormatter.format(tx.before) +
                        ' → ' +
                        numberFormatter.format(tx.after)
                    )
                );

                row.appendChild(
                    createCell(tx.reason || '—')
                );

                row.appendChild(
                    createCell(tx.administrator || 'System')
                );

                historyBody.appendChild(row);
            });
        }

        openModal('wmHistoryModal');
    }

    document.querySelectorAll('[data-wm-history]')
        .forEach(function (button) {
            button.addEventListener('click', function () {
                const participant = getParticipant(
                    this.dataset.wmHistory
                );

                openHistory(participant);
            });
        });

    /*
    |--------------------------------------------------------------------------
    | FORM VALIDATION & DUPLICATE SUBMISSION PREVENTION
    |--------------------------------------------------------------------------
    */

    if (adjustForm) {
        adjustForm.addEventListener('submit', function (event) {
            if (adjustForm.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            if (!adjustForm.checkValidity()) {
                event.preventDefault();
                adjustForm.reportValidity();
                return;
            }

            const type = typeInput.value;
            const amount = Number(amountInput.value);

            if (
                !Number.isSafeInteger(amount) ||
                amount < 1 ||
                amount > 999999999
            ) {
                event.preventDefault();
                amountInput.setCustomValidity(
                    'Please enter a valid whole number of credits.'
                );
                amountInput.reportValidity();
                amountInput.setCustomValidity('');
                return;
            }

            if (
                type === 'debit' &&
                amount > selectedBalance
            ) {
                event.preventDefault();
                window.alert(
                    'You cannot deduct more credits than are available.'
                );
                return;
            }

            if (
                type === 'credit' &&
                selectedBalance + amount > 999999999
            ) {
                event.preventDefault();
                window.alert(
                    'The wallet would exceed the maximum balance.'
                );
                return;
            }

            adjustForm.dataset.submitting = 'true';

            adjustSubmit.disabled = true;
            adjustSubmit.textContent = 'Saving...';
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RESTORE MODAL AFTER VALIDATION FAILURE
    |--------------------------------------------------------------------------
    */

    const validation = document.getElementById(
        'wmValidation'
    );

    if (
        validation &&
        validation.dataset.modal === 'adjust'
    ) {
        const id = validation.dataset.participantId;
        const participant = getParticipant(id);

        if (participant && adjustForm) {
            openAdjustment(participant);

            typeInput.value =
                validation.dataset.type || 'credit';

            amountInput.value =
                validation.dataset.amount || '';

            reasonInput.value =
                validation.dataset.reason || '';

            updateBalancePreview();
        }
    }

});
