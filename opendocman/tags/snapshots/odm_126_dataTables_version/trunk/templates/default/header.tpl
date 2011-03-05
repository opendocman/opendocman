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
<!--[if IE]>
    <script type="text/javascript" src="{$g_base_url}/templates/default/js/buttonfix.js"></script>
<![endif]-->

{* Must Include This Section *}
{include file='../../templates/common/head_include.tpl'}

<link type="text/css" rel="stylesheet" href="{$g_base_url}/templates/default/css/default.css">
    </head>
    <body >