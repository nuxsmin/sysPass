<?php
/**
 * @var ThemeIcons         $icons
 * @var callable           $_getvar
 * @var ConfigData         $configData
 * @var AccountSearchItem  $account
 * @var DataGridTab        $data
 * @var DataGridActionBase $actionMenu
 * @var DataGridActionBase $action
 * @var AccountSearchItem  $accountSearchItem
 */

use SP\Config\ConfigData;
use SP\Core\Acl\ActionsInterface;
use SP\Core\UI\ThemeIcons;
use SP\Html\DataGrid\Action\DataGridActionBase;
use SP\Html\DataGrid\DataGridTab;
use SP\Services\Account\AccountSearchItem;
use SP\Util\DateUtil;

if (!isset($data)) {
    $data = $_getvar('data');
}

$wikiFilter = $_getvar('wikiFilter');
$favoriteRouteOn = $_getvar('favoriteRouteOn');
$favoriteRouteOff = $_getvar('favoriteRouteOff');
?>

<div id="data-search">
    <?php foreach ($data->getData()->getData() as $index => $accountSearchItem):
        if ($index === 'count'): continue; endif;
        $accountSearchData = $accountSearchItem->getAccountSearchVData();
        ?>

        <div class="account-label round shadow">
            <div class="label-left">
                <div class="label-row-up">
                    <div class="field-customer label-field">
                        <div class="field-name-hidden"><?php echo __('Client'); ?></div>
                        <?php if (null !== $accountSearchItem->getClientLink()): ?>
                            <a href="<?php echo $accountSearchItem->getClientLink(); ?>"
                               target="blank"
                               title="<?php echo __('Search in Wiki'); ?>">
                                <span class="mdl-chip mdl-chip--contact">
                                    <span style="background-color: <?php echo $accountSearchItem->getColor(); ?>;"
                                          class="mdl-chip__contact mdl-color-text--white"><?php echo mb_ucfirst($accountSearchData->getClientName()); ?></span>
                                    <span class="mdl-chip__text"
                                          title="<?php echo $accountSearchData->getClientName(); ?>"><?php echo $accountSearchItem->getShortClientName(); ?></span>
                                </span>
                            </a>
                        <?php else: ?>
                            <span class="mdl-chip mdl-chip--contact">
                                <span style="background-color: <?php echo $accountSearchItem->getColor(); ?>;"
                                      class="mdl-chip__contact mdl-color-text--white"><?php echo mb_ucfirst($accountSearchData->getClientName()); ?></span>
                                <span class="mdl-chip__text"
                                      title="<?php echo $accountSearchData->getClientName(); ?>"><?php echo $accountSearchItem->getShortClientName(); ?></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="field-account field-text label-field">
                        <div class="field-name"><?php echo __('Name'); ?></div>
                        <?php if ($accountSearchItem->isShow()
                            && $accountSearchItem::$accountLink
                            && $accountSearchItem->getLink()): ?>
                            <a title="<?php echo __('Account Details'); ?>"
                               class="btn-action"
                               data-action-route="<?php echo $_getvar('viewAccountRoute'); ?>"
                               data-item-id="<?php echo $accountSearchData->getId(); ?>"
                               data-onclick="account/view"><?php echo $accountSearchData->getName(); ?></a>
                        <?php else: ?>
                            <div class="field-text">
                                <?php echo $accountSearchData->getName(); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="field-category field-text label-field">
                        <div class="field-name"><?php echo __('Category'); ?></div>
                        <div class="field-text"><?php echo $accountSearchData->getCategoryName(); ?></div>
                    </div>

                    <?php if ($accountSearchItem->isShow()): ?>
                        <div class="field-user field-text label-field">
                            <div class="field-name"><?php echo __('User'); ?></div>
                            <div class="field-text"><?php echo $accountSearchItem->getShortLogin(); ?></div>
                        </div>

                        <div class="field-url field-text label-field">
                            <div class="field-name"><?php echo __('URL / IP'); ?></div>
                            <?php if ($accountSearchItem->isUrlIslink()): ?>
                                <a href="<?php echo $accountSearchData->getUrl(); ?>"
                                   target="_blank"
                                   title="<?php printf(__('Open link to: %s'), $accountSearchData->getUrl()); ?>"><?php echo $accountSearchItem->getShortUrl(); ?></a>
                            <?php else: ?>
                                <div class="field-text"><?php echo $accountSearchItem->getShortUrl(); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="field-user field-text label-field"></div>
                        <div class="field-url field-text label-field"></div>
                    <?php endif; ?>
                </div>
                <div class="label-row-down">
                    <div class="tags-box">
                        <?php foreach ($accountSearchItem->getTags() as $tag): ?>
                            <span class="tag"
                                  data-tag-id="<?php echo $tag->id; ?>"><?php echo $tag->name; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="label-right">
                <div class="account-info">
                    <?php if ($accountSearchItem->isShow()): ?>
                        <?php if ($accountSearchItem->isPasswordExpired()): ?>
                            <i id="icon-expired-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons <?php echo $icons->getIconWarning()->getClass(); ?>">
                                <?php echo $icons->getIconWarning()->getIcon(); ?></i>
                            <span
                                    for="icon-expired-<?php echo $accountSearchData->getId(); ?>"
                                    class="mdl-tooltip mdl-tooltip--top"><?php echo __('Password Expired'); ?></span>
                        <?php endif; ?>

                        <?php if ($accountSearchData->getIsPrivate() === 1): ?>
                            <i id="icon-private-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons">lock</i>
                            <span
                                    for="icon-private-<?php echo $accountSearchData->getId(); ?>"
                                    class="mdl-tooltip mdl-tooltip--top"><?php echo __('Private Account'); ?></span>
                        <?php elseif ($accountSearchData->getIsPrivateGroup() === 1): ?>
                            <i id="icon-private-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons">lock_open</i>
                            <span
                                    for="icon-private-<?php echo $accountSearchData->getId(); ?>"
                                    class="mdl-tooltip mdl-tooltip--top"><?php echo __('Private Account (Group)'); ?></span>
                        <?php else: ?>
                            <i id="accesses-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons">face</i>

                            <div class="mdl-tooltip mdl-tooltip--top"
                                 for="accesses-<?php echo $accountSearchData->getId(); ?>">
                                <div class="tooltip-text">
                                    <p class="tooltip-header"><?php echo __('Permissions'); ?></p>
                                    <p>
                                        <?php echo implode('<br>', $accountSearchItem->getAccesses()); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($accountSearchItem->isFavorite()): ?>
                            <i id="btn-favorite-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons icon-favorite mdl-color-text--amber-A100"
                               data-action-route-on="<?php echo $favoriteRouteOn; ?>"
                               data-action-route-off="<?php echo $favoriteRouteOff; ?>"
                               data-item-id="<?php echo $accountSearchData->getId(); ?>"
                               data-status="<?php echo 'on'; ?>">star</i>
                            <span
                                    for="btn-favorite-<?php echo $accountSearchData->getId(); ?>"
                                    class="mdl-tooltip mdl-tooltip--top"><?php echo __('Delete Favorite'); ?></span>
                        <?php else: ?>
                            <i id="btn-favorite-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons icon-favorite"
                               data-action-route-on="<?php echo $favoriteRouteOn; ?>"
                               data-action-route-off="<?php echo $favoriteRouteOff; ?>"
                               data-item-id="<?php echo $accountSearchData->getId(); ?>"
                               data-status="<?php echo 'off'; ?>">star_border</i>
                            <span
                                    for="btn-favorite-<?php echo $accountSearchData->getId(); ?>"
                                    class="mdl-tooltip mdl-tooltip--top"><?php echo __('Mark as Favorite'); ?></span>
                        <?php endif; ?>

                        <?php if ($accountSearchData->getNotes() !== ''): ?>
                            <i id="notes-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons">speaker_notes</i>
                            <div class="mdl-tooltip mdl-tooltip--top"
                                 for="notes-<?php echo $accountSearchData->getId(); ?>">
                                <div class="tooltip-text">
                                    <p class="tooltip-header"><?php echo __('Notes'); ?></p>
                                    <p>
                                        <?php echo htmlspecialchars($accountSearchItem->getShortNotes(), ENT_QUOTES); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($accountSearchItem->getNumFiles() > 0): ?>
                            <i id="attachments-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons">attach_file</i>
                            <div class="mdl-tooltip mdl-tooltip--top"
                                 for="attachments-<?php echo $accountSearchData->getId(); ?>">
                                <div class="tooltip-text">
                                    <?php printf('%s: %d', __('Attachments'), $accountSearchItem->getNumFiles()); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (null !== $accountSearchItem->getPublicLink()): ?>
                            <i id="publiclink-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons"><?php echo $icons->getIconPublicLink()->getIcon(); ?></i>
                            <div class="mdl-tooltip mdl-tooltip--top"
                                 for="publiclink-<?php echo $accountSearchData->getId(); ?>">
                                <div class="tooltip-text">
                                    <p class="tooltip-header"><?php echo __('Public Link'); ?></p>
                                    <p>
                                        <?php printf('%s: %s', __('Expiry Date'), DateUtil::getDateFromUnix($accountSearchData->getPublicLinkDateExpire())); ?>
                                        <br>
                                        <?php printf('%s: %s', __('Visits'), $accountSearchData->getPublicLinkTotalCountViews()); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($wikiFilter
                            && $accountSearchItem->isWikiMatch($wikiFilter)): ?>
                            <?php if (AccountSearchItem::$dokuWikiEnabled): ?>
                                <a href="<?php echo $_getvar('wikiPageUrl'), $accountSearchData->getName(); ?>"
                                   target="_blank">
                                    <i class="material-icons"
                                       title="<?php echo __('Link to Wiki'); ?>">library_books</i>
                                </a>
                                <i class="btn-action material-icons fg-green100"
                                   title="<?php echo __('View at Wiki'); ?>"
                                   data-action-route="<?php echo ActionsInterface::WIKI_VIEW; ?>"
                                   data-pagename="<?php echo $accountSearchData->getName(); ?>"
                                   data-onclick="wiki/show">library_books</i>
                            <?php else: ?>
                                <a href="<?php echo $_getvar('wikiPageUrl'), $accountSearchData->getName(); ?>"
                                   target="_blank">
                                    <i class="material-icons"
                                       title="<?php echo __('Link to Wiki'); ?>">library_books</i>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>


                <?php if ($accountSearchItem->isShow()
                    || $accountSearchItem->isShowRequest()): ?>
                    <div class="account-actions">
                        <?php foreach ($data->getDataActionsFiltered($accountSearchItem) as $action):
                            $actionUid = uniqid('btn-action-', true);
                            ?>
                            <i id="<?php echo $actionUid; ?>"
                               class="btn-action material-icons <?php echo $action->getClassesAsString(), ' ', $action->getIcon()->getClass(); ?>"
                               data-item-id="<?php echo $accountSearchData->getId(); ?>"
                               data-parent-id="<?php echo $accountSearchData->getParentId(); ?>"
                                <?php foreach ($action->getData() as $dataName => $dataValue): printf('data-%s="%s"', $dataName, $dataValue); endforeach; ?>>
                                <?php echo $action->getIcon()->getIcon(); ?></i>
                            <span
                                    for="<?php echo $actionUid; ?>"
                                    class="mdl-tooltip mdl-tooltip--bottom"><?php echo $action->getTitle(); ?></span>
                        <?php endforeach; ?>

                        <?php if ($data->getDataActionsMenuCount()):
                            $actionsMenu = $data->getDataActionsMenuFiltered($accountSearchItem);
                            if (count($actionsMenu) > 0):
                                $actionMenuUid = uniqid('actions-menu-lower-right-', true);
                                ?>
                                <button id="<?php echo $actionMenuUid; ?>"
                                        class="mdl-button mdl-js-button mdl-button--icon">
                                    <i class="material-icons">more_vert</i>
                                </button>
                                <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect"
                                    for="<?php echo $actionMenuUid; ?>">

                                    <?php foreach ($actionsMenu as $actionMenu): ?>
                                        <li class="btn-action mdl-menu__item <?php echo $actionMenu->getClassesAsString(); ?>"
                                            data-item-id="<?php echo $accountSearchData->getId(); ?>"
                                            data-parent-id="<?php echo $accountSearchData->getParentId(); ?>"
                                            <?php foreach ($actionMenu->getData() as $dataName => $dataValue): printf('data-%s="%s"', $dataName, $dataValue); endforeach; ?>>
                                            <i class="material-icons <?php echo $actionMenu->getIcon()->getClass(); ?>"
                                               title="<?php echo $actionMenu->getTitle(); ?>"><?php echo $actionMenu->getIcon()->getIcon(); ?></i>
                                            <?php echo $actionMenu->getName(); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>