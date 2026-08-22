document.addEventListener('DOMContentLoaded', function () {
  var filter = document.getElementById('settingsFilter');
  if (!filter) return;

  filter.addEventListener('input', function () {
    var q = filter.value.trim().toLowerCase();
    var rows = document.querySelectorAll('.setting-row');

    var anyVisible = false;
    rows.forEach(function (r) {
      var hay = (r.dataset.settingsName + ' ' + r.dataset.settingsDesc).toLowerCase();
      var hide = q !== '' && hay.indexOf(q) === -1;
      r.style.display = hide ? 'none' : '';
      if (!hide) anyVisible = true;
    });

    // Empty-state text when nothing matches
    var empty = document.getElementById('settingsEmpty');
    if (q !== '' && !anyVisible) {
      if (!empty) {
        empty = document.createElement('div');
        empty.id = 'settingsEmpty';
        empty.className = 'text-muted text-center py-4';
        empty.textContent = filter.dataset.emptyMsg || 'No settings match';
        document.getElementById('settingsTabContent').appendChild(empty);
      }
      empty.style.display = '';
    } else if (empty) {
      empty.style.display = 'none';
    }
  });
});