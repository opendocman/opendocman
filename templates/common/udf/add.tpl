<form id="udfAddForm" action="udf.php?last_message={$last_message|escape:'html'}" method="GET" enctype="multipart/form-data">
    <table border="0" cellspacing="5" cellpadding="5">
        <tr>
            <td><b>{$g_lang_label_name}(limit 5)</b></td>
            <td colspan="3"><input maxlength="5" name="table_name" type="text" class="required"></td>
        </tr>
        <tr>
            <td><b>{$g_lang_label_display} {$g_lang_label_name}</b></td>
            <td colspan="3"><input maxlength="16" name="display_name" type="text" class="required"></td>
        </tr>
        <tr>
            <td><b>{$g_lang_type}</b></td>
            <td colspan="3"><select name="field_type">
                    <option value=1>{$g_lang_select} {$g_lang_list}</option>
                    <option value=4>{$g_lang_label_sub_select_list}</option>
                    <option value=2>{$g_lang_label_radio_button}</option>
                    <option value=3>{$g_lang_label_text}</option>
                </select>
            </td>
        </tr>
        <tr>
            <td align="center">
                <div class="buttons">
                    <button class="positive" type="Submit" name="submit" value="Add User Defined Field">{$g_lang_button_save}</button>
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
<script>
    {literal}
    $(document).ready(function(){
        $('#udfAddForm').validate();
    });
    {/literal}
</script>