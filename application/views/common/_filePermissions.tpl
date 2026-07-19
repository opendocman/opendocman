<div class="accordion w-50" id="permissionsAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingDept">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDept" data-toggle="collapse" data-target="#collapseDept" aria-expanded="true" aria-controls="collapseDept">
                {$g_lang_filepermissionspage_edit_department_permissions}
            </button>
        </h2>
        <div id="collapseDept" class="accordion-collapse collapse show in" aria-labelledby="headingDept" data-bs-parent="#permissionsAccordion" data-parent="#permissionsAccordion">
            <div class="accordion-body p-0">
                <div class="table-responsive" style="max-height:300px; overflow-y:auto;">
                    <table class="table table-striped table-sm mb-0" id="department_permissions_table">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Forbidden</th>
                                <th>None</th>
                                <th>View</th>
                                <th>Read</th>
                                <th>Write</th>
                                <th>Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$avail_depts item=dept}
                            <tr>
                                <td>{$dept.name|escape:'html'}</td>
                                <td><input type="radio" name="department_permission[{$dept.id}]" value="-1" {if isset($dept.rights) && $dept.rights eq '-1'}checked{/if} class="form-check-input" /></td>
                                <td><input type="radio" name="department_permission[{$dept.id}]" value="0" {if isset($dept.rights) && $dept.rights eq '0'}checked{elseif !isset($dept.rights) || $dept.rights eq ''}checked{/if} class="form-check-input" /></td>
                                <td><input type="radio" name="department_permission[{$dept.id}]" value="1" {if isset($dept.rights) && $dept.rights eq 1}checked{elseif isset($dept.selected) && $dept.selected eq 'selected' && (!isset($dept.rights) || $dept.rights eq '')}checked{/if} class="form-check-input" /></td>
                                <td><input type="radio" name="department_permission[{$dept.id}]" value="2" {if isset($dept.rights) && $dept.rights eq 2}checked{/if} class="form-check-input" /></td>
                                <td><input type="radio" name="department_permission[{$dept.id}]" value="3" {if isset($dept.rights) && $dept.rights eq 3}checked{/if} class="form-check-input" /></td>
                                <td><input type="radio" name="department_permission[{$dept.id}]" value="4" {if isset($dept.rights) && $dept.rights eq 4}checked{/if} class="form-check-input" /></td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="accordion-item">
        <h2 class="accordion-header" id="headingUser">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUser" data-toggle="collapse" data-target="#collapseUser" aria-expanded="false" aria-controls="collapseUser">
                {$g_lang_filepermissionspage_edit_user_permissions}
            </button>
        </h2>
        <div id="collapseUser" class="accordion-collapse collapse" aria-labelledby="headingUser" data-bs-parent="#permissionsAccordion" data-parent="#permissionsAccordion">
            <div class="accordion-body p-0">
                <div class="table-responsive" style="max-height:300px; overflow-y:auto;">
                    <table class="table table-striped table-sm mb-0" id="user_permissions_table">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Forbidden</th>
                                <th>View</th>
                                <th>Read</th>
                                <th>Write</th>
                                <th>Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$avail_users item=user}
                            <tr>
                                <td>{$user.last_name|escape:'html'}, {$user.first_name|escape:'html'}</td>
                                <td><input type="radio" name="user_permission[{$user.id}]" value="-1" {if isset($user.rights) && $user.rights eq '-1'}checked{/if} class="form-check-input" /></td>
                                <td><input type="radio" name="user_permission[{$user.id}]" value="1" {if isset($user.rights) && $user.rights eq 1}checked{/if} class="form-check-input" /></td>
                                <td><input type="radio" name="user_permission[{$user.id}]" value="2" {if isset($user.rights) && $user.rights eq 2}checked{/if} class="form-check-input" /></td>
                                <td><input type="radio" name="user_permission[{$user.id}]" value="3" {if isset($user.rights) && $user.rights eq 3}checked{/if} class="form-check-input" /></td>
                                <td><input type="radio" name="user_permission[{$user.id}]" value="4" {if (isset($user.rights) && $user.rights eq 4) || ($user.id eq $user_id && (!isset($user.rights) || $user.rights eq ''))}checked{/if} class="form-check-input" /></td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>