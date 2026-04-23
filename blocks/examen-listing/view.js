(function () {
  'use strict';

  var root = document.querySelector('.dc26-examen-listing');
  if (!root) return;

  var details = root.querySelectorAll('.dc26-examen-listing__year');
  var searchInput = root.querySelector('.dc26-examen-listing__search');
  var sessions = root.querySelectorAll('.dc26-examen-session');
  var isLoggedIn = root.hasAttribute('data-logged-in');

  // ── Accordion: one open at a time (only when not searching) ──

  details.forEach(function (el) {
    el.addEventListener('toggle', function () {
      if (!el.open || (searchInput && searchInput.value.trim())) return;
      details.forEach(function (other) {
        if (other !== el) other.removeAttribute('open');
      });
    });
  });

  // ── Search filter ──

  if (searchInput) {
    var debounceTimer;
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(applySearch, 200);
    });
  }

  function normalize(str) {
    return str.toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }

  function applySearch() {
    var query = normalize(searchInput.value.trim());

    if (!query) {
      resetView();
      return;
    }

    root.classList.add('is-searching');

    details.forEach(function (yearBlock) {
      var yearSessions = yearBlock.querySelectorAll('.dc26-examen-session');
      var hasMatch = false;

      yearSessions.forEach(function (session) {
        var text = normalize(session.textContent);
        var match = text.indexOf(query) !== -1;
        session.classList.toggle('is-hidden', !match);
        if (match) hasMatch = true;
      });

      yearBlock.classList.toggle('is-hidden', !hasMatch);
      if (hasMatch) {
        yearBlock.setAttribute('open', '');
      } else {
        yearBlock.removeAttribute('open');
      }
    });
  }

  function resetView() {
    root.classList.remove('is-searching');

    sessions.forEach(function (s) {
      s.classList.remove('is-hidden');
    });

    details.forEach(function (el, i) {
      el.classList.remove('is-hidden');
      if (i === 0) {
        el.setAttribute('open', '');
      } else {
        el.removeAttribute('open');
      }
    });
  }

  // ── Progress tracking ──

  if (!isLoggedIn) return;

  var cfg = {
    restUrl: root.dataset.restUrl,
    nonce: root.dataset.nonce
  };

  if (!cfg.restUrl || !cfg.nonce) return;

  root.addEventListener('click', function (e) {
    var btn = e.target.closest('.dc26-examen-check');
    if (!btn) return;

    e.preventDefault();
    var li = btn.closest('li[data-key]');
    if (!li) return;

    var key = li.dataset.key;
    var wasDone = li.classList.contains('is-done');
    var nowDone = !wasDone;

    li.classList.toggle('is-done', nowDone);
    var yearBlock = li.closest('.dc26-examen-listing__year');
    updateSessionProgress(li.closest('.dc26-examen-session'));
    updateYearProgress(yearBlock);

    fetch(cfg.restUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': cfg.nonce
      },
      credentials: 'same-origin',
      body: JSON.stringify({ key: key, done: nowDone })
    })
    .then(function (res) {
      if (!res.ok) throw new Error(res.status);
    })
    .catch(function () {
      li.classList.toggle('is-done', wasDone);
      updateSessionProgress(li.closest('.dc26-examen-session'));
      updateYearProgress(yearBlock);
    });
  });

  function updateSessionProgress(session) {
    if (!session) return;
    var progressWrap = session.querySelector('.dc26-examen-session__progress');
    if (!progressWrap) return;

    var total = parseInt(progressWrap.dataset.total, 10) || 0;
    var done = session.querySelectorAll('li.is-done').length;

    progressWrap.dataset.done = done;
    var pct = total > 0 ? Math.round((done / total) * 100) : 0;

    var fill = progressWrap.querySelector('.dc26-examen-session__progress-fill');
    if (fill) fill.style.width = pct + '%';

    var label = progressWrap.querySelector('.dc26-examen-session__progress-label');
    if (label) label.textContent = done + '/' + total;
  }

  function updateYearProgress(yearBlock) {
    if (!yearBlock) return;
    var progressWrap = yearBlock.querySelector('.dc26-examen-year__progress');
    if (!progressWrap) return;

    var total = parseInt(progressWrap.dataset.total, 10) || 0;
    var done = yearBlock.querySelectorAll('li.is-done').length;

    progressWrap.dataset.done = done;
    var pct = total > 0 ? Math.round((done / total) * 100) : 0;

    var fill = progressWrap.querySelector('.dc26-examen-year__progress-fill');
    if (fill) fill.style.width = pct + '%';

    var label = progressWrap.querySelector('.dc26-examen-year__progress-label');
    if (label) label.textContent = done + '/' + total;
  }
})();
