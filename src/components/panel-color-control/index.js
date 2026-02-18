import { PanelColorSettings } from '@wordpress/block-editor';

const PanelColorControl = ({ label, colorSettings }) => {
    return (
        <div className="native-panel-color-control">
            <PanelColorSettings title={label} initialOpen={false} colorSettings={colorSettings} enableAlpha={true} />
        </div>
    );
};

export default PanelColorControl;
