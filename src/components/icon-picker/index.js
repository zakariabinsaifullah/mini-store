import { __ } from '@wordpress/i18n';
import { Dropdown, Button, BaseControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

import Library from './library';
import QuickInserter from './quick-inserter';
import { getIconType } from '../../utils/icons';
import { RenderIcon } from '../../helpers';

const folderOpen = (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="#000000">
        <path d="M170-180q-29.15 0-49.58-20.42Q100-220.85 100-250v-457.69q0-29.15 21.58-50.73T172.31-780H362q14.46 0 27.81 5.62 13.34 5.61 23.19 15.46L471.92-700h354.62q12.77 0 21.38 8.62 8.62 8.61 8.62 21.38t-8.62 21.38q-8.61 8.62-21.38 8.62H447.38l-80-80H172.31q-5.39 0-8.85 3.46t-3.46 8.85V-240q0-5.39 2.12-4.04 2.11 1.35 5.57 3.27l77.85-259.92q7.23-23.31 26.61-37.46 19.39-14.16 43.08-14.16h514.46q36.77 0 58.35 29.23 21.57 29.23 11.34 63.77l-68.92 229.62q-6.85 22.53-25.65 36.11Q786-180 763.08-180H170Zm60.54-60h531q4.23 0 7.5-2.31 3.27-2.31 4.42-6.54l68.16-227.69q1.92-6.15-1.93-10.96-3.84-4.81-10-4.81H315.23q-4.23 0-7.5 2.31-3.27 2.31-4.42 6.54L230.54-240ZM160-500.08V-720-500.08ZM230.54-240l72.77-243.46q1.15-4.23 1.92-6.54l.77-2.31-1.35 4.81q-1.34 4.81-3.27 10.96l-68.15 227.69q-1.15 4.23-1.92 6.54l-.77 2.31Z" />
    </svg>
);

const NativeIconPicker = ({
    label = __('Icon', 'gl-layout-builder'),
    onIconSelect,
    onCustomSvgInsert,
    iconName,
    customSvgCode,
    iconSize,
    strokeWidth
}) => {
    // states
    const [modalState, setModalState] = useState({
        isOpen: false,
        activeTab: 'library'
    });

    const openModal = (tab = 'library') => {
        setModalState({
            isOpen: true,
            activeTab: tab
        });
    };

    const closeModal = () => {
        setModalState(prev => ({
            ...prev,
            isOpen: false
        }));
    };

    const handleIconSelect = iconData => {
        onIconSelect(iconData.name, getIconType(iconData.icon), iconData.icon);
    };

    const handleCustomSvgInsert = ({ customSvgCode, iconType, strokeWidth }) => {
        onCustomSvgInsert({ customSvgCode, iconType, strokeWidth });
    };

    return (
        <>
            <Library
                onClose={closeModal}
                onIconSelect={handleIconSelect}
                onCustomSvgInsert={handleCustomSvgInsert}
                currentIconName={iconName}
                currentCustomSvg={customSvgCode}
                currentIconSize={iconSize}
                currentStrokeWidth={strokeWidth}
                modalState={modalState}
                setModalState={setModalState}
            />
            <BaseControl id="gutenlayouts-icon-settings" label={label}>
                <Dropdown
                    popoverProps={{
                        placement: 'left-start',
                        offset: 36,
                        shift: true
                    }}
                    className="gutenlayouts-icon-settings"
                    renderToggle={({ isOpen, onToggle, onClose }) => (
                        <div className="gutenlayouts-icon-settings__dropdown">
                            <Button
                                onClick={onToggle}
                                aria-expanded={isOpen}
                                className="gutenlayouts-icon-settings__dropdown-toggle"
                                __next40pxDefaultSize
                                __nextHasNoMarginBottom
                            >
                                {!!(iconName || customSvgCode) ? (
                                    <span className="gutenlayouts-icon-settings__indicator">
                                        <RenderIcon customSvgCode={customSvgCode} iconName={iconName} size={20} />
                                    </span>
                                ) : (
                                    <span className="gutenlayouts-icon-settings__indicator disabled"></span>
                                )}

                                <span className="gutenlayouts-icon-settings__dropdown-label">
                                    {__(iconName || (customSvgCode ? 'Custom SVG' : 'Select Icon', 'gl-layout-builder'))}
                                </span>
                            </Button>
                            <Button
                                label={__('Browse library', 'gl-layout-builder')}
                                onClick={() => {
                                    onClose();
                                    openModal('library');
                                }}
                                iconSize={18}
                                size="small"
                                icon={folderOpen}
                                className="gutenlayouts-icon-settings__dropdown-more"
                                __next40pxDefaultSize
                                __nextHasNoMarginBottom
                            />
                        </div>
                    )}
                    renderContent={({ onClose }) => <QuickInserter setIcon={handleIconSelect} onClose={onClose} openModal={openModal} />}
                />
            </BaseControl>
        </>
    );
};

export default NativeIconPicker;
