import { Button, Modal, SearchControl } from '@wordpress/components';
import { RawHTML, useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import defaultIcons from './icons.json';

const IconPicker = ({ iconsPanel = false, setIconsPanel, value, onChange, customIcons = null }) => {
    const [filterIcons, setFilterIcons] = useState([]);
    const [searchText, setSearchText] = useState('');

    const iconsSource = customIcons || defaultIcons;

    const allSvgItems = useMemo(() => {
        return iconsSource.map(icon => ({
            label: icon.label,
            svg: icon.svg,
            terms: icon.terms
        }));
    }, [iconsSource]);

    useEffect(() => {
        let displayIcons = allSvgItems;

        if (searchText) {
            displayIcons = displayIcons.filter(
                item =>
                    item.label.toLowerCase().includes(searchText.toLowerCase()) ||
                    item.terms.some(term => term.toLowerCase().includes(searchText.toLowerCase()))
            );

            if (displayIcons.length === 0) {
                displayIcons = [
                    {
                        label: __('No Icons Found', 'svg-icon-block'),
                        svg: {
                            solid: {
                                width: 512,
                                height: 512,
                                path: 'M256 0C114.62 0 0 114.62 0 256s114.62 256 256 256 256-114.62 256-256S397.38 0 256 0zm0 480C132.48 480 32 379.52 32 256S132.48 32 256 32s224 100.48 224 224-100.48 224-224 224zM336 192H176a16 16 0 0 0-16 16v64a16 16 0 0 0 16 16h160a16 16 0 0 0 16-16v-64a16 16 0 0 0-16-16zm-16 64H192v-32h128z'
                            }
                        }
                    }
                ];
            }
        }

        setFilterIcons(displayIcons);
    }, [allSvgItems, searchText]);

    const generateIcon = icon => {
        let iconCat;
        if (icon.svg.solid) {
            iconCat = 'solid';
        } else if (icon.svg.brands) {
            iconCat = 'brands';
        } else if (icon.svg.regular) {
            iconCat = 'regular';
        }

        const svgData = icon.svg[iconCat];
        const width = svgData.width || 16;
        const height = svgData.height || width; // height না থাকলে width use করবে

        // viewBox সঠিকভাবে set করা
        const viewBox = `0 0 ${width} ${height}`;

        const svgRaw = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${viewBox}" fill="currentColor"><path d="${svgData.path}" /></svg>`;
        return svgRaw;
    };

    return (
        <>
            <div className="svgib-icon-picker">
                {iconsPanel && (
                    <Modal className="svgib__modal" title={__('Select Icon', 'svg-icon-block')} onRequestClose={() => setIconsPanel(false)}>
                        <div className="search__input">
                            <SearchControl
                                __nextHasNoMarginBottom
                                placeholder={__('Search Icon', 'svg-icon-block')}
                                onChange={e => setSearchText(e)}
                                value={searchText}
                            />
                        </div>
                        <div className="svgib-modal__wrapper">
                            <div className="svgib-icons-wrap">
                                <div className="svgib__icons-container">
                                    {filterIcons.map((item, index) => {
                                        const generatedIcon = generateIcon(item);
                                        return (
                                            <Button
                                                key={index}
                                                className={`single__icon ${value === generatedIcon ? 'active' : ''}`}
                                                onClick={() => {
                                                    onChange(generatedIcon);
                                                    setIconsPanel(false); // modal close করার জন্য
                                                }}
                                                title={item.label}
                                            >
                                                <RawHTML className="single__icon_svg" children={generatedIcon} />
                                            </Button>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    </Modal>
                )}
            </div>
        </>
    );
};

export default IconPicker;
