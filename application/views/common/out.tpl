<div class="mt-3">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            {if $state == 2}
                <h3 class="card-title mb-0">{$g_lang_area_deleted_files}</h3>
                <div>
                    <button id="undelete-selected" class="btn btn-success btn-sm" disabled>Undelete</button>
                    <button id="delete-permanent-selected" class="btn btn-danger btn-sm" disabled>Delete Permanently</button>
                    <div id="delete-csrf-fields" class="d-none">{$delete_csrf_field|default:''}</div>
                </div>
            {elseif $state == 3}
                <h3 class="card-title mb-0">{$g_lang_label_checked_out_files}</h3>
                <div>
                    <button id="clear-status-selected" class="btn btn-success btn-sm" disabled>{$g_lang_button_clear_status}</button>
                    <div id="clear-csrf-fields" class="d-none">{$clear_csrf_field|default:''}</div>
                </div>
            {else}
                <h3 class="card-title mb-0">{$g_lang_label_file_listing}</h3>
                <div>
                    <button id="delete-selected" class="btn btn-danger btn-sm" disabled>Delete Selected</button>
                    <div id="delete-csrf-fields" class="d-none">{$delete_csrf_field|default:''}</div>
                </div>
            {/if}
        </div>
        <div class="card-body">
            <div id="file-table" data-state="{$state|default:1}"></div>
        </div>
    </div>
</div>
