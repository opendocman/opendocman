        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <title>{$g_title}</title>
        </head>

        <body bgcolor="White" style="margin-left:10px;">
        <table cellspacing="0" cellpadding="0">
        <tr>
        <td align="left"><img src="images/logo.gif" alt="Site Logo" border=0></td>
        </tr>
        </table>

        <table border="0" cellspacing="5" cellpadding="5">
        <tr>
        <td valign="top">
        <table border="0" cellspacing="5" cellpadding="5">
        <form action="index.php" method="post">
            {if $redirection}
                <input type="hidden" name="redirection" value="{$redirection|escape}">
            {/if}
            
         <tr>
        <td>{$g_lang_username}</td>
        <td><input type="Text" name="frmuser" size="15"></td>
        </tr>
        <tr>
        <td>{$g_lang_password}</td>
        <td><input type="password" name="frmpass" size="15">
            {if $g_allow_password_reset eq 'True'}
                <a href="{$g_base_url}/forgot_password.php">{$g_lang_forgotpassword}</a>
             {/if}
                     </td>
        </tr>
        <tr>
        <td colspan="2" align="center"><input type="submit" name="login" value="{$g_lang_enter}"></td>
        </tr>
                </tr>
                {if $g_demo eq 'True'}
        Regular User: <br />Username:demo Password:demo<br />
        Admin User: <br />Username:admin Password:admin<br />
        {/if}
        {if $g_allow_signup eq 'True'}
                <tr>
            <td colspan="2"><a href="{$g_base_url}/signup.php">{$g_lang_signup}</a>
        </tr>
        {/if}
        
        </form>
        </table>
        </td>
        <td valign="top">
        {$g_lang_welcome}
        <p>
        {$g_lang_welcome2}
        </td>
        <td width="20%">
        &nbsp;
    </td>
        </tr>
        </table>
