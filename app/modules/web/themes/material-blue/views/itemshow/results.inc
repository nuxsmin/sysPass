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
