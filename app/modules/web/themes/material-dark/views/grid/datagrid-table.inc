<?php
/**
 * @var DataGridTab                         $data
 * @var Template                            $this
 * @var DataGridAction|DataGridActionSearch $action
 * @var callable                            $_getvar
 */

use SP\Html\DataGrid\Action\DataGridAction;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\DataGridTab;
use SP\Mvc\View\Template;

if (!isset($data)) {
    $data = $_getvar('data');
}
?>
    <table class="mdl-data-table mdl-js-data-table mdl-data-table--selectable table-responsive data-table">
        <thead>
        <tr>
            <?php foreach ($data->getHeader()->getHeaders() as $header): ?>
                <th class="mdl-data-table__cell--non-numeric"><?php echo $header; ?></th>
            <?php endforeach; ?>

            <?php if ($data->getDataActionsCount() > 0): ?>
                <th class="mdl-data-table__cell--non-numeric">&nbsp;</th>
            <?php endif; ?>
        </tr>
        </thead>

        <tbody id="data-rows-<?php echo $data->getId(); ?>">
        <?php include $data->getDataRowTemplate(); ?>
        </tbody>
    </table>

    <!-- Pager -->
<?php include $data->getDataPagerTemplate(); ?>