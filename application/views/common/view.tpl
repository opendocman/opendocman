<p>
<form action="view" method="post">
    {$csrf_token_field}
    <input type="hidden" name="id" value="{$file_id|escape:'html'}">
    <input type="submit" name="submit" value="Click here"> to begin downloading the selected document to your local workstation.
</form>
Once the document has completed downloading, you may <a href="out">continue browsing</a>.