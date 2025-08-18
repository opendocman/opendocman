{* Always include this file inside the <head></head> of your header.tpl file *}
<meta name="csrf-token" content="{$csrf_token_value}" />
<link rel="stylesheet" type="text/css" href="{$g_base_url}css/common/system.css" />
<link type="text/css" rel="stylesheet" href="{$g_base_url}css/DataTables/demo_table.css" />

<link rel="stylesheet" type="text/css" href="{$g_base_url}css/common/multiSelect112/jquery-ui-1.8.18.custom.css" />
<link rel="stylesheet" type="text/css" href="{$g_base_url}css/common/multiSelect112/smoothness/jquery-ui-1.8.18.custom.css" />

<script type="text/javascript" src="{$g_base_url}js/jquery.min.js"></script>
<script type="text/javascript" src="{$g_base_url}js/jquery-compatibility.js"></script>
<script type="text/javascript" src="{$g_base_url}js/DataTables/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="{$g_base_url}js/common/multiSelect112/jquery-ui-1.8.18.custom.min.js"></script>
<script type="text/javascript" src="{$g_base_url}js/jquery.validate.min.js"></script>
<script type="text/javascript" src="{$g_base_url}js/additional-methods.min.js"></script>

<link rel="stylesheet" type="text/css" href="{$g_base_url}css/common/multiSelect112/jquery.multiselect.css" />
<link rel="stylesheet" type="text/css" href="{$g_base_url}css/common/multiSelect112/jquery.multiselect.filter.css" />
<script type="text/javascript" src="{$g_base_url}js/common/multiSelect112/jquery.multiselect.js"></script>
<script type="text/javascript" src="{$g_base_url}js/common/multiSelect112/jquery.multiselect.filter.js"></script>

<script type="text/javascript" src="{$g_base_url}js/default.js"></script>
<script type="text/javascript" src="{$g_base_url}js/csrf-helper.js"></script>
<script>
    // CSRF token for JavaScript access
    window.csrf_token = '{$csrf_token_value}';
    window.csrf_field_name = '{$csrf_field_name}';
    
    // Here are the translations for the multiselect area of this page
    var langUncheckAll = '{$g_lang_editpage_uncheck_all}';
    var langCheckAll = '{$g_lang_editpage_check_all}';
    var langOf = '{$g_lang_editpage_of}';
    var langSelected = '{$g_lang_editpage_selected}';
    var langLanguage = '{$g_language}';
    var langNoneSelected = '{$g_lang_editpage_none_selected}';
</script>
<!--[if lt IE 9]>
<script type="text/javascript" src={$g_base_url}"js/common/buttonfix.js"></script>
<![endif]-->