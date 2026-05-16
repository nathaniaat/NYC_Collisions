<?php
// panel1.php — Panel 1: Temporal & Spatial Fatality Pattern
// NYC Crash Intelligence Dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NYC Crash Intelligence — Panel 1</title>

<!-- Leaflet.js for choropleth map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Chart.js for heatmap + bar chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- chartjs-chart-matrix plugin for heatmap -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@2.0.1/dist/chartjs-chart-matrix.min.js"></script>

<style>
  :root {
    --bg:        #0d1117;
    --surface:   #161b22;
    --border:    #30363d;
    --text:      #e6edf3;
    --muted:     #8b949e;
    --accent:    #f78166;
    --accent2:   #79c0ff;
    --fatal-low: #1f4e79;
    --fatal-high:#c0392b;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 14px;
    min-height: 100vh;
  }

  /* ── Header ── */
  header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 14px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
  }
  header h1 { font-size: 18px; font-weight: 600; letter-spacing: 0.3px; }
  header span { color: var(--accent); font-size: 12px; font-weight: 500;
    background: rgba(247,129,102,0.12); padding: 3px 8px; border-radius: 4px; }

  /* ── Layout ── */
  .layout { display: flex; height: calc(100vh - 53px); }

  /* ── Sidebar ── */
  aside {
    width: 220px;
    flex-shrink: 0;
    background: var(--surface);
    border-right: 1px solid var(--border);
    padding: 20px 16px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    overflow-y: auto;
  }
  aside h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
    color: var(--muted); margin-bottom: 8px; }
  aside select, aside input[type=range] {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 7px 10px;
    border-radius: 6px;
    font-size: 13px;
  }
  aside select:focus { outline: 1px solid var(--accent2); }
  .range-row { display: flex; justify-content: space-between; font-size: 12px;
    color: var(--muted); margin-top: 4px; }

  .stat-pill {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 12px;
  }
  .stat-pill .val { font-size: 22px; font-weight: 700; color: var(--accent); }
  .stat-pill .lbl { font-size: 11px; color: var(--muted); margin-top: 2px; }

  /* ── Main content ── */
  main {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  /* ── Top row: heatmap + choropleth ── */
  .top-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    height: 520px;
  }

  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 16px;
    display: flex;
    flex-direction: column;
  }
  .card h2 {
    font-size: 13px;
    font-weight: 600;
    color: var(--muted);
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .card-body { flex: 1; position: relative; min-height: 0; }

  #map { width: 100%; height: 100%; border-radius: 6px; }

  /* ── Bar chart row ── */
  .bar-card { height: 240px; }

  /* ── Crash list + detail row ── */
  .bottom-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }
  .crash-list-card { height: 260px; }
  .crash-detail-card { height: 260px; }

  /* crash list items */
  #crashList {
    overflow-y: auto;
    height: 190px;
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .crash-item {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: border-color 0.15s;
  }
  .crash-item:hover { border-color: var(--accent2); }
  .crash-item.active { border-color: var(--accent); }
  .crash-item .cid { font-size: 12px; color: var(--muted); }
  .crash-item .meta { font-size: 13px; }
  .badge-fatal { background: rgba(192,57,43,0.2); color: #e74c3c;
    border-radius: 4px; padding: 2px 6px; font-size: 11px; font-weight: 600; }
  .badge-inj   { background: rgba(247,129,102,0.15); color: var(--accent);
    border-radius: 4px; padding: 2px 6px; font-size: 11px; }

  /* crash detail */
  #crashDetail {
    overflow-y: auto;
    height: 190px;
    font-size: 12px;
    color: var(--muted);
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
  }
  .detail-section { margin-bottom: 10px; }
  .detail-section h4 { color: var(--accent2); font-size: 11px;
    text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
  .detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px 12px;
  }
  .detail-kv { display: flex; flex-direction: column; }
  .detail-kv .k { font-size: 10px; color: var(--muted); }
  .detail-kv .v { font-size: 12px; color: var(--text); }

  /* slot info bar */
  #slotInfo {
    font-size: 12px;
    color: var(--muted);
    padding: 6px 0;
    min-height: 24px;
  }
  #slotInfo span { color: var(--accent2); font-weight: 600; }

  /* loading overlay */
  .loading { color: var(--muted); font-size: 12px; text-align: center;
    padding: 20px; }

  /* legend */
  .legend {
    display: flex; gap: 12px; align-items: center;
    font-size: 11px; color: var(--muted); margin-top: 6px;
  }
  .legend-swatch {
    width: 12px; height: 12px; border-radius: 2px; display: inline-block;
  }
