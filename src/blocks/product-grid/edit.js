/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import { Disabled, Placeholder, Spinner } from '@wordpress/components';
import { store as coreDataStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import Inspector from './inspector';

/**
 * Internal Dependencies
 */
import './editor.scss';

// Block edit function
const Edit = props => {
    const { attributes, setAttributes, isSelected } = props;
    const { columns, productsToShow, orderBy, order, showPrice, showAddToCart, showImage ,gap,btnLabel,titleColor,titleSize,descriptionColor ,descSize,priceColor,priceSize,btnColor,btnBgColor,btnLabelSize,btnHoverColor,btnHoverBgColor,salepriceColor} = attributes;
     const cssCustomProperties = {
       ...(gap && { '--gap': `${gap}px` }),
       ...(columns && { '--columns': columns }),
       ...(titleColor && { '--title-color': titleColor }),
       ...(titleSize && { '--title-size': titleSize + 'px' }),
       ...(descriptionColor && { '--desc-color': descriptionColor }),
       ...(descSize && { '--desc-size': descSize + 'px' }),
       ...(priceColor && { '--price-color': priceColor }),
       ...(priceSize && { '--price-size': priceSize + 'px' }),
       ...(btnColor && { '--btn-color': btnColor }),
       ...(btnBgColor && { '--btn-bg-color': btnBgColor }),
       ...(btnLabelSize && { '--btn-label-size': btnLabelSize }),
       ...(btnHoverColor && { '--btn-hover-color': btnHoverColor }),
       ...(btnHoverBgColor && { '--btn-hover-bg-color': btnHoverBgColor }),
       ...(salepriceColor && { '--saleprice-color': salepriceColor })

    };
    useEffect(() => {
        setAttributes({
            blockStyle: cssCustomProperties
        });
    }, [gap]);

    // Fetch products from WordPress
    const { products, isResolving } = useSelect(
        select => {
            const { getEntityRecords, isResolving: isResolvingSelector } = select(coreDataStore);

            const query = {
                per_page: productsToShow,
                // orderby: orderBy,
                // order: order,
                // status: 'publish'
            };

            return {
                products: getEntityRecords('postType', 'ms_product', query),
                isResolving: isResolvingSelector('getEntityRecords', ['postType', 'ms_product', query])
            };
        },
        [productsToShow, orderBy, order]
    );

    const blockProps = useBlockProps({
        style: cssCustomProperties
    });

    // Show loading state
    if (isResolving) {
        return (
            <div {...blockProps}>
                <Placeholder icon="store" label={__('Product Grid', 'mini-store')}>
                    <Spinner />
                </Placeholder>
            </div>
        );
    }

    // Show empty state
    if (!products || !Array.isArray(products) || products.length === 0) {
        return (
            <>
                {isSelected && <Inspector {...props} />}
                <div {...blockProps}>
                    <Placeholder icon="store" label={__('Product Grid', 'mini-store')}>
                        <p>{__('No products found. Create some "ms_product" items to get started!', 'mini-store')}</p>
                    </Placeholder>
                </div>
            </>
        );
    }

    return (
        <>
            {isSelected && <Inspector {...props} />}
            <div {...blockProps}>
                <Disabled>
                    <div className="mini-store-products">
                        {products.map(product => {
                            // Defensive checks for all properties
                            if (!product) return null;

                            const title = product.title?.rendered || __('Untitled Product', 'mini-store');
                            const excerpt = product.excerpt?.rendered || '';
                            
                            // Meta handling
                            const meta = product.meta || {};
                            const regularPrice = meta._ms_regular_price || '';
                            const salePrice = meta._ms_sale_price || '';
                            const price = salePrice ? salePrice : regularPrice;
                            
                            // Image handling
                            // featured_media_src_url is added via register_rest_field
                            const imageUrl = product.featured_media_src_url || '';

                            return (
                                <div key={product.id} className="mini-store-product">
                                    {showImage && (
                                        <div className="product-image">
                                            {imageUrl ? (
                                                <img src={imageUrl} alt={title} />
                                            ) : (
                                                <div className="placeholder-image">Image</div>
                                            )}
                                        </div>
                                    )}
                                    <div className="product-details">
                                        <h3 className="product-title">{title}</h3>
                                        
                                        <div className="product-excerpt" dangerouslySetInnerHTML={{ __html: excerpt }} />

                                        {showPrice && (
                                            <div className="product-price">
                                                {salePrice ? (
                                                    <>
                                                        <del className="regular-price">৳{regularPrice}</del>
                                                        <ins className="sale-price">৳{salePrice}</ins>
                                                    </>
                                                ) : (
                                                    <span className="regular-price">৳{regularPrice || '0.00'}</span>
                                                )}
                                            </div>
                                        )}
                                        
                                        {showAddToCart && (
                                           <a href={product.link} className="add-to-cart-button">{btnLabel}</a>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </Disabled>
            </div>
        </>
    );
};

export default Edit;
