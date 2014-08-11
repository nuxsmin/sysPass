var lastlen = 0;

var order = {};
order.key = 0;
order.dir = 0;

// Variable para determinar si una clave de cuenta ha sido copiada al portapapeles
var passToClip = 0;
// Variable para el ajuste óptimo del contenido a la altura del documento
var windowAdjustSize = 350;

var strPassword;
var minPasswordLength = 8;
var baseScore = 0, score = 0;

var num = {};
num.Excess = 0;
num.Upper = 0;
num.Numbers = 0;
num.Symbols = 0;

var bonus = {};
bonus.Excess = 3;
bonus.Upper = 4;
bonus.Numbers = 5;
bonus.Symbols = 5;
bonus.Combo = 0;
bonus.FlatLower = 0;
bonus.FlatNumber = 0;

var powertipOptions = {placement: 'ne', smartPlacement: 'true', fadeOutTime: 500};

jQuery.extend(jQuery.fancybox.defaults, {
    type: 'ajax',
    autoWidth: 'true',
    autoHeight: 'true',
    minHeight: 50,
    padding: 0,
    helpers: {overlay: { css: { 'background': 'rgba(0, 0, 0, 0.1)'}}},
    afterShow: function () {
        "use strict";

        $('#fancyContainer').find('input:visible:first').focus();
    }
});

$(document).ready(function () {
    "use strict";

    $("[title]").powerTip(powertipOptions);
    $('input, textarea').placeholder();
    setContentSize();
    setWindowAdjustSize();
}).ajaxComplete(function () {
    "use strict";

    $("[title]").powerTip(powertipOptions);
    $('input, textarea').placeholder();
});

