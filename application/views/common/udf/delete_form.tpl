<form action="udf" method="POST" enctype="multipart/form-data">
    {$csrf_token_field}
    <div class="card">
        <div class="card-body">
            <input type="hidden" name="type" value="{$udf.field_type|escape:'html'}">
            <input type="hidden" name="id" value="{$udf.table_name|escape:'html'}">
            <dl class="row mb-3">
                <dt class="col-sm-3">{$g_lang_label_name}:</dt>
                <dd class="col-sm-9">{$udf.table_name|escape:'html'}</dd>
                <dt class="col-sm-3">{$g_lang_label_display}:</dt>
                <dd class="col-sm-9">{$udf.display_name|escape:'html'}</dd>
            </dl>
            <p class="mb-3">{$g_lang_message_are_you_sure_remove}</p>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="Submit" name="deleteudf" value="Yes">{$g_lang_button_yes}</button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_button_cancel}</button>
            </div>
        </div>
    </div>
</form>
