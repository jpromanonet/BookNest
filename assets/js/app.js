/**
 * BookNest — client-side interactions
 * Pastel Fantasy Terminal UI helpers
 */
(function () {
  'use strict';

  var VIEW_KEY = 'booknest-library-view';
  var DEBOUNCE_MS = 300;

  /* -----------------------------------------------------------------------
     Utilities
     ----------------------------------------------------------------------- */

  function $(selector, root) {
    return (root || document).querySelector(selector);
  }

  function $$(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function debounce(fn, wait) {
    var timer;
    return function () {
      var ctx = this;
      var args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(ctx, args);
      }, wait);
    };
  }

  function getCsrfToken(form) {
    if (form) {
      var input = form.querySelector('input[name="_csrf"]');
      if (input && input.value) {
        return input.value;
      }
    }
    var global = $('input[name="_csrf"]');
    return global ? global.value : '';
  }

  function setFieldValue(form, name, value) {
    if (value === null || value === undefined || value === '') {
      return;
    }
    var field = form.querySelector('[name="' + name + '"]');
    if (!field) {
      return;
    }
    if (field.type === 'checkbox') {
      field.checked = !!value;
    } else {
      field.value = String(value);
    }
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
  }

  /* -----------------------------------------------------------------------
     Sidebar mobile toggle
     ----------------------------------------------------------------------- */

  function initSidebarToggle() {
    var shell = $('.app-shell');
    var toggle = $('#sidebar-toggle');
    var backdrop = $('.sidebar-backdrop');

    if (!shell || !toggle) {
      return;
    }

    function openSidebar() {
      shell.classList.add('sidebar-open');
      toggle.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
      shell.classList.remove('sidebar-open');
      toggle.setAttribute('aria-expanded', 'false');
    }

    function isOpen() {
      return shell.classList.contains('sidebar-open');
    }

    toggle.addEventListener('click', function () {
      if (isOpen()) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    if (backdrop) {
      backdrop.addEventListener('click', closeSidebar);
    }

    $$('.side-nav a, .sidebar-nav a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.matchMedia('(max-width: 900px)').matches) {
          closeSidebar();
        }
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpen()) {
        closeSidebar();
      }
    });
  }

  /* -----------------------------------------------------------------------
     Library view mode (table / grid)
     ----------------------------------------------------------------------- */

  function applyViewMode(mode) {
    var container = $('.library-view');
    if (!container) {
      return;
    }

    var normalized = mode === 'grid' ? 'grid' : 'table';
    container.setAttribute('data-view', normalized);

    $$('[data-view-mode]').forEach(function (btn) {
      var btnMode = btn.getAttribute('data-view-mode');
      btn.classList.toggle('is-active', btnMode === normalized);
      btn.setAttribute('aria-pressed', btnMode === normalized ? 'true' : 'false');
    });
  }

  function initLibraryViewMode() {
    var container = $('.library-view');
    if (!container) {
      return;
    }

    var saved = localStorage.getItem(VIEW_KEY);
    var initial = saved === 'grid' ? 'grid' : 'table';
    applyViewMode(initial);

    $$('[data-view-mode]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var mode = btn.getAttribute('data-view-mode');
        if (mode !== 'grid' && mode !== 'table') {
          return;
        }
        localStorage.setItem(VIEW_KEY, mode);
        applyViewMode(mode);
      });
    });
  }

  /* -----------------------------------------------------------------------
     Filter panel toggle
     ----------------------------------------------------------------------- */

  function initFilterPanel() {
    var panel = $('.filter-panel');
    var toggle = $('#filter-toggle');

    if (!panel) {
      return;
    }

    var storageKey = 'booknest-filter-open';
    var stored = localStorage.getItem(storageKey);

    if (stored === '0') {
      panel.classList.add('is-collapsed');
    } else if (stored === '1') {
      panel.classList.remove('is-collapsed');
    }

    function syncToggle() {
      var collapsed = panel.classList.contains('is-collapsed');
      if (toggle) {
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      }
    }

    syncToggle();

    function handleToggle(e) {
      if (e) {
        e.preventDefault();
      }
      panel.classList.toggle('is-collapsed');
      var collapsed = panel.classList.contains('is-collapsed');
      localStorage.setItem(storageKey, collapsed ? '0' : '1');
      syncToggle();
    }

    if (toggle) {
      toggle.addEventListener('click', handleToggle);
    }

    $$('[data-filter-toggle]').forEach(function (el) {
      el.addEventListener('click', handleToggle);
    });
  }

  /* -----------------------------------------------------------------------
     Confirm delete dialogs
     ----------------------------------------------------------------------- */

  function confirmAction(message) {
    return window.confirm(message || '¿Eliminar este registro del archivo? Esta acción no se puede deshacer.');
  }

  function initConfirmDelete() {
    $$('form[data-confirm]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        var msg = form.getAttribute('data-confirm');
        if (!confirmAction(msg)) {
          e.preventDefault();
        }
      });
    });

    $$('[data-confirm]').forEach(function (el) {
      if (el.tagName === 'FORM') {
        return;
      }
      el.addEventListener('click', function (e) {
        var msg = el.getAttribute('data-confirm');
        if (!confirmAction(msg)) {
          e.preventDefault();
          e.stopPropagation();
        }
      });
    });
  }

  /* -----------------------------------------------------------------------
     Global search (debounced)
     ----------------------------------------------------------------------- */

  function initGlobalSearch() {
    var input = $('#global-search');
    if (!input) {
      return;
    }

    var url = input.getAttribute('data-url');
    if (!url) {
      return;
    }

    var wrap = input.closest('.topbar-search') || input.parentElement;
    var dropdown = $('.search-dropdown', wrap);

    if (!dropdown) {
      dropdown = document.createElement('div');
      dropdown.className = 'search-dropdown';
      dropdown.setAttribute('role', 'listbox');
      wrap.appendChild(dropdown);
    }

    var activeIndex = -1;
    var items = [];

    function closeDropdown() {
      dropdown.classList.remove('is-open');
      dropdown.innerHTML = '';
      activeIndex = -1;
      items = [];
    }

    function highlightIndex(index) {
      var links = $$('.search-dropdown-item', dropdown);
      links.forEach(function (link, i) {
        link.classList.toggle('is-highlighted', i === index);
      });
      activeIndex = index;
      if (links[index]) {
        links[index].scrollIntoView({ block: 'nearest' });
      }
    }

    function renderResults(data) {
      var list = (data && data.items) ? data.items : [];
      dropdown.innerHTML = '';

      if (!list.length) {
        dropdown.innerHTML = '<div class="search-dropdown-empty">Sin resultados en el archivo.</div>';
        dropdown.classList.add('is-open');
        return;
      }

      list.forEach(function (item) {
        var link = document.createElement('a');
        link.className = 'search-dropdown-item';
        link.href = item.url || '#';
        link.setAttribute('role', 'option');

        if (item.cover) {
          var img = document.createElement('img');
          img.src = item.cover;
          img.alt = '';
          link.appendChild(img);
        }

        var text = document.createElement('div');
        var title = document.createElement('div');
        title.textContent = item.title || '—';
        title.style.fontWeight = '600';
        text.appendChild(title);

        if (item.subtitle) {
          var sub = document.createElement('div');
          sub.className = 'muted text-xs';
          sub.textContent = item.subtitle;
          text.appendChild(sub);
        }

        link.appendChild(text);
        dropdown.appendChild(link);
      });

      dropdown.classList.add('is-open');
      items = list;
      activeIndex = -1;
    }

    var fetchResults = debounce(function () {
      var q = input.value.trim();
      if (q.length < 2) {
        closeDropdown();
        return;
      }

      var fetchUrl = url + (url.indexOf('?') >= 0 ? '&' : '?') +
        'q=' + encodeURIComponent(q) + '&format=json';

      fetch(fetchUrl, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      })
        .then(function (res) {
          if (!res.ok) {
            throw new Error('Search failed');
          }
          return res.json();
        })
        .then(renderResults)
        .catch(function () {
          dropdown.innerHTML = '<div class="search-dropdown-empty">Error al buscar.</div>';
          dropdown.classList.add('is-open');
        });
    }, DEBOUNCE_MS);

    input.addEventListener('input', fetchResults);

    input.addEventListener('keydown', function (e) {
      var links = $$('.search-dropdown-item', dropdown);
      if (!dropdown.classList.contains('is-open') || !links.length) {
        return;
      }

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightIndex(Math.min(activeIndex + 1, links.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightIndex(Math.max(activeIndex - 1, 0));
      } else if (e.key === 'Enter' && activeIndex >= 0 && links[activeIndex]) {
        e.preventDefault();
        window.location.href = links[activeIndex].href;
      } else if (e.key === 'Escape') {
        closeDropdown();
      }
    });

    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) {
        closeDropdown();
      }
    });
  }

  /* -----------------------------------------------------------------------
     Goodreads metadata fetch (#seek-goodreads)
     ----------------------------------------------------------------------- */

  function initGoodreadsSeek() {
    var btn = $('#seek-goodreads');
    if (!btn) {
      return;
    }

    var form = btn.closest('form');
    if (!form) {
      return;
    }

    var endpoint = btn.getAttribute('data-url') || form.getAttribute('action');
    var queryInput = $('#goodreads-query') ||
      form.querySelector('[name="goodreads_query"]') ||
      form.querySelector('[name="query"]');

    btn.addEventListener('click', function (e) {
      e.preventDefault();

      if (!endpoint) {
        return;
      }

      var query = queryInput ? queryInput.value.trim() : '';
      if (!query) {
        window.alert('Ingresá un ISBN, título o URL de Goodreads.');
        if (queryInput) {
          queryInput.focus();
        }
        return;
      }

      var csrf = getCsrfToken(form);
      btn.disabled = true;
      btn.classList.add('is-loading');
      var originalText = btn.textContent;
      btn.textContent = 'Consultando…';
      var statusEl = $('#goodreads-status');
      if (statusEl) {
        statusEl.textContent = 'Searching the archives…';
      }

      var body = new FormData();
      body.append('query', query);
      if (csrf) {
        body.append('_csrf', csrf);
      }

      fetch(endpoint, {
        method: 'POST',
        body: body,
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
        .then(function (res) {
          return res.json().then(function (data) {
            if (!res.ok || !data.ok) {
              throw new Error((data && data.error) || 'No se pudo obtener metadata.');
            }
            return data.book || {};
          });
        })
        .then(function (book) {
          var fieldMap = {
            title: book.title,
            subtitle: book.subtitle,
            authors_text: book.authors_text,
            isbn10: book.isbn10,
            isbn13: book.isbn13,
            publisher_name: book.publisher,
            publication_year: book.publication_year,
            pages: book.pages,
            language: book.language,
            format: book.format,
            series_name: book.series_name,
            series_number: book.series_number,
            description: book.description,
            genres_text: book.genres_text,
            cover_url: book.cover,
            goodreads_url: book.goodreads_url,
          };

          Object.keys(fieldMap).forEach(function (name) {
            setFieldValue(form, name, fieldMap[name]);
          });

          var coverPreview = $('#cover-preview');
          if (coverPreview && book.cover) {
            coverPreview.src = book.cover;
          }

          btn.textContent = 'BOOK FOUND';
          if (statusEl) {
            statusEl.textContent = 'BOOK FOUND — revisá y editá antes de guardar.';
          }
          setTimeout(function () {
            btn.textContent = originalText;
          }, 2000);
        })
        .catch(function (err) {
          window.alert(err.message || 'Error al consultar Goodreads.');
          btn.textContent = originalText;
          if (statusEl) {
            statusEl.textContent = 'The archive could not be opened.';
          }
        })
        .finally(function () {
          btn.disabled = false;
          btn.classList.remove('is-loading');
        });
    });
  }

  /* -----------------------------------------------------------------------
     Flash dismiss
     ----------------------------------------------------------------------- */

  function initFlashDismiss() {
    $$('.flash-dismiss').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var flash = btn.closest('.flash');
        if (flash) {
          flash.remove();
        }
      });
    });
  }

  /* -----------------------------------------------------------------------
     Modal helpers (data-modal-open / data-modal-close)
     ----------------------------------------------------------------------- */

  function initModals() {
    $$('[data-modal-open]').forEach(function (trigger) {
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        var targetId = trigger.getAttribute('data-modal-open');
        var backdrop = targetId ? document.getElementById(targetId) : null;
        if (backdrop) {
          backdrop.classList.add('is-open');
          backdrop.setAttribute('aria-hidden', 'false');
        }
      });
    });

    $$('[data-modal-close]').forEach(function (trigger) {
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        var backdrop = trigger.closest('.modal-backdrop');
        if (backdrop) {
          backdrop.classList.remove('is-open');
          backdrop.setAttribute('aria-hidden', 'true');
        }
      });
    });

    $$('.modal-backdrop').forEach(function (backdrop) {
      backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) {
          backdrop.classList.remove('is-open');
          backdrop.setAttribute('aria-hidden', 'true');
        }
      });
    });
  }

  /* -----------------------------------------------------------------------
     Progress bars (data-progress="0-100")
     ----------------------------------------------------------------------- */

  function initProgressBars() {
    $$('[data-progress]').forEach(function (bar) {
      var value = parseFloat(bar.getAttribute('data-progress') || '0');
      value = Math.max(0, Math.min(100, value));
      var segments = $$('.progress-bar-segment', bar);
      if (!segments.length) {
        return;
      }
      var filled = Math.round((value / 100) * segments.length);
      segments.forEach(function (seg, i) {
        seg.classList.toggle('is-filled', i < filled);
      });
    });
  }

  /* -----------------------------------------------------------------------
     Charts (Chart.js)
     ----------------------------------------------------------------------- */

  var PASTEL_PALETTE = [
    '#BBA9D6', '#ABC4A4', '#A8C4D8', '#E5B59B', '#D6A2AA', '#CFAC68',
    '#685878', '#61735D', '#895B61', '#C4B5A0', '#9BB8A8', '#D4C4E8'
  ];

  function parseChartData(el) {
    var raw = el.getAttribute('data-chart');
    if (!raw) {
      return null;
    }
    try {
      return JSON.parse(raw);
    } catch (err) {
      return null;
    }
  }

  function initCharts() {
    if (typeof Chart === 'undefined') {
      return;
    }

    Chart.defaults.font.family = '"IBM Plex Mono", "Courier New", monospace';
    Chart.defaults.color = '#403845';
    Chart.defaults.borderColor = '#6E626A';

    $$('canvas[data-chart]').forEach(function (canvas) {
      var data = parseChartData(canvas);
      if (!data || !data.labels) {
        return;
      }

      var type = data.type || (canvas.id && canvas.id.indexOf('pie') >= 0 ? 'pie'
        : canvas.id && canvas.id.indexOf('doughnut') >= 0 ? 'doughnut'
        : canvas.id && canvas.id.indexOf('line') >= 0 ? 'line'
        : canvas.id && canvas.id.indexOf('status') >= 0 ? 'doughnut'
        : canvas.id && canvas.id.indexOf('lang') >= 0 ? 'doughnut'
        : 'bar');

      var colors = data.labels.map(function (_, i) {
        return PASTEL_PALETTE[i % PASTEL_PALETTE.length];
      });

      var isRadial = type === 'pie' || type === 'doughnut';
      var dataset = {
        label: data.label || 'Total',
        data: data.values || [],
        backgroundColor: isRadial || type === 'bar' ? colors : 'rgba(187, 169, 214, 0.55)',
        borderColor: isRadial ? '#6E626A' : '#685878',
        borderWidth: 2,
        tension: 0.25,
        fill: type === 'line',
        pointBackgroundColor: '#BBA9D6',
        pointBorderColor: '#6E626A',
      };

      new Chart(canvas, {
        type: type,
        data: {
          labels: data.labels,
          datasets: [dataset],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: isRadial,
              position: 'bottom',
              labels: { boxWidth: 12, font: { size: 11 } },
            },
          },
          scales: isRadial ? {} : {
            x: {
              ticks: { maxRotation: 45, minRotation: 0, font: { size: 10 } },
              grid: { color: 'rgba(110,98,106,0.15)' },
            },
            y: {
              beginAtZero: true,
              ticks: { precision: 0, font: { size: 11 } },
              grid: { color: 'rgba(110,98,106,0.15)' },
            },
          },
        },
      });
    });
  }

  /* -----------------------------------------------------------------------
     Goodreads bulk enrich
     ----------------------------------------------------------------------- */

  function initGoodreadsEnrich() {
    var startBtn = $('#enrich-start');
    var allBtn = $('#enrich-all');
    if (!startBtn && !allBtn) {
      return;
    }

    var statusEl = $('#enrich-status');
    var pendingEl = $('#enrich-pending');
    var pctEl = $('#enrich-pct');
    var blocks = $$('[data-enrich-block]');
    var running = false;
    var skipIds = [];
    var processed = 0;
    var initialPending = pendingEl ? parseInt(pendingEl.textContent, 10) || 0 : 0;

    function setProgress(pending, total) {
      var base = initialPending || total || 1;
      var done = Math.max(0, base - pending);
      var pct = Math.min(100, Math.round((done / base) * 100));
      if (pctEl) {
        pctEl.textContent = pct + '%';
      }
      var on = Math.round((pct / 100) * blocks.length);
      blocks.forEach(function (b, i) {
        b.classList.toggle('is-on', i < on);
      });
      if (pendingEl) {
        pendingEl.textContent = String(pending);
      }
    }

    function run(mode) {
      if (running) {
        return;
      }
      running = true;
      processed = 0;
      skipIds = [];
      initialPending = pendingEl ? parseInt(pendingEl.textContent, 10) || 0 : 0;
      if (mode === 'all') {
        initialPending = 0;
      }
      [startBtn, allBtn].forEach(function (b) {
        if (b) {
          b.disabled = true;
        }
      });
      if (statusEl) {
        statusEl.textContent = 'Searching the archives…';
      }
      step(mode);
    }

    function step(mode) {
      var btn = mode === 'all' ? allBtn : startBtn;
      var endpoint = (btn && btn.getAttribute('data-url')) || '/index.php?r=/goodreads/enrich';
      var csrf = (btn && btn.getAttribute('data-csrf')) || '';

      var body = new FormData();
      body.append('_csrf', csrf);
      body.append('mode', mode === 'all' ? 'all' : 'missing');
      skipIds.forEach(function (id) {
        body.append('skip_ids[]', String(id));
      });

      fetch(endpoint, {
        method: 'POST',
        body: body,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then(function (res) {
          return res.json().then(function (data) {
            return { res: res, data: data };
          });
        })
        .then(function (payload) {
          var data = payload.data || {};
          if (data.pending != null) {
            setProgress(data.pending, data.total || initialPending);
            if (mode === 'all' && !initialPending && data.total) {
              initialPending = data.total;
            }
          }

          if (data.done) {
            if (statusEl) {
              statusEl.textContent = data.message || 'Archive sync complete.';
            }
            finish();
            return;
          }

          if (!payload.res.ok || data.ok === false) {
            if (data.book_id) {
              skipIds.push(data.book_id);
            }
            if (statusEl) {
              statusEl.textContent = 'Skip: ' + (data.title || data.book_id || '?') + ' — ' + (data.error || 'error');
            }
            setTimeout(function () {
              step(mode);
            }, 600);
            return;
          }

          processed += 1;
          if (data.book_id) {
            skipIds.push(data.book_id);
          }
          if (statusEl) {
            statusEl.textContent = (data.message || 'Updated') + (data.pages ? ' · ' + data.pages + ' pages' : '');
          }
          // For full re-sync, finish when we've touched every book.
          if (mode === 'all' && data.total && skipIds.length >= data.total) {
            if (statusEl) {
              statusEl.textContent = 'Archive sync complete. ' + processed + ' volumes updated.';
            }
            setProgress(0, data.total);
            finish();
            return;
          }
          setTimeout(function () {
            step(mode);
          }, 450);
        })
        .catch(function (err) {
          if (statusEl) {
            statusEl.textContent = 'The archive could not be opened. ' + (err.message || '');
          }
          finish();
        });
    }

    function finish() {
      running = false;
      [startBtn, allBtn].forEach(function (b) {
        if (b) {
          b.disabled = false;
        }
      });
    }

    if (startBtn) {
      startBtn.addEventListener('click', function () {
        run('missing');
      });
    }
    if (allBtn) {
      allBtn.addEventListener('click', function () {
        if (!window.confirm('¿Re-sincronizar todos los libros con Goodreads? Puede tardar varios minutos.')) {
          return;
        }
        run('all');
      });
    }
  }

  /* -----------------------------------------------------------------------
     Boot
     ----------------------------------------------------------------------- */

  function init() {
    initSidebarToggle();
    initLibraryViewMode();
    initFilterPanel();
    initConfirmDelete();
    initGlobalSearch();
    initGoodreadsSeek();
    initGoodreadsEnrich();
    initCharts();
    initFlashDismiss();
    initModals();
    initProgressBars();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
