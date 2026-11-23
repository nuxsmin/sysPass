<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Domain\Core\Exceptions\SPException;
use SP\Modules\Web\Mvc\View\TemplateInterface;

?>
<?php if (count($_getvar('errors')) > 0): ?>
    <ul class="errors round">
        <?php foreach ($_getvar('errors') as $err): ?>
            <?php if (is_array($err)): ?>
                <?php if ($err['type'] === SPException::CRITICAL): ?>
                    <li class="msg-critical">
                        <i class="material-icons <?php echo $icons->getIconCritical()->getClass(); ?>"><?php echo $icons->getIconCritical()->getIcon(); ?></i>
                        <strong><?php echo $err['description']; ?></strong>
                        <?php if ($err['hint']): ?>
                            <p class="hint">
                                <?php echo $err['hint']; ?>
                            </p>
                        <?php endif; ?>
                    </li>
                <?php elseif ($err['type'] === SPException::WARNING): ?>
                    <li class="msg-warning">
                        <i class="material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></i>
                        <strong><?php echo $err['description']; ?></strong>
                        <?php if ($err['hint']): ?>
                            <p class="hint">
                                <?php echo $err['hint']; ?>
                            </p>
                        <?php endif; ?>
                    </li>
                <?php else: ?>
                    <li class="msg-ok">
                        <strong><?php echo $err['description']; ?></strong>
                        <?php if ($err['hint']): ?>
                            <i class="material-icons <?php echo $icons->getIconInfo()->getClass(); ?>"><?php echo $icons->getIconInfo()->getIcon(); ?></i>
                            <p class="hint">
                                <?php echo $err['hint']; ?>
                            </p>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
