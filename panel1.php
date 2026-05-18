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
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@2.0.1/dist/chartjs-chart-matrix.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/d3@7.9.0/dist/d3.min.js"></script>
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0
    }

    :root {
      --bg: #09090e;
      --surface: #111116;
      --surface2: #17171e;
      --border: rgba(255, 255, 255, 0.06);
      --border2: rgba(255, 255, 255, 0.11);
      --text: #ededf2;
      --muted: #55556a;
      --dim: #2a2a38;
      --red: #e63946;
      --red-bg: rgba(230, 57, 70, 0.12);
      --amber: #f4a261;
      --blue: #457b9d;
      --blue-bg: rgba(69, 123, 157, 0.15);
      --mono: 'JetBrains Mono', monospace;
    }

    html,
    body {
      height: 100%;
      overflow: hidden
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', system-ui, sans-serif;
      font-size: 13px;
      line-height: 1.5
    }

    /* ══ PANEL 2 STYLES ══════════════════════════════════════════════════════ */
    .panel2-wrapper {
      background: transparent;
      border-top: none;
      padding: 18px 16px 10px;
      display: flex;
      flex-direction: column;
      gap: 18px;
      flex-shrink: 0;
    }

    .panel2-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }

    .panel2-header h2 {
      font-size: 10px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--text);
    }

    .panel2-header .p2-meta {
      font-family: var(--mono);
      font-size: 9px;
      color: var(--muted);
    }

    .panel2-controls {
      display: flex;
      align-items: center;
      gap: 18px;
      flex-wrap: wrap;
    }

    .p2-ctrl {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 14px;
    }

    .p2-ctrl label {
      font-size: 10px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--muted);
      white-space: nowrap;
    }

    .p2-ctrl input[type=range] {
      width: 110px;
      accent-color: var(--blue);
      cursor: pointer;
    }

    .p2-ctrl .ctrl-val {
      font-family: var(--mono);
      font-size: 10px;
      color: var(--amber);
      min-width: 36px;
    }

    .p2-filter-badge {
      display: none;
      align-items: center;
      gap: 6px;
      background: var(--blue-bg);
      border: 1px solid rgba(69, 123, 157, 0.3);
      border-radius: 6px;
      padding: 5px 10px;
      font-size: 10px;
      color: #6fb3d4;
      font-family: var(--mono);
    }

    .p2-filter-badge.show {
      display: flex
    }

    .p2-filter-badge .badge-x {
      cursor: pointer;
      color: var(--muted);
      font-size: 11px;
      line-height: 1;
      margin-left: 2px;
    }

    .p2-filter-badge .badge-x:hover {
      color: var(--text)
    }

    .panel2-body {
      display: grid;
      grid-template-columns: minmax(0, 2.2fr) minmax(360px, 1fr);
      gap: 16px;
      height: 560px;
      flex-shrink: 0;
    }

    .p2-graph-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 1rem;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .p2-graph-card .ch {
      padding: 12px 14px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }

    .p2-graph-card .ch h3 {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--text);
    }

    .p2-graph-card .ch .cm {
      font-family: var(--mono);
      font-size: 10px;
      color: var(--muted)
    }

    #graphSvg {
      width: 100%;
      height: 100%;
      cursor: grab
    }

    #graphSvg:active {
      cursor: grabbing
    }

    #graphSvg .node-circle {
      stroke-width: 1.5;
      transition: opacity 0.2s;
    }

    #graphSvg .edge-line {
      transition: opacity 0.2s;
    }

    .graph-legend {
      padding: 10px 14px;
      display: flex;
      gap: 14px;
      border-top: 1px solid var(--border);
      flex-shrink: 0;
      flex-wrap: wrap;
    }

    .gl-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 10px;
      color: var(--muted);
    }

    .gl-line {
      width: 24px;
      height: 2px;
      border-radius: 1px;
    }

    .gl-node {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--surface2);
      border: 1px solid var(--border2);
    }

    /* ── Co-occurrence Table ─────────────────────────────────── */
    .p2-table-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 1rem;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .p2-table-card .ch {
      padding: 12px 14px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }

    .p2-table-card .ch h3 {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--text);
    }

    .p2-table-card .ch .cm {
      font-family: var(--mono);
      font-size: 10px;
      color: var(--muted)
    }

    .p2-table-scroll {
      flex: 1;
      overflow-y: auto
    }

    .p2-table-scroll::-webkit-scrollbar {
      width: 4px
    }

    .p2-table-scroll::-webkit-scrollbar-track {
      background: transparent
    }

    .p2-table-scroll::-webkit-scrollbar-thumb {
      background: rgba(85, 85, 106, 0.45);
      border-radius: 2px
    }

    .cooc-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
    }

    .cooc-table thead th {
      position: sticky;
      top: 0;
      background: var(--surface);
      padding: 8px 10px;
      text-align: left;
      font-size: 10px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--muted);
      border-bottom: 1px solid var(--border);
      cursor: pointer;
      white-space: nowrap;
      user-select: none;
    }

    .cooc-table thead th:hover {
      color: var(--text)
    }

    .cooc-table thead th.sort-asc::after {
      content: ' ↑';
      color: var(--blue)
    }

    .cooc-table thead th.sort-desc::after {
      content: ' ↓';
      color: var(--blue)
    }

    .cooc-table tbody tr {
      border-bottom: 1px solid var(--border);
      cursor: pointer;
      transition: background 0.1s;
    }

    .cooc-table tbody tr:hover {
      background: var(--surface)
    }

    .cooc-table tbody tr.row-active {
      background: rgba(69, 123, 157, 0.12);
      border-color: rgba(69, 123, 157, 0.25)
    }

    .cooc-table tbody td {
      padding: 10px 10px;
      vertical-align: middle;
      color: var(--text);
      font-size: 12px;
    }

    .combo-cell {
      display: flex;
      align-items: center;
      gap: 4px;
      flex-wrap: wrap
    }

    .tag-factor {
      background: var(--surface2);
      border: 1px solid var(--border2);
      border-radius: 2px;
      padding: 2px 6px;
      font-size: 10px;
      color: var(--text);
      font-family: var(--mono);
      white-space: nowrap;
    }

    .tag-x {
      color: var(--muted);
      font-size: 10px
    }

    .col-num {
      font-family: var(--mono);
      font-size: 11px;
      color: var(--text);
      text-align: right;
      white-space: nowrap;
    }

    .rate-pill {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 4px;
      font-family: var(--mono);
      font-size: 10px;
      font-weight: 600;
    }

    .rate-critical {
      background: rgba(230, 57, 70, 0.18);
      color: #e63946;
      border: 1px solid rgba(230, 57, 70, 0.25)
    }

    .rate-high {
      background: rgba(244, 162, 97, 0.18);
      color: #f4a261;
      border: 1px solid rgba(244, 162, 97, 0.25)
    }

    .rate-mid {
      background: rgba(233, 196, 106, 0.14);
      color: #e9c46a;
      border: 1px solid rgba(233, 196, 106, 0.2)
    }

    .rate-low {
      background: rgba(69, 123, 157, 0.14);
      color: #6fb3d4;
      border: 1px solid rgba(69, 123, 157, 0.2)
    }

    .p2-empty {
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      color: var(--muted);
      font-family: var(--mono);
    }

    /* ══ END PANEL 2 STYLES ══════════════════════════════════════════════════ */

    header {
      height: 46px;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      padding: 0 18px;
      gap: 10px;
      flex-shrink: 0
    }

    header h1 {
      font-size: 1em;
      font-weight: 500;
      letter-spacing: 0.1px
    }

    header .sep {
      color: var(--dim);
      font-size: 16px;
      font-weight: 200
    }

    header .sub {
      font-size: 10px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      font-weight: 400
    }

    header .tag {
      margin-left: auto;
      font-family: var(--mono);
      font-size: 9px;
      color: var(--red);
      background: var(--red-bg);
      border: 1px solid rgba(230, 57, 70, 0.18);
      padding: 2px 7px;
      border-radius: 2px;
      letter-spacing: 1px
    }

    .layout {
      display: flex;
      height: calc(100vh - 46px)
    }

    .main-scroll {
      overflow-y: auto;
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column
    }

    aside {
      width: 196px;
      flex-shrink: 0;
      background: var(--surface);
      border-right: 1px solid var(--border);
      padding: 14px 12px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      overflow-y: auto
    }

    .fg label {
      display: block;
      font-size: 9px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--muted);
      margin-bottom: 5px
    }

    aside select {
      width: 100%;
      background: var(--bg);
      border: 1px solid var(--border2);
      color: var(--text);
      padding: 5px 8px;
      border-radius: 3px;
      font-family: 'Inter', sans-serif;
      font-size: 12px;
      appearance: none;
      cursor: pointer;
      outline: none
    }

    aside select:focus {
      border-color: var(--blue)
    }

    .fr {
      display: flex;
      gap: 5px
    }

    .fr select {
      flex: 1
    }

    .stats {
      border-top: 1px solid var(--border);
      padding-top: 12px;
      display: flex;
      flex-direction: column;
      gap: 10px
    }

    .stat .v {
      font-family: var(--mono);
      font-size: 18px;
      font-weight: 500;
      color: var(--text);
      letter-spacing: -0.5px
    }

    .stat .v.r {
      color: var(--red)
    }

    .stat .l {
      font-size: 9px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-top: 1px
    }

    main {
      flex: 1;
      padding: 14px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      min-width: 0
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 5px;
      display: flex;
      flex-direction: column;
      overflow: hidden
    }

    .ch {
      padding: 9px 13px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0
    }

    .ch h2 {
      font-size: 10px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--text)
    }

    .ch .cm {
      font-family: var(--mono);
      font-size: 9px;
      color: var(--muted)
    }

    .cb {
      flex: 1;
      position: relative;
      min-height: 0;
      overflow: hidden
    }

    .top-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      height: 520px;
      flex-shrink: 0
    }

    .bar-card {
      height: 195px;
      flex-shrink: 0
    }

    .bot-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      height: 230px;
      flex-shrink: 0
    }

    #map {
      width: 100%;
      height: 100%
    }

    .leaflet-container {
      background: var(--bg) !important
    }

    .si {
      padding: 6px 13px;
      font-size: 10px;
      color: var(--muted);
      border-top: 1px solid var(--border);
      flex-shrink: 0;
      font-family: var(--mono)
    }

    .si span {
      color: var(--amber)
    }

    .mleg {
      padding: 5px 13px;
      display: flex;
      align-items: center;
      gap: 8px;
      border-top: 1px solid var(--border);
      flex-shrink: 0
    }

    .mleg-i {
      display: flex;
      align-items: center;
      gap: 3px
    }

    .mleg-d {
      width: 7px;
      height: 7px;
      border-radius: 50%
    }

    .mleg-l {
      font-size: 9px;
      color: var(--muted)
    }

    #crashList {
      overflow-y: auto;
      height: 100%;
      padding: 6px;
      display: flex;
      flex-direction: column;
      gap: 3px
    }

    .ci {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 3px;
      padding: 6px 9px;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: border-color 0.1s
    }

    .ci:hover {
      border-color: var(--border2)
    }

    .ci.active {
      border-color: var(--red)
    }

    .ci-b {
      font-size: 12px;
      color: var(--text)
    }

    .ci-s {
      font-family: var(--mono);
      font-size: 10px;
      color: var(--muted)
    }

    .bd {
      font-size: 9px;
      font-family: var(--mono);
      padding: 2px 5px;
      border-radius: 2px;
      font-weight: 500
    }

    .bd-f {
      background: var(--red-bg);
      color: var(--red);
      border: 1px solid rgba(230, 57, 70, 0.25)
    }

    .bd-i {
      background: var(--blue-bg);
      color: #6fb3d4;
      border: 1px solid rgba(69, 123, 157, 0.25)
    }

    #crashDetail {
      overflow-y: auto;
      height: 100%;
      padding: 10px 12px;
      font-size: 12px;
      color: var(--muted)
    }

    .cd-empty {
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      color: var(--muted);
      font-family: var(--mono)
    }

    .cds {
      margin-bottom: 10px
    }

    .cds-t {
      font-size: 9px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--muted);
      margin-bottom: 5px;
      padding-bottom: 4px;
      border-bottom: 1px solid var(--border)
    }

    .cdg {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 5px
    }

    .cdkv .k {
      font-size: 9px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.3px
    }

    .cdkv .v {
      font-size: 11px;
      color: var(--text);
      font-family: var(--mono)
    }

    .cdkv .v.r {
      color: var(--red)
    }

    .vb {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 3px;
      padding: 6px 8px;
      margin-bottom: 5px
    }

    .vb-t {
      font-size: 9px;
      color: var(--blue);
      font-family: var(--mono);
      margin-bottom: 4px;
      font-weight: 500
    }

    .pr {
      display: flex;
      justify-content: space-between;
      padding: 3px 0;
      border-bottom: 1px solid var(--border);
      font-size: 10px
    }

    .pr:last-child {
      border-bottom: none
    }

    .lt {
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      color: var(--muted);
      font-family: var(--mono)
    }

    .leaflet-tooltip {
      background: var(--surface2) !important;
      border: 1px solid var(--border2) !important;
      color: var(--text) !important;
      font-family: 'Inter', sans-serif !important;
      font-size: 11px !important;
      border-radius: 3px !important;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.6) !important
    }

    .leaflet-tooltip::before {
      display: none !important
    }

    .leaflet-tooltip b {
      color: var(--amber)
    }

    .heat-legend {
      padding: 8px 13px;
      display: flex;
      align-items: center;
      gap: 10px;
      border-top: 1px solid var(--border);
      flex-shrink: 0;
      font-size: 11px
    }

    .heat-legend .h-swatch {
      width: 34px;
      height: 12px;
      border-radius: 3px;
      border: 1px solid rgba(255, 255, 255, 0.04)
    }

    .heat-legend .h-item {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--muted)
    }

    .bar-legend {
      padding: 10px 0 10px 16px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      font-size: 11px;
      color: var(--muted);
      min-width: 170px;
      max-width: 220px
    }

    .bar-legend .b-item {
      display: flex;
      align-items: center;
      gap: 8px
    }

    .bar-legend .b-swatch {
      width: 16px;
      height: 16px;
      display: inline-block;
      flex-shrink: 0
    }

    .bar-chart-row {
      display: flex;
      gap: 14px;
      height: 100%;
      min-height: 0;
      align-items: stretch
    }

    .bar-chart-area {
      flex: 1;
      min-width: 0;
      display: flex;
      align-items: stretch
    }

    .bar-chart-area canvas {
      width: 100%;
      height: 100%
    }

    #heatmapCanvas {
      border-radius: 4px;
      display: block
    }
  
    /* ── Panel 3 ─────────────────────────────────────────────────────────── */
    .panel3-wrapper {
      background: transparent;
      border-top: none;
      padding: 18px 16px 10px;
      display: flex;
      flex-direction: column;
      gap: 18px;
      flex-shrink: 0;
    }
    .panel3-card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 12px 14px;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }
    .panel3-controls {
      display: flex;
      align-items: center;
      gap: 24px;
      padding: 10px 14px;
      border-top: 1px solid var(--border);
      background: var(--bg);
    }
    .p3-ctrl { display: flex; align-items: center; gap: 8px; }
    .p3-ctrl label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--muted); }
    .p3-toggle { display: flex; background: var(--bg); border: 1px solid var(--border2); border-radius: 4px; overflow: hidden; }
    .p3-btn { background: transparent; border: none; color: var(--muted); padding: 4px 12px; font-family: 'Inter', sans-serif; font-size: 11px; cursor: pointer; transition: background 0.1s, color 0.1s; }
    .p3-btn.active { background: var(--red-bg); color: var(--red); }
    .p3-stat { display: flex; flex-direction: column; }
    .p3-stat-v { font-family: var(--mono); font-size: 18px; font-weight: 500; color: var(--text); }
    .p3-stat-l { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); }
    .panel3-row1 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .panel3-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .p3-card { background: var(--surface); border: 1px solid var(--border); border-radius: 5px; display: flex; flex-direction: column; overflow: hidden; }
    .p3-card-head {
      padding: 9px 13px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-shrink: 0;
    }
    .p3-card-head h3 {
      font-size: 10px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--text);
      margin: 0;
    }
    .p3-card-head .cm {
      font-family: var(--mono);
      font-size: 9px;
      color: var(--muted);
    }
    .p3-ch-title { font-size: 10px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.8px; color: var(--muted); }
    .p3-chart-body { flex: 1; min-height: 0; padding: 8px; position: relative; height: 280px; }
    .p3-chart-body canvas { width: 100% !important; height: 100% !important; }
    .p3-legend-row { display: flex; align-items: center; gap: 8px; padding: 6px 13px; border-top: 1px solid var(--border); flex-shrink: 0; }
    .p3-leg-label { font-size: 9px; color: var(--muted); }
    .p3-leg-swatch { width: 10px; height: 10px; border-radius: 2px; }
    .p3-leg-txt { font-size: 9px; color: var(--muted); }
    .p3-sub-title { font-size: 9px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--muted); padding: 6px 12px 4px; }
    .p3-driver-body { display: grid; grid-template-columns: 200px 1px 1fr; height: 240px; }
    .p3-driver-half { display: flex; flex-direction: column; overflow: hidden; }
    .p3-driver-bar { padding: 4px 10px 8px; }
    .p3-driver-bar canvas { width: 100% !important; height: 100% !important; }
    .p3-donut-wrap { flex: 1; padding: 6px 12px 10px; position: relative; }
    .p3-donut-wrap canvas { width: 100% !important; height: 100% !important; }
    .p3-divider { width: 1px; background: var(--border); margin: 12px 0; }
    .p3-profile-body { display: grid; grid-template-columns: 1fr 1px 1fr; height: 240px; }
    .p3-profile-third { display: flex; flex-direction: column; overflow: hidden; }
    .p3-profile-third canvas { width: 100% !important; height: 100% !important; }
    .p3-profile-third .p3-donut-wrap { flex: 1; }

