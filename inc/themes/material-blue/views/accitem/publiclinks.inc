<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>
    <table class="fancydata">
        <tbody>
        <tr>
            <td class="descField"><?php echo _('Cuenta'); ?></td>
            <td class="valField"><?php echo $link->publicLink_account; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Fecha Creación'); ?></td>
            <td class="valField"><?php echo $link->publicLink_dateAdd; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Fecha Caducidad'); ?></td>
            <td class="valField"><?php echo $link->publicLink_dateExpire; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Usuario'); ?></td>
            <td class="valField"><?php echo $link->publicLink_user; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Notificar'); ?></td>
            <td class="valField"><?php echo $link->publicLink_notify; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Visitas'); ?></td>
            <td class="valField"><?php echo $link->publicLink_views; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Uso'); ?></td>
            <td class="valField">
                <?php foreach ($link->publicLink_useInfo as $useInfo): ?>
                    <div class="link-use-info"><?php echo $useInfo['who'], ' - ' , date('Y-m-d H:i', $useInfo['time']); ?></div>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Hash'); ?></td>
            <td class="valField"><?php echo $link->publicLink_hash; ?></td>
        </tr>
        </tbody>
    </table>
</div>