import React, { useState } from 'react';

const App = () => {
    // Состояние для активного раздела
    const [activeSection, setActiveSection] = useState('general');

    // Состояние для настроек
    const [theme, setTheme] = useState('light');
    const [selectedColor, setSelectedColor] = useState('#3498db');
    const [isSecondaryColorEnabled, setIsSecondaryColorEnabled] = useState(false);
    const [buttonTextColor, setButtonTextColor] = useState('white');
    const [siteWidth, setSiteWidth] = useState('1464');
    const [selectedFont, setSelectedFont] = useState('Golos Text');
    const [fontSize, setFontSize] = useState('16');
    const [useCustomHeadingsFont, setUseCustomHeadingsFont] = useState(false);
    const [useSelfHostedFonts, setUseSelfHostedFonts] = useState(true);
    const [roundedButtons, setRoundedButtons] = useState('8');
    const [quickView, setQuickView] = useState(true);
    const [productGallery, setProductGallery] = useState(true);
    const [galleryImagesCount, setGalleryImagesCount] = useState(4);
    const [relatedProductsView, setRelatedProductsView] = useState('Каруселью');
    const [subcategoriesInBreadcrumb, setSubcategoriesInBreadcrumb] = useState('Не отображать');
    const [pricesDisplay, setPricesDisplay] = useState('Списком');
    const [fixedHeader, setFixedHeader] = useState(true);
    const [headerTemplate, setHeaderTemplate] = useState(1);
    const [headerWidth, setHeaderWidth] = useState(true);
    const [catalogPosition, setCatalogPosition] = useState('Каталог в центральной части');
    const [upperPanelColor, setUpperPanelColor] = useState('Прозрачный');
    const [bottomMenuColor, setBottomMenuColor] = useState('Прозрачный');

    // Цветовая палитра
    const colorPalette = [
        '#e74c3c', '#e67e22', '#f1c40f', '#2ecc71',
        '#3498db', '#9b59b6', '#ecf0f1', '#34495e',
        '#f39c12', '#ffeb3b', '#48bb78', '#4286f4'
    ];

    return (
        <div className="min-h-screen bg-gray-50">
            <div className="flex">
                {/* Боковая панель навигации */}
                <div className="w-64 bg-white border-r border-gray-200 p-6 min-h-screen">
                    <h2 className="text-xl font-bold mb-6">Настройки сайта</h2>

                    <nav className="space-y-2">
                        <button
                            onClick={() => setActiveSection('general')}
                            className={`w-full text-left px-4 py-2 rounded-md transition-colors ${
                                activeSection === 'general'
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-700 hover:bg-gray-100'
                            }`}
                        >
                            Общие
                        </button>
                        <button
                            onClick={() => setActiveSection('colors')}
                            className={`w-full text-left px-4 py-2 rounded-md transition-colors ${
                                activeSection === 'colors'
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-700 hover:bg-gray-100'
                            }`}
                        >
                            Цвета
                        </button>
                        <button
                            onClick={() => setActiveSection('typography')}
                            className={`w-full text-left px-4 py-2 rounded-md transition-colors ${
                                activeSection === 'typography'
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-700 hover:bg-gray-100'
                            }`}
                        >
                            Типографика
                        </button>
                        <button
                            onClick={() => setActiveSection('layout')}
                            className={`w-full text-left px-4 py-2 rounded-md transition-colors ${
                                activeSection === 'layout'
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-700 hover:bg-gray-100'
                            }`}
                        >
                            Макет
                        </button>
                        <button
                            onClick={() => setActiveSection('catalog')}
                            className={`w-full text-left px-4 py-2 rounded-md transition-colors ${
                                activeSection === 'catalog'
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-700 hover:bg-gray-100'
                            }`}
                        >
                            Каталог
                        </button>
                        <button
                            onClick={() => setActiveSection('header')}
                            className={`w-full text-left px-4 py-2 rounded-md transition-colors ${
                                activeSection === 'header'
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-gray-700 hover:bg-gray-100'
                            }`}
                        >
                            Шапка
                        </button>
                    </nav>

                    <div className="mt-10">
                        <h3 className="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Дополнительно</h3>
                        <nav className="space-y-2">
                            <button className="w-full text-left px-4 py-2 rounded-md text-gray-700 hover:bg-gray-100">
                                Поделиться настройками
                            </button>
                            <button className="w-full text-left px-4 py-2 rounded-md text-gray-700 hover:bg-gray-100">
                                Обновления
                            </button>
                            <button className="w-full text-left px-4 py-2 rounded-md text-gray-700 hover:bg-gray-100">
                                Получить демодоступ
                            </button>
                        </nav>
                    </div>
                </div>

                {/* Основной контент */}
                <div className="flex-1 p-8">
                    <div className="max-w-4xl mx-auto">
                        <div className="bg-white rounded-lg shadow-sm p-6">
                            {activeSection === 'general' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium mb-3">Оформление</h3>
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => setTheme('auto')}
                                                className={`px-4 py-2 rounded-md ${theme === 'auto' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                            >
                                                Автоматически
                                            </button>
                                            <button
                                                onClick={() => setTheme('light')}
                                                className={`px-4 py-2 rounded-md ${theme === 'light' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                            >
                                                Светлое
                                            </button>
                                            <button
                                                onClick={() => setTheme('dark')}
                                                className={`px-4 py-2 rounded-md ${theme === 'dark' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                            >
                                                Темное
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-medium mb-3">Базовый цвет</h3>
                                        <div className="flex flex-wrap gap-2 mb-4">
                                            {colorPalette.map((color) => (
                                                <button
                                                    key={color}
                                                    onClick={() => setSelectedColor(color)}
                                                    className={`w-8 h-8 rounded-full border-2 ${
                                                        selectedColor === color ? 'border-blue-500 scale-110' : 'border-transparent'
                                                    }`}
                                                    style={{ backgroundColor: color }}
                                                />
                                            ))}
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="text"
                                                value={selectedColor}
                                                onChange={(e) => setSelectedColor(e.target.value)}
                                                className="px-3 py-2 border rounded-md w-32"
                                            />
                                            <button className="p-2 bg-gray-200 rounded-md">
                                                <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.34l.66-3.33a2 2 0 00-1.92-2.39H12a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <label className="flex items-center cursor-pointer">
                                            <div className="relative">
                                                <input
                                                    type="checkbox"
                                                    checked={isSecondaryColorEnabled}
                                                    onChange={() => setIsSecondaryColorEnabled(!isSecondaryColorEnabled)}
                                                    className="sr-only"
                                                />
                                                <div className={`block w-14 h-8 rounded-full ${isSecondaryColorEnabled ? 'bg-blue-500' : 'bg-gray-300'}`}></div>
                                                <div className={`dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition ${isSecondaryColorEnabled ? 'transform translate-x-6' : ''}`}></div>
                                            </div>
                                            <span className="ml-3">Дополнительный цвет</span>
                                        </label>
                                    </div>
                                </div>
                            )}

                            {activeSection === 'colors' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium mb-3">Цвет текста кнопок</h3>
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => setButtonTextColor('white')}
                                                className={`px-4 py-2 rounded-md ${buttonTextColor === 'white' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                            >
                                                Белый
                                            </button>
                                            <button
                                                onClick={() => setButtonTextColor('black')}
                                                className={`px-4 py-2 rounded-md ${buttonTextColor === 'black' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                            >
                                                Черный
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeSection === 'typography' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium mb-3">Шрифт</h3>
                                        <div className="space-y-4">
                                            {['Golos Text', 'Manrope', 'Rubik', 'Wix Madefor Text'].map((font) => (
                                                <div key={font} className="border p-4 rounded-lg">
                                                    <div className="flex justify-between items-center mb-3">
                                                        <span className="font-medium">{font}</span>
                                                        <div className="flex gap-2">
                                                            {['15', '16', '17'].map((size) => (
                                                                <button
                                                                    key={size}
                                                                    onClick={() => {
                                                                        setSelectedFont(font);
                                                                        setFontSize(size);
                                                                    }}
                                                                    className={`px-3 py-1 rounded-md ${
                                                                        selectedFont === font && fontSize === size ? 'bg-blue-500 text-white' : 'bg-gray-200'
                                                                    }`}
                                                                >
                                                                    {size} px
                                                                </button>
                                                            ))}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <label className="flex items-center cursor-pointer">
                                            <div className="relative">
                                                <input
                                                    type="checkbox"
                                                    checked={useCustomHeadingsFont}
                                                    onChange={() => setUseCustomHeadingsFont(!useCustomHeadingsFont)}
                                                    className="sr-only"
                                                />
                                                <div className={`block w-14 h-8 rounded-full ${useCustomHeadingsFont ? 'bg-blue-500' : 'bg-gray-300'}`}></div>
                                                <div className={`dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition ${useCustomHeadingsFont ? 'transform translate-x-6' : ''}`}></div>
                                            </div>
                                            <span className="ml-3">Другой шрифт заголовков</span>
                                        </label>
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <label className="flex items-center cursor-pointer">
                                            <div className="relative">
                                                <input
                                                    type="checkbox"
                                                    checked={useSelfHostedFonts}
                                                    onChange={() => setUseSelfHostedFonts(!useSelfHostedFonts)}
                                                    className="sr-only"
                                                />
                                                <div className={`block w-14 h-8 rounded-full ${useSelfHostedFonts ? 'bg-blue-500' : 'bg-gray-300'}`}></div>
                                                <div className={`dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition ${useSelfHostedFonts ? 'transform translate-x-6' : ''}`}></div>
                                            </div>
                                            <span className="ml-3">Использовать SELF-HOSTED шрифты</span>
                                        </label>
                                    </div>
                                </div>
                            )}

                            {activeSection === 'layout' && (
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="text-lg font-medium mb-3">Ширина сайта</h3>
                                        <div className="flex gap-2">
                                            {['1696', '1464', '1296'].map((width) => (
                                                <button
                                                    key={width}
                                                    onClick={() => setSiteWidth(width)}
                                                    className={`px-4 py-2 rounded-md ${siteWidth === width ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                                >
                                                    {width} px
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-medium mb-3">Скругление кнопок</h3>
                                        <div className="flex flex-wrap gap-2">
                                            {['0', '8', '12', '16', '20', '24'].map((radius) => (
                                                <button
                                                    key={radius}
                                                    onClick={() => setRoundedButtons(radius)}
                                                    className={`px-4 py-2 rounded-md ${roundedButtons === radius ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                                >
                                                    {radius}px
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeSection === 'catalog' && (
                                <div className="space-y-6">
                                    <div className="flex items-center gap-4">
                                        <label className="flex items-center cursor-pointer">
                                            <div className="relative">
                                                <input
                                                    type="checkbox"
                                                    checked={quickView}
                                                    onChange={() => setQuickView(!quickView)}
                                                    className="sr-only"
                                                />
                                                <div className={`block w-14 h-8 rounded-full ${quickView ? 'bg-blue-500' : 'bg-gray-300'}`}></div>
                                                <div className={`dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition ${quickView ? 'transform translate-x-6' : ''}`}></div>
                                            </div>
                                            <span className="ml-3">Использовать быстрый просмотр товаров</span>
                                        </label>
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <label className="flex items-center cursor-pointer">
                                            <div className="relative">
                                                <input
                                                    type="checkbox"
                                                    checked={productGallery}
                                                    onChange={() => setProductGallery(!productGallery)}
                                                    className="sr-only"
                                                />
                                                <div className={`block w-14 h-8 rounded-full ${productGallery ? 'bg-blue-500' : 'bg-gray-300'}`}></div>
                                                <div className={`dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition ${productGallery ? 'transform translate-x-6' : ''}`}></div>
                                            </div>
                                            <span className="ml-3">Отображать галерею картинок у товаров в списке</span>
                                        </label>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-medium mb-3">Количество картинок в галереи у товаров в списке</h3>
                                        <div className="flex gap-2">
                                            {[1, 2, 3, 4, 5].map((num) => (
                                                <button
                                                    key={num}
                                                    onClick={() => setGalleryImagesCount(num)}
                                                    className={`px-4 py-2 rounded-md ${galleryImagesCount === num ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                                >
                                                    {num}
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-medium mb-3">Вид списка связанных товаров</h3>
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => setRelatedProductsView('Каруселью')}
                                                className={`px-4 py-2 rounded-md ${relatedProductsView === 'Каруселью' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                            >
                                                Каруселью
                                            </button>
                                            <button
                                                onClick={() => setRelatedProductsView('Блочным')}
                                                className={`px-4 py-2 rounded-md ${relatedProductsView === 'Блочным' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                            >
                                                Блочным
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-medium mb-3">Выпадающее меню с подразделами каталога в навигационной цепочке</h3>
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => setSubcategoriesInBreadcrumb('Не отображать')}
                                                className={`px-4 py-2 rounded-md ${subcategoriesInBreadcrumb === 'Не отображать' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                            >
                                                Не отображать
                                            </button>
                                            <button
                                                onClick={() => setSubcategoriesInBreadcrumb('Отображать')}
                                                className={`px-4 py-2 rounded-md ${subcategoriesInBreadcrumb === 'Отображать' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                            >
                                                Отображать
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-medium mb-3">Расширенные цены</h3>
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => setPricesDisplay('Списком')}
                                                className={`px-4 py-2 rounded-md ${pricesDisplay === 'Списком' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                            >
                                                Списком
                                            </button>
                                            <button
                                                onClick={() => setPricesDisplay('Во всплывающем окне')}
                                                className={`px-4 py-2 rounded-md ${pricesDisplay === 'Во всплывающем окне' ? 'bg-blue-500 text-white' : 'bg-gray-200'}`}
                                            >
                                                Во всплывающем окне
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeSection === 'header' && (
                                <div className="space-y-6">
                                    <div className="flex items-center gap-4">
                                        <label className="flex items-center cursor-pointer">
                                            <div className="relative">
                                                <input
                                                    type="checkbox"
                                                    checked={fixedHeader}
                                                    onChange={() => setFixedHeader(!fixedHeader)}
                                                    className="sr-only"
                                                />
                                                <div className={`block w-14 h-8 rounded-full ${fixedHeader ? 'bg-blue-500' : 'bg-gray-300'}`}></div>
                                                <div className={`dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition ${fixedHeader ? 'transform translate-x-6' : ''}`}></div>
                                            </div>
                                            <span className="ml-3">Фиксированная шапка сайта</span>
                                        </label>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-medium mb-3">Варианты шапок</h3>
                                        <div className="space-y-6">
                                            <div className={`border p-4 rounded-lg ${headerTemplate === 1 ? 'border-blue-500 bg-blue-50' : 'border-gray-200'}`}>
                                                <div className="relative aspect-video bg-gray-100 rounded mb-4 overflow-hidden">
                                                    <div className="absolute inset-0 flex items-center justify-center">
                                                        <div className="bg-black bg-opacity-50 text-white text-center p-2 rounded">
                                                            Превью шаблона 1
                                                        </div>
                                                    </div>
                                                </div>
                                                <button
                                                    onClick={() => setHeaderTemplate(1)}
                                                    className={`w-full text-center py-2 rounded-md mb-4 ${
                                                        headerTemplate === 1 ? 'bg-blue-500 text-white' : 'bg-gray-200'
                                                    }`}
                                                >
                                                    Выбрать шаблон 1
                                                </button>

                                                <div className="flex items-center gap-4 mb-4">
                                                    <label className="flex items-center cursor-pointer">
                                                        <div className="relative">
                                                            <input
                                                                type="checkbox"
                                                                checked={headerWidth}
                                                                onChange={() => setHeaderWidth(!headerWidth)}
                                                                className="sr-only"
                                                            />
                                                            <div className={`block w-14 h-8 rounded-full ${headerWidth ? 'bg-blue-500' : 'bg-gray-300'}`}></div>
                                                            <div className={`dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition ${headerWidth ? 'transform translate-x-6' : ''}`}></div>
                                                        </div>
                                                        <span className="ml-3">По ширине контента</span>
                                                    </label>
                                                </div>

                                                <div className="grid grid-cols-3 gap-4">
                                                    <div>
                                                        <label className="block mb-2">Позиция кнопки каталога</label>
                                                        <select
                                                            value={catalogPosition}
                                                            onChange={(e) => setCatalogPosition(e.target.value)}
                                                            className="w-full px-3 py-2 border rounded-md"
                                                        >
                                                            <option>Каталог в центральной части</option>
                                                            <option>Каталог слева</option>
                                                            <option>Каталог справа</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label className="block mb-2">Цвет верхней панели</label>
                                                        <select
                                                            value={upperPanelColor}
                                                            onChange={(e) => setUpperPanelColor(e.target.value)}
                                                            className="w-full px-3 py-2 border rounded-md"
                                                        >
                                                            <option>Прозрачный</option>
                                                            <option>#ffffff</option>
                                                            <option>#f5f5f5</option>
                                                            <option>#e0e0e0</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label className="block mb-2">Цвет нижнего меню</label>
                                                        <select
                                                            value={bottomMenuColor}
                                                            onChange={(e) => setBottomMenuColor(e.target.value)}
                                                            className="w-full px-3 py-2 border rounded-md"
                                                        >
                                                            <option>Прозрачный</option>
                                                            <option>#ffffff</option>
                                                            <option>#f5f5f5</option>
                                                            <option>#e0e0e0</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className={`border p-4 rounded-lg ${headerTemplate === 2 ? 'border-blue-500 bg-blue-50' : 'border-gray-200'}`}>
                                                <div className="relative aspect-video bg-gray-100 rounded mb-4 overflow-hidden">
                                                    <div className="absolute inset-0 flex items-center justify-center">
                                                        <div className="bg-black bg-opacity-50 text-white text-center p-2 rounded">
                                                            Превью шаблона 2
                                                        </div>
                                                    </div>
                                                </div>
                                                <button
                                                    onClick={() => setHeaderTemplate(2)}
                                                    className={`w-full text-center py-2 rounded-md mb-4 ${
                                                        headerTemplate === 2 ? 'bg-blue-500 text-white' : 'bg-gray-200'
                                                    }`}
                                                >
                                                    Выбрать шаблон 2
                                                </button>

                                                <div className="flex items-center gap-4 mb-4">
                                                    <label className="flex items-center cursor-pointer">
                                                        <div className="relative">
                                                            <input
                                                                type="checkbox"
                                                                checked={!headerWidth}
                                                                onChange={() => setHeaderWidth(!headerWidth)}
                                                                className="sr-only"
                                                            />
                                                            <div className={`block w-14 h-8 rounded-full ${!headerWidth ? 'bg-blue-500' : 'bg-gray-300'}`}></div>
                                                            <div className={`dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition ${!headerWidth ? 'transform translate-x-6' : ''}`}></div>
                                                        </div>
                                                        <span className="ml-3">По ширине контента</span>
                                                    </label>
                                                </div>

                                                <div className="grid grid-cols-3 gap-4">
                                                    <div>
                                                        <label className="block mb-2">Позиция кнопки каталога</label>
                                                        <select
                                                            value={catalogPosition}
                                                            onChange={(e) => setCatalogPosition(e.target.value)}
                                                            className="w-full px-3 py-2 border rounded-md"
                                                        >
                                                            <option>Каталог в центральной части</option>
                                                            <option>Каталог слева</option>
                                                            <option>Каталог справа</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label className="block mb-2">Цвет верхней панели</label>
                                                        <select
                                                            value={upperPanelColor}
                                                            onChange={(e) => setUpperPanelColor(e.target.value)}
                                                            className="w-full px-3 py-2 border rounded-md"
                                                        >
                                                            <option>Прозрачный</option>
                                                            <option>#ffffff</option>
                                                            <option>#f5f5f5</option>
                                                            <option>#e0e0e0</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label className="block mb-2">Цвет нижнего меню</label>
                                                        <select
                                                            value={bottomMenuColor}
                                                            onChange={(e) => setBottomMenuColor(e.target.value)}
                                                            className="w-full px-3 py-2 border rounded-md"
                                                        >
                                                            <option>Прозрачный</option>
                                                            <option>#ffffff</option>
                                                            <option>#f5f5f5</option>
                                                            <option>#e0e0e0</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default App;