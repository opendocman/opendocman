<div class="admin-sidebar" id="adminSidebar">
    <div class="position-relative mb-3">
        <input type="search" id="adminSidebarSearch" class="form-control form-control-sm" placeholder="{$g_lang_settings_sidebar_search|escape:'html'}">
    </div>
    <ul class="nav nav-pills flex-column gap-1 admin-sidebar-nav" id="adminSidebarNav">
        <li class="nav-item">
            <a class="nav-link {if $active_admin eq 'dashboard'}active{/if}" href="admin">
                {$g_lang_label_admin|default:'Admin'}
            </a>
        </li>
        <li class="nav-item mt-2">
            <div class="admin-sidebar-group-label">{$g_lang_settings_sidebar_group_content|default:'Content Management'}</div>
        </li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'users'}active{/if}" href="admin_users">{$g_lang_users|default:'Users'}</a></li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'departments'}active{/if}" href="admin_departments">{$g_lang_label_department|default:'Departments'}</a></li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'categories'}active{/if}" href="admin_categories">{$g_lang_category|default:'Categories'}</a></li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'udf'}active{/if}" href="udf?submit=add">{$g_lang_label_user_defined_fields|default:'User Defined Fields'}</a></li>

        <li class="nav-item mt-2">
            <div class="admin-sidebar-group-label">{$g_lang_settings_sidebar_group_files|default:'Files'}</div>
        </li>
        <li class="nav-item"><a class="nav-link" href="delete?mode=view_del_archive">{$g_lang_label_delete_undelete|default:'Delete / Undelete'}</a></li>
        <li class="nav-item"><a class="nav-link" href="toBePublished">{$g_lang_label_reviews|default:'Reviews'}</a></li>
        <li class="nav-item"><a class="nav-link" href="rejects">{$g_lang_label_rejections|default:'Rejections'}</a></li>
        <li class="nav-item"><a class="nav-link" href="check-exp">{$g_lang_label_check_expiration|default:'Check Expiration'}</a></li>
        <li class="nav-item"><a class="nav-link" href="file_ops?submit=view_checkedout">{$g_lang_label_checked_out_files|default:'Checked-out Files'}</a></li>

        <li class="nav-item mt-2">
            <div class="admin-sidebar-group-label">{$g_lang_settings_sidebar_group_system|default:'Settings &amp; System'}</div>
        </li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'settings'}active{/if}" href="settings?submit=update">{$g_lang_label_settings|default:'Settings'}</a></li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'filetypes'}active{/if}" href="filetypes?submit=update">{$g_lang_adminpage_edit_filetypes|default:'File Types'}</a></li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'content_index'}active{/if}" href="content_index">{$g_lang_label_content_search_index|default:'Content Search Index'}</a></li>

        <li class="nav-item mt-2">
            <div class="admin-sidebar-group-label">{$g_lang_settings_sidebar_group_reports|default:'Reports'}</div>
        </li>
        <li class="nav-item"><a class="nav-link {if $active_admin eq 'access_log'}active{/if}" href="access_log?submit=update">{$g_lang_adminpage_access_log|default:'Access Log'}</a></li>

        {if isset($settings_groups)}
        <li class="nav-item mt-2">
            <div class="admin-sidebar-group-label">{$g_lang_settings_sidebar_group_settings_groups|default:'Settings Groups'}</div>
        </li>
        {foreach from=$settings_groups item=grp}
        <li class="nav-item">
            <a class="nav-link setting-group-link {if isset($smarty.request.settings_group) and $smarty.request.settings_group eq $grp.name}active{/if}"
               href="#" data-group="{$grp.name}">{$grp.label|escape:'html'}</a>
        </li>
        {/foreach}
        {/if}
    </ul>
</div>