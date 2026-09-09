<div class="card">
    <div class="card-body">
        <h5 class="card-title">{$g_lang_label_user_defined_fields}</h5>
        <div class="d-flex gap-2 mb-3">
            <a class="btn btn-primary" href="udf?submit=add">{$g_lang_label_add}&nbsp;{$g_lang_label_user_defined_field}</a>
        </div>
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>{$g_lang_label_display}</th>
                    <th>{$g_lang_type}</th>
                    <th>{$g_lang_label_name}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$udfs item=item}
                <tr>
                    <td>{$item.display_name|escape:'html'}</td>
                    <td>{$item.field_type|escape:'html'}</td>
                    <td><code>{$item.table_name|escape:'html'}</code></td>
                    <td>
                        <form action="udf" method="POST" class="d-inline">
                            {$csrf_token_field}
                            <input type="hidden" name="submit" value="delete">
                            <input type="hidden" name="item" value="{$item.table_name|escape:'html'}">
                            <button type="submit" class="btn btn-danger btn-sm">{$g_lang_label_delete}</button>
                        </form>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>
