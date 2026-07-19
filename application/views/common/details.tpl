<form name="data" class="mt-3">
    {$csrf_token_field}
    <input type="hidden" name="to" value="{$file_detail.to_value|escape:'html'}" />
    <input type="hidden" name="subject" value="{$file_detail.subject_value|escape:'html'}" />
    <input type="hidden" name="comments" value="{$file_detail.comments_value|escape:'html'}" />

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            {if $file_detail.file_unlocked}
                <img src="images/file_unlocked.png" alt="" class="me-2">
            {else}
                <img src="images/file_locked.png" alt="" class="me-2">
            {/if}
            <h5 class="mb-0">{$file_detail.realname|escape:'html'}</h5>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{$g_lang_category}</dt>
                <dd class="col-sm-9">{$file_detail.category|escape:'html'}</dd>

                {$file_detail.udf_details_display}

                <dt class="col-sm-3">{$g_lang_label_size}</dt>
                <dd class="col-sm-9">{$file_detail.filesize|escape:'html'}</dd>

                <dt class="col-sm-3">{$g_lang_label_created_date}</dt>
                <dd class="col-sm-9">{$file_detail.created|escape:'html'}</dd>

                <dt class="col-sm-3">{$g_lang_owner}</dt>
                <dd class="col-sm-9">
                    <a href="mailto:{$file_detail.owner_email|escape:'html'}?Subject=Regarding%20your%20document:{$file_detail.realname|escape:'html'}&Body=Hello%20{$file_detail.owner_fullname|escape:'html'}">{$file_detail.owner|escape:'html'}</a>
                </dd>

                <dt class="col-sm-3">{$g_lang_label_description}</dt>
                <dd class="col-sm-9">{$file_detail.description|escape:'html'}</dd>

                <dt class="col-sm-3">{$g_lang_label_comment}</dt>
                <dd class="col-sm-9">{$file_detail.comment|escape:'html'}</dd>

                <dt class="col-sm-3">{$g_lang_revision}</dt>
                <dd class="col-sm-9"><div id="details_revision">{$file_detail.revision|escape:'html'}</div></dd>

                {if $file_detail.file_under_review}
                <dt class="col-sm-3">{$g_lang_label_reviewer}</dt>
                <dd class="col-sm-9">
                    {$file_detail.reviewer|escape:'html'} (<a href="javascript:showMessage()">{$g_lang_message_reviewers_comments_re_rejection}</a>)
                </dd>
                {/if}

                {if $file_detail.status gt 0}
                <dt class="col-sm-3">{$g_lang_detailspage_file_checked_out_to}</dt>
                <dd class="col-sm-9">
                    <a href="mailto:{$checkout_person_email|escape:'html'}?Subject=Regarding%20your%20checked-out%20document:{$file_detail.realname|escape:'html'}&Body=Hello%20{$checkout_person_full_name.$fullname[0]|escape:'html'}">{$checkout_person_full_name[1]|escape:'html'}, {$checkout_person_full_name[0]|escape:'html'}</a>
                </dd>
                {/if}
            </dl>
        </div>
        <div class="card-footer">
            <div class="btn-group" role="group">
                {if $view_link ne ''}
                    <a href="{$view_link|escape}" class="btn btn-outline-primary btn-sm">{$g_lang_detailspage_view}</a>
                {/if}
                {if $check_out_link ne ''}
                    <a href="{$check_out_link|escape}" class="btn btn-outline-secondary btn-sm">{$g_lang_detailspage_check_out}</a>
                {/if}
                {if $edit_link ne ''}
                    <a href="{$edit_link|escape}" class="btn btn-outline-warning btn-sm">{$g_lang_detailspage_edit}</a>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="deleteBtn">{$g_lang_detailspage_delete}</button>
                {/if}
                <a href="{$history_link|escape}" class="btn btn-outline-info btn-sm">{$g_lang_detailspage_history}</a>
            </div>
        </div>
    </div>
</form>

{literal}
<script>
(function() {
    var message_window;
    var mesg_window_frm;

    var deleteBtn = document.getElementById('deleteBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (window.confirm('{/literal}{$g_lang_detailspage_are_sure}{literal}')) {
                window.location = '{/literal}{$my_delete_link}{literal}';
            }
        });
    }

    function sendFields() {
        mesg_window_frm = message_window.document.author_note_form;
        if (mesg_window_frm) {
            var dataForm = document.forms['data'];
            mesg_window_frm.to.value = dataForm.to.value;
            mesg_window_frm.subject.value = dataForm.subject.value;
            mesg_window_frm.comments.value = dataForm.comments.value;
        }
    }

    window.showMessage = function() {
        message_window = window.open('{/literal}{$comments_link|escape}{literal}' , 'comment_wins', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=450,height=200');
        message_window.focus();
        setTimeout(sendFields, 500);
    };
})();
</script>
{/literal}