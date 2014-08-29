{literal}
<style>
.accordion {
   margin: 10px;   
   dt, dd {
      padding: 10px;
      border: 1px solid black;
      border-bottom: 0; 
      &:last-of-type {
        border-bottom: 1px solid black; 
      }
      a {
        display: block;
        color: black;
        font-weight: bold;
      }
   }
  dd {
     border-top: 0; 
     font-size: 12px;
     &:last-of-type {
       border-top: 1px solid white;
       position: relative;
       top: -1px;
     }
  }
}
</style>
{/literal}

<dl class="accordion">
    <dt><a href="">{$g_lang_filepermissionspage_edit_department_permissions}</a></dt>
    <dd>
        <table id="department_permissions_table" class="display">
            <thead>
                <tr>
                    <td>Department</td>
                    <td>Forbidden</td>
                    <td>None</td>
                    <td>View</td>
                    <td>Read</td>
                    <td>Write</td>
                    <td>Admin</td>
                </tr>
            </thead>
            <tbody>
                {foreach from=$avail_depts item=dept}
                    {if $dept.selected eq 'selected'}
                        {assign var="selected" value="checked='checked'"}
                    {else}
                        {assign var="noneselected" value="checked='checked'"}
                    {/if}
                <tr>
                    <td>{$dept.name}</td>
                    <td><input type="radio" name="department_permission[{$dept.id}]" value="-1" {if $dept.rights eq '-1'}checked="checked"{/if} /></td>
                    <td><input type="radio" name="department_permission[{$dept.id}]" value="0" {if $dept.rights eq '0'}checked="checked"{/if} {$noneselected}/></td>
                    <td><input type="radio" name="department_permission[{$dept.id}]" value="1" {if $dept.rights eq 1}checked="checked"{/if} {$selected} /></td>
                    <td><input type="radio" name="department_permission[{$dept.id}]" value="2" {if $dept.rights eq 2}checked="checked"{/if} /></td>
                    <td><input type="radio" name="department_permission[{$dept.id}]" value="3" {if $dept.rights eq 3}checked="checked"{/if} /></td>
                    <td><input type="radio" name="department_permission[{$dept.id}]" value="4" {if $dept.rights eq 4}checked="checked"{/if} /></td>
                </tr>
                    {assign var="selected" value=""}
                {/foreach}       
            </tbody>
        </table>
    </dd>
    <hr />
    <dt><a href="">{$g_lang_filepermissionspage_edit_user_permissions}</a></dt>
    <dd>
        <table id="user_permissions_table" class="display">
            <thead>
                <tr>
                    <td>User</td>
                    <td>Forbidden</td>
                    <td>View</td>
                    <td>Read</td>
                    <td>Write</td>
                    <td>Admin</td>
                </tr>
            </thead>
            <tbody>
                {foreach from=$avail_users item=user}
                {if $user.rights eq ''}
                    {assign var="selected" value="checked='checked'"}
                {/if} 

                <tr>
                    <td>{$user.last_name}, {$user.first_name}</td>
                    <td><input type="radio" name="user_permission[{$user.id}]" value="-1" {if $user.rights eq '-1'}checked="checked"{/if} /></td>
                    <td><input type="radio" name="user_permission[{$user.id}]" value="1" {if $user.rights eq 1}checked="checked"{/if} /></td>
                    <td><input type="radio" name="user_permission[{$user.id}]" value="2" {if $user.rights eq 2}checked="checked"{/if} /></td>
                    <td><input type="radio" name="user_permission[{$user.id}]" value="3" {if $user.rights eq 3}checked="checked"{/if} /></td>
                    <td><input type="radio" name="user_permission[{$user.id}]" value="4" {if $user.rights eq 4 || ($user.id eq $user_id && $user.rights eq '') }checked="checked"{/if} /></td>
                </tr>
                {/foreach}       
            </tbody>
        </table>
    </dd>
</dl>
{literal}
<script>
    $(document).ready(function() {
        
        (function($) {
            var allPanels = $('.accordion > dd').hide();           
            $('.accordion > dt > a').click(function() {
                allPanels.slideUp();
                $(this).parent().next().slideDown();
                return false;
                });
         })(jQuery);

    $department_permissions_table = $('#department_permissions_table');
    
    if ($department_permissions_table && $department_permissions_table.length > 0) {
       var oTable = $department_permissions_table.dataTable({
            "sScrollY": "300px",
            "bPaginate": false,
            "bAutoWidth": false,
            "oLanguage": {
                "sUrl": "includes/language/DataTables/datatables." + langLanguage + ".txt"
            }
        });
    }
    
    $user_permissions_table = $('#user_permissions_table');
    if ($user_permissions_table && $user_permissions_table.length > 0) {
       var oTable2 = $user_permissions_table.dataTable({
            "sScrollY": "300px",
            "bPaginate": false,
            "bAutoWidth": false,
            "oLanguage": {
                "sUrl": "includes/language/DataTables/datatables." + langLanguage + ".txt"
            }
        });

    }
    } );

</script>
{/literal}
