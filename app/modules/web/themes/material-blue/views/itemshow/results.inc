<?php
/**
 * @var ThemeIcons $icons
 * @var \SP\Domain\Config\In\ConfigDataInterface $configData
 * @var callable $_getvar
 * @var \SP\Mvc\View\TemplateInterface $this
 */

use SP\Core\UI\ThemeIcons;

?>
<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>
    <table class="popup-data">
        <tbody>
        <tr id="ldap-results">
            <td class="valField">
                <div class="lowres-title"><?php echo $_getvar('header'); ?></div>
                <div class="list-wrap"></div>
            </td>
        </tr>
        </tbody>
    </table>
</div>
