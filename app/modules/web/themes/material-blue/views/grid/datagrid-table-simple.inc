<?php
/**
 * @var DataGridTab $data
 * @var \SP\Modules\Web\Mvc\View\TemplateInterface $this
 * @var DataGridAction|DataGridActionSearch $action
 */

use SP\Modules\Web\Html\DataGrid\Action\DataGridAction;
use SP\Modules\Web\Html\DataGrid\Action\DataGridActionSearch;
use SP\Modules\Web\Html\DataGrid\DataGridTab;
use SP\Modules\Web\Mvc\View\Template;

use function SP\__;

if (!isset($data)) {
    $data = $_getvar('data');
}
?>

    <div class="sp-table-scroll" role="region" tabindex="0" aria-label="<?php echo __('Results'); ?>">
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
    </div>

    <!-- Pager -->
<?php include $data->getDataPagerTemplate(); ?>