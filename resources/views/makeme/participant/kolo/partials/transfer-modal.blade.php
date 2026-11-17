@php
    $sender = auth('makeme')->user();
    $transferBalance = (int) ($balance ?? 0);
    $transferRecipients = collect($transferRecipients ?? []);
    $transferError = $errors->hasAny(['recipient_id','amount','password','request_key']);
@endphp
<div class="kt-bar">
    <div><strong>Send KoloMoni Credits</strong><p>Transfer securely to another Kreativ.</p></div>
    <button type="button" class="kt-primary" id="ktOpen">⇄ Transfer Credits</button>
</div>
@if($transferError)
    <div class="kt-alert" role="alert"><strong>Transfer unsuccessful.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<div class="kt-overlay" id="ktOverlay" hidden>
    <section class="kt-modal" id="ktModal" role="dialog" aria-modal="true" aria-labelledby="ktHeading" tabindex="-1">
        <header class="kt-head">
            <div><span>KOLOMONI TRANSFERS</span><h2 id="ktHeading">Transfer Credits</h2><p>Choose a recipient and confirm the transfer with your password.</p></div>
            <button type="button" id="ktClose" class="kt-close" aria-label="Close">×</button>
        </header>
        <div class="kt-columns">
            <aside class="kt-left">
                <label for="ktSearch">Select a Kreativ</label>
                <input id="ktSearch" type="search" placeholder="Search by name or Kreativ number" autocomplete="off">
                <div class="kt-list" id="ktList">
                    @forelse($transferRecipients as $recipient)
                        @php
                            $recipientName = trim(implode(' ', array_filter([$recipient->first_name, $recipient->other_name, $recipient->surname]))) ?: 'Kreativ #'.$recipient->id;
                        @endphp
                        <button type="button" class="kt-person" data-kt-person data-id="{{ $recipient->id }}" data-name="{{ $recipientName }}" data-number="{{ $recipient->participant_number ?? '#'.$recipient->id }}" aria-pressed="false">
                            <span class="kt-avatar">{{ mb_strtoupper(mb_substr($recipientName,0,1)) }}</span>
                            <span class="kt-person-text"><strong>{{ $recipientName }}</strong><small>{{ $recipient->participant_number ?? '#'.$recipient->id }}</small></span>
                            <span aria-hidden="true">›</span>
                        </button>
                    @empty
                        <p class="kt-muted">No eligible Kreativs available.</p>
                    @endforelse
                </div>
                <p class="kt-muted" id="ktNoMatches" hidden>No Kreativs match this search.</p>
            </aside>
            <div class="kt-right">
                <form method="POST" action="{{ route('makeme.participant.kolo.transfer') }}" id="ktForm" autocomplete="off">
                    @csrf
                    <input type="hidden" name="recipient_id" id="ktRecipientId" value="{{ old('recipient_id') }}">
                    <input type="hidden" name="request_key" value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}">
                    <div class="kt-parties">
                        <div><span>FROM</span><strong>{{ trim(($sender?->first_name ?? '').' '.($sender?->surname ?? '')) }}</strong><small>Your wallet</small></div>
                        <span aria-hidden="true">→</span>
                        <div><span>TO</span><strong id="ktRecipientName">Select recipient</strong><small id="ktRecipientNumber">Choose on the left</small></div>
                    </div>
                    <div class="kt-balance"><span>AVAILABLE BALANCE</span><strong id="ktBalance" data-value="{{ $transferBalance }}">{{ number_format($transferBalance) }} <small>credits</small></strong></div>
                    <label for="ktAmount">Amount to send</label>
                    <div class="kt-input-group"><input type="number" id="ktAmount" name="amount" min="1" max="{{ min(999999999,max(0,$transferBalance)) }}" step="1" inputmode="numeric" placeholder="0" value="{{ old('amount') }}" required><span>credits</span></div>
                    <p class="kt-remaining">Balance after transfer: <strong id="ktRemaining">{{ number_format($transferBalance) }}</strong> credits</p>
                    <label for="ktPassword">Confirm with your password</label>
                    <div class="kt-input-group"><input type="password" id="ktPassword" name="password" autocomplete="current-password" placeholder="Enter your password" required><button id="ktShowPassword" type="button">Show</button></div>
                    <p class="kt-warning">Check the recipient and amount carefully. Credits are moved immediately after confirmation.</p>
                    <footer class="kt-footer"><button type="button" id="ktCancel" class="kt-secondary">Cancel</button><button type="submit" id="ktSubmit" class="kt-primary" disabled>Confirm Transfer</button></footer>
                </form>
            </div>
        </div>
    </section>