// Función para cargar el contenido de la acción del menú seleccionada
function doAction(action, lastAction, id) {
    "use strict";

    var data = {'action': action, 'lastAction': lastAction, 'id': id, isAjax: 1};

    $('#content').fadeOut(function () {
        $.fancybox.showLoading();

        $.ajax({
            type: 'POST',
            dataType: 'html',
            url: APP_ROOT + '/ajax/ajax_getContent.php',
            data: data,
            success: function (response) {
                $('#content').fadeIn().html(response);
                setContentSize();
            },
            error: function () {
                $('#content').html(resMsg("nofancyerror"));
            },
            complete: function () {
                $.fancybox.hideLoading();
            }
        });
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

    $('html, body').animate({ scrollTop: 0 }, 'slow');
}

// Función para limpiar un formulario
function clearSearch(clearStart) {
    "use strict";

    if (clearStart === 1) {
        $('#frmSearch').find('input[name="start"]').val(0);
        return;
    }

    document.frmSearch.search.value = "";
    document.frmSearch.customer.selectedIndex = 0;
    document.frmSearch.category.selectedIndex = 0;
    $('#frmSearch').find('input[name="start"]').val(0);
    $('#frmSearch').find('input[name="skey"]').val(0);
    $('#frmSearch').find('input[name="sorder"]').val(0);
    $(".select-box").val('').trigger("chosen:updated");
    order.key = 0;
    order.dir = 0;
}

// Funcion para crear un desplegable con opciones
function mkChosen(options) {
    "use strict";

    $('#' + options.id).chosen({
        allow_single_deselect: true,
        placeholder_text_single: options.placeholder,
        disable_search_threshold: 10,
        no_results_text: options.noresults
    });
}

// Función para la búsqueda de cuentas mediante filtros
function accSearch(continous, event) {
    "use strict";

    var lenTxtSearch = $('#txtSearch').val().length;

    if (typeof (event) !== 'undefined' && ((event.keyCode < 48 && event.keyCode !== 13) || (event.keyCode > 105 && event.keyCode < 123))) {
        return;
    }

    if (lenTxtSearch < 3 && continous === 1 && lenTxtSearch > window.lastlen && event.keyCode != 13) {
        return;
    }

    window.lastlen = lenTxtSearch;

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

            if (order.key) {
                $('#search-sort-' + order.key).addClass('filterOn');
                if (order.dir === 0) {
                    $('#search-sort-' + order.key).append('<img src="imgs/arrow_down.png" style="width:17px;height:12px;" />');
                } else {
                    $('#search-sort-' + order.key).append('<img src="imgs/arrow_up.png" style="width:17px;height:12px;" />');
                }
            }
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
        async: false,
        data: {'accountid': id, 'full': full, 'isHistory': history, 'isAjax': 1},
        success: function (data) {
            if (data === "-1") {
                doLogout();
            } else {
                if (full === 0) {
                    // Copiamos la clave en el objeto que tiene acceso al portapapeles
                    $('#clip_pass_text').html(data);
                    passToClip = 1;
                } else {
                    resMsg("none", data);
                }
            }
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
            }}
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

    var data = {accountid: id, savetyp: action, sk: sk};
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
function configMgmt(action) {
    "use strict";

    var frm, url;

    switch (action) {
        case "saveconfig":
            frm = 'frmConfig';
            url = '/ajax/ajax_configSave.php';
            break;
        case "savempwd":
            frm = 'frmCrypt';
            url = '/ajax/ajax_configSave.php';
            break;
        case "backup":
            frm = 'frmBackup';
            url = '/ajax/ajax_backup.php';
            break;
        case "migrate":
            frm = 'frmMigrate';
            url = '/ajax/ajax_migrate.php';
            break;
        default:
            return;
    }

    var data = $('#' + frm).serialize();

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
            isAjax: 1
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
function usrUpdPass(id, usrlogin) {
    "use strict";

    var data = {'usrid': id, 'usrlogin': usrlogin, 'isAjax': 1};

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
function appMgmtData(id, type, sk, active, view) {
    "use strict";

    var data = {'id': id, 'type': type, 'sk': sk, 'active': active, 'view': view, 'isAjax': 1};
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

// Función para editar los datos de un registro
function appMgmtSave(frmId, isDel, id, type, sk, nextaction) {
    "use strict";

    var data;
    var url = '/ajax/ajax_appMgmtSave.php';

    if (isDel === 1) {
        data = {'id': id, 'type': type, 'action': 4, 'sk': sk, 'activeTab': frmId, 'onCloseAction': nextaction };
        var atext = '<div id="alert"><p id="alert-text">' + LANG[12] + '</p></div>';

        alertify.confirm(atext, function (e) {
            if (e) {
                sendAjax(data, url);
            }
        });
    } else {
        data = $("#" + frmId).serialize();
        sendAjax(data, url);
    }
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
            var data = { 'clear': 1, 'sk': sk, 'isAjax': 1};
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
function password(length, special, fancy, dstId) {
    "use strict";

    var iteration = 0;
    var genPassword = '';
    var randomNumber;

    if (typeof special === 'undefined') {
        special = false;
    }

    while (iteration < length) {
        randomNumber = (Math.floor((Math.random() * 100)) % 94) + 33;
        if (!special) {
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
        iteration++;
        genPassword += String.fromCharCode(randomNumber);
    }

    if (fancy === true) {
        $("#viewPass").attr("title", genPassword);
        //alertify.alert('<div id="alert"><p id="alert-text">' + LANG[6] + '</p><p id="alert-pass"> ' + password + '</p>');
    } else {
        alertify.alert('<div id="alert"><p id="alert-text">' + LANG[6] + '</p><p id="alert-pass"> ' + genPassword + '</p>');
    }

    if (dstId) {
        checkPassLevel(genPassword);
        $('#' + dstId + ' input:password').val(genPassword);
        $('#' + dstId + ' #passLevel').show(500);
    } else {
        checkPassLevel(genPassword);
        $('input:password').val(genPassword);
        $('#passLevel').show(500);
    }
    //return password;
}

// Funciones para analizar al fortaleza de una clave
// From http://net.tutsplus.com/tutorials/javascript-ajax/build-a-simple-password-strength-checker/
function checkPassLevel(password, dstId) {
    "use strict";

    strPassword = password;

    num.Excess = 0;
    num.Upper = 0;
    num.Numbers = 0;
    num.Symbols = 0;
    bonus.Combo = 0;
    bonus.FlatLower = 0;
    bonus.FlatNumber = 0;
    baseScore = 0;
    score = 0;

    if (password.length >= minPasswordLength) {
        baseScore = 50;
        analyzeString();
        calcComplexity();
    } else {
        baseScore = 0;
    }

    if (dstId) {
        outputResult(dstId);
    } else {
        outputResult(dstId);
    }
}

function analyzeString() {
    "use strict";

    var chars = strPassword.split('');

    for (var i = 0; i < strPassword.length; i++) {
        if (chars[i].match(/[A-Z]/g)) {
            num.Upper++;
        }
        if (chars[i].match(/[0-9]/g)) {
            num.Numbers++;
        }
        if (chars[i].match(/(.*[!,@,#,$,%,&,*,?,%,_])/)) {
            num.Symbols++;
        }
    }

    num.Excess = strPassword.length - minPasswordLength;

    if (num.Upper && num.Numbers && num.Symbols) {
        bonus.Combo = 25;
    }

    else if ((num.Upper && num.Numbers) || (num.Upper && num.Symbols) || (num.Numbers && num.Symbols)) {
        bonus.Combo = 15;
    }

    if (strPassword.match(/^[\sa-z]+$/)) {
        bonus.FlatLower = -15;
    }

    if (strPassword.match(/^[\s0-9]+$/)) {
        bonus.FlatNumber = -35;
    }
}

function calcComplexity() {
    "use strict";

    score = baseScore + (num.Excess * bonus.Excess) + (num.Upper * bonus.Upper) + (num.Numbers * bonus.Numbers) + (num.Symbols * bonus.Symbols) + bonus.Combo + bonus.FlatLower + bonus.FlatNumber;
}

function outputResult(dstId) {
    "use strict";

    var complexity, selector = '.passLevel';

    if (dstId) {
        selector = '#' + dstId + ' .passLevel';
    }

    complexity = $(selector);
    complexity.removeClass("weak good strong strongest");

    if (strPassword.length === 0) {
        complexity.attr('title', '').empty();
    } else if (strPassword.length < minPasswordLength) {
        complexity.attr('title', LANG[11]).addClass("weak");
    } else if (score < 50) {
        complexity.attr('title', LANG[9]).addClass("weak");
    } else if (score >= 50 && score < 75) {
        complexity.attr('title', LANG[8]).addClass("good");
    } else if (score >= 75 && score < 100) {
        complexity.attr('title', LANG[7]).addClass("strong");
    } else if (score >= 100) {
        complexity.attr('title', LANG[10]).addClass("strongest");
    }

    $('.passLevel').powerTip(powertipOptions);
}

// Función para mostrar mensaje con alertify
function resMsg(type, txt, url, action) {
    "use strict";

    if (typeof url !== "undefined") {
        $.ajax({ url: url, type: 'get', dataType: 'html', async: false, success: function (data) {
            txt = data;
        }});
    }

    var html;

    txt = txt.replace(/(\\n|;;)/g, "<br>");

    switch (type) {
        case "ok":
            alertify.set({ beforeCloseAction: action });
            return alertify.success(txt);
        case "error":
            alertify.set({ beforeCloseAction: action });
            return alertify.error(txt);
        case "warn":
            alertify.set({ beforeCloseAction: action });
            return alertify.log(txt);
        case "info":
            html = '<div id="fancyMsg" class="msgInfo">' + txt + '</div>';
            break;
        case "none":
            html = txt;
            break;
        case "nofancyerror":
            html = '<P CLASS="error round">Oops...<BR />' + LANG[1] + '<BR />' + txt + '</P>';
            return html;
        default:
            alertify.set({ beforeCloseAction: action });
            return alertify.error(txt);
    }

    $.fancybox(html, {afterLoad: function () {
        $('.fancybox-skin,.fancybox-outer,.fancybox-inner').css({'border-radius': '25px', '-moz-border-radius': '25px', '-webkit-border-radius': '25px'});
    }, afterClose: function () {
        if (typeof action !== "undefined") {
            eval(action);
        }
    } });
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
    var data = {'ldap_server': ldapServer, 'ldap_base': ldapBase, 'ldap_group': ldapGroup, 'ldap_binduser': ldapBindUser, 'ldap_bindpass': ldapBindPass, 'isAjax': 1, 'sk': sk};

    sendAjax(data, '/ajax/ajax_checkLdap.php');
}

// Función para volver al login
function goLogin() {
    "use strict";

    setTimeout(function () {
        location.href = "index.php";
    }, 2000);
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