<?php
/**
 * @var \SP\Html\DataGrid\DataGridTab                                                         $data
 * @var \SP\Mvc\View\Template                                                                 $this
 * @var \SP\Html\DataGrid\Action\DataGridAction|\SP\Html\DataGrid\Action\DataGridActionSearch $action
 */

if (!isset($data)) {
    $data = $_getvar('data');
}
?>

    <table class="mdl-data-table mdl-js-data-table table-responsive data-table">
        <thead>
        <tr>
            <?php foreach ($data->getHeader()->getHeaders() as $header): ?>
                <th class="mdl-data-table__cell--non-numeric"><?php echo $header; ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>

        <tbody id="data-rows-<?php echo $data->getId(); ?>">
        <?php include $data->getDataRowTemplate(); ?>
        </tbody>
    </table>

    <!-- Pager -->
<?php include $data->getDataPagerTemplate(); ?>