import { BorderBoxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { select } from '@wordpress/data';

const NativeBorderBoxControl = ({ label, value, onChange }) => {
    const themeColors = select('core/block-editor').getSettings().colors || [];
    return <BorderBoxControl __next40pxDefaultSize colors={themeColors} label={label} onChange={v => onChange(v)} value={value} />;
};
export default NativeBorderBoxControl;
