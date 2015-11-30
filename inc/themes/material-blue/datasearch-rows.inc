<?php
/**
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $account SP\Controller\AccountsSearchData
 * @var $this    SP\Core\Template
 */
?>
<div id="data-search">
    <?php foreach ($data->getData()->getData() as $index => $account): ?>
        <?php if ($index === 'count'): continue; endif; ?>

        <div class="account-label round shadow">
            <div class="field-customer label-field">
                <div class="field-name"><?php echo _('Cliente'); ?></div>
                <div class="field-text round5 no-link"
                     style="background-color: <?php echo $account->getColor(); ?>;">
                    <?php if (!empty($account->getCustomerLink())): ?>
                        <a href="<?php echo $account->getCustomerLink(); ?>" target="blank"
                           title="<?php echo _('Buscar en Wiki'); ?>&nbsp;<?php echo $account->getCustomerName(); ?>">
                            <?php echo $account->getCustomerName(); ?>
                        </a>
                    <?php else: ?>
                        <?php echo $account->getCustomerName(); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="field-account label-field">
                <div class="field-name"><?php echo _('Nombre'); ?></div>
                <?php if ($account->isShow() && $account->getLink()): ?>
                    <a title="<?php echo _('Detalles de Cuenta'); ?>"
                       OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account->getId(); ?>)"><?php echo $account->getName(); ?></a>
                <?php else: ?>
                    <div class="field-text"><?php echo $account->getName(); ?></div>
                <?php endif; ?>
            </div>

            <div class="field-category label-field">
                <div class="field-name"><?php echo _('Categoría'); ?></div>
                <div class="field-text"><?php echo $account->getCategoryName(); ?></div>
            </div>

            <?php if ($account->isShow()): ?>
                <div class="field-user label-field">
                    <div class="field-name"><?php echo _('Usuario'); ?></div>
                    <div class="field-text"><?php echo $account->getLogin(); ?></div>
                </div>

                <div class="field-url label-field">
                    <div class="field-name"><?php echo _('URL / IP'); ?></div>
                    <?php if ($account->isUrlIslink()): ?>
                        <a href="<?php echo $account->getUrl(); ?>" target="_blank"
                           title="<?php echo _('Abrir enlace a'), ': ', $account->getUrl(); ?>"><?php echo $account->getUrlShort(); ?></a>
                    <?php else: ?>
                        <div class="field-text"><?php echo $account->getUrlShort(); ?></div>
                    <?php endif; ?>
                </div>

                <div class="account-info">
                    <i id="accesses-<?php echo $account->getId(); ?>" class="material-icons">face</i>

                    <div class="mdl-tooltip" for="accesses-<?php echo $account->getId(); ?>">
                        <?php echo _('Permisos'), '<br>', $account->getAccesses(); ?>
                    </div>

                    <?php if (!empty($account->getNotes())): ?>
                        <i id="notes-<?php echo $account->getId(); ?>" class="material-icons">speaker_notes</i>
                        <div class="mdl-tooltip" for="notes-<?php echo $account->getId(); ?>">
                            <?php echo _('Notas'), '<br>', $account->getNotes(); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($account->getNumFiles() > 0): ?>
                        <i id="attachments-<?php echo $account->getId(); ?>" class="material-icons">attach_file</i>
                        <div class="mdl-tooltip" for="attachments-<?php echo $account->getId(); ?>">
                            <?php echo _('Archivos adjuntos'), ': ', $account->getNumFiles(); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($wikiFilter)): ?>
                        <?php if (preg_match('/^(' . preg_quote($wikiFilter) . ').*/i', $account->getName())): ?>
                            <?php if (\SP\Controller\AccountsSearchData::$dokuWikiEnabled): ?>
                                <a href="<?php echo $wikiPageUrl, $account->getName(); ?>" target="_blank">
                                    <i class="material-icons"
                                       title="<?php echo _('Enlace a Wiki'); ?>">library_books</i>
                                </a>
                                <i class="material-icons fg-green100" title="<?php echo _('Ver en Wiki'); ?>"
                                   onClick="sysPassUtil.Common.viewWiki('<?php echo $account->getName() ?>',<?php echo \SP\Core\ActionsInterface::ACTION_WIKI_VIEW; ?>)">library_books</i>
                            <?php else: ?>
                                <a href="<?php echo $wikiPageUrl, $account->getName(); ?>" target="_blank">
                                    <i class="material-icons"
                                       title="<?php echo _('Enlace a Wiki'); ?>">library_books</i>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!$account->isShow()): ?>
                <div class="account-spacer"></div>
            <?php endif; ?>

            <div class="account-actions round">
                <?php foreach ($data->getDataActions() as $action): ?>
                    <?php if ($action->getReflectionFilter()->invoke($account)): ?>
                        <i class="material-icons <?php echo $action->getIcon()->getClass(); ?>"
                           title="<?php echo $action->getTitle(); ?>"
                           data-account-id="<?php echo $account->getId(); ?>"
                           data-sk="<?php echo $sk; ?>"
                           onclick="<?php echo $action->getOnClick(); ?>"><?php echo $action->getIcon()->getIcon(); ?></i>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

        </div>
    <?php endforeach; ?>
</div>