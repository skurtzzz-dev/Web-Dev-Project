(() => {
  'use strict';
  const body = document.getElementById('ordersBody');
  const message = document.getElementById('adminMessage');
  const csrf = window.FILIPINONLY_ADMIN?.csrf || '';
  const money = value => `₱${Number(value || 0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
  const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  async function api(action,payload={}) {
    const response = await fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify({action,...payload})});
    const data = await response.json().catch(()=>({ok:false,message:'Invalid server response.'}));
    if(!response.ok || !data.ok) throw new Error(data.message || 'Request failed.');
    return data;
  }
  function showMessage(text,isError=false){if(isError)window.FilipinOnlySound?.error();message.textContent=text;message.classList.toggle('error',isError);message.hidden=false;}
  async function loadOrders(){
    body.innerHTML='<tr><td colspan="8">Loading orders…</td></tr>';
    try{
      const data=await api('admin_list_orders');
      document.getElementById('ordersCount').textContent=`${data.orders.length} order(s)`;
      document.getElementById('lastUpdated').textContent=`Updated ${new Date().toLocaleTimeString()}`;
      if(!data.orders.length){body.innerHTML='<tr><td colspan="8" class="admin-empty">No orders yet. Place a test order from the storefront.</td></tr>';return;}
      body.innerHTML=data.orders.map(order=>{
        const items=(order.items||[]).map(item=>`${Number(item.qty||1)} × ${esc(item.name)}${item.serving?` <small>(${esc(item.serving)})</small>`:''}`).join('<br>');
        return `<tr><td><strong>${esc(order.id)}</strong><small>${esc(order.created_at||'')}</small></td><td><strong>${esc(order.name)}</strong><small>${esc(order.phone)}</small><small>${esc(order.email)}</small></td><td>${items}</td><td><strong>${money(order.total)}</strong><small>${esc(order.payment_status)}</small></td><td>${esc(order.payment)}</td><td><span class="mode-chip">${esc(order.status)}</span></td><td><select class="admin-status" data-status-for="${esc(order.id)}"><option ${order.status==='Order received'?'selected':''}>Order received</option><option ${order.status==='Preparing'?'selected':''}>Preparing</option><option ${order.status==='On the way'?'selected':''}>On the way</option><option ${order.status==='Delivered'?'selected':''}>Delivered</option><option ${order.status==='Cancelled'?'selected':''}>Cancelled</option></select><button class="admin-action" data-save-status="${esc(order.id)}">Save</button></td><td><a class="admin-receipt" target="_blank" rel="noopener" href="receipt.php?id=${encodeURIComponent(order.id)}">View receipt ↗</a></td></tr>`;
      }).join('');
    }catch(error){body.innerHTML=`<tr><td colspan="8" class="admin-empty">${esc(error.message)}</td></tr>`;showMessage(error.message,true);}
  }
  body.addEventListener('click',async event=>{
    const button=event.target.closest('[data-save-status]');if(!button)return;
    const id=button.dataset.saveStatus;const select=body.querySelector(`[data-status-for="${CSS.escape(id)}"]`);
    button.disabled=true;button.textContent='Saving…';
    try{const result=await api('admin_update_status',{id,status:select.value});showMessage(`${id}: ${result.message}`);await loadOrders();}
    catch(error){showMessage(error.message,true);button.disabled=false;button.textContent='Save';}
  });
  document.getElementById('refreshOrders').addEventListener('click',loadOrders);
  loadOrders();
})();
