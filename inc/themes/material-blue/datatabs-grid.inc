<!-- Start Tabs-->
<div id="tabs">
    <ul>
        <?php foreach ($tabs as $index => $tab):
            /**
             * @var $tab SP\Html\DataGrid\DataGridTab
             */
            ?>
            <li>
                <a href="#tabs-<?php echo $index; ?>"
                   title="<?php echo $tab->getTitle(); ?>"><?php echo $tab->getTitle(); ?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php foreach ($tabs as $index => $tab):
        /**
         * @var $tab SP\Html\DataGrid\DataGridTab
         */
        ?>
        <div id="tabs-<?php echo $index; ?>">
            <div class="action fullWidth">
                <ul>
                    <?php foreach ($tab->getDataActions() as $action): ?>
                        <?php if ($action->isNew()): ?>
                            <li>
                                <button
                                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored mdl-js-ripple-effect"
                                    data-itemid="0" data-activetab="<?php echo $index; ?>"
                                    data-nextactionid="<?php echo $tab->getOnCloseAction(); ?>"
                                    OnClick="<?php echo $action->getOnClick(); ?>">
                                    <i class="material-icons"
                                       title="<?php echo $action->getTitle(); ?>"><?php echo $action->getIcon()->getIcon(); ?></i>
                                </button>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <li>
                        <button id="btnBack" type="button"
                                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                                OnClick="sysPassUtil.Common.doAction(<?php echo $lastAction; ?>, <?php echo $actionId; ?>, <?php echo $lastAccountId; ?>)"
                                title="<?php echo _('Atrás'); ?>">
                            <i class="material-icons">arrow_back</i>
                        </button>
                    </li>
                </ul>
            </div>

            <div id="<?php echo $tab->getId(); ?>" class="data-header">
                <ul class="round5 header-grey">
                    <?php foreach ($tab->getHeader()->getHeaders() as $header): ?>
                        <li style="width: <?php echo $tab->getHeader()->getWidth(); ?>%;"><?php echo $header; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="data-rows">
                <?php foreach ($tab->getData()->getData() as $item): ?>
                    <ul>
                        <?php foreach ($tab->getData()->getDataRowSources() as $rowSrc): ?>
                            <li class="cell-data" style="width: <?php echo $tab->getHeader()->getWidth(); ?>%;">
                                <?php echo (!is_null($item->$rowSrc)) ? $item->$rowSrc : '&nbsp;'; // Fix height ?>
                            </li>
                        <?php endforeach; ?>

                        <?php if (count($tab->getData()->getDataRowSourcesWithIcon()) > 0): ?>
                            <li class="cell-nodata" style="width: <?php echo $tab->getHeader()->getWidth(); ?>%;">
                                <?php foreach ($tab->getData()->getDataRowSourcesWithIcon() as $rowSrcIcon): ?>
                                    <?php if ($item->$rowSrcIcon[0] == 1): ?>
                                        <i class="material-icons" title="<?php echo $rowSrcIcon[1]->getTitle(); ?>">
                                            <?php echo $rowSrcIcon[1]->getIcon(); ?>
                                        </i>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </li>
                        <?php endif; ?>

                        <li class="cell-actions round">
                            <?php foreach ($tab->getDataActions() as $action): ?>
                                <?php if (!$action->isSkip()): ?>
                                    <?php if ($action->getFilterRowSource() && $item->{$action->getFilterRowSource()} == 1): continue; endif; ?>
                                    <i class="material-icons <?php echo $action->getIcon()->getClass(); ?>"
                                       title="<?php echo $action->getTitle(); ?>"
                                       data-itemid="<?php echo $item->{$tab->getData()->getDataRowSourceId()}; ?>"
                                       data-activetab="<?php echo $index; ?>"
                                       data-nextactionid="<?php echo $actionId; ?>"
                                       Onclick="<?php echo $action->getOnClick(); ?>"><?php echo $action->getIcon()->getIcon(); ?></i>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </li>
                    </ul>
                <?php endforeach; ?>
            </div>

            <div id="pageNav" class="round shadow">
                <div id="pageNavLeft">
                    <?php echo count($tab->getData()->getData()); ?> @ <?php echo $tab->getTime(); ?>s
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div> <!-- End Tabs-->

<script>
    $("#tabs").tabs({
        active: <?php echo $activeTabId; ?>,
        create: function (event, ui) {
            $("input:visible:first").focus();
        },
        activate: function (event, ui) {
            sysPassUtil.Common.setContentSize();
        }
    });
</script>
