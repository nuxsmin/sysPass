var lastlen = 0;

var order = {};
order.key = 0;
order.dir = 0;

// Variable para determinar si una clave de cuenta ha sido copiada al portapapeles
var passToClip = 0;
// Variable para el ajuste óptimo del contenido a la altura del documento
var windowAdjustSize = 350;
// Variable para almacena la llamada a setTimeout()
var timeout;

var passLength;
var minPasswordLength = 8;

var complexity = {};
complexity.numbers = true;
complexity.symbols = true;
complexity.uppercase = true;
complexity.numlength = 10;

jQuery.extend(jQuery.fancybox.defaults, {
    type: 'ajax',
    autoWidth: 'true',
    autoHeight: 'true',
    minHeight: 50,
    padding: 0,
    helpers: {overlay: {css: {'background': 'rgba(0, 0, 0, 0.1)'}}},
    afterShow: function () {
        "use strict";

        $('#fancyContainer').find('input:visible:first').focus();
    }
});

$(document).ready(function () {
    "use strict";

    $('input[type="text"], select, textarea').placeholder().mouseenter(function () {
        $(this).focus();
    });

    setContentSize();
    setWindowAdjustSize();

    // Activar tooltips
    activeTooltip();

}).ajaxComplete(function () {
    "use strict";

    $('input[type="text"], select, textarea').placeholder().mouseenter(function () {
        $(this).focus();
    });

    // Activar tooltips
    activeTooltip();
});


function activeTooltip() {
    "use strict";

    // Activar tooltips
    $('.active-tooltip').tooltip({
        content: function () {
            return $(this).attr('title');
        },
        tooltipClass: "tooltip"
    });

    $('.help-tooltip').tooltip({
        content: function () {
            return $(this).next('div').html();
        },
        tooltipClass: "tooltip"
    });
}

//$(function() {
//    "use strict";
//
//    $.ajaxSetup({
//        error: function(jqXHR, exception) {
//            if (jqXHR.status === 0) {
//                $('#content').fadeIn().html(resMsg("nofancyerror", jqXHR.responseText));
//            } else if (jqXHR.status == 404) {
//                $('#content').fadeIn().html(resMsg("nofancyerror", jqXHR.responseText));
//            } else if (jqXHR.status == 500) {
//                $('#content').fadeIn().html(resMsg("nofancyerror", jqXHR.responseText));
//            } else if (exception === 'parsererror') {
//                $('#content').fadeIn().html(resMsg("nofancyerror", jqXHR.responseText));
//            } else if (exception === 'timeout') {
//                $('#content').fadeIn().html(resMsg("nofancyerror", jqXHR.responseText));
//            } else if (exception === 'abort') {
//                $('#content').fadeIn().html(resMsg("nofancyerror", jqXHR.responseText));
//            } else {
//                $('#content').fadeIn().html(resMsg("nofancyerror", jqXHR.responseText));
//                //alert('Uncaught Error.n' + jqXHR.responseText);
//            }
//        }
//    });
//});

// Función para cargar el contenido de la acción del menú seleccionada
function doAction(actionId, lastAction, itemId) {
    "use strict";

    var data = {'actionId': actionId, 'lastAction': lastAction, 'itemId': itemId, isAjax: 1};

    $.fancybox.showLoading();

    $.ajax({
        type: 'POST',
        dataType: 'html',
        url: APP_ROOT + '/ajax/ajax_getContent.php',
        data: data,
        success: function (response) {
            $('#content').html(response);
        },
        error: function () {
            $('#content').html(resMsg("nofancyerror"));
        },
        complete: function () {
            $.fancybox.hideLoading();
            setContentSize();
        }
    });
}

// Función para establecer la altura del contenedor ajax
function setContentSize() {
    "use strict";

    // Calculate total height for full body resize
    var totalHeight = $("#content").height() + 100;
    //var totalWidth = $("#wrap").width();

    $("#container").css("height", totalHeight);
}

// Función para establecer la variable de ajuste óptimo de altura
function setWindowAdjustSize() {
    "use strict";

    var browser = getBrowser();

    if (browser === "MSIE") {
        windowAdjustSize = 150;
    }
    //console.log(windowAdjustSize);
}

// Función para retornar el scroll a la posición inicial
function scrollUp() {
    "use strict";

    $('html, body').animate({scrollTop: 0}, 'slow');
}

// Función para limpiar un formulario
function clearSearch(clearStart) {
    "use strict";

    if (clearStart === 1) {
        $('#frmSearch').find('input[name="start"]').val(0);
        return;
    }

    document.frmSearch.search.value = "";
    $('#frmSearch').find('select').prop('selectedIndex', 0).trigger("chosen:updated");
    $('#frmSearch').find('input[name="start"], input[name="skey"], input[name="sorder"]').val(0);
    order.key = 0;
    order.dir = 0;
}

