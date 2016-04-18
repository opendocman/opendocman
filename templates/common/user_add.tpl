<form name="add_user" id="add_user" action="user.php" method="POST" enctype="multipart/form-data">
    <table border="0" cellspacing="5" cellpadding="5">
        {$onBeforeAddUser}
        <tr><td><b>{$g_lang_label_last_name}</b></td><td><input name="last_name" type="text" class="required" minlength="2" maxlength="255"></td></tr>
        <tr><td><b>{$g_lang_label_first_name}</b></td><td><input name="first_name" type="text" class="required" minlength="2" maxlength="255"></td></tr>
        <tr><td><b>{$g_lang_username}</b></td><td><input name="username" type="text" class="required" minlength="2" maxlength="25"></td></tr>
        <tr>
            <td><b>{$g_lang_label_phone_number}</b></td>
            <td>
                <input name="phonenumber" type="text" maxlength="20">
            </td>
        </tr>
        <tr>
            <td><b>{$g_lang_label_example}</b></td>
            <td><b>999 9999999</b></td>
        </tr>
        
        {if $mysql_auth}
        <tr>
            <td><b>{$g_lang_userpage_password}</b></td>
            <td>
                <input name="password" type="text" value="{$rand_password}" class="required" minlength="5" maxlength="32">
            </td>
        </tr>
        {/if}
        
        <tr>
            <td><b>{$g_lang_label_email_address}</b></td>
            <td>
                <input name="Email" type="text" class="required email" maxlength="50">
            </td>
        </tr>
        <tr>

        <tr>
            <td><b>{$g_lang_label_department}</b></td>
            <td>
                <select name="department">
                    {foreach from=$department_list item=item name=department_list}
                    <option value={$item.id|escape}>{$item.name|escape:'html'}</option>
                    {/foreach}
                </select>
            </td>
        <tr>
            <td><b>{$g_lang_label_is_admin}?</b></td>
            <td>
                <input name="admin" type="checkbox" value="1" id="cb_admin">
            </td>
        </tr>
        <tr id="userReviewDepartmentRow">
            <td id="userReviewDepartmentLabelTd"><b>{$g_lang_label_reviewer_for}</b></td>
            <td id="userReviewDepartmentListTd">
                <select class="multiView" name="department_review[]" multiple="multiple" id="userReviewDepartmentsList" />
                {foreach from=$department_list item=item name=department_list}
                    <option value={$item.id|escape}>{$item.name|escape:'html'}</option>
                {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td><b>{$g_lang_userpage_can_add}?</b></td>
            <td>
                <input name="can_add" type="checkbox" value="1" id="cb_can_add"  checked="checked">
            </td>
        </tr>
        <tr>
            <td><b>{$g_lang_userpage_can_checkin}?</b></td>
            <td>
                <input name="can_checkin" type="checkbox" value="1" id="cb_can_checkin"  checked="checked">
            </td>
        </tr>
        <tr>
            <td align="center">
                <div class="buttons">
                    <button id="submitButton" class="positive" type="Submit" name="submit" value="Add User">{$g_lang_userpage_button_add_user}</button>
                </div>
            </td>
            <td>
                <div class="buttons">
                    <button id="cancelButton" class="negative cancel" type="Submit" name="cancel" value="Cancel">{$g_lang_userpage_button_cancel}</button>
                </div>
            </td>
        </tr>
    </table>
</form>
<script>
    {literal}
    $(document).ready(function(){
        $('#submitButton').click(function(){
            $('#add_user').validate();
        })
    });
    {/literal}
</script>