<form action="user.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="state" value="{$state}" />
    <table border="0" cellspacing="5" cellpadding="5">
        <tr>
            <td><b>{$g_lang_userpage_user}</b></td>
            <td colspan=3>
                <select name="item">
                    {foreach from=$users item=item name=users}
                        <option value="{$item.id}">{$item.last_name}, {$item.first_name} - {$item.username}</option>
                    {/foreach}
                </select>
            </td>
            <td>
                <div class="buttons">
                    <button class="positive" type="Submit" name="submit" value="Modify User">{$g_lang_userpage_button_modify}</button>
                </div>
            </td>
            <td>
                <div class="buttons">
                    <button class="negative" type="Submit" name="cancel" value="Cancel">{$g_lang_userpage_button_cancel}</button>
                </div>
            </td>
        </tr>
    </table>
</form>