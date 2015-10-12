<div id="actions" align="center">

    <?php if ($showLogo): ?>
        <div id="logo">
            <img src="<?php echo $logo; ?>" alt="sysPass logo"/>
        </div>
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
</div>