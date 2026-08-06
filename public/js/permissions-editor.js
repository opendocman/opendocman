/**
 * Permissions Editor — dual mode component
 *
 * Edit mode: tab-per-level additive selection
 * Overview mode: full matrix with checkmark/X indicators
 *
 * Usage:
 *   $('#perms').permissionsEditor({ departments: [...], users: [...] });
 *   $('#perms').permissionsEditor('loadTemplate', { dept_perms: {...}, user_perms: {...} });
 *   var data = $('#perms').permissionsEditor('getData');
 */
(function ($) {
    var RIGHT_LABELS = { '-1': 'Forbidden', '1': 'View', '2': 'Read', '3': 'Write', '4': 'Admin' };
    var RIGHT_ORDER = ['view', 'read', 'write', 'admin', 'forbidden'];
    var RIGHT_VALUES = { forbidden: -1, view: 1, read: 2, write: 3, admin: 4 };

    function PermissionsEditor(el, options) {
        this.$el = $(el);
        this.departments = options.departments || [];
        this.users = options.users || [];
        this.state = { dept_perms: {}, user_perms: {} };
        this.render();
    }

    PermissionsEditor.prototype.render = function () {
        var self = this;
        var html = '';

        // Mode toggle
        html += '<div class="mb-2">';
        html += '  <div class="btn-group btn-group-sm" role="group">';
        html += '    <button type="button" class="btn btn-primary perm-mode-btn" data-mode="edit">Edit</button>';
        html += '    <button type="button" class="btn btn-outline-secondary perm-mode-btn" data-mode="overview">Overview</button>';
        html += '  </div>';
        html += '</div>';

        // Edit mode container
        html += '<div class="perm-edit-mode">';
        html += '  <ul class="nav nav-tabs small" role="tablist">';
        $.each(RIGHT_ORDER, function (i, level) {
            var label = RIGHT_LABELS[RIGHT_VALUES[level]];
            html += '    <li class="nav-item" role="presentation">';
            html += '      <button class="nav-link' + (i === 0 ? ' active' : '') + '" data-bs-toggle="tab" data-target="#perm-tab-' + level + '" type="button" role="tab">' + label + '</button>';
            html += '    </li>';
        });
        html += '  </ul>';
        html += '  <div class="tab-content border border-top-0 p-2" style="max-height:300px; overflow-y:auto;">';
        $.each(RIGHT_ORDER, function (ti, level) {
            var label = RIGHT_LABELS[RIGHT_VALUES[level]];
            html += '    <div class="tab-pane' + (ti === 0 ? ' show active' : '') + '" id="perm-tab-' + level + '" role="tabpanel">';
            html += '      <div class="perm-assigned-list" data-level="' + level + '"></div>';
            html += '      <div class="mt-1"><select class="form-select form-select-sm perm-add-select" data-level="' + level + '" style="width:auto;display:inline-block;"><option value="">+ Add...</option></select></div>';
            html += '    </div>';
        });
        html += '  </div>';
        html += '</div>';

        // Overview mode container
        html += '<div class="perm-overview-mode" style="display:none;">';
        html += '  <div class="table-responsive" style="max-height:300px; overflow-y:auto;">';
        html += '    <table class="table table-sm table-striped mb-0">';
        html += '      <thead class="table-light"><tr><th>Name</th><th>Type</th>';
        $.each(RIGHT_ORDER, function (_, level) {
            html += '<th class="text-center">' + RIGHT_LABELS[RIGHT_VALUES[level]] + '</th>';
        });
        html += '      </tr></thead><tbody class="perm-overview-body"></tbody></table>';
        html += '  </div>';
        html += '</div>';

        self.$el.html(html);
        self.bindEvents();
        self.refreshAll();
    };

    PermissionsEditor.prototype.bindEvents = function () {
        var self = this;
        self.$el.off('.perms');

        // Mode toggle
        self.$el.on('click.perms', '.perm-mode-btn', function () {
            var mode = $(this).data('mode');
            self.$el.find('.perm-mode-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
            $(this).addClass('btn-primary').removeClass('btn-outline-secondary');
            if (mode === 'edit') {
                self.$el.find('.perm-edit-mode').show();
                self.$el.find('.perm-overview-mode').hide();
            } else {
                self.$el.find('.perm-edit-mode').hide();
                self.$el.find('.perm-overview-mode').show();
                self.renderOverview();
            }
        });

        // Add from dropdown
        self.$el.on('change.perms', '.perm-add-select', function () {
            var val = $(this).val();
            if (!val) return;
            var level = $(this).data('level');
            var parts = val.split(':');
            var type = parts[0], id = parseInt(parts[1]);
            var rightVal = RIGHT_VALUES[level];
            if (type === 'dept') {
                self.state.dept_perms[id] = rightVal;
            } else {
                self.state.user_perms[id] = rightVal;
            }
            self.refreshTab(level);
            $(this).val('');
        });

        // Remove button
        self.$el.on('click.perms', '.perm-remove-btn', function () {
            var type = $(this).data('type');
            var id = parseInt($(this).data('id'));
            var level = $(this).data('level');
            if (type === 'dept') {
                delete self.state.dept_perms[id];
            } else {
                delete self.state.user_perms[id];
            }
            self.refreshTab(level);
        });

        // Overview click navigates to edit tab
        self.$el.on('click.perms', '.perm-overview-body td', function () {
            var level = $(this).data('level');
            if (!level) return;
            self.$el.find('.perm-mode-btn[data-mode="edit"]').click();
            self.$el.find('[data-target="#perm-tab-' + level + '"]').tab('show');
        });
    };

    PermissionsEditor.prototype.refreshTab = function (level) {
        var self = this;
        var rightVal = RIGHT_VALUES[level];
        var assigned = [];

        // Collect dept assignments
        $.each(self.state.dept_perms, function (id, rights) {
            if (rights === rightVal) {
                var dept = self.findDept(parseInt(id));
                if (dept) assigned.push({ type: 'dept', id: id, name: dept.name });
            }
        });
        // Collect user assignments
        $.each(self.state.user_perms, function (id, rights) {
            if (rights === rightVal) {
                var user = self.findUser(parseInt(id));
                if (user) assigned.push({ type: 'user', id: id, name: user.last_name + ', ' + user.first_name });
            }
        });

        var listEl = self.$el.find('.perm-assigned-list[data-level="' + level + '"]');
        if (assigned.length === 0) {
            listEl.html('<div class="text-muted small p-1">None assigned</div>');
        } else {
            var html = '';
            $.each(assigned, function (_, item) {
                var badge = item.type === 'dept' ? 'secondary' : 'info';
                html += '<span class="badge bg-' + badge + ' me-1 mb-1">';
                html += item.name;
                html += ' <a href="#" class="text-white text-decoration-none perm-remove-btn" data-type="' + item.type + '" data-id="' + item.id + '" data-level="' + level + '">&times;</a>';
                html += '</span> ';
            });
            listEl.html(html);
        }

        // Update add dropdown — exclude already assigned items at THIS level
        var selectEl = self.$el.find('.perm-add-select[data-level="' + level + '"]');
        var currentOptions = '<option value="">+ Add...</option>';
        $.each(self.departments, function (_, dept) {
            if (!self.state.dept_perms[dept.id] || self.state.dept_perms[dept.id] !== rightVal) {
                currentOptions += '<option value="dept:' + dept.id + '">[Dept] ' + dept.name + '</option>';
            }
        });
        $.each(self.users, function (_, user) {
            if (!self.state.user_perms[user.id] || self.state.user_perms[user.id] !== rightVal) {
                currentOptions += '<option value="user:' + user.id + '">[User] ' + user.last_name + ', ' + user.first_name + '</option>';
            }
        });
        selectEl.html(currentOptions);
    };

    PermissionsEditor.prototype.refreshAll = function () {
        var self = this;
        $.each(RIGHT_ORDER, function (_, level) {
            self.refreshTab(level);
        });
    };

    PermissionsEditor.prototype.renderOverview = function () {
        var self = this;
        var html = '';
        var allItems = [];

        $.each(self.departments, function (_, d) {
            allItems.push({ id: d.id, name: d.name, type: 'dept' });
        });
        $.each(self.users, function (_, u) {
            allItems.push({ id: u.id, name: u.last_name + ', ' + u.first_name, type: 'user' });
        });

        $.each(allItems, function (_, item) {
            html += '<tr>';
            html += '<td>' + item.name + '</td>';
            html += '<td><span class="badge bg-' + (item.type === 'dept' ? 'secondary' : 'info') + '">' + item.type + '</span></td>';
            $.each(RIGHT_ORDER, function (_, level) {
                var rightVal = RIGHT_VALUES[level];
                var perms = item.type === 'dept' ? self.state.dept_perms : self.state.user_perms;
                var set = perms[item.id] === rightVal;
                html += '<td class="text-center" data-level="' + level + '" style="cursor:pointer;">';
                html += set ? '<span class="text-success fw-bold">&#10003;</span>' : '<span class="text-muted">&#9679;</span>';
                html += '</td>';
            });
            html += '</tr>';
        });
        self.$el.find('.perm-overview-body').html(html || '<tr><td colspan="7" class="text-muted">No permissions set</td></tr>');
    };

    PermissionsEditor.prototype.findDept = function (id) {
        var found = null;
        $.each(this.departments, function (_, d) { if (d.id === id) { found = d; return false; } });
        return found;
    };

    PermissionsEditor.prototype.findUser = function (id) {
        var found = null;
        $.each(this.users, function (_, u) { if (u.id === id) { found = u; return false; } });
        return found;
    };

    PermissionsEditor.prototype.loadTemplate = function (data) {
        this.state.dept_perms = data.dept_perms || {};
        // Convert string keys to int
        var deptPerms = {};
        $.each(this.state.dept_perms, function (k, v) { deptPerms[parseInt(k)] = v; });
        this.state.dept_perms = deptPerms;
        var userPerms = {};
        $.each(data.user_perms || {}, function (k, v) { userPerms[parseInt(k)] = v; });
        this.state.user_perms = userPerms;
        this.refreshAll();
    };

    PermissionsEditor.prototype.getData = function () {
        var deptPerm = {};
        $.each(this.state.dept_perms, function (id, rights) { deptPerm[id] = rights; });
        var userPerm = {};
        $.each(this.state.user_perms, function (id, rights) { userPerm[id] = rights; });
        return { department_permission: deptPerm, user_permission: userPerm };
    };

    // jQuery plugin
    $.fn.permissionsEditor = function (methodOrOptions) {
        if (typeof methodOrOptions === 'string') {
            var instance = this.data('permsEditor');
            if (instance && instance[methodOrOptions]) {
                return instance[methodOrOptions].apply(instance, Array.prototype.slice.call(arguments, 1));
            }
        } else {
            var options = methodOrOptions || {};
            if (!this.data('permsEditor')) {
                this.data('permsEditor', new PermissionsEditor(this, options));
            }
        }
        return this;
    };
})(jQuery);