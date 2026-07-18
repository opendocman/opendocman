<form name="update" id="modifyUserForm" action="user" method="POST" enctype="multipart/form-data" novalidate class="needs-validation container mt-3">
    {$csrf_token_field}
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">{$g_lang_userpage_id}</label>
                <p class="form-control-plaintext">{$user->id|escape:'html'}</p>
                <input type="hidden" name="id" value="{$user->id|escape:'html'}">
            </div>
            <div class="mb-3">
                <label class="form-label">{$g_lang_userpage_last_name}</label>
                <input name="last_name" type="text" value="{$user->last_name|escape:'html'}" class="form-control" required minlength="2" maxlength="255">
                <div class="invalid-feedback">{$g_lang_userpage_last_name} is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{$g_lang_userpage_first_name}</label>
                <input name="first_name" type="text" value="{$user->first_name|escape:'html'}" class="form-control" required minlength="2" maxlength="255">
                <div class="invalid-feedback">{$g_lang_userpage_first_name} is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{$g_lang_userpage_username}</label>
                <input name="username" type="text" value="{$user->username|escape:'html'}" class="form-control" required minlength="2" maxlength="25">
                <div class="invalid-feedback">{$g_lang_userpage_username} is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{$g_lang_userpage_phone_number}</label>
                <input name="phonenumber" type="text" value="{$user->phone|escape:'html'}" class="form-control" maxlength="20">
            </div>
            {if $mysql_auth}
            <div class="mb-3">
                <label class="form-label">{$g_lang_userpage_password}</label>
                <input name="password" type="password" class="form-control" maxlength="32">
                <div class="form-text">{$g_lang_userpage_leave_empty}</div>
            </div>
            {/if}
            <div class="mb-3">
                <label class="form-label">{$g_lang_userpage_email}</label>
                <input name="Email" type="email" value="{$user->email|escape:'html'}" class="form-control" required maxlength="50">
                <div class="invalid-feedback">A valid email address is required.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{$g_lang_userpage_department}</label>
                <select name="department" class="form-select" {$mode|escape:'html'}>
                    {foreach from=$department_list item=item name=department_list}
                        {if $item.id == $user_department}
                            <option selected value="{$item.id|escape:'html'}">{$item.name|escape:'html'}</option>
                        {else}
                            <option value="{$item.id|escape:'html'}">{$item.name|escape:'html'}</option>
                        {/if}
                    {/foreach}
                </select>
            </div>
            <div class="mb-3 form-check">
                <input name="admin" type="checkbox" value="1" {if $is_admin}checked{/if} {$mode|escape:'html'} id="cb_admin" class="form-check-input">
                <label class="form-check-label" for="cb_admin">{$g_lang_userpage_admin}</label>
            </div>
            <div class="mb-3" id="userReviewDepartmentRow" {if $display_reviewer_row}style="display: none;"{/if}>
                <label class="form-label" id="userReviewDepartmentLabelTd">{$g_lang_userpage_reviewer_for}</label>
                <select id="userReviewDepartmentsList" name="department_review[]" multiple="multiple" class="form-select" {$mode}>
                    {foreach from=$department_select_options item=item name=department_select_options}
                        {$item}
                    {/foreach}
                </select>
            </div>
            <div class="mb-3 form-check">
                <input name="can_add" type="checkbox" value="1" {$can_add|escape:'html'} {$mode|escape:'html'} id="cb_can_add" class="form-check-input">
                <label class="form-check-label" for="cb_can_add">{$g_lang_userpage_can_add}?</label>
            </div>
            <div class="mb-3 form-check">
                <input name="can_checkin" type="checkbox" value="1" {$can_checkin|escape:'html'} {$mode|escape:'html'} id="cb_can_checkin" class="form-check-input">
                <label class="form-check-label" for="cb_can_checkin">{$g_lang_userpage_can_checkin}?</label>
            </div>
            <div class="d-flex gap-2">
                <input type="hidden" name="set_password" value="0">
                <button class="btn btn-primary" type="submit" name="submit" value="Update User">{$g_lang_userpage_button_update}</button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_userpage_button_cancel}</button>
            </div>
        </div>
    </div>
</form>
<script>
(function() {
    var form = document.getElementById('modifyUserForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();
</script>
