// generate border-width value
export const generateBorderWidth = borderData => {
    if (borderData?.top && borderData?.right && borderData?.bottom && borderData?.left) {
        const topWidth = borderData.top.width || '0';
        const rightWidth = borderData.right.width || '0';
        const bottomWidth = borderData.bottom.width || '0';
        const leftWidth = borderData.left.width || '0';

        // If all 4 sides have the same width, return single value
        if (topWidth === rightWidth && rightWidth === bottomWidth && bottomWidth === leftWidth) {
            return topWidth?.toString();
        }

        // Otherwise return 4 values
        return `${topWidth} ${rightWidth} ${bottomWidth} ${leftWidth}`;
    }
    return borderData?.width;
};

// generate border-style value
export const generateBorderStyle = borderData => {
    if (borderData?.top && borderData?.right && borderData?.bottom && borderData?.left) {
        const topStyle = borderData.top.style || 'solid';
        const rightStyle = borderData.right.style || 'solid';
        const bottomStyle = borderData.bottom.style || 'solid';
        const leftStyle = borderData.left.style || 'solid';

        // If all 4 sides have the same style, return single value
        if (topStyle === rightStyle && rightStyle === bottomStyle && bottomStyle === leftStyle) {
            return topStyle?.toString();
        }

        // Otherwise return 4 values
        return `${topStyle} ${rightStyle} ${bottomStyle} ${leftStyle}`;
    }
    return borderData?.style;
};

// generate border-color value
export const generateBorderColor = borderData => {
    if (borderData?.top && borderData?.right && borderData?.bottom && borderData?.left) {
        const topColor = borderData.top.color || 'transparent';
        const rightColor = borderData.right.color || 'transparent';
        const bottomColor = borderData.bottom.color || 'transparent';
        const leftColor = borderData.left.color || 'transparent';

        // If all 4 sides have the same color, return single value
        if (topColor === rightColor && rightColor === bottomColor && bottomColor === leftColor) {
            return topColor?.toString();
        }

        // Otherwise return 4 values
        return `${topColor} ${rightColor} ${bottomColor} ${leftColor}`;
    }
    return borderData?.color;
};
