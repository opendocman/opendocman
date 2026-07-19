<form action="filetypes" method="POST" enctype="multipart/form-data" class="mt-3">
    {$csrf_token_field}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">{$g_lang_label_allowed}&nbsp;{$g_lang_label_filetypes}</h3>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <select class="form-select" multiple="multiple" id="types" name="types[]" style="min-height:200px;">
                    {foreach from=$filetypes_array item=i}
                        <option value="{$i.id|escape}" {if $i.active eq '1'}selected="selected"{/if}>{$i.type|escape:'html'}</option>
                    {/foreach}
                </select>
            </div>
            <div class="d-flex gap-2 mb-3">
                <a href="filetypes?submit=AddNew" class="btn btn-outline-primary btn-sm">{$g_lang_label_add}&nbsp;{$g_lang_label_filetype}</a>
                <a href="filetypes?submit=DeleteSelect" class="btn btn-outline-danger btn-sm">{$g_lang_label_delete}&nbsp;{$g_lang_label_filetype}</a>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit" name="submit" value="Save">{$g_lang_button_save}</button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_button_cancel}</button>
            </div>
        </div>
    </div>
</form>