</style>
</head>
<body>

<header>
  <h1>NYC Crash Intelligence</h1>
  <span>Panel 1 — Temporal &amp; Spatial Fatality Pattern</span>
</header>

<div class="layout">

  <!-- ── Sidebar ── -->
  <aside>
    <div>
      <h3>Borough</h3>
      <select id="filterBorough">
        <option value="ALL">All Boroughs</option>
        <option value="BROOKLYN">Brooklyn</option>
        <option value="QUEENS">Queens</option>
        <option value="MANHATTAN">Manhattan</option>
        <option value="BRONX">Bronx</option>
        <option value="STATEN ISLAND">Staten Island</option>
      </select>
    </div>

    <div>
      <h3>Year Range</h3>
      <label style="font-size:12px;color:var(--muted)">From</label>
      <select id="filterYearStart">
        <?php for($y=2020;$y<=2025;$y++) echo "<option value='$y'" . ($y==2020?" selected":"") . ">$y</option>"; ?>
      </select>
      <label style="font-size:12px;color:var(--muted);margin-top:6px;display:block">To</label>
      <select id="filterYearEnd">
        <?php for($y=2020;$y<=2025;$y++) echo "<option value='$y'" . ($y==2025?" selected":"") . ">$y</option>"; ?>
      </select>
    </div>

    <div>
      <h3>Color Mode</h3>
      <select id="colorMode">
        <option value="killed">By Fatalities</option>
        <option value="crash">By Total Crash</option>
      </select>
    </div>

    <div class="stat-pill">
      <div class="val" id="statTotalCrash">—</div>
      <div class="lbl">Total Crashes</div>
    </div>
    <div class="stat-pill">
      <div class="val" id="statTotalKilled">—</div>
      <div class="lbl">Total Killed</div>
    </div>
    <div class="stat-pill">
      <div class="val" id="statPeakSlot">—</div>
      <div class="lbl">Peak Fatal Slot</div>
    </div>
  </aside>

  <!-- ── Main ── -->
  <main>

    <!-- Row 1: heatmap + choropleth -->
    <div class="top-row">

      <div class="card">
        <h2>Crash Heatmap — Hour × Day</h2>
        <div class="card-body">
          <canvas id="heatmapCanvas"></canvas>
        </div>
        <div id="slotInfo">Click a cell to explore crashes at that time slot</div>
      </div>

      <div class="card">
        <h2>Fatality Rate per Borough</h2>
        <div class="card-body">
          <div id="map"></div>
        </div>
        <div class="legend">
          <span class="legend-swatch" style="background:var(--fatal-low)"></span> Low
          <span class="legend-swatch" style="background:#e67e22"></span> Mid
          <span class="legend-swatch" style="background:var(--fatal-high)"></span> High
          <span style="margin-left:auto;font-size:10px">per 1,000 crashes</span>
        </div>
      </div>

    </div>

    <!-- Row 2: bar chart -->
    <div class="card bar-card">
      <h2 id="barTitle">Top Contributing Factors — All Boroughs</h2>
      <div class="card-body">
        <canvas id="barCanvas"></canvas>
      </div>
    </div>

    <!-- Row 3: crash list + detail -->
    <div class="bottom-row">

      <div class="card crash-list-card">
        <h2 id="listTitle">Crash List — Click a heatmap cell</h2>
        <div id="crashList">
          <div class="loading">Select a heatmap cell to see crash list</div>
        </div>
      </div>

      <div class="card crash-detail-card">
        <h2>Crash Detail</h2>
        <div id="crashDetail">
          <div>Click a crash from the list to see full detail</div>
        </div>
      </div>

    </div>

  </main>
