<div class="card">
    <div class="card-body">
        <h5 class="card-title">{$g_lang_userpage_user_info}</h5>
        <dl class="row mb-0">
            <dt class="col-sm-3">{$g_lang_userpage_id}</dt>
            <dd class="col-sm-9">{$user->id|escape:'html'}</dd>
            <dt class="col-sm-3">{$g_lang_userpage_last_name}</dt>
            <dd class="col-sm-9">{$last_name|escape:'html'}</dd>
            <dt class="col-sm-3">{$g_lang_userpage_first_name}</dt>
            <dd class="col-sm-9">{$first_name|escape:'html'}</dd>
            <dt class="col-sm-3">{$g_lang_userpage_username}</dt>
            <dd class="col-sm-9">{$user->username|escape:'html'}</dd>
            <dt class="col-sm-3">{$g_lang_userpage_department}</dt>
            <dd class="col-sm-9">{$user->department|escape:'html'}</dd>
            <dt class="col-sm-3">{$g_lang_userpage_email}</dt>
            <dd class="col-sm-9">{$user->email|escape:'html'}</dd>
            <dt class="col-sm-3">{$g_lang_userpage_phone_number}</dt>
            <dd class="col-sm-9">{$user->phone|escape:'html'}</dd>
            <dt class="col-sm-3">{$g_lang_userpage_admin}</dt>
            <dd class="col-sm-9">
                {if $isAdmin}
                    {$g_lang_userpage_yes}
                {else}
                    {$g_lang_userpage_no}
                {/if}
            </dd>
            <dt class="col-sm-3">{$g_lang_userpage_reviewer}</dt>
            <dd class="col-sm-9">
                {if $isReviewer}
                    {$g_lang_userpage_yes}
                {else}
                    {$g_lang_userpage_no}
                {/if}
            </dd>
        </dl>
        <form action="admin" method="POST" enctype="multipart/form-data">
            {$csrf_token_field}
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-secondary" type="button"
                        onclick="window.location.href='admin'">{$g_lang_userpage_back}</button>
            </div>
        </form>
    </div>
</div>
