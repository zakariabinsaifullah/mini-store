import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, __experimentalToolsPanel as ToolsPanel, __experimentalToolsPanelItem as ToolsPanelItem } from '@wordpress/components';
import {
    NativeToggleGroupControl,
    NativeRangeControl,
    NativeToggleControl,
    PanelColorControl,
    NativeTextControl,
    NativeSelectControl
} from '../../components';

const Inspector = props => {
    const { attributes, setAttributes } = props;
    const { columns, productsToShow, orderBy, order, showPrice, showAddToCart, showImage, gap,btnLabel,titleColor,titleSize,btnColor,btnBgColor,btnLabelSize,btnHoverColor,btnHoverBgColor,descriptionColor,descSize,priceSize,priceColor,salepriceColor  } = attributes;

    return (
        <>
            <InspectorControls group="settings">
                <PanelBody title={__('Layout Settings', 'mini-store')} initialOpen={true}>
                    <NativeRangeControl
                        label={__('Columns', 'mini-store')}
                        value={columns}
                        onChange={value => setAttributes({ columns: value })}
                        min={1}
                        max={6}
                        step={1}
                        help={__('Number of columns in the grid', 'mini-store')}
                    />
                    <NativeRangeControl
                        label={__('Products to Show', 'mini-store')}
                        value={productsToShow}
                        onChange={value => setAttributes({ productsToShow: value })}
                        min={1}
                        max={50}
                        step={1}
                        help={__('Maximum number of products to display', 'mini-store')}
                    />
                    <NativeRangeControl
                        label={__('Grid Gap (px)', 'mini-store')}
                        value={gap}
                        onChange={value => setAttributes({ gap: value })}
                        min={0}
                        max={100}
                        step={2}
                        help={__('Space between products', 'mini-store')}
                    />
                </PanelBody>

                <PanelBody title={__('Sorting', 'mini-store')} initialOpen={false}>
                    <NativeSelectControl
                        label={__('Order By', 'mini-store')}
                        value={orderBy}
                        options={[
                            { label: __('Date', 'mini-store'), value: 'date' },
                            { label: __('Title', 'mini-store'), value: 'title' },
                            { label: __('Modified', 'mini-store'), value: 'modified' },
                            { label: __('Random', 'mini-store'), value: 'rand' }
                        ]}
                        onChange={value => setAttributes({ orderBy: value })}
                    />
                    <NativeSelectControl    
                        label={__('Order', 'mini-store')}
                        value={order}
                        options={[
                            { label: __('Descending', 'mini-store'), value: 'DESC' },
                            { label: __('Ascending', 'mini-store'), value: 'ASC' }
                        ]}
                        onChange={value => setAttributes({ order: value })}
                    />
                </PanelBody>

                <PanelBody title={__('Display Options', 'mini-store')} initialOpen={false}>
                    <NativeToggleControl
                        label={__('Show Product Image', 'mini-store')}
                        checked={showImage}
                        onChange={value => setAttributes({ showImage: value })}
                    />
                    <NativeToggleControl
                        label={__('Show Price', 'mini-store')}
                        checked={showPrice}
                        onChange={value => setAttributes({ showPrice: value })}
                    />
                    <NativeToggleControl
                        label={__('Show Add to Cart Button', 'mini-store')}
                        checked={showAddToCart}
                        onChange={value => setAttributes({ showAddToCart: value })}
                    />
                </PanelBody>
                <PanelBody title={__('Button Settings', 'mini-store')} initialOpen={false}>
                    <NativeTextControl
                        label={__('Button Label', 'mini-store')}
                        value={btnLabel}
                        onChange={value => setAttributes({ btnLabel: value })}
                    />
                </PanelBody>
            </InspectorControls>
            
            <InspectorControls group="styles">
                    <ToolsPanel
                    label={__('Title', 'mini-store')}
                    resetAll={() =>
                        setAttributes({
                            titleSize: undefined,
                            titleColor: undefined
                        })
                    }
                >
                    <ToolsPanelItem
                        hasValue={() => !!titleSize}
                        label={__('Size', 'mini-store')}
                        onDeselect={() => {
                            setAttributes({
                                titleSize: undefined
                            });
                        }}
                        onSelect={() => {}}
                    >
                        <NativeRangeControl
                            label={__('Title Size', 'mini-store')}
                            value={titleSize}
                            onChange={value => setAttributes({ titleSize: value })}
                            min={0}
                            max={100}
                            step={1}
                        />
                    </ToolsPanelItem>

                    <ToolsPanelItem
                        hasValue={() => !!titleColor}
                        label={__('Color', 'mini-store')}
                        onDeselect={() => {
                            setAttributes({
                                titleColor: undefined
                            });
                        }}
                        onSelect={() => {}}
                    >
                        <PanelColorControl
                            label={__('Title Color', 'mini-store')}
                            colorSettings={[
                                {
                                    value: titleColor,
                                    onChange: color => setAttributes({ titleColor: color })
                                }
                            ]}
                        />
                    </ToolsPanelItem>
                </ToolsPanel>

                <ToolsPanel
                    label={__('Description', 'mini-store')}
                    resetAll={() =>
                        setAttributes({
                            descSize: undefined,
                            descriptionColor: undefined
                        })
                    }
                >
                    <ToolsPanelItem
                        hasValue={() => !!descSize}
                        label={__('Size', 'mini-store')}
                        onDeselect={() => {
                            setAttributes({
                                descSize: undefined
                            });
                        }}
                        onSelect={() => {}}
                    >
                        <NativeRangeControl
                            label={__('Description Size', 'mini-store')}
                            value={descSize}
                            onChange={value => setAttributes({ descSize: value })}
                            min={0}
                            max={100}
                            step={1}
                        />
                    </ToolsPanelItem>

                    <ToolsPanelItem
                        hasValue={() => !!descriptionColor}
                        label={__('Color', 'mini-store')}
                        onDeselect={() => {
                            setAttributes({
                                descriptionColor: undefined
                            });
                        }}
                        onSelect={() => {}}
                    >
                        <PanelColorControl
                            label={__('Description Color', 'mini-store')}
                            colorSettings={[
                                {
                                    value: descriptionColor,
                                    onChange: color => setAttributes({ descriptionColor: color })
                                }
                            ]}
                        />
                    </ToolsPanelItem>
                </ToolsPanel>

                <ToolsPanel
                    label={__('Price', 'mini-store')}
                    resetAll={() =>
                        setAttributes({
                            priceSize: undefined,
                            priceColor: undefined,
                            salepriceColor: undefined
                        })
                    }
                >
                    <ToolsPanelItem
                        hasValue={() => !!priceSize}
                        label={__('Size', 'mini-store')}
                        onDeselect={() => {
                            setAttributes({
                                priceSize: undefined
                            });
                        }}
                        onSelect={() => {}}
                    >
                        <NativeRangeControl
                            label={__('Price Size', 'mini-store')}
                            value={priceSize}
                            onChange={value => setAttributes({ priceSize: value })}
                            min={0}
                            max={100}
                            step={1}
                        />
                    </ToolsPanelItem>

                    <ToolsPanelItem
                        hasValue={() => !!priceColor}
                        label={__('Color', 'mini-store')}
                        onDeselect={() => {
                            setAttributes({
                                priceColor: undefined
                            });
                        }}
                        onSelect={() => {}}
                    >
                        <PanelColorControl
                            label={__('Regular Price Color', 'mini-store')}
                            colorSettings={[
                                {
                                    value: priceColor,
                                    onChange: color => setAttributes({ priceColor: color })
                                }
                            ]}
                        />

                        <ToolsPanelItem
                        hasValue={() => !!salepriceColor}
                        label={__('Color', 'mini-store')}
                        onDeselect={() => {
                            setAttributes({
                                salepriceColor: undefined
                            });
                        }}
                        onSelect={() => {}}
                    >
                        <PanelColorControl
                            label={__('Sale Price Color', 'mini-store')}
                            colorSettings={[
                                {
                                    value: salepriceColor,
                                    onChange: color => setAttributes({ salepriceColor: color })
                                }
                            ]}
                        />
                    </ToolsPanelItem>
                    </ToolsPanelItem>
                </ToolsPanel>
                <ToolsPanel
                    label={__('Button', 'mini-store')}
                    resetAll={() =>
                        setAttributes({
                            btnLabelSize: undefined,
                            buttonColor: undefined,
                            buttonBgColor: undefined,
                            buttonBorderRadius: undefined
                        })
                    }
                >
                    <ToolsPanelItem
                        hasValue={() => !!btnLabelSize}
                        label={__('Size', 'mini-store')}
                        onDeselect={() => {
                            setAttributes({
                                btnLabelSize: undefined
                            });
                        }}
                        onSelect={() => {}}
                    >
                        <NativeRangeControl
                            label={__('Button Size', 'mini-store')}
                            value={btnLabelSize}
                            onChange={value => setAttributes({ btnLabelSize: value })}
                            min={0}
                            max={100}
                            step={1}
                        />
                    </ToolsPanelItem>

                    <ToolsPanelItem
                        hasValue={() => !!btnColor}
                        label={__('Color', 'mini-store')}
                        onDeselect={() => {
                            setAttributes({
                                btnColor: undefined
                            });
                        }}
                        onSelect={() => {}}
                    >
                      
                        <PanelColorControl
                            label={__('Colors', 'gl-layout-builder')}
                            colorSettings={[
                                {
                                    value: btnColor,
                                    onChange: color => setAttributes({ btnColor: color }),
                                    label: __('Labels', 'gl-layout-builder')
                                },
                                {
                                    value: btnBgColor,
                                    onChange: color => setAttributes({ btnBgColor: color }),
                                    label: __('Background', 'gl-layout-builder')
                                },
                                {
                                    value: btnHoverColor,
                                    onChange: color => setAttributes({ btnHoverColor: color }),
                                    label: __('Hover Color', 'gl-layout-builder')
                                },
                               
                                {
                                    value:  btnHoverBgColor,
                                    onChange: color => setAttributes({ btnHoverBgColor: color }),
                                    label: __('Hover Background', 'gl-layout-builder')
                                }
                            ]}
                        />
                    </ToolsPanelItem>
                </ToolsPanel>
            </InspectorControls>
        </>
    );
};

export default Inspector;
