<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Core\UI\ThemeIconsInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Mvc\View\TemplateInterface;

$header = $_getvar('header');
$pageData = $_getvar('pageData');
$pageSearch = $_getvar('pageSearch', []);
$pageInfo = $_getvar('pageInfo');
?>

<div id="box-popup">
    <h2 class="center"><?php
        echo (is_array($header) && !empty($header[0])) ? $header[0] : __('View Wiki'); ?></h2>

    <div id="wikiPage">
        <?php
        if (is_array($pageData) && !empty($pageData[0])): ?>
            <?php
            echo $pageData[0]; ?>
        <?php
        elseif (count($pageSearch) > 0): ?>
            <h1><?php
                echo __('Page not found'); ?></h1>
            <hr>
            <?php
            printf(__('Search results of \'%s\''), $_getvar('pageName')); ?>
            <ul>
                <?php
                foreach ($pageSearch as $result): ?>
                    <li><?php
                        printf(
                            '%s: <a href="%s" target="_blank">%s</a>',
                            __('Page'),
                            $_getvar('wikiUrlBase') . '/' . $result['id'],
                            $result['id']
                        ); ?></li>
                <?php
                endforeach; ?>
            </ul>
        <?php
        else: ?>
            <h1><?php
                echo __('Page not found'); ?></h1>
        <?php
        endif; ?>
    </div>
    <div id="wikiPageInfo">
        <?php
        if ($pageInfo): ?>
            <ul>
                <li><?php
                    printf(
                        '%s: <a href="%s" target="_blank">%s</a>',
                        __('Page'),
                        $_getvar('wikiUrlBase') . $pageInfo['name'],
                        $pageInfo['name']
                    ); ?></li>
                <li><?php
                    printf('%s: %s', __('Date'), $pageInfo['lastModified']); ?></li>
                <li><?php
                    printf('%s: %s', __('Author'), $pageInfo['author']); ?></li>
            </ul>
        <?php
        endif; ?>
    </div>
</div>
