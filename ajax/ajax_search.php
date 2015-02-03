<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

define('APP_ROOT', '..');
require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'init.php';

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    SP_Util::logout();
}

$sk = SP_Common::parseParams('p', 'sk', false);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    die('<div class="error round">' . _('CONSULTA INVÁLIDA') . '</div>');
}

$startTime = microtime();

// Valores Configuración
$accountLink = SP_Config::getValue('account_link', 0);
$accountCount = (isset($_POST["rpp"]) && $_POST["rpp"] > 0) ? (int)$_POST["rpp"] : SP_Config::getValue('account_count', 10);
$filesEnabled = SP_Util::fileIsEnabled();
$wikiEnabled = SP_Util::wikiIsEnabled();
if ($wikiEnabled) {
    $wikiSearchUrl = SP_Config::getValue('wiki_searchurl', false);
    $wikiFilter = explode(',', SP_Config::getValue('wiki_filter'));
    $wikiPageUrl = SP_Config::getValue('wiki_pageurl');
}
$requestEnabled = SP_Util::mailrequestIsEnabled();
$maxTextLength = (SP_Util::resultsCardsIsEnabled()) ? 40 : 60;
$isDemoMode = SP_Util::demoIsEnabled();

// Valores POST
$sortKey = SP_Common::parseParams('p', 'skey', 0);
$sortOrder = SP_Common::parseParams('p', 'sorder', 0);
$customerId = SP_Common::parseParams('p', 'customer', 0);
$categoryId = SP_Common::parseParams('p', 'category', 0);
$searchTxt = SP_Common::parseParams('p', 'search', '');
$limitStart = SP_Common::parseParams('p', 'start', 0);
$globalSearch = SP_Common::parseParams('p', 'gsearch', 0, false, 1);

// Valores Sesión
$userGroupId = SP_Common::parseParams('s', 'ugroup', 0);
$userProfileId = SP_Common::parseParams('s', 'uprofile', 0);
$userId = SP_Common::parseParams('s', 'uid', 0);

$filterOn = ($sortKey > 1 || $customerId || $categoryId || $searchTxt) ? true : false;

$colors = array(
    'FF66CC',
    'FF99FF',
    'CC99FF',
    '9999FF',
    '6699FF',
    '3399FF',
    '0099FF',
    '6699FF',
    '3399FF',
    '00CC66',
    '00CC66',
    '00CC99',
    '00CCCC',
    'FFCC66',
    'FF9999',
    'FF6699',
    'FF99CC'
);

$objAccount = new SP_Account;
$arrSearchFilter = array("txtSearch" => $searchTxt,
    "userId" => $userId,
    "groupId" => $userGroupId,
    "categoryId" => $categoryId,
    "customerId" => $customerId,
    "keyId" => $sortKey,
    "txtOrder" => $sortOrder,
    "limitStart" => $limitStart,
    "limitCount" => $accountCount,
    "globalSearch" => $globalSearch);

$resQuery = $objAccount->getAccounts($arrSearchFilter);

if (!$resQuery) {
    die('<div class="noRes round">' . _('No se encontraron registros') . '</div>');
}

