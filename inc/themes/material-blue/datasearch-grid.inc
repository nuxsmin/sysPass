<?php
/**
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $account SP\Controller\AccountsSearchData
 * @var $this    SP\Core\Template
 */
?>
<div id="resBuscar">
    <?php if ($data->getData()->getDataCount() === 0): ?>
        <div class="noRes round"><?php echo _('No se encontraron registros'); ?></div>
    <?php else: ?>
        <!-- Data Header-->
        <?php include $data->getDataHeaderTemplate(); ?>

        <?php if (\SP\Controller\AccountsSearchData::$topNavbar): ?>
            <!-- Data Pager -->
            <?php include $data->getDataPagerTemplate(); ?>
        <?php endif; ?>

        <!-- Data Rows-->
        <?php include $data->getDataRowTemplate(); ?>

        <!-- Data Pager -->
        <?php include $data->getDataPagerTemplate(); ?>

        <div id="clip-pass-text" style="visibility: hidden"></div>

        <script>
            $(function () {
                $('#data-search-header').find('.sort-down').each(function () {
                    $(this).click(function () {
                        var key = $(this).parent().data('key');
                        $(this).parent().find('a').addClass('filterOn');
                        sysPassUtil.Common.searchSort(key, 0, <?php echo $data->getPager()->getLimitStart(); ?>);
                    });
                });

                $('#data-search-header').find('.sort-up').each(function () {
                    $(this).click(function () {
                        var key = $(this).parent().data('key');
                        $(this).parent().find('a').addClass('filterOn');
                        sysPassUtil.Common.searchSort(key, 1, <?php echo $data->getPager()->getLimitStart(); ?>);
                    });
                });
            })
        </script>
    <?php endif; ?>
</div>