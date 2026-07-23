document.addEventListener('DOMContentLoaded', function () {
    var showBtn = document.getElementById('showAddCategory');
    var formDiv = document.getElementById('addCategoryForm');
    var cancelBtn = document.getElementById('cancelCategory');
    var saveBtn = document.getElementById('saveCategory');
    var nameInput = document.getElementById('newCategoryName');
    var statusEl = document.getElementById('categoryStatus');
    var catSelect = document.querySelector('select[name="category"]');

    if (!showBtn) return;

    function toggleForm(show) {
        formDiv.classList.toggle('d-none', !show);
        statusEl.textContent = '';
        if (show) nameInput.focus();
    }

    showBtn.addEventListener('click', function () { toggleForm(true); });
    cancelBtn.addEventListener('click', function () { toggleForm(false); });

    saveBtn.addEventListener('click', function () {
        var name = nameInput.value.trim();
        if (!name) { statusEl.textContent = 'Name required'; nameInput.focus(); return; }
        nameInput.value = name;

        var opts = catSelect.options;
        for (var i = 0; i < opts.length; i++) {
            if (opts[i].textContent.toLowerCase() === name.toLowerCase()) {
                statusEl.textContent = 'Category already exists';
                nameInput.focus();
                return;
            }
        }

        var fd = new FormData();
        fd.append('category', name);
        fd.append(CSRF_FIELD_NAME, CSRF_FIELD_VALUE);
        fd.append(CSRF_INDEX_NAME, CSRF_INDEX_VALUE);
        fd.append('submit', 'add_json');

        statusEl.textContent = 'Saving...';
        saveBtn.disabled = true;

        fetch('category', { method: 'POST', body: fd })
            .then(function (r) {
                if (!r.ok) return r.json().then(function (e) { throw new Error(e.error || 'Save failed'); });
                return r.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error('Save failed');
                var newId = data.id;
                return fetch('category?submit=list_json').then(function (r) { return r.json(); })
                    .then(function (cats) { return { cats: cats, newId: newId }; });
            })
            .then(function (result) {
                catSelect.innerHTML = '';
                result.cats.forEach(function (c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    catSelect.appendChild(opt);
                });
                if (result.cats.some(function (c) { return c.id === result.newId; })) {
                    catSelect.value = result.newId;
                }
                nameInput.value = '';
                toggleForm(false);
                statusEl.textContent = '';
            })
            .catch(function (err) {
                statusEl.textContent = 'Error: ' + err.message;
            })
            .finally(function () {
                saveBtn.disabled = false;
            });
    });
});
