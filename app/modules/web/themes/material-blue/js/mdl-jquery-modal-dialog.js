const mdlDialog = function () {
    let lastTrigger = null;

    function getFocusableElements(container) {
        if (!container || container.length === 0 || typeof container[0].querySelectorAll !== "function") {
            return [];
        }
        const nodes = Array.prototype.slice.call(
            container[0].querySelectorAll(
                "a[href], button:not([disabled]), input:not([disabled]):not([type='hidden']), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex='-1'])"
            )
        );
        return nodes.filter(function (el) {
            return !el.hasAttribute("disabled")
                && el.getAttribute("aria-hidden") !== "true"
                && (el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length > 0);
        });
    }

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

        if (typeof document !== "undefined" && document.activeElement && document.activeElement !== document.body) {
            lastTrigger = document.activeElement;
        } else {
            lastTrigger = null;
        }

        if (options.hideOther) {
            // remove existing dialogs
            $('.dialog-container').remove();
            $(document).off("keyup.dialog keydown.dialogTrap");
        }

        const headerId = options.id + '_title';
        $('<div id="' + options.id + '" class="dialog-container"><div class="mdl-card mdl-shadow--16dp sp-modal-card" id="' + options.id + '_content" role="dialog" aria-modal="true"></div></div>').appendTo("body");
        const dialog = $('#' + options.id);
        const content = dialog.find('.mdl-card');
        if (options.contentStyle !== null) {
            content.css(options.contentStyle);
        }

        if (options.title !== null) {
            $('<header id="' + headerId + '">' + options.title + '</header>').appendTo(content);
            content.attr("aria-labelledby", headerId);
        }

        if (options.text !== null) {
            const $body = $('<div class="sp-modal-body"></div>');
            $body.append(options.text);
            $body.appendTo(content);
        }

        if (options.neutral || options.negative || options.positive) {
            const buttonBar = $('<div class="mdl-card__actions dialog-button-bar"></div>');
            if (options.neutral) {
                options.neutral = $.extend({
                    id: 'neutral',
                    title: 'Neutral',
                    onClick: null
                }, options.neutral);
                var neuButton = $('<button type="button" class="mdl-button mdl-js-button mdl-js-ripple-effect sp-btn-secondary" id="' + options.neutral.id + '">' + options.neutral.title + '</button>');
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
                var negButton = $('<button type="button" class="mdl-button mdl-js-button mdl-js-ripple-effect sp-btn-secondary" id="' + options.negative.id + '">' + options.negative.title + '</button>');
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
                var posButton = $('<button type="button" class="mdl-button mdl-button--colored mdl-js-button mdl-js-ripple-effect sp-btn-primary" id="' + options.positive.id + '">' + options.positive.title + '</button>');
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
                if (e.which === 27 || e.key === "Escape") {
                    hideDialog(dialog);
                }
            });
            content.click(function (e) {
                e.stopPropagation();
            });
        }

        $(document).on("keydown.dialogTrap", function (e) {
            if (e.key !== "Tab") {
                return;
            }
            const focusables = getFocusableElements(content);
            if (focusables.length === 0) {
                e.preventDefault();
                return;
            }
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const active = typeof document !== "undefined" ? document.activeElement : null;

            if (e.shiftKey) {
                if (active === first || !content[0].contains(active)) {
                    e.preventDefault();
                    last.focus();
                }
            } else if (active === last || !content[0].contains(active)) {
                e.preventDefault();
                first.focus();
            }
        });

        setTimeout(function () {
            if (options.onLoaded) {
                options.onLoaded();
            }

            componentHandler.upgradeDom();
            dialog.css({opacity: 1});

            const focusables = getFocusableElements(content);
            const positiveBtn = content.find("#positive")[0];
            if (positiveBtn && focusables.length === 1) {
                positiveBtn.focus();
            } else if (focusables.length > 0 && typeof focusables[0].focus === "function") {
                focusables[0].focus();
            }
        }, 1);
    }

    function hideDialog(dialog) {
        $(document).off("keyup.dialog keydown.dialogTrap");
        dialog.css({opacity: 0});
        const triggerToFocus = lastTrigger;
        lastTrigger = null;
        setTimeout(function () {
            dialog.remove();
            if (triggerToFocus && typeof triggerToFocus.focus === "function") {
                if (typeof document === "undefined" || !document.contains || document.contains(triggerToFocus)) {
                    triggerToFocus.focus();
                }
            }
        }, 400);
    }

    return {
        show: showDialog,
        hide: hideDialog
    };
};