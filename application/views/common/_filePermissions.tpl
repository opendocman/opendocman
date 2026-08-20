<div id="permissionsEditor" style="width:100%;">
    <p class="text-muted small mb-2">{$g_lang_filepermissionspage_edit_department_permissions}</p>
</div>

<script src="{$g_base_url}js/permissions-editor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {ldelim}
    var departments = [
        {foreach from=$avail_depts item=dept}
        {ldelim}id: {$dept.id}, name: '{$dept.name|escape:'javascript'}'{rdelim},
        {/foreach}
    ];
    var users = [
        {foreach from=$avail_users item=user}
        {ldelim}id: {$user.id}, last_name: '{$user.last_name|escape:'javascript'}', first_name: '{$user.first_name|escape:'javascript'}'{rdelim},
        {/foreach}
    ];
    var labels = {ldelim}
        forbidden: '{$g_lang_addpage_forbidden|escape:'javascript'}',
        view: '{$g_lang_addpage_view|escape:'javascript'}',
        read: '{$g_lang_addpage_read|escape:'javascript'}',
        write: '{$g_lang_addpage_write|escape:'javascript'}',
        admin: '{$g_lang_addpage_admin|escape:'javascript'}',
        unset: '{$g_lang_addpage_none|escape:'javascript'}',
        edit: '{$g_lang_edit|escape:'javascript'}',
        overview: '{$g_lang_filepermissionspage_perm_overview|escape:'javascript'}',
        add: '{$g_lang_filepermissionspage_perm_add|escape:'javascript'}',
        name: '{$g_lang_label_name|escape:'javascript'}',
        type: '{$g_lang_type|escape:'javascript'}',
        inheritedFrom: '{$g_lang_filepermissionspage_perm_inherited_from|escape:'javascript'}',
        inherited: '{$g_lang_filepermissionspage_perm_inherited|escape:'javascript'}',
        deptPrefix: '{$g_lang_department|escape:'javascript'}',
        userPrefix: '{$g_lang_label_user|escape:'javascript'}',
        noPermissions: '{$g_lang_filepermissionspage_perm_no_permissions|escape:'javascript'}',
        category: '{$g_lang_category|escape:'javascript'}'
    {rdelim};
    initPermissionsEditor('#permissionsEditor', {ldelim} departments: departments, users: users, defaultOwnerId: {$pre_selected_owner|default:$user_id|default:'0'}, labels: labels {rdelim});

    {if isset($dept_perms) || isset($user_perms)}
    var editor = initPermissionsEditor('#permissionsEditor');
    editor.loadTemplate({ldelim}
        dept_perms: {if isset($dept_perms)}{$dept_perms|@json_encode}{else}[]{/if},
        user_perms: {if isset($user_perms)}{$user_perms|@json_encode}{else}[]{/if}
{rdelim});
{/if}
{literal}
    var ownerSelect = document.querySelector('select[name="file_owner"]');
    if (ownerSelect) {
        ownerSelect.addEventListener('change', function () {
            var editor = initPermissionsEditor('#permissionsEditor');
            if (editor) editor.setDefaultOwner(ownerSelect.value);
        });
    }
    document.getElementById('permissionsEditor').closest('form').addEventListener('submit', function () {
        var form = this;
        var editor = initPermissionsEditor('#permissionsEditor');
        var data = editor.getData();
        Object.keys(data.department_permission).forEach(function (deptId) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'department_permission[' + deptId + ']';
            input.value = data.department_permission[deptId];
            form.appendChild(input);
        });
        Object.keys(data.user_permission).forEach(function (userId) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_permission[' + userId + ']';
            input.value = data.user_permission[userId];
            form.appendChild(input);
        });
    });
{/literal}
{rdelim});
</script>