</style>
</head>

<body>
  <header>
    <h1>NYC Crash Intelligence</h1>
    <span class="sep">/</span>
  </header>
  <div class="layout">
    <aside>
      <div class="fg"><label for="filterBorough">Borough</label>
        <select id="filterBorough">
          <option value="ALL">All Boroughs</option>
          <option value="BROOKLYN">Brooklyn</option>
          <option value="QUEENS">Queens</option>
          <option value="MANHATTAN">Manhattan</option>
          <option value="BRONX">Bronx</option>
          <option value="STATEN ISLAND">Staten Island</option>
        </select>
      </div>
      <div class="fg"><label for="filterYearStart">Year Range</label>
        <div class="fr">
          <select id="filterYearStart"><?php for ($y = 2020; $y <= 2025; $y++)
            echo "<option value='$y'" . ($y == 2020 ? " selected" : "") . ">$y</option>"; ?></select>
          <select id="filterYearEnd"><?php for ($y = 2020; $y <= 2025; $y++)
            echo "<option value='$y'" . ($y == 2025 ? " selected" : "") . ">$y</option>"; ?></select>
        </div>
      </div>
      <div class="fg"><label for="colorMode">Heatmap Mode</label>
        <select id="colorMode">
          <option value="killed">By Fatalities</option>
          <option value="crash">By Total Crashes</option>
        </select>
      </div>
      <div class="stats">
        <div class="stat">
          <div class="v" id="statTotalCrash">—</div>
          <div class="l">Total Crashes</div>
        </div>
        <div class="stat">
          <div class="v r" id="statTotalKilled">—</div>
          <div class="l">Total Killed</div>
        </div>
        <div class="stat">
          <div class="v" id="statPeakSlot">—</div>
          <div class="l">Peak Fatal Slot</div>
        </div>
        <div class="stat">
          <div class="v" id="statVRUKilled">—</div>
          <div class="l">VRU Killed</div>
        </div>
        <div class="stat">
          <div class="v" id="statVRUInjured">—</div>
          <div class="l">VRU Injured</div>
        </div>
        <div class="stat">
          <div class="v" id="statHotBorough">—</div>
          <div class="l">Highest VRU Kill</div>
        </div>
      </div>
    </aside>
    <div class="main-scroll">
      <main>
        <div class="top-row">
          <div class="card">
            <div class="ch">
              <h2>Crash Heatmap — Hour × Day</h2>
            </div>
            <div class="si" id="slotInfo">Click a time slot to focus on</div>
            <div class="cb"><canvas id="heatmapCanvas"></canvas></div>
            <div class="heat-legend" id="heatLegend">
              <div class="h-item">
                <div class="h-label">Number of Fatalities</div>
              </div>
              <div class="h-item">
                <div class="h-swatch" style="background:rgba(15,15,22,0.9)"></div>
                <div class="h-label">None</div>
              </div>
              <div class="h-item">
                <div class="h-swatch" style="background:#1d3557"></div>
                <div class="h-label">Low</div>
              </div>
              <div class="h-item">
                <div class="h-swatch" style="background:#457b9d"></div>
                <div class="h-label">Med</div>
              </div>
              <div class="h-item">
                <div class="h-swatch" style="background:#f4a261"></div>
                <div class="h-label">High</div>
              </div>
              <div class="h-item">
                <div class="h-swatch" style="background:#e63946"></div>
                <div class="h-label">Critical</div>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="ch">
              <h2>Fatality Rate per Borough</h2>
            </div>
            <div class="si mapHint">Click a borough to filter crashes</div>
            <div class="cb">
              <div id="map"></div>
            </div>
          </div>
        </div>
        <div class="card bar-card">
          <div class="ch">
            <h2 id="barTitle">Top Contributing Factors</h2><span class="cm" id="barMeta">All boroughs</span>
          </div>
          <div class="cb bar-chart-row">
            <div class="bar-chart-area"><canvas id="barCanvas"></canvas></div>
            <div class="bar-legend">
              <div class="b-item">
                <div class="h-label">FATALITY RATE</div>
              </div>
              <div class="b-item"><span class="b-swatch" style="background:#e63946"></span><span>≥ 1.5%</span></div>
              <div class="b-item"><span class="b-swatch" style="background:#f4a261"></span><span>0.5% – 1.49%</span>
              </div>
              <div class="b-item"><span class="b-swatch" style="background:#457b9d"></span><span>
                  < 0.5%</span>
              </div>
            </div>
          </div>
        </div>
        <div class="bot-row">
          <div class="card">
            <div class="ch">
              <h2 id="listTitle">Crash List</h2><span class="cm">Top 50 by severity</span>
            </div>
            <div class="cb">
              <div id="crashList">
                <div class="lt">loading crash list...</div>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="ch">
              <h2>Crash Detail</h2>
            </div>
            <div class="cb">
              <div id="crashDetail">
                <div class="cd-empty">Select a crash from the list</div>
              </div>
            </div>
          </div>
        </div>
      </main>

      <!-- ══ PANEL 2: Factor Co-occurrence Network ══════════════════════════════ -->
      <div class="panel2-wrapper">
        <!-- Header + controls -->
        <div class="card">
          <div class="ch">
            <h2>Factor Co-occurrence Network</h2>
          </div>
          <div class="panel2-controls">
            <div class="p2-ctrl">
              <label for="p2SliderCount">Min Co-occurrence</label>
              <input type="range" id="p2SliderCount" min="1" max="100" value="5" step="1">
              <span class="ctrl-val" id="p2ValCount">5</span>
            </div>
            <div class="p2-ctrl">
              <label for="p2SliderFatal">Min Fatal Rate (%)</label>
              <input type="range" id="p2SliderFatal" min="0" max="20" value="0" step="0.5">
              <span class="ctrl-val" id="p2ValFatal">0%</span>
            </div>
            <div class="p2-filter-badge" id="p2FilterBadge">
              <span id="p2BadgeText">—</span>
              <span class="badge-x" id="p2BadgeClear" title="Clear filter">✕</span>
            </div>
          </div>
        </div>
        <!-- Graph + Table side by side -->
        <div class="panel2-body">
          <!-- Force-directed graph -->
          <div class="p2-graph-card">
            <div class="ch">
              <h3>Force-Directed Graph</h3>
            </div>
            <div class="cb" style="position:relative;flex:1;min-height:0;overflow:hidden">
              <svg id="graphSvg"></svg>
              <div id="graphLoading"
                style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:10px;color:var(--muted);font-family:var(--mono)">
                loading graph from neo4j…</div>
            </div>
            <div class="graph-legend">
              <div class="gl-item">
                <div class="gl-node"></div><span>Factor node (size = frequency)</span>
              </div>
              <div class="gl-item">
                <div class="gl-line" style="background:#b5d4f4"></div><span>Low fatal rate</span>
              </div>
              <div class="gl-item">
                <div class="gl-line" style="background:#a32d2d"></div><span>High fatal rate</span>
              </div>
            </div>
          </div>

          <!-- Co-occurrence table -->
          <div class="p2-table-card">
            <div class="ch">
              <h3>Co-occurrence Table</h3>
              <span class="cm" id="p2TableMeta">sorted by fatal rate</span>
            </div>
            <div class="p2-table-scroll">
              <table class="cooc-table">
                <thead>
                  <tr>
                    <th id="th-combo" data-col="combo">Factor Combination</th>
                    <th id="th-crash" data-col="crash_count" class="sort-desc">Crashes ↕</th>
                    <th id="th-fatal" data-col="fatal_count">Fatal ↕</th>
                    <th id="th-rate" data-col="fatal_rate">Rate ↕</th>
                  </tr>
                </thead>
                <tbody id="p2TableBody">
                  <tr>
                    <td colspan="4">
                      <div class="p2-empty">loading…</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <!-- ══ END PANEL 2 ═══════════════════════════════════════════════════════ -->

      <!-- ══ PANEL 3: VRU Vulnerability Analysis ══════════════════════════════ -->
      <div class="panel3-wrapper">
        <div class="panel3-row1">
          <div class="p3-card">
            <div class="p3-card-head">
              <div>
                <h3 class="p3-ch-title">VRU Borough x Hour</h3>
              </div>
              <div class="p3-ctrl">
                <label for="p3BtnKilled">Mode</label>
                <div class="p3-toggle">
                  <button class="p3-btn active" id="p3BtnKilled" type="button" onclick="p3SetMode('killed')">Killed</button>
                  <button class="p3-btn" id="p3BtnInjured" type="button" onclick="p3SetMode('injured')">Injured</button>
                </div>
              </div>
            </div>
            <div class="p3-chart-body" id="p3HeatmapBody"></div>
            <div class="p3-legend-row">
              <span class="p3-leg-label">VRU Concentration:</span>
              <div class="p3-leg-swatch" style="background:rgba(29,53,87,0.9)"></div><span class="p3-leg-txt">Low</span>
              <div class="p3-leg-swatch" style="background:#457b9d"></div><span class="p3-leg-txt">Med</span>
              <div class="p3-leg-swatch" style="background:#f4a261"></div><span class="p3-leg-txt">High</span>
              <div class="p3-leg-swatch" style="background:#e63946"></div><span class="p3-leg-txt">Critical</span>
            </div>
          </div>
          <div class="p3-card">
            <div class="ch">
              <h2>Risk Ratio Bubble Chart</h2>
            </div>
            <div class="p3-chart-body"><canvas id="p3BubbleCanvas"></canvas></div>
            <div class="p3-legend-row">
              <span class="p3-leg-label">Bubble size = absolute VRU killed &nbsp;|&nbsp; Y-axis = kill rate per 1,000 crashes</span>
            </div>
          </div>
        </div>

        <div class="panel3-row2">
          <div class="p3-card">
            <div class="ch">
              <h2>Driver Behavior</h2>
            </div>
            <div class="p3-driver-body">
              <div class="p3-driver-half">
                <div class="p3-sub-title">License Status</div>
                <div class="p3-donut-wrap"><canvas id="p3LicenseCanvas"></canvas></div>
              </div>
              <div class="p3-divider"></div>
              <div class="p3-driver-half p3-driver-bar">
                <div class="p3-sub-title">Pre-Collision Action</div>
                <canvas id="p3PreCollisionCanvas"></canvas>
              </div>
            </div>
          </div>
          <div class="p3-card">
            <div class="ch">
              <h2>VRU Victim Profile</h2>
            </div>
            <div class="p3-profile-body">
              <div class="p3-profile-third">
                <div class="p3-sub-title">Age Group</div>
                <canvas id="p3AgeCanvas"></canvas>
              </div>
              <div class="p3-divider"></div>
              <div class="p3-profile-third">
                <div class="p3-sub-title">Bodily Injury</div>
                <canvas id="p3InjuryCanvas"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- end .main-scroll -->
  </div>
  <script>
    const state = { borough: 'ALL', yearStart: 2020, yearEnd: 2025, colorMode: 'killed', activeHour: null, activeDay: null };
    const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    function getFilters() { return new URLSearchParams({ borough: state.borough, year_start: state.yearStart, year_end: state.yearEnd }) }

    // ── COLORS ──
    function heatColor(val, max) {
      if (!max || val === 0) return 'rgba(36,42,57,0.92)';
      const ratio = Math.min(Math.max(val / max, 0), 1);
      const t = Math.pow(ratio, 0.4); // perceptual scaling
      // Map into the same four legend stops: Low | Med | High | Critical
      if (t < 0.25) return 'rgba(29,53,87,0.9)'; // Low
      if (t < 0.5) return '#457b9d'; // Med
      if (t < 0.75) return '#f4a261'; // High
      return '#e63946'; // Critical
    }
    function choroplethColor(r) {
      if (r >= 2.5) return '#e63946';
      if (r >= 1.8) return '#f4a261';
      if (r >= 1.2) return '#e9c46a';
      if (r >= 0.8) return '#457b9d';
      if (r >= 0.4) return '#2d6a8f';
      return '#1d3557';
    }

    // ── HEATMAP ──
    let heatmapChart = null;
    async function loadHeatmap() {
      const res = await fetch('api/get_heatmap.php?' + getFilters());
      const data = await res.json();
      const matrix = {};
      let maxK = 0, maxC = 0, totC = 0, totK = 0, peakV = 0, peakL = '—';
      data.forEach(r => {
        matrix[`${r.col_hour}_${r.day_of_week}`] = r;
        const k = +r.total_killed, c = +r.total_crash;
        if (k > maxK) maxK = k; if (c > maxC) maxC = c;
        totC += c; totK += k;
        if (k > peakV) { peakV = k; peakL = `${(r.day_of_week || '').slice(0, 3)} ${r.col_hour}:00` }
      });
      document.getElementById('statTotalCrash').textContent = totC.toLocaleString();
      document.getElementById('statTotalKilled').textContent = totK.toLocaleString();
      document.getElementById('statPeakSlot').textContent = peakL;

      const useK = state.colorMode === 'killed';
      const maxV = useK ? maxK : maxC;
      const md = [];
      for (let h = 0; h < 24; h++) for (let d = 0; d < 7; d++) {
        const r = matrix[`${h}_${DAYS[d]}`] || { total_killed: 0, total_crash: 0 };
        md.push({ x: d, y: h, v: useK ? +r.total_killed : +r.total_crash, killed: +r.total_killed, crash: +r.total_crash });
      }
      const ctx = document.getElementById('heatmapCanvas').getContext('2d');
      if (heatmapChart) heatmapChart.destroy();
      heatmapChart = new Chart(ctx, {
        type: 'matrix',
        data: {
          datasets: [{
            data: md,
            backgroundColor(c) { const v = c.dataset.data[c.dataIndex]; return heatColor(v.v, maxV) },
            borderColor: 'rgba(255,255,255,0.12)', borderWidth: 1,
            width: ({ chart }) => (chart.chartArea?.width || 420) / 7 - 1.2,
            height: ({ chart }) => (chart.chartArea?.height || 560) / 24 - 0.8,
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false, animation: { duration: 250 },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#17171e', borderColor: 'rgba(255,255,255,0.08)', borderWidth: 1,
              titleColor: '#f4a261', bodyColor: '#55556a',
              titleFont: { family: 'JetBrains Mono', size: 11 }, bodyFont: { family: 'JetBrains Mono', size: 10 },
              callbacks: {
                title: i => `${DAYS[i[0].raw.x]}  ${String(i[0].raw.y).padStart(2, '0')}:00`,
                label: i => [`crashes : ${i.raw.crash.toLocaleString()}`, `killed  : ${i.raw.killed}`]
              }
            }
          },
          scales: {
            x: {
              type: 'linear', min: -0.5, max: 6.5, position: 'top',
              grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false },
              ticks: {
                stepSize: 1, callback: v => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][v] ?? '',
                color: '#55556a', font: { family: 'Inter', size: 10, weight: '500' }
              }
            },
            y: {
              type: 'linear', min: -0.5, max: 23.5, reverse: true,
              grid: { color: 'rgba(255,255,255,0.03)', drawBorder: false },
              ticks: {
                stepSize: 1, callback: v => Number.isInteger(v) ? String(v).padStart(2, '0') + ':00' : '',
                color: '#3a3a50', font: { family: 'JetBrains Mono', size: 9 }
              }
            }
          },
          onClick(event, elements) {
            if (!elements.length) return;
            const d = elements[0].element.$context.raw;
            state.activeHour = d.y; state.activeDay = DAYS[d.x];
            document.getElementById('slotInfo').innerHTML =
              `<span>${state.activeDay}</span> · <span>${String(state.activeHour).padStart(2, '0')}:00</span> — ${d.crash.toLocaleString()} crashes · ${d.killed} killed`;
            loadCrashList(); loadBarChart();
          }
        }
      });
    }

    // ── CHOROPLETH ──
    let map = null, geojsonLayer = null, boroughData = {};
    async function initMap() {
      map = L.map('map', { zoomControl: false, attributionControl: false }).setView([40.65, -73.97], 10);
      L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', { maxZoom: 19, subdomains: 'abcd' }).addTo(map);

      const res = await fetch('api/get_choropleth.php');
      const data = await res.json();
      data.forEach(r => { boroughData[r.borough] = r });

      const gjRes = await fetch('data/nyc_boroughs.geojson');
      const gjData = await gjRes.json();
      const sp = gjData.features[0]?.properties || {};
      const nk = sp.boro_name !== undefined ? 'boro_name' : sp.name !== undefined ? 'name' : sp.borough !== undefined ? 'borough' : Object.keys(sp)[0];

      geojsonLayer = L.geoJSON(gjData, {
        style(f) {
          const nm = ((f.properties[nk] || '').toUpperCase().replace('THE BRONX', 'BRONX').trim());
          const d = boroughData[nm] || {};
          return { fillColor: choroplethColor(+d.fatality_rate_per_1000 || 0), fillOpacity: 0.6, weight: 1, color: 'rgba(255,255,255,0.12)' };
        },
        onEachFeature(f, layer) {
          const raw = f.properties[nk] || '';
          const nm = (raw.toUpperCase().replace('THE BRONX', 'BRONX').trim());
          const d = boroughData[nm] || {};
          const rate = (+d.fatality_rate_per_1000 || 0).toFixed(2);
          layer.bindTooltip(`<b>${raw}</b><br>${(+d.total_crash || 0).toLocaleString()} crashes<br>${rate} killed/1,000`, { sticky: true, offset: [10, 0] });
          layer.on('click', () => { state.borough = nm; document.getElementById('filterBorough').value = nm; refreshAll() });
          layer.on('mouseover', () => layer.setStyle({ fillOpacity: 0.9, weight: 2, color: 'rgba(255,255,255,0.5)' }));
          layer.on('mouseout', () => geojsonLayer.resetStyle(layer));
        }
      }).addTo(map);
      map.fitBounds(geojsonLayer.getBounds(), { padding: [8, 8] });
    }

    // ── BAR CHART ──
    let barChart = null;
    async function loadBarChart() {
      const p = getFilters();
      if (state.activeHour !== null) p.set('hour', state.activeHour);
      if (state.activeDay !== null) p.set('day', state.activeDay);
      const res = await fetch('api/get_factor_chart.php?' + p);
      const data = await res.json();

      // If a heatmap slot is selected but there are no factors, show message
      const cbEl = document.querySelector('.bar-card .cb');
      if (state.activeHour !== null && (!data || data.length === 0)) {
        document.getElementById('barMeta').textContent = `${state.activeDay.toLowerCase()} · ${String(state.activeHour).padStart(2, '0')}:00`;
        if (barChart) { barChart.destroy(); barChart = null }
        cbEl.innerHTML = '<div class="lt">No Factors Were Identified</div>';
        return;
      }

      // Ensure canvas exists (in case it was replaced by a message previously)
      if (!cbEl.querySelector('canvas')) cbEl.innerHTML = '<canvas id="barCanvas"></canvas>';

      const labels = data.map(r => r.factor_name);
      const counts = data.map(r => +r.crash_count);
      const fPcts = data.map(r => +r.fatal_pct);
      const colors = fPcts.map(p => p >= 1.5 ? '#e63946' : p >= 0.5 ? '#f4a261' : '#457b9d');
      document.getElementById('barMeta').textContent =
        state.activeDay ? `${state.activeDay.toLowerCase()} · ${String(state.activeHour).padStart(2, '0')}:00`
          : `${state.borough === 'ALL' ? 'all boroughs' : state.borough.toLowerCase()} · ${state.yearStart}–${state.yearEnd}`;
      const cx = document.getElementById('barCanvas').getContext('2d');
      if (barChart) barChart.destroy();
      barChart = new Chart(cx, {
        type: 'bar',
        data: { labels, datasets: [{ data: counts, backgroundColor: colors, borderRadius: 2, borderSkipped: false }] },
        options: {
          indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: { duration: 200 },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#17171e', borderColor: 'rgba(255,255,255,0.08)', borderWidth: 1,
              titleColor: '#ededf2', bodyColor: '#55556a',
              titleFont: { family: 'JetBrains Mono', size: 10 }, bodyFont: { family: 'JetBrains Mono', size: 10 },
              callbacks: { label: i => `${counts[i.dataIndex].toLocaleString()} crashes  ·  ${fPcts[i.dataIndex].toFixed(2)}% fatal` }
            }
          },
          scales: {
            x: { grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false }, ticks: { color: '#3a3a50', font: { family: 'JetBrains Mono', size: 9 } } },
            y: { grid: { display: false }, ticks: { color: '#909090', font: { family: 'Inter', size: 11 } } }
          },
          onClick(event, elements) {
            if (elements.length) {
              const factorName = labels[elements[0].index];
              sessionStorage.setItem('highlightFactor', factorName);
              // ── Notify Panel 2 ──
              document.dispatchEvent(new CustomEvent('p1FactorSelected', { detail: factorName }));
            }
          }
        }
      });
    }

    // ── CRASH LIST ──
    async function loadCrashList() {
      const p = getFilters();
      if (state.activeHour !== null) p.set('hour', state.activeHour);
      if (state.activeDay !== null) p.set('day', state.activeDay);
      document.getElementById('crashList').innerHTML = '<div class="lt">loading...</div>';
      document.getElementById('listTitle').textContent =
        state.activeHour !== null
          ? `${state.activeDay}  ${String(state.activeHour).padStart(2, '0')}:00`
          : 'Top Crashes by Severity';
      const res = await fetch('api/get_crash_list.php?' + p);
      const data = await res.json();
      if (!data.length) { document.getElementById('crashList').innerHTML = '<div class="lt">no crashes found</div>'; return }
      document.getElementById('crashList').innerHTML = data.map(r => `
    <div class="ci" onclick="loadCrashDetail(${r.collision_id})" data-cid="${r.collision_id}">
      <div><div class="ci-b">${r.borough}</div><div class="ci-s">#${r.collision_id} · ${r.col_time}</div></div>
      ${+r.people_killed > 0
          ? `<span class="bd bd-f">${r.people_killed} killed</span>`
          : `<span class="bd bd-i">${r.people_injured} inj</span>`}
    </div>`).join('');
    }

    // ── CRASH DETAIL ──
    async function loadCrashDetail(cid) {
      document.querySelectorAll('.ci').forEach(e => e.classList.remove('active'));
      document.querySelector(`[data-cid="${cid}"]`)?.classList.add('active');
      const res = await fetch(`api/get_crash_detail.php?id=${cid}`);
      const doc = await res.json();
      if (!doc || doc.error) { document.getElementById('crashDetail').innerHTML = '<div class="cd-empty">not found in crash_events</div>'; return }
      const vH = (doc.vehicles || []).map((v, i) => `
    <div class="vb"><div class="vb-t">V${i + 1} · ${v.vehicle_type || '—'} · ${v.vehicle_make || '—'}</div>
    <div class="cdg">
      <div class="cdkv"><div class="k">Pre-collision</div><div class="v">${v.pre_collision || '—'}</div></div>
      <div class="cdkv"><div class="k">Impact</div><div class="v">${v.point_of_impact || '—'}</div></div>
      <div class="cdkv"><div class="k">License</div><div class="v">${v.driver_license_status || '—'}</div></div>
      <div class="cdkv"><div class="k">Year</div><div class="v">${v.vehicle_year || '—'}</div></div>
    </div></div>`).join('');
      const pH = (doc.persons || []).map(p => `
    <div class="pr"><span>${p.person_type} · ${p.age_group} · ${p.person_sex}</span>
    <span style="color:${p.person_injury === 'Injured' ? 'var(--red)' : 'var(--muted)'}">${p.person_injury}</span></div>`).join('');
      document.getElementById('crashDetail').innerHTML = `
    <div class="cds"><div class="cds-t">Crash #${doc.collision_id}</div>
    <div class="cdg">
      <div class="cdkv"><div class="k">Borough</div><div class="v">${doc.borough}</div></div>
      <div class="cdkv"><div class="k">Hour</div><div class="v">${String(doc.crash_hour).padStart(2, '0')}:00</div></div>
      <div class="cdkv"><div class="k">Killed</div><div class="v r">${doc.people_killed}</div></div>
      <div class="cdkv"><div class="k">Injured</div><div class="v">${doc.people_injured}</div></div>
      <div class="cdkv" style="grid-column:1/-1"><div class="k">Factor</div><div class="v" style="font-size:11px;white-space:normal">${doc.contributing_factor}</div></div>
    </div></div>
    ${vH ? `<div class="cds"><div class="cds-t">${(doc.vehicles || []).length} vehicle(s)</div>${vH}</div>` : ''}
    ${pH ? `<div class="cds"><div class="cds-t">${(doc.persons || []).length} person(s)</div>${pH}</div>` : ''}`;
    }

    // ── REFRESH ──
    function refreshAll() {
      state.activeHour = null; state.activeDay = null;
      document.getElementById('slotInfo').textContent = 'Click a cell to drill down';
      document.getElementById('crashDetail').innerHTML = '<div class="cd-empty">Select a crash from the list</div>';
      loadHeatmap(); loadBarChart(); loadCrashList();
      p3LoadHeatmap(); p3LoadBubble(); p3LoadDriver(); p3LoadProfile();
    }

    document.getElementById('filterBorough').addEventListener('change', e => { state.borough = e.target.value; refreshAll() });
    document.getElementById('filterYearStart').addEventListener('change', e => { state.yearStart = +e.target.value; refreshAll() });
    document.getElementById('filterYearEnd').addEventListener('change', e => { state.yearEnd = +e.target.value; refreshAll() });
    document.getElementById('colorMode').addEventListener('change', e => { state.colorMode = e.target.value; loadHeatmap() });

    (async () => { await initMap(); await loadHeatmap(); await loadBarChart(); await loadCrashList() })();
  </script>

  <!-- ══ PANEL 2 JavaScript ════════════════════════════════════════════════ -->
  <script>
    // ── Panel 2 State ───────────────────────────────────────────────────────
    const p2State = {
      graphData: null,         // raw {nodes, links} from Neo4j
      activeFactor: null,      // factor highlighted from Panel 1 bar click
      activeLink: null,        // {source_name, target_name} from table row click
      sortCol: 'fatal_rate',   // current sort column
      sortDir: 'desc',
      minCount: 5,
      minFatal: 0,
      simulation: null,
    };

    // ── Color scale for edges (fatal_rate 0–20%) ────────────────────────────
    const edgeColorScale = d3.scaleSequential()
      .domain([0, 15])
      .interpolator(d3.interpolateRgb('#2d5a8e', '#a32d2d'));

    // ── Fetch graph data from Neo4j via PHP ────────────────────────────────
    async function p2LoadGraph() {
      document.getElementById('graphLoading').style.display = 'flex';
      const url = `api/get_forcedirected.php?min_count=${p2State.minCount}&min_fatal=${p2State.minFatal}`;
      try {
        const res = await fetch(url);
        const data = await res.json();
        if (data.error) {
          document.getElementById('graphLoading').textContent = 'Neo4j error: ' + data.error;
          return;
        }
        p2State.graphData = data;
        document.getElementById('graphLoading').style.display = 'none';
        p2RenderGraph(data);
        p2RenderTable(data.links);
        document.getElementById('p2Meta').textContent =
          `Neo4j · ${data.nodes.length} nodes · ${data.links.length} edges`;
      } catch (e) {
        document.getElementById('graphLoading').textContent = 'Failed to load: ' + e.message;
      }
    }

    // ── D3 Force-directed Graph ─────────────────────────────────────────────
    function p2RenderGraph(data) {
      const container = document.getElementById('graphSvg').parentElement;
      const W = container.clientWidth || 700;
      const H = container.clientHeight || 460;

      // Clear previous
      d3.select('#graphSvg').selectAll('*').remove();

      const svg = d3.select('#graphSvg')
        .attr('viewBox', `0 0 ${W} ${H}`)
        .attr('width', W)
        .attr('height', H);

      // Zoom support
      const g = svg.append('g');
      svg.call(d3.zoom()
        .scaleExtent([0.3, 4])
        .on('zoom', e => g.attr('transform', e.transform))
      );

      // Scales
      const maxFreq = d3.max(data.nodes, d => d.freq) || 1;
      const nodeRadius = d3.scaleSqrt().domain([0, maxFreq]).range([5, 26]);

      const maxCount = d3.max(data.links, d => d.crash_count) || 1;
      const edgeWidth = d3.scaleLinear().domain([0, maxCount]).range([0.8, 5]);

      // Clone links/nodes for simulation (D3 mutates them)
      const nodes = data.nodes.map(d => ({ ...d }));
      const links = data.links.map(d => ({ ...d }));

      // Simulation
      if (p2State.simulation) p2State.simulation.stop();
      p2State.simulation = d3.forceSimulation(nodes)
        .force('link', d3.forceLink(links).id(d => d.id).distance(110).strength(0.4))
        .force('charge', d3.forceManyBody().strength(-280))
        .force('center', d3.forceCenter(W / 2, H / 2))
        .force('collision', d3.forceCollide(d => nodeRadius(d.freq) + 6));

      // Draw edges
      const edgeG = g.append('g').attr('class', 'edges');
      const edgeElems = edgeG.selectAll('line')
        .data(links)
        .join('line')
        .attr('class', 'edge-line')
        .attr('stroke', d => edgeColorScale(d.fatal_rate))
        .attr('stroke-width', d => edgeWidth(d.crash_count))
        .attr('stroke-opacity', 0.65)
        .attr('data-src', d => d.source_name)
        .attr('data-tgt', d => d.target_name);

      // Draw nodes
      const nodeG = g.append('g').attr('class', 'nodes');
      const nodeElems = nodeG.selectAll('g')
        .data(nodes)
        .join('g')
        .attr('class', 'node-g')
        .attr('data-name', d => d.name)
        .style('cursor', 'pointer')
        .call(d3.drag()
          .on('start', (event, d) => {
            if (!event.active) p2State.simulation.alphaTarget(0.3).restart();
            d.fx = d.x; d.fy = d.y;
          })
          .on('drag', (event, d) => { d.fx = event.x; d.fy = event.y; })
          .on('end', (event, d) => {
            if (!event.active) p2State.simulation.alphaTarget(0);
            d.fx = null; d.fy = null;
          })
        )
        .on('click', (event, d) => {
          event.stopPropagation();
          p2HighlightFactor(d.name, 'node');
        });

      nodeElems.append('circle')
        .attr('class', 'node-circle')
        .attr('r', d => nodeRadius(d.freq))
        .attr('fill', 'var(--surface2)')
        .attr('stroke', 'rgba(255,255,255,0.18)')
        .attr('stroke-width', 1.5);

      nodeElems.append('text')
        .text(d => d.name.length > 16 ? d.name.slice(0, 14) + '…' : d.name)
        .attr('text-anchor', 'middle')
        .attr('dy', '0.35em')
        .attr('font-size', d => Math.min(10, nodeRadius(d.freq) * 0.72) + 'px')
        .attr('fill', 'var(--text)')
        .attr('pointer-events', 'none');

      // Tooltip
      const tooltip = d3.select('body').select('#p2Tooltip').empty()
        ? d3.select('body').append('div').attr('id', 'p2Tooltip')
          .style('position', 'fixed').style('pointer-events', 'none')
          .style('background', 'var(--surface2)').style('border', '1px solid var(--border2)')
          .style('border-radius', '3px').style('padding', '6px 9px')
          .style('font-size', '10px').style('font-family', 'var(--mono)')
          .style('color', 'var(--text)').style('z-index', '9999')
          .style('display', 'none').style('line-height', '1.6')
        : d3.select('#p2Tooltip');

      nodeElems
        .on('mouseover', (event, d) => {
          tooltip.style('display', 'block')
            .html(`<b style="color:var(--amber)">${d.name}</b><br>frequency: ${d.freq.toLocaleString()}`);
        })
        .on('mousemove', event => {
          tooltip.style('left', (event.clientX + 12) + 'px').style('top', (event.clientY - 28) + 'px');
        })
        .on('mouseout', () => tooltip.style('display', 'none'));

      edgeElems
        .on('mouseover', (event, d) => {
          tooltip.style('display', 'block')
            .html(`<b style="color:var(--amber)">${d.source_name} × ${d.target_name}</b><br>crashes: ${d.crash_count.toLocaleString()}<br>fatal: ${d.fatal_count.toLocaleString()}<br>rate: <span style="color:${edgeColorScale(d.fatal_rate)}">${d.fatal_rate.toFixed(1)}%</span>`);
        })
        .on('mousemove', event => {
          tooltip.style('left', (event.clientX + 12) + 'px').style('top', (event.clientY - 28) + 'px');
        })
        .on('mouseout', () => tooltip.style('display', 'none'));

      // Click on background → reset highlight
      svg.on('click', () => {
        if (p2State.activeFactor || p2State.activeLink) {
          p2State.activeFactor = null;
          p2State.activeLink = null;
          p2ResetHighlight();
          p2RenderTable(data.links);
          hideBadge();
        }
      });

      // Tick
      p2State.simulation.on('tick', () => {
        edgeElems
          .attr('x1', d => d.source.x).attr('y1', d => d.source.y)
          .attr('x2', d => d.target.x).attr('y2', d => d.target.y);
        nodeElems.attr('transform', d => `translate(${d.x},${d.y})`);
      });

      // Store references on svg element for later highlight updates
      svg.node()._edgeElems = edgeElems;
      svg.node()._nodeElems = nodeElems;
      svg.node()._links = links;
      svg.node()._nodes = nodes;
    }

    // ── Highlight a factor (from bar chart click or node click) ────────────
    function p2HighlightFactor(factorName, source) {
      const data = p2State.graphData;
      if (!data) return;

      p2State.activeFactor = factorName;
      p2State.activeLink = null;

      const svg = document.getElementById('graphSvg');
      if (!svg._nodeElems) return;

      // Collect neighbors
      const neighbors = new Set();
      svg._links.forEach(e => {
        const sName = e.source.name || e.source_name;
        const tName = e.target.name || e.target_name;
        if (sName === factorName) neighbors.add(tName);
        if (tName === factorName) neighbors.add(sName);
      });
      neighbors.add(factorName);

      // Fade non-neighbors
      svg._nodeElems.selectAll('circle')
        .attr('opacity', d => neighbors.has(d.name) ? 1.0 : 0.08);
      svg._nodeElems.selectAll('text')
        .attr('opacity', d => neighbors.has(d.name) ? 1.0 : 0.08);
      svg._edgeElems
        .attr('stroke-opacity', d => {
          const sName = d.source.name || d.source_name;
          const tName = d.target.name || d.target_name;
          return (neighbors.has(sName) && neighbors.has(tName)) ? 0.85 : 0.04;
        });

      // Filter table: only rows involving this factor
      const filtered = data.links.filter(l =>
        l.source_name === factorName || l.target_name === factorName
      );
      p2RenderTable(filtered);

      // Show badge
      showBadge(`Factor: ${factorName}`);
      document.getElementById('p2TableMeta').textContent =
        `${filtered.length} pairs with "${factorName.length > 18 ? factorName.slice(0, 16) + '…' : factorName}"`;
    }

    // ── Highlight specific edge (from table row click) ──────────────────────
    function p2HighlightEdge(srcName, tgtName) {
      const svg = document.getElementById('graphSvg');
      if (!svg._nodeElems) return;

      p2State.activeLink = { source_name: srcName, target_name: tgtName };

      svg._nodeElems.selectAll('circle')
        .attr('opacity', d => (d.name === srcName || d.name === tgtName) ? 1.0 : 0.06);
      svg._nodeElems.selectAll('text')
        .attr('opacity', d => (d.name === srcName || d.name === tgtName) ? 1.0 : 0.06);

      svg._edgeElems
        .attr('stroke-opacity', d => {
          const sName = d.source.name || d.source_name;
          const tName = d.target.name || d.target_name;
          const isMatch = (sName === srcName && tName === tgtName) || (sName === tgtName && tName === srcName);
          return isMatch ? 1.0 : 0.03;
        })
        .attr('stroke-width', d => {
          const sName = d.source.name || d.source_name;
          const tName = d.target.name || d.target_name;
          const isMatch = (sName === srcName && tName === tgtName) || (sName === tgtName && tName === srcName);
          return isMatch ? 4 : (d3.max(p2State.graphData.links, l => l.crash_count) ? 1 : 1);
        });
    }

    function p2ResetHighlight() {
      const svg = document.getElementById('graphSvg');
      if (!svg._nodeElems) return;
      svg._nodeElems.selectAll('circle').attr('opacity', 1.0);
      svg._nodeElems.selectAll('text').attr('opacity', 1.0);
      svg._edgeElems.attr('stroke-opacity', 0.65).attr('stroke-width', d => {
        const maxCount = d3.max(p2State.graphData.links, l => l.crash_count) || 1;
        return d3.scaleLinear().domain([0, maxCount]).range([0.8, 5])(d.crash_count);
      });
      document.getElementById('p2TableMeta').textContent = 'sorted by fatal rate';
    }

    // ── Badge helpers ────────────────────────────────────────────────────────
    function showBadge(text) {
      const b = document.getElementById('p2FilterBadge');
      document.getElementById('p2BadgeText').textContent = text;
      b.classList.add('show');
    }
    function hideBadge() {
      document.getElementById('p2FilterBadge').classList.remove('show');
    }

    // ── Co-occurrence Table Render ──────────────────────────────────────────
    let p2SortCol = 'fatal_rate';
    let p2SortDir = 'desc';

    function p2RenderTable(links) {
      const tbody = document.getElementById('p2TableBody');
      if (!links || links.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4"><div class="p2-empty">no combinations match current filters</div></td></tr>';
        return;
      }

      // Sort
      const sorted = [...links].sort((a, b) => {
        let va, vb;
        if (p2SortCol === 'combo') {
          va = a.source_name + a.target_name;
          vb = b.source_name + b.target_name;
          return p2SortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
        }
        va = +a[p2SortCol]; vb = +b[p2SortCol];
        return p2SortDir === 'asc' ? va - vb : vb - va;
      });

      // Rate class
      function rateClass(r) {
        if (r >= 8) return 'critical';
        if (r >= 5) return 'high';
        if (r >= 3) return 'mid';
        return 'low';
      }

      tbody.innerHTML = sorted.map(link => `
    <tr data-src="${link.source_name.replace(/"/g, '&quot;')}" data-tgt="${link.target_name.replace(/"/g, '&quot;')}">
      <td>
        <div class="combo-cell">
          <span class="tag-factor">${link.source_name.length > 18 ? link.source_name.slice(0, 16) + '…' : link.source_name}</span>
          <span class="tag-x">×</span>
          <span class="tag-factor">${link.target_name.length > 18 ? link.target_name.slice(0, 16) + '…' : link.target_name}</span>
        </div>
      </td>
      <td class="col-num">${link.crash_count.toLocaleString()}</td>
      <td class="col-num">${link.fatal_count.toLocaleString()}</td>
      <td><span class="rate-pill rate-${rateClass(link.fatal_rate)}">${link.fatal_rate.toFixed(1)}%</span></td>
    </tr>
  `).join('');

      // Row click → highlight edge in graph + mark row active
      tbody.querySelectorAll('tr').forEach(tr => {
        tr.addEventListener('click', () => {
          tbody.querySelectorAll('tr').forEach(r => r.classList.remove('row-active'));
          tr.classList.add('row-active');
          const src = tr.dataset.src;
          const tgt = tr.dataset.tgt;
          p2HighlightEdge(src, tgt);
          showBadge(`${src.length > 15 ? src.slice(0, 13) + '…' : src} × ${tgt.length > 15 ? tgt.slice(0, 13) + '…' : tgt}`);
        });
      });
    }

    // ── Table sorting ────────────────────────────────────────────────────────
    ['th-combo', 'th-crash', 'th-fatal', 'th-rate'].forEach(id => {
      const colMap = { 'th-combo': 'combo', 'th-crash': 'crash_count', 'th-fatal': 'fatal_count', 'th-rate': 'fatal_rate' };
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('click', () => {
        const col = colMap[id];
        if (p2SortCol === col) {
          p2SortDir = p2SortDir === 'desc' ? 'asc' : 'desc';
        } else {
          p2SortCol = col;
          p2SortDir = 'desc';
        }
        // Update header classes
        document.querySelectorAll('.cooc-table thead th').forEach(th => {
          th.classList.remove('sort-asc', 'sort-desc');
        });
        el.classList.add(p2SortDir === 'desc' ? 'sort-desc' : 'sort-asc');
        // Re-render with current data
        const data = p2State.graphData;
        if (!data) return;
        const displayLinks = p2State.activeFactor
          ? data.links.filter(l => l.source_name === p2State.activeFactor || l.target_name === p2State.activeFactor)
          : data.links;
        p2RenderTable(displayLinks);
      });
    });

    // ── Slider controls ──────────────────────────────────────────────────────
    let p2SliderTimer = null;
    document.getElementById('p2SliderCount').addEventListener('input', e => {
      p2State.minCount = +e.target.value;
      document.getElementById('p2ValCount').textContent = p2State.minCount;
      clearTimeout(p2SliderTimer);
      p2SliderTimer = setTimeout(() => p2LoadGraph(), 600);
    });
    document.getElementById('p2SliderFatal').addEventListener('input', e => {
      p2State.minFatal = +e.target.value;
      document.getElementById('p2ValFatal').textContent = p2State.minFatal + '%';
      clearTimeout(p2SliderTimer);
      p2SliderTimer = setTimeout(() => p2LoadGraph(), 600);
    });

    // ── Badge clear button ────────────────────────────────────────────────────
    document.getElementById('p2BadgeClear').addEventListener('click', () => {
      p2State.activeFactor = null;
      p2State.activeLink = null;
      hideBadge();
      p2ResetHighlight();
      if (p2State.graphData) {
        p2RenderTable(p2State.graphData.links);
        document.getElementById('p2TableMeta').textContent = 'sorted by fatal rate';
      }
    });

    // ── Listen for Panel 1 bar chart factor selection ────────────────────────
    document.addEventListener('p1FactorSelected', e => {
      const factorName = e.detail;
      if (!factorName) return;
      // Scroll to panel 2
      document.querySelector('.panel2-wrapper').scrollIntoView({ behavior: 'smooth', block: 'start' });
      // Highlight the factor in the graph + filter table
      p2HighlightFactor(factorName, 'p1');
    });

    // ── Init Panel 2 on page load ────────────────────────────────────────────
    p2LoadGraph();

    // ════════════════════════════════════════════════════════════════════════
    // PANEL 3 — VRU Vulnerability Analysis
    // ════════════════════════════════════════════════════════════════════════

    const p3State = {
      mode: 'killed',       // 'killed' | 'injured'
      heatData: null,
      bubbleChart: null,
      licenseChart: null,
      preColChart: null,
      ageChart: null,
      injuryChart: null,
    };

    function p3HeatColor(val, max) {
      if (!max || val <= 0) return 'rgba(15,15,22,0.7)';
      const ratio = Math.min(Math.max(val / max, 0), 1);
      if (ratio < 0.25) return 'rgba(29,53,87,0.9)'; // Low
      if (ratio < 0.5) return '#457b9d'; // Med
      if (ratio < 0.75) return '#f4a261'; // High
      return '#e63946'; // Critical
    }

    // ── Mode toggle ──────────────────────────────────────────────────────────
    function p3SetMode(mode) {
      p3State.mode = mode;
      document.getElementById('p3BtnKilled').classList.toggle('active', mode === 'killed');
      document.getElementById('p3BtnInjured').classList.toggle('active', mode === 'injured');
      if (p3State.heatData) p3RenderHeatmap(p3State.heatData);
    }

    async function p3FetchJson(url) {
      const res = await fetch(url);
      const text = await res.text();
      if (!res.ok) {
        let err = `HTTP ${res.status} ${res.statusText}`;
        try {
          const json = JSON.parse(text);
          if (json.error) err += ` - ${json.error}`;
        } catch (_) {}
        throw new Error(err);
      }
      try {
        return JSON.parse(text);
      } catch (e) {
        throw new Error(`Invalid JSON from ${url}: ${text.slice(0, 200)}`);
      }
    }

    // ── Heatmap Borough × Hour (MongoDB) ─────────────────────────────────────
    async function p3LoadHeatmap() {
      try {
        const data = await p3FetchJson('api/get_vru_heatmap.php?' + getFilters());
        p3State.heatData = data;

        // Compute summary stats
        let totKilled = 0, totInjured = 0;
        const boroughKill = {};
        data.forEach(r => {
          totKilled  += +r.total_vru_killed;
          totInjured += +r.total_vru_injured;
          boroughKill[r.borough] = (boroughKill[r.borough] || 0) + +r.total_vru_killed;
        });
        const hotBorough = Object.entries(boroughKill).sort((a,b)=>b[1]-a[1])[0]?.[0] || '—';
        const p3TotalVRUKilledEl = document.getElementById('p3TotalVRUKilled');
        const p3TotalVRUInjEl = document.getElementById('p3TotalVRUInj');
        const p3HotBoroughEl = document.getElementById('p3HotBorough');
        const statVRUKilledEl = document.getElementById('statVRUKilled');
        const statVRUInjuredEl = document.getElementById('statVRUInjured');
        const statHotBoroughEl = document.getElementById('statHotBorough');

        if (p3TotalVRUKilledEl) p3TotalVRUKilledEl.textContent = totKilled.toLocaleString();
        if (p3TotalVRUInjEl) p3TotalVRUInjEl.textContent    = totInjured.toLocaleString();
        if (p3HotBoroughEl) p3HotBoroughEl.textContent     = hotBorough;
        if (statVRUKilledEl) statVRUKilledEl.textContent    = totKilled.toLocaleString();
        if (statVRUInjuredEl) statVRUInjuredEl.textContent   = totInjured.toLocaleString();
        if (statHotBoroughEl) statHotBoroughEl.textContent   = hotBorough;

        p3RenderHeatmap(data);
      } catch (e) {
        console.error('P3 heatmap load failed:', e);
        const body = document.getElementById('p3HeatmapBody');
        if (body) body.textContent = 'Failed to load VRU heatmap: ' + e.message;
      }
    }

    function p3RenderHeatmap(data) {
      const BOROUGHS = ['BROOKLYN','QUEENS','MANHATTAN','BRONX','STATEN ISLAND'];
      const useKilled = p3State.mode === 'killed';
      const field = useKilled ? 'total_vru_killed' : 'total_vru_injured';

      // Build matrix [borough][hour]
      const matrix = {};
      BOROUGHS.forEach(b => { matrix[b] = {}; });
      data.forEach(r => {
        if (matrix[r.borough]) matrix[r.borough][r.crash_hour] = r;
      });

      let maxVal = 0;
      data.forEach(r => { const v = +r[field]; if (v > maxVal) maxVal = v; });

      const CELL_W = 28, CELL_H = 42, PAD_L = 100, PAD_T = 32, PAD_B = 20;
      const W = PAD_L + 24 * CELL_W, H = PAD_T + BOROUGHS.length * CELL_H + PAD_B;

      const body = document.getElementById('p3HeatmapBody');
      body.innerHTML = '';
      const svg = d3.select(body).append('svg')
        .attr('width', '100%').attr('height', H)
        .attr('viewBox', `0 0 ${W} ${H}`)
        .style('display', 'block');

      // Hour labels (top)
      for (let h = 0; h < 24; h++) {
        svg.append('text')
          .attr('x', PAD_L + h * CELL_W + CELL_W / 2)
          .attr('y', PAD_T - 6)
          .attr('text-anchor', 'middle')
          .attr('font-size', 9).attr('fill', '#55556a')
          .attr('font-family', 'JetBrains Mono, monospace')
          .text(h % 3 === 0 ? String(h).padStart(2,'0') : '');
      }

      // Borough rows
      BOROUGHS.forEach((b, bi) => {
        const y = PAD_T + bi * CELL_H;
        // Label
        svg.append('text')
          .attr('x', PAD_L - 8).attr('y', y + CELL_H / 2 + 4)
          .attr('text-anchor', 'end')
          .attr('font-size', 10).attr('fill', '#909090')
          .attr('font-family', 'Inter, sans-serif').attr('font-weight', 500)
          .text(b.charAt(0) + b.slice(1).toLowerCase());

        for (let h = 0; h < 24; h++) {
          const row  = matrix[b][h] || {};
          const val  = +(row[field] || 0);
          const x    = PAD_L + h * CELL_W;
          const fill = p3HeatColor(val, maxVal);

          const cell = svg.append('rect')
            .attr('x', x+1).attr('y', y+1)
            .attr('width', CELL_W-2).attr('height', CELL_H-2)
            .attr('rx', 2).attr('fill', fill)
            .style('cursor', 'pointer');

          // Tooltip on hover
          cell.on('mouseover', function(event) {
            const tip = d3.select('body').append('div').attr('class', 'p3-tip')
              .style('position','fixed').style('pointer-events','none')
              .style('background','#17171e').style('border','1px solid rgba(255,255,255,0.1)')
              .style('border-radius','4px').style('padding','7px 10px')
              .style('font-family','JetBrains Mono,monospace').style('font-size','11px')
              .style('color','#ededf2').style('z-index','9999')
              .style('box-shadow','0 4px 16px rgba(0,0,0,0.6)');
            tip.html(`<b style="color:#f4a261">${b} · ${String(h).padStart(2,'0')}:00</b><br>
              ${useKilled ? 'VRU killed' : 'VRU injured'}: <b>${val}</b><br>
              Total crashes: ${row.total_crashes||0}`);
            tip.style('left',(event.clientX+14)+'px').style('top',(event.clientY-10)+'px');
          }).on('mousemove', function(event) {
            d3.select('.p3-tip').style('left',(event.clientX+14)+'px').style('top',(event.clientY-10)+'px');
          }).on('mouseout', () => d3.selectAll('.p3-tip').remove());
        }
      });
    }

    // ── Bubble Chart (PostgreSQL) ─────────────────────────────────────────────
    async function p3LoadBubble() {
      try {
        const data = await p3FetchJson('api/get_vru_bubble.php?' + getFilters());

        const maxBubble = Math.max(...data.map(r => +r.vru_killed || 0));

      const datasets = data.map(r => ({
        label: r.vehicle_type,
        data: [{
          x: +r.crash_involving,
          y: +r.vru_kill_rate_per_1000,
          r: Math.max(4, Math.sqrt((+r.vru_killed || 0) / (maxBubble || 1)) * 28),
        }],
        backgroundColor: `rgba(230,57,70,${0.2 + (+r.vru_kill_rate_per_1000 / 10) * 0.6})`,
        borderColor: '#e63946',
        borderWidth: 1,
      }));

      const ctx = document.getElementById('p3BubbleCanvas').getContext('2d');
      if (p3State.bubbleChart) p3State.bubbleChart.destroy();

      p3State.bubbleChart = new Chart(ctx, {
        type: 'bubble',
        data: { datasets },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#17171e', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1,
              titleColor: '#f4a261', bodyColor: '#909090',
              titleFont: { family: 'JetBrains Mono', size: 11 },
              bodyFont:  { family: 'JetBrains Mono', size: 10 },
              callbacks: {
                title: items => items[0].dataset.label,
                label: item => [
                  `crashes: ${item.raw.x.toLocaleString()}`,
                  `kill rate: ${item.raw.y.toFixed(2)}/1,000`,
                  `VRU killed: ${data.find(r=>r.vehicle_type===item.dataset.label)?.vru_killed||0}`,
                ]
              }
            }
          },
          scales: {
            x: {
              title: { display: true, text: 'Total Crashes Involving Vehicle Type', color: '#55556a', font: { family: 'Inter', size: 10 } },
              grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#3a3a50', font: { family: 'JetBrains Mono', size: 9 } }
            },
            y: {
              title: { display: true, text: 'VRU Kill Rate per 1,000 Crashes', color: '#55556a', font: { family: 'Inter', size: 10 } },
              grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#3a3a50', font: { family: 'JetBrains Mono', size: 9 } }
            }
          },
          // Draw vehicle type labels on bubbles
          animation: { onComplete: function() {
            const chart = this;
            const ctx2  = chart.ctx;
            chart.data.datasets.forEach((ds, di) => {
              const meta = chart.getDatasetMeta(di);
              if (!meta.hidden) {
                const el = meta.data[0];
                if (!el) return;
                ctx2.save();
                ctx2.fillStyle = '#ededf2';
                ctx2.font = '9px Inter';
                ctx2.textAlign = 'center';
                ctx2.textBaseline = 'middle';
                const lbl = ds.label.length > 12 ? ds.label.slice(0,10)+'…' : ds.label;
                ctx2.fillText(lbl, el.x, el.y);
                ctx2.restore();
              }
            });
          }}
        }
      });
    } catch (e) {
      console.error('P3 bubble load failed:', e);
      const bubbleBody = document.getElementById('p3BubbleCanvas')?.closest('.p3-chart-body');
      if (bubbleBody) bubbleBody.textContent = 'Failed to load VRU bubble: ' + e.message;
    }
}

    // ── Driver Behavior (PostgreSQL) ──────────────────────────────────────────
    async function p3LoadDriver() {
      try {
        const data = await p3FetchJson('api/get_vru_driver.php?' + getFilters());

        // Donut — License Status
        const lic = data.license_status || [];
      const licCtx = document.getElementById('p3LicenseCanvas').getContext('2d');
      if (p3State.licenseChart) p3State.licenseChart.destroy();
      p3State.licenseChart = new Chart(licCtx, {
        type: 'doughnut',
        data: {
          labels: lic.map(r => r.driver_license_status),
          datasets: [{ data: lic.map(r => +r.total), backgroundColor: ['#457b9d','#e63946','#f4a261','#2d6a4f'], borderWidth: 0 }]
        },
        options: {
          responsive: true, maintainAspectRatio: false, cutout: '65%',
          plugins: {
            legend: { position: 'bottom', labels: { color: '#909090', font: { family: 'Inter', size: 10 }, padding: 8, boxWidth: 10 } },
            tooltip: {
              backgroundColor: '#17171e', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1,
              bodyFont: { family: 'JetBrains Mono', size: 10 }, titleFont: { family: 'JetBrains Mono', size: 11 },
              callbacks: { label: item => ` ${item.label}: ${item.raw.toLocaleString()} (${lic[item.dataIndex]?.pct}%)` }
            }
          }
        }
      });

      // Horizontal bar — Pre-collision Action
      const pre = data.pre_collision || [];
      const preCtx = document.getElementById('p3PreCollisionCanvas').getContext('2d');
      if (p3State.preColChart) p3State.preColChart.destroy();
      p3State.preColChart = new Chart(preCtx, {
        type: 'bar',
        data: {
          labels: pre.map(r => r.pre_collision),
          datasets: [{ data: pre.map(r => +r.total), backgroundColor: '#457b9d', borderRadius: 2, borderSkipped: false }]
        },
        options: {
          indexAxis: 'y', responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#17171e', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1,
              bodyFont: { family: 'JetBrains Mono', size: 10 },
              callbacks: { label: item => ` ${item.raw.toLocaleString()} crashes (${pre[item.dataIndex]?.pct}%)` }
            }
          },
          scales: {
            x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#3a3a50', font: { family: 'JetBrains Mono', size: 9 } } },
            y: { grid: { display: false }, ticks: { color: '#909090', font: { family: 'Inter', size: 10 } } }
          }
        }
      });
    } catch (e) {
      console.error('P3 driver load failed:', e);
      const driverCard = document.getElementById('p3LicenseCanvas')?.closest('.p3-card');
      if (driverCard) driverCard.querySelector('.p3-driver-body').textContent = 'Failed to load VRU driver behavior: ' + e.message;
    }
}

    // ── VRU Victim Profile (PostgreSQL) ──────────────────────────────────────
    async function p3LoadProfile() {
      try {
        const data = await p3FetchJson('api/get_vru_profile.php?' + getFilters());

        // Bar — Age Group
        const age = data.age_group || [];
      const ageCtx = document.getElementById('p3AgeCanvas').getContext('2d');
      if (p3State.ageChart) p3State.ageChart.destroy();
      p3State.ageChart = new Chart(ageCtx, {
        type: 'bar',
        data: {
          labels: age.map(r => r.age_group),
          datasets: [{ data: age.map(r => +r.total), backgroundColor: '#f4a261', borderRadius: 2, borderSkipped: false }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#17171e', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1,
              bodyFont: { family: 'JetBrains Mono', size: 10 },
              callbacks: { label: item => ` ${item.raw.toLocaleString()} (${age[item.dataIndex]?.pct}%)` }
            }
          },
          scales: {
            x: { grid: { display: false }, ticks: { color: '#909090', font: { family: 'Inter', size: 9 } } },
            y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#3a3a50', font: { family: 'JetBrains Mono', size: 9 } } }
          }
        }
      });

      // Horizontal bar — Bodily Injury
      const inj = data.bodily_injury || [];
      const injCtx = document.getElementById('p3InjuryCanvas').getContext('2d');
      if (p3State.injuryChart) p3State.injuryChart.destroy();
      p3State.injuryChart = new Chart(injCtx, {
        type: 'bar',
        data: {
          labels: inj.map(r => r.bodily_injury),
          datasets: [{ data: inj.map(r => +r.total), backgroundColor: '#e63946', borderRadius: 2, borderSkipped: false }]
        },
        options: {
          indexAxis: 'y', responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#17171e', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1,
              bodyFont: { family: 'JetBrains Mono', size: 10 },
              callbacks: { label: item => ` ${item.raw.toLocaleString()} (${inj[item.dataIndex]?.pct}%)` }
            }
          },
          scales: {
            x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#3a3a50', font: { family: 'JetBrains Mono', size: 9 } } },
            y: { grid: { display: false }, ticks: { color: '#909090', font: { family: 'Inter', size: 10 } } }
          }
        }
      });

    } catch (e) {
      console.error('P3 profile load failed:', e);
      const profileCard = document.getElementById('p3AgeCanvas')?.closest('.p3-card');
      if (profileCard) profileCard.querySelector('.p3-profile-body').textContent = 'Failed to load VRU profile: ' + e.message;
    }
  }

    // ── Init Panel 3 ─────────────────────────────────────────────────────────
    p3LoadHeatmap();
    p3LoadBubble();
    p3LoadDriver();
    p3LoadProfile();


  </script>


</div>
</body>
</html>