</div>

<script>
// ════════════════════════════════════════════════
// STATE
// ════════════════════════════════════════════════
const state = {
  borough:   'ALL',
  yearStart: 2020,
  yearEnd:   2025,
  colorMode: 'killed',
  activeHour: null,
  activeDay:  null,
  activeCid:  null,
};

// ════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════
const DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

function getFilters() {
  return new URLSearchParams({
    borough:    state.borough,
    year_start: state.yearStart,
    year_end:   state.yearEnd,
  });
}

function fatalColor(rate) {
  // rate = fatality_rate_per_1000
  if (rate >= 10) return '#c0392b';
  if (rate >= 7)  return '#e74c3c';
  if (rate >= 4)  return '#e67e22';
  if (rate >= 2)  return '#2980b9';
  return '#1f4e79';
}

function heatColor(val, max) {
  const t = Math.min(val / (max || 1), 1);
  const r = Math.round(31  + t * (192 - 31));
  const g = Math.round(78  + t * (57  - 78));
  const b = Math.round(121 + t * (43  - 121));
  return `rgba(${r},${g},${b},0.85)`;
}

// ════════════════════════════════════════════════
// HEATMAP (Chart.js matrix)
// ════════════════════════════════════════════════
let heatmapChart = null;

async function loadHeatmap() {
  const p = getFilters();
  const res = await fetch('api/get_heatmap.php?' + p);
  const data = await res.json();

  // Build 24×7 matrix
  const matrix = {};
  let maxKilled = 0, maxCrash = 0;
  let totalCrash = 0, totalKilled = 0;
  let peakVal = 0, peakLabel = '—';

  data.forEach(row => {
    const key = `${row.col_hour}_${row.day_of_week}`;
    matrix[key] = row;
    maxKilled = Math.max(maxKilled, +row.total_killed);
    maxCrash  = Math.max(maxCrash,  +row.total_crash);
    totalCrash  += +row.total_crash;
    totalKilled += +row.total_killed;
    if (+row.total_killed > peakVal) {
      peakVal   = +row.total_killed;
      peakLabel = `${row.day_of_week.slice(0,3)} ${row.col_hour}:00`;
    }
  });

  // Update sidebar stats
  document.getElementById('statTotalCrash').textContent  = totalCrash.toLocaleString();
  document.getElementById('statTotalKilled').textContent = totalKilled.toLocaleString();
  document.getElementById('statPeakSlot').textContent    = peakLabel;

  // Build dataset for chartjs-chart-matrix
  const datasets = [];
  const matrixData = [];
  const useKilled  = state.colorMode === 'killed';
  const maxVal     = useKilled ? maxKilled : maxCrash;

  for (let h = 0; h < 24; h++) {
    for (let d = 0; d < 7; d++) {
      const row = matrix[`${h}_${DAYS[d]}`] || { total_killed: 0, total_crash: 0 };
      const val = useKilled ? +row.total_killed : +row.total_crash;
      matrixData.push({
        x: d,
        y: h,
        v: val,
        killed: +row.total_killed,
        crash:  +row.total_crash,
      });
    }
  }

  const ctx = document.getElementById('heatmapCanvas').getContext('2d');
  if (heatmapChart) heatmapChart.destroy();

  heatmapChart = new Chart(ctx, {
    type: 'matrix',
    data: {
      datasets: [{
        label: 'Crashes',
        data: matrixData,
        backgroundColor(ctx) {
          const v = ctx.dataset.data[ctx.dataIndex];
          return heatColor(v.v, maxVal);
        },
        borderColor: '#0d1117',
        borderWidth: 1,
        width:  ({ chart }) => (chart.chartArea?.width  || 400) / 7  - 1,
        height: ({ chart }) => (chart.chartArea?.height || 600) / 24 - 1,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title(items) {
              const d = items[0].raw;
              return `${DAYS[d.x]}, ${d.y}:00`;
            },
            label(item) {
              const d = item.raw;
              return [`${d.crash.toLocaleString()} crashes`, `${d.killed} killed`];
            }
          }
        }
      },
      scales: {
        x: {
          type: 'linear', min: -0.5, max: 6.5,
          position: 'top',
          ticks: {
            stepSize: 1,
            callback: v => DAYS[v] ?? '',
            color: '#e6edf3', font: { size: 11, weight: '600' }
          },
          grid: { color: '#21262d' }
        },
        y: {
          type: 'linear', min: -0.5, max: 23.5,
          reverse: true,
          ticks: {
            stepSize: 1,
            callback: v => Number.isInteger(v) ? `${String(v).padStart(2,'0')}:00` : '',
            color: '#8b949e', font: { size: 10 }
          },
          grid: { color: '#21262d' }
        }
      },
      onClick(event, elements) {
        if (!elements.length) return;
        const d = elements[0].element.$context.raw;
        state.activeHour = d.y;
        state.activeDay  = DAYS[d.x];
        document.getElementById('slotInfo').innerHTML =
          `Showing crashes on <span>${state.activeDay}</span> at <span>${state.activeHour}:00</span>`;
        loadCrashList();
        loadBarChart();
      }
    }
  });
}

