<div id="permissionsEditor" class="w-50">
    <p class="text-muted small mb-2">{$g_lang_filepermissionspage_edit_department_permissions}</p>
</div>

<script src="{$g_base_url}js/permissions-editor.js"></script>
<script>
$(document).ready(function () {ldelim}
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
    $('#permissionsEditor').permissionsEditor({ldelim} departments: departments, users: users {rdelim});

    {if isset($dept_perms) || isset($user_perms)}
    $('#permissionsEditor').permissionsEditor('loadTemplate', {ldelim}
        dept_perms: {if isset($dept_perms)}{$dept_perms|@json_encode}{else}[]{/if},
        user_perms: {if isset($user_perms)}{$user_perms|@json_encode}{else}[]{/if}
{rdelim});
{/if}

    $('#permissionsEditor').closest('form').on('submit', function () {
        var form = $(this);
        var data = $('#permissionsEditor').permissionsEditor('getData');
        $.each(data.department_permission, function (deptId, rights) {
            $('<input>', { type: 'hidden', name: 'department_permission[' + deptId + ']', value: rights }).appendTo(form);
        });
        $.each(data.user_permission, function (userId, rights) {
            $('<input>', { type: 'hidden', name: 'user_permission[' + userId + ']', value: rights }).appendTo(form);
        });
    });
{rdelim});
</script>