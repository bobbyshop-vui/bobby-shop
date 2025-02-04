(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var PanelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;

    registerBlockType('mycred-gb-blocks/mycred-content-sale-count', {
        title: __('Content Sale Count', 'mycred'),
        category: 'mycred',
        attributes: {
            postID: {
                type: 'string',
            },
            wrapper: {
                type: 'string',
            },
            salesCount: {
                type: 'number',
                default: 0,
            },
        },
        edit: function (props) {
            var { wrapper, postID, salesCount } = props.attributes;

            function setPOSTID(value) {
                props.setAttributes({ postID: value });
                fetchSalesCount(value); // Fetch sales count when post ID changes
            }
            function setWrapper(value) {
                props.setAttributes({ wrapper: value });
            }

            function fetchSalesCount(postID) {
                if (!postID) return;

                jQuery.post(ajaxurl, {
                    action: 'mycred_update_sales_count',
                    post_id: postID
                }).done(function (data) {
                    props.setAttributes({ salesCount: parseInt(data) });
                });
            }

            // Fetch the sales count on component mount
            if (postID) {
                fetchSalesCount(postID);
            }

            return el('div', {}, [
                el('p', {}, __('Content Sale Count Shortcode', 'mycred')),
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Shortcode Attributes', 'mycred'), initialOpen: true },
                        el(TextControl, {
                            label: __('Wrapper', 'mycred'),
                            help: __('The HTML element to use as the wrapper around the number. Leave empty for no wrapper.', 'mycred'),
                            value: wrapper,
                            onChange: setWrapper,
                        }),
                        el(TextControl, {
                            label: __('Post ID', 'mycred'),
                            help: __('Option to provide a post ID. If not used, the current post\'s ID is used.', 'mycred'),
                            value: postID,
                            onChange: setPOSTID,
                        }),
                    )
                ),
                el('div', { className: 'mycred-sell-this-sales-count' }, [
                    wrapper ? el(wrapper, {}, salesCount) : salesCount
                ])
            ]);
        },
        save: function () {
            return null; // Dynamic block - rendered server-side
        }
    });
})(window.wp);
