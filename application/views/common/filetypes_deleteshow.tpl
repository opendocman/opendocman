<div class="card">
    <div class="card-body">
        <h5 class="card-title">{$g_lang_label_delete}&nbsp;{$g_lang_label_filetypes}&nbsp;-&nbsp;{$g_lang_choose}</h5>
        <form action="filetypes" method="POST" enctype="multipart/form-data">
            {$csrf_token_field}
            <div class="mb-3">
                <select class="form-select multiView" id="types" multiple="multiple" name="types[]">
                {foreach from=$filetypes_array item=i}
                    <option value="{$i.id|escape}">{$i.type|escape:'html'}</option>
                {/foreach}
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit" name="submit" value="Delete">{$g_lang_button_delete}</button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_button_cancel}</button>
            </div>
        </form>
    </div>
</div>
