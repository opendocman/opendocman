document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('adminSidebarSearch');
  if (!input) return;
  input.addEventListener('input', function () {
    var q = input.value.trim().toLowerCase();
    var links = document.querySelectorAll('#adminSidebarNav .nav-link');
    links.forEach(function (a) {
      a.style.display = (!q || a.textContent.trim().toLowerCase().indexOf(q) !== -1) ? '' : 'none';
    });
    var groupLabels = document.querySelectorAll('.admin-sidebar-group-label');
    groupLabels.forEach(function (h) {
      var sibling = h.nextElementSibling;
      h.style.display = 'block';
      if (!q) return;
      var visible = false;
      var node = sibling;
      while (node && node.tagName === 'LI') {
        var link = node.querySelector('.nav-link');
        if (link && link.style.display !== 'none') { visible = true; break; }
        node = node.nextElementSibling;
      }
      if (!visible) h.style.display = 'none';
    });
  });
});