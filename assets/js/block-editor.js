(function (blocks, blockEditor, components, element, serverSideRender) {
    if (!blocks || !blockEditor || !components || !element || !serverSideRender) {
        return;
    }

    const el = element.createElement;
    const InspectorControls = blockEditor.InspectorControls;
    const PanelBody = components.PanelBody;
    const SelectControl = components.SelectControl;
    const ToggleControl = components.ToggleControl;
    const ColorPalette = components.ColorPalette;
    const RangeControl = components.RangeControl;
    const ServerSideRender = serverSideRender;

    blocks.registerBlockType('open-hours/status', {
        apiVersion: 2,
        title: 'Open Hours Status',
        icon: 'clock',
        category: 'widgets',
        attributes: {
            view: {
                type: 'string',
                default: 'badge'
            },
            showNext: {
                type: 'boolean',
                default: true
            },
            showStatus: {
                type: 'boolean',
                default: true
            },
            showClosureNotice: {
                type: 'boolean',
                default: true
            },
            compact: {
                type: 'boolean',
                default: false
            },
            accentColor: {
                type: 'string',
                default: ''
            },
            borderRadius: {
                type: 'number',
                default: 14
            }
        },
        edit: function (props) {
            const attributes = props.attributes;

            return el(
                element.Fragment,
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        {
                            title: 'Display Settings',
                            initialOpen: true
                        },
                        el(SelectControl, {
                            label: 'View',
                            value: attributes.view,
                            options: [
                                { label: 'Badge', value: 'badge' },
                                { label: 'Today', value: 'today' },
                                { label: 'Full', value: 'full' }
                            ],
                            onChange: function (value) {
                                props.setAttributes({ view: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Show Next Opening',
                            checked: attributes.showNext,
                            onChange: function (value) {
                                props.setAttributes({ showNext: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Show Live Status',
                            checked: attributes.showStatus,
                            onChange: function (value) {
                                props.setAttributes({ showStatus: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Show Closure Notice',
                            checked: attributes.showClosureNotice,
                            onChange: function (value) {
                                props.setAttributes({ showClosureNotice: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Compact Mode',
                            checked: attributes.compact,
                            onChange: function (value) {
                                props.setAttributes({ compact: value });
                            }
                        }),
                        el('p', null, 'Accent Color'),
                        el(ColorPalette, {
                            value: attributes.accentColor,
                            onChange: function (value) {
                                props.setAttributes({ accentColor: value || '' });
                            }
                        }),
                        el(RangeControl, {
                            label: 'Border Radius',
                            min: 0,
                            max: 40,
                            value: attributes.borderRadius,
                            onChange: function (value) {
                                props.setAttributes({ borderRadius: value || 0 });
                            }
                        })
                    )
                ),
                el(ServerSideRender, {
                    block: 'open-hours/status',
                    attributes: attributes
                })
            );
        },
        save: function () {
            return null;
        }
    });
}(
    window.wp && window.wp.blocks,
    window.wp && window.wp.blockEditor,
    window.wp && window.wp.components,
    window.wp && window.wp.element,
    window.wp && window.wp.serverSideRender
));
