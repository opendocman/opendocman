<script src="{$g_base_url}js/functions.js"></script>

<form id="addeditform" name="main" action="edit" method="POST" enctype="multipart/form-data" novalidate class="needs-validation">
    {$csrf_token_field}
    <input type="hidden" id="db_prefix" value="{$db_prefix}" />

    {assign var='i' value='0'}
    {foreach from=$t_name item=name name='loop1'}
    <input type="hidden" id="secondary{$i|escape}" name="secondary{$i|escape:'html'}" value="" />
    <input type="hidden" id="tablename{$i|escape}" name="tablename{$i|escape:'html'}" value="{$name|escape:'html'}" />
    {assign var='i' value=$i+1}
    {/foreach}
    <input type="hidden" id="id" name="id" value="{$file_id|escape:'html'}" />
    <input id="i_value" type="hidden" name="i_value" value="{$i|escape:'html'}" />

    <div class="mt-3">
        <div class="card">
            <div class="card-body form-grid">
                <div class="mb-3">
                    <label class="form-label">{$g_lang_label_name}</label>
                    <p class="form-control-plaintext"><b>{$realname|escape:'html'}</b></p>
                </div>

                {if $is_admin == true}
                <div class="mb-3">
                    <label class="form-label">{$g_lang_editpage_assign_owner}</label>
                    <select name="file_owner" class="form-select">
                    {foreach from=$avail_users|smarty:nodefaults item=user}
                        <option value="{$user.id|escape}" {if $pre_selected_owner eq $user.id}selected{/if}>{$user.last_name|escape:'html'}, {$user.first_name|escape:'html'}</option>
                    {/foreach}
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">{$g_lang_editpage_assign_department}</label>
                    <select name="file_department" class="form-select">
                    {foreach from=$avail_depts|smarty:nodefaults item=dept}
                        <option value="{$dept.id|escape}" {if $pre_selected_department eq $dept.id}selected{/if}>{$dept.name|escape:'html'}</option>
                    {/foreach}
                    </select>
                </div>
                {/if}

                <div class="mb-3">
                    <label class="form-label">
                        <a href="help.html#Add_File_-_Category" onClick="return popup(this, 'Help')" class="text-decoration-none">{$g_lang_category}</a>
                    </label>
                    <select name="category" class="form-select">
                    {foreach from=$cats_array|smarty:nodefaults item=cat}
                        <option value="{$cat.id|escape}" {if $pre_selected_category eq $cat.id}selected{/if}>{$cat.name|escape:'html'}</option>
                    {/foreach}
                    </select>
                    {if $is_admin}
                    <button type="button" id="showAddCategory" class="btn btn-sm btn-outline-primary mt-1">+ {$g_lang_button_add_category}</button>
                    <div id="addCategoryForm" class="mt-1 p-2 border rounded d-none">
                        <div class="input-group input-group-sm mb-1">
                            <input type="text" id="newCategoryName" class="form-control" maxlength="40" placeholder="{$g_lang_label_name}" required>
                            <button type="button" id="saveCategory" class="btn btn-primary">{$g_lang_button_add_category}</button>
                            <button type="button" id="cancelCategory" class="btn btn-secondary">{$g_lang_button_cancel}</button>
                        </div>
                        <span id="categoryStatus" class="small"></span>
                    </div>
                    {/if}
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
                    <input type="text" name="description" class="form-control" value="{$description|escape:'html'}">
                </div>

                <div class="mb-3 full-width">
                    <label class="form-label">
                        <a href="help.html#Add_File_-_Comment" onClick="return popup(this, 'Help')" class="text-decoration-none">{$g_lang_label_comment}</a>
                    </label>
                    <textarea name="comment" rows="4" class="form-control" onchange="this.value=enforceLength(this.value, 255);">{$comment|escape:'html'}</textarea>
                </div>

<script>
    var CSRF_FIELD_NAME = '{$csrf_field_name}';
    var CSRF_INDEX_NAME = '{$csrf_index_name}';
    var CSRF_FIELD_VALUE = '{$category_csrf_token}';
    var CSRF_INDEX_VALUE = '{$category_csrf_index}';
</script>
<script src="{$g_base_url}js/inline-add-category.js"></script>

<script>
$(document).ready(function () {ldelim}
    $('select[name="category"]').on('change', function () {ldelim}
        var catId = $(this).val();
        if (!catId) return;
        $.getJSON('category?submit=get_perms_json&cat_id=' + catId, function (data) {ldelim}
            $('#permissionsEditor').permissionsEditor('loadTemplate', data);
        {rdelim});
    {rdelim});
{rdelim});
</script>

(End of file - total 154 lines)
</content>