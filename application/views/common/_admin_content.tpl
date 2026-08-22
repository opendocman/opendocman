<div class="row g-3 admin-content-shell">
    <div class="col-lg-3 col-xl-2 admin-sidebar-col">
        {include file="../../views/common/_admin_sidebar.tpl"}
    </div>
    <div class="col-lg-9 col-xl-10">
        <div class="card h-100">
            <div class="card-body">
                {$content}
            </div>
        </div>
    </div>
</div>
<script src="{$g_base_url}js/bootstrap5/admin-sidebar.js"></script>