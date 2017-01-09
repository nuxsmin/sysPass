<div id="box-popup">
    <h2 class="center"><?php echo (is_array($header) && !empty($header[0])) ? $header[0] : _('Ver Wiki'); ?></h2>

    <div id="wikiPage">
        <?php if (is_array($pageData) && !empty($pageData[0])): ?>
            <?php echo $pageData[0]; ?>
        <?php elseif (is_array($pageSearch) && count($pageSearch) > 0): ?>
            <h1><?php echo _('Página no encontrada'); ?></h1>
            <hr>
            <?php printf(_('Resultados de búsqueda de \'%s\''), $pageName); ?>
            <ul>
                <?php foreach ($pageSearch as $result): ?>
                    <li><?php printf('%s: <a href="%s" target="_blank">%s</a>', _('Página'), $wikiUrlBase . '/' . $result['id'], $result['id']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <h1><?php echo _('Página no encontrada'); ?></h1>
        <?php endif; ?>
    </div>
    <div id="wikiPageInfo">
        <?php if (is_array($pageInfo)): ?>
            <ul>
                <li><?php printf('%s: <a href="%s" target="_blank">%s</a>', _('Página'), $wikiUrlBase . '/' . $pageInfo['name'], $pageInfo['name']); ?></li>
                <li><?php printf('%s: %s', _('Fecha'), $pageInfo['lastModified']); ?></li>
                <li><?php printf('%s: %s', _('Autor'), $pageInfo['author']); ?></li>
            </ul>
        <?php endif; ?>
    </div>
</div>
<script>
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>