<script type="text/javascript" src="../includes/jquery.min.js"></script>
<script type="text/javascript" src="http://ajax.microsoft.com/ajax/jquery.validate/1.7/jquery.validate.pack.js"></script>
<script type="text/javascript" src="http://ajax.microsoft.com/ajax/jquery.validate/1.7/additional-methods.js"></script>
<center>
    <table class="form-table">
        <form action="settings.php" method="POST" enctype="multipart/form-data">
        
            <tr>
                <th>{$g_lang_label_name}</th><th>{$g_lang_value}</th><th>{$g_lang_label_description}</th>{$g_lang_label_settings}</th>
            </tr>
            {foreach from=$settings_array item=i}
            <tr>
                <td>{$i.name}</td>
                <td>
                {if $i.validation eq 'bool'}
                    <select name="{$i.name}">
                        <option value="True" {if $i.value eq 'True'} selected="selected"{/if}>True</option>
                        <option value="False" {if $i.value eq 'False'} selected="selected"{/if}>False</option>
                    </select>
                {elseif $i.name eq 'theme'}
                    <select name="theme">
                        {foreach from=$themes item=theme}
                            <option value="{$theme}" {if $i.value eq $theme}selected="selected"{/if}>{$theme}</option>
                        {/foreach}
                    </select>
                {elseif $i.name eq 'language'}
                    <select name="language">
                        {foreach from=$languages item=language}
                            <option value="{$language}" {if $i.value eq $language} selected="selected"{/if}>{$language}</option>
                        {/foreach}
                    </select>
                 {elseif $i.name eq 'file_expired_action'}
                    <select name="file_expired_action">
                        <option value="1" {if $i.value eq '1'}selected="selected"{/if}>Remove from file list until renewed</option>
                        <option value="2" {if $i.value eq '2'}selected="selected"{/if}>Show in file list but non-checkoutable</option>
                        <option value="3" {if $i.value eq '3'}selected="selected"{/if}>Send email to reviewer only</option>
                        <option value="4" {if $i.value eq '4'}selected="selected"{/if}>Do Nothing</option>
                    </select>
                {elseif $i.name eq 'authen'}
                    <select name="authen">
                        <option value="mysql" {if $i.value eq 'mysql'}selected="selected"{/if}>MySQL</option>
                    </select>
                {elseif $i.name eq 'root_username'}
                    <select name="root_username">
                        {foreach from=$usernames item=username}
                            <option value="{$username[0]}" {if $i.value eq $username[0]} selected="selected"{/if}>{$username[0]}</option>
                        {/foreach}
                    </select>
                {else}
                    <input size="40" name="{$i.name}" type="text" value="{$i.value}">
                {/if}
                </td>
                <td><em>{$i.description}</em></td>
            </tr>
            {/foreach}
                <td align="center">
                    <div class="buttons"><button class="positive" type="submit" name="submit" value="Save">{$g_lang_button_save}</buttons></td>
         </form>
         <form action="{php}echo $_SERVER['PHP_SELF']; {/php}">
            <td align="center">
                <div class="buttons"><button class="negative" type="submit" name="submit" value="Cancel">{$g_lang_button_cancel}</button></div>
            </td>
        </form>
        </tr>
    </table>
</center>