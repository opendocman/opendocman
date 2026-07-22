<html>
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>{$g_lang_change_password} - {$site_title|escape}</title>
</head>
<body bgcolor="White" style="margin-left:10px;">
    <table cellspacing="0" cellpadding="0">
        <tr>
            <td align="left"><img src="images/logo.gif" alt="Site Logo" border=0></td>
        </tr>
    </table>
    <h2>{$g_lang_change_password}</h2>
    <p><b>{$g_lang_change_password_required_message}</b></p>
    {if $error ne ''}
        <p><font color="red">{$error|escape}</font></p>
    {/if}
    <form action="change_password" method="post">
        {$csrf_token_field}
        <table border="0" cellspacing="5" cellpadding="5">
            <tr>
                <td>{$g_lang_change_password_current_password}</td>
                <td><input type="password" name="current_password" size="15" required autofocus></td>
            </tr>
            <tr>
                <td>{$g_lang_change_password_new_password}</td>
                <td><input type="password" name="new_password" size="15" required></td>
            </tr>
            <tr>
                <td>{$g_lang_change_password_confirm_password}</td>
                <td><input type="password" name="confirm_password" size="15" required></td>
            </tr>
            <tr>
                <td colspan="2" align="center"><input type="submit" name="submit" value="{$g_lang_change_password_button}"></td>
            </tr>
        </table>
    </form>
</body>
</html>
