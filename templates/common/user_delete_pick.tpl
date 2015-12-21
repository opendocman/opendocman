<table border="0" cellspacing="5" cellpadding="5">
    <form action="user.php" method="POST" enctype="multipart/form-data">
        {assign var="nextstate" value=$state+1}
        <input type="hidden" name="state" value="{$nextstate|escape:'html'}">
        <tr>
            <td><b>{$g_lang_userpage_user}</b></td>
            <td colspan=3>
                <select name="item">
                    {foreach from=$user_list item=item name=user_list}
                        <option value="{$item.id|escape}">{$item.last_name|escape:'html'}, {$item.first_name|escape:'html'} - {$item.username|escape:'html'}</option>
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
