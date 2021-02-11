<form id="editUdfForm" method="post">
    <input type="hidden" name="submit" value="edit">
    <input type="hidden" name="udf" value="{$udf|escape:'html'}">

    <table>
        <tr>
            <th align="right">{$g_lang_label_name}:</th>
            <td>{$udf}</td>
        </tr>
        <tr>
            <th align="right">{$g_lang_label_display} {$g_lang_label_name}:</th>
            <td>
                <input maxlength="16" name="display_name" value="{$display_name|escape:'html'}" class="required">
            </td>
        </tr>
        <tr>
            <th align="right">{$g_lang_label_type_pr_sec}:</th>
            <td>
                <select name="type_pr_sec" class="required" onchange="showdivs(this.value,'{$udf|escape:'html'}')">
                    <option value="primary">Primary Items</option>
                    <option value="secondary">Secondary Items</option>
                </select>
            </td>
        </tr>
    </table>
    <div id="txtHint">
    <table>
        <tr bgcolor="83a9f7">
            <th>{$g_lang_button_delete}?</th>
            <th>{$g_lang_value} </th>
        </tr>
        {foreach from=$rows item=item}
            {cycle values='FCFCFC, E3E7F9' assign=CellCSS}
            <tr bgcolor="{$CellCSS}">
                <td align="center">
                    <input type="checkbox" name="x{$item[0]|escape:'html'}">
                </td>
                <td>{$item[1]|escape:'html'}</td>
            </tr>
        {/foreach}
        <tr>
            <th align="right">{$g_lang_new}:</th>
            <td>
                <input maxlength="16" name="newvalue">
            </td>
        </tr>
        </div>
        <tr>
            <td colspan="2">
                <div class="buttons">
                    <button class="positive" type="submit" value="Update">{$g_lang_button_update}</button>
                    <button class="negative" type="Submit" name="cancel" value="Cancel">{$g_lang_button_cancel}</button>
                </div>

            </td>
        </tr>
    </table>
</form>
{literal}
<script>
    $(document).ready(function(){
        $('#editUdfForm').validate();
    });
</script>
{/literal}