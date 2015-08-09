<form action="udf.php" method="POST" enctype="multipart/form-data">
    <table border=0>

            <tr><th align=right>{$g_lang_label_name}:</th><td>{$udf.table_name}</td></tr>
            <tr><th align=right>{$g_lang_label_display}:</th><td>{$udf.display_name}</td></tr>
            <input type="hidden" name="type" value="{$udf.field_type}">

        <input type="hidden" name="id" value="{$udf.table_name}">

        <tr>
            <td valign="top">{$g_lang_message_are_you_sure_remove}</td>
            <td align="center">
                <div class="buttons">
                    <button class="positive" type="Submit" name="deleteudf" value="Yes">{$g_lang_button_yes}</button>
                    <button class="negative" type="Submit" name="cancel" value="Cancel">{$g_lang_button_cancel}</button>
                </div>
            </td>
        </tr>
    </table>
</form>