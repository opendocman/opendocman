<!-- ----------------begin_draw_menu----------------- -->
<!-- ----------------UID is {$uid} ----------------- -->
<table width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td align="left">
            <a href="{$g_base_url}/out.php">
                <img src="{$g_base_url}/images/logo.gif" title="{$site_title}" alt="{$site_title}" border="0">
            </a>
        </td>
        <td align="right" >
            <p>
                <div class="buttons">
                <a href="{$g_base_url}/in.php" class="regular"><img src="{$g_base_url}/images/import-2.png" alt="check in"/>{$g_lang_button_check_in}</a>
                <a href="{$g_base_url}/search.php" class="regular"><img src="{$g_base_url}/images/find-new-users.png" alt="search"/>{$g_lang_search}</a>
                <a href="{$g_base_url}/add.php" class="regular"><img src="{$g_base_url}/images/plus.png" alt="add file"/>{$g_lang_button_add_document}</a>
            {if $isadmin eq 'yes'}
                <a href="{$g_base_url}/admin.php" class="positive"><img src="{$g_base_url}/images/control.png" alt="admin"/>{$g_lang_label_admin}</a>
            {/if}
                <a href="{$g_base_url}/logout.php" class="negative">{$g_lang_logout}</a>
            </div>
            </p>
        </td>
    </tr>
</table>
<!-- ----------------end_draw_menu----------------- -->