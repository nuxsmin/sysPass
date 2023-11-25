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
 * @var ThemeIconsInterface $icons
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\TemplateInterface;

?>
<div ID='debuginfo' class='round'>
    <h3>DEBUG INFO</h3>
    <ul>
        <li>RENDER -> <?php
            echo $_getvar('time'); ?> sec
        </li>
        <li>MEM -> Init: <?php
            echo $_getvar('memInit'); ?> KB -
            End: <?php
            echo $_getvar('memEnd'); ?> KB -
            Total: <?php
            echo $_getvar('memEnd') - $_getvar('memInit'); ?> KB
        </li>
        <li>SESSION:
            <pre><?php
                echo print_r($_SESSION, true) ?></pre>
        </li>
</div>
