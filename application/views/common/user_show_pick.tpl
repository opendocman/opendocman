<form action="user" method="POST" enctype="multipart/form-data">
    {$csrf_token_field}
    <input type="hidden" name="state" value="{$state|escape:'html'}" />
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">{$g_lang_userpage_user}</label>
                <select name="item" class="form-select">
                    {foreach from=$user_list item=item name=user_list}
                        <option value="{$item.id|escape}">{$item.last_name|escape:'html'}, {$item.first_name|escape:'html'} - {$item.username|escape:'html'}</option>
                    {/foreach}
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="Submit" name="submit" value="Show User">{$g_lang_userpage_button_show}</button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_userpage_button_cancel}</button>
            </div>
        </div>
    </div>
</form>