// Función para la búsqueda de cuentas mediante filtros
function accSearch(continous, event) {
    "use strict";

    var lenTxtSearch = $('#txtSearch').val().length;

    if (typeof (event) !== 'undefined' && ((event.keyCode < 48 && event.keyCode !== 13) || (event.keyCode > 105 && event.keyCode < 123))) {
        return;
    }

    if (lenTxtSearch < 3 && continous === 1 && lenTxtSearch > window.lastlen && event.keyCode !== 13) {
        return;
    }

    window.lastlen = lenTxtSearch;

    $('#frmSearch').find('input[name="start"]').val(0);

    doSearch();
}

// Función para la búsqueda de cuentas mediante ordenación
function searchSort(skey, start, nav) {
    "use strict";

    if (typeof(skey) === "undefined" || typeof(start) === "undefined") return false;

    if (order.key > 0 && order.key != skey) {
        order.key = skey;
        order.dir = 0;
    } else if (nav != 1) {
        order.key = skey;

        if (order.dir === 1) {
            order.dir = 0;
        } else {
            order.dir = 1;
        }
    }

    $('#frmSearch').find('input[name="skey"]').val(order.key);
    $('#frmSearch').find('input[name="sorder"]').val(order.dir);
    $('#frmSearch').find('input[name="start"]').val(start);

    doSearch();
}

// Función para la búsqueda de cuentas
function doSearch() {
    "use strict";

    var frmData = $("#frmSearch").serialize();

    $.fancybox.showLoading();

    $.ajax({
        type: 'POST',
        dataType: 'html',
        url: APP_ROOT + '/ajax/ajax_search.php',
        data: frmData,
        success: function (response) {
            $('#resBuscar').html(response);
            $('#resBuscar').css("max-height", $('html').height() - windowAdjustSize);
        },
        error: function () {
            $('#resBuscar').html(resMsg("nofancyerror"));
        },
        complete: function () {
            scrollUp();
            $.fancybox.hideLoading();
        }
    });
}

// Mostrar el orden de campo y orden de búsqueda utilizados
function showSearchOrder() {
    "use strict";

    if (order.key) {
        $('#search-sort-' + order.key).addClass('filterOn');
        if (order.dir === 0) {
            $('#search-sort-' + order.key).append('<img src="imgs/arrow_down.png" style="width:17px;height:12px;" />');
        } else {
            $('#search-sort-' + order.key).append('<img src="imgs/arrow_up.png" style="width:17px;height:12px;" />');
        }
    }
}

// Función para navegar por el log de eventos
function navLog(start, current) {
    "use strict";

    if (typeof(start) === "undefined") return false;

    $.fancybox.showLoading();

    $.ajax({
        type: 'POST',
        dataType: 'html',
        url: APP_ROOT + '/ajax/ajax_eventlog.php',
        data: {'start': start, 'current': current},
        success: function (response) {
            $('#content').html(response);
        },
        error: function () {
            $('#content').html(resMsg("nofancyerror"));
        },
        complete: function () {
            $.fancybox.hideLoading();
            scrollUp();
            setContentSize();
        }
    });
}

// Función para ver la clave de una cuenta
function viewPass(id, full, history) {
    "use strict";

    // Comprobamos si la clave ha sido ya obtenida para copiar
    if (passToClip === 1 && full === 0) {
        return;
    }

    $.ajax({
        type: 'POST',
        url: APP_ROOT + '/ajax/ajax_viewpass.php',
        dataType: "json",
        async: false,
        data: {'accountid': id, 'full': full, 'isHistory': history, 'isAjax': 1},
        success: function (json) {

            if (json.status === 10) {
                doLogout();
                return;
            }

            if (full === false) {
                // Copiamos la clave en el objeto que tiene acceso al portapapeles
                $('#clip-pass-text').html(json.accpass);
                passToClip = 1;
                return;
            }

            $('<div></div>').dialog({
                modal: true,
                title: json.title,
                width: 'auto',
                open: function () {
                    var content;
                    var pass = '';
                    var clipboardUserButton =
                        '<button id="dialog-clip-user-button-' + id + '" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary">' +
                        '<span class="ui-button-icon-primary ui-icon ui-icon-clipboard"></span>' +
                        '<span class="ui-button-text">' + LANG[33] + '</span>' +
                        '</button>';
                    var clipboardPassButton =
                        '<button id="dialog-clip-pass-button-' + id + '" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary">' +
                        '<span class="ui-button-icon-primary ui-icon ui-icon-clipboard"></span>' +
                        '<span class="ui-button-text">' + LANG[34] + '</span>' +
                        '</button>';
                    var useImage = json.useimage;

                    if (json.status === 0) {
                        if (useImage === 0) {
                            pass = '<p class="dialog-pass-text">' + json.accpass + '</p>';
                        } else {
                            pass = '<img class="dialog-pass-text" src="data:image/png;base64,' + json.accpass + '" />';
                            clipboardPassButton = '';
                        }

                        content = pass + '<br>' + '<div class="dialog-buttons">' + clipboardUserButton + clipboardPassButton + '</div>';
                    } else {
                        content = '<span class="altTxtRed">' + json.description + '</span>';

                        $(this).dialog("option", "buttons",
                            [{
                                text: "Ok",
                                icons: {primary: "ui-icon-close"}, click: function () {
                                    $(this).dialog("close");
                                }
                            }]
                        );
                    }

                    $(this).html(content);

                    // Recentrar después de insertar el contenido
                    $(this).dialog('option', 'position', 'center');

                    // Carga de objeto flash para copiar al portapapeles
                    var clientPass = new ZeroClipboard($("#dialog-clip-pass-button-" + id), {swfPath: APP_ROOT + "/js/ZeroClipboard.swf"});
                    var clientUser = new ZeroClipboard($("#dialog-clip-user-button-" + id), {swfPath: APP_ROOT + "/js/ZeroClipboard.swf"});

                    clientPass.on('ready', function (e) {
                        $("#dialog-clip-pass-button-" + id).attr("data-clip", 1);
                        clientPass.on('copy', function (e) {
                            //e.clipboardData.setData('text/plain', json.accpass);
                            clientPass.setText(json.accpass);
                        });
                        clientPass.on('aftercopy', function (e) {
                            $('.dialog-pass-text').addClass('dialog-clip-pass-copy round');
                        });
                    });

                    clientPass.on('error', function (e) {
                        ZeroClipboard.destroy();
                    });

                    clientUser.on('ready', function (e) {
                        clientUser.on('copy', function (e) {
                            clientUser.setText(json.acclogin);
                        });
                    });

                    // Timeout del mensaje
                    var thisDialog = $(this);
                    timeout = setTimeout(function () {
                        thisDialog.dialog('close');
                    }, 30000);
                },
                // Forzar la eliminación del objeto para que ZeroClipboard siga funcionando al abrirlo de nuevo
                close: function () {
                    clearTimeout(timeout);
                    $(this).dialog("destroy");
                }
            });
        }
    });
}

