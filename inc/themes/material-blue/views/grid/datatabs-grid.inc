<!-- Start Tabs-->

<?php
/**
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $this    SP\Core\Template
 * @var $action  SP\Html\DataGrid\DataGridAction|SP\Html\DataGrid\DataGridActionSearch
 */
?>
<div id="tabs">
    <ul id="tabsHeader"></ul>

    <?php foreach ($tabs as $index => $data): ?>
        <script>
            $('#tabsHeader').append('<li><a href="#tabs-<?php echo $index; ?>" title="<?php echo $data->getTitle(); ?>"><?php echo $data->getTitle(); ?></a></li>');
        </script>

        <div id="tabs-<?php echo $index; ?>">
            <div class="tab-actions action fullWidth">
                <ul>
                    <?php foreach ($data->getDataActions() as $action): ?>
                        <?php if ($action->getType() === \SP\Html\DataGrid\DataGridActionType::NEW_ITEM): ?>
                            <li>
                                <button
                                    class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored mdl-color--indigo-A200 mdl-js-ripple-effect"
                                    data-action-id="<?php echo $action->getId(); ?>"
                                    data-item-id="0"
                                    data-activetab="<?php echo $index; ?>"
                                    data-nextaction-id="<?php echo $data->getOnCloseAction(); ?>"
                                    data-onclick="<?php echo $action->getOnClick(); ?>"
                                    title="<?php echo $action->getTitle(); ?>">
                                    <i class="material-icons"><?php echo $action->getIcon()->getIcon(); ?></i>
                                </button>
                            </li>
                        <?php elseif ($action->getType() === \SP\Html\DataGrid\DataGridActionType::SEARCH_ITEM): ?>
                            <li class="datagrid-action-search" style="width: auto;">
                                <form method="post" id="<?php echo $action->getName(); ?>"
                                      name="<?php echo $action->getName(); ?>"
                                      class="form-action"
                                      data-onsubmit="<?php echo $action->getOnSubmit(); ?>">
                                    <i class="btn-clear material-icons"
                                       title="<?php echo _('Limpiar'); ?>">clear_all</i>
                                    <div class="mdl-textfield mdl-js-textfield">
                                        <input class="mdl-textfield__input mdl-color-text--indigo-400" type="text"
                                               id="txtSearch"
                                               name="search" value=""/>
                                        <label class="mdl-textfield__label"
                                               for="txtSearch"><?php echo $action->getTitle(); ?></label>
                                    </div>
                                    <input type="hidden" name="sk" value="">
                                    <input type="hidden" name="target" value="data-rows-<?php echo $data->getId(); ?>">
                                    <input type="hidden" name="activeTab" value="<?php echo $index; ?>">
                                    <input type="hidden" name="actionId" value="<?php echo $action->getId(); ?>">
                                    <input type="hidden" name="isAjax" value="1">
                                </form>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <li>
                        <button type="button"
                                class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                                title="<?php echo $icons->getIconBack()->getTitle(); ?>">
                            <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                        </button>
                    </li>
                </ul>
            </div>

            <div id="<?php echo $data->getId(); ?>" class="data-header">
                <ul class="round5 header-grey">
                    <?php foreach ($data->getHeader()->getHeaders() as $header): ?>
                        <li style="width: <?php echo $data->getHeader()->getWidth(); ?>%;"><?php echo $header; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div id="data-rows-<?php echo $data->getId(); ?>" class="data-rows">
                <?php include $data->getDataRowTemplate(); ?>
            </div>
        </div>
    <?php endforeach; ?>
</div> <!-- End Tabs-->

<script type="text/javascript">
    sysPassApp.triggers().views.datatabs(<?php echo $activeTabId; ?>);
</script>
