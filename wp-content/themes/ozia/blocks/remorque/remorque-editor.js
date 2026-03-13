( function( blocks, element, editor, components, serverSideRender ) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = editor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var ServerSideRender = serverSideRender;

    registerBlockType( 'ozi/remorque-details', {
        title: 'Détails Remorque',
        icon: 'admin-tools',
        category: 'widgets',
        attributes: {
            weight: { type: 'string', source: 'meta', meta: '_ozi_weight' },
            capacity: { type: 'string', source: 'meta', meta: '_ozi_capacity' }
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            // inline editing interface
            return el( 'div', { className: props.className },
                // inspector left for optional extra settings
                el( InspectorControls, {},
                    el( PanelBody, { title: 'Caractéristiques' },
                        el( TextControl, {
                            label: 'Poids',
                            value: attributes.weight || '',
                            onChange: function( val ) { setAttributes( { weight: val } ); }
                        } ),
                        el( TextControl, {
                            label: 'Capacité',
                            value: attributes.capacity || '',
                            onChange: function( val ) { setAttributes( { capacity: val } ); }
                        } )
                    )
                ),

                // main block content: editable fields directly in place
                el( 'div', { className: 'remorque-block-body' },
                    el( 'h3', {}, 'Détails remorque (édition directe)' ),
                    el( TextControl, {
                        label: 'Poids (kg)',
                        value: attributes.weight || '',
                        onChange: function( val ) { setAttributes( { weight: val } ); }
                    } ),
                    el( TextControl, {
                        label: 'Charge utile (kg)',
                        value: attributes.capacity || '',
                        onChange: function( val ) { setAttributes( { capacity: val } ); }
                    } )
                ),

                // divider between edit fields and preview
                el( 'hr' ),
                // server-side preview below for full layout
                el( ServerSideRender, { block: 'ozi/remorque-details', attributes: attributes } )
            );
        },
        save: function() {
            return null; // dynamic block
        }
    } );
} )( window.wp.blocks, window.wp.element, window.wp.editor, window.wp.components, window.wp.serverSideRender );