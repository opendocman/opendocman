<!DOCTYPE html>
<html>
    <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <title>{$g_title} - {$page_title}</title>
{literal}
        <script type="text/javascript">
        <!--
        function popup(mylink, windowname)
        {
            if (! window.focus)return true;
            var href;
            if (typeof(mylink) == 'string')
                href=mylink;
            else
                href=mylink.href;
            window.open(href, windowname, 'width=300,height=500,scrollbars=yes');
            return false;
        }
        //-->
        </script>
{/literal}

{* Must Include This Section *}
{include file='../../templates/common/head_include.tpl'}

<link type="text/css" rel="stylesheet" href="{$g_base_url}/templates/default/css/default.css">
    </head>
    <body >
        <!-- ----------------begin_draw_menu----------------- -->
<!-- ----------------UID is {$uid} ----------------- -->
<table width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td align="left">
            <a href="{$g_base_url}/out.php">
                <img src="{$g_base_url}/images/logo.gif" title="{$site_title}" alt="{$site_title}" border="0">
            </a>
        </td>
        <td align="right" >
            <p>
                <div class="buttons">
                <a href="{$g_base_url}/in.php" class="regular"><img src="{$g_base_url}/images/import-2.png" alt="check in"/>{$g_lang_button_check_in}</a>
                <a href="{$g_base_url}/search.php" class="regular"><img src="{$g_base_url}/images/find-new-users.png" alt="search"/>{$g_lang_search}</a>
                <a href="{$g_base_url}/add.php" class="regular"><img src="{$g_base_url}/images/plus.png" alt="add file"/>{$g_lang_button_add_document}</a>
            {if $isadmin eq 'yes'}
                <a href="{$g_base_url}/admin.php" class="positive"><img src="{$g_base_url}/images/control.png" alt="admin"/>{$g_lang_label_admin}</a>
            {/if}
                <a href="{$g_base_url}/logout.php" class="negative">{$g_lang_logout}</a>
            </div>
            </p>
        </td>
    </tr>
</table>
<!-- ----------------end_draw_menu----------------- -->
<link rel="stylesheet" type="text/css" href="{$g_base_url}/linkcontrol.css">
<table width="100%" border="0" cellspacing="0" cellpadding="5">
<tr>
{if $userName}
<td bgcolor="#0000A0" align="left" valign="middle" width="10">
<span class="statusbar">{$userName}</span></td>
{/if}

<td bgcolor="#0000A0" align="left" valign="middle" width="10">
<a class="statusbar" href="{$g_base_url}/out.php" style="text-decoration:none">{$g_lang_home}</a></td>
<td bgcolor="#0000A0" align="left" valign="middle" width="10">
<a class="statusbar" href="{$g_base_url}/profile.php" style="text-decoration:none">{$g_lang_preferences}</a></td>
<td bgcolor="#0000A0" align="left" valign="middle" width="10">
<a class="statusbar" href="{$g_base_url}/help.html" onClick="return popup(this, 'Help')" style="text-decoration:none">{$g_lang_help}</a></td>
<td bgcolor="#0000A0" align="middle" valign="middle" width="0"><font size="3" face="Arial" color="White">|</font></td>
<td bgcolor="#0000A0" align="left" valign="middle">{$breadCrumb}</td>
<td bgcolor="#0000A0" align="right" valign="middle">
    <b><font size="-2" face="Arial" color="White">{$g_lang_message_last_message}: {$lastmessage}</font></b>
</td>
</tr>
</table>
<div id="content">
{if $lastmessage ne ''}
    <div id="last_message">{$lastmessage}</div>
{/if}