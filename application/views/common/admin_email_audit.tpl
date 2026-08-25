<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Email Ingest Log</h5>
        <div>
            <a class="btn btn-sm {if $audit_outcome_filter eq ''}btn-primary{else}btn-outline-secondary{/if}" href="admin_email_audit">{$g_lang_all|default:'All'}</a>
            <a class="btn btn-sm {if $audit_outcome_filter eq 'created'}btn-primary{else}btn-outline-secondary{/if}" href="admin_email_audit?outcome=created">Created</a>
            <a class="btn btn-sm {if $audit_outcome_filter eq 'rejected'}btn-primary{else}btn-outline-secondary{/if}" href="admin_email_audit?outcome=rejected">Rejected</a>
            <a class="btn btn-sm {if $audit_outcome_filter eq 'error'}btn-primary{else}btn-outline-secondary{/if}" href="admin_email_audit?outcome=error">Errors</a>
        </div>
    </div>
    <div class="card-body">
        {if $audit_rows|@count eq 0}
            <p class="text-muted mb-0">No email ingest activity yet.</p>
        {else}
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Outcome</th>
                        <th>Document</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$audit_rows item=row}
                    <tr>
                        <td class="text-nowrap">{$row.created|escape:'html'}</td>
                        <td>{$row.from_address|escape:'html'}</td>
                        <td class="text-muted">{$row.message_id|escape:'html'}</td>
                        <td>
                            {if $row.outcome eq 'created'}<span class="badge bg-success">Created</span>
                            {elseif $row.outcome eq 'rejected'}<span class="badge bg-warning text-dark">Rejected</span>
                            {else}<span class="badge bg-danger">Error</span>{/if}
                        </td>
                        <td>
                            {if $row.document_id}
                                <a href="details?id={$row.document_id|escape:'html'}">#{$row.document_id|escape:'html'}</a>
                            {else}
                                <span class="text-muted">-</span>
                            {/if}
                        </td>
                        <td>{$row.reason|escape:'html'}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        {/if}
    </div>
</div>