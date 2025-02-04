(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var PanelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;

    registerBlockType('mycred-gb-blocks/mycred-content-buyer-count', {
        title: __('Content Buyer Count', 'mycred'),
        category: 'mycred',
        attributes: {
            postID: {
                type: 'string',
            },
            wrapper: {
                type: 'string',
            },
        },
        edit: function (props) {
            var wrapper = props.attributes.wrapper;
            var postID = props.attributes.postID;

            function setPOSTID(value) {
                props.setAttributes({ postID: value });
            }
            function setWrapper(value) {
                props.setAttributes({ wrapper: value });
            }

            return el('div', {}, [
                el('p', {}, __('Content Buyer Count Shortcode', 'mycred')),
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Shortcode Attributes', 'mycred'), initialOpen: true },
                        el(TextControl, {
                            label: __('Wrapper', 'mycred'),
                            help: __("The HTML element to use as the wrapper around the number. Leave empty for no wrapper.", 'mycred'),
                            value: wrapper,
                            onChange: setWrapper,
                        }),
                        el(TextControl, {
                            label: __('Post ID', 'mycred'),
                            help: __("Option to provide a post ID. If not used, the current post's ID is used.", 'mycred'),
                            value: postID,
                            onChange: setPOSTID,
                        }),
                    )
                )
            ]);
        },
        save: function () {
            return null; // Dynamic block - rendered server-side
        }
    });
})(window.wp);
