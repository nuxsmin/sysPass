<!-- Start Tabs-->
<div id="tabs">
    <ul>
        <?php foreach($tabs as $index => $tab): ?>
            <li>
                <a href="#tabs-<?php echo $index; ?>" title="<?php echo $tab['title']; ?>"><?php echo $tab['title']; ?></a>
            </li>
        <?php endforeach; ?>
    </ul>