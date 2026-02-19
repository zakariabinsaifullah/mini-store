const generateBoxStyles = value => {
    if (!value) {
        return;
    }

    const addUnit = val => {
        if (val === undefined || val === null || val === '') {
            return '';
        }
        if (String(val).match(/^-?\d+(\.\d+)?$/)) {
            return `${val}px`;
        }
        return val;
    };

    const top = addUnit(value?.top);
    const right = addUnit(value?.right);
    const bottom = addUnit(value?.bottom);
    const left = addUnit(value?.left);

    if (top === right && right === bottom && bottom === left) {
        return `${top}`;
    }
    return `${top} ${right} ${bottom} ${left}`;
};
export default generateBoxStyles;
