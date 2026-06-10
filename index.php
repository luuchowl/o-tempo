<?php
$csv_path    = __DIR__ . '/data/weather.csv';
$weather_json = 'null';

if (is_readable($csv_path)) {
    $lines   = file($csv_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $headers = array_map('trim', explode(';', array_shift($lines)));
    $dt_idx  = array_search('date_time',   $headers, true);
    $tp_idx  = array_search('temperatura', $headers, true);
    $rows    = [];
    foreach ($lines as $line) {
        $vals  = explode(';', $line);
        $dt    = isset($vals[$dt_idx]) ? trim($vals[$dt_idx]) : '';
        $temp  = isset($vals[$tp_idx]) ? trim($vals[$tp_idx]) : '';
        if ($dt !== '' && $temp !== '') {
            $rows[] = [$dt, (float) $temp];
        }
    }
    $weather_json = json_encode($rows, JSON_UNESCAPED_UNICODE);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>o-tempo — Ibirapuera</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { background: #0a0a0f; overflow: hidden; font-family: monospace; color: #ccc; }

    #hint {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 11px;
      opacity: 0.4;
      pointer-events: none;
    }

    #legend {
      position: fixed;
      bottom: 24px;
      right: 24px;
      display: flex;
      align-items: stretch;
      gap: 8px;
      transition: right 0.25s ease;
    }
    #legend.shifted { right: 154px; }
    #legend-bar {
      width: 10px;
      height: 120px;
      background: linear-gradient(to bottom, #ff5722, #ffb300, #4fc3f7);
      border-radius: 3px;
    }
    #legend-labels {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      font-size: 11px;
    }

    /* ── Sidebar ── */
    #sidebar {
      position: fixed;
      top: 0; right: 0; bottom: 0;
      width: 180px;
      background: rgba(10, 10, 20, 0.94);
      border-left: 1px solid #1e1e3f;
      transform: translateX(180px);
      transition: transform 0.25s ease;
      display: flex;
      flex-direction: column;
      padding: 24px 14px 20px;
      gap: 16px;
    }
    #sidebar.open { transform: translateX(0); }

    .sb-field { display: flex; flex-direction: column; gap: 4px; }
    .sb-label { font-size: 9px; letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.4; }
    .sb-value { font-size: 15px; font-weight: bold; color: #e8e8f0; }
    .sb-value.temp { font-size: 22px; }

    /* ── Tooltip ── */
    #tooltip {
      position: fixed;
      pointer-events: none;
      background: rgba(10, 10, 20, 0.92);
      border: 1px solid #2e2e5a;
      border-radius: 4px;
      padding: 8px 10px;
      font-size: 11px;
      line-height: 1.9;
      opacity: 0;
      transition: opacity 0.12s ease;
      white-space: nowrap;
    }
    #tooltip.visible { opacity: 1; }

    #error {
      display: none;
      position: fixed;
      inset: 0;
      background: #0a0a0f;
      place-items: center;
      font-size: 14px;
      text-align: center;
      line-height: 2;
    }
    #error.visible { display: grid; }
  </style>
</head>
<body>

<div id="hint">drag to orbit &nbsp;·&nbsp; Parque Ibirapuera, São Paulo</div>

<div id="legend">
  <div id="legend-bar"></div>
  <div id="legend-labels">
    <span id="temp-max">—</span>
    <span id="temp-min">—</span>
  </div>
</div>

<div id="tooltip"></div>

<div id="sidebar">
  <div class="sb-field"><span class="sb-label">day</span><span class="sb-value" id="sb-day">—</span></div>
  <div class="sb-field"><span class="sb-label">month</span><span class="sb-value" id="sb-month">—</span></div>
  <div class="sb-field"><span class="sb-label">avg</span><span class="sb-value temp" id="sb-avg">—</span></div>
  <div class="sb-field"><span class="sb-label">min / max</span><span class="sb-value" id="sb-minmax">—</span></div>
</div>

<div id="error">
  <div>
    <?php if ($weather_json === 'null'): ?>
      Could not read <code>data/weather.csv</code>.<br/>
      Make sure the file exists and is readable by the web server.
    <?php else: ?>
      An unexpected error occurred.
    <?php endif; ?>
  </div>
</div>

<!-- PHP-injected weather data: array of [date_time, temperatura] tuples -->
<script>
const WEATHER_DATA = <?= $weather_json ?>;
</script>

<script type="importmap">
{
  "imports": {
    "three": "https://unpkg.com/three@0.170.0/build/three.module.js",
    "three/addons/": "https://unpkg.com/three@0.170.0/examples/jsm/"
  }
}
</script>

<script type="module">
import { initChart } from './chart.js';
initChart(WEATHER_DATA);
</script>
</body>
</html>
