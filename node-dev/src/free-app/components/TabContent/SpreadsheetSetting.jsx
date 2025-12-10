import React, { useState, useEffect } from 'react';
import { message, Button, Divider, Row, Col, Alert, Flex, Card, Steps, Tag } from 'antd';
import { DeleteOutlined, DownOutlined, ExportOutlined, CheckCircleFilled, CloseCircleFilled, SafetyCertificateOutlined } from '@ant-design/icons';
import { GoogleDriveIcon, GoogleSheetsIcon, } from '../../utilities/custom-icons';
import { ProForm, ProCard, ProFormUploadDragger, ProFormText, ProFormTextArea, } from '@ant-design/pro-components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import '../../styles/spreadsheetsetting.scss';

const { Meta } = Card;
const { Step } = Steps;

const IntegrationSetting = ( { navVisibility } ) => {
	const [ fileList, setFileList ] = useState( [] );
	const [ uploadedFile, setUploadedFile ] = useState( null );
	const [ jsonContent, setJsonContent ] = useState( null );
	const [ settingAPIResponse, setSettingAPIResponse ] = useState( null );
	const [ isVisible, setIsVisible ] = useState( false );

	// Auto Connect States
	const [ autoConnectStatus, setAutoConnectStatus ] = useState( 'not_connected' );
	const [ connectedEmail, setConnectedEmail ] = useState( '' );
	const [ isAuthenticating, setIsAuthenticating ] = useState( false );

	// Determine which section is active
	const isAutoConnected = autoConnectStatus === 'connected';
	const isManualConnected = uploadedFile !== null;

	// Update navVisibility based on connection status
	useEffect(() => {
		console.log('isAutoConnected : ', isAutoConnected);
		console.log('isManualConnected : ', isManualConnected);
		console.log('navVisibility : ', navVisibility);
		if (isAutoConnected) {
			navVisibility(false);
		} else if (isManualConnected) {
			navVisibility(false);
		} else {
			navVisibility(true);
		}
	}, [isAutoConnected, isManualConnected, navVisibility]);

	useEffect( () => {
		const fetchSettings = async () => {
			try {
				const response = await apiFetch( {
					path: '/saifgs/v1/get-integration-setting/',
					method: 'GET',
				} );
				setSettingAPIResponse( response );
				if ( response.uploadedFile ) {
					// navVisibility( false );
					setUploadedFile( response.uploadedFile );
					setJsonContent( response.json_data );
				} else {
					// navVisibility( true );
				}

				// Fetch auto connect status
				const autoConnectResponse = await apiFetch( {
					path: '/saifgs/v1/get-auto-connect-status/',
					method: 'GET',
				} );
				
				if ( autoConnectResponse.success ) {
					setAutoConnectStatus( autoConnectResponse.data.status );
					setConnectedEmail( autoConnectResponse.data.email || '' );
				}
			} catch ( error ) {
				message.error( 'Failed to fetch settings.' );
			}
		};
		fetchSettings();
	}, [] );

	// Auto Connect Handlers
	const handleAutoConnect = async () => {
		setIsAuthenticating( true );
		try {
			const response = await apiFetch( {
				path: '/saifgs/v1/initiate-auto-connect/',
				method: 'POST',
				data: {
					_wpnonce: saifgs_customizations_localized_objects.nonce
				}
			} );
			
			if ( response.success && response.data.auth_url ) {
				// Redirect to Google OAuth
				window.location.href = response.data.auth_url;
			} else {
				message.error( __( 'Failed to initiate authentication', 'sa-integrations-for-google-sheets' ) );
			}
		} catch ( error ) {
			message.error( __( 'Authentication failed. Please try again.', 'sa-integrations-for-google-sheets' ) );
		} finally {
			setIsAuthenticating( false );
		}
	};

	const handleDeactivateAutoConnect = async () => {
		setIsAuthenticating( true );
		try {
			const response = await apiFetch( {
				path: '/saifgs/v1/deactivate-auto-connect/',
				method: 'POST',
				data: {
					_wpnonce: saifgs_customizations_localized_objects.nonce
				}
			} );
			
			if ( response.success ) {
				setAutoConnectStatus( 'not_connected' );
				setConnectedEmail( '' );
				message.success( __( 'Successfully disconnected from Google', 'sa-integrations-for-google-sheets' ) );
			} else {
				message.error( response.message || __( 'Failed to disconnect', 'sa-integrations-for-google-sheets' ) );
			}
		} catch ( error ) {
			message.error( __( 'Failed to disconnect. Please try again.', 'sa-integrations-for-google-sheets' ) );
		} finally {
			setIsAuthenticating( false );
		}
	};

	const handleRemoveFile = async () => {
		try {
			const response_remove = await apiFetch( {
				path: '/saifgs/v1/remove-file/',
				method: 'POST',
				data: {
					_wpnonce: saifgs_customizations_localized_objects.nonce 
				}
			} );
			// navVisibility( true );
			setUploadedFile( null );
			setJsonContent( null );
			setSettingAPIResponse( null );
			message.success( __( 'File removed successfully!', 'sa-integrations-for-google-sheets' ) );
		} catch ( error ) {
			message.error( __( 'Failed to remove file. Please try again.', 'sa-integrations-for-google-sheets' ) );
		}
	};
	const handleToggle = () => {
		setIsVisible( ( prevState ) => ! prevState );
	};

	const formInitialValues = {
		type: '',
		project_id: '',
		private_key_id: '',
		private_key: '',
		client_email: '',
		client_id: '',
		auth_uri: '',
		token_uri: '',
		auth_provider_x509_cert_url: '',
		client_x509_cert_url: '',
		universe_domain: '',
	};

	// Don't show manual section if auto connected
	const showManualSection = !isAutoConnected;
	// Don't show auto connect section if manual connected
	const showAutoConnectSection = !isManualConnected;

	return (
		<div className="saifgs-app-tab-content-container spreadsheet">
			{/* Auto Connect Section - Only show when not manually connected */}
			{showAutoConnectSection && (
				<>
					{/* Auto Connect Section */}
					<ProCard
						title={ __( 'Auto Google API Configuration', 'sa-integrations-for-google-sheets' ) }
						headerBordered
						className="auto-connect-section"
						extra={
							autoConnectStatus === 'connected' ? (
								<Tag color="green" icon={<CheckCircleFilled />}>
									{ __( 'Connected', 'sa-integrations-for-google-sheets' ) }
								</Tag>
							) : (
								<Tag color="red" icon={<CloseCircleFilled />}>
									{ __( 'Not Connected', 'sa-integrations-for-google-sheets' ) }
								</Tag>
							)
						}
					>
						<Row gutter={[24, 24]}>
							<Col xs={24} lg={16}>
								<div className="auto-connect-content">
									<h3 className="section-subtitle">
										{ __( 'Use Built-in Google API Configuration', 'sa-integrations-for-google-sheets' ) }
									</h3>
									
									<Alert
										message={ __( 'Automatic Integration', 'sa-integrations-for-google-sheets' ) }
										description={ 
											__('Automatic integration allows you to connect with Google Sheets using built-in Google API configuration. By authorizing your Google account, the plugin will handle API setup and authentication automatically, enabling seamless data sync.', 'sa-integrations-for-google-sheets' )
										}
										type="info"
										showIcon
										style={{ marginBottom: 20 }}
									/>

									{autoConnectStatus === 'not_connected' && (
										<Card 
											className="auth-steps-card"
											title={ __( 'Authentication Steps', 'sa-integrations-for-google-sheets' ) }
										>
											<Steps direction="vertical" current={0}>
												<Step 
													title={ __( 'Sign In with Google', 'sa-integrations-for-google-sheets' ) }
													description={ __( 'Click on the "Sign In With Google" button to start the authentication process.', 'sa-integrations-for-google-sheets' ) }
												/>
												<Step 
													title={ __( 'Grant Permissions', 'sa-integrations-for-google-sheets' ) }
													description={ 
														<div>
															<div>{ __( 'Grant permissions for the following services:', 'sa-integrations-for-google-sheets' ) }</div>
															<div style={{ marginTop: 8 }}>
																<Tag icon={<GoogleDriveIcon />} color="blue">
																	Google Drive
																</Tag>
																<Tag icon={<GoogleSheetsIcon />} color="green">
																	Google Sheets
																</Tag>
															</div>
															<div style={{ marginTop: 8, fontSize: '12px', color: '#ff4d4f' }}>
																* { __( 'Ensure that you enable the checkbox for each of these services.', 'sa-integrations-for-google-sheets' ) }
															</div>
														</div>
													}
												/>
												<Step 
													title={ __( 'Automatic Configuration', 'sa-integrations-for-google-sheets' ) }
													description={ __( 'The plugin will automatically handle API setup and authentication for seamless integration.', 'sa-integrations-for-google-sheets' ) }
												/>
											</Steps>
										</Card>
									)}

									{autoConnectStatus === 'connected' && connectedEmail && (
										<Alert
											message={ __( 'Connected Successfully', 'sa-integrations-for-google-sheets' ) }
											description={
												<div>
													<strong>{ __( 'Connected Email Account:', 'sa-integrations-for-google-sheets' ) }</strong>{' '}
													{connectedEmail}
												</div>
											}
											type="success"
											showIcon
											style={{ marginBottom: 20 }}
										/>
									)}

									<div className="auth-actions">
										{autoConnectStatus === 'connected' ? (
											<Flex gap="middle">
												<Button 
													type="primary" 
													danger
													icon={<CloseCircleFilled />}
													onClick={handleDeactivateAutoConnect}
													loading={isAuthenticating}
												>
													{ __( 'Deactivate Connection', 'sa-integrations-for-google-sheets' ) }
												</Button>
											</Flex>
										) : (
											<Button 
												onClick={handleAutoConnect}
												loading={isAuthenticating}
												size="large"
												className="google-auth-btn"
												target="_blank"
												style={{ 
													padding: 0, 
													height: 'auto', 
													background: 'transparent',
													border: 'none',
													boxShadow: 'none'
												}}
											>
												<img 
													src={saifgs_customizations_localized_objects.btn_google_signin} 
													alt={__( 'Sign In With Google', 'sa-integrations-for-google-sheets' )}
													style={{ 
														display: 'block',
														height: '46px', // Standard Google button height
														width: 'auto'
													}}
												/>
											</Button>
										)}
									</div>
								</div>
							</Col>
							
							<Col xs={24} lg={8}>
								<Card 
									className="privacy-card"
									title={ __( 'Privacy & Security', 'sa-integrations-for-google-sheets' ) }
									headStyle={{ backgroundColor: '#f0f8ff', borderBottom: '1px solid #d6e4ff' }}
								>
									<Meta
										description={
											<div className="privacy-content">
												<div style={{ marginBottom: 12 }}>
													<SafetyCertificateOutlined style={{ color: '#52c41a', marginRight: 8 }} />
													<strong>{ __( 'Your Data is Secure', 'sa-integrations-for-google-sheets' ) }</strong>
												</div>
												<p style={{ fontSize: '13px', lineHeight: '1.5', color: '#666' }}>
													{ __( 'We do not store any of the data from your Google account on our servers. Everything is processed & stored on your server. We take your privacy extremely seriously and ensure it is never misused.', 'sa-integrations-for-google-sheets' ) }
												</p>
											</div>
										}
									/>
								</Card>
							</Col>
						</Row>
					</ProCard>
					{/* Only show OR Divider when both sections could be visible */}
					{!isManualConnected && !isAutoConnected && (
						<Divider>{ __( 'OR', 'sa-integrations-for-google-sheets' ) }</Divider>
					)}
				</>
			)}
			
			{/* Existing Manual Configuration Section - Only show when not auto connected */}
			{showManualSection && (
				<ProForm
					initialValues={ formInitialValues }
					layout="vertical"
					submitter={ {
						searchConfig: {
							resetText: __( 'Reset', 'sa-integrations-for-google-sheets' ),
							submitText: __( 'Submit', 'sa-integrations-for-google-sheets' ),
						},
						resetButtonProps: {
							style: { display: 'none', },
						},
						submitButtonProps: {
							style: { display: 'none', },
						},
					} }
				>
					<ProCard
						title={ __( 'Google Account Credentials', 'sa-integrations-for-google-sheets' ) }
						extra={
							<div>
								<Flex vertical gap="small">
									<Flex gap="small" wrap>
										<Button>
											<GoogleDriveIcon />
											{ uploadedFile ? (
												<CheckCircleFilled style={ { color: '#52C41A' } } />
											) : (
												<CloseCircleFilled style={ { color: '#FF4D4F' } } />
											) }
										</Button>
										<Button>
											<GoogleSheetsIcon />
											{ uploadedFile ? (
												<CheckCircleFilled style={ { color: '#52C41A' } } />
											) : (
												<CloseCircleFilled style={ { color: '#FF4D4F' } } />
											) }
										</Button>
									</Flex>
								</Flex>
							</div>
						}
					>
						{ uploadedFile ? (
							<Row>
								<Col span={ 24 }>
									<div>
										{ __('You can generate your Google account credentials file according to the official documentation from', 'sa-integrations-for-google-sheets' ) }
										<a target="_blank" href="https://developers.google.com/workspace/guides/create-credentials" >
											{ __('here', 'sa-integrations-for-google-sheets' ) } <ExportOutlined />
										</a>
										.
									</div>
									<Alert
										style={ { marginTop: '15px' } }
										message={
											<div>
												<strong>
													{ __( 'Uploaded File:', 'sa-integrations-for-google-sheets' ) }
												</strong>{ ' ' }
												{ uploadedFile.name }
											</div>
										}
										type="success"
										showIcon
										action={
											<>
												<Button
													icon={ <DeleteOutlined /> }
													onClick={ handleRemoveFile }
												>
													{ __( 'Remove File', 'sa-integrations-for-google-sheets' ) }
												</Button>
												<Button
													icon={ <DownOutlined /> }
													onClick={ handleToggle }
													className="sa-toggleBtn"
												>
													{ __( 'View Detail', 'sa-integrations-for-google-sheets' ) }
												</Button>
											</>
										}
									/>
								</Col>
							</Row>
						) : (
							<>
								<ProFormUploadDragger
									label={ __( 'Google Services API Credentials', 'sa-integrations-for-google-sheets' ) }
									name={ [ 'json_file' ] }
									fieldProps={ {
										name: __( 'json_file', 'sa-integrations-for-google-sheets' ),
										headers: {
											'X-WP-Nonce': saifgs_customizations_localized_objects.nonce
										},
										data: {
											_wpnonce: saifgs_customizations_localized_objects.nonce,
										}
									} }
									title={ __( 'Google Services API Credentials', 'sa-integrations-for-google-sheets' ) }
									description={ __( 'Upload your Google Services API credentials JSON file.', 'sa-integrations-for-google-sheets' ) }
									fileList={ fileList }
									beforeUpload={(file) => {
										// Additional client-side validation
										const isJson = file.type === 'application/json';
										if (!isJson) {
											message.error(__('You can only upload JSON files!', 'sa-integrations-for-google-sheets'));
										}
										return isJson ? false : Upload.LIST_IGNORE;
									}}
									maxCount={ 1 }
									accept=".json"
									action="/wp-json/saifgs/v1/save-settings/"
									onChange={ ( info ) => {
										if ( info.file.status === 'done' ) {
											const response = info.file.response;
											if ( response && response.success && response.data.uploadedFile ) {
												const jsonContent = response.data.uploadedFile .jsonContent;
												setJsonContent( jsonContent );
												// navVisibility( false );
												setUploadedFile( response.data.uploadedFile );
											} else {
												// navVisibility( true );
												message.error( __( 'Failed to upload file. Please try again.', 'sa-integrations-for-google-sheets' ) );
											}
										}
									} }
								/>
							</>
						) }
					</ProCard>

					{ jsonContent && (
						<div className={ `slide-container ${ isVisible ? 'expanded' : 'collapsed' }` } >
							<Divider>
								{ __( 'JSON File Contents', 'sa-integrations-for-google-sheets' ) }
							</Divider>
							<ProCard
								className="read-only-text"
								title={ __( 'JSON Extracted Fields', 'sa-integrations-for-google-sheets' ) }
							>
								<ProFormText
									name="type"
									label={ __( 'Type', 'sa-integrations-for-google-sheets' ) }
									fieldProps={ {
										readOnly: true,
										value: jsonContent.type,
										color: 'gray',
									} }
								/>
								<ProFormText
									name="project_id"
									label={ __( 'Project Id', 'sa-integrations-for-google-sheets' ) }
									fieldProps={ {
										readOnly: true,
										value: jsonContent.project_id,
									} }
								/>
								<ProFormText
									name="private_key_id"
									label={ __( 'Private Key ID', 'sa-integrations-for-google-sheets' ) }
									fieldProps={ {
										readOnly: true,
										value: jsonContent.private_key_id,
									} }
								/>
								<ProFormTextArea
									name="private_key"
									label={ __( 'Private Key', 'sa-integrations-for-google-sheets' ) }
									fieldProps={ {
										readOnly: true,
										value: jsonContent.private_key,
										rows: 29,
									} }
								/>
								<ProFormText
									name="client_email"
									label={ __( 'Client Email', 'sa-integrations-for-google-sheets' ) }
									fieldProps={ {
										readOnly: true,
										value: jsonContent.client_email,
									} }
								/>
								<ProFormText
									name="client_id"
									label={ __( 'Client ID', 'sa-integrations-for-google-sheets' ) }
									fieldProps={ {
										readOnly: true,
										value: jsonContent.client_id,
									} }
								/>
								<ProFormText
									name="auth_uri"
									label={ __( 'Auth URI', 'sa-integrations-for-google-sheets' ) }
									fieldProps={ {
										readOnly: true,
										value: jsonContent.auth_uri,
									} }
								/>
								<ProFormText
									name="token_uri"
									label={ __( 'Token URI', 'sa-integrations-for-google-sheets' ) }
									fieldProps={ {
										readOnly: true,
										value: jsonContent.token_uri,
									} }
								/>
								<ProFormText
									name="auth_provider_x509_cert_url"
									label={ __( 'Auth Provider x509 Cert URL', 'sa-integrations-for-google-sheets' ) }
									fieldProps={ {
										readOnly: true,
										value: jsonContent.auth_provider_x509_cert_url,
									} }
								/>
								<ProFormText
									name="client_x509_cert_url"
									label={ __( 'Client x509 Cert URL', 'sa-integrations-for-google-sheets' ) }
									fieldProps={ {
										readOnly: true,
										value: jsonContent.client_x509_cert_url,
									} }
								/>
								<ProFormText
									name="universe_domain"
									label={ __( 'Universe Domain', 'sa-integrations-for-google-sheets' ) }
									fieldProps={ {
										readOnly: true,
										value: jsonContent.universe_domain,
									} }
								/>
							</ProCard>
						</div>
					) }
				</ProForm>
			)}
		</div>
	);
};

export default IntegrationSetting;
