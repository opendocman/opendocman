		{$mode}
                {$g_lang_email_note_to_authors}
		<form name="author_note_form"
			{if $mode eq 'root'}
			 action="{$smarty.server.PHP_SELF}?mode=root" method="POST">
			{else}
			 action="{$smarty.server.PHP_SELF}" method="POST">
                        {/if}
		<table name="author_note_table">
		<tr>
		<td>{$g_lang_email_to}</td>
		<td>
                    <input type="text" name="to" value="Author(s)" size='15' {$access_mode}>
                </td>
		</tr>
                <tr>
                    <td>{$g_lang_email_subject}</td>
                    <td>
                        <input type="text" name="subject" size=50 value="" size='30' {$access_mode}></td>
                </tr>
                <tr>
                    <td>{$g_lang_email_custom_comment}</td>
                    <td><textarea name="comments" cols=45 rows=7 size='220' {$access_mode}></textarea></td>
                </tr>
		</table>
		<br />&nbsp&nbsp
                    

			<tr><input type="hidden" name="checkbox" value="{foreach from=$checkbox item=id}{$id} {/foreach}" /></tr>
			<table border="0">
			<tr>
                            <td>{$g_lang_email_email_all_users}</td>
                            <td>
                                <input type="checkbox" name="send_to_all" onchange="send_to_dept.disabled = !send_to_dept.disabled; author_note_form.elements['send_to_users[]'].disabled = !author_note_form.elements['send_to_users[]'].disabled;"></td>
                        </tr>
			<tr>
                            <td>{$g_lang_email_email_whole_department}</td>
                            <td>
                                <input type="checkbox" name="send_to_dept" onchange="check(this.form.elements['send_to_users[]'], this, send_to_all);"></td>
                        </tr>
			<tr>
                            <td valign="top">{$g_lang_email_email_these_users}:</td>
                            <td>
                                <select name="send_to_users[]" multiple onchange="check(this, send_to_dept, send_to_all);">
                                    <option value="0">no one</option>
                                    <option value="owner" selected="selected">file owners</option>
                                    {foreach from=$user_info item=user}
                                    <option value="{$user.id}">{$user.last_name}, {$user.first_name}</option>
                                    {/foreach}

                                    
			</select></td></tr></table>
			<br />
                         <div class="buttons">
                            <button class="positive" type="submit" name="submit" value="{$submit_value}">{$submit_value}</button>
                            <button class="negative" type="submit" name="submit" value="Cancel">{$g_lang_button_cancel}</button>
                         </div><br /><br />

		</form>
                {literal}
                <script type="text/javascript">
		function check(select, send_dept, send_all)
		{
			if(send_dept.checked || select.options[select.selectedIndex].value != "0")
				send_all.disabled = true;
			else
			{
				send_all.disabled = false;
				for(var i = 1; i < select.options.length; i++)
					select.options[i].selected = false;
			}
		}

	</script>
                {/literal}