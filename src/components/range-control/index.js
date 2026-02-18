import { RangeControl } from '@wordpress/components';

const NativeRangeControl = ({ label, value, onChange, min, max, step, resetFallbackValue }) => {
    return (
        <div className="native-control-wrapper">
            <RangeControl
                label={label}
                value={value}
                onChange={v => onChange(v)}
                min={min}
                max={max}
                step={step}
                allowReset={resetFallbackValue ? true : false}
                resetFallbackValue={resetFallbackValue}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
            />
        </div>
    );
};

export default NativeRangeControl;
