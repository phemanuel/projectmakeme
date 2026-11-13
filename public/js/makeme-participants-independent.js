(() => {
  let lastTrigger;
  const modals = [...document.querySelectorAll('.modal-backdrop')];
  function open(id, trigger) {
    const modal = document.getElementById(id);
    if (!modal) return;
    lastTrigger = trigger;
    modal.hidden = false;
    document.body.classList.add('modal-open');
    modal.querySelector('input:not([type=hidden]),select,button')?.focus();
  }
  function close(modal) {
    if (!modal) return;
    modal.hidden = true;
    if (modals.every(m => m.hidden)) document.body.classList.remove('modal-open');
    lastTrigger?.focus();
  }
  document.querySelectorAll('[data-open-modal]').forEach(el=>el.addEventListener('click', ()=>open(el.dataset.openModal,el)));
  document.querySelectorAll('[data-close-modal]').forEach(el=>el.addEventListener('click',()=>close(el.closest('.modal-backdrop'))));
  modals.forEach(el=>el.addEventListener('click',ev=>{if(ev.target===el)close(el)}));
  document.addEventListener('keydown',ev=>{if(ev.key==='Escape')modals.forEach(el=>!el.hidden&&close(el))});
  document.querySelectorAll('.js-edit').forEach(el=>el.addEventListener('click',()=>{
    document.getElementById('editForm').action = el.dataset.url;
    document.getElementById('editName').textContent = el.dataset.first+' '+el.dataset.last;
    for(const [id,prop] of Object.entries({editFirst:'first',editLast:'last',editOther:'other',editEmail:'email',editPhone:'phone',editSkill:'skill',editStatus:'status'})){
      document.getElementById(id).value = el.dataset[prop] || '';
    }
    open('editModal',el);
  }));
})();
