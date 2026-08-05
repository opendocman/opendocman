{if isset($mode)}{$mode|escape:'html'}{/if}
{$g_lang_email_note_to_authors}
<form name="author_note_form" class="needs-validation mt-3" novalidate
    {if isset($mode) && $mode eq 'root'}
     action="toBePublished?mode=root" method="POST">
    {else}
     action="toBePublished" method="POST">
    {/if}
{$csrf_token_field}
<div class="card mb-3">
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">{$g_lang_email_to}</label>
            <input type="text" name="to" value="Author(s)" class="form-control" size="15" {$access_mode|escape:'html'}>
        </div>
        <div class="mb-3">
            <label class="form-label">{$g_lang_email_subject}</label>
            <input type="text" name="subject" value="" class="form-control" size="50" {$access_mode|escape:'html'}>
        </div>
        <div class="mb-3">
            <label class="form-label">{$g_lang_email_custom_comment}</label>
            <textarea name="comments" cols="45" rows="7" class="form-control" {$access_mode|escape:'html'}></textarea>
        </div>
    </div>
</div>

<input type="hidden" name="checkbox" value="{$checkbox|escape:'html'}" />

<div class="card mb-3">
    <div class="card-body">
        <div class="mb-3 form-check">
            <input type="checkbox" name="send_to_all" id="send_to_all" class="form-check-input">
            <label class="form-check-label" for="send_to_all">{$g_lang_email_email_all_users}</label>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="send_to_dept" id="send_to_dept" class="form-check-input">
            <label class="form-check-label" for="send_to_dept">{$g_lang_email_email_whole_department}</label>
        </div>
        <div class="mb-3">
            <label class="form-label">{$g_lang_email_email_these_users}:</label>
            <select name="send_to_users[]" id="send_to_users" multiple class="form-select">
                <option value="0">no one</option>
                <option value="owner" selected="selected">file owners</option>
                {foreach from=$user_info item=user}
                <option value="{$user.id}">{$user.last_name|escape:'html'}, {$user.first_name|escape:'html'}</option>
                {/foreach}
            </select>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mb-3">
    <button class="btn btn-primary" type="submit" name="submit" value="{$submit_value|escape:'html'}">{$submit_value|escape:'html'}</button>
    <button class="btn btn-secondary" type="button" onclick="window.location.href='out'">{$g_lang_button_cancel}</button>
</div>
</form>

{literal}
<script>
(function() {
    var sendToAll = document.getElementById('send_to_all');
    var sendToDept = document.getElementById('send_to_dept');
    var sendToUsers = document.getElementById('send_to_users');

    function updateDisableState() {
        if (sendToDept.checked || (sendToUsers.selectedIndex > -1 && sendToUsers.options[sendToUsers.selectedIndex].value !== '0')) {
            sendToAll.disabled = true;
        } else {
            sendToAll.disabled = false;
            for (var i = 1; i < sendToUsers.options.length; i++) {
                sendToUsers.options[i].selected = false;
            }
        }
    }

    sendToAll.addEventListener('change', function() {
        sendToDept.disabled = sendToAll.checked;
        sendToUsers.disabled = sendToAll.checked;
    });

    sendToDept.addEventListener('change', function() {
        updateDisableState();
    });

    sendToUsers.addEventListener('change', function() {
        updateDisableState();
    });
})();
</script>
{/literal}