</div>
@push('styles')
<style>
.kt-bar{display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;margin:22px 0;padding:18px 20px;border-radius:13px;background:#1b1b22;color:#fff;border:1px solid #a97827}.kt-bar strong{font-size:15px}.kt-bar p{font-size:12px;color:#dedde1;margin:5px 0 0}
.kt-primary{border:0;border-radius:9px;background:#f4a116;color:#171717;font-size:13px;font-weight:800;padding:12px 18px;cursor:pointer}.kt-primary:disabled{opacity:.5;cursor:not-allowed}.kt-secondary{background:#fff;border:1px solid #d5d8df;border-radius:9px;padding:12px 18px;cursor:pointer}.kt-alert{margin:10px 0;padding:14px;border-radius:9px;background:#fff1ee;color:#94251c}
.kt-overlay[hidden],.kt-muted[hidden],.kt-person[hidden]{display:none!important}.kt-overlay{position:fixed;inset:0;background:rgba(10,12,19,.74);z-index:99999;padding:15px;display:flex;align-items:center;justify-content:center}.kt-modal{background:#fff;color:#17171a;width:min(990px,100%);max-height:92vh;overflow-y:auto;border-radius:17px;box-shadow:0 20px 70px #0006}
.kt-head{display:flex;justify-content:space-between;gap:15px;padding:20px 24px;border-bottom:1px solid #e5e7ed}.kt-head span{font-size:10px;letter-spacing:.13em;font-weight:800;color:#ac761e}.kt-head h2{margin:4px 0;font-size:22px}.kt-head p{margin:0;font-size:12px;color:#6e7580}.kt-close{width:34px;height:34px;border:0;border-radius:8px;cursor:pointer;font-size:25px;background:#f0f1f4}
.kt-columns{display:grid;grid-template-columns:42% minmax(0,58%)}.kt-left{min-width:0;padding:20px;background:#f7f8fa;border-right:1px solid #e7e9ee}.kt-right{padding:22px;min-width:0}.kt-left label,.kt-right label{display:block;font-size:12px;font-weight:800;margin-bottom:8px}.kt-left input,.kt-right input{width:100%;box-sizing:border-box;border:1px solid #d5d8df;border-radius:9px;padding:12px;font:inherit;font-size:13px;background:#fff;color:#17171a}.kt-left input:focus,.kt-right input:focus{outline:2px solid #f4a11688}
.kt-list{display:flex;flex-direction:column;gap:5px;max-height:400px;overflow:auto;margin-top:12px}.kt-person{width:100%;display:flex;align-items:center;gap:10px;text-align:left;border:1px solid transparent;border-radius:10px;background:transparent;padding:9px;cursor:pointer;color:#17171a}.kt-person:hover,.kt-person[aria-pressed=true]{background:#fff;border-color:#f4a116}.kt-avatar{display:grid;place-items:center;flex:0 0 35px;width:35px;height:35px;background:#ffedcd;border-radius:50%;font-weight:800;color:#8b5f19}.kt-person-text{flex:1;min-width:0}.kt-person-text strong,.kt-person-text small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.kt-person-text strong{font-size:12px}.kt-person-text small{font-size:11px;color:#6b7280;margin-top:3px}.kt-muted{font-size:12px;color:#777e89;padding:10px}
.kt-parties{display:grid;grid-template-columns:minmax(0,1fr) 20px minmax(0,1fr);align-items:center;gap:9px;margin-bottom:17px}.kt-parties>div{border:1px solid #e7e8ed;border-radius:10px;padding:11px;background:#fafafa;min-width:0}.kt-parties span,.kt-balance>span{font-size:10px;color:#7b818e;font-weight:800}.kt-parties strong,.kt-parties small{display:block;overflow-wrap:anywhere}.kt-parties strong{font-size:12px;margin:5px 0}.kt-parties small{font-size:11px;color:#7a818e}.kt-balance{background:#171920;padding:15px;border-radius:10px;margin-bottom:20px;display:flex;justify-content:space-between;gap:8px;align-items:center}.kt-balance>span{color:#efeadf}.kt-balance strong{font-size:23px;color:#f4a116}.kt-balance small{font-size:11px}.kt-input-group{position:relative;margin-bottom:10px}.kt-input-group>span,.kt-input-group>button{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:12px}.kt-input-group>button{background:transparent;border:0;cursor:pointer;color:#8b5f19}.kt-input-group:has(>span) input{padding-right:75px}.kt-input-group:has(>button) input{padding-right:65px}.kt-remaining{font-size:12px;color:#626a77;margin:3px 0 18px}.kt-warning{padding:11px 13px;background:#fff7e6;border:1px solid #eddbb7;border-radius:9px;color:#76551c;font-size:12px;line-height:1.5}.kt-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}
@media(max-width:700px){.kt-overlay{padding:8px;align-items:flex-end}.kt-modal{border-radius:16px 16px 0 0;max-height:95vh}.kt-columns{grid-template-columns:1fr}.kt-left{padding:14px;border-right:0;border-bottom:1px solid #e7e9ee}.kt-list{max-height:175px}.kt-right{padding:15px}.kt-head{padding:15px}.kt-head h2{font-size:18px}}
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const el=id=>document.getElementById(id), overlay=el('ktOverlay');if(!overlay)return;
 const form=el('ktForm'),buttons=[...overlay.querySelectorAll('[data-kt-person]')],search=el('ktSearch'),amount=el('ktAmount'),pass=el('ktPassword'),submit=el('ktSubmit'),balance=Number(el('ktBalance').dataset.value),id=el('ktRecipientId');let selected=null,processing=false,previous=null;
 const validate=()=>{const n=Number(amount.value),valid=amount.value!==''&&Number.isSafeInteger(n)&&n>0&&n<=Math.min(balance,999999999);el('ktRemaining').textContent=(valid?balance-n:balance).toLocaleString();submit.disabled=processing||!selected||!valid||!pass.value;};
 function choose(b){selected=b;id.value=b.dataset.id;el('ktRecipientName').textContent=b.dataset.name;el('ktRecipientNumber').textContent=b.dataset.number;buttons.forEach(x=>x.setAttribute('aria-pressed',String(x===b)));validate();}
 function open(){previous=document.activeElement;overlay.hidden=false;document.body.style.overflow='hidden';search.focus();}
 function close(){if(processing)return;overlay.hidden=true;document.body.style.overflow='';pass.value='';validate();previous?.focus?.();}
 el('ktOpen').addEventListener('click',open);el('ktClose').addEventListener('click',close);el('ktCancel').addEventListener('click',close);overlay.addEventListener('click',e=>{if(e.target===overlay)close()});
 buttons.forEach(b=>b.addEventListener('click',()=>choose(b)));
 search.addEventListener('input',()=>{const q=search.value.trim().toLowerCase();let hits=0;buttons.forEach(b=>{b.hidden=!(b.dataset.name+' '+b.dataset.number).toLowerCase().includes(q);if(!b.hidden)hits++});el('ktNoMatches').hidden=hits!==0||buttons.length===0;});
 amount.addEventListener('input',validate);pass.addEventListener('input',validate);
 el('ktShowPassword').addEventListener('click',function(){let visible=pass.type==='password';pass.type=visible?'text':'password';this.textContent=visible?'Hide':'Show'});
 document.addEventListener('keydown',e=>{if(overlay.hidden)return;if(e.key==='Escape'){e.preventDefault();close()}if(e.key==='Tab'){const nodes=[...el('ktModal').querySelectorAll('button:not([disabled]), input:not([disabled])')].filter(x=>!x.hidden&&x.getClientRects().length);if(!nodes.length)return;if(e.shiftKey&&document.activeElement===nodes[0]){e.preventDefault();nodes.at(-1).focus()}else if(!e.shiftKey&&document.activeElement===nodes.at(-1)){e.preventDefault();nodes[0].focus()}}});
 form.addEventListener('submit',e=>{validate();if(submit.disabled||processing){e.preventDefault();return}if(!confirm(`Transfer ${Number(amount.value).toLocaleString()} credits to ${selected.dataset.name}?`)){e.preventDefault();return}processing=true;submit.disabled=true;submit.textContent='Processing…';});
 if(id.value){const old=buttons.find(b=>b.dataset.id===id.value);if(old)choose(old)}validate();
 @if($transferError)open();@endif
});
</script>
@endpush
