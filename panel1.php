<?php
// panel1.php — Panel 1: Temporal & Spatial Fatality Pattern
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NYC Crash Intelligence — Panel 1</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@2.0.1/dist/chartjs-chart-matrix.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#09090e;--surface:#111116;--surface2:#17171e;
  --border:rgba(255,255,255,0.06);--border2:rgba(255,255,255,0.11);
  --text:#ededf2;--muted:#55556a;--dim:#2a2a38;
  --red:#e63946;--red-bg:rgba(230,57,70,0.12);
  --amber:#f4a261;--blue:#457b9d;--blue-bg:rgba(69,123,157,0.15);
  --mono:'JetBrains Mono',monospace;
}
html,body{height:100%;overflow:hidden}
body{background:var(--bg);color:var(--text);font-family:'Inter',system-ui,sans-serif;font-size:13px;line-height:1.5}

header{height:46px;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 18px;gap:10px;flex-shrink:0}
header h1{font-size:13px;font-weight:500;letter-spacing:0.1px}
header .sep{color:var(--dim);font-size:16px;font-weight:200}
header .sub{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:0.8px;font-weight:400}
header .tag{margin-left:auto;font-family:var(--mono);font-size:9px;color:var(--red);background:var(--red-bg);border:1px solid rgba(230,57,70,0.18);padding:2px 7px;border-radius:2px;letter-spacing:1px}

.layout{display:flex;height:calc(100vh - 46px)}

aside{width:196px;flex-shrink:0;background:var(--surface);border-right:1px solid var(--border);padding:14px 12px;display:flex;flex-direction:column;gap:14px;overflow-y:auto}
.fg label{display:block;font-size:9px;font-weight:500;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:5px}
aside select{width:100%;background:var(--bg);border:1px solid var(--border2);color:var(--text);padding:5px 8px;border-radius:3px;font-family:'Inter',sans-serif;font-size:12px;appearance:none;cursor:pointer;outline:none}
aside select:focus{border-color:var(--blue)}
.fr{display:flex;gap:5px}
.fr select{flex:1}
.stats{border-top:1px solid var(--border);padding-top:12px;display:flex;flex-direction:column;gap:10px}
.stat .v{font-family:var(--mono);font-size:18px;font-weight:500;color:var(--text);letter-spacing:-0.5px}
.stat .v.r{color:var(--red)}
.stat .l{font-size:9px;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-top:1px}

main{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;min-width:0}

.card{background:var(--surface);border:1px solid var(--border);border-radius:5px;display:flex;flex-direction:column;overflow:hidden}
.ch{padding:9px 13px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.ch h2{font-size:10px;font-weight:500;text-transform:uppercase;letter-spacing:0.8px;color:var(--muted)}
.ch .cm{font-family:var(--mono);font-size:9px;color:var(--dim)}
.cb{flex:1;position:relative;min-height:0;overflow:hidden}

.top-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;height:520px;flex-shrink:0}
.bar-card{height:195px;flex-shrink:0}
.bot-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;height:230px;flex-shrink:0}

#map{width:100%;height:100%}
.leaflet-container{background:var(--bg)!important}

.si{padding:6px 13px;font-size:10px;color:var(--muted);border-top:1px solid var(--border);flex-shrink:0;font-family:var(--mono)}
.si span{color:var(--amber)}

.mleg{padding:5px 13px;display:flex;align-items:center;gap:8px;border-top:1px solid var(--border);flex-shrink:0}
.mleg-i{display:flex;align-items:center;gap:3px}
.mleg-d{width:7px;height:7px;border-radius:50%}
.mleg-l{font-size:9px;color:var(--muted)}