// ════════════════════════════════════════════════
// CHOROPLETH (Leaflet.js)
// ════════════════════════════════════════════════
let map = null;
let geojsonLayer = null;
let boroughData  = {};

async function initMap() {
  map = L.map('map', { zoomControl: true, attributionControl: false })
           .setView([40.70, -73.94], 10);

  // Dark tile layer
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    maxZoom: 19,
    subdomains: 'abcd'
  }).addTo(map);

  // Load borough fatality data from PostgreSQL
  const res  = await fetch('api/get_choropleth.php');
  const data = await res.json();
  data.forEach(r => { boroughData[r.borough] = r; });

  // Load NYC GeoJSON
  const gjRes  = await fetch('data/nyc_boroughs.geojson');
  const gjData = await gjRes.json();

  geojsonLayer = L.geoJSON(gjData, {
    style(feature) {
      const name = feature.properties.boro_name?.toUpperCase()
                    .replace('THE BRONX','BRONX');
      const d    = boroughData[name] || {};
      return {
        fillColor:   fatalColor(+d.fatality_rate_per_1000 || 0),
        fillOpacity: 0.75,
        weight: 2,
        color: '#ffffff',
      };
    },
    onEachFeature(feature, layer) {
      const name = feature.properties.boro_name?.toUpperCase()
                    .replace('THE BRONX','BRONX');
      const d    = boroughData[name] || {};
      layer.bindTooltip(
        `<b>${feature.properties.boro_name}</b><br>
         ${(+d.total_crash||0).toLocaleString()} crashes<br>
         ${d.fatality_rate_per_1000 || 0} killed/1,000`,
        { sticky: true }
      );
      layer.on('click', () => {
        state.borough = name;
        document.getElementById('filterBorough').value = name;
        refreshAll();
      });
      layer.on('mouseover', () => layer.setStyle({ fillOpacity: 0.95, weight: 3 }));
      layer.on('mouseout',  () => geojsonLayer.resetStyle(layer));
    }
  }).addTo(map);
}

// ════════════════════════════════════════════════
// BAR CHART (contributing factors)
// ════════════════════════════════════════════════
let barChart = null;

