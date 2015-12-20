<form name="update" id="modifyUserForm" action="user.php" method="POST" enctype="multipart/form-data">
    <table border="0" cellspacing="5" cellpadding="5">
        <tr>
            <td><b>{$g_lang_userpage_id}</b></td><td colspan=4>{$user->id|escape:'html'}</td>
            <input type=hidden name=id value="{$user->id|escape:'html'}">
            </tr>
        <tr>
            <td><b>{$g_lang_userpage_last_name}</b></td>
            <td colspan=4><input name="last_name" type="text" value="{$user->last_name|escape:'html'}" class="required" minlength="2" maxlength="255"></td>
        </tr>
        <tr>
            <td><b>{$g_lang_userpage_first_name}</b></td>
            <td colspan=4><input name="first_name" type="text" value="{$user->first_name|escape:'html'}" class="required" minlength="2" maxlength="255"></td>
        </tr>
        <tr>
            <td><b>{$g_lang_userpage_username}</b></td>
            <td colspan=4><input name="username" type="text" value="{$user->username|escape:'html'}" class="required" minlength="2" maxlength="25"></td>
        </tr>
        <tr>
            <td><b>{$g_lang_userpage_phone_number}</b></td>
            <td colspan=4><input name="phonenumber" type="text" value="{$user->phone|escape:'html'}" maxlegnth="20"></td>
        </tr>
        {if $mysql_auth}
            <tr>
                <td><b>{$g_lang_userpage_password}</b></td>
                <td>
                    <input name="password" type="password" maxlength="32">
                    {$g_lang_userpage_leave_empty}
                </td>
            </tr>
        {/if}
        <tr>
            <td><b>{$g_lang_userpage_email}</td>
            <td colspan=4>
                <input name="Email" type="text" value="{$user->email|escape:'html'}" class="email required" maxlength="50"></td>
        </tr>
        <tr>
            <td><b>{$g_lang_userpage_department}</b></td>
            <td colspan=3>

                <select name="department" {$mode|escape:'html'}>
                    {foreach from=$department_list item=item name=department_list}
                        {if $item.id == $user_department}
                            <option selected value="{$item.id|escape:'html'}">{$item.name|escape:'html'}</option>
                        {else}
                            <option value="{$item.id|escape:'html'}">{$item.name|escape:'html'}</option>
                        {/if}
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td><b>{$g_lang_userpage_admin}</b></td>
            <td colspan=1>
                <input name="admin" type="checkbox" value="1" {if $is_admin}checked{/if} {$mode|escape:'html'} id="cb_admin" />
            </td>
        </tr>
        <tr id="userReviewDepartmentRow" {if $display_reviewer_row}style="display: none;"{/if} >
            <td id="userReviewDepartmentLabelTd">{$g_lang_userpage_reviewer_for}</td>
            <td id="userReviewDepartmentListTd">
                <select class="multiView" id="userReviewDepartmentsList" name="department_review[]" multiple="multiple" {$mode} >
                    {foreach from=$department_select_options item=item name=department_select_options}
                        {$item}
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td>{$g_lang_userpage_can_add}?</td>
            <td>
                <input name="can_add" type="checkbox" value="1" {$can_add|escape:'html'} {$mode|escape:'html'} id="cb_can_add" />
            </td>
        </tr>
        <tr>
            <td>{$g_lang_userpage_can_checkin}?</td>
            <td>
                <input name="can_checkin" type="checkbox" value="1" {$can_checkin|escape:'html'} {$mode|escape:'html'} id="cb_can_checkin" />
            </td>
        </tr>
        <tr>
            <td align="center">
                <input type="hidden" name="set_password" value="0">

                <div class="buttons">
                    <button class="positive" type="Submit" name="submit" value="Update User">{$g_lang_userpage_button_update}</button>
                </div>
            </td>
            <td>
                <div class="buttons">
                    <button class="negative cancel" type="Submit" name="cancel" value="Cancel">{$g_lang_userpage_button_cancel}</button>
                </div>
            </td>
        </tr>
    </table>
</form>
<script>
    {literal}
        $(document).ready(function () {
            $('#modifyUserForm').validate();
        });
    {/literal}
</script>