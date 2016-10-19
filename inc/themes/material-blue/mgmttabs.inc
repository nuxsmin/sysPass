<?php foreach ($tabs as $index => $tab): ?>
    <div id="tabs-<?php echo $index; ?>">
        <div class="action fullWidth">
            <ul>
                <li>
                    <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored mdl-js-ripple-effect" data-itemid="0" data-activetab="<?php echo $index; ?>"
                            data-nextactionid="<?php echo (isset($onCloseAction)) ? $onCloseAction : ''; ?>"
                            OnClick="<?php echo $tab['props']['actions']['new']['onclick']; ?>">
                        <i class="material-icons" title="<?php echo $tab['props']['actions']['new']['title']; ?>">add</i>
                    </button>
                </li>
                <li>
                    <button id="btnBack" type="button" class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored" OnClick="sysPassUtil.Common.doAction(<?php echo $lastAction; ?>, <?php echo $actionId; ?>, <?php echo $lastAccountId; ?>)"  title="<?php echo _('Atrás'); ?>">
                        <i class="material-icons">arrow_back</i>
                    </button>
                </li>
            </ul>
        </div>

        <?php if ($tab['props']['header']): ?>
            <div id="title" class="midroundup titleBlue"><?php echo $tab['props']['header']; ?></div>
        <?php endif; ?>

        <div id="<?php echo $tab['props']['tblId']; ?>" class="data-header">
            <ul class="round5 header-grey">
                <?php foreach ($tab['props']['tblHeaders'] as $header): ?>
                    <?php if (is_array($header)): ?>
                        <li class="<?php echo $header['class']; ?>"
                            style="width: <?php echo $cellWidth; ?>%;"><?php echo $header['name']; ?></li>
                    <?php else: ?>
                        <li style="width:<?php echo $tab['props']['cellWidth']; ?>%;"><?php echo $header; ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="data-rows">
            <?php foreach ($tab['query'] as $item): ?>
                <?php $i = 0; ?>
                <?php $itemId = $item->{$tab['props']['tblRowSrcId']}; ?>
                <?php $action_check = array(); ?>
                <ul>
                    <?php foreach ($tab['props']['tblRowSrc'] as $type => $rowSrc): ?>
                        <!-- If row is an array handle images in it-->
                        <?php if ($type === 'images'): ?>
                            <li class="cell-nodata" style="width: <?php echo $tab['props']['cellWidth']; ?>%;">
                                <?php foreach ($rowSrc as $rowName => $imgProp): ?>
                                    <?php if ($item->$rowName): ?>
                                        <i class="material-icons" title="<?php echo $imgProp['img_title']; ?>">
                                            <?php echo $imgProp['icon']; ?>
                                        </i>
                                        <?php $action_check[$rowName] = 1; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </li>
                        <?php else: ?>
                            <li class="cell-data" style="width: <?php echo $tab['props']['cellWidth'] ?>%;">
                                <?php echo $item->$rowSrc ?: '&nbsp;'; // Fix height ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <li class="cell-actions round">
                        <?php foreach ($tab['props']['actions'] as $actionName => $action): ?>
                            <?php
                                if (isset($action['skip'])
                                    || ($action['id'] == \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDITPASS && isset($action_check['user_isLdap']))
                                ): continue; endif;

                                $i++;

                                switch($actionName){
                                    case 'edit':
                                        $actionClass = 'fg-orange80';
                                        break;
                                    case 'del':
                                        $actionClass = 'fg-red80';
                                        break;
                                    default:
                                        $actionClass = 'fg-blue80';
                                    }
                            ?>
                            <i class="material-icons btn-action <?php echo $actionClass; ?>"
                               title="<?php echo $action['title']; ?>"
                               data-itemid="<?php echo $itemId; ?>"
                               data-activetab="<?php echo $index; ?>"
                               data-nextactionid="<?php echo $actionId; ?>"
                               data-onclick="<?php echo $action['onclick']; ?>"><?php echo $action['icon']; ?></i>
                        <?php endforeach; ?>
                    </li>
                </ul>
            <?php endforeach; ?>
        </div>

        <div id="pageNav" class="round shadow">
            <div id="pageNavLeft">
                <?php echo count($tab['query']); ?> @ <?php echo $tab['time']; ?>s
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
    $(function () {
        $(".btn-action").on("click", function () {
            eval($(this).data('onclick'));
        })
    })
</script>