async function loadBarChart() {
  const p = getFilters();
  if (state.activeHour !== null) p.set('hour', state.activeHour);
  if (state.activeDay  !== null) p.set('day',  state.activeDay);

  const res  = await fetch('api/get_factor_chart.php?' + p);
  const data = await res.json();

  const labels     = data.map(r => r.factor_name);
  const counts     = data.map(r => +r.crash_count);
  const fatalPcts  = data.map(r => +r.fatal_pct);
  const colors     = fatalPcts.map(p => {
    if (p >= 8) return '#c0392b';
    if (p >= 5) return '#e74c3c';
    if (p >= 3) return '#378add';
    return '#85b7eb';
  });

  // Update title
  const ctx = document.getElementById('barTitle');
  ctx.textContent = state.activeDay
    ? `Top Factors — ${state.activeDay} ${state.activeHour}:00`
    : `Top Contributing Factors — ${state.borough === 'ALL' ? 'All Boroughs' : state.borough}`;

  const canvasCtx = document.getElementById('barCanvas').getContext('2d');
  if (barChart) barChart.destroy();

  barChart = new Chart(canvasCtx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        data: counts,
        backgroundColor: colors,
        borderRadius: 4,
        borderSkipped: false,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label(item) {
              const i = item.dataIndex;
              return [`${counts[i].toLocaleString()} crashes`, `${fatalPcts[i].toFixed(1)}% fatal`];
            }
          }
        }
      },
      scales: {
        x: { grid: { color: '#21262d' }, ticks: { color: '#8b949e' } },
        y: { grid: { display: false },   ticks: { color: '#e6edf3', font: { size: 11 } } }
      },
      onClick(event, elements) {
        if (!elements.length) return;
        const factor = labels[elements[0].index];
        // Cross-panel: store for Panel 2 highlight
        sessionStorage.setItem('highlightFactor', factor);
        // Visual feedback
        document.getElementById('barTitle').textContent += ` → "${factor}" selected`;
      }
    }
  });
}

// ════════════════════════════════════════════════
// CRASH LIST (from PostgreSQL)
// ════════════════════════════════════════════════
async function loadCrashList() {
  if (state.activeHour === null) return;

  const p = getFilters();
  p.set('hour', state.activeHour);
  p.set('day',  state.activeDay);

  document.getElementById('crashList').innerHTML = '<div class="loading">Loading...</div>';
  document.getElementById('listTitle').textContent =
    `Crashes — ${state.activeDay} ${state.activeHour}:00`;

  const res  = await fetch('api/get_crash_list.php?' + p);
  const data = await res.json();

  if (!data.length) {
    document.getElementById('crashList').innerHTML = '<div class="loading">No crashes found</div>';
    return;
  }

  document.getElementById('crashList').innerHTML = data.map(r => `
    <div class="crash-item" onclick="loadCrashDetail(${r.collision_id})" data-cid="${r.collision_id}">
      <div>
        <div class="meta">#${r.collision_id} · ${r.borough}</div>
        <div class="cid">${r.col_time} · ${r.people_injured} injured</div>
      </div>
      <div>
        ${+r.people_killed > 0
          ? `<span class="badge-fatal">${r.people_killed} killed</span>`
          : `<span class="badge-inj">${r.people_injured} injured</span>`}
      </div>
    </div>
  `).join('');
}

