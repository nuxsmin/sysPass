<div id="fancyView" class="msgError">
    <?php if (count($errors) > 0): ?>
        <ul class="errors round">
            <?php foreach ($errors as $err): ?>
                <?php if (is_array($err)): ?>
                    <li class="err_<?php echo $err["type"]; ?>">
                        <strong><?php echo $err['description']; ?></strong>
                        <?php echo ($err['hint']) ? '<p class="hint">' . $err['hint'] . '</p>' : ''; ?>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>