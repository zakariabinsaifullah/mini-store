import { ToggleControl } from '@wordpress/components';

const NativeToggleControl = ({ label, checked, onChange }) => {
    return <ToggleControl label={label} checked={checked} onChange={onChange} __nextHasNoMarginBottom />;
};

export default NativeToggleControl;
