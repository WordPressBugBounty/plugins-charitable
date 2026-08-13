/**
 * Charitable Mini Donation Widget — Frontend
 *
 * Handles tab switching, amount selection, impact text, validation, and CTA.
 *
 * @package Charitable
 * @since   1.8.12
 */
(function($) {
    'use strict';

    /**
     * Initialize all mini widgets on the page.
     */
    function initAll() {
        $('[data-charitable-mini-widget]').each(function() {
            var $widget = $(this);
            if ($widget.data('cmw-initialized')) {
                return;
            }
            $widget.data('cmw-initialized', true);
            initWidget($widget);
        });
    }

    /**
     * Initialize a single widget instance.
     *
     * @param {jQuery} $widget The widget wrapper element.
     */
    function initWidget($widget) {
        var config;
        try {
            config = JSON.parse($widget.attr('data-charitable-mini-widget'));
        } catch (e) {
            return;
        }

        // Current state.
        var state = {
            tab: config.recurringActive ? 'monthly' : 'onetime',
            amount: null,
            customAmount: null,
            isOther: false
        };

        // Set initial selected amount.
        var tabCfg = config[state.tab];
        state.amount = tabCfg['default'] || (tabCfg.amounts.length ? tabCfg.amounts[0] : 0);

        // Tab clicks.
        $widget.on('click', '.charitable-mini-widget__tab', function() {
            var newTab = $(this).data('tab');
            if (newTab === state.tab) {
                return;
            }
            state.tab = newTab;
            state.isOther = false;
            state.customAmount = null;

            // Update tab UI.
            $widget.find('.charitable-mini-widget__tab').removeClass('charitable-mini-widget__tab--active').attr('aria-selected', 'false');
            $(this).addClass('charitable-mini-widget__tab--active').attr('aria-selected', 'true');

            // Reset to default for new tab.
            var newTabCfg = config[newTab];
            state.amount = newTabCfg['default'] || (newTabCfg.amounts.length ? newTabCfg.amounts[0] : 0);

            // Re-render amounts.
            renderAmounts($widget, config, state);
            hideOtherInput($widget);
            updateImpact($widget, config, state);
            updateCta($widget, config, state);
        });

        // Amount button clicks.
        $widget.on('click', '.charitable-mini-widget__amount', function() {
            var $btn = $(this);
            var amountVal = $btn.data('amount');

            if (amountVal === 'other') {
                state.isOther = true;
                state.amount = null;
                $widget.find('.charitable-mini-widget__amount').removeClass('charitable-mini-widget__amount--selected');
                $btn.addClass('charitable-mini-widget__amount--selected');
                showOtherInput($widget, config, state);
                updateImpact($widget, config, state);
                updateCta($widget, config, state);
                return;
            }

            state.isOther = false;
            state.amount = parseFloat(amountVal);
            state.customAmount = null;

            $widget.find('.charitable-mini-widget__amount').removeClass('charitable-mini-widget__amount--selected');
            $btn.addClass('charitable-mini-widget__amount--selected');

            hideOtherInput($widget);
            clearError($widget);
            updateImpact($widget, config, state);
            updateCta($widget, config, state);
        });

        // Other amount input.
        $widget.on('input', '.charitable-mini-widget__other-input', function() {
            var val = parseFloat($(this).val());
            state.customAmount = isNaN(val) ? null : val;
            validateOther($widget, config, state);
            updateCta($widget, config, state);
        });

        // CTA click — redirect mode.
        $widget.on('click', '.charitable-mini-widget__cta:not(.charitable-modal-trigger)', function(e) {
            e.preventDefault();
            if (!canDonate(config, state)) {
                return;
            }
            var amount = getEffectiveAmount(state);
            var period = state.tab === 'monthly' ? 'month' : 'once';
            var url = config.campaignUrl;
            url += (url.indexOf('?') >= 0 ? '&' : '?') +
                'amount=' + encodeURIComponent(amount) +
                '&period=' + encodeURIComponent(period);
            window.location.href = url;
        });

        // CTA click — modal mode: update data-* before modal JS handles the click.
        $widget.on('click', '.charitable-mini-widget__cta.charitable-modal-trigger', function() {
            if (!canDonate(config, state)) {
                return false;
            }
            var amount = getEffectiveAmount(state);
            var period = state.tab === 'monthly' ? 'month' : 'once';
            $(this)
                .attr('data-amount', amount)
                .attr('data-period', period);
            // Allow event to propagate to charitable-universal-modal.js.
        });

        // Initial render.
        renderAmounts($widget, config, state);
        updateImpact($widget, config, state);
        updateCta($widget, config, state);
    }

    /**
     * Re-render the amount buttons for the current tab.
     */
    function renderAmounts($widget, config, state) {
        var tabCfg = config[state.tab];
        var $container = $widget.find('.charitable-mini-widget__amounts');
        $container.empty();

        $.each(tabCfg.amounts, function(i, amount) {
            var isSelected = (parseFloat(amount) === parseFloat(state.amount) && !state.isOther);
            var $btn = $('<button>')
                .attr('type', 'button')
                .attr('data-amount', amount)
                .addClass('charitable-mini-widget__amount')
                .toggleClass('charitable-mini-widget__amount--selected', isSelected);

            // Amount number display.
            var displayAmount = config.currencyPos === 'right'
                ? formatAmount(amount) + config.currencySymbol
                : config.currencySymbol + formatAmount(amount);

            var $num = $('<span>').addClass('charitable-mini-widget__amount-number').text(displayAmount);
            $btn.append($num);

            // Currency label.
            if (config.showCurrency) {
                var period = (state.tab === 'monthly' && config.recurringActive) ? '/mo' : '';
                var $curr = $('<span>').addClass('charitable-mini-widget__amount-currency').text(config.currencyCode + period);
                $btn.append($curr);
            }

            $container.append($btn);
        });

        // Other button.
        if (tabCfg.showOther) {
            var $other = $('<button>')
                .attr('type', 'button')
                .attr('data-amount', 'other')
                .addClass('charitable-mini-widget__amount charitable-mini-widget__amount--other')
                .toggleClass('charitable-mini-widget__amount--selected', state.isOther)
                .text(wpCharitableMiniWidget.i18n.other || 'Other');
            $container.append($other);
        }
    }

    /**
     * Format a number as a clean amount string (no trailing .00).
     */
    function formatAmount(amount) {
        var n = parseFloat(amount);
        return n === Math.floor(n) ? String(Math.floor(n)) : n.toFixed(2);
    }

    /**
     * Show the "Other amount" input.
     */
    function showOtherInput($widget, config, state) {
        var $wrap = $widget.find('.charitable-mini-widget__other-wrap');
        var $input = $wrap.find('.charitable-mini-widget__other-input');

        // Update placeholder for current period.
        var periodHint = (state.tab === 'monthly' && config.recurringActive)
            ? (wpCharitableMiniWidget.i18n.enterAmountMonthly || 'Enter amount/mo')
            : (wpCharitableMiniWidget.i18n.enterAmount || 'Enter amount');
        $input.attr('placeholder', periodHint).val('');

        $wrap.show();
        $input.focus();
    }

    /**
     * Hide the "Other amount" input.
     */
    function hideOtherInput($widget) {
        $widget.find('.charitable-mini-widget__other-wrap').hide();
        $widget.find('.charitable-mini-widget__other-input').val('');
        clearError($widget);
    }

    /**
     * Validate the custom amount against min/max.
     * Returns true if valid.
     */
    function validateOther($widget, config, state) {
        var val = state.customAmount;
        var $wrap = $widget.find('.charitable-mini-widget__other-wrap');
        var $error = $widget.find('.charitable-mini-widget__error');

        clearError($widget);

        if (val === null || isNaN(val) || val <= 0) {
            return false;
        }

        if (config.minAmount !== null && config.minAmount !== '' && val < parseFloat(config.minAmount)) {
            $wrap.addClass('charitable-mini-widget__other-wrap--error');
            $error.text(
                (wpCharitableMiniWidget.i18n.minError || 'Minimum donation is ') +
                config.currencySymbol + formatAmount(config.minAmount) + '.'
            );
            return false;
        }

        if (config.maxAmount !== null && config.maxAmount !== '' && val > parseFloat(config.maxAmount)) {
            $wrap.addClass('charitable-mini-widget__other-wrap--error');
            $error.text(
                (wpCharitableMiniWidget.i18n.maxError || 'Maximum donation is ') +
                config.currencySymbol + formatAmount(config.maxAmount) + '.'
            );
            return false;
        }

        return true;
    }

    /**
     * Clear validation error state.
     */
    function clearError($widget) {
        $widget.find('.charitable-mini-widget__other-wrap').removeClass('charitable-mini-widget__other-wrap--error');
        $widget.find('.charitable-mini-widget__error').text('');
    }

    /**
     * Update the impact text for the currently selected amount.
     */
    function updateImpact($widget, config, state) {
        var $impact = $widget.find('.charitable-mini-widget__impact');

        if (state.isOther) {
            $impact.text('');
            return;
        }

        var tabCfg = config[state.tab];
        var key = String(state.amount);
        // Try integer key too (e.g. "40" for amount 40.0).
        var intKey = String(Math.floor(state.amount));
        var text = tabCfg.impact[key] || tabCfg.impact[intKey] || '';

        $impact.text(text); // text() is XSS-safe; HTML was stripped server-side.
    }

    /**
     * Check if donation can proceed.
     */
    function canDonate(config, state) {
        if (state.isOther) {
            return validateOtherValue(config, state);
        }
        return state.amount > 0;
    }

    /**
     * Validate without updating DOM (used in canDonate).
     */
    function validateOtherValue(config, state) {
        var val = state.customAmount;
        if (val === null || isNaN(val) || val <= 0) {
            return false;
        }
        if (config.minAmount !== null && config.minAmount !== '' && val < parseFloat(config.minAmount)) {
            return false;
        }
        if (config.maxAmount !== null && config.maxAmount !== '' && val > parseFloat(config.maxAmount)) {
            return false;
        }
        return true;
    }

    /**
     * Get the effective amount to donate.
     */
    function getEffectiveAmount(state) {
        return state.isOther ? state.customAmount : state.amount;
    }

    /**
     * Update CTA button enabled/disabled state.
     */
    function updateCta($widget, config, state) {
        var $cta = $widget.find('.charitable-mini-widget__cta');
        var able = canDonate(config, state);
        $cta.prop('disabled', !able).toggleClass('charitable-mini-widget__cta--disabled', !able);
    }

    // Initialize on DOM ready and after AJAX content loads.
    $(document).ready(function() {
        initAll();
    });

    $(document).on('charitable:content:loaded', function() {
        initAll();
    });

    // Expose for external init.
    window.CharitableMiniWidget = { init: initAll };

})(jQuery);
