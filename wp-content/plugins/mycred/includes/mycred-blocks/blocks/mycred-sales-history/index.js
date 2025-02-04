(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-sales-history', {
        title: __('Sales History', 'mycred'),
        category: 'mycred',
        attributes: {
            userID : {
                type: 'integer',
                default: 'current'
            },
            number: {
                type: 'integer',
                default: 25
            },
            nothing: {
                type: 'string',
                default: "No purchases found"
            },
            order: {
                type: 'string',
                default: "DESC"
            },
            ctype: {
                type: 'string',
            },
        },
        edit: function (props) {
            var userID = props.attributes.userID;
            var number = props.attributes.number;
            var nothing = props.attributes.nothing;
            var ctype = props.attributes.ctype;
            var order = props.attributes.order;
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
            function setNumber(value) {
                props.setAttributes({number: value});
            }
            function setNothing(value) {
                props.setAttributes({nothing: value});
            }
            function setCtype(value) {
                props.setAttributes({ctype: value});
            }
            function setOrder(value) {
                props.setAttributes({order: value});
            }
            return el('div', {}, [
                el('p', {}, __('Sales History Shortcode', 'mycred')
                        ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Shortcode attributes', initialOpen: true },
                        el(TextControl, {
                            label: __('User ID', 'mycred'),
                            help: __("Option to filter purchase history based on user. By default it will show purchases made by the current user viewing the shortcode.", 'mycred'),
                            value: userID,
                            onChange: setUserID
                        }),
                        el(TextControl, {
                            label: __('Number', 'mycred'),
                            help: __("  The number of purchases to show. Use -1 to show all purchases.", 'mycred'),
                            value: number,
                            onChange: setNumber
                        }),
                        el(TextControl, {
                            label: __('Nothing', 'mycred'),
                            help: __("Message to show when the user has no purchase history. If this attribute is set to be empty and the user has not made any purchases yet, the shortcode will render nothing!", 'mycred'),
                            value: nothing,
                            onChange: setNothing
                        }),
                        el(TextControl, {
                            label: __('Order', 'mycred'),
                            help: __("The purchase history is ordered by time of purchase and this attribute allows you to set the order. Accepts 'DESC' for descending or 'ASC' for ascending order.", 'mycred'),
                            value: order,
                            onChange: setOrder
                        }),
                        el(SelectControl, {
                            label: __('Point Types', 'mycred'),
                            help: __('  Option to filter purchases based on point type if you allow payments using more then one point type. Should not be used if you only have one point type setup or you want to show all purchases no matter which point type was used as payment.', 'mycred'),
                            value: ctype,
                            onChange: setCtype,
                            options
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