/** Profile fields and photographs extension. Existing modal/import JS is retained. */
document.addEventListener('DOMContentLoaded', () => {
    const columns = [
        'first_name','surname','other_name','email','mobile_no','alternative_email',
        'business_name','profession','age','city','state_of_origin',
        'instagram_handle','tiktok_handle','facebook_handle','x_handle','linkedin_handle',
        'skill_category','status'
    ];
    const prettyNames = {
        first_name:'First name',surname:'Surname',other_name:'Other name',email:'Email',mobile_no:'Phone',
        alternative_email:'Alternative email',business_name:'Business name',profession:'Profession',
        age:'Age',city:'City',state_of_origin:'State of origin',instagram_handle:'Instagram',
        tiktok_handle:'TikTok',facebook_handle:'Facebook',x_handle:'X (Twitter)',linkedin_handle:'LinkedIn',
        skill_category:'Skill',status:'Status'
    };
    let pendingPhotoUrl = new WeakMap();

    function renderImage(container, url) {
        if (!container) return;
        container.replaceChildren();
        if (!url) { const span = document.createElement('span'); span.textContent = 'Photo'; container.appendChild(span); return; }
        const img = document.createElement('img'); img.src = url; img.alt = 'Contestant photograph';
        img.addEventListener('error', () => { container.replaceChildren(); const span = document.createElement('span'); span.textContent = 'Photo'; container.appendChild(span); }, {once:true});
        container.appendChild(img);
    }
    function profileFromButton(button) { try { return JSON.parse(button.dataset.profile || '{}'); } catch { return {}; } }
    function populateEdit(button) {
        const p = profileFromButton(button), form = document.getElementById('editForm');
        if (!form) return;
        form.action = button.dataset.url || '';
        const hidden = form.querySelector('[name="_participant_id"]'); if (hidden) hidden.value = p.id || '';
        columns.forEach(key => { const input = form.elements.namedItem(key); if (input) input.value = p[key] ?? ''; });
        const label = document.getElementById('editName'); if (label) label.textContent = p.full_name || '';
        const imageInput = form.querySelector('[name="photo"]'); if (imageInput) imageInput.value = '';
        renderImage(form.querySelector('[data-photo-preview]'), p.photo_url || '');
    }
    document.querySelectorAll('.js-edit').forEach(button => button.addEventListener('click', () => populateEdit(button)));
    document.querySelectorAll('[data-photo-input]').forEach(input => {
        input.addEventListener('change', () => {
            const form = input.closest('form'); const target = form?.querySelector('[data-photo-preview]');
            const prior = pendingPhotoUrl.get(input); if (prior) URL.revokeObjectURL(prior);
            const file = input.files?.[0];
            if (!file) { if (form?.id === 'editForm') return; renderImage(target, null); return; }
            if (!['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > 3 * 1024 * 1024) {
                input.value = ''; alert('Choose a JPG, PNG or WebP photograph, maximum 3 MB.'); return;
            }
            const url = URL.createObjectURL(file); pendingPhotoUrl.set(input,url); renderImage(target,url);
        });
    });
    document.querySelectorAll('.js-profile').forEach(button => button.addEventListener('click', () => {
        const p = profileFromButton(button), grid = document.getElementById('mpProfileInfo');
        const title = document.getElementById('profileModalTitle'); if (title) title.textContent = p.full_name || 'Contestant details';
        renderImage(document.getElementById('mpViewPhoto'),p.photo_url);
        if (grid) {
            grid.replaceChildren();
            const entries = [['Participant number',p.participant_number], ...columns.map(key=>[prettyNames[key],p[key]])];
            entries.forEach(([label,value])=>{
                const item = document.createElement('div');item.className='mp-profile-entry';
                const term=document.createElement('span');term.textContent=label;
                const data=document.createElement('strong');data.textContent=value === null || value === undefined || value === '' ? '—' : String(value);
                item.append(term,data);grid.appendChild(item);
            });
        }
        const modal = document.getElementById('profileModal'); if (modal) { modal.hidden = false; document.body.style.overflow = 'hidden'; }
    }));
    // The old JS handles other modals; provide a fallback for the new View Profile modal only.
    document.getElementById('profileModal')?.querySelectorAll('[data-close-modal]').forEach(button=>button.addEventListener('click',()=>{
        document.getElementById('profileModal').hidden=true;
        if (![...document.querySelectorAll('.modal-backdrop')].some(el=>!el.hidden)) document.body.style.overflow='';
    }));
    document.addEventListener('keydown', event=>{ if(event.key==='Escape') {
        const modal=document.getElementById('profileModal');if(modal && !modal.hidden){modal.hidden=true;document.body.style.overflow='';}
    }});
    const restore = document.getElementById('mpRestoreProfile');
    if (restore) {
        try {
            const info = JSON.parse(restore.textContent), modal = document.getElementById(info.modal === 'edit' ? 'editModal' : 'addModal');
            const form=modal?.querySelector('form');
            if (form) {
                columns.forEach(key => { const field = form.elements.namedItem(key); if(field && Object.hasOwn(info.values,key)) field.value = info.values[key] ?? ''; });
                if (info.modal === 'edit') {
                    const button=[...document.querySelectorAll('.js-edit')].find(el=>profileFromButton(el).id == info.id);
                    if(button) { populateEdit(button); columns.forEach(key=>{const field=form.elements.namedItem(key);if(field && Object.hasOwn(info.values,key))field.value=info.values[key] ?? '';}); }
                }
                modal.hidden=false;
            }
        } catch(error) { console.error('Profile restoration failed',error); }
    }
});
