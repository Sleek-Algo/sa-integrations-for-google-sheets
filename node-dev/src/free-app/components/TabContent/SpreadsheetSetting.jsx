import React, { useState, useEffect } from 'react';
import { message, Button, Divider, Row, Col, Alert, Flex } from 'antd';
import {
	DeleteOutlined,
	DownOutlined,
	ExportOutlined,
	CheckCircleFilled,
	CloseCircleFilled,
} from '@ant-design/icons';
import {
	GoogleDriveIcon,
	GoogleSheetsIcon,
} from '../../utilities/custom-icons';
import {
	ProForm,
	ProCard,
	ProFormUploadDragger,
	ProFormText,
	ProFormTextArea,
} from '@ant-design/pro-components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import '../../styles/spreadsheetsetting.scss';

const IntegrationSetting = ( { navVisibility } ) => {
	const [ fileList, setFileList ] = useState( [] );
	const [ uploadedFile, setUploadedFile ] = useState( null );
	const [ jsonContent, setJsonContent ] = useState( null );
	const [ settingAPIResponse, setSettingAPIResponse ] = useState( null );
	const [ isVisible, setIsVisible ] = useState( false );

	useEffect( () => {
		const fetchSettings = async () => {
			try {
				const response = await apiFetch( {
					path: '/saifgs/v1/get-integration-setting/',
					method: 'GET',
				} );
				setSettingAPIResponse( response );
				if ( response.uploadedFile ) {
					navVisibility( false );
					setUploadedFile( response.uploadedFile );
					setJsonContent( response.json_data );
				} else {
					navVisibility( true );
				}
			} catch ( error ) {
				message.error( 'Failed to fetch settings.' );
			}
		};
		fetchSettings();
	}, [] );

	const handleRemoveFile = async () => {
		try {
			const response_remove = await apiFetch( {
				path: '/saifgs/v1/remove-file/',
				method: 'POST',
			} );
			navVisibility( true );
			setUploadedFile( null );
			setJsonContent( null );
			setSettingAPIResponse( null );
			message.success(
				__(
					'File removed successfully!',
					'sa-integrations-for-google-sheets'
				)
			);
		} catch ( error ) {
			message.error(
				__(
					'Failed to remove file. Please try again.',
					'sa-integrations-for-google-sheets'
				)
			);
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

	return (
		<div className="saifgs-app-tab-content-container spreadsheet">
			<ProForm
				initialValues={ formInitialValues }
				layout="vertical"
				submitter={ {
					searchConfig: {
						resetText: __(
							'Reset',
							'sa-integrations-for-google-sheets'
						),
						submitText: __(
							'Submit',
							'sa-integrations-for-google-sheets'
						),
					},
					resetButtonProps: {
						style: {
							display: 'none',
						},
					},
					submitButtonProps: {
						style: {
							display: 'none',
						},
					},
				} }
			>
				<ProCard
					title={ __(
						'Google Account Credentials',
						'sa-integrations-for-google-sheets'
					) }
					extra={
						<div>
							<Flex vertical gap="small">
								<Flex gap="small" wrap>
									<Button>
										<GoogleDriveIcon />
										{ uploadedFile ? (
											<CheckCircleFilled
												style={ { color: '#52C41A' } }
											/>
										) : (
											<CloseCircleFilled
												style={ { color: '#FF4D4F' } }
											/>
										) }
									</Button>
									<Button>
										<GoogleSheetsIcon />
										{ uploadedFile ? (
											<CheckCircleFilled
												style={ { color: '#52C41A' } }
											/>
										) : (
											<CloseCircleFilled
												style={ { color: '#FF4D4F' } }
											/>
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
									<a
										target="_blank"
										href="https://developers.google.com/workspace/guides/create-credentials"
									>
										{ __('here', 'sa-integrations-for-google-sheets' ) } <ExportOutlined />
									</a>
									.
								</div>
								<Alert
									style={ { marginTop: '15px' } }
									message={
										<div>
											<strong>
												{ __(
													'Uploaded File:',
													'sa-integrations-for-google-sheets'
												) }
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
												{ __(
													'Remove File',
													'sa-integrations-for-google-sheets'
												) }
											</Button>
											<Button
												icon={ <DownOutlined /> }
												onClick={ handleToggle }
												className="sa-toggleBtn"
											>
												{ __(
													'View Detail',
													'sa-integrations-for-google-sheets'
												) }
											</Button>
										</>
									}
								/>
							</Col>
						</Row>
					) : (
						<>
							<ProFormUploadDragger
								label={ __(
									'Google Services API Credentials',
									'sa-integrations-for-google-sheets'
								) }
								name={ [ 'json_file' ] }
								fieldProps={ {
									name: __(
										'json_file',
										'sa-integrations-for-google-sheets'
									),
								} }
								title={ __(
									'Google Services API Credentials',
									'sa-integrations-for-google-sheets'
								) }
								description={ __(
									'Upload your Google Services API credentials JSON file.',
									'sa-integrations-for-google-sheets'
								) }
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
									console.log('onChange={ ( info )', info);
									if ( info.file.status === 'done' ) {
										const response = info.file.response;
										if (
											response &&
											response.success &&
											response.data.uploadedFile
										) {
											const jsonContent =
												response.data.uploadedFile
													.jsonContent;
											setJsonContent( jsonContent );
											navVisibility( false );
											setUploadedFile(
												response.data.uploadedFile
											);
										} else {
											navVisibility( true );
											message.error(
												__(
													'Failed to upload file. Please try again.',
													'sa-integrations-for-google-sheets'
												)
											);
										}
									}
								} }
							/>
						</>
					) }
				</ProCard>

				{ jsonContent && (
					<div
						className={ `slide-container ${
							isVisible ? 'expanded' : 'collapsed'
						}` }
					>
						<Divider>
							{ __(
								'JSON File Contents',
								'sa-integrations-for-google-sheets'
							) }
						</Divider>
						<ProCard
							className="read-only-text"
							title={ __(
								'JSON Extracted Fields',
								'sa-integrations-for-google-sheets'
							) }
						>
							<ProFormText
								name="type"
								label={ __(
									'Type',
									'sa-integrations-for-google-sheets'
								) }
								fieldProps={ {
									readOnly: true,
									value: jsonContent.type,
									color: 'gray',
								} }
							/>
							<ProFormText
								name="project_id"
								label={ __(
									'Project Id',
									'sa-integrations-for-google-sheets'
								) }
								fieldProps={ {
									readOnly: true,
									value: jsonContent.project_id,
								} }
							/>
							<ProFormText
								name="private_key_id"
								label={ __(
									'Private Key ID',
									'sa-integrations-for-google-sheets'
								) }
								fieldProps={ {
									readOnly: true,
									value: jsonContent.private_key_id,
								} }
							/>
							<ProFormTextArea
								name="private_key"
								label={ __(
									'Private Key',
									'sa-integrations-for-google-sheets'
								) }
								fieldProps={ {
									readOnly: true,
									value: jsonContent.private_key,
									rows: 29,
								} }
							/>
							<ProFormText
								name="client_email"
								label={ __(
									'Client Email',
									'sa-integrations-for-google-sheets'
								) }
								fieldProps={ {
									readOnly: true,
									value: jsonContent.client_email,
								} }
							/>
							<ProFormText
								name="client_id"
								label={ __(
									'Client ID',
									'sa-integrations-for-google-sheets'
								) }
								fieldProps={ {
									readOnly: true,
									value: jsonContent.client_id,
								} }
							/>
							<ProFormText
								name="auth_uri"
								label={ __(
									'Auth URI',
									'sa-integrations-for-google-sheets'
								) }
								fieldProps={ {
									readOnly: true,
									value: jsonContent.auth_uri,
								} }
							/>
							<ProFormText
								name="token_uri"
								label={ __(
									'Token URI',
									'sa-integrations-for-google-sheets'
								) }
								fieldProps={ {
									readOnly: true,
									value: jsonContent.token_uri,
								} }
							/>
							<ProFormText
								name="auth_provider_x509_cert_url"
								label={ __(
									'Auth Provider x509 Cert URL',
									'sa-integrations-for-google-sheets'
								) }
								fieldProps={ {
									readOnly: true,
									value: jsonContent.auth_provider_x509_cert_url,
								} }
							/>
							<ProFormText
								name="client_x509_cert_url"
								label={ __(
									'Client x509 Cert URL',
									'sa-integrations-for-google-sheets'
								) }
								fieldProps={ {
									readOnly: true,
									value: jsonContent.client_x509_cert_url,
								} }
							/>
							<ProFormText
								name="universe_domain"
								label={ __(
									'Universe Domain',
									'sa-integrations-for-google-sheets'
								) }
								fieldProps={ {
									readOnly: true,
									value: jsonContent.universe_domain,
								} }
							/>
						</ProCard>
					</div>
				) }
			</ProForm>
		</div>
	);
};

export default IntegrationSetting;
