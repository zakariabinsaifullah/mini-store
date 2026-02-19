import {
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption
} from '@wordpress/components';

const NativeToggleGroupControl = ({ label, value, onChange, options = [] }) => {
    return (
        <div className="native-control-wrapper">
            <ToggleGroupControl
                label={label}
                value={value}
                onChange={v => onChange(v)}
                isBlock
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            >
                {options &&
                    options.map(option => <ToggleGroupControlOption key={option.value} value={option.value} label={option.label} />)}
            </ToggleGroupControl>
        </div>
    );
};

export default NativeToggleGroupControl;
