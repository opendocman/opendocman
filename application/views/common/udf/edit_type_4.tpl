<form id="editUdfForm" method="post" class="container mt-3">
    <div class="card">
        <div class="card-body">
            {$csrf_token_field}
            <input type="hidden" name="submit" value="edit">
            <input type="hidden" name="udf" value="{$udf|escape:'html'}">
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label">{$g_lang_label_name}:</label>
                <div class="col-sm-9"><p class="form-control-plaintext">{$udf|escape:'html'}</p></div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label">{$g_lang_label_display} {$g_lang_label_name}:</label>
                <div class="col-sm-4">
                    <input maxlength="16" name="display_name" value="{$display_name|escape:'html'}" class="form-control required">
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label">{$g_lang_label_type_pr_sec}:</label>
                <div class="col-sm-4">
                    <select name="type_pr_sec" class="form-select" onchange="showdivs(this.value,'{$udf|escape:'html'}')">
                        <option value="primary">Primary Items</option>
                        <option value="secondary">Secondary Items</option>
                    </select>
                </div>
            </div>
            <hr>
            <div id="txtHint">
                <div class="table-responsive">
                    <table class="table table-bordered" id="editUdfTable">
                        <thead class="table-primary">
                            <tr>
                                <th>{$g_lang_button_delete}?</th>
                                <th>{$g_lang_value}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$rows item=item}
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="x{$item[0]|escape:'html'}" class="form-check-input">
                                </td>
                                <td>{$item[1]|escape:'html'}</td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label">{$g_lang_new}:</label>
                <div class="col-sm-4">
                    <input maxlength="16" name="newvalue" class="form-control">
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" value="Update" class="btn btn-primary">{$g_lang_button_update}</button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='admin'">{$g_lang_button_cancel}</button>
            </div>
        </div>
    </div>
</form>
{literal}
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('editUdfForm');
    form.addEventListener('submit', function(e) {
        var required = form.querySelectorAll('.required');
        for (var i = 0; i < required.length; i++) {
            if (required[i].value.trim() === '') {
                e.preventDefault();
                required[i].classList.add('is-invalid');
                required[i].focus();
                return;
            }
            required[i].classList.remove('is-invalid');
        }
    });
});
</script>
{/literal}