// Función para obtener las variables de la URL y parsearlas a un array.
function getUrlVars() {
    "use strict";

    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

// Función para autentificar usuarios
function doLogin() {
    "use strict";

    $.fancybox.showLoading();

    var data = $('#frmLogin').serialize();

    $("#btnLogin").prop('disabled', true);

    $.ajax({
        type: "POST",
        dataType: "json",
        url: APP_ROOT + '/ajax/ajax_doLogin.php',
        data: data,
        success: function (json) {
            var status = json.status;
            var description = json.description;

            if (status === 0 || status === 2) {
                location.href = description;
            } else if (status === 3 || status === 4) {
                resMsg("error", description);
                $("#mpass").prop('disabled', false);
                $('#smpass').show();
            } else if (status === 5) {
                resMsg("warn", description, '', "location.href = 'index.php';");
            } else {
                $('#user').val('').focus();
                $('#pass').val('');
                resMsg("error", description);
            }
        },
        complete: function () {
            $('#btnLogin').prop('disabled', false);
            $.fancybox.hideLoading();
        },
        statusCode: {
            404: function () {
                var txt = LANG[1] + '<p>' + LANG[13] + '</p>';
                resMsg("error", txt);
            }
        }
    });

    return false;
}

// Función para salir de la sesión
function doLogout() {
    "use strict";

    var url = window.location.search;

    if (url.length > 0) {
        location.href = 'index.php' + url + '&logout=1';
    } else {
        location.href = 'index.php?logout=1';
    }
}

// Función para comprobar si se ha salido de la sesión
function checkLogout() {
    "use strict";

    var session = getUrlVars()["session"];

    if (session === 0) {
        resMsg("warn", LANG[2], '', "location.search = ''");
    }
}

// Función para añadir/editar una cuenta
function saveAccount(frm) {
    "use strict";

    var data = $("#" + frm).serialize();
    var id = $('input[name="accountid"]').val();
    var savetyp = $('input[name="savetyp"]').val();
    var action = $('input[name="next"]').val();

    $('#btnGuardar').attr('disabled', true);
    $.fancybox.showLoading();

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: APP_ROOT + '/ajax/ajax_accountSave.php',
        data: data,
        success: function (json) {
            var status = json.status;
            var description = json.description;

            if (status === 0) {
                resMsg("ok", description);

                if (savetyp === 1) {
                    $('#btnSave').hide();
                } else {
                    $('#btnSave').attr('disabled', true);
                }

                if (action && id) {
                    doAction(action, 'accsearch', id);
                }
            } else if (status === 10) {
                doLogout();
            } else {
                resMsg("error", description);
                $('#btnSave').removeAttr("disabled");
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            var txt = LANG[1] + '<p>' + errorThrown + textStatus + '</p>';
            resMsg("error", txt);
        },
        complete: function () {
            $.fancybox.hideLoading();
        }
    });
}

// Función para eliminar una cuenta
function delAccount(id, action, sk) {
    "use strict";

    var data = {accountid: id, actionId: action, sk: sk};
    var atext = '<div id="alert"><p id="alert-text">' + LANG[3] + '</p></div>';
    var url = '/ajax/ajax_accountSave.php';

    alertify.confirm(atext, function (e) {
        if (e) {
            sendAjax(data, url);
        }
    });
}

