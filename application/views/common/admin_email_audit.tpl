<script>
window.crudEntity = 'email_audit';
window.crudOutcome = '{$audit_outcome_filter|escape:'javascript'}';
window.csrfIndexName = '{$csrf_index_name}';
window.csrfIndex = '{$csrf_index_value}';
</script>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{$g_lang_adminpage_email_ingest_log|default:'Email Ingest Log'}</h5>
        <div>
            <a class="btn btn-sm {if $audit_outcome_filter eq ''}btn-primary{else}btn-outline-secondary{/if}" href="admin_email_audit">{$g_lang_all|default:'All'}</a>
            <a class="btn btn-sm {if $audit_outcome_filter eq 'created'}btn-primary{else}btn-outline-secondary{/if}" href="admin_email_audit?outcome=created">Created</a>
            <a class="btn btn-sm {if $audit_outcome_filter eq 'rejected'}btn-primary{else}btn-outline-secondary{/if}" href="admin_email_audit?outcome=rejected">Rejected</a>
            <a class="btn btn-sm {if $audit_outcome_filter eq 'error'}btn-primary{else}btn-outline-secondary{/if}" href="admin_email_audit?outcome=error">Errors</a>
        </div>
    </div>
    <div class="card-body">
        <div id="crud-table"></div>
    </div>
</div>