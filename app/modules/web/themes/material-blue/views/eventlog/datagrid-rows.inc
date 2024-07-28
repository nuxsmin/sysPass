<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @var SP\Html\DataGrid\DataGridTab $data
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\TemplateInterface;

$data = $_getvar('data');
?>

<!-- Rows -->
<?php
if ($data->getData()->getDataCount() > 0):
    foreach ($data->getData()->getData() as $dataIndex => $dataItem):
        if ($dataIndex === 'count'): continue; endif; ?>

        <tr data-item-id="<?php
        echo $dataItem->{$data->getData()->getDataRowSourceId()}; ?>">
            <?php
            foreach ($data->getData()->getDataRowSources() as $rowSrc): ?>
                <?php
                $value = $rowSrc['isMethod'] === true &&
                         method_exists($dataItem, $rowSrc['name']) ? $dataItem->{$rowSrc['name']}(
                ) : $dataItem->{$rowSrc['name']}; ?>
                <?php
                $value = isset($rowSrc['filter']) ? $rowSrc['filter']($value) : $value; ?>
                <td class="cell-data"><?php
                    echo $value !== '' ? $value : '&nbsp;'; // Fix height ?></td>
            <?php
            endforeach; ?>
        </tr>
    <?php
    endforeach; ?>
<?php
endif; ?>
