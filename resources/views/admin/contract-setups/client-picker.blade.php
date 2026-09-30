 const clients=@json($contractClientOptions);
 const lockedClients=@json(isset($prefill) ? ['primary'=>$prefill['primary_client_id'],'co'=>$prefill['co_client_id']] : []);

 const clientById=id=>clients.find(client=>String(client.id)===String(id));
 const bindClientPicker=picker=>{
   const role=picker.dataset.contractClientPicker;
   const select=field(role==='primary'?'primary_client_id':'co_client_id');
   const search=picker.querySelector('.client-search');
   const results=picker.querySelector('.client-results');
   const selected=picker.querySelector('.selected-client');
   const locked=!!lockedClients[role];
   search.disabled=locked;
   const render=()=>{
     selected.innerHTML='';
     const client=clientById(select.value);
     if(!client){selected.innerHTML='<span class="text-muted small">No client selected.</span>';return;}
     const chip=document.createElement('span');chip.className='selected-client-chip';
     const strong=document.createElement('strong');strong.textContent=client.label;chip.appendChild(strong);
     const remove=document.createElement('button');remove.type='button';remove.setAttribute('aria-label','Remove selected client');remove.innerHTML='&times;';
     remove.addEventListener('click',()=>{select.value='';render();search.focus();});if(!locked)chip.appendChild(remove);selected.appendChild(chip);
   };
   const show=()=>{
     const query=search.value.trim().toLowerCase();results.innerHTML='';
     if(!query){results.classList.remove('show');return;}
     clients.filter(client=>[client.label,client.email,client.phone].filter(Boolean).join(' ').toLowerCase().includes(query)).slice(0,6).forEach(client=>{
       const button=document.createElement('button');button.type='button';button.className='list-group-item list-group-item-action';
       const strong=document.createElement('strong');strong.textContent=client.label;button.appendChild(strong);
       const small=document.createElement('small');small.textContent=[client.email,client.phone].filter(Boolean).join(' / ');button.appendChild(small);
       button.addEventListener('click',()=>{select.value=String(client.id);search.value='';results.classList.remove('show');render();});results.appendChild(button);
     });
     results.classList.toggle('show',results.children.length>0);
   };
   search.addEventListener('input',show);search.addEventListener('focus',show);render();
 };
 form.querySelectorAll('[data-contract-client-picker]').forEach(bindClientPicker);
 const setMode=select=>{
   const p=select.dataset.prefix,m=select.value;
   if(lockedClients[p])select.querySelectorAll('option').forEach(option=>option.disabled=option.value!==m);
   form.querySelectorAll('[data-owner="'+p+'"]').forEach(box=>{const show=box.classList.contains('mode-'+m);box.classList.toggle('d-none',!show);box.querySelectorAll('input,select,textarea').forEach(el=>el.disabled=!show);});
   field(p+'_client_id').disabled=m!=='existing';
 };
 form.querySelectorAll('.mode-select').forEach(s=>{s.addEventListener('change',()=>setMode(s));setMode(s);});
