<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$g_lang_change_password} - {$site_title|escape}</title>
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
                            <h4>{$g_lang_change_password}</h4>
                            <p class="text-muted small">{$g_lang_change_password_required_message}</p>
                        </div>
                        {if $error ne ''}
                            <div class="alert alert-danger">{$error|escape}</div>
                        {/if}
                        <form action="change_password" method="post">
                            {$csrf_token_field}
                            <div class="mb-3">
                                <label for="current_password" class="form-label">{$g_lang_change_password_current_password}</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">{$g_lang_change_password_new_password}</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">{$g_lang_change_password_confirm_password}</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="submit" class="btn btn-primary w-100">{$g_lang_change_password_button}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