// Función para enviar una solicitud de modificación de cuenta
function sendRequest() {
    "use strict";

    var url = '/ajax/ajax_sendRequest.php';
    var data = $('#frmRequestModify').serialize();

    sendAjax(data, url);
}

// Función para guardar la configuración
function configMgmt(action, obj) {
    "use strict";

    var url;

    switch (action) {
        case "config":
            url = '/ajax/ajax_configSave.php';
            break;
        case "export":
            url = '/ajax/ajax_backup.php';
            break;
        case "import":
            url = '/ajax/ajax_migrate.php';
            break;
        default:
            return;
    }

    var data = $(obj).serialize();

    sendAjax(data, url);
}

// Función para descargar/ver archivos de una cuenta
function downFile(id, sk, action) {
    "use strict";

    var data = {'fileId': id, 'sk': sk, 'action': action};

    if (action === 'view') {
        $.fancybox.showLoading();

        $.ajax({
            type: "POST",
            cache: false,
            url: APP_ROOT + "/ajax/ajax_files.php",
            data: data,
            success: function (response) {
                if (response) {
                    $.fancybox(response, {padding: [10, 10, 10, 10]});
                    // Actualizar fancybox para adaptarlo al tamaño de la imagen
                    setTimeout(function () {
                        $.fancybox.update();
                    }, 1000);
                } else {
                    resMsg("error", LANG[14]);
                }

            },
            complete: function () {
                $.fancybox.hideLoading();
            }
        });
    } else if (action === 'download') {
        $.fileDownload(APP_ROOT + '/ajax/ajax_files.php', {'httpMethod': 'POST', 'data': data});
    }
}

// Función para obtener la lista de archivos de una cuenta
function getFiles(id, isDel, sk) {
    "use strict";

    var data = {'id': id, 'del': isDel, 'sk': sk};

    $.ajax({
        type: "GET",
        cache: false,
        url: APP_ROOT + "/ajax/ajax_getFiles.php",
        data: data,
        success: function (response) {
            $('#downFiles').html(response);
        },
        complete: function () {
            $.fancybox.hideLoading();
        }
    });
}

// Función para eliminar archivos de una cuenta
function delFile(id, sk, accid) {
    "use strict";

    var atext = '<div id="alert"><p id="alert-text">' + LANG[15] + '</p></div>';

    alertify.confirm(atext, function (e) {
        if (e) {
            $.fancybox.showLoading();

            var data = {'fileId': id, 'action': 'delete', 'sk': sk};

            $.post(APP_ROOT + '/ajax/ajax_files.php', data,
                function (data) {
                    $.fancybox.hideLoading();
                    resMsg("ok", data);
                    $("#downFiles").load(APP_ROOT + "/ajax/ajax_getFiles.php?id=" + accid + "&del=1&isAjax=1&sk=" + sk);
                }
            );
        }
    });
}

// Función para activar el Drag&Drop de archivos en las cuentas
function dropFile(accountId, sk, maxsize) {
    "use strict";

    var dropfiles = $('#dropzone');
    var file_exts_ok = dropfiles.attr('data-files-ext').toLowerCase().split(',');

    dropfiles.filedrop({
        fallback_id: 'inFile',
        paramname: 'inFile',
        maxfiles: 5,
        maxfilesize: maxsize,
        allowedfileextensions: file_exts_ok,
        url: APP_ROOT + '/ajax/ajax_files.php',
        data: {
            sk: sk,
            accountId: accountId,
            action: 'upload',
            isAjax: 1
        },
        uploadFinished: function (i, file, response) {
            $.fancybox.hideLoading();

            var sk = $('input[name="sk"]').val();
            $("#downFiles").load(APP_ROOT + "/ajax/ajax_getFiles.php?id=" + accountId + "&del=1&isAjax=1&sk=" + sk);

            resMsg("ok", response);
        },
        error: function (err, file) {
            switch (err) {
                case 'BrowserNotSupported':
                    resMsg("error", LANG[16]);
                    break;
                case 'TooManyFiles':
                    resMsg("error", LANG[17] + ' (max. ' + this.maxfiles + ')');
                    break;
                case 'FileTooLarge':
                    resMsg("error", LANG[18] + ' ' + maxsize + ' MB' + '<br>' + file.name);
                    break;
                case 'FileExtensionNotAllowed':
                    resMsg("error", LANG[19]);
                    break;
                default:
                    break;
            }
        },
        uploadStarted: function (i, file, len) {
            $.fancybox.showLoading();
        }
    });
}

