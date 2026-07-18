<form name="add_user" id="add_user" action="user" method="POST" enctype="multipart/form-data" novalidate class="needs-validation container mt-3">
    {$csrf_token_field}
    <div class="card">
        <div class="card-body">
            {$onBeforeAddUser}
            <div class="mb-3">
                <label class="form-label">{$g_lang_label_last_name}</label>
                <input name="last_name" type="text" class="form-control" required minlength="2" maxlength="255">
                <div class="invalid-feedback">{$g_lang_label_last_name} is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{$g_lang_label_first_name}</label>
                <input name="first_name" type="text" class="form-control" required minlength="2" maxlength="255">
                <div class="invalid-feedback">{$g_lang_label_first_name} is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{$g_lang_username}</label>
                <input name="username" type="text" class="form-control" required minlength="2" maxlength="25">
                <div class="invalid-feedback">{$g_lang_username} is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{$g_lang_label_phone_number}</label>
                <input name="phonenumber" type="text" class="form-control" maxlength="20">
                <div class="form-text">{$g_lang_label_example}: 999 9999999</div>
            </div>
            {if $mysql_auth}
            <div class="mb-3">
                <label class="form-label">{$g_lang_userpage_password}</label>
                <input name="password" type="text" value="{$rand_password}" class="form-control" required minlength="5" maxlength="32">
                <div class="invalid-feedback">Password must be at least 5 characters.</div>
            </div>
            {/if}
            <div class="mb-3">
                <label class="form-label">{$g_lang_label_email_address}</label>
                <input name="Email" type="email" class="form-control" required maxlength="50">
                <div class="invalid-feedback">A valid email address is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{$g_lang_label_department}</label>
                <select name="department" class="form-select">
                    {foreach from=$department_list item=item name=department_list}
                    <option value={$item.id|escape}>{$item.name|escape:'html'}</option>
                    {/foreach}
                </select>
            </div>
            <div class="mb-3 form-check">
                <input name="admin" type="checkbox" value="1" id="cb_admin" class="form-check-input">
                <label class="form-check-label" for="cb_admin">{$g_lang_label_is_admin}?</label>
            </div>
            <div class="mb-3" id="userReviewDepartmentRow">
                <label class="form-label" id="userReviewDepartmentLabelTd">{$g_lang_label_reviewer_for}</label>
                <select name="department_review[]" multiple="multiple" id="userReviewDepartmentsList" class="form-select">
                {foreach from=$department_list item=item name=department_list}
                    <option value={$item.id|escape}>{$item.name|escape:'html'}</option>
                {/foreach}
                </select>
            </div>
            <div class="mb-3 form-check">
                <input name="can_add" type="checkbox" value="1" id="cb_can_add" checked="checked" class="form-check-input">
                <label class="form-check-label" for="cb_can_add">{$g_lang_userpage_can_add}?</label>
            </div>
            <div class="mb-3 form-check">
                <input name="can_checkin" type="checkbox" value="1" id="cb_can_checkin" checked="checked" class="form-check-input">
                <label class="form-check-label" for="cb_can_checkin">{$g_lang_userpage_can_checkin}?</label>
            </div>
            <div class="d-flex gap-2">
                <button id="submitButton" class="btn btn-primary" type="submit" name="submit" value="Add User">{$g_lang_userpage_button_add_user}</button>
                <button id="cancelButton" class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_userpage_button_cancel}</button>
            </div>
        </div>
    </div>
</form>
<script>
(function() {
    var form = document.getElementById('add_user');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();
</script>
