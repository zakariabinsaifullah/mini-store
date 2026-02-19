import { Icon } from '@wordpress/icons';
import { icons, getIconByName } from '../utils/icons';

export const RenderIcon = ({ customSvgCode, iconName, size = '24' }) => {
    const renderCurrentIcon = (size = '24') => {
        if (customSvgCode) {
            return (
                <div
                    className="gutenlayouts-custom-svg-container"
                    style={{
                        width: size + 'px',
                        height: size + 'px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center'
                    }}
                    dangerouslySetInnerHTML={{ __html: customSvgCode }}
                />
            );
        }

        if (iconName) {
            const selectedIcon = getIconByName(iconName);
            if (selectedIcon) {
                return <Icon icon={selectedIcon.icon} size={size} />;
            }
        }

        return <Icon icon={icons[0].icon} size={size} />;
    };

    return renderCurrentIcon(size);
};

// soft minify
export const softMinifyCssStrings = (cssString = ' ') =>
    cssString.replace(/\s+/g, ' ').replace(/\.gutenlayouts\-[\w\-\s\.\,\:\>\(\)\d\+\[\]\#\>]+\{[\s]+\}/g, '');

export const svgToBase64DataUrl = svgCode => {
    if (!svgCode) return '';
    try {
        const base64 = btoa(unescape(encodeURIComponent(svgCode)));
        return `data:image/svg+xml;base64,${base64}`;
    } catch (e) {
        console.error('Error converting SVG to Base64:', e);
        return '';
    }
};

/**
 * Get SVG string from a WordPress icon or component
 */
export const getSVGString = (icon, size = 24) => {
    if (typeof icon === 'string') return icon;

    try {
        // Try using wp.element.renderToString if available
        if (window.wp && window.wp.element && window.wp.element.renderToString) {
            return window.wp.element.renderToString(icon);
        }
    } catch (e) {
        console.error('Error rendering icon to string:', e);
    }

    return '';
};