#crashList{overflow-y:auto;height:100%;padding:6px;display:flex;flex-direction:column;gap:3px}
.ci{background:var(--bg);border:1px solid var(--border);border-radius:3px;padding:6px 9px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;transition:border-color 0.1s}
.ci:hover{border-color:var(--border2)}
.ci.active{border-color:var(--red)}
.ci-b{font-size:12px;color:var(--text)}
.ci-s{font-family:var(--mono);font-size:10px;color:var(--muted)}
.bd{font-size:9px;font-family:var(--mono);padding:2px 5px;border-radius:2px;font-weight:500}
.bd-f{background:var(--red-bg);color:var(--red);border:1px solid rgba(230,57,70,0.25)}
.bd-i{background:var(--blue-bg);color:#6fb3d4;border:1px solid rgba(69,123,157,0.25)}

#crashDetail{overflow-y:auto;height:100%;padding:10px 12px;font-size:12px;color:var(--muted)}
.cd-empty{height:100%;display:flex;align-items:center;justify-content:center;font-size:10px;color:var(--dim);font-family:var(--mono)}
.cds{margin-bottom:10px}
.cds-t{font-size:9px;font-weight:500;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:5px;padding-bottom:4px;border-bottom:1px solid var(--border)}
.cdg{display:grid;grid-template-columns:1fr 1fr;gap:5px}
.cdkv .k{font-size:9px;color:var(--dim);text-transform:uppercase;letter-spacing:0.3px}
.cdkv .v{font-size:11px;color:var(--text);font-family:var(--mono)}
.cdkv .v.r{color:var(--red)}
.vb{background:var(--bg);border:1px solid var(--border);border-radius:3px;padding:6px 8px;margin-bottom:5px}
.vb-t{font-size:9px;color:var(--blue);font-family:var(--mono);margin-bottom:4px;font-weight:500}
.pr{display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid var(--border);font-size:10px}
.pr:last-child{border-bottom:none}
.lt{height:100%;display:flex;align-items:center;justify-content:center;font-size:10px;color:var(--dim);font-family:var(--mono)}

.leaflet-tooltip{background:var(--surface2)!important;border:1px solid var(--border2)!important;color:var(--text)!important;font-family:'Inter',sans-serif!important;font-size:11px!important;border-radius:3px!important;box-shadow:0 4px 16px rgba(0,0,0,0.6)!important}
.leaflet-tooltip::before{display:none!important}
.leaflet-tooltip b{color:var(--amber)}
</style>
</head>
<body>
<header>
  <h1>NYC Crash Intelligence</h1>
  <span class="sep">/</span>
  <span class="sub">Temporal &amp; Spatial Fatality Pattern</span>
  <span class="tag">PANEL 01</span>
</header>
<div class="layout">
<aside>
  <div class="fg"><label>Borough</label>
    <select id="filterBorough">
      <option value="ALL">All Boroughs</option>
      <option value="BROOKLYN">Brooklyn</option>
      <option value="QUEENS">Queens</option>
      <option value="MANHATTAN">Manhattan</option>
      <option value="BRONX">Bronx</option>
      <option value="STATEN ISLAND">Staten Island</option>
    </select>
  </div>
  <div class="fg"><label>Year Range</label>
    <div class="fr">
      <select id="filterYearStart"><?php for($y=2020;$y<=2025;$y++) echo "<option value='$y'".($y==2020?" selected":"").">$y</option>";?></select>
      <select id="filterYearEnd"><?php for($y=2020;$y<=2025;$y++) echo "<option value='$y'".($y==2025?" selected":"").">$y</option>";?></select>
    </div>
  </div>
  <div class="fg"><label>Heatmap Mode</label>
    <select id="colorMode">
      <option value="killed">By Fatalities</option>
      <option value="crash">By Total Crashes</option>
    </select>
  </div>
  <div class="stats">
    <div class="stat"><div class="v" id="statTotalCrash">—</div><div class="l">Total Crashes</div></div>
    <div class="stat"><div class="v r" id="statTotalKilled">—</div><div class="l">Total Killed</div></div>
    <div class="stat"><div class="v" id="statPeakSlot">—</div><div class="l">Peak Fatal Slot</div></div>
  </div>
</aside>
<main>
  <div class="top-row">
    <div class="card">
      <div class="ch"><h2>Crash Heatmap — Hour × Day</h2><span class="cm">24 × 7</span></div>
      <div class="cb"><canvas id="heatmapCanvas"></canvas></div>
      <div class="si" id="slotInfo">click a cell to drill down</div>
    </div>
    <div class="card">
      <div class="ch"><h2>Fatality Rate per Borough</h2><span class="cm">killed / 1,000</span></div>
      <div class="cb"><div id="map"></div></div>
      <div class="mleg">
        <div class="mleg-i"><div class="mleg-d" style="background:#1d3557"></div><span class="mleg-l">Low</span></div>
        <div class="mleg-i"><div class="mleg-d" style="background:#457b9d"></div><span class="mleg-l">Med</span></div>
        <div class="mleg-i"><div class="mleg-d" style="background:#f4a261"></div><span class="mleg-l">High</span></div>
        <div class="mleg-i"><div class="mleg-d" style="background:#e63946"></div><span class="mleg-l">Critical</span></div>
        <span style="margin-left:auto;font-size:9px;color:var(--dim)">click to filter</span>
      </div>
    </div>
  </div>
  <div class="card bar-card">
    <div class="ch"><h2 id="barTitle">Top Contributing Factors</h2><span class="cm" id="barMeta">all boroughs</span></div>
    <div class="cb"><canvas id="barCanvas"></canvas></div>
  </div>
  <div class="bot-row">
    <div class="card">
      <div class="ch"><h2 id="listTitle">Crash List</h2><span class="cm">top 50 by severity</span></div>
      <div class="cb"><div id="crashList"><div class="lt">select a heatmap cell</div></div></div>
    </div>
    <div class="card">
      <div class="ch"><h2>Crash Detail</h2><span class="cm" id="detailMeta">crash_events · mongodb</span></div>
      <div class="cb"><div id="crashDetail"><div class="cd-empty">select a crash from the list</div></div></div>
    </div>
  </div>
</main>
</div>
<script>
const state={borough:'ALL',yearStart:2020,yearEnd:2025,colorMode:'killed',activeHour:null,activeDay:null};
const DAYS=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
function getFilters(){return new URLSearchParams({borough:state.borough,year_start:state.yearStart,year_end:state.yearEnd})}

// ── COLORS ──
function heatColor(val,max){
  if(!max||val===0) return 'rgba(15,15,22,0.8)';
  const t=Math.pow(Math.min(val/max,1),0.4);
  // black → dark blue → blue → amber → red
  if(t<0.25){const s=t/0.25;return `rgba(${Math.round(s*29)},${Math.round(s*53)},${Math.round(22+s*65)},0.9)`}
  if(t<0.5){const s=(t-0.25)/0.25;return `rgba(${Math.round(29+s*40)},${Math.round(53+s*70)},${Math.round(87-s*10)},0.9)`}
  if(t<0.75){const s=(t-0.5)/0.25;return `rgba(${Math.round(69+s*175)},${Math.round(123+s*36)},${Math.round(77-s*74)},0.9)`}
  const s=(t-0.75)/0.25;
  return `rgba(${Math.round(244-s*14)},${Math.round(159-s*102)},${Math.round(3+s*24)},0.9)`;
}
function choroplethColor(r){
  if(r>=2.5)return '#e63946';
  if(r>=1.8)return '#f4a261';
  if(r>=1.2)return '#e9c46a';
  if(r>=0.8)return '#457b9d';
  if(r>=0.4)return '#2d6a8f';
  return '#1d3557';
}

// ── HEATMAP ──
let heatmapChart=null;
async function loadHeatmap(){
  const res=await fetch('api/get_heatmap.php?'+getFilters());
  const data=await res.json();
  const matrix={};
  let maxK=0,maxC=0,totC=0,totK=0,peakV=0,peakL='—';
  data.forEach(r=>{
    matrix[`${r.col_hour}_${r.day_of_week}`]=r;
    const k=+r.total_killed,c=+r.total_crash;
    if(k>maxK)maxK=k; if(c>maxC)maxC=c;
    totC+=c; totK+=k;
    if(k>peakV){peakV=k;peakL=`${(r.day_of_week||'').slice(0,3)} ${r.col_hour}:00`}
  });
  document.getElementById('statTotalCrash').textContent=totC.toLocaleString();
  document.getElementById('statTotalKilled').textContent=totK.toLocaleString();
  document.getElementById('statPeakSlot').textContent=peakL;

  const useK=state.colorMode==='killed';
  const maxV=useK?maxK:maxC;
  const md=[];
  for(let h=0;h<24;h++) for(let d=0;d<7;d++){
    const r=matrix[`${h}_${DAYS[d]}`]||{total_killed:0,total_crash:0};
    md.push({x:d,y:h,v:useK?+r.total_killed:+r.total_crash,killed:+r.total_killed,crash:+r.total_crash});
  }
  const ctx=document.getElementById('heatmapCanvas').getContext('2d');
  if(heatmapChart)heatmapChart.destroy();
  heatmapChart=new Chart(ctx,{
    type:'matrix',
    data:{datasets:[{
      data:md,
      backgroundColor(c){const v=c.dataset.data[c.dataIndex];return heatColor(v.v,maxV)},
      borderColor:'rgba(9,9,14,0.8)',borderWidth:1,
      width:({chart})=>(chart.chartArea?.width||420)/7-1.2,
      height:({chart})=>(chart.chartArea?.height||560)/24-0.8,
    }]},
    options:{
      responsive:true,maintainAspectRatio:false,animation:{duration:250},
      plugins:{
        legend:{display:false},
        tooltip:{
          backgroundColor:'#17171e',borderColor:'rgba(255,255,255,0.08)',borderWidth:1,
          titleColor:'#f4a261',bodyColor:'#55556a',
          titleFont:{family:'JetBrains Mono',size:11},bodyFont:{family:'JetBrains Mono',size:10},
          callbacks:{
            title:i=>`${DAYS[i[0].raw.x]}  ${String(i[0].raw.y).padStart(2,'0')}:00`,
            label:i=>[`crashes : ${i.raw.crash.toLocaleString()}`,`killed  : ${i.raw.killed}`]
          }
        }
      },
      scales:{
        x:{type:'linear',min:-0.5,max:6.5,position:'top',
          grid:{color:'rgba(255,255,255,0.03)',drawBorder:false},
          ticks:{stepSize:1,callback:v=>['Mon','Tue','Wed','Thu','Fri','Sat','Sun'][v]??'',
            color:'#55556a',font:{family:'Inter',size:10,weight:'500'}}},
        y:{type:'linear',min:-0.5,max:23.5,reverse:true,
          grid:{color:'rgba(255,255,255,0.03)',drawBorder:false},
          ticks:{stepSize:1,callback:v=>Number.isInteger(v)?String(v).padStart(2,'0')+':00':'',
            color:'#3a3a50',font:{family:'JetBrains Mono',size:9}}}
      },
      onClick(event,elements){
        if(!elements.length)return;
        const d=elements[0].element.$context.raw;
        state.activeHour=d.y; state.activeDay=DAYS[d.x];
        document.getElementById('slotInfo').innerHTML=
          `<span>${state.activeDay}</span> · <span>${String(state.activeHour).padStart(2,'0')}:00</span> — ${d.crash.toLocaleString()} crashes · ${d.killed} killed`;
        loadCrashList(); loadBarChart();
      }
    }
  });
}

// ── CHOROPLETH ──
let map=null,geojsonLayer=null,boroughData={};
async function initMap(){
  map=L.map('map',{zoomControl:false,attributionControl:false}).setView([40.65,-73.97],10);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png',{maxZoom:19,subdomains:'abcd'}).addTo(map);

  const res=await fetch('api/get_choropleth.php');
  const data=await res.json();
  data.forEach(r=>{boroughData[r.borough]=r});

  const gjRes=await fetch('data/nyc_boroughs.geojson');
  const gjData=await gjRes.json();
  const sp=gjData.features[0]?.properties||{};
  const nk=sp.boro_name!==undefined?'boro_name':sp.name!==undefined?'name':sp.borough!==undefined?'borough':Object.keys(sp)[0];

  geojsonLayer=L.geoJSON(gjData,{
    style(f){
      const nm=((f.properties[nk]||'').toUpperCase().replace('THE BRONX','BRONX').trim());
      const d=boroughData[nm]||{};
      return{fillColor:choroplethColor(+d.fatality_rate_per_1000||0),fillOpacity:0.6,weight:1,color:'rgba(255,255,255,0.12)'};
    },
    onEachFeature(f,layer){
      const raw=f.properties[nk]||'';
      const nm=(raw.toUpperCase().replace('THE BRONX','BRONX').trim());
      const d=boroughData[nm]||{};
      const rate=(+d.fatality_rate_per_1000||0).toFixed(2);
      layer.bindTooltip(`<b>${raw}</b><br>${(+d.total_crash||0).toLocaleString()} crashes<br>${rate} killed/1,000`,{sticky:true,offset:[10,0]});
      layer.on('click',()=>{state.borough=nm;document.getElementById('filterBorough').value=nm;refreshAll()});
      layer.on('mouseover',()=>layer.setStyle({fillOpacity:0.9,weight:2,color:'rgba(255,255,255,0.5)'}));
      layer.on('mouseout',()=>geojsonLayer.resetStyle(layer));
    }
  }).addTo(map);
  map.fitBounds(geojsonLayer.getBounds(),{padding:[8,8]});
}

// ── BAR CHART ──
let barChart=null;
async function loadBarChart(){
  const p=getFilters();
  if(state.activeHour!==null)p.set('hour',state.activeHour);
  if(state.activeDay!==null)p.set('day',state.activeDay);
  const res=await fetch('api/get_factor_chart.php?'+p);
  const data=await res.json();
  const labels=data.map(r=>r.factor_name);
  const counts=data.map(r=>+r.crash_count);
  const fPcts=data.map(r=>+r.fatal_pct);
  const colors=fPcts.map(p=>p>=1.5?'#e63946':p>=0.5?'#f4a261':'#457b9d');
  document.getElementById('barMeta').textContent=
    state.activeDay?`${state.activeDay.toLowerCase()} · ${String(state.activeHour).padStart(2,'0')}:00`
    :`${state.borough==='ALL'?'all boroughs':state.borough.toLowerCase()} · ${state.yearStart}–${state.yearEnd}`;
  const cx=document.getElementById('barCanvas').getContext('2d');
  if(barChart)barChart.destroy();
  barChart=new Chart(cx,{
    type:'bar',
    data:{labels,datasets:[{data:counts,backgroundColor:colors,borderRadius:2,borderSkipped:false}]},
    options:{
      indexAxis:'y',responsive:true,maintainAspectRatio:false,animation:{duration:200},
      plugins:{
        legend:{display:false},
        tooltip:{
          backgroundColor:'#17171e',borderColor:'rgba(255,255,255,0.08)',borderWidth:1,
          titleColor:'#ededf2',bodyColor:'#55556a',
          titleFont:{family:'JetBrains Mono',size:10},bodyFont:{family:'JetBrains Mono',size:10},
          callbacks:{label:i=>`${counts[i.dataIndex].toLocaleString()} crashes  ·  ${fPcts[i.dataIndex].toFixed(2)}% fatal`}
        }
      },
      scales:{
        x:{grid:{color:'rgba(255,255,255,0.04)',drawBorder:false},ticks:{color:'#3a3a50',font:{family:'JetBrains Mono',size:9}}},
        y:{grid:{display:false},ticks:{color:'#909090',font:{family:'Inter',size:11}}}
      },
      onClick(event,elements){if(elements.length)sessionStorage.setItem('highlightFactor',labels[elements[0].index])}
    }
  });
}

// ── CRASH LIST ──
async function loadCrashList(){
  if(state.activeHour===null)return;
  const p=getFilters();p.set('hour',state.activeHour);p.set('day',state.activeDay);
  document.getElementById('crashList').innerHTML='<div class="lt">loading...</div>';
  document.getElementById('listTitle').textContent=`${state.activeDay}  ${String(state.activeHour).padStart(2,'0')}:00`;
  const res=await fetch('api/get_crash_list.php?'+p);
  const data=await res.json();
  if(!data.length){document.getElementById('crashList').innerHTML='<div class="lt">no crashes found</div>';return}
  document.getElementById('crashList').innerHTML=data.map(r=>`
    <div class="ci" onclick="loadCrashDetail(${r.collision_id})" data-cid="${r.collision_id}">
      <div><div class="ci-b">${r.borough}</div><div class="ci-s">#${r.collision_id} · ${r.col_time}</div></div>
      ${+r.people_killed>0
        ?`<span class="bd bd-f">${r.people_killed} killed</span>`
        :`<span class="bd bd-i">${r.people_injured} inj</span>`}
    </div>`).join('');
}

// ── CRASH DETAIL ──
async function loadCrashDetail(cid){
  document.querySelectorAll('.ci').forEach(e=>e.classList.remove('active'));
  document.querySelector(`[data-cid="${cid}"]`)?.classList.add('active');
  document.getElementById('crashDetail').innerHTML='<div class="lt">fetching from mongodb...</div>';
  const res=await fetch(`api/get_crash_detail.php?id=${cid}`);
  const doc=await res.json();
  if(!doc||doc.error){document.getElementById('crashDetail').innerHTML='<div class="cd-empty">not found in crash_events</div>';return}
  const vH=(doc.vehicles||[]).map((v,i)=>`
    <div class="vb"><div class="vb-t">V${i+1} · ${v.vehicle_type||'—'} · ${v.vehicle_make||'—'}</div>
    <div class="cdg">
      <div class="cdkv"><div class="k">Pre-collision</div><div class="v">${v.pre_collision||'—'}</div></div>
      <div class="cdkv"><div class="k">Impact</div><div class="v">${v.point_of_impact||'—'}</div></div>
      <div class="cdkv"><div class="k">License</div><div class="v">${v.driver_license_status||'—'}</div></div>
      <div class="cdkv"><div class="k">Year</div><div class="v">${v.vehicle_year||'—'}</div></div>
    </div></div>`).join('');
  const pH=(doc.persons||[]).map(p=>`
    <div class="pr"><span>${p.person_type} · ${p.age_group} · ${p.person_sex}</span>
    <span style="color:${p.person_injury==='Injured'?'var(--red)':'var(--muted)'}">${p.person_injury}</span></div>`).join('');
  document.getElementById('crashDetail').innerHTML=`
    <div class="cds"><div class="cds-t">Crash #${doc.collision_id}</div>
    <div class="cdg">
      <div class="cdkv"><div class="k">Borough</div><div class="v">${doc.borough}</div></div>
      <div class="cdkv"><div class="k">Hour</div><div class="v">${String(doc.crash_hour).padStart(2,'0')}:00</div></div>
      <div class="cdkv"><div class="k">Killed</div><div class="v r">${doc.people_killed}</div></div>
      <div class="cdkv"><div class="k">Injured</div><div class="v">${doc.people_injured}</div></div>
      <div class="cdkv" style="grid-column:1/-1"><div class="k">Factor</div><div class="v" style="font-size:11px;white-space:normal">${doc.contributing_factor}</div></div>
    </div></div>
    ${vH?`<div class="cds"><div class="cds-t">${(doc.vehicles||[]).length} vehicle(s)</div>${vH}</div>`:''}
    ${pH?`<div class="cds"><div class="cds-t">${(doc.persons||[]).length} person(s)</div>${pH}</div>`:''}`;
}

// ── REFRESH ──
function refreshAll(){
  state.activeHour=null;state.activeDay=null;
  document.getElementById('slotInfo').textContent='click a cell to drill down';
  document.getElementById('crashList').innerHTML='<div class="lt">select a heatmap cell</div>';
  document.getElementById('crashDetail').innerHTML='<div class="cd-empty">select a crash from the list</div>';
  loadHeatmap();loadBarChart();
}

document.getElementById('filterBorough').addEventListener('change',e=>{state.borough=e.target.value;refreshAll()});
document.getElementById('filterYearStart').addEventListener('change',e=>{state.yearStart=+e.target.value;refreshAll()});
document.getElementById('filterYearEnd').addEventListener('change',e=>{state.yearEnd=+e.target.value;refreshAll()});
document.getElementById('colorMode').addEventListener('change',e=>{state.colorMode=e.target.value;loadHeatmap()});

(async()=>{await initMap();await loadHeatmap();await loadBarChart()})();
</script>
</body>
</html>