<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">{$g_lang_accesslogpage_access_log}</h3>
    </div>
    <div class="card-body">
        <form name="table" method="post" action="access_log">
            {$csrf_token_field}
            <table id="filetable" class="display table table-striped" border="0">
            <thead>
                <tr>
                    <th>{$g_lang_label_file_name}</th>
                    <th>{$g_lang_label_fileid}</th>
                    <th>{$g_lang_label_username}</th>
                    <th>{$g_lang_label_action}</th>
                    <th>{$g_lang_label_date}</th>
                </tr>
            </thead>
            <tbody>
            {foreach from=$accesslog_array item=item}
                <tr>
                    <td><a href="{$item.details_link}">{$item.realname|escape:'html'}</a></td>
                    <td>{$item.file_id}</td>
                    <td>{$item.user_name|escape:'html'}</td>
                    <td>{$item.action|escape:'html'}</td>
                    <td>{$item.timestamp|escape:'html'}</td>
                </tr>
            {foreachelse}
                <tr><td colspan="5">{$g_lang_message_no_records}</td></tr>
            {/foreach}
            </tbody>
            </table>
        </form>
    </div>
</div>