// ════════════════════════════════════════════════
// CRASH DETAIL (from MongoDB)
// ════════════════════════════════════════════════
async function loadCrashDetail(cid) {
  // Highlight active item
  document.querySelectorAll('.crash-item').forEach(el => el.classList.remove('active'));
  document.querySelector(`[data-cid="${cid}"]`)?.classList.add('active');

  document.getElementById('crashDetail').innerHTML = '<div class="loading">Loading from MongoDB...</div>';

  const res = await fetch(`api/get_crash_detail.php?id=${cid}`);
  const doc = await res.json();

  if (!doc || doc.error) {
    document.getElementById('crashDetail').innerHTML = '<div class="loading">Detail not found</div>';
    return;
  }

  const vCount = doc.vehicles?.length || 0;
  const pCount = doc.persons?.length  || 0;

  const vehicleHtml = (doc.vehicles || []).map((v, i) => `
    <div style="background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:8px;margin-bottom:6px;">
      <div style="color:var(--accent2);font-size:10px;font-weight:600;margin-bottom:4px">
        Vehicle ${i+1}: ${v.vehicle_type} ${v.vehicle_make}
      </div>
      <div class="detail-grid">
        <div class="detail-kv"><span class="k">Pre-collision</span><span class="v">${v.pre_collision}</span></div>
        <div class="detail-kv"><span class="k">Point of impact</span><span class="v">${v.point_of_impact}</span></div>
        <div class="detail-kv"><span class="k">License status</span><span class="v">${v.driver_license_status}</span></div>
        <div class="detail-kv"><span class="k">Year</span><span class="v">${v.vehicle_year}</span></div>
      </div>
    </div>
  `).join('');

  const personHtml = (doc.persons || []).map((p, i) => `
    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--border);">
      <span>${p.person_type} · ${p.age_group} · ${p.person_sex}</span>
      <span style="color:${p.person_injury==='Injured'?'var(--accent)':'var(--muted)'}">${p.person_injury}</span>
    </div>
  `).join('');

  document.getElementById('crashDetail').innerHTML = `
    <div style="width:100%">
      <div class="detail-section">
        <h4>Crash #${doc.collision_id}</h4>
        <div class="detail-grid">
          <div class="detail-kv"><span class="k">Borough</span><span class="v">${doc.borough}</span></div>
          <div class="detail-kv"><span class="k">Hour</span><span class="v">${doc.crash_hour}:00</span></div>
          <div class="detail-kv"><span class="k">Killed</span><span class="v" style="color:var(--accent)">${doc.people_killed}</span></div>
          <div class="detail-kv"><span class="k">Injured</span><span class="v">${doc.people_injured}</span></div>
          <div class="detail-kv" style="grid-column:1/-1"><span class="k">Factor</span><span class="v">${doc.contributing_factor}</span></div>
        </div>
      </div>
      <div class="detail-section">
        <h4>${vCount} Vehicle(s)</h4>
        ${vehicleHtml}
      </div>
      ${pCount > 0 ? `
      <div class="detail-section">
        <h4>${pCount} Person(s)</h4>
        ${personHtml}
      </div>` : ''}
    </div>
  `;
}

// ════════════════════════════════════════════════
// REFRESH ALL
// ════════════════════════════════════════════════
function refreshAll() {
  state.activeHour = null;
  state.activeDay  = null;
  document.getElementById('slotInfo').textContent = 'Click a cell to explore crashes at that time slot';
  document.getElementById('crashList').innerHTML  = '<div class="loading">Select a heatmap cell to see crash list</div>';
  document.getElementById('crashDetail').innerHTML = '<div>Click a crash from the list to see full detail</div>';
  loadHeatmap();
  loadBarChart();
}

// ════════════════════════════════════════════════
// FILTER LISTENERS
// ════════════════════════════════════════════════
document.getElementById('filterBorough').addEventListener('change', e => {
  state.borough = e.target.value;
  refreshAll();
});
document.getElementById('filterYearStart').addEventListener('change', e => {
  state.yearStart = +e.target.value;
  refreshAll();
});
document.getElementById('filterYearEnd').addEventListener('change', e => {
  state.yearEnd = +e.target.value;
  refreshAll();
});
document.getElementById('colorMode').addEventListener('change', e => {
  state.colorMode = e.target.value;
  loadHeatmap();
});

// ════════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════════
(async () => {
  await initMap();
  await loadHeatmap();
  await loadBarChart();
})();
</script>
</body>
</html>