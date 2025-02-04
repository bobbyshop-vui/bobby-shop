(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-content-buyer-avatars', {
        title: __('Content buyer Avatars', 'mycred'),
        category: 'mycred',
        attributes: {
            postID : {
                type: 'integer',
            },
            number: {
                type: 'integer',
                default: 10 
            },
            size: {
                type: 'integer',
                default: 42
            },
            ctype: {
                type: 'string',
            },
            use_email: {
                type: 'integer',
                default: 0
            },
            default: {
                type: 'string',
            },
            alt: {
                type: 'string',
            }
        },
        edit: function (props) {
            var postID = props.attributes.postID;
            var number = props.attributes.number;
            var size = props.attributes.size;
            var ctype = props.attributes.ctype;
            var use_email = props.attributes.use_email;
            var default_att = props.attributes.default;
            var alt = props.attributes.alt;
            var options = [];
            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });

            function setPOSTID(value) {
                props.setAttributes({postID: value});
            }
            function setNumber(value) {
                props.setAttributes({number: value});
            }
            function setSize(value) {
                props.setAttributes({size: value});
            }
            function setCtype(value) {
                props.setAttributes({ctype: value});
            }
            function setUse_email(value) {
                props.setAttributes({use_email: value});
            }
            function setDefault(value) {
                props.setAttributes({default: value});
            }
            function setAlt(value) {
                props.setAttributes({alt: value});
            }
            return el('div', {}, [
                el('p', {}, __('Content buyer Avatars Shortcode', 'mycred')
                        ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Shortcode attributes', initialOpen: true },
                        el(TextControl, {
                            label: __('Post ID', 'mycred'),
                            help: __("Option to provide a post ID. If not used, the current posts ID is used instead.", 'mycred'),
                            value: postID,
                            onChange: setPOSTID
                        }),
                        el(TextControl, {
                            label: __('Number', 'mycred'),
                            help: __("The number of avatars to show.", 'mycred'),
                            value: number,
                            onChange: setNumber
                        }),
                        el(TextControl, {
                            label: __('Size', 'mycred'),
                            help: __("he avatar size in pixels.", 'mycred'),
                            value: size,
                            onChange: setSize
                        }),
                        el(SelectControl, {
                            label: __('Point Types', 'mycred'),
                            help: __('Option to show buyers that paid using a particular point type. Should not be used if you only have one point type installed or if users can only pay using one point type.', 'mycred'),
                            value: ctype,
                            onChange: setCtype,
                            options
                        }),
                        el(TextControl, {
                            label: __('Use Email', 'mycred'),
                            help: __("By default avatars are loaded using the users ID (0) but you can select to show avatars based on their email instead (1).", 'mycred'),
                            value: use_email,
                            onChange: setUse_email
                        }),
                        el(TextControl, {
                            label: __('Default', 'mycred'),
                            help: __("Default image to use. Requires an image URL. If not used, the WordPress default one is used.", 'mycred'),
                            value: default_att,
                            onChange: setDefault
                        }),
                        el(TextControl, {
                            label: __('Alt', 'mycred'),
                            help: __("Option to set the avatar images alt attribute.", 'mycred'),
                            value: alt,
                            onChange: setAlt
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