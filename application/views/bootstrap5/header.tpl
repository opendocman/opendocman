<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$page_title|escape} - {$site_title|escape}</title>
    {include file="head_include.tpl"}
</head>
<body>
    {if $g_lang_username != ''}
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="out">{$site_title|escape}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="out">{$g_lang_out_page_file_listing}</a></li>
                    {if $can_checkin eq '1'}<li class="nav-item"><a class="nav-link" href="check-out">{$g_lang_check_out_page_check_out}</a></li>{/if}
                    <li class="nav-item"><a class="nav-link" href="search">{$g_lang_search}</a></li>
                    {if $can_add eq '1'}<li class="nav-item"><a class="nav-link" href="add">{$g_lang_add_page_add_document}</a></li>{/if}
                    {if $isadmin eq 'yes'}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">{$g_lang_label_admin}</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="admin">{$g_lang_label_admin}</a></li>
                            <li><a class="dropdown-item" href="settings">{$g_lang_label_settings}</a></li>
                            <li><a class="dropdown-item" href="access_log">{$g_lang_access_log_page_access_log}</a></li>
                        </ul>
                    </li>
                    {/if}
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="profile">{$userName|escape}</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout">{$g_lang_label_logout}</a></li>
                </ul>
            </div>
        </div>
    </nav>
    {/if}
    {if $breadCrumb ne ''}
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 py-2 px-3 bg-light">{$breadCrumb}</ol>
    </nav>
    {/if}
    {if $lastmessage ne ''}
    <div class="container mt-2">
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {$lastmessage|escape}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    {/if}
    <main class="container-fluid py-3">
