// public/js/graph.js
/* global Chart */
(() => {
  const $ = (s, r = document) => r.querySelector(s);

  const toNum = (v) => (v === null || v === '' || v === undefined ? null : Number(v));
  const fmt   = (v, d = 2) => (v == null ? '—' : Number(v).toFixed(d));

  // Initiale History
  // ----------------------------------------------------------------------------------------
  const initialScript  = $('#history-data');
  const initialHistory = initialScript ? JSON.parse(initialScript.textContent || '[]') : [];
  let currentRange = '60min';

  // Labels formatieren je nach Range
  //----------------------------------------------------------------------------------------
  function labelFor(ts, range) {
    if (range === '60min') return ts.slice(11, 16); 
    if (range === '24h')   return ts.slice(5, 10) + ' ' + ts.slice(11, 13) + 'h';
    if (range === 'week')  return ts.slice(5, 10); 
    return ts;
  }

  function makeSeries(history, range) {
    const labels = history.map(r => labelFor(r.timestamp, range));
    return {
      labels,
      temperature: history.map(r => toNum(r.temperature)),
      humidity:    history.map(r => toNum(r.humidity)),
      pressure:    history.map(r => toNum(r.pressure)),
      brightness:  history.map(r => toNum(r.brightness)),
    };
  }

  // Chart Builder
  // ----------------------------------------------------------------------------------------
  const makeChart = (canvasId, series, key, title, unit) => {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height || 280);
    gradient.addColorStop(0, 'rgba(56,189,248,0.45)');
    gradient.addColorStop(1, 'rgba(56,189,248,0.02)');

    return new Chart(ctx, {
      type: 'line',
      data: {
        labels: series.labels,
        datasets: [{
          label: title,
          data: series[key],
          borderColor: 'rgba(56,189,248,0.9)',
          backgroundColor: gradient,
          pointRadius: 0,
          borderWidth: 2,
          tension: 0.25,
          spanGaps: true,
          fill: true
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 350, easing: 'easeOutQuart' },
        plugins: {
          legend: { display: false },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label: (ctx) => {
                const v = ctx.parsed.y;
                return (v == null ? '—' : v) + (unit ? ' ' + unit : '');
              }
            }
          }
        },
        interaction: { mode: 'index', intersect: false },
        scales: {
          x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,0.12)' } },
          y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,0.12)' } }
        }
      }
    });
  };

  // Charts bauen und auf 60min stellen
  // ------------------------------------------------------------------------------------------------- 
  let charts = {};
  (function initialRender() {
    const series = makeSeries(initialHistory, '60min');
    charts.temperature = makeChart('chart-temperature', series, 'temperature', 'Temperatur', '°C');
    charts.humidity    = makeChart('chart-humidity',    series, 'humidity',    'Luftfeuchte', '%');
    charts.pressure    = makeChart('chart-pressure',    series, 'pressure',    'Luftdruck',   'hPa');
    charts.brightness  = makeChart('chart-brightness',  series, 'brightness',  'Helligkeit',  'Lux');
  })();

  // Kacheln Aktualisieren (alle 3 s)
  // ----------------------------------------------------------------------------------------
  async function refreshLive() {
    try {
      const res = await fetch('/status/live', { cache: 'no-store' });
      if (!res.ok) return;
      const j = await res.json();

      const elT = $('#metric-temperature');
      const elH = $('#metric-humidity');
      const elP = $('#metric-pressure');
      const elB = $('#metric-brightness');
      const elU = $('#metric-lastUpdate');

      if (elT) elT.textContent = fmt(j.temperature);
      if (elH) elH.textContent = fmt(j.humidity);
      if (elP) elP.textContent = fmt(j.pressure);
      if (elB) elB.textContent = fmt(j.brightness);
      if (elU) elU.textContent = j.timestamp ?? '—';
    } catch (_) {}
  }

  // History für gewählte Zeitrange laden
  // ----------------------------------------------------------------------------------------
  const chartsEmptyEl = $('#charts-empty');

  function seriesHasAllNull(seriesArr) {
    return seriesArr.every(v => v === null || Number.isNaN(v));
  }

  function updateChartsFromSeries(series) {
    const update = (chart, key) => {
      if (!chart) return;
      chart.data.labels = series.labels;
      chart.data.datasets[0].data = series[key];
      chart.update('none');
    };
    update(charts.temperature, 'temperature');
    update(charts.humidity,    'humidity');
    update(charts.pressure,    'pressure');
    update(charts.brightness,  'brightness');
  }

  async function loadHistory(range) {
    const url = `/status/history?range=${encodeURIComponent(range)}`;
    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) return;

    const { range: confirmedRange, history } = await res.json();
    currentRange = confirmedRange;

    const series = makeSeries(history, confirmedRange);
    const noTemp = seriesHasAllNull(series.temperature);
    const noHum  = seriesHasAllNull(series.humidity);
    const noPres = seriesHasAllNull(series.pressure);
    const noBri  = seriesHasAllNull(series.brightness);
    const noData = noTemp && noHum && noPres && noBri;

    if (chartsEmptyEl) chartsEmptyEl.hidden = !noData;

    updateChartsFromSeries(series);
  }

  // Dropdown binden
  // --------------------------------------------------
  const rangeSelect = $('#range-select');
  if (rangeSelect) {
    rangeSelect.addEventListener('change', () => {
      const val = rangeSelect.value;
      loadHistory(val).catch(() => {});
    });
  }

  refreshLive();
  setInterval(refreshLive, 1000);

  // Charts nur neu laden, wenn Range 1min/60min ist: jede Minute sinnvoll
  // ----------------------------------------------
  setInterval(() => {
    if (currentRange === '60min') {
      loadHistory(currentRange).catch(() => {});
    }
  }, 60000);
})();
