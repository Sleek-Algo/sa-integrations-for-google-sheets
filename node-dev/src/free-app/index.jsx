import { __ } from '@wordpress/i18n';
import Header from './components/Header';
import Footer from './components/Footer';
import Content from './components/Content';
import { createRoot } from '@wordpress/element';
import { ConfigProvider } from 'antd';
import './styles/app.scss';

import en_US from 'antd/lib/locale/en_US';

const BackendApp = () => {
	return (
		<ConfigProvider
			locale={ en_US }
			direction={ saifgs_customizations_localized_objects?.language_dir }
			prefixCls="saifgs"
			theme={ {
				token: {
					colorPrimary: '#1677FF',
					borderRadius: 6,
				},
				components: {
					Typography: {
						titleMarginTop: '0px',
					},
					Segmented: {
						itemSelectedBg: '#1677ff',
						itemSelectedColor: '#fff',
					},
					Tooltip: {
						zIndexPopup: 9999999999999,
					},
				},
			} }
		>
			<div id="saifgs-app-main">
				<Header />
				<Content />
				<Footer />
			</div>
		</ConfigProvider>
	);
};

createRoot( document.getElementById( 'saifgs-app' ) ).render( <BackendApp /> );
