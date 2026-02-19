import { SelectControl } from '@wordpress/components';

const NativeSelectControl = ({ label, value, onChange, options, width = '' }) => {
    return (
        <div
            className="native-control-wrapper"
            style={{
                '--max-width': width
            }}
        >
            <SelectControl
                label={label}
                value={value}
                options={options}
                onChange={v => onChange(v)}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
            />
        </div>
    );
};

export default NativeSelectControl;
