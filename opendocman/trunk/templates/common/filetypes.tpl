<center>
    <h2>{$last_message}</h2>
    <table class="form-table" style="width: 200px;">
        <thead>
            <tr>
                <th>{$g_lang_label_allowed}&nbsp;{$g_lang_label_filetypes}</th>
            </tr>

            </thead>
            <tbody>
                <tr>
                    <td>
                        <form action="filetypes.php" method="POST" enctype="multipart/form-data">
                            <select multiple="multiple" id="types" name="types[]">
                            {foreach from=$filetypes_array item=i}
                                <option value="{$i.id}" {if $i.active eq '1'}selected="selected"{/if}>{$i.type}</option>
                            {/foreach}
                            </select>
                    </td>
                
                    <td align="center">
                    <div class="buttons"><button class="positive" type="submit" name="submit" value="Save">{$g_lang_button_save}</button>

         </form>
                                            </td>
                                            <td>

         <form action="{php}echo $_SERVER['PHP_SELF']; {/php}">
                <div class="buttons"><button class="negative" type="submit" name="submit" value="Cancel">{$g_lang_button_cancel}</button></div>
            </td>
        </form>
        </tr>
        <tr>
            <td>
                <a href="filetypes.php?submit=AddNew">{$g_lang_label_add}&nbsp;{$g_lang_label_filetype}</a>&nbsp;|&nbsp;<a href="filetypes.php?submit=DeleteSelect">{$g_lang_label_delete}&nbsp;{$g_lang_label_filetype}</a>
            </td>
        </tr>
        <tbody>
    </table>
</center>