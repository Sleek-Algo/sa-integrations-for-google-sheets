import { useState, useEffect } from 'react';
import { Row, Col, message, Tooltip, Divider } from 'antd';
import {
	QuestionCircleOutlined,
	AuditOutlined,
	ArrowRightOutlined,
} from '@ant-design/icons';
import {
	ProCard,
	ProFormGroup,
	ProFormSwitch,
} from '@ant-design/pro-components';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import '../../styles/supportplugin.scss';
const SupportedPlugins = () => {
	/**
	 * States
	 */
	const [ isApiProcessing, setIsApiProcessing ] = useState( false );
	const [ availablePluginList, setAvailablePluginList ] = useState( [] );
	const [ selectedPluginsList, setSelectedPluginsList ] = useState( [] );
	const [ integrationStatuses, setIntegrationStatuses ] = useState( {} ); // Track status per plugin
	const sa_tooltip = {
		display: 'flex',
		justifyContent: 'space-between',
		marginTop: '0px',
		color: '#000000e0',
		opacity: '0.7',
	};
	const pluginValidation = async (
		checked,
		id,
		key,
		usability_status,
		availability_status,
		title
	) => {
		if ( checked ) {
			const response = await apiFetch( {
				path: addQueryArgs( '/saifgs/v1/plugin-integrations/', {
					checked: { checked },
					plugin_name: { key },
				} ),
				method: 'GET',
			} );
			if ( response ) {
				saveValues(
					checked,
					id,
					key,
					usability_status,
					availability_status,
					title
				);
			} else {
				message.error(
					__(
						'Please activate your ',
						'sa-integrations-for-google-sheets'
					) +
						title +
						__( ' plugin', 'sa-integrations-for-google-sheets' )
				);
			}
		} else {
			saveValues(
				checked,
				id,
				key,
				usability_status,
				availability_status,
				title
			);
		}
	};
	/*
	save and update plugin values
	*/
	const saveValues = (
		checked,
		id,
		key,
		usability_status,
		availability_status,
		title
	) => {
		let updatedSelectedPluginsList = selectedPluginsList;
		if ( checked ) {
			const newPaymentMethodsData = {
				id: id,
				title: title,
				usability_status: usability_status,
				availability_status: availability_status,
			};
			updatedSelectedPluginsList.push( newPaymentMethodsData );
		} else {
			const index = updatedSelectedPluginsList.findIndex(
				( method ) => method.title === title
			);
			if ( index > -1 ) {
				updatedSelectedPluginsList.splice( index, 1 );
			}
		}
		setSelectedPluginsList( updatedSelectedPluginsList );
		apiFetch( {
			path: addQueryArgs( '/saifgs/v1/integrated-plugins-list/', {
				data: {
					checked: checked,
					id: id,
					key: key,
					usability_status: usability_status,
					availability_status: availability_status,
				},
			} ),
			method: 'POST',
		} ).then( () => {
			apiFetch( {
				path: '/wp/v2/settings/',
				method: 'POST',
				data: {
					saifgs_supported_plugins: updatedSelectedPluginsList,
				},
			} ).then( ( response ) => {
				if ( response.success ) {
					message.success(
						__(
							'Congratulations! Your settings have been successfully saved.',
							'sa-integrations-for-google-sheets'
						)
					);
				}
				setIsApiProcessing( false ); // Set API Processing State
			} );
		} );
	};
	useEffect( () => {
		apiFetch( {
			path: '/saifgs/v1/integrated-plugins-list/',
			method: 'GET',
		} )
			.then( ( response ) => {
				// Ensure the response is an array before setting it to state
				if ( Array.isArray( response ) ) {
					setAvailablePluginList( response );
					// Initialize integration statuses
					const initialStatuses = response.reduce(
						( acc, plugin ) => {
							acc[ plugin.key ] =
								plugin.usability_status === 'yes';
							return acc;
						},
						{}
					);
					setIntegrationStatuses( initialStatuses );
				} else {
					// Handle the case where response is not an array
					console.error( 'Unexpected API response:', response );
					setAvailablePluginList( [] );
				}
			} )
			.catch( ( error ) => {
				console.error( 'API Fetch Error:', error );
				setAvailablePluginList( [] );
			} );
	}, [] );

	const handleSwitchChange = async ( checked, plugin ) => {
		setIntegrationStatuses( ( prevStatuses ) => ( {
			...prevStatuses,
			[ plugin.key ]: checked,
		} ) );
		pluginValidation(
			checked,
			plugin.id,
			plugin.key,
			plugin.usability_status,
			plugin.availability_status,
			plugin.title
		);
	};
	return (
		<div className="saifgs-app-tab-content-container">
			<Row gutter={ [ 16, 16 ] } type="flex">
				{ availablePluginList.map( ( plugin ) => (
					<Col
						xs={ { flex: '100%' } }
						sm={ { flex: '50%' } }
						md={ { flex: '50%' } }
						lg={ { flex: '25%' } }
						xl={ { flex: '25%' } }
						style={ { height: 'auto' } }
						key={ plugin.key }
					>
						<ProCard
							className="hover-effect"
							bordered
							style={ { height: '100%' } }
						>
							<div className="saifgs-main">
								<div className="img-h">
									<span className="new-log">
										{ __(
											'New',
											'sa-integrations-for-google-sheets'
										) }
									</span>
									<img src={ plugin.image_url } alt="" />
								</div>
								<div className="first-content">
									<h4 style={ { margin: 0 } }>
										{ plugin.title }
									</h4>
									<p style={ { flexGrow: '1' } }>
										{ plugin.discription }
									</p>
									<a
										href={ plugin?.url }
										target="_blank"
										className="learn-more"
									>
										{ __(
											'Learn more',
											'sa-integrations-for-google-sheets'
										) }
										<ArrowRightOutlined />
									</a>
								</div>
								<Divider />
								<div className="last-content">
									<p style={ { flexBasis: '80%' } }>
										{ integrationStatuses[ plugin.key ]
											? __(
													'Integration is Enabled',
													'sa-integrations-for-google-sheets'
											  )
											: __(
													'Integration Status',
													'sa-integrations-for-google-sheets'
											  ) }
									</p>
									<ProFormGroup style={ { width: 'auto' } }>
										<ProFormSwitch
											noStyle
											checkedChildren={ __(
												'active',
												'sa-integrations-for-google-sheets'
											) }
											unCheckedChildren={ __(
												'inactive',
												'sa-integrations-for-google-sheets'
											) }
											data-parent_id={ plugin.title }
											fieldProps={ {
												checked:
													integrationStatuses[
														plugin.key
													],
											} }
											onChange={ ( checked ) =>
												handleSwitchChange(
													checked,
													plugin
												)
											}
										/>
									</ProFormGroup>
								</div>
								<div style={ sa_tooltip }>
									{ plugin.help && (
										<span>
											{ __(
												'Help',
												'sa-integrations-for-google-sheets'
											) }
											<Tooltip
												title={ plugin.help }
												zIndex={ 9999 }
												placement="bottom"
												overlayInnerStyle={ {
													backgroundColor: '#fff',
													color: 'black',
												} }
											>
												<QuestionCircleOutlined
													style={ {
														fontSize: '16px',
													} }
												/>
											</Tooltip>
										</span>
									) }
									{ plugin.legal && (
										<span>
											{ __(
												'Legal*',
												'sa-integrations-for-google-sheets'
											) }
											<Tooltip
												title={ plugin.legal }
												color="#fff"
												zIndex={ 9999 }
												placement="top"
												overlayInnerStyle={ {
													backgroundColor: '#fff',
													color: 'black',
												} }
											>
												<AuditOutlined
													style={ {
														color: '#00C4C4',
														fontSize: '16px',
													} }
												/>
											</Tooltip>
										</span>
									) }
								</div>
							</div>
						</ProCard>
					</Col>
				) ) }
			</Row>
		</div>
	);
};
export default SupportedPlugins;