// Función para activar el Drag&Drop de archivos en la importación de cuentas
function importFile(sk) {
    "use strict";

    var dropfiles = $('#dropzone');
    var file_exts_ok = ['csv', 'xml'];

    dropfiles.filedrop({
        fallback_id: 'inFile',
        paramname: 'inFile',
        maxfiles: 1,
        maxfilesize: 1,
        allowedfileextensions: file_exts_ok,
        url: APP_ROOT + '/ajax/ajax_import.php',
        data: {
            sk: sk,
            action: 'import',
            isAjax: 1,
            importPwd: function () {
                return $('input[name="importPwd"]').val();
            },
            defUser: function () {
                return $('#import_defaultuser').chosen().val();
            },
            defGroup: function () {
                return $('#import_defaultgroup').chosen().val();
            },
            csvDelimiter: function () {
                return $('input[name="csvDelimiter"]').val();
                ;
            }
        },
        uploadFinished: function (i, file, json) {
            $.fancybox.hideLoading();

            var status = json.status;
            var description = json.description;

            if (status === 0) {
                resMsg("ok", description);
            } else if (status === 10) {
                resMsg("error", description);
                doLogout();
            } else {
                resMsg("error", description);
            }
        },
        error: function (err, file) {
            switch (err) {
                case 'BrowserNotSupported':
                    resMsg("error", LANG[16]);
                    break;
                case 'TooManyFiles':
                    resMsg("error", LANG[17] + ' (max. ' + this.maxfiles + ')');
                    break;
                case 'FileTooLarge':
                    resMsg("error", LANG[18] + '<br>' + file.name);
                    break;
                case 'FileExtensionNotAllowed':
                    resMsg("error", LANG[19]);
                    break;
                default:
                    break;
            }
        },
        uploadStarted: function (i, file, len) {
            $.fancybox.showLoading();
        }
    });
}

// Función para realizar una petición ajax
function sendAjax(data, url) {
    "use strict";

    $.fancybox.showLoading();

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: APP_ROOT + url,
        data: data,
        success: function (json) {
            var status = json.status;
            var description = json.description;
            var action = json.action;

            switch (status) {
                case 0:
                    $.fancybox.close();
                    resMsg("ok", description, undefined, action);
                    break;
                case 1:
                    $.fancybox.close();
                    resMsg("error", description, undefined, action);
                    break;
                case 2:
                    $("#resFancyAccion").html('<span class="altTxtError">' + description + '</span>').show();
                    break;
                case 3:
                    $.fancybox.close();
                    resMsg("warn", description, undefined, action);
                    break;
                case 10:
                    doLogout();
                    break;
                default:
                    return;
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            var txt = LANG[1] + '<p>' + errorThrown + textStatus + '</p>';
            resMsg("error", txt);
        },
        complete: function () {
            $.fancybox.hideLoading();
        }
    });
}

// Función para mostrar el formulario para cambio de clave de usuario
function usrUpdPass(object, actionId, sk) {
    "use strict";

    var userId = $(object).attr("data-itemid");

    var data = {'userId': userId, 'actionId': actionId, 'sk': sk, 'isAjax': 1};

    $.fancybox.showLoading();

    $.ajax({
        type: "GET",
        cache: false,
        url: APP_ROOT + '/ajax/ajax_usrpass.php',
        data: data,
        success: function (data) {
            if (data.length === 0) {
                doLogout();
            } else {
                $.fancybox(data, {padding: 0});
            }
        }
    });
}

// Función para mostrar los datos de un registro
function appMgmtData(obj, actionId, sk) {
    "use strict";

    var itemId = $(obj).attr('data-itemid');
    var activeTab = $(obj).attr('data-activetab');

    var data = {'itemId': itemId, 'actionId': actionId, 'sk': sk, 'activeTab': activeTab, 'isAjax': 1};
    var url = APP_ROOT + '/ajax/ajax_appMgmtData.php';

    $.fancybox.showLoading();

    $.ajax({
        type: 'POST',
        dataType: 'html',
        url: url,
        data: data,
        success: function (response) {
            $.fancybox(response, {padding: [0, 10, 10, 10]});
        },
        error: function (jqXHR, textStatus, errorThrown) {
            var txt = LANG[1] + '<p>' + errorThrown + textStatus + '</p>';
            resMsg("error", txt);
        },
        complete: function () {
            $.fancybox.hideLoading();
        }
    });
}

// Función para borrar un registro
function appMgmtDelete(obj, actionId, sk) {
    "use strict";

    var itemId = $(obj).attr('data-itemid');
    var activeTab = $(obj).attr('data-activetab');
    var nextActionId = $(obj).attr('data-nextactionid');
    var atext = '<div id="alert"><p id="alert-text">' + LANG[12] + '</p></div>';

    var url = '/ajax/ajax_appMgmtSave.php';
    var data = {
        'itemId': itemId,
        'actionId': actionId,
        'sk': sk,
        'activeTab': activeTab,
        'onCloseAction': nextActionId
    };

    alertify.confirm(atext, function (e) {
        if (e) {
            sendAjax(data, url);
        }
    });
}

// Función para editar los datos de un registro
function appMgmtSave(frmId) {
    "use strict";

    var url = '/ajax/ajax_appMgmtSave.php';
    var data = $("#" + frmId).serialize();

    sendAjax(data, url);
}

