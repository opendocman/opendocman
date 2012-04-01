<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>{$g_title} - {$page_title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->

    <link href="{$g_base_url}/templates/tweeter/css/bootstrap.css" rel="stylesheet">
    <link href="{$g_base_url}/templates/tweeter/css/tweeter.css" rel="stylesheet">
    <style type="text/css">
        {literal}
      body {
        padding-top: 60px;
        padding-bottom: 40px;
      }
      {/literal}
    </style>
    <link href="{$g_base_url}/templates/tweeter/css/bootstrap-responsive.css" rel="stylesheet">

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="{$g_base_url}/templates/tweeter/js/html5.js"></script>
    <![endif]-->

    <!-- Le fav and touch icons -->
    <link rel="shortcut icon" href="{$g_base_url}/templates/tweeter/images/favicon.ico">

    <link rel="apple-touch-icon" href="{$g_base_url}/templates/tweeter/images/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="72x72" href="{$g_base_url}/templates/tweeter/images/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="114x114" href="{$g_base_url}/templates/tweeter/images/apple-touch-icon-114x114.png">
    
    
    {* Must Include This Section *}
    {include file='../../templates/common/head_include.tpl'}
    
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
          <a class="brand" href="{$g_base_url}/out.php">{$g_title}</a>
          <div class="nav-collapse">
            <ul class="nav">

              <li class="active"><a href="{$g_base_url}/out.php">Home</a></li>
              <li><a href="{$g_base_url}/in.php">{$g_lang_button_check_in}</a></li>
              <li><a href="{$g_base_url}/search.php">{$g_lang_search}</a></li>
              <li><a href="{$g_base_url}/add.php">{$g_lang_button_add_document}</a></li>
              {if $isadmin eq 'yes'}
              <li>
                 
                    <a href="{$g_base_url}/admin.php">{$g_lang_label_admin}</a>
              </li>
              {/if}
              <li><a href="{$g_base_url}/logout.php">{$g_lang_logout}</a></li>
            </ul>
              <p class="navbar-text pull-right">
Logged in as
<a href="{$base_url}/profile.php">{$userName}</a>
</p>
          </div><!--/.nav-collapse -->
        </div>
      </div>

    </div>
    <div class="container">
        <div class="row">
            <div class="span4">
                You are here: {$breadCrumb}
            </div>
        </div>
        <p></p>
        {if $lastmessage ne ''}
            <div id="last_message">{$lastmessage}</div>
        {/if}