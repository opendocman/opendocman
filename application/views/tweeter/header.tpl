<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>{$g_title} - {$page_title|escape:'html'}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->

    <link href="{$g_base_url}css/tweeter/bootstrap.css" rel="stylesheet">
    <link href="{$g_base_url}css/tweeter/tweeter.css" rel="stylesheet">
    <style type="text/css">
        {literal}
      body {
        padding-top: 60px;
        padding-bottom: 40px;
      }
      {/literal}
    </style>
    <link href="{$g_base_url}css/tweeter/bootstrap-responsive.css" rel="stylesheet">

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
    <script src={$g_base_url}"js/tweeter/html5.js"></script>
    <![endif]-->

    <!-- Le fav and touch icons -->
    <link rel="shortcut icon" href="{$g_base_url}images/tweeter/favicon.ico">

    {* Must Include This Section *}
    {include file='../common/head_include.tpl'}
    
  </head>

  <body>

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">

          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="{$g_base_url|escape:'html'}out">{$g_title|escape:'html'}</a>
          <div class="nav-collapse collapse">
            <ul class="nav">

              <li class="active"><a href="{$g_base_url|escape:'html'}out">{$g_lang_home}</a></li>
              {if $can_checkin || $isadmin eq 'yes'}
                <li><a href="{$g_base_url}in">{$g_lang_button_check_in}</a></li>
              {/if}
              <li><a href="{$g_base_url|escape:'html'}search">{$g_lang_search}</a></li>
              {if $can_add || $isadmin eq 'yes'} 
                <li><a href="{$g_base_url|escape:'html'}add">{$g_lang_button_add_document}</a></li>
              {/if}
              {if $isadmin eq 'yes'}
              <li>
                 
                    <a href="{$g_base_url|escape:'html'}admin">{$g_lang_label_admin}</a>
              </li>
              {/if}
              <li><a href="{$g_base_url|escape:'html'}logout">{$g_lang_logout}</a></li>
            </ul>          
              <p class="navbar-text pull-right">
{$g_lang_label_logged_in_as}
<a href="{$base_url|escape:'html'}profile">{$userName}</a>
</p>
          </div><!--/.nav-collapse -->
        </div>
      </div>

    </div>
{if $g_demo eq 'True'}
    <h1>Demo resets once per hour</h1>
{/if}
      <div class="container">
        <div class="row">
            <div class="span4">
                You are here: {$breadCrumb}
            </div>
        </div>
        <p></p>
        {if $lastmessage ne ''}
            <div id="last_message">{$lastmessage|escape:'html'}</div>
        {/if}