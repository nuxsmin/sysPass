const mdlDialog = function () {
    function showDialog(options) {
        options = $.extend({
            id: 'orrsDiag',
            title: null,
            text: null,
            neutral: false,
            negative: false,
            positive: false,
            cancelable: true,
            contentStyle: null,
            onLoaded: false,
            hideOther: true
        }, options);

        if (options.hideOther) {
            // remove existing dialogs
            $('.dialog-container').remove();
            $(document).off("keyup.dialog");
        }

        $('<div id="' + options.id + '" class="dialog-container"><div class="mdl-card mdl-shadow--16dp" id="' + options.id + '_content"></div></div>').appendTo("body");
        const dialog = $('#' + options.id);
        const content = dialog.find('.mdl-card');
        if (options.contentStyle !== null) {
            content.css(options.contentStyle);
        }

        if (options.title !== null) {
            $('<header>' + options.title + '</header>').appendTo(content);
        }

        if (options.text !== null) {
            $(options.text).appendTo(content);
        }

        if (options.neutral || options.negative || options.positive) {
            const buttonBar = $('<div class="mdl-card__actions dialog-button-bar"></div>');
            if (options.neutral) {
                options.neutral = $.extend({
                    id: 'neutral',
                    title: 'Neutral',
                    onClick: null
                }, options.neutral);
                var neuButton = $('<button class="mdl-button mdl-js-button mdl-js-ripple-effect" id="' + options.neutral.id + '">' + options.neutral.title + '</button>');
                neuButton.click(function (e) {
                    e.preventDefault();
                    if (options.neutral.onClick === null || !options.neutral.onClick(e)) {
                        hideDialog(dialog);
                    }
                });
                neuButton.appendTo(buttonBar);
            }
            if (options.negative) {
                options.negative = $.extend({
                    id: 'negative',
                    title: 'Cancel',
                    onClick: null
                }, options.negative);
                var negButton = $('<button class="mdl-button mdl-js-button mdl-js-ripple-effect" id="' + options.negative.id + '">' + options.negative.title + '</button>');
                negButton.click(function (e) {
                    e.preventDefault();
                    if (options.negative.onClick === null || !options.negative.onClick(e)) {
                        hideDialog(dialog);
                    }
                });
                negButton.appendTo(buttonBar);
            }
            if (options.positive) {
                options.positive = $.extend({
                    id: 'positive',
                    title: 'OK',
                    onClick: null
                }, options.positive);
                var posButton = $('<button class="mdl-button mdl-button--colored mdl-js-button mdl-js-ripple-effect" id="' + options.positive.id + '">' + options.positive.title + '</button>');
                posButton.click(function (e) {
                    e.preventDefault();
                    if (options.positive.onClick === null || !options.positive.onClick(e)) {
                        hideDialog(dialog);
                    }
                });
                posButton.appendTo(buttonBar);
            }
            buttonBar.appendTo(content);
        }

        if (options.cancelable) {
            dialog.click(function () {
                hideDialog(dialog);
            });
            $(document).on("keyup.dialog", function (e) {
                if (e.which === 27) {
                    hideDialog(dialog);
                }
            });
            content.click(function (e) {
                e.stopPropagation();
            });
        }
        setTimeout(function () {
            if (options.onLoaded) {
                options.onLoaded();
            }

            componentHandler.upgradeDom();
            dialog.css({opacity: 1});
        }, 1);
    }

    function hideDialog(dialog) {
        $(document).off("keyup.dialog");
        dialog.css({opacity: 0});
        setTimeout(function () {
            dialog.remove();
        }, 400);
    }

    return {
        show: showDialog,
        hide: hideDialog
    };
};