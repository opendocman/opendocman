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
                    </td>
                <td>
                        <div class="buttons"><button class="positive" type="submit" name="submit" value="AddNewSave">{$g_lang_button_save}</button>
                    </td>
                    <td >
                        <div class="buttons">
                            <button class="negative" type="Submit" name="submit" value="Cancel">{$g_lang_button_cancel}</button>
                        </div>
                     </td>
        </tr>
    </tbody>
    </table>