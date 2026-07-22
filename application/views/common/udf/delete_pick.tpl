<form action="udf" method="POST" enctype="multipart/form-data">
    {$csrf_token_field}
    <input type="hidden" name="state" value="{$state}">
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label"><?php echo msg('label_user_defined_field')?></label>
                <select name="item" class="form-select">
                    {foreach from=$udfs item=item}
                        <option value="{$item.table_name|escape:'html'}">{$item.display_name|escape:'html'}</option>
                    {/foreach}
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="Submit" name="submit" value="delete">{$g_lang_button_delete}</button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_button_cancel}</button>
            </div>
        </div>
    </div>
</form>
