document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const readData = id => {
        try { return JSON.parse(document.getElementById(id)?.textContent || 'null'); }
        catch (_) { return null; }
    };
    const orders = readData('omOrderData') || [];
    const participants = readData('omParticipantData') || [];
    const products = readData('omProductData') || [];
    const byId = id => document.getElementById(id);
    const credits = number => new Intl.NumberFormat('en-NG').format(Number(number) || 0) + ' credits';
    const orderById = id => orders.find(o => String(o.id) === String(id));
    let activeModal = null;
    let lastFocus = null;

    function openModal(id) {
        const modal = byId(id);
        if (!modal) return;
        if (activeModal) activeModal.hidden = true;
        else lastFocus = document.activeElement;
        activeModal = modal;
        modal.hidden = false;
        document.body.classList.add('om-modal-open');
        modal.querySelector('button, input:not([type="hidden"]), select, textarea')?.focus();
    }
    function closeModal() {
        if (!activeModal) return;
        activeModal.hidden = true;
        activeModal = null;
        document.body.classList.remove('om-modal-open');
        if (lastFocus?.isConnected) lastFocus.focus();
        lastFocus = null;
    }
    document.querySelectorAll('[data-om-close]').forEach(button => button.addEventListener('click', closeModal));
    document.querySelectorAll('.om-overlay').forEach(overlay => overlay.addEventListener('click', event => {
        if (event.target === overlay) closeModal();
    }));
    document.addEventListener('keydown', event => {
        if (!activeModal) return;
        if (event.key === 'Escape') { closeModal(); return; }
        if (event.key !== 'Tab') return;
        const controls = [...activeModal.querySelectorAll('button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), a[href]')]
            .filter(element => element.getClientRects().length);
        if (!controls.length) return;
        if (event.shiftKey && document.activeElement === controls[0]) {
            event.preventDefault(); controls[controls.length - 1].focus();
        } else if (!event.shiftKey && document.activeElement === controls[controls.length - 1]) {
            event.preventDefault(); controls[0].focus();
        }
    });

    const participantSelect = byId('omParticipant');
    const itemsContainer = byId('omItems');
    const createForm = byId('omCreateForm');
    let rowSequence = 0;
    function addOption(select, value, label) {
        const option = document.createElement('option');
        option.value = String(value);
        option.textContent = label;
        select.appendChild(option);
    }
    if (participantSelect) {
        participants.forEach(person => addOption(participantSelect, person.id,
            `${person.name} (#${person.id}) — ${credits(person.balance)}`));
    }
    function makeItemRow(productId = '', quantity = 1) {
        if (!itemsContainer) return;
        const index = rowSequence++;
        const wrap = document.createElement('div');
        wrap.className = 'om-item-row';
        const productField = document.createElement('div');
        productField.className = 'om-field';
        const productLabel = document.createElement('label');
        productLabel.textContent = 'Product';
        const select = document.createElement('select');
        select.name = `items[${index}][product_id]`;
        select.required = true;
        addOption(select, '', 'Select product');
        products.forEach(product => addOption(select, product.id,
            `${product.name} — ${credits(product.price)} (${product.stock} in stock)`));
        select.value = String(productId || '');
        productField.append(productLabel, select);

        const quantityField = document.createElement('div');
        quantityField.className = 'om-field';
        const qtyLabel = document.createElement('label');
        qtyLabel.textContent = 'Quantity';
        const qtyInput = document.createElement('input');
        qtyInput.type = 'number';
        qtyInput.name = `items[${index}][quantity]`;
        qtyInput.min = '1';
        qtyInput.max = '100000';
        qtyInput.step = '1';
        qtyInput.required = true;
        qtyInput.value = String(quantity || 1);
        quantityField.append(qtyLabel, qtyInput);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'om-btn om-secondary om-remove';
        remove.textContent = 'Remove';
        remove.addEventListener('click', () => { wrap.remove(); recalculate(); });
        select.addEventListener('change', recalculate);
        qtyInput.addEventListener('input', recalculate);
        wrap.append(productField, quantityField, remove);
        itemsContainer.appendChild(wrap);
        recalculate();
    }
    function recalculate() {
        const participant = participants.find(person => String(person.id) === participantSelect?.value);
        if (byId('omWalletBalance')) byId('omWalletBalance').textContent = participant ? credits(participant.balance) : '—';
        let total = 0;
        let valid = true;
        const seen = new Set();
        itemsContainer?.querySelectorAll('.om-item-row').forEach(row => {
            const select = row.querySelector('select');
            const input = row.querySelector('input');
            const product = products.find(p => String(p.id) === select.value);
            const qty = Number(input.value);
            row.classList.remove('om-invalid');
            if (!product || !Number.isSafeInteger(qty) || qty < 1 || qty > 100000) {
                valid = false; row.classList.add('om-invalid'); return;
            }
            if (seen.has(product.id) || qty > product.stock || (product.limit > 0 && qty > product.limit)) {
                valid = false; row.classList.add('om-invalid');
            }
            seen.add(product.id);
            total += product.price * qty;
        });
        if (byId('omOrderTotal')) byId('omOrderTotal').textContent = credits(total);
        const insufficient = !!participant && total > participant.balance;
        const warning = byId('omBalanceWarning');
        if (warning) {
            warning.hidden = !insufficient && valid;
            warning.textContent = insufficient
                ? 'Insufficient wallet credits for this order.'
                : 'Review selected products: avoid duplicates and quantities above available stock or item limits.';
        }
        const save = byId('omSaveOrder');
        if (save) save.disabled = !participant || !itemsContainer?.children.length || !valid || insufficient || total > 999999999;
    }
    participantSelect?.addEventListener('change', recalculate);
    byId('omAddItem')?.addEventListener('click', () => makeItemRow());
    byId('omOpenCreate')?.addEventListener('click', () => {
        if (!itemsContainer.children.length) makeItemRow();
        recalculate();
        openModal('omCreateModal');
    });
    createForm?.addEventListener('submit', event => {
        recalculate();
        if (byId('omSaveOrder').disabled || !createForm.checkValidity()) {
            event.preventDefault(); createForm.reportValidity(); return;
        }
        if (createForm.dataset.submitting === 'yes') { event.preventDefault(); return; }
        createForm.dataset.submitting = 'yes';
        byId('omSaveOrder').disabled = true;
        byId('omSaveOrder').textContent = 'Placing...';
    });

    function appendCell(row, value) {
        const cell = document.createElement('td');
        cell.textContent = String(value ?? '—');
        row.appendChild(cell);
    }
    document.querySelectorAll('[data-om-details]').forEach(button => button.addEventListener('click', () => {
        const order = orderById(button.dataset.omDetails);
        if (!order) return;
        byId('omDetailsSubtitle').textContent = `${order.reference} · ${order.participant}`;
        const meta = byId('omDetailMeta');
        meta.replaceChildren();
        [
            ['Participant ID', order.participant_id],
            ['Status', order.status],
            ['Placed', order.date],
            ['Reference', order.reference],
        ].forEach(([label, value]) => {
            const box = document.createElement('div');
            const strong = document.createElement('strong'); strong.textContent = label;
            const text = document.createElement('span'); text.textContent = String(value || '—');
            box.append(strong, text); meta.appendChild(box);
        });
        const rows = byId('omDetailItems');
        rows.replaceChildren();
        order.items.forEach(item => {
            const tr = document.createElement('tr');
            appendCell(tr, `${item.name}${item.sku ? ` (${item.sku})` : ''}`);
            appendCell(tr, item.quantity);
            appendCell(tr, credits(item.price));
            appendCell(tr, credits(item.total));
            rows.appendChild(tr);
        });
        byId('omDetailTotal').textContent = credits(order.total);
        const notes = byId('omDetailNotes');
        notes.textContent = [order.notes ? `Notes: ${order.notes}` : '',
            order.cancellation_reason ? `Cancellation reason: ${order.cancellation_reason}` : '']
            .filter(Boolean).join('\n');
        openModal('omDetailsModal');
    }));
    document.querySelectorAll('[data-om-fulfill]').forEach(button => button.addEventListener('click', () => {
        const order = orderById(button.dataset.omFulfill);
        if (!order) return;
        byId('omFulfillForm').action = order.fulfill_url;
        byId('omFulfillSubtitle').textContent = order.reference;
        openModal('omFulfillModal');
    }));
    document.querySelectorAll('[data-om-cancel]').forEach(button => button.addEventListener('click', () => {
        const order = orderById(button.dataset.omCancel);
        if (!order) return;
        byId('omCancelForm').action = order.cancel_url;
        byId('omCancelOrderId').value = order.id;
        byId('omCancelSubtitle').textContent = order.reference;
        byId('omCancelReason').value = '';
        openModal('omCancelModal');
    }));
    const restore = readData('omRestoreData');
    if (restore?.modal === 'create' && createForm) {
        participantSelect.value = String(restore.participant_id || '');
        itemsContainer.replaceChildren();
        Object.values(restore.items || {}).forEach(item => makeItemRow(item.product_id, item.quantity));
        if (!itemsContainer.children.length) makeItemRow();
        recalculate();
        openModal('omCreateModal');
    } else if (restore?.modal === 'cancel') {
        const order = orderById(restore.order_id);
        if (order && byId('omCancelForm')) {
            byId('omCancelForm').action = order.cancel_url;
            byId('omCancelOrderId').value = order.id;
            byId('omCancelSubtitle').textContent = order.reference;
            byId('omCancelReason').value = restore.reason || '';
            openModal('omCancelModal');
        }
    }
});
