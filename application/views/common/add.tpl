<script src="{$g_base_url}js/functions.js"></script>

<form id="addeditform" name="main" action="add" method="POST" enctype="multipart/form-data" onsubmit="return checksec();" novalidate class="needs-validation">
    {$csrf_token_field}
    <input type="hidden" id="db_prefix" value="{$db_prefix|escape:'html'}" />

    {assign var='i' value='0'}
    {foreach from=$t_name item=name name='loop1'}
    <input type="hidden" id="secondary{$i|escape:'html'}" name="secondary{$i|escape:'html'}" value="" />
    <input type="hidden" id="tablename{$i|escape:'html'}" name="tablename{$i|escape:'html'}" value="{$name|escape:'html'}" />
    {assign var='i' value=$i+1}
    {/foreach}
    <input id="i_value" type="hidden" name="i_value" value="{$i|escape:'html'}" />

    <div class="mt-3">
        <div class="card">
            <div class="card-body form-grid">
                <div class="mb-3 full-width">
                    <label class="form-label">
                        <a href="help.html#Add_File_-_File_Location" onClick="return popup(this, 'Help')" class="text-decoration-none">{$g_lang_label_file_location}</a>
                    </label>
                    <input name="file[]" type="file" multiple="multiple" class="form-control">
                </div>

                {if $is_admin == true}
                <div class="mb-3">
                    <label class="form-label">{$g_lang_editpage_assign_owner}</label>
                    <select name="file_owner" class="form-select">
                    {foreach from=$avail_users item=user}
                        <option value="{$user.id}" {$user.selected}>{$user.last_name|escape:'html'}, {$user.first_name|escape:'html'}</option>
                    {/foreach}
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">{$g_lang_editpage_assign_department}</label>
                    <select name="file_department" class="form-select">
                    {foreach from=$avail_depts item=dept}
                        <option value="{$dept.id}" {$dept.selected}>{$dept.name|escape:'html'}</option>
                    {/foreach}
                    </select>
                </div>
                {/if}

                <div class="mb-3">
                    <label class="form-label">
                        <a href="help.html#Add_File_-_Category" onClick="return popup(this, 'Help')" class="text-decoration-none">{$g_lang_category}</a>
                    </label>
                    <select name="category" class="form-select">
                    {foreach from=$cats_array item=cat}
                        <option value="{$cat.id}">{$cat.name|escape:'html'}</option>
                    {/foreach}
                    </select>
                </div>

                <div class="mb-3 full-width" id="departmentSelect">
                    <label class="form-label">
                        <a href="help.html#Add_File_-_Department" onClick="return popup(this, 'Help')" class="text-decoration-none">{$g_lang_addpage_permissions}</a>
                    </label>
                    <hr>
                    {include file='../../views/common/_filePermissions.tpl'}
                    <hr>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <a href="help.html#Add_File_-_Description" onClick="return popup(this, 'Help')" class="text-decoration-none">{$g_lang_label_description}</a>
                    </label>
                    <input type="text" name="description" class="form-control">
                </div>

                <div class="mb-3 full-width">
                    <label class="form-label">
                        <a href="help.html#Add_File_-_Comment" onClick="return popup(this, 'Help')" class="text-decoration-none">{$g_lang_label_comment}</a>
                    </label>
                    <textarea name="comment" rows="4" class="form-control" onchange="this.value=enforceLength(this.value, 255);"></textarea>
                </div>