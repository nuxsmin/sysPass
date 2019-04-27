<?php
/**
 * @var ThemeIcons $icons
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;

?>
<div ID='debuginfo' class='round'>
    <h3>DEBUG INFO</h3>
    <ul>
        <li>RENDER -> <?php echo $_getvar('time'); ?> sec</li>
        <li>MEM -> Init: <?php echo $_getvar('memInit'); ?> KB -
            End: <?php echo $_getvar('memEnd'); ?> KB -
            Total: <?php echo $_getvar('memEnd') - $_getvar('memInit'); ?> KB
        </li>
        <li>SESSION:
            <pre><?php echo print_r($_SESSION, true) ?></pre>
        </li>
</div>
