<div ID='debuginfo' class='round'>
    <h3>DEBUG INFO</h3>
    <ul>
        <li>RENDER -> <?php echo $time; ?> sec</li>
        <li>MEM -> Init: <?php echo $memInit; ?> KB - End: <?php echo $memEnd; ?> KB -
            Total: <?php echo $memEnd - $memInit; ?> KB
        </li>
        <li>SESSION:
            <pre><?php echo print_r($_SESSION, true) ?></pre>
        </li>
        <li>CONFIG FILE:
            <pre><?php echo print_r(\SP\Core\Session::getConfig(), true); ?></pre>
        </li>
</div>

<?php //foreach ($debugTxt as $out): ?>
<!--    --><?php //echo $out; ?>
<?php //endforeach; ?>