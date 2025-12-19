import React from 'react';
import { useState, useEffect } from 'react';
import {
	ProForm,
	ProFormSegmented,
	EditableProTable,
	ProFormSelect,
	ProFormText,
	ProCard,
	ModalForm,
} from '@ant-design/pro-components';
import { message, Button, Tooltip, Segmented, Tag } from 'antd';
import { InfoCircleOutlined } from '@ant-design/icons';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import '../../styles/form.scss';

const EditIntegration = ( { record, onReturnMessage } ) => {
	const [ formRef ] = ProForm.useForm();
	const [ isApiProcessing, setIsApiProcessing ] = useState( false );
	//  Set plugin form by plugin list
	const [ selectedPluginID, setSelectedPluginID ] = useState( '' );
	const [ pluginSelectedFormFiled, setPluginSelectedFormFiled ] = useState( [] );
	
	// set plugin form fields
	const [ selectedSourceIntityID, setSelectedSourceIntityID ] = useState( '' );
	const [ selectedOrderStatus, setSelectedOrderStatus ] = useState( '' );
	const [ Pluginformfields, setpluginformfields ] = useState( '' );
	
	// Set google sheet tab by google sheet
	const [ selectedGoogleSheet, setSelectedGoogleSheet ] = useState( '' );
	const [ googleSheetTabList, setGoogleSheetTabList ] = useState( [] );
	
	// set googgle sheet coulmn by google sheet tab
	const [ selectedGoogleSheetTab, setSelectedGoogleSheetTab ] = useState( '' );
	
	
	const [ integrationMapSourceData, setintegrationMapSourceData ] = useState( null );
	const [ editableKeys, setEditableRowKeys ] = useState( null );
	const [ mapping, setMapping ] = useState( false );

	const [ isUrlRemoved, setisUrlRemoved ] = useState( false );
	const [ editFormID, setEditFormID ] = useState( [] );
	const [ FormID, setFormID ] = useState( record );

	// getting map Fields data.
	const [ sourceFieldsData, setSourceFieldsData ] = useState( '' );
	const [ googleFieldsData, setGoogleFieldsData ] = useState( '' );
	const [ isMappingFieldsChanged, setIsMappingFieldsChanged ] = useState( false );
	const [ editableProTableValueChange, setEditableProTableValueChange ] = useState( false );


	// Add token validity state
	const [ isTokenValid, setIsTokenValid ] = useState( true );

	const customLocale = {
		emptyText: __(
			'Please check your worksheet column labels for mapping',
			'sa-integrations-for-google-sheets'
		),
	};
	const [ formInitialValues, setFormInitialValues ] = useState( {
		saifgs_title: '',
		saifgs_source_plugin: '',
		saifgs_source_intity: '',
		saifgs_order_status: '',
		saifgs_spreadsheet_worksheet: '',
		saifgs_spreadsheet_tab: '',
		source_filed_index: '',
		saifgs_source_filed_index: '',
	} );

	const removeIntegrationFormIDFromURL = () => {
		const newURL =
			window.location.pathname +
			window.location.search.replace( /integration_form_id=[^&]+&?/, '' );
		window.history.replaceState( {}, '', newURL );
		setisUrlRemoved( true );
	};

	message.config( { top: 100 } );

	// Token check and refresh function
	const checkTokenAndRefresh = async () => {
		try {
			// First check if token is valid
			const tokenStatus = await apiFetch({
				path: '/saifgs/v1/check-token-status/',
				method: 'GET',
			});
			
			console.log('Token status response:', tokenStatus);
			
			// If not connected via OAuth (Client ID/Secret), return true
			if (!tokenStatus.is_oauth_connection) {
				console.log('Not connected via OAuth. Skipping token refresh.');
				setIsTokenValid(true);
				return true;
			}

			if (tokenStatus.requires_refresh && tokenStatus.is_oauth_connection) {
				// Automatically refresh token
				const refreshResponse = await apiFetch({
					path: '/saifgs/v1/refresh-client-token/',
					method: 'POST',
				});
				
				if (!refreshResponse.success) {
					setIsTokenValid(false);
					message.error(__('Session expired. Please reconnect to Google.', 'sa-integrations-for-google-sheets'));
					return false;
				}
				setIsTokenValid(true);
			}
			return true;
		} catch (error) {
			console.error('Token check failed:', error);
			setIsTokenValid(false);
			return false;
		}
	};

	// Get Form Fileds from API
	const getSourceFields = async (
		selectedPluginID,
		selectedSourceIntityID,
		selectedOrderStatus
	) => {
		if ( selectedPluginID == '' || selectedSourceIntityID == '' ) {
			return;
		}

		// Check token before API call
		const tokenValid = await checkTokenAndRefresh();
		if (!tokenValid) return;

		return await apiFetch( {
			path: '/saifgs/v1/plugins-form-field-data/',
			method: 'POST',
			body: JSON.stringify( {
				plugin_form_id: selectedSourceIntityID,
				plugin_name: selectedPluginID,
				order_status: selectedOrderStatus,
			} ),
		} ).then( ( response ) => {
			setSourceFieldsData( response );
			return response;
		} );
	};

	const getGoogleFields = async (
		selectedGoogleSheet,
		selectedGoogleSheetTab
	) => {
		if ( selectedGoogleSheet == '' || selectedGoogleSheetTab == '' ) {
			return;
		}

		// Check token before API call
		const tokenValid = await checkTokenAndRefresh();
		if (!tokenValid) return;

		return await apiFetch( {
			path: '/saifgs/v1/google-sheet-tab/',
			method: 'POST',
			body: JSON.stringify( {
				google_sheet_data: selectedGoogleSheet,
				google_sheet_tab_selected: selectedGoogleSheetTab,
			} ),
		} ).then( ( response ) => {
			setGoogleFieldsData( response );
			return response;
		} );
	};


	// Initial token check on component mount
	useEffect( () => {
		const initializeToken = async () => {
			await checkTokenAndRefresh();
		};
		initializeToken();
	}, [] );

	// Getting plugin list
	useEffect( () => {
		if ( selectedPluginID == '' ) {
			return;
		}
		// Check token before API call
		checkTokenAndRefresh().then(tokenValid => {
			if (!tokenValid) return;
			try {
				apiFetch( {
					path: '/saifgs/v1/plugins-form-data/',
					method: 'POST',
					body: JSON.stringify( { pluginName: selectedPluginID } ),
				} ).then( ( response ) => {
					setPluginSelectedFormFiled( response );
				} );
			} catch ( error ) {
				console.error( 'Error fetching plugin data:', error );
			}
		});
	}, [ selectedPluginID ] );

	// Getting google sheet tab
	useEffect( () => {
		if ( selectedGoogleSheet == '' ) {
			return;
		}

		// Check token before API call
		checkTokenAndRefresh().then(tokenValid => {
			if (!tokenValid) return;
			try {
				apiFetch( {
					path: '/saifgs/v1/google-drive-sheets/',
					method: 'POST',
					body: JSON.stringify( {
						google_sheet_data: selectedGoogleSheet,
					} ),
				} ).then( ( response ) => {
					setGoogleSheetTabList( response );
				} );
			} catch ( error ) {
				console.error( 'Error fetching google-drive-sheets:', error );
			}
		});
	}, [ selectedGoogleSheet ] );

	// Saving data in to database
	const handleClick = async ( formData ) => {

		// Check token before saving
		const tokenValid = await checkTokenAndRefresh();
		if (!tokenValid) {
			message.error(__('Cannot save integration. Google session is expired.', 'sa-integrations-for-google-sheets'));
			return;
		}

		if ( mapping == true ) {
			if ( editableKeys.length > 0 ) {
				apiFetch( {
					path: '/saifgs/v1/integrated-form/',
					method: 'POST',
					data: formData,
				} ).then( ( response ) => {
					removeIntegrationFormIDFromURL();
					message.success(
						__(
							'Congratulations! Your settings have been successfully saved.',
							'sa-integrations-for-google-sheets'
						)
					);
					onReturnMessage( false );
				} );
			} else {
				message.error(
					__(
						'No data saved. The Google sheet is empty.',
						'sa-integrations-for-google-sheets'
					)
				);
			}
		} else {
			message.error(
				__(
					'Please generate map before saving.',
					'sa-integrations-for-google-sheets'
				)
			);
		}
	};

	// Generate mapping functionalilty Handler
	const handleDataMapping = async (
		plugin_id = selectedPluginID,
		source_id = selectedSourceIntityID,
		google_work_sheet_id = selectedGoogleSheet,
		google_sheet_tab_id = selectedGoogleSheetTab,
		order_status = selectedOrderStatus
	) => {

		// Check token first
		const tokenValid = await checkTokenAndRefresh();
		if (!tokenValid) {
			message.error(__('Cannot generate mapping. Google session is expired.', 'sa-integrations-for-google-sheets'));
			return;
		}

		setMapping( false );
		if ( plugin_id == '' ) {
			message.error(
				__(
					'Please Select a Plugin.',
					'sa-integrations-for-google-sheets'
				)
			);
			return;
		} else if ( source_id == '' ) {
			message.error(
				__(
					'Please Select a Source Intity.',
					'sa-integrations-for-google-sheets'
				)
			);
			return;
		} else if ( order_status == '' && plugin_id == 'woocommerce' ) {
			message.error(
				__(
					'Please Select a Order Status.',
					'sa-integrations-for-google-sheets'
				)
			);
			return;
		} else if ( google_work_sheet_id == '' ) {
			message.error(
				__(
					'Please Select a Spreadsheet & Worksheet.',
					'sa-integrations-for-google-sheets'
				)
			);
			return;
		} else if ( google_sheet_tab_id == '' ) {
			message.error(
				__(
					'Please Select a Spreadsheet Tab.',
					'sa-integrations-for-google-sheets'
				)
			);
			return;
		}

		const googleFieldsResponse = await getGoogleFields(
			google_work_sheet_id,
			google_sheet_tab_id
		);

		// If token check failed in getGoogleFields, return early
		if (!googleFieldsResponse) return;

		const pluginFormFieldsResponse = await getSourceFields(
			plugin_id,
			source_id,
			order_status
		);

		// If token check failed in getSourceFields, return early
		if (!pluginFormFieldsResponse) return;

		const NewIntegrationMapSourceData = await googleFieldsResponse.map(
			( googleSheetCoulmn, index ) => {
				return {
					key: googleSheetCoulmn?.key,
					google_sheet_index: googleSheetCoulmn?.value,
					source_filed_index: pluginFormFieldsResponse[ 0 ]?.value,
				};
			}
		);

		setpluginformfields( pluginFormFieldsResponse );
		setintegrationMapSourceData( NewIntegrationMapSourceData );
		setMapping( true );
		const NewIntegrationMapSourceDataKeys = googleFieldsResponse.map(
			( googleSheetCoulmn ) => googleSheetCoulmn?.key
		);
		setEditableRowKeys( NewIntegrationMapSourceDataKeys );
	};

	return (
		<div className="saifgs-form">
			<ModalForm
				title={ __(
					'Edit Sheet Integration',
					'sa-integrations-for-google-sheets'
				) }
				form={ formRef }
				open={ !! record } // Simplified conditional rendering
				autoFocusFirstInput
				modalProps={ {
					destroyOnClose: true,
					className: 'edit-google-sheet',
					onCancel: () => onReturnMessage( false ),
				} }
				submitTimeout={ 2000 }
				width={ 1500 }
				layout="horizontal"
				loading={ isApiProcessing }
				grid={ true }
				initialValues={ formInitialValues } // Initial values set here
				submitter={ {
					searchConfig: {
						resetText: __(
							'Cancel',
							'sa-integrations-for-google-sheets'
						),
						submitText: __(
							'Save',
							'sa-integrations-for-google-sheets'
						),
					},
					resetButtonProps: {},
					// Disable save button if token is invalid
					submitButtonProps: {
						disabled: !isTokenValid,
					},
				} }
				onFinish={ async ( values ) => {
					values.formid = FormID;
					if ( editableProTableValueChange !== true ) {
						values.integration_map = editFormID;
					} else {
						values.integration_map = integrationMapSourceData;
					}
					values.formid = FormID;
					values.selected_plugin_id_data = selectedPluginID;
					values.is_mapping_fields_changed = isMappingFieldsChanged;

					handleClick( values );
				} }
				request={ async ( params = {} ) => {
					return await apiFetch( {
						path: '/saifgs/v1/integrated-edit-form/',
						body: JSON.stringify( { form_id: FormID } ),
						method: 'POST',
					} ).then( ( response ) => {
						setEditFormID( response?.google_sheet_column_map );

						setSelectedPluginID( response?.plugin_id );
						setSelectedSourceIntityID( response?.source_id );
						setSelectedGoogleSheet(
							response?.google_work_sheet_id
						);
						setSelectedGoogleSheetTab(
							response?.google_sheet_tab_id
						);

						setSelectedOrderStatus( response?.order_status );
						const updatedFormValues = {
							...formInitialValues,
							saifgs_title: response?.title,
							saifgs_source_plugin: response?.plugin_id,
							saifgs_source_intity: response?.source_id,
							saifgs_order_status: response?.order_status,
							saifgs_spreadsheet_worksheet:
								response?.google_work_sheet_id,
							saifgs_spreadsheet_tab:
								response?.google_sheet_tab_id,
							saifgs_source_filed_index:
								response?.google_sheet_column_map,
						};
						handleDataMapping(
							response?.plugin_id,
							response?.source_id,
							response?.google_work_sheet_id,
							response?.google_sheet_tab_id,
							response?.order_status
						);

						formRef.current?.setFieldsValue( updatedFormValues ); // Set form values

						return updatedFormValues;
					} );
				} }
			>
				<ProCard>
					<ProFormText
						colProps={ { xl: 18, md: 24 } }
						name="saifgs_title"
						label={ __(
							'Title',
							'sa-integrations-for-google-sheets'
						) }
						tooltip={ __(
							'Enter the title of google sheet integration.',
							'sa-integrations-for-google-sheets'
						) }
						placeholder={ __(
							'Title',
							'sa-integrations-for-google-sheets'
						) }
						rules={ [
							{
								required: true,
								message: __(
									'Please add a title',
									'sa-integrations-for-google-sheets'
								),
							},
						] }
						onChange={ async ( change, event ) => {
							setIsMappingFieldsChanged( true );
						} }
					/>

					<ProFormSegmented
						label={ __(
							'Source Plugin',
							'sa-integrations-for-google-sheets'
						) }
						name="saifgs_source_plugin"
						placeholder={ __(
							'Source Plugin',
							'sa-integrations-for-google-sheets'
						) }
						tooltip={ __(
							'Select the plugin that you want to use for your integration.',
							'sa-integrations-for-google-sheets'
						) }
						request={ async () => {
							return await apiFetch( {
								path: '/saifgs/v1/integrated-plugins-list/',
								method: 'GET',
							} ).then( ( response ) => {
								var plugins_list = [];
								response.map( ( pluginData ) => {
									if (
										pluginData?.usability_status !== 'no'
									) {
										plugins_list.push( {
											label: pluginData?.title,
											value: pluginData?.key,
										} );
									}
								} );
								return plugins_list;
							} );
						} }
						onChange={ async ( change, event ) => {
							setMapping( false );
							setisUrlRemoved( true );
							setSelectedPluginID( change );
							setIsMappingFieldsChanged( true );

							// Reset the ProFormSelect field
							formRef.setFieldValue(
								[ 'saifgs_source_intity' ],
								''
							);
						} }
						style={ { marginTop: '10px' } }
					/>

					{ selectedPluginID == 'woocommerce' && (
						<ProFormSelect
							colProps={ { xl: 18, md: 24 } }
							label={ __(
								'Order Status',
								'sa-integrations-for-google-sheets'
							) }
							name="saifgs_order_status"
							tooltip={ __(
								'Select the status of the WooCommerce order ',
								'sa-integrations-for-google-sheets'
							) }
							placeholder={ __(
								'Order status',
								'sa-integrations-for-google-sheets'
							) }
							mode="multiple"
							request={ async () => [
								{
									label: __(
										'Pending payment',
										'sa-integrations-for-google-sheets'
									),
									value: 'pending',
								},
								{
									label: __(
										'Processing ',
										'sa-integrations-for-google-sheets'
									),
									value: 'processing',
								},
								{
									label: __(
										'On hold',
										'sa-integrations-for-google-sheets'
									),
									value: 'on-hold',
								},
								{
									label: __(
										'Completed',
										'sa-integrations-for-google-sheets'
									),
									value: 'completed',
								},
								{
									label: __(
										'Cancelled',
										'sa-integrations-for-google-sheets'
									),
									value: 'cancelled',
								},
								{
									label: __(
										'Refunded',
										'sa-integrations-for-google-sheets'
									),
									value: 'refunded',
								},
								{
									label: __(
										'Failed',
										'sa-integrations-for-google-sheets'
									),
									value: 'failed',
								},
								{
									label: __(
										'Checkout Draft',
										'sa-integrations-for-google-sheets'
									),
									value: 'checkout-draft',
								},
							] }
							onChange={ async ( change, event ) => {
								setMapping( false );
								setSelectedOrderStatus( change );
							} }
							width="x"
						/>
					) }

					<ProFormSelect
						colProps={ { xl: 18, md: 24 } }
						label={ __(
							'Source Entity',
							'sa-integrations-for-google-sheets'
						) }
						name="saifgs_source_intity"
						tooltip={ __(
							'Select the form that you wish to use for your integration.',
							'sa-integrations-for-google-sheets'
						) }
						placeholder={ __(
							'Source Entity',
							'sa-integrations-for-google-sheets'
						) }
						options={ pluginSelectedFormFiled }
						onChange={ async ( change, event ) => {
							setMapping( false );
							setisUrlRemoved( true );
							setSelectedSourceIntityID( change );
							setIsMappingFieldsChanged( true );
						} }
						width="x"
						rules={ [
							{
								required: true,
								message: __(
									'Please select a Source Entity',
									'sa-integrations-for-google-sheets'
								),
							},
						] }
					/>

					<ProFormSelect
						colProps={ { xl: 18, md: 24 } }
						name="saifgs_spreadsheet_worksheet"
						label={ __(
							'Spreadsheet & Worksheet',
							'sa-integrations-for-google-sheets'
						) }
						tooltip={ __(
							'Select the specified google spreadsheet that you wish to integrate.',
							'sa-integrations-for-google-sheets'
						) }
						placeholder={ __(
							'Spreadsheet & Worksheet',
							'sa-integrations-for-google-sheets'
						) }
						request={ async () => {
							return await apiFetch( {
								path: '/saifgs/v1/google-drive-sheets/',
								method: 'GET',
							} ).then( ( response ) => {
								var plugins_list = [];
								response.map( ( GoogleSheets ) => {
									plugins_list.push( {
										label: GoogleSheets?.label,
										value: GoogleSheets?.value,
									} );
								} );
								return plugins_list;
							} );
						} }
						onChange={ async ( change, event ) => {
							setMapping( false );
							setisUrlRemoved( true );
							setSelectedGoogleSheet( change );
							setIsMappingFieldsChanged( true );
						} }
						width="x"
						rules={ [
							{
								required: true,
								message: __(
									'Please select a worksheet',
									'sa-integrations-for-google-sheets'
								),
							},
						] }
					/>

					<ProFormSelect
						colProps={ { xl: 18, md: 24 } }
						name="saifgs_spreadsheet_tab"
						label={ __(
							'Spreadsheet Tabs',
							'sa-integrations-for-google-sheets'
						) }
						tooltip={ __(
							'Select the spreadsheet tab on which you want to see your data reflected',
							'sa-integrations-for-google-sheets'
						) }
						placeholder={ __(
							'Spreadsheet Tabs',
							'sa-integrations-for-google-sheets'
						) }
						options={ googleSheetTabList }
						onChange={ async ( change, event ) => {
							setMapping( false );
							setisUrlRemoved( true );
							setSelectedGoogleSheetTab( event?.data_title );
							setIsMappingFieldsChanged( true );
						} }
						width="x"
						rules={ [
							{
								required: true,
								message: __(
									'Please select spreadsheet tab',
									'sa-integrations-for-google-sheets'
								),
							},
						] }
					/>
					<Tooltip
						title={ __(
							'This feature is available in the premium version only',
							'sa-integrations-for-google-sheets'
						) }
					>
						<ProFormSegmented
							className="saifgs-blurred-table"
							colProps={ { xl: 24, md: 6 } }
							label={
								<span className="disabled-prem">
									{ __(
										'Automatically update Google Sheet record',
										'sa-integrations-for-google-sheets'
									) }
									<Tooltip
										title={ __(
											'Premium',
											'sa-integrations-for-google-sheets'
										) }
										placement="top"
									>
										<InfoCircleOutlined />
									</Tooltip>
								</span>
							}
							name="saifgs_disable_integration"
							tooltip={ __(
								'This option allows user to update google sheet data automatically upon updating order details.',
								'sa-integrations-for-google-sheets'
							) }
							fieldProps={ {
								className:
									'saifgs-blurred-table saifgs-disbale-mapping-btn',
							} }
							request={ async () => [
								{
									label: __(
										'Enable',
										'sa-integrations-for-google-sheets'
									),
									value: 'yes',
								},
								{
									label: __(
										'Disable',
										'sa-integrations-for-google-sheets'
									),
									value: 'no',
								},
							] }
							initialValue="no"
							onChange={ ( value ) => {
								setIntegrationDisable( value );
							} }
							disabled={ true }
						/>
					</Tooltip>
					<Button
						type="primary"
						onClick={ () =>
							handleDataMapping(
								selectedPluginID,
								selectedSourceIntityID,
								selectedGoogleSheet,
								selectedGoogleSheetTab,
								selectedOrderStatus
							)
						}
						disabled={ mapping == true }
					>
						{ __(
							'Generate Mapping!',
							'sa-integrations-for-google-sheets'
						) }
					</Button>
				</ProCard>
				{ mapping == true && (
					<ProCard
						title={
							<h2>
								{ __(
									'Worksheet Mapping',
									'sa-integrations-for-google-sheets'
								) }
							</h2>
						}
					>
						<>
							<EditableProTable
								maxLength={ 0 }
								locale={ customLocale }
								id="saifgs_source_filed_index"
								columns={ [
									{
										title: __(
											'Google Sheet Column',
											'sa-integrations-for-google-sheets'
										),
										dataIndex: 'google_sheet_index',
										key: 'google_sheet_index',
										valueType: 'select',
										width: 'x',
										fieldProps: () => ( {
											options: googleFieldsData.map(
												( field ) => ( {
													label: field.label,
													value: field.value,
												} )
											),
											className: 'disable_dorupdown',
										} ),
										render: ( _, row ) => {},
									},
									{
										title: __(
											'Source Date Field',
											'sa-integrations-for-google-sheets'
										),
										dataIndex: 'source_filed_index',
										key: 'source_filed_index',
										valueType: 'select',
										fieldProps: () => ( {
											showSearch: true,
											options: sourceFieldsData.map(
												( field ) => {
													// Check if the field is premium
													const isPremium =
														field.is_premium ===
														'yes';

													return {
														label: (
															<span>
																{ field.label }
																{ isPremium && (
																	<Tag color="gold">
																		{ __(
																			'(Premium)',
																			'sa-integrations-for-google-sheets'
																		) }
																	</Tag>
																) }
															</span>
														),
														value: field.value,
														disabled:
															field.is_premium ===
															'yes', // Disable the field if premium
													};
												}
											),
											onChange: ( value ) => {
												setIsMappingFieldsChanged(
													true
												);
											},
											allowClear: false,
										} ),
									},
									{
										title: __(
											'Auto Sync',
											'sa-integrations-for-google-sheets'
										),
										dataIndex: 'source_filed_index_toggle',
										key: 'source_filed_index_toggle',
										valueType: 'segmented',
										fieldProps: () => ( {
											options: [
												{
													label: __(
														'Enabled',
														'sa-integrations-for-google-sheets'
													),
													value: true,
												},
												{
													label: __(
														'Disabled',
														'sa-integrations-for-google-sheets'
													),
													value: false,
												},
											],
											defaultValue: false,
											disabled: true,
										} ),
										renderFormItem: (
											_,
											{ fieldProps }
										) => (
											<div
												style={ {
													display: 'flex',
													alignItems: 'center',
												} }
											>
												<Tooltip
													title={ __(
														'This feature is available in the premium version only',
														'sa-integrations-for-google-sheets'
													) }
												>
													<Segmented
														className="saifgs-blurred-table saifgs-disbale-mapping-btn"
														options={ [
															{
																label: __(
																	'Enabled',
																	'sa-integrations-for-google-sheets'
																),
																value: true,
															},
															{
																label: __(
																	'Disabled',
																	'sa-integrations-for-google-sheets'
																),
																value: false,
															},
														] }
														defaultValue={ false }
														disabled={ true } // Ensure it stays disabled
													/>
												</Tooltip>
											</div>
										),
									},
								] }
								scroll={ {
									x: 960,
								} }
								googleFieldsResponse
								value={
									FormID !== null &&
									FormID.trim() !== '' &&
									isUrlRemoved == false &&
									mapping === true
										? editFormID
										: integrationMapSourceData
								}
								onChange={ setintegrationMapSourceData }
								recordCreatorProps={ {
									newRecordType: 'dataSource',
									record: () => ( {
										id: Date.now(),
									} ),
								} }
								editable={ {
									type: 'multiple',
									editableKeys,
									onValuesChange: ( record, recordList ) => {
										setEditableProTableValueChange( true );
										setintegrationMapSourceData(
											recordList
										);
									},
									onChange: setEditableRowKeys,
								} }
							/>
						</>
					</ProCard>
				) }
			</ModalForm>
		</div>
	);
};
export default EditIntegration;
