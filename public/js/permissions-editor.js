/**
 * Permissions Editor — dual mode component
 *
 * Supports two permission layers:
 *   - state (explicit): document-level perms, editable
 *   - inherited (from category): read-only, shown as dimmed
 *
 * Usage:
 *   initPermissionsEditor('#perms', { departments: [...], users: [...] });
 *   editor.loadTemplate({ dept_perms: {...}, user_perms: {...} });  // explicit
 *   editor.loadCategoryTemplate({ dept_perms: {...}, user_perms: {...} }, 'CategoryName');  // inherited
 *   var data = editor.getData();
 */
(function () {
    'use strict';
    var RIGHT_LABELS = { '-1': 'Forbidden', '1': 'View', '2': 'Read', '3': 'Write', '4': 'Admin' };
    var RIGHT_ORDER = ['view', 'read', 'write', 'admin', 'forbidden'];
    var RIGHT_VALUES = { forbidden: -1, view: 1, read: 2, write: 3, admin: 4 };

    function forEachObj(obj, fn) {
        Object.keys(obj).forEach(function (k) {
            fn(k, obj[k]);
        });
    }

    function PermissionsEditor(el, options) {
        this.el = el;
        this.departments = options.departments || [];
        this.users = options.users || [];
        this.state = { dept_perms: {}, user_perms: {} };
        this.inherited = { dept_perms: {}, user_perms: {}, catName: '' };
        this.render();
    }

    PermissionsEditor.prototype.render = function () {
        var self = this;
        var html = '';

        html += '<div class="mb-2">';
        html += '  <div class="btn-group btn-group-sm" role="group">';
        html += '    <button type="button" class="btn btn-primary perm-mode-btn" data-mode="edit">Edit</button>';
        html += '    <button type="button" class="btn btn-outline-secondary perm-mode-btn" data-mode="overview">Overview</button>';
        html += '  </div>';
        html += '</div>';

        html += '<div class="perm-edit-mode">';
        html += '  <ul class="nav nav-tabs small" role="tablist">';
        RIGHT_ORDER.forEach(function (level, i) {
            var label = RIGHT_LABELS[RIGHT_VALUES[level]];
            html += '    <li class="nav-item" role="presentation">';
            html += '      <button class="nav-link' + (i === 0 ? ' active' : '') + '" data-bs-toggle="tab" data-bs-target="#perm-tab-' + level + '" type="button" role="tab">' + label + '</button>';
            html += '    </li>';
        });
        html += '  </ul>';
        html += '  <div class="tab-content border border-top-0 p-2" style="max-height:300px; overflow-y:auto;">';
        RIGHT_ORDER.forEach(function (level, ti) {
            var label = RIGHT_LABELS[RIGHT_VALUES[level]];
            html += '    <div class="tab-pane' + (ti === 0 ? ' show active' : '') + '" id="perm-tab-' + level + '" role="tabpanel">';
            html += '      <div class="perm-assigned-list" data-level="' + level + '"></div>';
            html += '      <div class="mt-1"><select class="form-select form-select-sm perm-add-select" data-level="' + level + '" style="width:auto;display:inline-block;"><option value="">+ Add...</option></select></div>';
            html += '    </div>';
        });
        html += '  </div>';
        html += '</div>';

        html += '<div class="perm-overview-mode" style="display:none;">';
        html += '  <div class="table-responsive" style="max-height:300px; overflow-y:auto;">';
        html += '    <table class="table table-sm table-striped mb-0">';
        html += '      <thead class="table-light"><tr><th>Name</th><th>Type</th>';
        RIGHT_ORDER.forEach(function (level) {
            html += '<th class="text-center">' + RIGHT_LABELS[RIGHT_VALUES[level]] + '</th>';
        });
        html += '      </tr></thead><tbody class="perm-overview-body"></tbody></table>';
        html += '  </div>';
        html += '</div>';

        self.el.innerHTML = html;
        self.bindEvents();
        self.refreshAll();
    };

    PermissionsEditor.prototype.bindEvents = function () {
        var self = this;

        self.el.addEventListener('click', function (e) {
            var btn = e.target.closest('.perm-mode-btn');
            if (btn) {
                var mode = btn.getAttribute('data-mode');
                var allBtns = self.el.querySelectorAll('.perm-mode-btn');
                allBtns.forEach(function (b) {
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-outline-secondary');
                });
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-primary');
                var editMode = self.el.querySelector('.perm-edit-mode');
                var overviewMode = self.el.querySelector('.perm-overview-mode');
                if (mode === 'edit') {
                    editMode.style.display = 'block';
                    overviewMode.style.display = 'none';
                } else {
                    editMode.style.display = 'none';
                    overviewMode.style.display = 'block';
                    self.renderOverview();
                }
            }
        });

        self.el.addEventListener('change', function (e) {
            var select = e.target.closest('.perm-add-select');
            if (!select) return;
            var val = select.value;
            if (!val) return;
            var level = select.getAttribute('data-level');
            var parts = val.split(':');
            var type = parts[0], id = parseInt(parts[1]);
            var rightVal = RIGHT_VALUES[level];
            if (type === 'dept') {
                self.state.dept_perms[id] = rightVal;
            } else {
                self.state.user_perms[id] = rightVal;
            }
            self.refreshAll();
            select.value = '';
        });

        self.el.addEventListener('click', function (e) {
            var link = e.target.closest('.perm-remove-btn');
            if (!link) return;
            e.preventDefault();
            var type = link.getAttribute('data-type');
            var id = parseInt(link.getAttribute('data-id'));
            if (type === 'dept') {
                delete self.state.dept_perms[id];
            } else {
                delete self.state.user_perms[id];
            }
            self.refreshAll();
        });

        self.el.addEventListener('click', function (e) {
            var td = e.target.closest('.perm-overview-body td');
            if (!td) return;
            var level = td.getAttribute('data-level');
            if (!level) return;
            var editBtn = self.el.querySelector('.perm-mode-btn[data-mode="edit"]');
            if (editBtn) editBtn.click();
            var tabBtn = self.el.querySelector('[data-bs-target="#perm-tab-' + level + '"]');
            if (tabBtn) tabBtn.click();
        });
    };

    PermissionsEditor.prototype.refreshTab = function (level) {
        var self = this;
        var rightVal = RIGHT_VALUES[level];
        var assigned = [];

        // Explicit perms first
        forEachObj(self.state.dept_perms, function (id, rights) {
            if (rights === rightVal) {
                var dept = self.findDept(parseInt(id));
                if (dept) assigned.push({ type: 'dept', id: id, name: dept.name, inherited: false });
            }
        });
        forEachObj(self.state.user_perms, function (id, rights) {
            if (rights === rightVal) {
                var user = self.findUser(parseInt(id));
                if (user) assigned.push({ type: 'user', id: id, name: user.last_name + ', ' + user.first_name, inherited: false });
            }
        });

        // Inherited perms (only if not overridden by explicit)
        forEachObj(self.inherited.dept_perms, function (id, rights) {
            if (rights === rightVal && !(id in self.state.dept_perms)) {
                var dept = self.findDept(parseInt(id));
                if (dept) assigned.push({ type: 'dept', id: id, name: dept.name, inherited: true });
            }
        });
        forEachObj(self.inherited.user_perms, function (id, rights) {
            if (rights === rightVal && !(id in self.state.user_perms)) {
                var user = self.findUser(parseInt(id));
                if (user) assigned.push({ type: 'user', id: id, name: user.last_name + ', ' + user.first_name, inherited: true });
            }
        });

        var listEl = self.el.querySelector('.perm-assigned-list[data-level="' + level + '"]');
        if (assigned.length === 0) {
            listEl.innerHTML = '<div class="text-muted small p-1">None assigned</div>';
        } else {
            var html = '';
            assigned.forEach(function (item) {
                if (item.inherited) {
                    var catName = self.inherited.catName || 'category';
                    html += '<span class="badge bg-light text-secondary me-1 mb-1" title="Inherited from ' + catName + '">';
                    html += item.name + ' <span class="text-muted small">(inherited)</span>';
                    html += '</span> ';
                } else {
                    var badge = item.type === 'dept' ? 'secondary' : 'info';
                    html += '<span class="badge bg-' + badge + ' me-1 mb-1">';
                    html += item.name;
                    html += ' <a href="#" class="text-white text-decoration-none perm-remove-btn" data-type="' + item.type + '" data-id="' + item.id + '">&times;</a>';
                    html += '</span> ';
                }
            });
            listEl.innerHTML = html;
        }

        var selectEl = self.el.querySelector('.perm-add-select[data-level="' + level + '"]');
        var currentOptions = '<option value="">+ Add...</option>';
        self.departments.forEach(function (dept) {
            currentOptions += '<option value="dept:' + dept.id + '">[Dept] ' + dept.name + '</option>';
        });
        self.users.forEach(function (user) {
            currentOptions += '<option value="user:' + user.id + '">[User] ' + user.last_name + ', ' + user.first_name + '</option>';
        });
        selectEl.innerHTML = currentOptions;
    };

    PermissionsEditor.prototype.refreshAll = function () {
        var self = this;
        RIGHT_ORDER.forEach(function (level) {
            self.refreshTab(level);
        });
    };

    PermissionsEditor.prototype.renderOverview = function () {
        var self = this;
        var html = '';
        var allItems = [];

        self.departments.forEach(function (d) {
            allItems.push({ id: d.id, name: d.name, type: 'dept' });
        });
        self.users.forEach(function (u) {
            allItems.push({ id: u.id, name: u.last_name + ', ' + u.first_name, type: 'user' });
        });

        allItems.forEach(function (item) {
            html += '<tr>';
            html += '<td>' + item.name + '</td>';
            html += '<td><span class="badge bg-' + (item.type === 'dept' ? 'secondary' : 'info') + '">' + item.type + '</span></td>';
            RIGHT_ORDER.forEach(function (level) {
                var rightVal = RIGHT_VALUES[level];
                var perms = item.type === 'dept' ? self.state.dept_perms : self.state.user_perms;
                var inheritedPerms = item.type === 'dept' ? self.inherited.dept_perms : self.inherited.user_perms;
                var set = perms[item.id] === rightVal;
                var inherited = !set && inheritedPerms[item.id] === rightVal;
                var catName = self.inherited.catName;
                var title = inherited ? 'Inherited from ' + catName : '';
                html += '<td class="text-center" data-level="' + level + '" style="cursor:pointer;"' + (title ? ' title="' + title + '"' : '') + '>';
                if (set) {
                    html += '<span class="text-success fw-bold">&#10003;</span>';
                } else if (inherited) {
                    html += '<span class="text-success" style="opacity:0.5;">&#10003;</span>';
                } else {
                    html += '<span class="text-muted">&#9679;</span>';
                }
                html += '</td>';
            });
            html += '</tr>';
        });
        var body = self.el.querySelector('.perm-overview-body');
        body.innerHTML = html || '<tr><td colspan="7" class="text-muted">No permissions set</td></tr>';
    };

    PermissionsEditor.prototype.findDept = function (id) {
        var found = null;
        this.departments.some(function (d) { if (d.id === id) { found = d; return true; } return false; });
        return found;
    };

    PermissionsEditor.prototype.findUser = function (id) {
        var found = null;
        this.users.some(function (u) { if (u.id === id) { found = u; return true; } return false; });
        return found;
    };

    PermissionsEditor.prototype.loadTemplate = function (data) {
        var deptPerms = {};
        forEachObj(data.dept_perms || {}, function (k, v) { deptPerms[parseInt(k)] = v; });
        this.state.dept_perms = deptPerms;
        var userPerms = {};
        forEachObj(data.user_perms || {}, function (k, v) { userPerms[parseInt(k)] = v; });
        this.state.user_perms = userPerms;
        this.refreshAll();
    };

    PermissionsEditor.prototype.loadCategoryTemplate = function (data, catName) {
        var deptPerms = {};
        forEachObj(data.dept_perms || {}, function (k, v) { deptPerms[parseInt(k)] = v; });
        this.inherited.dept_perms = deptPerms;
        var userPerms = {};
        forEachObj(data.user_perms || {}, function (k, v) { userPerms[parseInt(k)] = v; });
        this.inherited.user_perms = userPerms;
        this.inherited.catName = catName || '';
        this.refreshAll();
    };

    PermissionsEditor.prototype.getData = function () {
        var deptPerm = {};
        forEachObj(this.state.dept_perms, function (id, rights) { deptPerm[id] = rights; });
        var userPerm = {};
        forEachObj(this.state.user_perms, function (id, rights) { userPerm[id] = rights; });
        return { department_permission: deptPerm, user_permission: userPerm };
    };

    PermissionsEditor.getInstance = function (el) {
        if (typeof el === 'string') {
            el = document.querySelector(el);
        }
        return el ? el._permsEditor : null;
    };

    window.PermissionsEditor = PermissionsEditor;

    function initEditor(selector, options) {
        var el = (typeof selector === 'string') ? document.querySelector(selector) : selector;
        if (el && !el._permsEditor) {
            el._permsEditor = new PermissionsEditor(el, options);
        }
        return el ? el._permsEditor : null;
    }

    window.initPermissionsEditor = initEditor;
})();