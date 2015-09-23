<?php foreach ($tabs as $index => $tab): ?>
    <div id="tabs-<?php echo $index; ?>">
        <div class="action fullWidth">
            <button type="submit" form="frmSecurity" title="<?php echo $tab['props']['actions']['new']['title']; ?>"
                    class="button-action" data-itemid="0" data-activetab="<?php echo $index; ?>"
                    data-nextactionid="<?php echo (isset($onCloseAction)) ? $onCloseAction : ''; ?>"
                    OnClick="<?php echo $tab['props']['actions']['new']['onclick']; ?>">
                <img src="<?php \SP\Init::$WEBURI; ?>imgs/add.png" alt="save"/>
            </button>
        </div>

        <?php if ($tab['props']['header']): ?>
            <div id="title" class="midroundup titleNormal"><?php echo $tab['props']['header']; ?></div>
        <?php endif; ?>

        <div id="<?php echo $tab['props']['tblId']; ?>" class="data-header">
            <ul class="round header-grey">
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
            <?php $numActions = count($tab['props']["actions"]); ?>
            <?php foreach ($tab['query'] as $item): ?>
                <?php $i = 0; ?>
                <?php $itemId = $item->$tab['props']['tblRowSrcId']; ?>
                <?php $action_check = array(); ?>
                <ul>
                    <?php foreach ($tab['props']['tblRowSrc'] as $type => $rowSrc): ?>
                        <!-- If row is an array handle images in it-->
                        <?php if ($type === 'images'): ?>
                            <li class="cell-nodata" style="width: <?php echo $tab['props']['cellWidth']; ?>%;">
                                <?php foreach ($rowSrc as $rowName => $imgProp): ?>
                                    <?php if ($item->$rowName): ?>
                                        <img src="imgs/<?php echo $imgProp['img_file']; ?>"
                                             title="<?php echo $imgProp['img_title']; ?>"/>
                                        <?php $action_check[$rowName] = 1; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </li>
                        <?php else: ?>
                            <li class="cell-data" style="width: <?php echo $tab['props']['cellWidth'] ?>%;">
                                <?php echo ($item->$rowSrc) ? $item->$rowSrc : '&nbsp;'; // Fix height ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <li class="cell-actions round" style="width: <?php echo($numActions * 5 + 2); ?>%;">
                        <?php foreach ($tab['props']['actions'] as $actionName => $action): ?>
                            <?php if (isset($action['skip']) || ($action['id'] == \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDITPASS && isset($action_check['user_isLdap']))): continue; endif; ?>
                            <?php $i++; ?>
                            <img src="<?php echo $action['img']; ?>" title="<?php echo $action['title']; ?>"
                                 class="inputImg <?php echo ($i >= $maxNumActions) ? 'actions-optional' : ''; ?>"
                                 data-itemid="<?php echo $itemId; ?>" data-activetab="<?php echo $index; ?>"
                                 data-nextactionid="<?php echo $actionId; ?>"
                                 Onclick="<?php echo $action['onclick'];; ?>;"/>
                        <?php endforeach; ?>
                        <?php if ($numActions > $maxNumActions): ?>
                            <img src="imgs/action.png" title="<?php echo _('Más Acciones'); ?>"
                                 OnClick="sysPassUtil.Common.showOptional(this)"/>
                        <?php endif; ?>
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