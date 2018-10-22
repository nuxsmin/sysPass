<?php
/**
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var callable               $_getvar
 * @var \SP\Mvc\View\Template  $this
 */
?>
<div ID='debuginfo' class='round'>
    <h3>DEBUG INFO</h3>
    <ul>
        <li>RENDER -> <?php echo $_getvar('time'); ?> sec</li>
        <li>MEM -> Init: <?php echo $_getvar('memInit'); ?> KB - End: <?php echo $_getvar('memEnd'); ?> KB -
            Total: <?php echo $_getvar('memEnd') - $_getvar('memInit'); ?> KB
        </li>
        <li>SESSION:
            <pre><?php echo print_r($_SESSION, true) ?></pre>
        </li>
</div>
