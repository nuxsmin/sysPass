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
            <div class="action fullWidth">
                <ul>
                    <?php foreach ($data->getDataActions() as $action): ?>
                        <?php if ($action->getType() === \SP\Html\DataGrid\DataGridActionType::NEW_ITEM): ?>
                            <li>
                                <button
                                    class="button-action"
                                    data-itemid="0" data-activetab="<?php echo $index; ?>"
                                    data-nextactionid="<?php echo $data->getOnCloseAction(); ?>"
                                    OnClick="<?php echo $action->getOnClick(); ?>"
                                    title="<?php echo $action->getTitle(); ?>">
                                    <img src="<?php echo $action->getIcon()->getIcon(); ?>"/>
                                </button>
                            </li>
                        <?php elseif ($action->getType() === \SP\Html\DataGrid\DataGridActionType::SEARCH_ITEM): ?>
                            <li class="datagrid-action-search" style="width: auto;">
                                <form method="post" id="<?php echo $action->getName(); ?>"
                                      name="<?php echo $action->getName(); ?>"
                                      onsubmit="<?php echo $action->getOnSubmit(); ?>">
                                    <img src="<?php echo $icons->getIconClear()->getIcon(); ?>" title="<?php echo _('Limpiar'); ?>"
                                         class="inputImg btnClear"
                                         onclick="$('#<?php echo $action->getName(); ?>').trigger('reset').submit();">
                                    <input class="txtSearch" type="text" name="search" value=""
                                           title="<?php echo $action->getTitle(); ?>"/>
                                    <input type="hidden" name="sk" value="<?php echo $sk; ?>">
                                    <input type="hidden" name="target" value="data-rows-<?php echo $data->getId(); ?>">
                                    <input type="hidden" name="activeTab" value="<?php echo $index; ?>">
                                    <input type="hidden" name="actionId" value="<?php echo $action->getId(); ?>">
                                    <input type="hidden" name="isAjax" value="1">
                                </form>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <li>
                        <button id="btnBack" type="button"
                                class="button-action"
                                OnClick="sysPassUtil.Common.doAction(<?php echo $lastAction; ?>, <?php echo $actionId; ?>, <?php echo $lastAccountId; ?>)"
                                title="<?php echo $icons->getIconBack()->getTitle(); ?>">
                            <img src="<?php echo $icons->getIconBack()->getIcon(); ?>" alt="save"/>
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

<script>
    $("#tabs").tabs({
        active: <?php echo $activeTabId; ?>
    });
</script>
