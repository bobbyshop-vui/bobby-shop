(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-leaderboard-position', {
        title: __('Leaderboard Position', 'mycred'),
        category: 'mycred',
        attributes: {
            userID : {
                type: 'string',
                default: 'current'
            },
            ctype: {
                type: 'string', 
            },
            based_on: {
                type: 'string',
                default: 'balance'
            },
            total: {
                type: 'integer',
                default: 0
            },
            missing: {
                type: 'string',
            },
            suffix: {
                type: 'string',
            },
            timeframe: {
                type: 'string',
            }
        },
        edit: function (props) {
            var userID = props.attributes.userID;
            var ctype = props.attributes.ctype;
            var based_on = props.attributes.based_on;
            var total = props.attributes.total;
            var missing = props.attributes.missing;
            var suffix = props.attributes.suffix;
            var timeframe = props.attributes.timeframe;
            var options = [];
            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });

            function setUserID(value) {
                props.setAttributes({userID: value});
            }
            function setCtype(value) {
                props.setAttributes({ctype: value});
            }
            function setBasedon(value) {
                props.setAttributes({based_on: value});
            }
            function setTotal(value) {
                props.setAttributes({total: value});
            }
            function setMissing(value) {
                props.setAttributes({missing: value});
            }
            function setSuffix(value) {
                props.setAttributes({suffix: value});
            }
            function setTimeframe(value) {
                props.setAttributes({timeframe: value});
            }
            return el('div', {}, [
                el('p', {}, __('Leaderboard Position Shortcode', 'mycred')
                        ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Shortcode attributes', initialOpen: true },
                        el(TextControl, {
                            label: __('User ID', 'mycred'),
                            help: __("Option to show the position of a particular user in the leaderboard. Accepts “current” for current user or a user ID.", 'mycred'),
                            value: userID,
                            onChange: setUserID
                        }),
                        el(SelectControl, {
                            label: __('Point Types', 'mycred'),
                            help: __('The point type to base the leaderboard on. Should not be used if you only have one point type installed.', 'mycred'),
                            value: ctype,
                            onChange: setCtype,
                            options
                        }),
                        el(TextControl, {
                            label: __('Based on', 'mycred'),
                            help: __("Option to base the leaderboard on users balance (balance) or a particular reference. For example basing a leaderboard on who got most points for approved comments.", 'mycred'),
                            value: based_on,
                            onChange: setBasedon
                        }),
                        el(TextControl, {
                            label: __('Total', 'mycred'),
                            help: __("When showing a leaderboard based on balances, you can select to use users total balance (1) instead of their current balance (0).", 'mycred'),
                            value: total,
                            onChange: setTotal
                        }),
                        el(TextControl, {
                            label: __('Missing', 'mycred'),
                            help: __("", 'mycred'),
                            value: missing,
                            onChange: setMissing
                        }),
                        el(TextControl, {
                            label: __('Suffix', 'mycred'),
                            help: __("", 'mycred'),
                            value: suffix,
                            onChange: setSuffix
                        }),
                        el(TextControl, {
                            label: __('Timeframe', 'mycred'),
                            help: __("If the leaderboard is based on references, you can set a timeframe for the leaderboard. Accepts the keywords “today” for todays leaderboard, “this-week” for this weeks leaderboard, “this-month” for this months leaderboard or a well formatted date to start from.", 'mycred'),
                            value: timeframe,
                            onChange: setTimeframe
                        }),
                    )
                )
            ]);
        },
        save: function (props) {
            return null;
        }
    });
})(window.wp);