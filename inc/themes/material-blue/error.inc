<div id="actions" align="center">

    <?php if ($showLogo): ?>
        <div id="logo"></div>
    <?php endif; ?>

    <?php if (count($errors) > 0): ?>
        <ul class="errors round">
            <?php foreach ($errors as $err): ?>
                <?php if (is_array($err)): ?>
                    <li class="err_<?php echo \SP\Core\SPException::getExceptionTypeName($err["type"]); ?>">
                        <strong><?php echo $err['description']; ?></strong>
                        <?php echo ($err['hint']) ? '<p class="hint">' . $err['hint'] . '</p>' : ''; ?>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <div class="buttons">
        <button class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent"
                onclick="window.location.href='index.php'">
            <i class="material-icons" title="<?php echo _('Volver'); ?>"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            <?php echo _('Volver'); ?>
        </button>
    </div>
</div>
