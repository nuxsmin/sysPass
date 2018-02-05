<?php if (isset($isImage)): ?>
    <img src="data:'<?php echo $fileData->getType(); ?>;base64, <?php echo $data; ?>" border="0"/>
    <div class="title"><?php echo $fileData->getName(); ?></div>
<?php else: ?>
    <pre><?php echo $data; ?></pre>
<?php endif; ?>