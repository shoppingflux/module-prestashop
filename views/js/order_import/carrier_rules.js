/*
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from SARL 202 ecommence
* Use, copy, modification or distribution of this source file without written
* license agreement from the SARL 202 ecommence is strictly forbidden.
* In order to obtain a license, please contact us: tech@202-ecommerce.com
* ...........................................................................
* INFORMATION SUR LA LICENCE D'UTILISATION
*
* L'utilisation de ce fichier source est soumise a une licence commerciale
* concedee par la societe 202 ecommence
* Toute utilisation, reproduction, modification ou distribution du present
* fichier source sans contrat de licence ecrit de la part de la SARL 202 ecommence est
* expressement interdite.
* Pour obtenir une licence, veuillez contacter 202-ecommerce <tech@202-ecommerce.com>
* ...........................................................................
*
* @author    202-ecommerce <tech@202-ecommerce.com>
* @copyright Copyright (c) 202-ecommerce
* @license   Commercial license
*/

/**
 * Carrier selection rules builder (#34133).
 *
 * Data comes from the shoppingfeedCarrierRules JS def (see
 * AdminShoppingfeedOrderImportRulesController::getCarrierRulesJsData()).
 * The rules are serialized as JSON in the hidden input
 * shoppingfeed_carrier_rules when the form is submitted.
 */
