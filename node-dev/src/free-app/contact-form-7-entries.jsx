import { useState, useEffect, useRef } from '@wordpress/element';
import {
	ProFormSelect,
	ProTable,
	ModalForm,
	ProForm,
	ProFormText,
	ProFormDatePicker,
	ProFormDigit,
	ProFormTextArea,
	ProFormCheckbox,
	ProFormRadio,
} from '@ant-design/pro-components';
import { createRoot } from 'react-dom/client';
import { ConfigProvider, Tag, Button } from 'antd';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import en_US from 'antd/lib/locale/en_US';
import './styles/contact-form-7-entries.scss';

const ContactForm7Entries = () => {
	const feddsTableRef = useRef();
	const [ forms, setForms ] = useState( [] );
	const [ formId, setFormID ] = useState();
	const [ selectedForm, setSelectedForm ] = useState( null );
	const [ columns, setColumns ] = useState( [] );
	const [ tableData, setTableData ] = useState( [] );
	const [ modalVisible, setModalVisible ] = useState( false );
	const [ editFormData, setEditFormData ] = useState( null );

	useEffect( () => {
		// Fetch Contact Forms.
		const fetchForms = async () => {
			try {
				const response = await apiFetch( {
					path: '/saifgs/v1/contact-forms',
				} );
				setForms( response );
			} catch ( error ) {
				console.error( 'Failed to fetch Contact Forms:', error );
			}
		};

		fetchForms();
	}, [] );

	// Function to capitalize first letter of each word.
	const capitalizeFirstLetter = ( str ) => {
		return str.replace(
			/\w\S*/g,
			( txt ) =>
				txt.charAt( 0 ).toUpperCase() + txt.substr( 1 ).toLowerCase()
		);
	};

	const handleFormChange = async ( formId, page = 1 ) => {
		setSelectedForm( formId );
		// Fetch entries for the selected form.
		setFormID( formId );
	};

	const handleEdit = ( record ) => {
		setEditFormData( record );
		setModalVisible( true );
	};

	const handleModalCancel = () => {
		setModalVisible( false );
		feddsTableRef.current?.reload();
	};

	const handleModalOk = async ( values ) => {
		try {
			await apiFetch( {
				path: `/saifgs/v1/contact-forms/update-entry`,
				method: 'POST',
				data: {
					form_id: values?.selectedForm,
					entry_id: parseInt( values?.editFormData?.id ),
					data: { meta: values?.processedValues },
				},
			} );
			const updatedData = tableData.map( ( item ) =>
				item.id === editFormData.id
					? { ...item, meta: processedValues }
					: item
			);
			setTableData( updatedData );
			setModalVisible( false );
			feddsTableRef.current?.reload();
		} catch ( error ) {
			console.error( 'Failed to update form data:', error );
		}
	};

	const renderFormField = ( field ) => {
		const commonProps = {
			label: capitalizeFirstLetter( field.name.replace( /[_-]/g, ' ' ) ),
			name: `${ field.name }`,
			initialValue: editFormData.meta[ field.name ],
			rules: [
				{
					required: field.type.includes( '*' ),
					message: `${ capitalizeFirstLetter(
						field.name.replace( /[_-]/g, ' ' )
					) } is required`,
				},
			],
		};

		switch ( field.basetype ) {
			case 'text':
				return <ProFormText { ...commonProps } key={ field.name } />;
			case 'email':
				return (
					<ProFormText
						{ ...commonProps }
						key={ field.name }
						rules={ [
							{
								type: 'email',
								message: __(
									'Please enter a valid email address',
									'sa-integrations-for-google-sheets'
								),
							},
							...commonProps.rules,
						] }
					/>
				);
			case 'url':
				return (
					<ProFormText
						{ ...commonProps }
						key={ field.name }
						rules={ [
							{
								type: 'url',
								message: __(
									'Please enter a valid URL',
									'sa-integrations-for-google-sheets'
								),
							},
							...commonProps.rules,
						] }
					/>
				);
			case 'tel':
				return <ProFormText { ...commonProps } key={ field.name } />;
			case 'number':
				return <ProFormDigit { ...commonProps } key={ field.name } />;
			case 'date':
				return (
					<ProFormDatePicker { ...commonProps } key={ field.name } />
				);
			case 'textarea':
				return (
					<ProFormTextArea { ...commonProps } key={ field.name } />
				);
			case 'select':
				return (
					<ProFormSelect
						{ ...commonProps }
						key={ field.name }
						options={ field.raw_values.map( ( option ) => ( {
							label: option,
							value: option,
						} ) ) }
					/>
				);
			case 'checkbox':
				return (
					<ProFormCheckbox.Group
						{ ...commonProps }
						key={ field.name }
						options={ field.raw_values.map( ( option ) => ( {
							label: option,
							value: option,
						} ) ) }
					/>
				);
			case 'radio':
				return (
					<ProFormRadio.Group
						{ ...commonProps }
						key={ field.name }
						options={ field.raw_values.map( ( option ) => ( {
							label: option,
							value: option,
						} ) ) }
					/>
				);
			case 'file': {
				const fileUrl = editFormData.meta[ field.name ];
				const fileName = fileUrl.split( '/' ).pop();
				return (
					<div key={ field.name }>
						<label>{ commonProps.label }</label>
						<a
							href={ fileUrl }
							download={ fileName }
							target="_blank"
							style={ { display: 'block', marginTop: 8 } }
						>
							{ fileName }
						</a>
					</div>
				);
			}
			default:
				return null;
		}
	};

	return (
		<ConfigProvider locale={ en_US }>
			<div>
				<ProFormSelect
					name="contactForms"
					label={ __(
						'Select Contact Form',
						'sa-integrations-for-google-sheets'
					) }
					options={ forms.map( ( form ) => ( {
						label: form.title,
						value: form.id,
					} ) ) }
					fieldProps={ {
						onChange: handleFormChange,
					} }
				/>
				<ProTable
					actionRef={ feddsTableRef }
					search={ false }
					columns={ columns }
					params={ {
						form_id: formId,
						page: 'saifgs-cf7-entries',
					} }
					request={ async ( params = {}, sort, filter ) => {
						if ( ! params?.form_id ) {
							return {
								data: [],
								total: 0,
								success: false,
							};
						}
						return apiFetch( {
							path: addQueryArgs(
								`/saifgs/v1/contact-forms/${ params?.form_id }/entries`,
								{
									limit: 10,
									current_page: params?.current,
									form_id: params?.form_id,
									...( params?.pageSize != '' && {
										limit: params?.pageSize,
									} ),
								}
							),
							method: 'GET',
						} ).then( ( response ) => {
							const entries = response.data;
							let columns = Object.keys( entries[ 0 ].meta ).map(
								( key ) => {
									return {
										title: capitalizeFirstLetter(
											key.replace( /[_-]/g, ' ' )
										),
										dataIndex: [ 'meta', key ],
									};
								}
							);

							// Add Edit column at the end.
							columns.push( {
								title: __(
									'Edit',
									'sa-integrations-for-google-sheets'
								),
								valueType: 'option',
								render: ( _, record ) => [
									<a
										key="edit"
										onClick={ () => handleEdit( record ) }
									>
										{ __(
											'Edit',
											'sa-integrations-for-google-sheets'
										) }
									</a>,
								],
							} );
							if ( columns.length > 5 ) {
								columns = columns
									.slice( 0, 5 )
									.concat( columns[ columns.length - 1 ] );
							}
							setColumns( columns );
							return {
								data: entries,
								total: response?.total,
								success: true,
							};
						} );
					} }
					rowKey="id"
					pagination={ {
						showQuickJumper: false,
						pageSize: 10,
						defaultPageSize: 10,
						pageSizeOptions: [ 10, 20 ],
						showTotal: ( total, range ) => (
							<div>{ `showing ${ range[ 0 ] }-${ range[ 1 ] } of ${ total } total items` }</div>
						),
					} }
					options={ {
						reload: false, // Hides the refresh button.
						density: false, // Hides the density button.
						setting: false, // Hides the setting button.
					} }
				/>
				<ModalForm
					title={ __(
						'Edit Form Data',
						'sa-integrations-for-google-sheets'
					) }
					open={ modalVisible ? true : false }
					modalProps={ {
						destroyOnClose: true,
						onCancel: handleModalCancel,
						className: 'edit-form-data',
					} }
					submitTimeout={ 2000 }
					initialValues={ editFormData }
					submitter={ {
						searchConfig: {
							resetText: __(
								'Cancel',
								'sa-integrations-for-google-sheets'
							),
							submitText: __(
								'Update',
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
					{ editFormData && (
						<ProForm
							submitTimeout={ 2000 }
							onFinish={ async ( values ) => {
								const processedValues = {};
								for ( const key in values ) {
									if ( Array.isArray( values[ key ] ) ) {
										// Convert array to comma-separated string.
										processedValues[ key ] =
											values[ key ].join( ', ' );
									} else {
										processedValues[ key ] = values[ key ];
									}
								}
								values.processedValues = processedValues;
								values.editFormData = editFormData;
								values.selectedForm = selectedForm;
								handleModalOk( values );
							} }
							submitter={ {
								searchConfig: {
									resetText: __(
										'reset',
										'sa-integrations-for-google-sheets'
									),
									submitText: __(
										`Submit`,
										'sa-integrations-for-google-sheets'
									),
								},
								resetButtonProps: {
									style: {
										display: 'none',
									},
								},
								submitButtonProps: {
									disabled: true, // Disable the button
									style: {
										filter: 'blur(1px)', // Add blur effect
										cursor: 'not-allowed', // Indicate the button is disabled
									},
								},
								render: ( _, dom ) => (
									<div
										style={ {
											display: 'flex',
											alignItems: 'center',
											gap: '8px',
										} }
									>
										<Button
											type="primary"
											disabled
											style={ {
												filter: 'blur(1px)', // Add blur effect to the button
												cursor: 'not-allowed', // Indicate the button is disabled
											} }
										>
											{ __(
												'Submit',
												'sa-integrations-for-google-sheets'
											) }
										</Button>
										<Tag
											color="gold"
											style={ {
												fontSize: '12px',
												fontWeight: 'bold',
											} }
										>
											Premium
										</Tag>
									</div>
								),
							} }
						>
							{ editFormData.fieldsmeta.map( ( field ) => (
								<React.Fragment key={ field.name }>
									{ renderFormField( field ) }
								</React.Fragment>
							) ) }
						</ProForm>
					) }
				</ModalForm>
			</div>
		</ConfigProvider>
	);
};

createRoot( document.getElementById( 'saifgs-cf7-app' ) ).render(
	<ContactForm7Entries />
);
