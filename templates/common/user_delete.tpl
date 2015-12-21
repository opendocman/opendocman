<table border="0" cellspacing="5" cellpadding="5">
    <form action="user.php" method="POST" enctype="multipart/form-data">
        <tr>
            <td valign="top">{$g_lang_userpage_are_sure}
                <input type="hidden" name="id" value="{$user_id|escape:'html'}">
                    {$full_name[0]|escape:'html'} {$full_name[1]|escape:'html'}?
            </td>
            <td align="center">
                <div class="buttons"><button class="positive" type="Submit" name="submit" value="Delete User">{$g_lang_userpage_button_delete}</button></div>
            </td>
            <td align="center">
                <div class="buttons"><button class="negative" type="Submit" name="cancel" value="Cancel">{$g_lang_userpage_button_cancel}</button></div>
            </td>
        </tr>
    </form>
</table>