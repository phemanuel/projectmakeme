(() => {
    const modals = document.querySelectorAll('.modal-backdrop');
    let lastTrigger = null;
    const openModal = (id, trigger) => {
        const target = document.getElementById(id);
        if (!target) return;
        lastTrigger = trigger || null;
        target.hidden = false;
        document.body.classList.add('modal-open');
        const field = target.querySelector('input:not([type=hidden]), select, button');
        field?.focus();
    };
    const closeModal = (target) => {
        target.hidden = true;
        if (![...modals].some(m => !m.hidden)) document.body.classList.remove('modal-open');
        lastTrigger?.focus();
    };
    document.querySelectorAll('[data-open-modal]').forEach(btn => btn.addEventListener('click', () => openModal(btn.dataset.openModal, btn)));
    document.querySelectorAll('[data-close-modal]').forEach(btn => btn.addEventListener('click', () => closeModal(btn.closest('.modal-backdrop'))));
    modals.forEach(modal => modal.addEventListener('mousedown', e => { if (e.target === modal) closeModal(modal); }));
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') modals.forEach(modal => { if (!modal.hidden) closeModal(modal); });
    });

    const editForm = document.getElementById('editForm');
    document.querySelectorAll('.js-edit').forEach(btn => btn.addEventListener('click', () => {
        editForm.action = btn.dataset.url;
        document.getElementById('editParticipantName').textContent = btn.dataset.name;
        document.getElementById('editSkill').value = btn.dataset.skill;
        document.getElementById('editStatus').value = btn.dataset.status;
        openModal('editModal', btn);
    }));

    const search = document.getElementById('userSearch');
    const results = document.getElementById('userResults');
    const userId = document.getElementById('selectedUserId');
    const selected = document.getElementById('selectedUser');
    let controller;
    let debounce;
    const clearSelection = () => { userId.value = ''; selected.hidden = true; selected.textContent = ''; };
    search.addEventListener('input', () => {
        clearSelection();
        results.hidden = true;
        results.replaceChildren();
        clearTimeout(debounce);
        controller?.abort();
        const q = search.value.trim();
        if (q.length < 2) return;
        debounce = setTimeout(async () => {
            controller = new AbortController();
            try {
                const url = new URL(search.dataset.searchUrl, window.location.origin);
                url.searchParams.set('q', q);
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal });
                if (!response.ok) throw new Error('Search failed');
                const payload = await response.json();
                results.replaceChildren();
                const users = payload.users || [];
                if (!users.length) {
                    const empty = document.createElement('div');
                    empty.className = 'user-result';
                    empty.textContent = 'No eligible NYFEW users found.';
                    results.appendChild(empty);
                }
                users.forEach(user => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'user-result';
                    option.setAttribute('role', 'option');
                    const name = `${user.first_name || ''} ${user.surname || ''}`.trim();
                    const title = document.createElement('strong');
                    title.textContent = name || 'Unnamed user';
                    const sub = document.createElement('small');
                    sub.textContent = user.email;
                    option.append(title, sub);
                    option.addEventListener('click', () => {
                        userId.value = String(user.id);
                        search.value = user.email;
                        selected.textContent = `✓ Selected: ${name || user.email}`;
                        selected.hidden = false;
                        results.hidden = true;
                    });
                    results.appendChild(option);
                });
                results.hidden = false;
            } catch (err) {
                if (err.name === 'AbortError') return;
                results.textContent = 'Unable to search right now. Please try again.';
                results.hidden = false;
            }
        }, 250);
    });
    document.getElementById('addForm').addEventListener('submit', e => {
        if (!userId.value) {
            e.preventDefault();
            search.setCustomValidity('Please select an existing NYFEW user from the search results.');
            search.reportValidity();
        }
    });
    search.addEventListener('input', () => search.setCustomValidity(''));
})();
