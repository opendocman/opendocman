<table border="0" cellspacing="5" cellpadding="5">
    <form action="user.php" method="POST" enctype="multipart/form-data">
        <INPUT type="hidden" name="state" value="{$state|escape:'html'}" />
        <tr>
            <td><b>{$g_lang_userpage_user}</b></td>
            <td colspan=3>
                <select name="item">
                    {foreach from=$user_list item=item name=user_list}
                        <option value="{$item.id|escape}">{$item.last_name|escape:'html'}, {$item.first_name|escape:'html'} - {$item.username|escape:'html'}</option>
                    {/foreach}
                </select>
            </td>
            <td  align="center">
                <div class="buttons">
                    <button class="positive" type="Submit" name="submit" value="Show User">{$g_lang_userpage_button_show}</button>
                </div>
            </td>
            <td>
                <div class="buttons">
                    <button class="negative" type="Submit" name="cancel" value="Cancel">{$g_lang_userpage_button_cancel}</button>
                </div>
            </td>
        </tr>
    </form>
</table>
