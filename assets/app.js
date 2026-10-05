(function () {
  'use strict';
  var $ = function (s) { return document.querySelector(s); };
  var root = document.documentElement;
  var init = window.INITIAL || { from: '', to: '', transport: '', spots: [] };
  var first = true;

  // ---- Theme toggle ----
  function paintBtn() { $('#themeBtn').textContent = root.dataset.theme === 'dark' ? '☀️ Light' : '🌙 Dark'; }
  $('#themeBtn').addEventListener('click', function () {
    var t = root.dataset.theme === 'dark' ? 'light' : 'dark';
    root.dataset.theme = t;
    try { localStorage.setItem('bd-theme', t); } catch (e) {}
    paintBtn();
  });
  paintBtn();

  // ---- Helpers ----
  function taka(n) { return '৳' + Number(n).toLocaleString('en-US'); }
  function el(tag, props, text) {
    var e = document.createElement(tag);
    for (var k in props) e[k] = props[k];
    if (text) e.textContent = text;
    return e;
  }
  function updateState() {
    var n = document.querySelectorAll('#spots input:checked').length;
    $('#spotCount').textContent = n + ' selected';
    $('#submitBtn').disabled = !($('#from').value && $('#to').value && $('#transport').value && n > 0);
  }

  // ---- Load route-dependent options from api.php ----
  function load() {
    var from = $('#from').value, to = $('#to').value;
    var tSel = $('#transport'), spots = $('#spots');
    if (!from || !to) {
      tSel.innerHTML = '<option value="">Choose start &amp; destination first</option>';
      tSel.disabled = true;
      spots.innerHTML = '<p class="hint">Choose start and destination to see spots.</p>';
      $('#infoCard').hidden = true;
      updateState();
      return;
    }
    fetch('api.php?from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to))
      .then(function (r) { if (!r.ok) throw new Error('bad'); return r.json(); })
      .then(function (d) {
        // transport
        tSel.innerHTML = '<option value="">Select transport…</option>';
        d.modes.forEach(function (m) {
          var o = el('option', { value: m.id }, m.label + ' — ' + m.hint);
          if (first && init.transport === m.id) o.selected = true;
          tSel.appendChild(o);
        });
        tSel.disabled = false;
        // spots
        spots.innerHTML = '';
        d.spots.forEach(function (s) {
          var lb = el('label', { className: 'spot' });
          var cb = el('input', { type: 'checkbox', name: 'spots[]', value: s.name });
          if (first && init.spots.indexOf(s.name) > -1) cb.checked = true;
          cb.addEventListener('change', updateState);
          lb.appendChild(cb);
          lb.appendChild(el('span', {}, s.name));
          lb.appendChild(el('small', {}, s.fee ? taka(s.fee) : 'Free'));
          spots.appendChild(lb);
        });
        // info
        $('#info').innerHTML =
          '<div><span>🏨 Avg hotel / room / night</span><b>' + taka(d.hotel) + '</b></div>' +
          '<div><span>🍽️ Avg food / person / day</span><b>' + taka(d.food) + '</b></div>' +
          '<div><span>🛺 Local transport / day (4 people)</span><b>' + taka(d.local) + '</b></div>' +
          '<div><span>📍 Approx. road distance</span><b>' + d.km + ' km</b></div>';
        $('#infoCard').hidden = false;
        first = false;
        updateState();
      })
      .catch(function () {
        spots.innerHTML = '<p class="hint">Could not load data. Run the project with a PHP server (see README).</p>';
      });
  }

  $('#from').addEventListener('change', function () { first = false; load(); });
  $('#to').addEventListener('change', function () { first = false; load(); });
  $('#transport').addEventListener('change', updateState);
  $('#selAll').addEventListener('click', function () {
    document.querySelectorAll('#spots input').forEach(function (c) { c.checked = true; }); updateState();
  });
  $('#selNone').addEventListener('click', function () {
    document.querySelectorAll('#spots input').forEach(function (c) { c.checked = false; }); updateState();
  });
  load();
})();
