import { TextareaControl } from '@wordpress/components';

const NativeTextareaControl = ({ label, value, onChange, placeholder = '', help = '' }) => {
    return (
        <div className="native-control-wrapper">
            <TextareaControl
                label={label}
                value={value}
                placeholder={placeholder}
                onChange={v => onChange(v)}
                help={help}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
            />
        </div>
    );
};

export default NativeTextareaControl;