if (count($resQuery) > 0) {
    $sortKeyImg = "";

    if ($sortKey > 0) {
        $sortKeyImg = ($sortOrder == 0) ? "imgs/sort_asc.png" : "imgs/sort_desc.png";
        $sortKeyImg = '<img src="' . $sortKeyImg . '" class="icon" />';
    }

    echo '<div id="data-search-header" class="data-header data-header-minimal">';
    echo '<ul>';
    echo '<li>';
    echo '<a id="search-sort-5" class="round" onClick="searchSort(5,' . $limitStart . ')" title="' . _('Ordenar por Cliente') . '" >' . _('Cliente') . '</a>';
    echo '</li>';
    echo '<li>';
    echo '<a id="search-sort-1" class="round" onClick="searchSort(1,' . $limitStart . ')" title="' . _('Ordenar por Nombre') . '">' . _('Nombre') . '</a>';
    echo '</li>';
    echo '<li>';
    echo '<a id="search-sort-2" class="round" onClick="searchSort(2,' . $limitStart . ')" title="' . _('Ordenar por Categoría') . '">' . _('Categoría') . '</a>';
    echo '</li>';
    echo '<li>';
    echo '<a id="search-sort-3" class="round" onClick="searchSort(3,' . $limitStart . ')" title="' . _('Ordenar por Usuario') . '">' . _('Usuario') . '</a>';
    echo '</li>';
    echo '<li>';
    echo '<a id="search-sort-4" class="round" onClick="searchSort(4,' . $limitStart . ')" title="' . _('Ordenar por URL / IP') . '">' . _('URL / IP') . '</a>';
    echo '</li>';
    echo '</ul>';
    echo '</div>';
}

echo '<div id="data-search">';

