import { __experimentalUnitControl as UnitControl } from '@wordpress/components';
import classnames from 'classnames';

const NativeUnitControl = ({
    label,
    value,
    onChange,
    mb = '',
    placeholder = '',
    units = [
        {
            label: 'px',
            value: 'px'
        },
        {
            label: 'em',
            value: 'em'
        },
        {
            label: 'rem',
            value: 'rem'
        }
    ],
    disableUnits = false
}) => {
    return (
        <div
            className={classnames('native-control-wrapper', {
                [`mb-0`]: mb !== ''
            })}
            style={{
                '--max-width': '80px'
            }}
        >
            <UnitControl
                label={label}
                value={value}
                onChange={value => onChange(value)}
                labelPosition="edge"
                placeholder={placeholder}
                __next40pxDefaultSize
                units={units}
                disableUnits={disableUnits}
            />
        </div>
    );
};
export default NativeUnitControl;
