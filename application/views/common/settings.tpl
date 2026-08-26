<form action="settings" method="POST" enctype="multipart/form-data" id="settingsForm" class="mt-3">
    {$csrf_token_field}

    <div class="mb-3">
        <input type="search" id="settingsFilter" class="form-control form-control-sm"
               placeholder="{$g_lang_settings_filter|default:'Search settings…'}" aria-label="{$g_lang_settings_filter|default:'Search settings…'}">
    </div>

    <div class="accordion" id="settingsAccordion">
        {foreach from=$settings_groups item=grp name=grps}
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-{$grp.name}">
                <button class="accordion-button {if !$smarty.foreach.grps.first}collapsed{/if}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#group-{$grp.name}" aria-expanded="{if $smarty.foreach.grps.first}true{else}false{/if}"
                        aria-controls="group-{$grp.name}">
                    {$grp.label|escape:'html'}
                    <span class="badge bg-secondary ms-2">{$grp.settings|@count}</span>
                </button>
            </h2>
            <div id="group-{$grp.name}" class="accordion-collapse collapse {if $smarty.foreach.grps.first}show{/if}"
                 aria-labelledby="heading-{$grp.name}">
                <div class="accordion-body">
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
{elseif $i.name eq 'incoming_mail_protocol'}
                            <select name="mail_protocol" class="form-select">
                                <option value="imap" {if $i.value eq 'imap'}selected="selected"{/if}>IMAP</option>
                                <option value="pop3" {if $i.value eq 'pop3'}selected="selected"{/if}>POP3</option>
                            </select>
                        {elseif $i.name eq 'incoming_mail_encryption'}
                            <select name="mail_encryption" class="form-select">
                                <option value="none" {if $i.value eq 'none'}selected="selected"{/if}>None</option>
                                <option value="ssl" {if $i.value eq 'ssl'}selected="selected"{/if}>SSL</option>
                                <option value="tls" {if $i.value eq 'tls'}selected="selected"{/if}>TLS</option>
                            </select>
                        {elseif $i.name eq 'incoming_mail_default_department'}
                            <select name="mail_default_department" class="form-select">
                                <option value="" {if $i.value eq ''}selected="selected"{/if}>-- none --</option>
                                {foreach from=$departments item=dept}
                                    <option value="{$dept[0]|escape}" {if $i.value eq $dept[0]}selected="selected"{/if}>{$dept[1]|escape:'html'}</option>
                                {/foreach}
                            </select>
                        {elseif $i.name eq 'incoming_mail_default_category'}
                            <select name="mail_default_category" class="form-select">
                                <option value="" {if $i.value eq ''}selected="selected"{/if}>-- none --</option>
                                {foreach from=$categories item=cat}
                                    <option value="{$cat[0]|escape}" {if $i.value eq $cat[0]}selected="selected"{/if}>{$cat[1]|escape:'html'}</option>
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
            </div>
        </div>
        {/foreach}
    </div>

    <div class="d-flex gap-2 mt-3 sticky-bottom bg-white py-2">
        <button class="btn btn-primary" type="submit" name="submit" value="Save">{$g_lang_button_save}</button>
        <button class="btn btn-secondary" type="button" onclick="window.location.href='admin'">{$g_lang_button_cancel}</button>
    </div>
</form>
<script src="{$g_base_url}js/bootstrap5/admin-settings.js"></script>