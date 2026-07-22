<form id="udfAddForm" action="udf?last_message={$last_message|escape:'html'}" method="POST" enctype="multipart/form-data" novalidate>
    {$csrf_token_field}
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">{$g_lang_label_name}(limit 5)</label>
                <input maxlength="5" name="table_name" type="text" class="required form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">{$g_lang_label_display} {$g_lang_label_name}</label>
                <input maxlength="16" name="display_name" type="text" class="required form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">{$g_lang_type}</label>
                <select name="field_type" class="form-select">
                    <option value=1>{$g_lang_select} {$g_lang_list}</option>
                    <option value=4>{$g_lang_label_sub_select_list}</option>
                    <option value=2>{$g_lang_label_radio_button}</option>
                    <option value=3>{$g_lang_label_text}</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="Submit" name="submit" value="Add User Defined Field">{$g_lang_button_save}</button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_button_cancel}</button>
            </div>
        </div>
    </div>
</form>
<script>
    {literal}
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('udfAddForm');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        }
    });
    {/literal}
</script>
