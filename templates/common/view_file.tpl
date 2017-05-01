<form action="view_file.php" name="view_file_form" method="get">
    <input type="hidden" name="id" value="{$file_id|escape:'html'}">
    <input type="hidden" name="mimetype" value="{$mimetype|escape:'html'}">
    <br />
    {$g_lang_message_to_view_your_file} 
        <a class="body" style="text-decoration:none" target="_new" href="view_file.php?submit=view&id={$file_id|escape:'html'}&mimetype={$mimetype|escape:'html'}">{$g_lang_button_click_here}</a>
    <br><br>
    <div class="buttons">
        <button class="regular" type="submit" name="submit" value="Download">
            {$g_lang_message_if_you_are_unable_to_view2}
        </button>
    </div>
    {$g_lang_message_if_you_are_unable_to_view1}
    {$g_lang_message_if_you_are_unable_to_view2}
    {$g_lang_message_if_you_are_unable_to_view3}
</form>