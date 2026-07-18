<form name="main" action="toBePublished" method="post" class="container mt-3">
    <div class="card">
        <div class="card-body">
            <div class="d-flex gap-2 mb-3">
                <input type="hidden" name="mode" value="{$lmode|escape:'html'}" />
                <input type="hidden" name="Docflag" value="-1" />
                <button type="submit" name="submit" value="commentAuthorize" class="btn btn-success">{$g_lang_button_authorize}</button>
                <button type="submit" name="submit" value="commentReject" class="btn btn-danger">{$g_lang_button_reject}</button>
            </div>
        </div>
    </div>
</form>
