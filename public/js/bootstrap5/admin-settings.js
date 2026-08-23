document.addEventListener('DOMContentLoaded', function () {
  var filter = document.getElementById('settingsFilter');
  var panels = Array.prototype.slice.call(document.querySelectorAll('#settingsAccordion .accordion-collapse'));
  var defaultState = {}; // group slug -> initially-open flag (from the template markup)
  var previousState = {}; // group slug -> open/closed as the user left it

  panels.forEach(function (panel) {
    var group = panel.id.replace('group-', '');
    defaultState[group] = panel.classList.contains('show');
    previousState[group] = defaultState[group];
  });

  function collapseInstance(panel) {
    return bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false });
  }

  function setPanel(panel, open) {
    var inst = collapseInstance(panel);
    if (open && !panel.classList.contains('show')) {
      inst.show();
    } else if (!open && panel.classList.contains('show')) {
      inst.hide();
    }
  }

  // Sidebar Settings-Groups deep-links: expand the matching accordion panel.
  document.querySelectorAll('#adminSidebarNav .setting-group-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var group = link.getAttribute('data-group');
      var panel = document.getElementById('group-' + group);
      if (panel) {
        setPanel(panel, true);
        previousState[group] = true;
      }
    });
  });

  if (!filter) return;

  filter.addEventListener('input', function () {
    var q = filter.value.trim().toLowerCase();
    var anyVisible = false;

    panels.forEach(function (panel) {
      var group = panel.id.replace('group-', '');
      var rows = panel.querySelectorAll('.setting-row');
      var panelHasMatch = false;

      rows.forEach(function (r) {
        var hay = (r.dataset.settingsName + ' ' + r.dataset.settingsDesc).toLowerCase();
        var hide = q !== '' && hay.indexOf(q) === -1;
        r.style.display = hide ? 'none' : '';
        if (!hide) {
          panelHasMatch = true;
          anyVisible = true;
        }
      });

      // While searching: auto-expand groups with matches, collapse groups without.
      // When the query is cleared: restore the user's previous open/closed state.
      if (q !== '') {
        setPanel(panel, panelHasMatch);
      } else {
        setPanel(panel, previousState[group]);
      }
    });

    // Empty-state text when nothing matches
    var empty = document.getElementById('settingsEmpty');
    if (q !== '' && !anyVisible) {
      if (!empty) {
        empty = document.createElement('div');
        empty.id = 'settingsEmpty';
        empty.className = 'text-muted text-center py-4';
        empty.textContent = filter.dataset.emptyMsg || 'No settings match';
        var accordion = document.getElementById('settingsAccordion');
        accordion.parentNode.insertBefore(empty, accordion);
      }
      empty.style.display = '';
    } else if (empty) {
      empty.style.display = 'none';
    }
  });

  // Record manual open/closed state so clearing the search can restore it.
  panels.forEach(function (panel) {
    panel.addEventListener('shown.bs.collapse', function () {
      previousState[panel.id.replace('group-', '')] = true;
    });
    panel.addEventListener('hidden.bs.collapse', function () {
      previousState[panel.id.replace('group-', '')] = false;
    });
  });
});