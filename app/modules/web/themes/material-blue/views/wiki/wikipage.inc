<?php
/**
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;

$header = $_getvar('header');
$pageData = $_getvar('pageData');
$pageSearch = $_getvar('pageSearch', []);
$pageInfo = $_getvar('pageInfo');
?>

<div id="box-popup">
    <h2 class="center"><?php echo (is_array($header) && !empty($header[0])) ? $header[0] : __('View Wiki'); ?></h2>

    <div id="wikiPage">
        <?php if (is_array($pageData) && !empty($pageData[0])): ?>
            <?php echo $pageData[0]; ?>
        <?php elseif (count($pageSearch) > 0): ?>
            <h1><?php echo __('Page not found'); ?></h1>
            <hr>
            <?php printf(__('Search results of \'%s\''), $_getvar('pageName')); ?>
            <ul>
                <?php foreach ($pageSearch as $result): ?>
                    <li><?php printf('%s: <a href="%s" target="_blank">%s</a>', __('Page'), $_getvar('wikiUrlBase') . '/' . $result['id'], $result['id']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <h1><?php echo __('Page not found'); ?></h1>
        <?php endif; ?>
    </div>
    <div id="wikiPageInfo">
        <?php if ($pageInfo): ?>
            <ul>
                <li><?php printf('%s: <a href="%s" target="_blank">%s</a>', __('Page'), $_getvar('wikiUrlBase') . $pageInfo['name'], $pageInfo['name']); ?></li>
                <li><?php printf('%s: %s', __('Date'), $pageInfo['lastModified']); ?></li>
                <li><?php printf('%s: %s', __('Author'), $pageInfo['author']); ?></li>
            </ul>
        <?php endif; ?>
    </div>
</div>