$(document).ready(function () {
    if (typeof shoppingfeedCarrierRules === 'undefined' || !$('#shoppingfeed_carrier-rules-app').length) {
        return;
    }

    var data = shoppingfeedCarrierRules;
    var t = data.trans;
    var LIST_OPERATORS = ['in', 'not_in'];
    var MAX_SUGGESTIONS = 10;
    var $app = $('#shoppingfeed_carrier-rules-app');
    var $input = $('#shoppingfeed_carrier-rules-input');
    var $switchInputs = $('input[name="' + data.switchName + '"]');
    var rules = $.isArray(data.rules) ? data.rules : [];
    var collapsed = {};
    var focusCondition = null;

    // --- state helpers ---------------------------------------------------

    function defaultValue(criterion, operator) {
        if (LIST_OPERATORS.indexOf(operator) !== -1) {
            return [];
        }
        return '';
    }

    function newCondition(criterion) {
        criterion = criterion || 'country';
        var operator = data.operators[criterion][0];
        if (data.operators[criterion].indexOf('in') !== -1) {
            operator = 'in';
        }
        return {criterion: criterion, operator: operator, value: defaultValue(criterion, operator)};
    }

    function newGroup() {
        return {conditions: [newCondition()]};
    }

    function newRule() {
        return {
            id_shoppingfeed_token: data.tokens.length ? data.tokens[0].id : 0,
            id_carrier_reference: 0,
            groups: [newGroup()]
        };
    }

    function suggestionsFor(criterion) {
        return data.suggestions[criterion] || [];
    }

    function labelFor(criterion, value) {
        var suggestions = suggestionsFor(criterion);
        for (var i = 0; i < suggestions.length; i++) {
            if (String(suggestions[i].value).toUpperCase() === String(value).toUpperCase()) {
                return suggestions[i].label;
            }
        }
        return value;
    }

    // --- rendering -------------------------------------------------------

    function select(options, selected, cssClass) {
        var $select = $('<select/>').addClass('form-control ' + (cssClass || ''));
        $.each(options, function (i, option) {
            $('<option/>').val(option.value).text(option.label)
                .prop('selected', String(option.value) === String(selected))
                .appendTo($select);
        });
        return $select;
    }

    function renderSuggestions($wrapper, criterion, query, onPick) {
        $wrapper.find('.sf-cr-suggestions').remove();
        query = $.trim(query).toLowerCase();
        if (!query) {
            return;
        }
        var matches = $.grep(suggestionsFor(criterion), function (suggestion) {
            return String(suggestion.label).toLowerCase().indexOf(query) === 0
                || String(suggestion.value).toLowerCase().indexOf(query) === 0;
        }).slice(0, MAX_SUGGESTIONS);
        if (!matches.length) {
            return;
        }
        var $list = $('<ul class="sf-cr-suggestions"/>');
        $.each(matches, function (i, suggestion) {
            var label = String(suggestion.label);
            $('<li/>')
                .append($('<strong/>').text(label.substr(0, query.length)))
                .append(document.createTextNode(label.substr(query.length)))
                .on('mousedown', function (e) {
                    e.preventDefault();
                    onPick(suggestion);
                })
                .appendTo($list);
        });
        $wrapper.append($list);
    }

    function renderTags(condition) {
        var $wrapper = $('<div class="sf-cr-tags form-control"/>');
        var $text = $('<input type="text" class="sf-cr-tag-input"/>').attr('placeholder', t.typeToSearch);

        function addTag(value) {
            value = $.trim(value);
            if (value === '') {
                return;
            }
            var exists = $.grep(condition.value, function (v) {
                return String(v).toLowerCase() === value.toLowerCase();
            }).length;
            if (!exists) {
                condition.value.push(value);
            }
            focusCondition = condition;
            render();
        }

        $.each(condition.value, function (i, value) {
            $('<span class="sf-cr-tag"/>')
                .text(labelFor(condition.criterion, value))
                .append($('<a href="#" class="sf-cr-tag-remove">&times;</a>').on('click', function (e) {
                    e.preventDefault();
                    condition.value.splice(i, 1);
                    render();
                }))
                .appendTo($wrapper);
        });

        $text
            .on('input', function () {
                renderSuggestions($wrapper, condition.criterion, $(this).val(), function (suggestion) {
                    addTag(String(suggestion.value));
                });
            })
            .on('keydown', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    var $first = $wrapper.find('.sf-cr-suggestions li').first();
                    if ($first.length) {
                        $first.trigger('mousedown');
                    } else if (condition.criterion !== 'country') {
                        // free text is allowed except for countries (ISO codes are checked on save)
                        addTag($(this).val());
                    }
                }
            })
            .on('blur', function () {
                $wrapper.find('.sf-cr-suggestions').remove();
            });

        if (focusCondition === condition) {
            focusCondition = null;
            setTimeout(function () {
                $text.focus();
            }, 0);
        }

        return $wrapper.append($text);
    }

    function renderValue(condition) {
        if (condition.criterion === 'weight') {
            return $('<div class="input-group"/>')
                .append($('<input type="text" class="form-control"/>').val(condition.value).on('input', function () {
                    condition.value = $(this).val();
                }))
                .append($('<span class="input-group-addon"/>').text(data.weightUnit));
        }
        if (LIST_OPERATORS.indexOf(condition.operator) !== -1) {
            return renderTags(condition);
        }
        var $wrapper = $('<div class="sf-cr-text"/>');
        var $text = $('<input type="text" class="form-control"/>').val(condition.value);
        $text
            .on('input', function () {
                condition.value = $(this).val();
                renderSuggestions($wrapper, condition.criterion, $(this).val(), function (suggestion) {
                    condition.value = String(suggestion.value);
                    $text.val(condition.value);
                    $wrapper.find('.sf-cr-suggestions').remove();
                });
            })
            .on('blur', function () {
                $wrapper.find('.sf-cr-suggestions').remove();
            });
        return $wrapper.append($text);
    }

    function renderCondition(group, conditionIndex) {
        var condition = group.conditions[conditionIndex];
        var criteria = $.map(data.criteria, function (label, value) {
            return {value: value, label: label};
        });
        var operators = $.map(data.operators[condition.criterion], function (operator) {
            return {value: operator, label: data.operatorLabels[operator]};
        });

        var $row = $('<div class="row sf-cr-condition"/>');
        $('<div class="col-lg-3"/>').append(
            select(criteria, condition.criterion).on('change', function () {
                group.conditions[conditionIndex] = newCondition($(this).val());
                render();
            })
        ).appendTo($row);
        $('<div class="col-lg-3"/>').append(
            select(operators, condition.operator).on('change', function () {
                var wasList = LIST_OPERATORS.indexOf(condition.operator) !== -1;
                condition.operator = $(this).val();
                var isList = LIST_OPERATORS.indexOf(condition.operator) !== -1;
                if (wasList !== isList) {
                    condition.value = isList
                        ? (condition.value === '' ? [] : [condition.value])
                        : (condition.value.length ? condition.value[0] : '');
                }
                render();
            })
        ).appendTo($row);
        $('<div class="col-lg-5"/>').append(renderValue(condition)).appendTo($row);
        $('<div class="col-lg-1"/>').append(
            $('<a href="#" class="sf-cr-icon-button"/>').attr('title', t.deleteCondition)
                .append('<i class="icon-trash"></i>')
                .on('click', function (e) {
                    e.preventDefault();
                    group.conditions.splice(conditionIndex, 1);
                    render();
                })
        ).appendTo($row);

        return $row;
    }

    function renderGroup(rule, groupIndex) {
        var group = rule.groups[groupIndex];
        var $group = $('<div class="sf-cr-group"/>');

        $.each(group.conditions, function (conditionIndex) {
            if (conditionIndex > 0) {
                $group.append($('<div class="sf-cr-and"/>').append($('<span class="badge"/>').text(t.and)));
            }
            $group.append(renderCondition(group, conditionIndex));
        });

        $('<button type="button" class="btn btn-default btn-sm sf-cr-add"/>')
            .append('<i class="icon-plus"></i> ').append(document.createTextNode(t.addAndCondition))
            .on('click', function () {
                group.conditions.push(newCondition());
                render();
            })
            .appendTo($group);

        return $group;
    }

    function renderRule(ruleIndex) {
        var rule = rules[ruleIndex];
        var isCollapsed = !!collapsed[ruleIndex];
        var $rule = $('<div class="sf-cr-rule"/>');

        var $header = $('<div class="sf-cr-rule-header"/>').appendTo($rule);
        $('<a href="#" class="sf-cr-rule-title"/>')
            .append($('<i/>').addClass(isCollapsed ? 'icon-caret-right' : 'icon-caret-down'))
            .append(document.createTextNode(' ' + t.rule.replace('%d', ruleIndex + 1)))
            .on('click', function (e) {
                e.preventDefault();
                collapsed[ruleIndex] = !isCollapsed;
                render();
            })
            .appendTo($header);
        $('<button type="button" class="btn btn-default btn-sm sf-cr-delete-rule"/>')
            .text(t.deleteRule)
            .on('click', function () {
                rules.splice(ruleIndex, 1);
                collapsed = {};
                render();
            })
            .appendTo($header);

        if (isCollapsed) {
            return $rule;
        }

        // Token
        var $token = $('<div class="panel sf-cr-panel"/>').appendTo($rule);
        $('<div class="form-group sf-cr-token"/>')
            .append($('<label/>').text(t.token))
            .append(select(data.tokens.map(function (token) {
                return {value: token.id, label: token.label};
            }), rule.id_shoppingfeed_token).on('change', function () {
                rule.id_shoppingfeed_token = parseInt($(this).val(), 10);
            }))
            .appendTo($token);

        // Conditions
        var $conditions = $('<div class="panel sf-cr-panel"/>').appendTo($rule);
        $('<div class="panel-heading"/>').append('<i class="icon-cogs"></i> ').append(document.createTextNode(t.conditions)).appendTo($conditions);
        $('<p class="help-block"/>').text(t.conditionsHelp).appendTo($conditions);
        $.each(rule.groups, function (groupIndex) {
            if (groupIndex > 0) {
                $conditions.append($('<div class="sf-cr-or"/>').append($('<span/>').text(t.or)));
            }
            $conditions.append(renderGroup(rule, groupIndex));
        });
        $('<button type="button" class="btn btn-default sf-cr-add"/>')
            .append('<i class="icon-plus"></i> ').append(document.createTextNode(t.addOrGroup))
            .on('click', function () {
                rule.groups.push(newGroup());
                render();
            })
            .appendTo($conditions);

        // Carrier
        var $carrier = $('<div class="panel sf-cr-panel"/>').appendTo($rule);
        $('<div class="panel-heading"/>').append('<i class="icon-truck"></i> ').append(document.createTextNode(t.carrier)).appendTo($carrier);
        $('<div class="form-group"/>')
            .append($('<label/>').text(t.thenSelectCarrier))
            .append(select([{value: 0, label: t.selectCarrier}].concat(data.carriers), rule.id_carrier_reference, 'fixed-width-xxl')
                .on('change', function () {
                    rule.id_carrier_reference = parseInt($(this).val(), 10);
                }))
            .appendTo($carrier);

        return $rule;
    }

    function render() {
        $app.empty();

        var $header = $('<div class="sf-cr-header"/>').appendTo($app);
        $('<h3/>').text(t.title).appendTo($header);
        $('<button type="button" class="btn btn-default"/>')
            .append('<i class="icon-question-circle"></i> ').append(document.createTextNode(t.help))
            .on('click', function () {
                $app.find('.sf-cr-help').toggle();
            })
            .appendTo($header);
        $('<div class="alert alert-info sf-cr-help" style="display: none;"/>').text(t.conditionsHelp).appendTo($app);

        if (!rules.length) {
            $('<p class="sf-cr-empty"/>').text(t.noRule).appendTo($app);
        }
        $.each(rules, function (ruleIndex) {
            $app.append(renderRule(ruleIndex));
        });

        var $footer = $('<div class="sf-cr-footer"/>').appendTo($app);
        $('<button type="button" class="btn btn-default"/>')
            .append('<i class="icon-plus"></i> ').append(document.createTextNode(t.addRule))
            .on('click', function () {
                rules.push(newRule());
                render();
            })
            .appendTo($footer);
        $('<a class="btn btn-default"/>').attr('href', window.location.href).text(t.cancel).appendTo($footer);
    }

    // --- switch and submit -----------------------------------------------

    function toggleEngine() {
        var enabled = $switchInputs.filter(':checked').val() === '1';
        $('#shoppingfeed_carrier-rules').toggle(enabled);
        $('#shoppingfeed_carriers-matching-group').toggle(!enabled);
    }

    $switchInputs.on('change', toggleEngine);

    $input.closest('form').on('submit', function () {
        $input.val(JSON.stringify(rules));
    });

    render();
    toggleEngine();
});
