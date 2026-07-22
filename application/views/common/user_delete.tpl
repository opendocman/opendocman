<form action="user" method="POST" enctype="multipart/form-data">
    {$csrf_token_field}
    <div class="card">
        <div class="card-body">
            <input type="hidden" name="id" value="{$user_id|escape:'html'}">
            <p class="mb-3">{$g_lang_userpage_are_sure} {$full_name[0]|escape:'html'} {$full_name[1]|escape:'html'}?</p>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="Submit" name="submit" value="Delete User">{$g_lang_userpage_button_delete}</button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_userpage_button_cancel}</button>
            </div>
        </div>
    </div>
</form>
