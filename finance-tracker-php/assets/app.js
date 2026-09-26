let currentType = 'income';
let trendChart, pieChart;
const freqLabel = {daily:'Harian', weekly:'Mingguan', monthly:'Bulanan', yearly:'Tahunan'};

function fmt(n){ return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

async function loadCategories(){
  const res = await fetch(`api/categories.php?type=${currentType}`);
  const cats = await res.json();
  const sel = document.getElementById('fCategory');
  sel.innerHTML = cats.map(c=>`<option value="${c.id}">${c.name}</option>`).join('');
}

document.getElementById('typeSeg').addEventListener('click', e=>{
  const btn = e.target.closest('button'); if(!btn) return;
  currentType = btn.dataset.type;
  document.querySelectorAll('#typeSeg button').forEach(b=>b.classList.toggle('active', b===btn));
  loadCategories();
});
document.getElementById('fRecurring').addEventListener('change', e=>{
  document.getElementById('recurringFields').style.display = e.target.checked ? 'block':'none';
});

document.getElementById('addBtn').addEventListener('click', async ()=>{
  const msg = document.getElementById('formMsg');
  msg.textContent=''; msg.className='msg';
  const amount = parseFloat(document.getElementById('fAmount').value);
  const category_id = document.getElementById('fCategory').value;
  const date = document.getElementById('fDate').value || new Date().toISOString().slice(0,10);
  const note = document.getElementById('fNote').value.trim();
  const isRecurring = document.getElementById('fRecurring').checked;

  if(!amount || amount<=0 || !category_id){ msg.textContent='Isi jumlah dan kategori dengan benar.'; msg.className='msg error'; return; }

  const payload = { type: currentType, category_id, amount, note, date, is_recurring: isRecurring };
  if(isRecurring){
    payload.frequency = document.getElementById('fFreq').value;
    payload.end_date = document.getElementById('fEndDate').value || null;
  }

  try{
    const res = await fetch('api/add_transaction.php', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(!res.ok || data.error){ msg.textContent = data.error || 'Gagal menyimpan.'; msg.className='msg error'; return; }

    document.getElementById('fAmount').value='';
    document.getElementById('fNote').value='';
    document.getElementById('fRecurring').checked=false;
    document.getElementById('recurringFields').style.display='none';
    msg.textContent='Transaksi tersimpan.'; msg.className='msg ok';
    refreshAll();
  }catch(err){ msg.textContent='Terjadi kesalahan koneksi.'; msg.className='msg error'; }
});

async function deleteTx(id){
  await fetch('api/delete_transaction.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})});
  refreshAll();
}
async function deleteRecurring(id){
  if(!confirm('Hentikan transaksi berulang ini? Riwayat yang sudah tercatat tidak akan dihapus.')) return;
  await fetch('api/delete_recurring.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})});
  refreshAll();
}
window.deleteTx = deleteTx;
window.deleteRecurring = deleteRecurring;

function renderSummary(s){
  document.getElementById('sumIncome').textContent = fmt(s.income);
  document.getElementById('sumExpense').textContent = fmt(s.expense);
  document.getElementById('sumBalance').textContent = fmt(s.balance);
}

function renderTable(list){
  const body = document.getElementById('txBody');
  document.getElementById('txEmpty').style.display = list.length ? 'none':'block';
  body.innerHTML = list.map(t=>`
    <tr>
      <td class="mono">${t.tx_date}</td>
      <td>${t.category}</td>
      <td>${t.note || '-'}</td>
      <td><span class="tag ${t.type}">${t.type==='income'?'Masuk':'Keluar'}</span></td>
      <td class="mono amt ${t.type}" style="text-align:right">${t.type==='income'?'+':'-'}${fmt(t.amount)}</td>
      <td style="text-align:right"><button class="del" onclick="deleteTx(${t.id})" title="Hapus">✕</button></td>
    </tr>`).join('');
}

function renderRecurring(list){
  const el = document.getElementById('recurringList');
  if(!list.length){ el.innerHTML = '<div class="empty">Belum ada transaksi berulang</div>'; return; }
  el.innerHTML = list.map(r=>`
    <div class="rec-item">
      <div>
        <div>${r.category} <span class="tag ${r.type}" style="margin-left:4px">${r.type==='income'?'Masuk':'Keluar'}</span></div>
        <div class="meta">${freqLabel[r.frequency]} · berikutnya ${r.next_date}</div>
      </div>
      <div style="text-align:right">
        <div class="mono amt ${r.type}">${fmt(r.amount)}</div>
        <button class="del" onclick="deleteRecurring(${r.id})" title="Hentikan">✕</button>
      </div>
    </div>`).join('');
}

function renderCharts(trend, byCategory){
  const ctx1 = document.getElementById('trendChart');
  if(trendChart) trendChart.destroy();
  trendChart = new Chart(ctx1, {
    type:'bar',
    data:{ labels: trend.map(m=>m.label), datasets:[
      {label:'Pendapatan', data:trend.map(m=>m.income), backgroundColor:'#43C08A', borderRadius:5, maxBarThickness:26},
      {label:'Pengeluaran', data:trend.map(m=>m.expense), backgroundColor:'#F2735A', borderRadius:5, maxBarThickness:26}
    ]},
    options:{ responsive:true, maintainAspectRatio:false,
      plugins:{legend:{labels:{color:'#8B93A6', font:{size:11}}}},
      scales:{
        x:{ticks:{color:'#8B93A6', font:{size:11}}, grid:{color:'#242A38'}},
        y:{ticks:{color:'#8B93A6', font:{size:10}, callback:v=>(v/1000)+'k'}, grid:{color:'#242A38'}}
      }
    }
  });

  const labels = byCategory.map(c=>c.category);
  const palette = ['#F2735A','#5B8DEF','#E5B95C','#43C08A','#A472E8','#4FC7D1','#E86AA6','#8B93A6'];
  const ctx2 = document.getElementById('pieChart');
  if(pieChart) pieChart.destroy();
  pieChart = new Chart(ctx2, {
    type:'doughnut',
    data:{ labels: labels.length?labels:['Belum ada data'], datasets:[{
      data: labels.length? byCategory.map(c=>c.total) : [1],
      backgroundColor: labels.length? palette.slice(0,labels.length) : ['#242A38'],
      borderColor:'#131722', borderWidth:3
    }]},
    options:{ responsive:true, maintainAspectRatio:false,
      plugins:{legend:{position:'bottom', labels:{color:'#8B93A6', font:{size:10.5}, boxWidth:10, padding:10}}}
    }
  });
}

async function refreshAll(){
  const res = await fetch('api/data.php');
  const data = await res.json();
  renderSummary(data.summary);
  renderTable(data.transactions);
  renderRecurring(data.recurring);
  renderCharts(data.trend, data.byCategory);
}

document.getElementById('fDate').value = new Date().toISOString().slice(0,10);
loadCategories();
refreshAll();
