<!-- Start Tabs-->

<?php
/**
 * @var $icons   SP\Core\UI\ThemeIconsBase
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $this    SP\Core\Template
 * @var $action  SP\Html\DataGrid\DataGridAction|SP\Html\DataGrid\DataGridActionSearch
 */
?>
<div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
    <div id="tabsHeader" class="mdl-tabs__tab-bar"></div>

    <?php foreach ($tabs as $index => $data): ?>
        <div id="tabs-<?php echo $index; ?>" class="mdl-tabs__panel">
            <div class="tab-actions">
                <ul>
                    <?php foreach ($data->getDataActions() as $action): ?>
                        <?php if ($action->getType() === \SP\Html\DataGrid\DataGridActionType::NEW_ITEM): ?>
                            <li>
                                <button
                                    id="btn-add-<?php echo $action->getId(); ?>"
                                    class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored mdl-color--indigo-A200 mdl-js-ripple-effect"
                                    data-action-id="<?php echo $action->getId(); ?>"
                                    data-item-id="0"
                                    data-activetab="<?php echo $index; ?>"
                                    data-nextaction-id="<?php echo $data->getOnCloseAction(); ?>"
                                    data-onclick="<?php echo $action->getOnClick(); ?>">
                                    <i class="material-icons"><?php echo $action->getIcon()->getIcon(); ?></i>
                                </button>
                                <span for="btn-add-<?php echo $action->getId(); ?>"
                                      class="mdl-tooltip mdl-tooltip--bottom"><?php echo $action->getTitle(); ?></span>
                            </li>
                        <?php elseif ($action->getType() === \SP\Html\DataGrid\DataGridActionType::SEARCH_ITEM): ?>
                            <li class="datagrid-action-search">
                                <form method="post" id="<?php echo $action->getName(); ?>"
                                      name="<?php echo $action->getName(); ?>"
                                      class="form-action"
                                      data-target="#data-table-<?php echo $data->getId(); ?>"
                                      data-activetab="<?php echo $index; ?>"
                                      data-onsubmit="<?php echo $action->getOnSubmit(); ?>">

                                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--expandable">
                                        <label class="mdl-button mdl-js-button mdl-button--icon" id="btn-search-<?php echo $index; ?>"
                                               for="search-<?php echo $index; ?>">
                                            <i class="material-icons">search</i>
                                        </label>
                                        <span for="btn-search-<?php echo $index; ?>"
                                              class="mdl-tooltip mdl-tooltip--bottom"><?php echo $action->getTitle(); ?></span>

                                        <div class="mdl-textfield__expandable-holder">
                                            <input class="mdl-textfield__input" type="text"
                                                   id="search-<?php echo $index; ?>" name="search"/>
                                            <label class="mdl-textfield__label"
                                                   for="search-<?php echo $index; ?>"><?php echo $action->getTitle(); ?></label>
                                        </div>
                                    </div>

                                    <button type="reset" id="btn-reset-<?php echo $index; ?>"
                                            class="btn-clear mdl-button mdl-js-button mdl-button--icon">
                                        <i class="material-icons"><?php echo $icons->getIconClear()->getIcon(); ?></i>
                                    </button>
                                    <span for="btn-reset-<?php echo $index; ?>"
                                          class="mdl-tooltip mdl-tooltip--bottom"><?php echo $icons->getIconClear()->getTitle(); ?></span>

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
                        <button type="button" id="btn-back-<?php echo $index; ?>"
                                class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                                title="<?php echo $icons->getIconBack()->getTitle(); ?>">
                            <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                        </button>
                        <span for="btn-back-<?php echo $index; ?>"
                              class="mdl-tooltip mdl-tooltip--bottom"><?php echo $icons->getIconBack()->getTitle(); ?></span>
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
                                        <?php echo __('Eliminar Seleccionados'); ?>
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

        <script type="text/javascript">
            sysPassApp.theme().html.tabs.add("#tabsHeader", <?php echo $index; ?>, "<?php echo $data->getTitle(); ?>", <?php echo $index === $activeTabId ? 1 : 0; ?>);
        </script>
    <?php endforeach; ?>
</div> <!-- End Tabs-->