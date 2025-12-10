import React from 'react';
import { useState, useEffect } from 'react';
import { ProFormSegmented, EditableProTable, ProFormSelect, ProFormText, ProCard, ModalForm, ProForm, } from '@ant-design/pro-components';
import { message, Button, Tooltip, Tag, Segmented } from 'antd';
import { InfoCircleOutlined } from '@ant-design/icons';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import '../../styles/form.scss';

const AddIntegration = ( { closeModel } ) => {
	const [ formRef ] = ProForm.useForm();
	//  Set plugin form by plugin list
	const [ selectedPluginID, setSelectedPluginID ] = useState( 'contact_form_7' );
	const [ pluginSelectedFormFiled, setPluginSelectedFormFiled ] = useState( [] );

	// set plugin form fields
	const [ selectedSourceIntityID, setSelectedSourceIntityID ] = useState( '' );
	const [ selectedOrderStatus, setSelectedOrderStatus ] = useState( '' );
	const [ Pluginformfields, setpluginformfields ] = useState( '' ); // update

	// Set google sheet tab by google sheet
	const [ selectedGoogleSheet, setSelectedGoogleSheet ] = useState( '' );
	const [ googleSheetTabList, setGoogleSheetTabList ] = useState( [] );

	// set googgle sheet coulmn by google sheet tab
	const [ selectedGoogleSheetTab, setSelectedGoogleSheetTab ] = useState( '' );

	const [ integrationMapSourceData, setintegrationMapSourceData ] = useState( null );
	const [ editableKeys, setEditableRowKeys ] = useState( null );
	const [ mapping, setMapping ] = useState( false );

	// getting map Fields data.
	const [ sourceFieldsData, setSourceFieldsData ] = useState( '' );
	const [ googleFieldsData, setGoogleFieldsData ] = useState( '' );

	const customLocale = { emptyText: __( 'Please check your worksheet column labels for mapping', 'sa-integrations-for-google-sheets' ), };
	message.config( { top: 100 } );
	// Get Form Fileds from API
	const getSourceFields = async ( selectedPluginID, selectedSourceIntityID, selectedOrderStatus ) => {
		if ( selectedPluginID == '' || selectedSourceIntityID == '' ) {
			return;
		}
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

	const getGoogleFields = async ( selectedGoogleSheet, selectedGoogleSheetTab ) => {
		if ( selectedGoogleSheet == '' || selectedGoogleSheetTab == '' ) {
			return;
		}

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

	// Getting plugin list
	useEffect( () => {
		if ( selectedPluginID == '' ) {
			return;
		}
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
	}, [ selectedPluginID ] );

	// Getting google sheet tab
	useEffect( () => {
		if ( selectedGoogleSheet == '' ) {
			return;
		}
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
	}, [ selectedGoogleSheet ] );

	// Saving data in to database
	const handleClick = ( formData ) => {
		if ( mapping == true ) {
			if ( editableKeys.length > 0 ) {
				apiFetch( {
					path: '/saifgs/v1/integrated-form/',
					method: 'POST',
					data: formData,
				} ).then( ( response ) => {
					message.success( __( 'Congratulations! Your settings have been successfully saved.', 'sa-integrations-for-google-sheets' ) );
					closeModel( false, true ); //Pass true to indicate data was saved
				} );
			} else {
				message.error( __( 'No data saved. The Google sheet is empty.', 'sa-integrations-for-google-sheets' ) );
			}
		} else {
			message.error( __( 'Please generate map before saving.', 'sa-integrations-for-google-sheets' ) );
		}
	};

	// Generate mapping functionalilty Handler
	const handleDataMapping = async () => {
		setMapping( false );
		if ( selectedPluginID == '' ) {
			message.error( __( 'Please Select a Plugin.', 'sa-integrations-for-google-sheets' ) );
			return;
		} else if ( selectedSourceIntityID == '' ) {
			message.error( __( 'Please Select a Source Intity.', 'sa-integrations-for-google-sheets' ) );
			return;
		} else if ( selectedOrderStatus == '' && selectedPluginID == 'woocommerce' ) {
			message.error( __( 'Please Select a Order Status.', 'sa-integrations-for-google-sheets' ) );
			return;
		} else if ( selectedGoogleSheet == '' ) {
			message.error( __( 'Please Select a Spreadsheet & Worksheet.', 'sa-integrations-for-google-sheets' ) );
			return;
		} else if ( selectedGoogleSheetTab == '' ) {
			message.error( __( 'Please Select a Spreadsheet Tab.', 'sa-integrations-for-google-sheets' ) );
			return;
		}

		const googleFieldsResponse = await getGoogleFields( selectedGoogleSheet, selectedGoogleSheetTab );

		const pluginFormFieldsResponse = await getSourceFields( selectedPluginID, selectedSourceIntityID, selectedOrderStatus );

		const NewIntegrationMapSourceData = await googleFieldsResponse.map(
			( googleSheetCoulmn, index ) => {
				return {
					key: googleSheetCoulmn?.key,
					google_sheet_index: googleSheetCoulmn?.value,
					source_filed_index: pluginFormFieldsResponse[ 0 ]?.value,
					source_filed_index_toggle: false,
				};
			}
		);

		setpluginformfields( pluginFormFieldsResponse );

		setintegrationMapSourceData( NewIntegrationMapSourceData );
		setMapping( true );
		const NewIntegrationMapSourceDataKeys = googleFieldsResponse.map( ( googleSheetCoulmn ) => googleSheetCoulmn?.key );
		setEditableRowKeys( NewIntegrationMapSourceDataKeys );
	};

	return (
		<div className="saifgs-form">
			<ModalForm
				title={ __( 'Add Sheet Integration', 'sa-integrations-for-google-sheets' ) }
				form={ formRef }
				open={ closeModel ? true : false }
				autoFocusFirstInput
				modalProps={ {
					destroyOnClose: true,
					onCancel: () => {
						closeModel( false, false ); // Pass false to indicate modal was closed without saving
					},
				} }
				id="saifgs-new-intermigration"
				submitTimeout={ 2000 }
				width={ 1500 }
				grid={ true }
				layout="horizontal"
				onFinish={ async ( values ) => {
					values.integration_map = integrationMapSourceData;
					values.selected_plugin_id_data = selectedPluginID;
					values.saifgs_source_plugin = selectedPluginID;
					values.add_integration_form = 'add_integration_Form';
					handleClick( values );
				} }
				submitter={ {
					searchConfig: {
						resetText: __( 'Cancel', 'sa-integrations-for-google-sheets' ),
						submitText: __( 'Save', 'sa-integrations-for-google-sheets' ),
					},
					resetButtonProps: {},
				} }
			>
				<ProCard>
					<ProFormText
						colProps={ { xl: 18, md: 24 } }
						name="saifgs_title"
						label={ __( 'Title', 'sa-integrations-for-google-sheets' ) }
						tooltip={ __( 'Enter the title of google sheet integration.', 'sa-integrations-for-google-sheets' ) }
						placeholder={ __( 'Title', 'sa-integrations-for-google-sheets' ) }
						rules={ [
							{
								required: true,
								message: __( 'Please add a title', 'sa-integrations-for-google-sheets' ),
							},
						] }
					/>

					<ProFormSegmented
						label={ __( 'Source Plugin', 'sa-integrations-for-google-sheets' ) }
						name="saifgs_source_plugin"
						placeholder={ __( 'Source Plugin', 'sa-integrations-for-google-sheets' ) }
						tooltip={ __( 'Select the plugin that you want to use for your integration.', 'sa-integrations-for-google-sheets' ) }
						request={ async () => {
							return await apiFetch( {
								path: '/saifgs/v1/integrated-plugins-list/',
								method: 'GET',
							} ).then( ( response ) => {
								var plugins_list = [];
								response.map( ( pluginData ) => {
									if ( pluginData?.usability_status !== 'no' ) {
										plugins_list.push( { label: pluginData?.title, value: pluginData?.key, } );
									}
								} );
								setSelectedPluginID( plugins_list[ 0 ]?.value );

								return plugins_list;
							} );
						} }
						onChange={ async ( change, event ) => {
							setPluginSelectedFormFiled( [] );
							setMapping( false );
							setSelectedPluginID( change );

							// Reset the ProFormSelect field
							formRef.resetFields( [ 'saifgs_source_intity' ] );
						} }
						style={ { marginTop: '10px' } }
					/>

					{ selectedPluginID == 'woocommerce' && (
						<ProFormSelect
							colProps={ { xl: 18, md: 24 } }
							label={ __( 'Order Status', 'sa-integrations-for-google-sheets' ) }
							name="saifgs_order_status"
							tooltip={ __( 'Select the status of the WooCommerce order', 'sa-integrations-for-google-sheets' ) }
							placeholder={ __( 'Order status', 'sa-integrations-for-google-sheets' ) }
							mode="multiple"
							request={ async () => [
								{
									label: __( 'Pending payment', 'sa-integrations-for-google-sheets' ),
									value: 'pending',
								},
								{
									label: __( 'Processing ', 'sa-integrations-for-google-sheets' ),
									value: 'processing',
								},
								{
									label: __( 'On hold', 'sa-integrations-for-google-sheets' ),
									value: 'on-hold',
								},
								{
									label: __( 'Completed', 'sa-integrations-for-google-sheets' ),
									value: 'completed',
								},
								{
									label: __( 'Cancelled', 'sa-integrations-for-google-sheets' ),
									value: 'cancelled',
								},
								{
									label: __( 'Refunded', 'sa-integrations-for-google-sheets' ),
									value: 'refunded',
								},
								{
									label: __( 'Failed', 'sa-integrations-for-google-sheets' ),
									value: 'failed',
								},
								{
									label: __( 'Checkout Draft', 'sa-integrations-for-google-sheets' ),
									value: 'checkout-draft',
								},
							] }
							onChange={ async ( change, event ) => {
								setSelectedOrderStatus( change );
							} }
							width="x"
						/>
					) }

					<ProFormSelect
						colProps={ { xl: 18, md: 24 } }
						label={ __( 'Source Entity', 'sa-integrations-for-google-sheets' ) }
						name="saifgs_source_intity"
						tooltip={ __( 'Select the form that you wish to use for your integration.', 'sa-integrations-for-google-sheets' ) }
						placeholder={ __( 'Source Entity', 'sa-integrations-for-google-sheets' ) }
						options={ pluginSelectedFormFiled }
						onChange={ async ( change, event ) => {
							setMapping( false );
							setSelectedSourceIntityID( change );
						} }
						width="x"
						rules={ [
							{
								required: true,
								message: __( 'Please select a Source Entity', 'sa-integrations-for-google-sheets' ),
							},
						] }
					/>

					<ProFormSelect
						colProps={ { xl: 18, md: 24 } }
						name="saifgs_spreadsheet_worksheet"
						label={ __( 'Spreadsheet & Worksheet', 'sa-integrations-for-google-sheets' ) }
						tooltip={ __( 'Select the specified google spreadsheet that you wish to integrate.', 'sa-integrations-for-google-sheets' ) }
						placeholder={ __( 'Spreadsheet & Worksheet', 'sa-integrations-for-google-sheets' ) }
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
							setSelectedGoogleSheet( change );
						} }
						width="x"
						rules={ [
							{
								required: true,
								message: __( 'Please select a worksheet', 'sa-integrations-for-google-sheets' ),
							},
						] }
					/>
					<ProFormSelect
						colProps={ { xl: 18, md: 24 } }
						name="saifgs_spreadsheet_tab"
						label={ __( 'Spreadsheet Tabs', 'sa-integrations-for-google-sheets' ) }
						tooltip={ __( 'Select the spreadsheet tab on which you want to see your data reflected', 'sa-integrations-for-google-sheets' ) }
						placeholder={ __( 'Spreadsheet Tabs', 'sa-integrations-for-google-sheets' ) }
						options={ googleSheetTabList }
						onChange={ async ( change, event ) => {
							setMapping( false );
							setSelectedGoogleSheetTab( event?.data_title );
						} }
						width="x"
						rules={ [
							{
								required: true,
								message: __( 'Please select spreadsheet tab', 'sa-integrations-for-google-sheets' ),
							},
						] }
					/>
					<ProFormSegmented
						colProps={ { xl: 24, md: 6 } }
						label={
							<span className="disabled-prem">
								{ __( 'Automatically update Google Sheet record', 'sa-integrations-for-google-sheets' ) }
								<Tooltip
									title={ __( 'Premium', 'sa-integrations-for-google-sheets' ) }
									placement="top"
								>
									<InfoCircleOutlined />
								</Tooltip>
							</span>
						}
						name="saifgs_disable_integration"
						tooltip={ __( 'This feature is available in the premium version only', 'sa-integrations-for-google-sheets' ) }
						fieldProps={ {
							className: 'saifgs-blurred-table saifgs-disbale-mapping-btn',
						} }
						request={ async () => [
							{
								label: __( 'Enable', 'sa-integrations-for-google-sheets' ),
								value: 'yes',
							},
							{
								label: __( 'Disable', 'sa-integrations-for-google-sheets' ),
								value: 'no',
							},
						] }
						initialValue="no"
						onChange={ ( value ) => {
							setIntegrationDisable( value );
						} }
						disabled={ true }
					/>
					<Button
						type="primary"
						onClick={ () => handleDataMapping( 0 ) }
					>
						{ __( 'Generate Mapping!', 'sa-integrations-for-google-sheets' ) }
					</Button>
				</ProCard>
				{ mapping == true && (
					<ProCard
						title={ <h2> { __( 'Worksheet Mapping', 'sa-integrations-for-google-sheets' ) } </h2> }
					>
						<>
							<EditableProTable
								locale={ customLocale }
								maxLength={ 0 }
								columns={ [
									{
										title: __( 'Google Sheet Column', 'sa-integrations-for-google-sheets' ),
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
										title: __( 'Source Date Field', 'sa-integrations-for-google-sheets' ),
										dataIndex: 'source_filed_index',
										key: 'source_filed_index',
										valueType: 'select',
										fieldProps: () => ( {
											showSearch: true,
											options: sourceFieldsData.map(
												( field ) => {
													// Check if the field is premium
													const isPremium = field.is_premium === 'yes';
													return {
														label: (
															<span>
																{ field.label }
																{ isPremium && ( <Tag color="gold"> { __( '(Premium)', 'sa-integrations-for-google-sheets' ) } </Tag> ) }
															</span>
														),
														value: field.value,
														disabled: field.is_premium === 'yes', // Disable the field if premium
													};
												}
											),
										} ),
									},
									{
										title: __( 'Auto Sync', 'sa-integrations-for-google-sheets' ),
										dataIndex: 'source_filed_index_toggle',
										key: 'source_filed_index_toggle',
										valueType: 'segmented',
										fieldProps: () => ( {
											options: [
												{
													label: __( 'Enabled', 'sa-integrations-for-google-sheets' ),
													value: true,
												},
												{
													label: __( 'Disabled', 'sa-integrations-for-google-sheets' ),
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
													width: '',
												} }
											>
												<Tooltip title={ __( 'This feature is available in the premium version only', 'sa-integrations-for-google-sheets' ) } >
													<Segmented
														className="saifgs-blurred-table saifgs-disbale-mapping-btn"
														options={ [
															{
																label: __( 'Enabled', 'sa-integrations-for-google-sheets' ),
																value: true,
															},
															{
																label: __( 'Disabled', 'sa-integrations-for-google-sheets' ),
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
								scroll={ { x: 960 } }
								value={ integrationMapSourceData }
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
										setintegrationMapSourceData( recordList );
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
export default AddIntegration;
