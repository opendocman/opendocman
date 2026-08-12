<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$g_lang_public_page_title} - {$site_title|escape}</title>
    {include file="head_include.tpl"}
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="public">{$site_title|escape} - {$g_lang_public_page_h1}</a>
        </div>
    </nav>
    <main class="container-fluid py-3">
        <div class="mt-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">{$g_lang_public_page_h1}</h3>
                </div>
                <div class="card-body">
                    {if $public_files|@count > 0}
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{$g_lang_label_filename}</th>
                                    <th>{$g_lang_label_description}</th>
                                    <th>{$g_lang_category}</th>
                                    <th>{$g_lang_date}</th>
                                    <th>{$g_lang_public_download}</th>
                                </tr>
                            </thead>
                            <tbody>
                            {foreach from=$public_files item=file}
                                <tr>
                                    <td>{$file.realname|escape}</td>
                                    <td>{$file.description|escape}</td>
                                    <td>{$file.category_name|escape}</td>
                                    <td>{$file.created|date_format:"%Y-%m-%d"}</td>
                                    <td>
                                        <a href="public?submit=download&amp;id={$file.id}" class="btn btn-sm btn-primary">
                                            {$g_lang_public_download}
                                        </a>
                                    </td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                    {else}
                    <p class="text-muted">No public files available.</p>
                    {/if}
                </div>
            </div>
        </div>
    </main>
    {include file="footer.tpl"}
</body>
</html>