// Función para verificar si existen actualizaciones
function checkUpds() {
    "use strict";

    $.ajax({
        type: 'GET',
        dataType: 'html',
        url: APP_ROOT + '/ajax/ajax_checkUpds.php',
        timeout: 10000,
        success: function (response) {
            $('#updates').html(response);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $('#updates').html('!');
        }
    });
}

// Función para limpiar el log de eventos
function clearEventlog(sk) {
    "use strict";

    var atext = '<div id="alert"><p id="alert-text">' + LANG[20] + '</p></div>';

    alertify.confirm(atext, function (e) {
        if (e) {
            var data = {'clear': 1, 'sk': sk, 'isAjax': 1};
            var url = '/ajax/ajax_eventlog.php';

            sendAjax(data, url);
        }
    });
}

// Función para mostrar los botones de acción en los resultados de búsqueda
function showOptional(me) {
    "use strict";

    $(me).hide();
    //$(me).parent().css('width','15em');
    //var actions =  $(me).closest('.account-actions').children('.actions-optional');
    var actions = $(me).parent().children('.actions-optional');
    actions.show(250);
}

// Función para obtener el tiempo actual en milisegundos
function getTime() {
    "use strict";

    var t = new Date();
    return t.getTime();
}

// Función para generar claves aleatorias. 
// By Uzbekjon from  http://jquery-howto.blogspot.com.es
function password(length, special, fancy, targetId) {
    "use strict";

    var iteration = 0;
    var genPassword = '';
    var randomNumber;

    while (iteration < complexity.numlength) {
        randomNumber = (Math.floor((Math.random() * 100)) % 94) + 33;
        if (!complexity.symbols) {
            if ((randomNumber >= 33) && (randomNumber <= 47)) {
                continue;
            }
            if ((randomNumber >= 58) && (randomNumber <= 64)) {
                continue;
            }
            if ((randomNumber >= 91) && (randomNumber <= 96)) {
                continue;
            }
            if ((randomNumber >= 123) && (randomNumber <= 126)) {
                continue;
            }
        }

        if (!complexity.numbers && randomNumber >= 48 && randomNumber <= 57) {
            continue;
        }

        if (!complexity.uppercase && randomNumber >= 65 && randomNumber <= 90) {
            continue;
        }

        iteration++;
        genPassword += String.fromCharCode(randomNumber);
    }

    if (fancy === true) {
        $("#viewPass").attr("title", genPassword);
        //alertify.alert('<div id="alert"><p id="alert-text">' + LANG[6] + '</p><p id="alert-pass"> ' + password + '</p>');
    } else {
        alertify.alert('<div id="alert"><p id="alert-text">' + LANG[6] + '</p><p id="alert-pass"> ' + genPassword + '</p>');
    }

    var level = zxcvbn(genPassword);
    passLength = genPassword.length;

    if (targetId) {
        outputResult(level.score, targetId);

        $('#' + targetId).val(genPassword);
        $('#' + targetId + 'R').val(genPassword);
    } else {
        outputResult(level.score, targetId);

        $('input:password, input.password').val(genPassword);
        $('#passLevel').show(500);
    }
}

// Funciones para analizar al fortaleza de una clave
// From http://net.tutsplus.com/tutorials/javascript-ajax/build-a-simple-password-strength-checker/
function checkPassLevel(password, dst) {
    "use strict";

    var level = zxcvbn(password);

    outputResult(level.score, dst);
}

function outputResult(level, dstId) {
    "use strict";

    var complexity, selector = '.passLevel-' + dstId;

    complexity = $(selector);
    complexity.removeClass("weak good strong strongest");

    if (passLength === 0) {
        complexity.attr('title', '').empty();
    } else if (passLength < minPasswordLength) {
        complexity.attr('title', LANG[11]).addClass("weak");
    } else if (level === 0) {
        complexity.attr('title', LANG[9]).addClass("weak");
    } else if (level === 1 || level === 2) {
        complexity.attr('title', LANG[8]).addClass("good");
    } else if (level === 3) {
        complexity.attr('title', LANG[7]).addClass("strong");
    } else if (level === 4) {
        complexity.attr('title', LANG[10]).addClass("strongest");
    }

    //$('.passLevel').powerTip(powertipOptions);
}

// Función para mostrar mensaje con alertify
function resMsg(type, txt, url, action) {
    "use strict";

    if (typeof url !== "undefined") {
        $.ajax({
            url: url, type: 'get', dataType: 'html', async: false, success: function (data) {
                txt = data;
            }
        });
    }

    var html;

    txt = txt.replace(/(\\n|;;)/g, "<br>");

    switch (type) {
        case "ok":
            alertify.set({beforeCloseAction: action});
            return alertify.success(txt);
        case "error":
            alertify.set({beforeCloseAction: action});
            return alertify.error(txt);
        case "warn":
            alertify.set({beforeCloseAction: action});
            return alertify.log(txt);
        case "info":
            html = '<div id="fancyMsg" class="msgInfo">' + txt + '</div>';
            break;
        case "none":
            html = txt;
            break;
        case "nofancyerror":
            html = '<p class="error round">Oops...<br>' + LANG[1] + '<br>' + txt + '</p>';
            return html;
        default:
            alertify.set({beforeCloseAction: action});
            return alertify.error(txt);
    }

    $.fancybox(html, {
        afterLoad: function () {
            $('.fancybox-skin,.fancybox-outer,.fancybox-inner').css({
                'border-radius': '25px',
                '-moz-border-radius': '25px',
                '-webkit-border-radius': '25px'
            });
        }, afterClose: function () {
            if (typeof action !== "undefined") {
                eval(action);
            }
        }
    });
}

