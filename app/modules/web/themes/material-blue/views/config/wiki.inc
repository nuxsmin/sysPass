<?php
/**
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var \SP\Config\ConfigData  $configData
 * @var callable               $_getvar
 * @var \SP\Mvc\View\Template  $this
 */
?>

<?php if (!$_getvar('curlIsAvailable')): ?>
    <div class="msg-option-unvailable">
        <?php printf(__('The \'%s\' extension is unavailable'), 'curl'); ?>
        <i id="help-curl" class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
        <div class="mdl-tooltip mdl-tooltip--large" for="help-curl">
            <p>
                <?php echo __('This extension is needed to connect with DokuWiki'); ?>
            </p>
        </div>
    </div>
<?php endif; ?>

<form method="post" name="frmWiki" id="frmWiki" class="form-action"
      data-onsubmit="config/save"
      data-action-route="configWiki/save"
      data-hash="">

    <div id="title" class="titleNormal">
        <i class="material-icons">folder</i>
        <?php echo __('Wiki'); ?>
    </div>

    <table id="tblWiki" class="data tblConfig">
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-wiki-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="wiki_enabled">
                                <input type="checkbox" id="wiki_enabled"
                                       class="mdl-switch__input"
                                       name="wiki_enabled" <?php echo $configData->isWikiEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Enable Wiki links'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Enables the option to add a link to search for results in an external Wiki.'); ?></span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Wiki search URL'); ?>
                <div id="help-wiki_searchurl"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-wiki_searchurl">
                    <p>
                        <?php echo __('URL that Wiki uses for making an entry searching.'); ?>
                    </p>

                    <p>
                        <?php echo __('The client\'s name is used as parameter.'); ?>
                    </p>

                    <p>
                        <?php __('Example:'); ?>
                    </p>

                    <p>
                        https://wiki.example.org/search.php?phrase=
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="wiki_searchurl" name="wiki_searchurl" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="255" value="<?php echo $configData->getWikiSearchurl(); ?>">
                    <label class="mdl-textfield__label"
                           for="wiki_searchurl"><?php echo __('Wiki search URL'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Wiki page URL'); ?>
                <div id="help-wiki_pageurl"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-wiki_pageurl">
                    <p>
                        <?php echo __('URL that Wiki uses for accessing to the entry details.'); ?>
                    </p>

                    <p>
                        <?php echo __('Account\'s name is used as parameter of Wiki search variable.'); ?>
                    </p>

                    <p>
                        <?php echo __('Example:'); ?>
                    </p>

                    <p>
                        https://wiki.example.org/show.php?name=
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="wiki_pageurl" name="wiki_pageurl" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="255" value="<?php echo $configData->getWikiPageurl(); ?>">
                    <label class="mdl-textfield__label"
                           for="wiki_pageurl"><?php echo __('Wiki page URL'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Account name prefix'); ?>
                <div id="help-wikifilter"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-wikifilter">
                    <p>
                        <?php echo __('Prefix to determine which accounts have a link to the Wiki.'); ?>
                    </p>

                    <p>
                        <?php echo __('Examples:') . ' serv- | srv- | vm-'; ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Account name prefix'); ?></div>

                <input type="text" name="wiki_filter" id="wikifilter"
                       value="<?php echo implode(',', $configData->getWikiFilter()); ?>"/>
            </td>
        </tr>
    </table>

    <div id="title" class="titleNormal">
        <i class="material-icons">folder</i>
        <?php echo __('DokuWiki API'); ?>
    </div>

    <table id="tblDokuWiki" class="data tblConfig">
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-wiki-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="dokuwiki_enabled">
                                <input type="checkbox" id="dokuwiki_enabled"
                                       class="mdl-switch__input"
                                       name="dokuwiki_enabled" <?php echo $configData->isDokuwikiEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Enable DokuWiki API'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Enables DokuWiki XML-RPC API for Wiki links.'); ?>
                                <br>
                                <?php echo __('In order to get this feature working, you should enable Wiki links for accounts filtering'); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>

        <tr>
            <td class="descField">
                <?php echo __('API URL'); ?>
                <div id="help-dokuwiki_url"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-dokuwiki_url">
                    <p>
                        <?php echo __('DokuWiki API URL'); ?>
                    </p>

                    <p>
                        <?php echo __('Example:'); ?>
                    <ul>
                        <li>http://wiki.syspass.org/lib/exe/xmlrpc.php</li>
                    </ul>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dokuwiki_url" name="dokuwiki_url" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="255" value="<?php echo $configData->getDokuwikiUrl(); ?>"/>
                    <label class="mdl-textfield__label" for="dokuwiki_url"><?php echo __('API URL'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Base URL'); ?>
                <div id="help-dokuwiki_urlbase"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-dokuwiki_urlbase">
                    <p>
                        <?php echo __('DokuWiki base URL'); ?>
                    </p>

                    <p>
                        <?php echo __('Example:'); ?>
                    <ul>
                        <li>http://wiki.syspass.org/dokuwiki</li>
                    </ul>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dokuwiki_urlbase" name="dokuwiki_urlbase" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="255" value="<?php echo $configData->getDokuwikiUrlBase(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="dokuwiki_urlbase"><?php echo __('Base URL'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('User'); ?>
                <div id="help-dokuwiki_user"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-dokuwiki_user">
                    <p>
                        <?php echo __('User for connecting to the DokuWiki API.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dokuwiki_user" name="dokuwiki_user" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="128" value="<?php echo $configData->getDokuwikiUser(); ?>"/>
                    <label class="mdl-textfield__label" for="dokuwiki_user"><?php echo __('User'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Password'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dokuwiki_pass" name="dokuwiki_pass" type="password"
                           class="mdl-textfield__input passwordfield__input mdl-color-text--indigo-400"
                           maxlength="128" value="<?php echo $configData->getDokuwikiPass(); ?>"/>
                    <label class="mdl-textfield__label" for="dokuwiki_pass"><?php echo __('Password'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Namespace'); ?>
                <div id="help-dokuwiki_namespace"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-dokuwiki_namespace">
                    <p>
                        <?php echo __('Namespace used to search pages.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="dokuwiki_namespace" name="dokuwiki_namespace" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="128"
                           value="<?php echo $configData->getDokuwikiNamespace(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="dokuwiki_namespace"><?php echo __('Namespace'); ?></label>
                </div>
            </td>
        </tr>
    </table>
</form>

<div class="tab-actions">
    <ul>
        <li>
            <button id="btnBack" type="button"
                    class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                    title="<?php echo __('Back'); ?>">
                <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            </button>
        </li>
        <li>
            <button type="button"
                    class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconCheck()->getClassButton(); ?>"
                    data-onclick="checks/wiki"
                    data-action-route="configWiki/checkDokuwiki"
                    data-src="#frmWiki"
                    title="<?php echo $icons->getIconCheck()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconCheck()->getIcon(); ?></i>
            </button>
        </li>
        <li>
            <button form="frmWiki"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>
