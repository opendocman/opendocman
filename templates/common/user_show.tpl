<table border=0>
    <th>{$g_lang_userpage_user_info}</th>
        <tr><td>{$g_lang_userpage_id}</td><td>{$user->id|escape:'html'}</td></tr>
        <tr><td>{$g_lang_userpage_last_name}</td><td>{$last_name|escape:'html'}</td></tr>
        <tr><td>{$g_lang_userpage_first_name}</td><td>{$first_name|escape:'html'}</td></tr>
        <tr><td>{$g_lang_userpage_username}</td><td>{$user->username|escape:'html'}</td></tr>
        <tr><td>{$g_lang_userpage_department}</td><td>{$user->department|escape:'html'}</td></tr>
        <tr><td>{$g_lang_userpage_email}</td><td>{$user->email|escape:'html'}</td></tr>
        <tr><td>{$g_lang_userpage_phone_number}</td><td>{$user->phone|escape:'html'}</td></tr>
        <tr>
            <td>{$g_lang_userpage_admin}</td>
            <td>
                {if $isAdmin}
                    {$g_lang_userpage_yes}
                {else}
                    {$g_lang_userpage_no}
                {/if}
            </td>
        </tr>
        <tr>
            <td>{$g_lang_userpage_reviewer}</td>
            <td>
                {if $isReviewer}
                    {$g_lang_userpage_yes}
                {else}
                    {$g_lang_userpage_no}
                {/if}
            </td>
        </tr>
    <form action="admin.php" method="POST" enctype="multipart/form-data">
        <tr>
            <td colspan="4" align="center">
                <div class="buttons">
                    <button class="regular" type="Submit" name=""
                            value="Back">{$g_lang_userpage_back}</button>
                </div>
            </td>
        </tr>
    </form>
</table>