<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{$g_lang_label_admin|escape:'html'}</h4>
</div>

<div class="row g-3">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-6 fw-bold">{$stats.users|intval}</div>
                <div class="text-muted small">{$g_lang_users|escape:'html'}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-6 fw-bold">{$stats.departments|intval}</div>
                <div class="text-muted small">{$g_lang_label_department|escape:'html'}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-6 fw-bold">{$stats.categories|intval}</div>
                <div class="text-muted small">{$g_lang_category|escape:'html'}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-6 fw-bold">{$stats.files|intval}</div>
                <div class="text-muted small">{$g_lang_label_files|default:'Documents'}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3 border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">{$g_lang_admin_quick_actions|default:'Quick actions'}</h6>
    </div>
    <div class="card-body d-flex gap-2 flex-wrap">
        <a class="btn btn-primary btn-sm" href="admin_users">+ {$g_lang_users}</a>
        <a class="btn btn-primary btn-sm" href="admin_departments">+ {$g_lang_label_department}</a>
        <a class="btn btn-primary btn-sm" href="admin_categories">+ {$g_lang_category}</a>
        <a class="btn btn-outline-primary btn-sm" href="settings?submit=update">{$g_lang_label_settings}</a>
    </div>
</div>

<div class="mt-3 text-muted small">
    {$g_lang_about_app_version|default:'App version'}: {$app_version} &middot;
    {$g_lang_about_db_version|default:'DB version'}: {$db_version}
</div>