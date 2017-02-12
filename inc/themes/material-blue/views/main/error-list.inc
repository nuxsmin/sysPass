<?php
/**
 * @var $icons \SP\Core\UI\ThemeIconsBase
 */
?>
<?php if (count($errors) > 0): ?>
    <ul class="errors round">
        <?php foreach ($errors as $err): ?>
            <?php if (is_array($err)): ?>
                <?php if ($err['type'] === \SP\Core\Exceptions\SPException::SP_CRITICAL): ?>
                    <li class="msg-critical">
                        <i class="material-icons <?php echo $icons->getIconCritical()->getClass(); ?>"><?php echo $icons->getIconCritical()->getIcon(); ?></i>
                        <strong><?php echo $err['description']; ?></strong>
                        <?php if ($err['hint']): ?>
                            <p class="hint">
                                <?php echo $err['hint']; ?>
                            </p>
                        <?php endif; ?>
                    </li>
                <?php elseif ($err['type'] === \SP\Core\Exceptions\SPException::SP_WARNING): ?>
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