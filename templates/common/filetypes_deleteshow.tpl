<table class="form-table" style="width: 200px;">
        <thead>
            <tr>
                <th colspan="3">{$g_lang_label_delete}&nbsp;{$g_lang_label_filetypes}&nbsp;-&nbsp;{$g_lang_choose}</th>

            </tr>

            </thead>
            <tbody>
                <tr>
                    <td>
                    <form action="filetypes.php" method="POST" enctype="multipart/form-data">
                        <select class="multiView" id="types" multiple="multiple" name="types[]">
                        {foreach from=$filetypes_array item=i}
                            <option value="{$i.id}">{$i.type}</option>
                        {/foreach}
                        </select>
                    </td>
                    <td align="center">
                    <div class="buttons"><button class="positive" type="submit" name="submit" value="Delete">{$g_lang_button_delete}</button>
         </form>
                </td>
         <form action="{php}echo $_SERVER['PHP_SELF']; {/php}">
            <td align="center">
                <div class="buttons"><button class="negative" type="submit" name="submit" value="Cancel">{$g_lang_button_cancel}</button></div>
            </td>
        </form>
        </tr>

        <tbody>
    </table>