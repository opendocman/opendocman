<?php use Aura\Html\Escaper as e; ?>
<div id="filetable_wrapper">
<form name="table" method="post" action="access_log.php">
    <table id="filetable" class="display" border="0" cellpadding="1" cellspacing="1">
    <thead>
        <tr>
            <?php if ($this->showCheckBox): ?>
                <th class="sorting_desc_disabled sorting_asc_disabled">
                    <input type="checkbox" id="checkall"/>
                </th>
            <?php endif; ?>
            <th class="sorting"><?= e::h(msg('label_file_name')) ?></th>
            <th><?= e::h(msg('label_fileid')) ?></th>
            <th><?= e::h(msg('label_username')) ?></th>
            <th class="sorting"><?= e::h(msg('label_action')) ?></th>
            <th class="sorting"><?= e::h(msg('label_date')) ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($this->accesslog_array as $item): ?>
        <tr>
            <td class="center"><a href="<?= e::a($item['details_link']) ?>"><?= e::h($item['realname']) ?></a></td>
            <td class="center" style="width: 50px;"><?= e::h($item['file_id']) ?></td>
            <td class="center"><?= e::h($item['user_name']) ?></td>
            <td class="center"><?= e::h($item['action']) ?></td>
            <td class="center"><?= e::h($item['timestamp']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
       <tr>
            <th class="sorting"><?= e::h(msg('label_file_name')) ?></th>
            <th><?= e::h(msg('label_fileid')) ?></th>
            <th><?= e::h(msg('label_username')) ?></th>
            <th class="sorting"><?= e::h(msg('label_action')) ?></th>
            <th class="sorting"><?= e::h(msg('label_date')) ?></th>
        </tr>
    </tfoot>
    <?php if ($this->form != '1'): ?>
</form>
    <?php endif; ?>
</table>
</div>
<br />