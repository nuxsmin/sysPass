<?php
/**
 * @var \SP\DataModel\FileData $fileData
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var \SP\Config\ConfigData  $configData
 * @var callable               $_getvar
 * @var \SP\Mvc\View\Template  $this
 */

$fileData = $_getvar('fileData');
?>

<?php if ($_getvar('isImage')): ?>
    <img src="data:'<?php echo $fileData->getType(); ?>;base64, <?php echo $_getvar('data'); ?>" border="0"/>
    <div class="title"><?php echo $fileData->getName(); ?></div>
<?php else: ?>
    <pre><?php echo $_getvar('data'); ?></pre>
<?php endif; ?>