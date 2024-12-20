import { useState, useEffect } from 'react';
import {
	SettingOutlined,
	ShareAltOutlined,
	GooglePlusOutlined,
} from '@ant-design/icons';
import { Tabs, Alert } from 'antd';
import SpreadsheetSetting from './TabContent/SpreadsheetSetting';
import SupportedPlugins from './TabContent/SupportedPlugins';
import SheetMapping from './TabContent/SheetMapping';
import { __ } from '@wordpress/i18n';
const NavTabs = () => {
	// States
	const [ disabledTab, setDisabledTab ] = useState( false );
	const [ activeTab, setActiveTab ] = useState( '1' );

	useEffect( () => {
		// Get active tab from local storage
		const savedTab = localStorage.getItem( 'activeTab' );
		if ( savedTab ) {
			setActiveTab( savedTab );
		}
	}, [] );

	useEffect( () => {
		// Save active tab to local storage
		localStorage.setItem( 'activeTab', activeTab );
	}, [ activeTab ] );

	// Handle Disable Tab Feature
	const handleTabVisibility = ( value ) => {
		setDisabledTab( value );
	};

	return (
		<>
			{ disabledTab === true && (
				<Alert
					description={ __(
						'Please add credention file to access the tabs.',
						'sa-integrations-for-google-sheets'
					) }
					type="error"
					showIcon={ true }
				/>
			) }
			<Tabs
				activeKey={ activeTab }
				onChange={ ( key ) => {
					setActiveTab( key );
				} }
				items={ [
					{
						key: '1',
						label: __(
							'Google Account Settings',
							'sa-integrations-for-google-sheets'
						),
						icon: <SettingOutlined />,
						children: (
							<SpreadsheetSetting
								navVisibility={ handleTabVisibility }
							/>
						),
					},
					{
						key: '2',
						label: __(
							'Supported Plugins',
							'sa-integrations-for-google-sheets'
						),
						icon: <ShareAltOutlined />,
						children: <SupportedPlugins />,
						disabled: disabledTab,
					},
					{
						key: '3',
						label: __(
							'Sheets Integrations',
							'sa-integrations-for-google-sheets'
						),
						icon: <GooglePlusOutlined />,
						children: <SheetMapping />,
						disabled: disabledTab,
					},
				] }
			/>
		</>
	);
};

export default NavTabs;
