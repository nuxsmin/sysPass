<?php
/** 
* sysPass
* 
* @author nuxsmin
* @link http://syspass.org
* @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
include_once (APP_ROOT."/inc/init.php");

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    SP_Util::logout();
}

( ! isset($_POST["sk"]) || ! SP_Common::checkSessionKey($_POST["sk"]) ) && die('<div class="error round">'._('CONSULTA INVÁLIDA').'</div>');

$startTime = microtime();

$blnAccountLink = SP_Config::getValue('account_link',0);
$intAccountCount = ( isset($_POST["rpp"]) && $_POST["rpp"] > 0 ) ? (int)$_POST["rpp"] : SP_Config::getValue('account_count',10);
$filesEnabled = SP_Config::getValue('filesenabled');
$wikiEnabled = SP_Config::getValue('wikienabled');
$wikiSearchUrl = SP_Config::getValue('wikisearchurl');
$wikiFilter = explode('||',SP_Config::getValue('wikifilter'));
$wikiPageUrl = SP_Config::getValue('wikipageurl');

$sortKey = ( isset($_POST["skey"]) ) ? (int)$_POST["skey"] : 0;
$sortOrder = ( isset($_POST["sorder"]) ) ? SP_Html::sanitize($_POST["sorder"]) : "ASC";
$customerId = ( isset($_POST["customer"]) ) ? (int)$_POST["customer"] : 0;
$categoryId = ( isset($_POST["category"]) ) ? (int)$_POST["category"] : 0;
$searchTxt = ( isset($_POST["search"]) ) ? SP_Html::sanitize($_POST["search"]) : "";
$limitStart = ( isset($_POST["start"]) ) ? (int)$_POST["start"] : 0;    

$userGroupId = (int)$_SESSION["ugroup"];
$userProfileId = (int)$_SESSION["uprofile"];
$userId = (int)$_SESSION["uid"];

$filterOn = ( $sortKey > 1 || $customerId || $categoryId || $searchTxt ) ? TRUE : FALSE;

$objAccount = new SP_Account;
$arrSearchFilter = array("txtSearch" => $searchTxt,
                        "userId" => $userId,
                        "groupId" => $userGroupId,
                        "categoryId" => $categoryId,
                        "customerId" => $customerId,
                        "keyId" => $sortKey,
                        "txtOrder" => $sortOrder,
                        "limitStart" => $limitStart,
                        "limitCount" => $intAccountCount);

$resQuery = $objAccount->getAccounts($arrSearchFilter);

if ( ! $resQuery ) die('<div class="error round">'._('ERROR EN LA CONSULTA').'</div>');
if ( ! is_array($resQuery) ) die('<div class="noRes round">'._('No se encontraron registros').'</div>');

if ( count($resQuery) > 0){
    $sortKeyImg = "";

    if ( $sortKey > 0 ){
        $sortKeyImg = ( $sortOrder == "DESC" ) ? "imgs/sort_asc.png" : "imgs/sort_desc.png";
        $sortKeyImg = '<img src="'.$sortKeyImg.'" class="icon" />';
    }
    
    echo '<div id="data-search-header" class="data-header">';
    echo '<ul class="round header-grey">';
    echo '<li class="header-txt">';
    echo '<a onClick="searchSort(5,'.$limitStart.')" title="'._('Ordenar por Cliente').'" >'._('Cliente').'</a>';
    echo ($sortKey == 5 ) ? $sortKeyImg : '';
    echo '</li>';
    echo '<li class="header-txt">';
    echo '<a onClick="searchSort(1,'.$limitStart.')" title="'._('Ordenar por Nombre').'">'._('Nombre').'</a>';
    echo ($sortKey == 1 ) ? $sortKeyImg : '';
    echo '</li>';        
    echo '<li class="header-txt">';
    echo '<a onClick="searchSort(2,'.$limitStart.')" title="'._('Ordenar por Categoría').'">'._('Categoría').'</a>';
    echo ($sortKey == 2 ) ? $sortKeyImg : '';
    echo '</li>';        
    echo '<li class="header-txt">';
    echo '<a onClick="searchSort(3,'.$limitStart.')" title="'._('Ordenar por Usuario').'">'._('Usuario').'</a>';
    echo ($sortKey == 3 ) ? $sortKeyImg : '';
    echo '</li>';
    echo '<li class="header-txt">';
    echo '<a onClick="searchSort(4,'.$limitStart.')" title="'._('Ordenar por URL / IP').'">'._('URL / IP').'</a>';
    echo ($sortKey == 4 ) ? $sortKeyImg : '';
    echo '</li>';        
    echo '</ul>';
    echo '</div>';
}

echo '<div id="data-search" class="data-rows">';

// Mostrar los resultados de la búsqueda
foreach ( $resQuery as $account ){
    $accView = ( $objAccount->checkAccountAccess("accview", $account->account_userId, $account->account_id, $account->account_userGroupId) 
            && SP_Users::checkUserAccess("accview") );
    $accViewPass = ( $objAccount->checkAccountAccess("accviewpass", $account->account_userId, $account->account_id, $account->account_userGroupId) 
            && SP_Users::checkUserAccess("accviewpass")  );
    $accEdit = ( $objAccount->checkAccountAccess("accedit", $account->account_userId, $account->account_id, $account->account_userGroupId)  
                && SP_Users::checkUserAccess("accedit") );
    $accCopy = ( $objAccount->checkAccountAccess("accview", $account->account_userId, $account->account_id, $account->account_userGroupId) 
            && SP_Users::checkUserAccess("accnew") );
    $accDel = ( $objAccount->checkAccountAccess("accdelete", $account->account_userId, $account->account_id, $account->account_userGroupId) 
            && SP_Users::checkUserAccess("accdelete") );
    
    echo '<ul>';
    echo '<li class="cell-txt txtCliente">';

    if ( $wikiEnabled ){
        $wikiLink = $wikiSearchUrl.$account->customer_name;
        echo '<a href="'.$wikiLink.'" target="blank" title="'._('Buscar en Wiki').'">'.$account->customer_name.'</a>';
    } else{
        echo $account->customer_name;
    }

    echo '</li>';
    echo '<li class="cell-txt">';

    if ( $blnAccountLink == "TRUE" ) {
        // Comprobación de accesos para mostrar enlaces de acciones de cuenta
        if ( $accView ){
            echo '<a title="'._('Detalles de Cuenta').'" OnClick="doAction(\'accview\',\'accsearch\','.$account->account_id.')">'.$account->account_name.'</a>';
        } else {
            echo $account->account_name;
        }
    } else {
        echo $account->account_name;
    }

    echo '</li>';
    echo '<li class="cell-txt">'.$account->category_name.'</li>';
    echo '<li class="cell-txt">';

    $vacLogin =  ( strlen($account->account_login) >= 20 ) ? SP_Html::truncate($account->account_login,20) : $account->account_login;

    echo ($vacLogin) ? $vacLogin : '&nbsp;';

    echo '</li>';
    echo '<li class="cell-txt">';

    $strAccUrl = $account->account_url;
    
    $urlIsLink = ( $strAccUrl &&  preg_match("#^https?://.*#i", $strAccUrl) );
        
    if ( strlen($strAccUrl) >= 25 ){
        $strAccUrl_short = SP_Html::truncate($strAccUrl,25);

        $strAccUrl = ( $urlIsLink ) ? '<a href="'.$strAccUrl.'" target="_blank" title="'._('Abrir enlace a').': '.$strAccUrl.'">'.$strAccUrl_short.'</a>' : $strAccUrl_short;
    } else {
        $strAccUrl = ( $urlIsLink ) ? '<a href="'.$strAccUrl.'" target="_blank" title="'._('Abrir enlace a').': '.$strAccUrl.'">'.$strAccUrl.'</a>' : $strAccUrl;
    }

    echo ( $strAccUrl ) ? $strAccUrl : '';
    echo '</li>';
    
    echo'<li class="cell-img">';
    echo '<img src="imgs/btn_group.png" title="'._('Grupo').': '.$account->usergroup_name.'" />';
    
    $strAccNotes = (strlen($account->account_notes) > 300 ) ? substr($account->account_notes, 0, 300) . "..." : $account->account_notes;
    echo ( $strAccNotes ) ? '<img src="imgs/notes.png" title="'._('Notas').': <br><br>'.  nl2br(wordwrap($strAccNotes,50,'<br>',TRUE)).'" />' : '';
   
    if ( $filesEnabled == 1 ){
        $intNumFiles = SP_Files::countFiles($account->account_id);
        echo ($intNumFiles) ? '<img src="imgs/attach.png" title="'._('Archivos adjuntos').': '.$intNumFiles.'" />' : ''; 
    }
    
    if ( $wikiEnabled ){
        if ( is_array($wikiFilter) ){
            foreach ( $wikiFilter as $strFilter ){
                // Quote filter string
                $strFilter = preg_quote($strFilter);
        
                if ( preg_match("/^".$strFilter.".*/i", $account->account_name) ){
                    $wikiLink = $wikiPageUrl.$account->account_name;
                    echo '<a href="'.$wikiLink.'" target="_blank" ><img src="imgs/wiki.png" title="'._('Enlace a Wiki').'" /></a>';
                }
            }
        }
    }
    
    echo '</li>';
    
    echo '<li class="cell-actions round">';
   
    // Comprobar accesos para mostrar enlaces de acciones de cuenta
    if ( $accView ){
        echo '<img src="imgs/view.png" title="'._('Detalles de Cuenta').'" OnClick="doAction(\'accview\',\'accsearch\','.$account->account_id.')" />';
    }

    if ( $accViewPass  ){
        echo '<img src="imgs/user-pass.png" title="'._('Ver clave').'" onClick="viewPass('.$account->account_id.', 1)" />';
    } 

    if ( $accEdit || $accCopy || $accDel ){
        echo '<img src="imgs/action.png" title="'._('Más Acciones').'" OnClick="showOptional(this)" />';
    }

    if ( $accEdit ){
        echo '<img src="imgs/edit.png" title="'._('Modificar Cuenta').'" class="actions-optional" OnClick="doAction(\'accedit\',\'accsearch\','.$account->account_id.')" />';
    }
    
    if ( $accCopy ){
        echo '<img src="imgs/btn_copy.png" title="'._('Copiar Cuenta').'" class="actions-optional" OnClick="doAction(\'acccopy\',\'accsearch\','.$account->account_id.')" />';
    }

    if ( $accDel ){
        echo '<img src="imgs/delete.png" title="'._('Eliminar Cuenta').'" class="actions-optional" OnClick="doAction(\'accdelete\',\'accsearch\','.$account->account_id.')"/>';
    }

    echo '</li>';
    echo '</ul>';

// Fin del bucle para obtener los registros
}
echo '</div>';

$endTime = microtime();
$totalTime = round($endTime - $startTime, 5);

SP_Html::printQuerySearchNavBar($sortKey, $arrSearchFilter["limitStart"], $objAccount->queryNumRows, $arrSearchFilter["limitCount"], $totalTime, $filterOn);