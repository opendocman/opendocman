<div class="card">
    <div class="card-body">
        <h5 class="card-title">{$g_lang_label_add}&nbsp;{$g_lang_label_filetype}</h5>
        <form action="filetypes" method="POST" enctype="multipart/form-data">
            {$csrf_token_field}
            <input type="hidden" name="Submit" value="add" />
            <div class="mb-3">
                <label class="form-label">{$g_lang_label_filetype}</label>
                <input type="text" name="filetype" class="form-control" />
                <div class="form-text">ex.: application/pdf</div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit" name="submit" value="AddNewSave">{$g_lang_button_save}</button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_button_cancel}</button>
            </div>
        </form>
    </div>
</div>
