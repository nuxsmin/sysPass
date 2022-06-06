<?php
/**
 * @var FileData $fileData
 * @var ThemeIcons $icons
 * @var \SP\Domain\Config\In\ConfigDataInterface $configData
 * @var callable $_getvar
 * @var \SP\Mvc\View\TemplateInterface $this
 */

use SP\Core\UI\ThemeIcons;
use SP\DataModel\FileData;

$fileData = $_getvar('fileData');
?>

<?php if ($_getvar('isImage')): ?>
    <img src="data:'<?php echo $fileData->getType(); ?>;base64, <?php echo $_getvar('data'); ?>"
         border="0"/>
    <div class="title"><?php echo htmlspecialchars($fileData->getName(), ENT_QUOTES); ?></div>
<?php else: ?>
    <pre><?php echo $_getvar('data'); ?></pre>
<?php endif; ?>