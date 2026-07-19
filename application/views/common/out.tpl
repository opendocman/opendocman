<div class="mt-3">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">{$g_lang_outpage_file_listing}</h3>
        </div>
        <div class="card-body">
            <div id="file-table"></div>
            {if $limit_reached}
                <div class="alert alert-warning mt-2">{$g_lang_message_max_number_of_results}</div>
            {/if}
        </div>
    </div>
</div>
<script id="file-table-data" type="application/json">{$file_list_json}</script>
