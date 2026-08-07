<div id="permissionsEditor" class="w-50">
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
    initPermissionsEditor('#permissionsEditor', {ldelim} departments: departments, users: users {rdelim});

    {if isset($dept_perms) || isset($user_perms)}
    var editor = initPermissionsEditor('#permissionsEditor');
    editor.loadTemplate({ldelim}
        dept_perms: {if isset($dept_perms)}{$dept_perms|@json_encode}{else}[]{/if},
        user_perms: {if isset($user_perms)}{$user_perms|@json_encode}{else}[]{/if}
{rdelim});
{/if}
{literal}
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