// Mostrar los resultados de la búsqueda
foreach ($resQuery as $account) {
    $objAccount->accountId = $account->account_id;
    $objAccount->accountUserId = $account->account_userId;
    $objAccount->accountUserGroupId = $account->account_userGroupId;
    $objAccount->accountOtherUserEdit = $account->account_otherUserEdit;
    $objAccount->accountOtherGroupEdit = $account->account_otherGroupEdit;

    $accView = (SP_ACL::checkAccountAccess("accview", $objAccount->getAccountDataForACL()) && SP_ACL::checkUserAccess("accview"));
    $accViewPass = (SP_ACL::checkAccountAccess("accviewpass", $objAccount->getAccountDataForACL()) && SP_ACL::checkUserAccess("accviewpass"));
    $accEdit = (SP_ACL::checkAccountAccess("accedit", $objAccount->getAccountDataForACL()) && SP_ACL::checkUserAccess("accedit"));
    $accCopy = (SP_ACL::checkAccountAccess("accview", $objAccount->getAccountDataForACL()) && SP_ACL::checkUserAccess("accnew"));
    $accDel = (SP_ACL::checkAccountAccess("accdelete", $objAccount->getAccountDataForACL()) && SP_ACL::checkUserAccess("accdelete"));

    $show = ($accView || $accViewPass || $accEdit || $accCopy || $accDel);

    //$randomRGB = array(rand(150, 210), rand(150, 210), rand(150, 210));
    //$color = array($account->account_customerId => array(SP_Html::rgb2hex($randomRGB), $randomRGB));
    $color = array_rand($colors);

    if (!isset($customerColor)) {
        $customerColor[$account->account_customerId] = '#'.$colors[$color];
    } elseif (isset($customerColor) && !array_key_exists($account->account_customerId, $customerColor)) {
        $customerColor[$account->account_customerId] = '#'.$colors[$color];
    }

    //$hexColor = $customerColor[$account->account_customerId][0];
    $hexColor = $customerColor[$account->account_customerId];

    if ($wikiEnabled) {
        $wikiLink = $wikiSearchUrl . $account->customer_name;
        $customerName = '<a href="' . $wikiLink . '" target="blank" title="' . _('Buscar en Wiki') . '<br><br>' . $account->customer_name . '">' . SP_Html::truncate($account->customer_name, $maxTextLength) . '</a>';
    } else {
        $customerName = SP_Html::truncate($account->customer_name, $maxTextLength);
    }

    if ($accountLink && $show) {
        // Comprobación de accesos para mostrar enlaces de acciones de cuenta
        if ($accView) {
            $accountName = '<a title="' . _('Detalles de Cuenta') . '" OnClick="doAction(\'accview\',\'accsearch\',' . $account->account_id . ')">' . $account->account_name . '</a>';
        } else {
            $accountName = $account->account_name;
        }
    } else {
        $accountName = $account->account_name;
    }

    // Obtenemos datos si el usuario tiene acceso a los datos de la cuenta
    if ($show) {
        $vacLogin = (strlen($account->account_login) >= $maxTextLength) ? SP_Html::truncate($account->account_login, $maxTextLength) : $account->account_login;

        $strAccUrl = $account->account_url;
        $urlIsLink = ($strAccUrl && preg_match("#^https?://.*#i", $strAccUrl));

        if (strlen($strAccUrl) >= $maxTextLength) {
            $strAccUrl_short = SP_Html::truncate($strAccUrl, $maxTextLength);

            $strAccUrl = ($urlIsLink) ? '<a href="' . $strAccUrl . '" target="_blank" title="' . _('Abrir enlace a') . ': ' . $strAccUrl . '">' . $strAccUrl_short . '</a>' : $strAccUrl_short;
        } else {
            $strAccUrl = ($urlIsLink) ? '<a href="' . $strAccUrl . '" target="_blank" title="' . _('Abrir enlace a') . ': ' . $strAccUrl . '">' . $strAccUrl . '</a>' : $strAccUrl;
        }

        $secondaryGroups = SP_Groups::getGroupsNameForAccount($account->account_id);
        $secondaryUsers = SP_Users::getUsersNameForAccount($account->account_id);

        $secondaryAccesses = '<em>(G) ' . $account->usergroup_name . '*</em><br>';

        if ($secondaryGroups) {
            foreach ($secondaryGroups as $group) {
                $secondaryAccesses .= '<em>(G) ' . $group . '</em><br>';
            }
        }

        if ($secondaryUsers) {
            foreach ($secondaryUsers as $user) {
                $secondaryAccesses .= '<em>(U) ' . $user . '</em><br>';
            }
        }

        if ($account->account_notes){
            $strAccNotes = (strlen($account->account_notes) > 300) ? substr($account->account_notes, 0, 300) . "..." : $account->account_notes;
            $strAccNotes = nl2br(wordwrap(htmlspecialchars($strAccNotes), 50, '<br>', true));
        }
    }

    //echo '<div class="account-label round shadow" onMouseOver="this.style.backgroundColor=\'RGBA('.$rgbaColor.')\'" onMouseOut="this.style.backgroundColor=\'#FFFFFF\'" >';
    echo '<div class="account-label round shadow">';

    echo '<div class="field-customer label-field">';
    echo '<div class="field-name">' . _('Cliente') . '</div>';
    echo '<div class="field-text round5 no-link" style="background-color: ' . $hexColor . ';">' . $customerName . '</div>';
    echo '</div>';

    echo '<div class="field-account label-field">';
    echo '<div class="field-name">' . _('Nombre') . '</div>';
    echo '<div class="field-text">' . $accountName . '</div>';
    echo '</div>';

    echo '<div class="field-category label-field">';
    echo '<div class="field-name">' . _('Categoría') . '</div>';
    echo '<div class="field-text">' . $account->category_name . '</div>';
    echo '</div>';

    // Mostramos datos si el usuario tiene acceso a los datos de la cuenta
    if ($show) {
        echo '<div class="field-user label-field">';
        echo '<div class="field-name">' . _('Usuario') . '</div>';
        echo '<div class="field-text">' . $vacLogin . '</div>';
        echo '</div>';

        echo '<div class="field-url label-field">';
        echo '<div class="field-name">' . _('URL / IP') . '</div>';
        echo '<div class="field-text">' . $strAccUrl . '</div>';
        echo '</div>';

        echo '<div class="account-info">';
        echo '<img src="imgs/btn_group.png" title="' . $secondaryAccesses . '" />';

        echo (isset($strAccNotes)) ? '<img src="imgs/notes.png" title="' . _('Notas') . ': <br><br>' . $strAccNotes . '" />' : '';

        if ($filesEnabled) {
            $intNumFiles = SP_Files::countFiles($account->account_id);
            echo ($intNumFiles) ? '<img src="imgs/attach.png" title="' . _('Archivos adjuntos') . ': ' . $intNumFiles . '" />' : '';
        }

        if ($wikiEnabled) {
            if (is_array($wikiFilter)) {
                foreach ($wikiFilter as $strFilter) {
                    // Quote filter string
                    $strFilter = preg_quote($strFilter);

                    if (preg_match("/^" . $strFilter . ".*/i", $account->account_name)) {
                        $wikiLink = $wikiPageUrl . $account->account_name;
                        echo '<a href="' . $wikiLink . '" target="_blank" ><img src="imgs/wiki.png" title="' . _('Enlace a Wiki') . '" /></a>';
                    }
                }
            }
        }

        echo '</div>';

        echo '<div class="account-actions round">';

        // Comprobar accesos para mostrar enlaces de acciones de cuenta
        if ($accView) {
            echo '<img src="imgs/view.png" title="' . _('Detalles de Cuenta') . '" OnClick="doAction(\'accview\',\'accsearch\',' . $account->account_id . ')" />';
        }

        if ($accViewPass) {
            echo '<img src="imgs/user-pass.png" title="' . _('Ver Clave') . '" onClick="viewPass(' . $account->account_id . ', 1)" />';
            echo '<img src="imgs/clipboard.png" title="' . _('Copiar Clave en Portapapeles') . '" onmouseover="viewPass(' . $account->account_id . ', 0)" onmouseout="passToClip = 0;" class="actions-optional clip_pass_button" data-clipboard-target="clip_pass_text" />';
        }

        if ($accEdit || $accCopy || $accDel || $accViewPass) {
            echo '<img src="imgs/action.png" title="' . _('Más Acciones') . '" OnClick="showOptional(this)" />';
        }

        if ($accEdit) {
            echo '<img src="imgs/edit.png" title="' . _('Modificar Cuenta') . '" class="actions-optional" OnClick="doAction(\'accedit\',\'accsearch\',' . $account->account_id . ')" />';
        }

        if ($accCopy) {
            echo '<img src="imgs/btn_copy.png" title="' . _('Copiar Cuenta') . '" class="actions-optional" OnClick="doAction(\'acccopy\',\'accsearch\',' . $account->account_id . ')" />';
        }

        if ($accDel) {
            echo '<img src="imgs/delete.png" title="' . _('Eliminar Cuenta') . '" class="actions-optional" OnClick="doAction(\'accdelete\',\'accsearch\',' . $account->account_id . ')"/>';
        }

        echo '</div>';
    } elseif ($requestEnabled || $isDemoMode) {
        echo '<div class="account-spacer"></div>';
        echo '<div class="account-actions round">';
        echo '<img src="imgs/request.png" title="' . _('Solicitar Modificación') . '" class="inputImg" OnClick="doAction(\'accrequest\',\'accsearch\',' . $account->account_id . ')" />';
        echo '</div>';
    }
    echo '</div>';
// Fin del bucle para obtener los registros
}

echo '</div>';

$endTime = microtime();
$totalTime = round($endTime - $startTime, 5);

SP_Html::printQuerySearchNavBar($sortKey, $arrSearchFilter["limitStart"], $objAccount->queryNumRows, $arrSearchFilter["limitCount"], $totalTime, $filterOn);

//echo $objAccount->query;
?>

<div id="clip_pass_text" style="visibility: hidden"></div>

<script>
    passToClip = 0;

    var client = new ZeroClipboard( $('.clip_pass_button'), {
        moviePath: "js/ZeroClipboard.swf",
        debug: false
    });

    //client.setText(data);
    client.on( 'load', function(client) {
        $('#global-zeroclipboard-html-bridge').attr('rel', 'tooltip').attr('title', '<?php echo _('Copiar Clave en Portapapeles'); ?>');
    });

    client.on( "complete", function(client, args) {
        resMsg("ok", "<?php echo _('Clave Copiada al Portapapeles'); ?>");
    });
</script>