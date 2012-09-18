<div id="filetable_wrapper">
<form name="table" method="post" action="{$smarty.server.PHP_SELF}">
    <table id="filetable" class="display" border="0" cellpadding="1" cellspacing="1">
    <thead>
        <tr>
            {if $showCheckBox}
                <th class="sorting_desc_disabled sorting_asc_disabled"><input type="checkbox" id="checkall"/></th>
            {/if}
            <th class="sorting">{$g_lang_label_file_name}</th>
            <th>{$g_lang_label_fileid}</th>
            <th>{$g_lang_label_username}</th>
            <th class="sorting">{$g_lang_label_action}</th>
            <th class="sorting">{$g_lang_label_date}</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$accesslog_array item=item name=accesslog_array}
        <tr {if $item.lock eq true}class="gradeX"{/if} >
            <td class="center"><a href="{$item.details_link}">{$item.realname}</a></td>
            <td class="center" style="width: 50px;">{$item.file_id}</td>
            <td class="center">{$item.user_name}</td>
            <td class="center">{$item.action}</td>
            <td class="center">{$item.timestamp}</td>
        </tr>
        {/foreach}
    </tbody>
    <tfoot>
       <tr>
            <th class="sorting">{$g_lang_label_file_name}</th>
            <th>{$g_lang_label_fileid}</th>
            <th>{$g_lang_label_username}</th>
            <th class="sorting">{$g_lang_label_action}</th>
            <th class="sorting">{$g_lang_label_date}</th>
        </tr>
    </tfoot>
    {if $form ne '1'}
</form>
    {/if}
</table>
</div>
<br />