<!-- Start Tabs-->

<?php
/**
 * @var $icons   SP\Core\UI\ThemeIconsBase
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $this    SP\Core\Template
 * @var $action  SP\Html\DataGrid\DataGridAction|SP\Html\DataGrid\DataGridActionSearch
 */
?>
<div id="tabs" xmlns="http://www.w3.org/1999/html">
    <ul id="tabsHeader"></ul>

    <?php foreach ($tabs as $index => $data): ?>
        <script>
            $('#tabsHeader').append('<li><a href="#tabs-<?php echo $index; ?>" title="<?php echo $data->getTitle(); ?>"><?php echo $data->getTitle(); ?></a></li>');
        </script>

        <div id="tabs-<?php echo $index; ?>">
            <div class="tab-actions">
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
                                      data-target="#data-table-<?php echo $data->getId(); ?>"
                                      data-activetab="<?php echo $index; ?>"
                                      data-onsubmit="<?php echo $action->getOnSubmit(); ?>">

                                    <div class="mdl-textfield mdl-js-textfield">
                                        <input class="mdl-textfield__input mdl-color-text--indigo-400" type="text"
                                               id="txtSearch"
                                               name="search" value=""/>
                                        <label class="mdl-textfield__label"
                                               for="txtSearch"><?php echo $action->getTitle(); ?></label>
                                    </div>

                                    <button type="reset"
                                            class="btn-clear mdl-button mdl-js-button mdl-button--icon"
                                            title="<?php echo $icons->getIconClear()->getTitle(); ?>">
                                        <i class="material-icons"><?php echo $icons->getIconClear()->getIcon(); ?></i>
                                    </button>

                                    <input type="hidden" name="sk" value="">
                                    <input type="hidden" name="start" value="0">
                                    <input type="hidden" name="count"
                                           value="<?php echo $data->getPager()->getLimitCount(); ?>">
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
                    <?php if (count($data->getDataActionsMenu()) > 0): ?>
                        <li>
                            <button id="<?php echo $data->getId(); ?>-menu-lower-right"
                                    class="mdl-button mdl-js-button mdl-button--icon">
                                <i class="material-icons">more_vert</i>
                            </button>

                            <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect"
                                for="<?php echo $data->getId(); ?>-menu-lower-right">
                                <?php foreach ($data->getDataActionsMenu() as $action): ?>
                                    <li class="btn-action mdl-menu__item"
                                        data-onclick="<?php echo $action->getOnClick(); ?>"
                                        data-action-id="<?php echo $action->getId(); ?>"
                                        data-nextaction-id="<?php echo $actionId; ?>"
                                        data-activetab="<?php echo $index; ?>"
                                        data-selection="#data-table-<?php echo $data->getId(); ?>">
                                        <i class="material-icons <?php echo $action->getIcon()->getClass(); ?>"><?php echo $action->getIcon()->getIcon(); ?></i>
                                        <?php echo _('Eliminar Seleccionados'); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div id="data-table-<?php echo $data->getId(); ?>">
                <?php include __DIR__ . DIRECTORY_SEPARATOR . 'datagrid-table.inc'; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div> <!-- End Tabs-->

<script type="text/javascript">
    sysPassApp.triggers().views.datatabs(<?php echo $activeTabId; ?>);
</script>
