import { useState, useRef } from 'react';
import {
	ConfigProvider,
	message,
	Button,
	Modal,
	Badge,
	Tag,
	Tooltip,
} from 'antd';
import {
	ExclamationCircleFilled,
	PlusOutlined,
	SelectOutlined,
	DeleteTwoTone,
	EditTwoTone,
} from '@ant-design/icons';
import { ProTable } from '@ant-design/pro-components';
import enUSIntl from 'antd/lib/locale/en_US';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import EditIntegration from './EditIntegrations';
import AddIntegration from './AddIntegration';
import '../../styles/googlesheet.scss';

const intlMap = { enUSIntl };

const { confirm } = Modal;
const SheetMapping = () => {
	const feddsTableRef = useRef();
	const [ intl, setIntl ] = useState( 'enUSIntl' );
	const [ deleteRow, setDeleteRow ] = useState( '' );
	const [ openEditModal, setOpenEditModal ] = useState( false );
	const [ editRecordId, setEditRecordId ] = useState( '' );
	const [ sheetIntegration, setSheetIntegration ] = useState( false );
	const [ activeKey, setActiveKey ] = useState( 'all' );
	const [ pluginFilter, setPluginFilter ] = useState( '' );
	const [ totalRows, setTotalRows ] = useState( 0 ); // Track total number of rows
	const [ pluginTableStatus, setPluginTableStatus ] = useState( 'disabled' ); // Control alert visibility
	const [ enabledPluginID, setEnabledPluginID ] = useState( 'all' );

	const renderBadge = ( count, active = false ) => {
		return (
			<Badge
				count={ count }
				style={ {
					marginBlockStart: -2,
					marginInlineStart: 4,
					color: active ? '#1890FF' : '#999',
					backgroundColor: active ? '#E6F7FF' : '#eee',
				} }
			/>
		);
	};
	// Handle edit model exit model functionality
	const handleeditCloseModel = ( value ) => {
		setOpenEditModal( value );
		feddsTableRef.current?.reload();
	};
	// Handle edit model exit model functionality
	const handleCloseModel = ( value ) => {
		setSheetIntegration( value );
		feddsTableRef.current?.reload();
	};

	const showPropsConfirm = ( id ) => {
		confirm( {
			title: __(
				'Are you sure you want to delete?',
				'sa-integrations-for-google-sheets'
			),
			icon: <ExclamationCircleFilled />,
			okText: __( 'Yes', 'sa-integrations-for-google-sheets' ),
			okType: __( 'danger', 'sa-integrations-for-google-sheets' ),
			cancelText: __( 'No', 'sa-integrations-for-google-sheets' ),
			onOk() {
				apiFetch( {
					path: '/saifgs/v1/integration-list/',
					method: 'POST',
					body: JSON.stringify( { id: id } ),
				} ).then( ( response ) => {
					message.success( response );
					setEnabledPluginID( 'all' );
					setPluginTableStatus( 'enabled' );
					feddsTableRef.current?.reload();
				} );
			},
			onCancel() {},
		} );
	};

	const columns = [
		{
			title: __( 'ID', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'id',
			key: 'table-column-customer-id',
			hideInSearch: true,
			hideInTable: true,
		},
		{
			title: __( 'Title', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'title',
			key: 'title',
			hideInSearch: true,
		},
		{
			title: __( 'Plugin Name', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'plugin_name',
			key: 'plugin_name',
			hideInSearch: true,
		},
		{
			title: __( 'Source Name', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'source_name',
			key: 'source_name',
			hideInSearch: true,
		},
		{
			title: __( 'Work Sheet', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'google_work_sheet_id',
			key: 'google_work_sheet_id',
			hideInSearch: true,
		},
		{
			title: __( 'Work Sheet Tab', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'google_sheet_tab_id',
			key: 'google_sheet_tab_id',
			hideInSearch: true,
		},
		{
			title: __( 'Created Date', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'created_at',
			key: 'created_at',
			hideInSearch: true,
		},
		{
			title: __( 'Actions', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'google_sheets_url',
			key: 'google_sheets_url',
			sorter: false,
			hideInForm: true,
			hideInSearch: true,
			render: ( text, record ) => (
				<div>
					<Button
						className="protable_detail_open_sheet"
						type="primary"
						icon={ <SelectOutlined /> }
						onClick={ () =>
							window.open( record.google_sheets_url, '_blank' )
						}
					></Button>
					<Button
						className="protable_detail_delete"
						type="primary"
						icon={ <DeleteTwoTone twoToneColor="red" /> }
						onClick={ () => {
							showPropsConfirm( record?.id );
							setDeleteRow( record );
						} }
					></Button>
					<Button
						className="gswpi_protable_edit"
						type="primary"
						icon={ <EditTwoTone twoToneColor="green" /> }
						onClick={ () => {
							setOpenEditModal( true );
							setEditRecordId( record?.id );
						} }
					></Button>
				</div>
			),
		},
	];

	const dummy_dataSource = [
		{
			key: '1',
			sn: 1,
			title: __( 'Integration 1', 'sa-integrations-for-google-sheets' ),
			plugin_name: __(
				'WooCommerce',
				'sa-integrations-for-google-sheets'
			),
			source_name: __(
				'Google Sheets',
				'sa-integrations-for-google-sheets'
			),
			work_sheet: __( 'Sheet 1', 'sa-integrations-for-google-sheets' ),
			work_sheet_tab: __( 'Tab 1', 'sa-integrations-for-google-sheets' ),
			created_date: __(
				'2024-10-11',
				'sa-integrations-for-google-sheets'
			),
			actions: __( 'Edit | Delete', 'sa-integrations-for-google-sheets' ),
		},
		{
			key: '2',
			sn: 2,
			title: __( 'Integration 2', 'sa-integrations-for-google-sheets' ),
			plugin_name: __( 'WPForms', 'sa-integrations-for-google-sheets' ),
			source_name: __(
				'Google Sheets',
				'sa-integrations-for-google-sheets'
			),
			work_sheet: __( 'Sheet 2', 'sa-integrations-for-google-sheets' ),
			work_sheet_tab: __( 'Tab 2', 'sa-integrations-for-google-sheets' ),
			created_date: __(
				'2024-10-12',
				'sa-integrations-for-google-sheets'
			),
			actions: __( 'Edit | Delete', 'sa-integrations-for-google-sheets' ),
		},
		{
			key: '3',
			sn: 3,
			title: __( 'Integration 3', 'sa-integrations-for-google-sheets' ),
			plugin_name: __(
				'Gravity Forms',
				'sa-integrations-for-google-sheets'
			),
			source_name: __(
				'Google Sheets',
				'sa-integrations-for-google-sheets'
			),
			work_sheet: __( 'Sheet 3', 'sa-integrations-for-google-sheets' ),
			work_sheet_tab: __( 'Tab 3', 'sa-integrations-for-google-sheets' ),
			created_date: __(
				'2024-10-13',
				'sa-integrations-for-google-sheets'
			),
			actions: __( 'Edit | Delete', 'sa-integrations-for-google-sheets' ),
		},
	];

	const dummy_columns = [
		{
			title: __( 'SN', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'sn',
			key: 'sn',
		},
		{
			title: __( 'Title', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'title',
			key: 'title',
		},
		{
			title: __( 'Plugin Name', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'plugin_name',
			key: 'plugin_name',
		},
		{
			title: __( 'Source Name', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'source_name',
			key: 'source_name',
		},
		{
			title: __( 'Work Sheet', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'work_sheet',
			key: 'work_sheet',
		},
		{
			title: __( 'Work Sheet Tab', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'work_sheet_tab',
			key: 'work_sheet_tab',
		},
		{
			title: __( 'Created Date', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'created_date',
			key: 'created_date',
		},
		{
			title: __( 'Actions', 'sa-integrations-for-google-sheets' ),
			dataIndex: 'google_sheets_url',
			key: 'google_sheets_url',
			sorter: false,
			hideInForm: true,
			hideInSearch: true,
			render: ( text, record ) => (
				<div>
					<Button
						className="protable_detail_open_sheet"
						type="primary"
						icon={ <SelectOutlined /> }
						onClick={ () =>
							window.open( record.google_sheets_url, '_blank' )
						}
					></Button>
					<Button
						className="protable_detail_delete"
						type="primary"
						icon={ <DeleteTwoTone twoToneColor="red" /> }
						onClick={ () => {
							showPropsConfirm( record?.id );
							setDeleteRow( record );
						} }
					></Button>
					<Button
						className="gswpi_protable_edit"
						type="primary"
						icon={ <EditTwoTone twoToneColor="green" /> }
						onClick={ () => {
							setOpenEditModal( true );
							setEditRecordId( record?.id );
						} }
					></Button>
				</div>
			),
		},
	];

	return (
		<div className="sa-scpp-app-tab-content-container googlesheet">
			<ConfigProvider locale={ intlMap[ intl ] }>
				<ProTable
					actionRef={ feddsTableRef }
					onLoad={ ( dataSource ) => {
						if ( dataSource[ 0 ]?.plugin_id ) {
							setEnabledPluginID( dataSource[ 0 ]?.plugin_id );
							setPluginTableStatus( 'disabled' );
						} else if ( enabledPluginID === 'all' ) {
							setPluginTableStatus( 'enabled' );
						}
					} }
					tableClassName={
						totalRows > 0
							? 'saifgs-table-is-disabled'
							: 'saifgs-table-is-enabled'
					}
					locale={ {
						emptyText: (
							<span>
								{ __(
									'No records found',
									'sa-integrations-for-google-sheets'
								) }
							</span>
						),
					} }
					rowKey="id"
					options={ false }
					search={ false }
					toolbar={ {
						menu: {
							type: 'tab',
							activeKey: activeKey,
							items: [
								{
									key: 'all',
									label: (
										<span>
											{ __(
												'All',
												'sa-integrations-for-google-sheets'
											) }
											{ activeKey === 'all' }
										</span>
									),
								},
								{
									key: 'contact_form_7',
									label: (
										<span>
											{ __(
												'Contact Form 7',
												'sa-integrations-for-google-sheets'
											) }
											{ activeKey === 'contact_form_7' }
										</span>
									),
								},
								{
									key: 'woocommerce',
									label: (
										<span>
											{ __(
												'WooCommerce',
												'sa-integrations-for-google-sheets'
											) }
											{ activeKey === 'woocommerce' }
										</span>
									),
								},
								{
									key: 'wpforms',
									label: (
										<span>
											{ __(
												'WP forms ',
												'sa-integrations-for-google-sheets'
											) }
											{ activeKey === 'wpforms' }
										</span>
									),
								},
								{
									key: 'gravityforms',
									label: (
										<span>
											{ __(
												'Gravity forms',
												'sa-integrations-for-google-sheets'
											) }
											{ activeKey === 'gravityforms' }
										</span>
									),
								},
							],
							onChange: ( key ) => {
								feddsTableRef.current?.reload();
								setPluginFilter( key );
								setActiveKey( key );
							},
						},
						actions:
							pluginTableStatus === 'disabled'
								? [
										<span>
											<Tooltip title="You can only add 1 integration at a time">
												<Button disabled>
													<PlusOutlined />
													{ __(
														'Add Integration',
														'sa-integrations-for-google-sheets'
													) }
													<Tag color="gold">
														{ __(
															'Premium',
															'sa-integrations-for-google-sheets'
														) }
													</Tag>
												</Button>
											</Tooltip>
										</span>,
								  ]
								: [
										<Button
											key="add"
											type="primary"
											disabled={
												pluginTableStatus === 'disabled'
													? true
													: false
											} // Disable during loading
											onClick={ () => {
												setSheetIntegration( true );
											} }
										>
											<PlusOutlined />
											{ __(
												'Add Integration',
												'sa-integrations-for-google-sheets'
											) }
										</Button>,
								  ],
					} }
					pagination={ {
						showQuickJumper: false,
						pageSize: 10,
						defaultPageSize: 10,
						pageSizeOptions: [ 10 ],
						showTotal: ( total, range ) => (
							<div>{ `showing ${ range[ 0 ] }-${ range[ 1 ] } of ${ total } total items` }</div>
						),
					} }
					request={ async ( params = {}, sort, filter, paginate ) => {
						return apiFetch( {
							path: addQueryArgs(
								'/saifgs/v1/integration-list/',
								{
									page: '?page=saifgs-dashboard&',
									limit: 10,
									current_page: params?.current,
									plugin_filter: pluginFilter,
									...( params?.pageSize != '' && {
										limit: params?.pageSize,
									} ),
									...( params?.search != '' && {
										filter_title: params?.search,
									} ),
									...( params?.startTime != '' && {
										filter_start_date: params?.startTime,
									} ),
									...( params?.endTime != '' && {
										filter_end_date: params?.endTime,
									} ),
								}
							),
							method: 'GET',
						} ).then( ( response ) => {
							setTotalRows( response?.new_response?.total || 0 ); // Update total rows
							const test_response = {
								data: response?.new_response,
								page: response?.new_response?.page,
								total: response?.new_response?.total,
							};
							return response?.new_response;
						} );
					} }
					columns={ columns }
				/>
				{ totalRows > 0 ? (
					<p></p>
				) : pluginTableStatus === 'disabled' ? (
					<div>
						<ProTable
							options={ false }
							search={ false }
							dataSource={ dummy_dataSource }
							columns={ dummy_columns }
							pagination={ false }
							className="saifgs-blurred-table" // Apply the CSS class here
						/>
						<div className="saifgs-sheets-integrations-table-vissible-div">
							<p>
								{ __(
									`To enable multiple sheets integrations, 
							please activate the premium version of the plugin. 
							This feature allows you to manage and configure multiple integrations seamlessly.`,
									'sa-integrations-for-google-sheets'
								) }
							</p>
							<a href="#">
								<Button className="saifgs-upg-pre-btn">
									{ __(
										'Upgrade to Premium ',
										'sa-integrations-for-google-sheets'
									) }
								</Button>
							</a>
						</div>
					</div>
				) : (
					<span>
						<center>
							{ __(
								'No records found',
								'sa-integrations-for-google-sheets'
							) }
						</center>
					</span>
				) }

				{ openEditModal && (
					<EditIntegration
						record={ editRecordId }
						onReturnMessage={ handleeditCloseModel }
					/>
				) }
				{ sheetIntegration && (
					<AddIntegration closeModel={ handleCloseModel } />
				) }
			</ConfigProvider>
		</div>
	);
};

export default SheetMapping;
