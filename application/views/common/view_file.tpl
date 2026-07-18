<div class="container mt-3">
    <div class="card">
        <div class="card-body text-center">
            <p class="card-text">{$g_lang_message_to_view_your_file}</p>
            <a class="btn btn-primary" target="_new" href="view_file?submit=view&id={$file_id|escape:'html'}&mimetype={$mimetype|escape:'html'}">{$g_lang_button_click_here}</a>
            <hr>
            <form action="view_file" method="get" class="d-inline">
                {$csrf_token_field}
                <input type="hidden" name="id" value="{$file_id|escape:'html'}">
                <input type="hidden" name="mimetype" value="{$mimetype|escape:'html'}">
                <button type="submit" name="submit" value="Download" class="btn btn-success">{$g_lang_message_if_you_are_unable_to_view2}</button>
            </form>
        </div>
    </div>
</div>