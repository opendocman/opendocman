<center>
    <table>
        <thead>
            <tr>
                <th>
                    {$g_lang_label_add}&nbsp;{$g_lang_label_filetype}
                </th>

            </tr>
        </thead
        <tbody>
            <tr>
                <td>
                    <form action="filetypes.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="Submit" value="add" />
            ex.: application/pdf<br />
            <input type="text" name="filetype" />
            <div class="buttons"><button class="positive" type="submit" name="submit" value="AddNewSave">{$g_lang_button_save}</button></div>
        </form>

        <form action="{php}echo $_SERVER['PHP_SELF']; {/php}">
            <div class="buttons"><button class="negative" type="submit" name="submit" value="Cancel">{$g_lang_button_cancel}</button></div>
        </form>
                </td>
                </tr>
    </tbody>
    </table>
</center>