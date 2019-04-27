<?php
/**
 * @var SP\Html\DataGrid\DataGridTab $data
 * @var ThemeIcons                   $icons
 * @var ConfigData                   $configData
 * @var callable                     $_getvar
 * @var Template                     $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;

$data = $_getvar('data');
?>

    <!-- Rows -->
<?php if ($data->getData()->getDataCount() > 0):
    foreach ($data->getData()->getData() as $dataIndex => $dataItem):
        if ($dataIndex === 'count'): continue; endif; ?>

        <tr data-item-id="<?php echo $dataItem->{$data->getData()->getDataRowSourceId()}; ?>">
            <?php foreach ($data->getData()->getDataRowSources() as $rowSrc): ?>
                <?php $value = $rowSrc['isMethod'] === true && method_exists($dataItem, $rowSrc['name']) ? $dataItem->{$rowSrc['name']}() : $dataItem->{$rowSrc['name']}; ?>
                <?php $value = $rowSrc['filter'] !== null && $rowSrc['filter'] ? $rowSrc['filter']($value) : $value; ?>
                <td class="cell-data"><?php echo $value !== '' ? $value : '&nbsp;'; // Fix height ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>