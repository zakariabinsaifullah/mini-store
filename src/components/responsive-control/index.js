import { dispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

const devices = [
    {
        name: 'Desktop',
        icon: 'desktop'
    },
    {
        name: 'Tablet',
        icon: 'tablet'
    },
    {
        name: 'Mobile',
        icon: 'smartphone'
    }
];

const NativeResponsiveControl = ({ label, children, props }) => {
    const { setAttributes } = props;
    const deviceType = useSelect(select => {
        return select('core/editor').getDeviceType();
    }, []);

    useEffect(() => {
        if (deviceType) {
            setAttributes({ resMode: deviceType });
        }
    }, [deviceType]);

    return (
        <div className="native-control responsive-control">
            <div className="native-responsive-control">
                <div className="native-responsive-label">{label}</div>
                <div className="native-responsive-value">
                    <div className="native-responsive-device-selector">
                        {devices.map(device => (
                            <button
                                key={device.name}
                                className={`native-responsive-device-button ${deviceType === device.name ? 'active' : ''}`}
                                onClick={() => {
                                    dispatch('core/editor').setDeviceType(device.name);
                                }}
                                title={`${device.name} View`}
                            >
                                <span className={`dashicons dashicons-${device.icon}`}></span>
                            </button>
                        ))}
                    </div>
                </div>
            </div>
            <div className="native-responsive-control-content">{children}</div>
        </div>
    );
};

export default NativeResponsiveControl;
