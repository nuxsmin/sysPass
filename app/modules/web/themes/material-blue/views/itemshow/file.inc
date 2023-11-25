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
 * @var FileData $fileData
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Core\UI\ThemeIconsInterface;
use SP\DataModel\FileData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Mvc\View\TemplateInterface;

$fileData = $_getvar('fileData');
?>

<?php
if ($_getvar('isImage')): ?>
    <img src="data:'<?php
    echo $fileData->getType(); ?>;base64, <?php
    echo $_getvar('data'); ?>"
         border="0"/>
    <div class="title"><?php
        echo htmlspecialchars($fileData->getName(), ENT_QUOTES); ?></div>
<?php
else: ?>
    <pre><?php
        echo $_getvar('data'); ?></pre>
<?php
endif; ?>
