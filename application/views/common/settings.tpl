<form action="settings" method="POST" enctype="multipart/form-data" id="settingsForm" class="mt-3">
    {$csrf_token_field}
    <div class="row g-3">
        <div class="col-lg-3 col-xl-2">
            <div class="d-grid gap-2">
                <input type="search" id="settingsFilter" class="form-control form-control-sm" placeholder="{$g_lang_settings_filter|default:'Search settings…'}">
            </div>
            <ul class="nav nav-pills flex-column mt-3" id="settingsTabs" role="tablist">
                {foreach from=$settings_groups item=grp name=grps}
                <li class="nav-item">
                    <a class="nav-link {if $smarty.foreach.grps.first}active{/if}" id="tab-{$grp.name}" data-bs-toggle="pill"
                       href="#group-{$grp.name}" role="tab" data-group="{$grp.name}">
                        {$grp.label|escape:'html'}
                        <span class="badge bg-secondary ms-1">{$grp.settings|@count}</span>
                    </a>
                </li>
                {/foreach}
            </ul>
        </div>

        <div class="col-lg-9 col-xl-10">
            <div class="tab-content" id="settingsTabContent">
                {foreach from=$settings_groups item=grp name=grps2}
                <div class="tab-pane fade {if $smarty.foreach.grps2.first}show active{/if}" id="group-{$grp.name}" role="tabpanel" data-group="{$grp.name}">
                    <h5 class="mb-3">{$grp.label|escape:'html'}</h5>
                    {foreach from=$grp.settings item=i}
                    <div class="mb-3 row setting-row" data-settings-name="{$i.name|escape:'html'}" data-settings-desc="{$i.description|escape:'html'}">
                        <label class="col-sm-3 col-form-label">{$i.name|escape:'html'}</label>
                        <div class="col-sm-4">
                        {if $i.validation eq 'bool'}
                            <select name="{$i.name|escape:'html'}" class="form-select">
                                <option value="True" {if $i.value eq 'True'} selected="selected"{/if}>True</option>
                                <option value="False" {if $i.value eq 'False'} selected="selected"{/if}>False</option>
                            </select>
                        {elseif $i.name eq 'theme'}
                            <select name="theme" class="form-select">
                                {foreach from=$themes item=theme}
                                    <option value="{$theme|escape}" {if $i.value eq $theme}selected="selected"{/if}>{$theme|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {elseif $i.name eq 'language'}
                            <select name="language" class="form-select">
                                {foreach from=$languages item=language}
                                    <option value="{$language|escape}" {if $i.value eq $language} selected="selected"{/if}>{$language|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {elseif $i.name eq 'file_expired_action'}
                            <select name="file_expired_action" class="form-select">
                                <option value="1" {if $i.value eq '1'}selected="selected"{/if}>Remove from file list until renewed</option>
                                <option value="2" {if $i.value eq '2'}selected="selected"{/if}>Show in file list but non-checkoutable</option>
                                <option value="3" {if $i.value eq '3'}selected="selected"{/if}>Send email to reviewer only</option>
                                <option value="4" {if $i.value eq '4'}selected="selected"{/if}>Do Nothing</option>
                            </select>
                        {elseif $i.name eq 'authen'}
                            <select name="authen" class="form-select">
                                <option value="mysql" {if $i.value eq 'mysql'}selected="selected"{/if}>MySQL</option>
                            </select>
                        {elseif $i.name eq 'root_id'}
                            <select name="root_id" class="form-select">
                                {foreach from=$useridnums item=useridnum}
                                    <option value="{$useridnum[0]|escape}" {if $i.value eq $useridnum[0]} selected="selected"{/if}>{$useridnum[1]|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {elseif $i.name eq 'default_signup_department'}
                            <select name="default_signup_department" class="form-select">
                                <option value="" {if $i.value eq ''}selected="selected"{/if}>-- unassigned --</option>
                                {foreach from=$departments item=dept}
                                    <option value="{$dept[0]|escape}" {if $i.value eq $dept[0]}selected="selected"{/if}>{$dept[1]|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {else}
                            <input name="{$i.name|escape}" type="text" value="{$i.value|escape:'html'}" class="form-control">
                        {/if}
                        </div>
                        <div class="col-sm-5"><em>{$i.description|escape:'html'}</em></div>
                    </div>
                    {/foreach}
                </div>
                {/foreach}
            </div>

            <div class="d-flex gap-2 mt-3 sticky-bottom bg-white py-2">
                <button class="btn btn-primary" type="submit" name="submit" value="Save">{$g_lang_button_save}</button>
                <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_button_cancel}</button>
            </div>
        </div>
    </div>
</form>