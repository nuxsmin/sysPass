<?php
/**
 * @var callable $_getvar
 * @var Template $this
 */

use SP\Core\Exceptions\SPException;
use SP\Mvc\View\Template;

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