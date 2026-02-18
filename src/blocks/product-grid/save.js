/**
 * WordPress Dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

// Block save function
const Save = () => {
    const blockProps = useBlockProps.save({
        className: 'wp-block-mini-store-product-grid'
    });

    // This block uses dynamic rendering via PHP
    // Return null to indicate server-side rendering
    return null;
};

export default Save;
