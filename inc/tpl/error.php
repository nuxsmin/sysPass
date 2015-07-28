<?php
/** 
* sysPass
* 
* @author nuxsmin
* @link http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
*
*/

?>

<div id="actions" align="center">

    <?php if (isset($data['showlogo'])): ?>
        <div id="logo">
            <img src="<?php echo SP_Init::$WEBROOT; ?>/imgs/logo_full.svg" alt="sysPass logo"/>
        </div>
    <?php endif; ?>

    <?php
    $errors = $data['errors'];

    if (count($errors) > 0) {
        echo '<ul class="errors round">';

        foreach ($errors as $err) {
            if (is_array($err)) {
                echo '<li class="err_' . $err["type"] . '">';
                echo '<strong>' . $err['description'] . '</strong>';
                echo ($err['hint']) ? '<p class="hint">' . $err['hint'] . '</p>' : '';
                echo '</li>';
            }
        }
        echo '</ul>';
    }
    ?>
</div>