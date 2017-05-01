<form action="udf.php" method="POST" enctype="multipart/form-data">
    <table border="0" cellspacing="5" cellpadding="5">

        <input type="hidden" name="state" value="{$state}">
        <tr>
            <td><b><?php echo msg('label_user_defined_field')?></b></td>
            <td colspan=3>
                <select name="item">
                    {foreach from=$udfs item=item}
                        <option value="{$item.table_name|escape:'html'}">{$item.display_name|escape:'html'}</option>
                    {/foreach}
                </select>
            </td>
            <td align="center">
                <div class="buttons">
                    <button class="positive" type="Submit" name="submit" value="delete">{$g_lang_button_delete}</button>
                </div>
            </td>
            <td align="center">
                <div class="buttons">
                    <button class="negative cancel" type="Submit" name="cancel" value="Cancel">{$g_lang_button_cancel}</button>
                </div>
            </td>
        </tr>

    </table>
</form>