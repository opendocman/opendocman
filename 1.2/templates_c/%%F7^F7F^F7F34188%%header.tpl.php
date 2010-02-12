<?php /* Smarty version 2.6.16, created on 2010-02-07 23:27:07
         compiled from header.tpl */ ?>
<!---------------------------Start drawing header-----------------------------!>
<html>
    <head>
        <title><?php echo $this->_tpl_vars['g_title']; ?>
 - <?php echo $this->_tpl_vars['page_title']; ?>
</title>
<?php echo '
        <script type="text/javascript">
        <!--
        function popup(mylink, windowname)
        {
            if (! window.focus)return true;
            var href;
            if (typeof(mylink) == \'string\')
                href=mylink;
            else
                href=mylink.href;
            window.open(href, windowname, \'width=300,height=500,scrollbars=yes\');
            return false;
        }
        //-->
        </script>
'; ?>

    </head>
    <body bgcolor="white">
    <!----------------------------End drawing header-----------------------------!>