// Función para comprobar la conexión con LDAP
function checkLdapConn() {
    "use strict";

    var ldapServer = $('#frmConfig').find('[name=ldap_server]').val();
    var ldapBase = $('#frmConfig').find('[name=ldap_base]').val();
    var ldapGroup = $('#frmConfig').find('[name=ldap_group]').val();
    var ldapBindUser = $('#frmConfig').find('[name=ldap_binduser]').val();
    var ldapBindPass = $('#frmConfig').find('[name=ldap_bindpass]').val();
    var sk = $('#frmConfig').find('[name=sk]').val();
    var data = {
        'ldap_server': ldapServer,
        'ldap_base': ldapBase,
        'ldap_group': ldapGroup,
        'ldap_binduser': ldapBindUser,
        'ldap_bindpass': ldapBindPass,
        'isAjax': 1,
        'sk': sk
    };

    sendAjax(data, '/ajax/ajax_checkLdap.php');
}

// Función para volver al login
function goLogin() {
    "use strict";

    setTimeout(function () {
        location.href = "index.php";
    }, 2000);
}

// Diálogo de configuración de complejidad de clave
function complexityDialog(targetId) {
    $('<div id="dialog-complexity"></div>').dialog({
        modal: true,
        title: 'Opciones de Complejidad',
        width: '450px',
        open: function () {
            var thisDialog = $(this);

            var content =
                '<div class="dialog-btns-complexity">' +
                '<label for="checkbox-numbers">' + LANG[35] + '</label>' +
                '<input type="checkbox" id="checkbox-numbers" name="checkbox-numbers"/>' +
                '<label for="checkbox-uppercase">' + LANG[36] + '</label>' +
                '<input type="checkbox" id="checkbox-uppercase" name="checkbox-uppercase"/>' +
                '<label for="checkbox-symbols">' + LANG[37] + '</label>' +
                '<input type="checkbox" id="checkbox-symbols" name="checkbox-symbols"/>' +
                '</div>' +
                '<div class="dialog-length-complexity">' +
                '<label for="passlength">' + LANG[38] + '</label>' +
                '<input class="inputNumber" pattern="[0-9]*" id="passlength" />' +
                '</div>' +
                '<div class="dialog-buttons">' +
                '<button class="btnDialogOk">Ok</button>' +
                '</div>';

            thisDialog.html(content);

            // Recentrar después de insertar el contenido
            thisDialog.dialog('option', 'position', 'center');

            // Actualizar componentes de MDL
            thisDialog.ready(function () {
                $('#checkbox-numbers').prop('checked', complexity.numbers);
                $('#checkbox-uppercase').prop('checked', complexity.uppercase);
                $('#checkbox-symbols').prop('checked', complexity.symbols);
                $('#passlength').val(complexity.numlength);

                $(".dialog-btns-complexity").buttonset({
                    icons: {primary: "ui-icon-transferthick-e-w"}
                });

                $(".inputNumber").spinner();

                $(".btnDialogOk")
                    .button()
                    .click(function () {
                        complexity.numbers = $(' #checkbox-numbers').is(':checked');
                        complexity.uppercase = $('#checkbox-uppercase').is(':checked');
                        complexity.symbols = $('#checkbox-symbols').is(':checked');
                        complexity.numlength = parseInt($('#passlength').val());

                        console.log(thisDialog);
                        thisDialog.dialog('close');
                    }
                );
            });
        },
        // Forzar la eliminación del objeto para que ZeroClipboard siga funcionando al abrirlo de nuevo
        close: function () {
            $(this).dialog("destroy");
        }
    });
}

// Función para obtener el navegador usado
function getBrowser() {
    "use strict";

    var browser;
    var ua = navigator.userAgent;
    var re = new RegExp("(MSIE|Firefox)[ /]?([0-9]{1,}[\.0-9]{0,})", "i");
    if (re.exec(ua) !== null) {
        browser = RegExp.$1;
        //version = parseFloat( RegExp.$2 );
    }

    return browser;
}

