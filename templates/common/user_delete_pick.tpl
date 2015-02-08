<table border="0" cellspacing="5" cellpadding="5">
    <form action="user.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="state" value="<?php echo($_REQUEST['state'] + 1); ?>">
        <tr>
            <td><b>{$g_lang_userpage_user}</b></td>
            <td colspan=3>
                <select name="item">
                    {foreach from=$user_list item=item name=user_list}
                        <option value="{$item.id}">{$item.last_name}, {$item.first_name} - {$item.username}</option>
                    {/foreach}
                </select>
            </td>
            <td>
                <div class="buttons"><button class="positive" type="Submit" name="submit" value="Delete">{$g_lang_userpage_button_delete}</button></div>
            </td>
            <td>
                <div class="buttons"><button class="negative" type="Submit" name="cancel" value="Cancel">{$g_lang_userpage_button_cancel}</button></div>
            </td>
        </tr>
    </form>
</table>
