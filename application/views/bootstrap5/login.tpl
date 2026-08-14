<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$g_lang_label_login} - {$site_title|escape}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{$g_base_url}css/bootstrap5/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow">
                    <div class="text-center pt-4">
                        <img src="{$g_base_url}images/logo.gif" alt="{$site_title|escape}" style="max-height: 80px;">
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h4>{$site_title|escape}</h4>
                        </div>
                        {if $lastmessage ne ''}
                            <div class="alert alert-danger">{$lastmessage|escape}</div>
                        {/if}
                        <form action="index" method="post">
                            {$csrf_token_field}
                            <div class="mb-3">
                                <label for="username" class="form-label">{$g_lang_label_username}</label>
                                <input type="text" class="form-control" id="username" name="frmuser" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">{$g_lang_label_password}</label>
                                <input type="password" class="form-control" id="password" name="frmpass" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">{$g_lang_label_login}</button>
                        </form>
                        <div class="text-center mt-3">
                            {if $g_allow_password_reset eq 'True'}
                                <a href="forgot_password" class="text-decoration-none small">{$g_lang_forgotpassword}</a>
                            {/if}
                            {if $g_allow_signup eq 'True'}
                                {if $g_allow_password_reset eq 'True'}| {/if}
                                <a href="signup" class="text-decoration-none small">{$g_lang_signup}</a>
                            {/if}
                        </div>
                        {if $g_public_sharing eq 'True'}
                        <div class="text-center mt-2">
                            <a href="public" class="text-decoration-none small">{$g_lang_public_link}</a>
                        </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>