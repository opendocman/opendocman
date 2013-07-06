<div id="filetable_wrapper">
<form name="table" method="post" action="{$smarty.server.PHP_SELF}">
    <table id="filetable" class="display" border="0" cellpadding="1" cellspacing="1">
    <thead>
        <tr>
            {if $showCheckBox}
                <th class="sorting_desc_disabled sorting_asc_disabled"><input type="checkbox" id="checkall"/></th>
            {/if}
            <th class="sorting">ID</th>
            <th>{$g_lang_label_view}</th>
            <th class="sorting">{$g_lang_label_file_name}</th>
            <th class="sorting">{$g_lang_label_description}</th>
            <th class="sorting">{$g_lang_label_rights}</th>
            <th class="sorting">{$g_lang_label_created_date}</th>
            <th class="sorting">{$g_lang_label_modified_date}</th>
            <th class="sorting">{$g_lang_label_author}</th>
            <th class="sorting">{$g_lang_label_department}</th>
            <th class="sorting">{$g_lang_label_size}</th>
            <th class="">{$g_lang_label_status}</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$file_list_arr item=item name=file_list}
        <tr {if $item.lock eq true}class="gradeX"{/if} >
            {if $item.showCheckbox eq '1'}
                {assign var=form value=1}
                <td><input type="checkbox" class="checkbox" value="{$item.id}" name="checkbox[]"/></td>
            {/if}
            <td class="center">{$item.id}</td>
            <td class="center" style="width: 50px;">
                {if $item.view_link eq 'none'}
                    &nbsp;
                {else}
                    <a href="{$item.view_link}">{$g_lang_outpage_view}</a></td>
                {/if}
            <td><a href="{$item.details_link}">{$item.filename}</a></td>
            <td >{$item.description}</td>
            <td class="center">{$item.rights[0][1]} | {$item.rights[1][1]} | {$item.rights[2][1]}</td>
            <td>{$item.created_date}</td>
            <td>{$item.modified_date}</td>
            <td>{$item.owner_name}</td>
            <td>{$item.dept_name}</td>
            <td >{$item.filesize}</td>
            <td class="center">
                {if $item.lock eq false}
                    <img src="{$g_base_url}/images/file_unlocked.png" alt="unlocked" />
		{else}
                    <img src="{$g_base_url}/images/file_locked.png" alt="locked" />
                {/if}
            </td>
        </tr>
        {/foreach}
    </tbody>
    <tfoot>
       <tr>
           {if $item.showCheckbox eq '1'}
           <th></th>
           {/if}
            <th>ID</th>
            <th>{$g_lang_label_view}</th>
            <th>{$g_lang_label_file_name}</th>
            <th>{$g_lang_label_description}</th>
            <th>{$g_lang_label_rights}</th>
            <th>{$g_lang_label_created_date}</th>
            <th>{$g_lang_label_modified_date}</th>
            <th>{$g_lang_label_author}</th>
            <th>{$g_lang_label_department}</th>
            <th>{$g_lang_label_size}</th>
            <th>{$g_lang_label_status}</th>
        </tr>
    </tfoot>
    {if $form ne '1'}
</form>
    {/if}
</table>
</div>
<br />