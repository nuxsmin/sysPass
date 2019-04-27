<?php
/**
 * @var FileData   $fileData
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\FileData;
use SP\Mvc\View\Template;

$fileData = $_getvar('fileData');
?>

<?php if ($_getvar('isImage')): ?>
    <img src="data:'<?php echo $fileData->getType(); ?>;base64, <?php echo $_getvar('data'); ?>"
         border="0"/>
    <div class="title"><?php echo $fileData->getName(); ?></div>
<?php else: ?>
    <pre><?php echo $_getvar('data'); ?></pre>
<?php endif; ?>