// Detectar los campos select y añadir funciones
function chosenDetect() {
    "use strict";

    var selectWidth = "210px";
    var searchTreshold = 10;

    $(".sel-chosen-usergroup").chosen({
        placeholder_text_single: LANG[21],
        disable_search_threshold: searchTreshold,
        no_results_text: LANG[26],
        width: selectWidth
    });

    $(".sel-chosen-user").chosen({
        placeholder_text_single: LANG[22],
        disable_search_threshold: searchTreshold,
        no_results_text: LANG[26],
        width: selectWidth
    });

    $(".sel-chosen-profile").chosen({
        placeholder_text_single: LANG[23],
        disable_search_threshold: searchTreshold,
        no_results_text: LANG[26],
        width: selectWidth
    });

    $(".sel-chosen-action").chosen({
        placeholder_text_single: LANG[23],
        disable_search_threshold: searchTreshold,
        no_results_text: LANG[26],
        width: selectWidth
    });

    $(".sel-chosen-customer").each(function () {
        var deselect = $(this).hasClass('sel-chosen-deselect');

        $(this).chosen({
            allow_single_deselect: deselect,
            placeholder_text_single: LANG[24],
            disable_search_threshold: searchTreshold,
            no_results_text: LANG[26],
            width: selectWidth
        });
    });

    $(".sel-chosen-category").each(function () {
        var deselect = $(this).hasClass('sel-chosen-deselect');

        $(this).chosen({
            allow_single_deselect: deselect,
            placeholder_text_single: LANG[25],
            disable_search_threshold: searchTreshold,
            no_results_text: LANG[26],
            width: selectWidth
        });
    });

    $(".sel-chosen-ns").chosen({disable_search: true, width: selectWidth});
}

// Detectar los campos de clave y añadir funciones
function passwordDetect() {
    "use strict";

    // Crear los iconos de acciones sobre claves
    $('.passwordfield__input').each(function () {
        var thisInput = $(this);
        var targetId = $(this).attr('id');

        if (thisInput.next().hasClass('password-actions')) {
            return;
        }

        console.log(targetId);

        var btnMenu = '<div><button type="button" class="quickGenPass" data-targetid="' + targetId + '">' + LANG[28] + '</button>';
        btnMenu += '<button type="button" class="genPassActions">' + LANG[27] + '</button></div>';
        btnMenu += '<ul>';
        btnMenu += '<li data-targetid="' + targetId + '" class="passGen">' + LANG[28] + '</li>';
        btnMenu += '<li data-targetid="' + targetId + '" class="passComplexity">' + LANG[29] + '</li>';
        btnMenu += '<li data-targetid="' + targetId + '" class="reset">' + LANG[30] + '</li>';
        btnMenu += '</ul>';

        thisInput.after('<div class="password-actions" />');

        thisInput.next('.password-actions')
            .prepend(btnMenu)
            .prepend('<img class="showpass inputImg" src="imgs/show.png" title="' + LANG[32] + '" data-targetid="' + targetId + '" />')
            .prepend('<span class="passLevel passLevel-' + targetId + ' fullround" title="' + LANG[31] + '"></span>');

        $(".quickGenPass")
            .button({
                text: false,
                icons: {
                    primary: "ui-icon-gear"
                }
            })
            .click(function () {
                password(11, true, true, targetId);
            })
            .next()
            .button({
                text: false,
                icons: {
                    primary: "ui-icon-key"
                }
            })
            .click(function () {
                var menu = $(this).parent().next().show().position({
                    my: "left top",
                    at: "left bottom",
                    of: this
                });
                $(document).one("click", function () {
                    menu.hide();
                });
                return false;
            })
            .parent()
            .buttonset()
            .next()
            .hide()
            .menu();


        $(this).on('keyup', function () {
            checkPassLevel($(this).val(), targetId);
        });
    });

    // Crear los iconos de acciones sobre claves (sólo mostrar clave)
    $('.passwordfield__input-show').each(function () {
        var thisParent = $(this);
        var targetId = $(this).attr('id');

        thisParent
            .after('<img class="showpass inputImg" src="imgs/show.png" title="' + LANG[32] + '" data-targetid="' + targetId + '" />');
    });

    // Crear evento para generar clave aleatoria
    $('.passGen').each(function () {
        $(this).on('click', function () {
            var targetId = $(this).data('targetid');
            password(11, true, true, targetId);
        });
    });

    $('.passComplexity').each(function () {
        $(this).on('click', function () {
            complexityDialog();
        });
    });

    // Crear evento para mostrar clave generada/introducida
    $('.showpass').each(function () {
        $(this).on('mouseover', function () {
            var targetId = $(this).data('targetid');
            $(this).attr('title', $('#' + targetId).val());
        });
    });

    // Reset de los campos de clave
    $('.reset').each(function () {
        $(this).on('click', function () {
            var targetId = $(this).data('targetid');
            $('#' + targetId).val('');
            $('#' + targetId + 'R').val('');
        });
    });
}

/**
 * Detectar los imputs del tipo checkbox para generar botones
 *
 * @param container El contenedor donde buscar
 */
function checkboxDetect(container){
    "use strict";

    $(container).find('.checkbox').button({
        icons: {primary: "ui-icon-transferthick-e-w"}
    }).click(
        function () {
            if ($(this).prop('checked') == true) {
                $(this).button('option', 'label', LANG[40]);
            } else {
                $(this).button('option', 'label', LANG[41]);
            }
        }
    );
}