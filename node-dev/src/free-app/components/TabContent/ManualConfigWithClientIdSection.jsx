import React, { useEffect, useState } from 'react';
import { ProCard, ProForm, ProFormText } from '@ant-design/pro-components';
import { Alert, Button, Tag, Steps, message } from 'antd';
import { CheckCircleFilled, CloseCircleFilled, SafetyCertificateOutlined, LinkOutlined } from '@ant-design/icons';
import { GoogleDriveIcon, GoogleSheetsIcon } from '../../utilities/custom-icons';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const { Step } = Steps;

const ManualConfigWithClientIdSection = () => {
  const [isConnecting, setIsConnecting] = useState(false);
  const [connectionStatus, setConnectionStatus] = useState('not_connected');
  const [connectedEmail, setConnectedEmail] = useState('');
  const [storedCredentials, setStoredCredentials] = useState(null);

  // 1. Get a reference to the form instance
  const [form] = ProForm.useForm();

  // Load existing credentials on component mount
  useEffect(() => {
    const loadCredentials = async () => {
      try {
        const response = await apiFetch({
          path: '/saifgs/v1/get-client-credentials/',
          method: 'GET',
        });
        
        if (response.success && response.data) {
          setStoredCredentials(response.data);
          
          if (response.data.status === 'connected' && response.data.email) {
            setConnectionStatus('connected');
            setConnectedEmail(response.data.email);

            // 2. CRITICAL: Set form values when credentials are loaded and connected
            // This will populate the fields with the stored credentials
            form.setFieldsValue({
              client_id: response.data.client_id,
              // Optionally set client_secret if you want to show a masked version
              // client_secret: '••••••••' // Masked for security
            });
          }
        }
      } catch (error) {
        console.error('Failed to load credentials:', error);
      }
    };
    
    loadCredentials();
  }, [form]);

  const handleConnectToGoogle = async (values) => {
    setIsConnecting(true);
    try {
      const response = await apiFetch({
        path: '/saifgs/v1/save-client-credentials/',
        method: 'POST',
        data: {
          client_id: values.client_id,
          client_secret: values.client_secret,
          _wpnonce: window.saifgs_customizations_localized_objects?.nonce || ''
        }
      });
      
      if (response.success && response.data.auth_url) {
        // Store credentials temporarily for callback
        localStorage.setItem('saifgs_client_credentials', JSON.stringify({
          client_id: values.client_id,
          client_secret: values.client_secret
        }));

        // Update form with the credentials that were just submitted
        // This ensures they're visible immediately after redirect
        form.setFieldsValue({
          client_id: values.client_id,
          client_secret: values.client_secret
        });
        
        // Redirect to Google OAuth
        window.location.href = response.data.auth_url;
      } else {
        message.error(response.message || __('Failed to save credentials', 'sa-integrations-for-google-sheets'));
      }
    } catch (error) {
      message.error(__('Failed to connect. Please try again.', 'sa-integrations-for-google-sheets'));
    } finally {
      setIsConnecting(false);
    }
  };

  const handleDisconnect = async () => {
    try {
      const response = await apiFetch({
        path: '/saifgs/v1/revoke-client-credentials/',
        method: 'POST',
        data: {
          _wpnonce: window.saifgs_customizations_localized_objects?.nonce || ''
        }
      });
      
      if (response.success) {
        setConnectionStatus('not_connected');
        setConnectedEmail('');
        setStoredCredentials(null);
        
        // Also reset the form to initial state
        form.resetFields();

        message.success(__('Successfully disconnected from Google', 'sa-integrations-for-google-sheets'));
        
        // reload the current page
        window.location.reload();
      }
    } catch (error) {
      message.error(__('Failed to disconnect. Please try again.', 'sa-integrations-for-google-sheets'));
    }
  };

  return (
    <ProCard
      title={__('Manual Configuration (Client ID & Secret)', 'sa-integrations-for-google-sheets')}
      headerBordered
      className="manual-clientid-section"
      extra={
        connectionStatus === 'connected' ? (
          <Tag color="green" icon={<CheckCircleFilled />}>
            {__('Connected', 'sa-integrations-for-google-sheets')}
          </Tag>
        ) : (
          <Tag color="orange" icon={<CloseCircleFilled />}>
            {__('Not Connected', 'sa-integrations-for-google-sheets')}
          </Tag>
        )
      }
    >
      <div className="clientid-content">
        <Alert
          message={__('Manual Connection via Client Credentials', 'sa-integrations-for-google-sheets')}
          description={
            <div>
              <p>
                {__('Enter your Google Cloud Platform Client ID and Client Secret to connect manually. You need to:', 'sa-integrations-for-google-sheets')}
              </p>
              <ol style={{ marginLeft: '20px', marginTop: '8px' }}>
                <li>{__('Create a project in Google Cloud Console', 'sa-integrations-for-google-sheets')}</li>
                <li>{__('Enable Google Drive and Google Sheets APIs', 'sa-integrations-for-google-sheets')}</li>
                <li>{__('Create OAuth 2.0 credentials (Web Application type)', 'sa-integrations-for-google-sheets')}</li>
                <li>{__('Add authorized redirect URI', 'sa-integrations-for-google-sheets')}</li>
                <li>{__('Copy Client ID and Client Secret', 'sa-integrations-for-google-sheets')}</li>
              </ol>
            </div>
          }
          type="info"
          showIcon
          style={{ marginBottom: 24 }}
        />

        <ProCard 
          className="instructions-card"
          title={__('Setup Instructions', 'sa-integrations-for-google-sheets')}
          style={{ marginBottom: 24 }}
          bordered
        >
          <Steps direction="vertical" current={connectionStatus === 'connected' ? 3 : 0}>
            <Step 
              title={__('Get Client Credentials', 'sa-integrations-for-google-sheets')}
              description={
                <div>
                  <p style={{ marginBottom: '8px' }}>{__('Go to Google Cloud Console → APIs & Services → Credentials', 'sa-integrations-for-google-sheets')}</p>
                  <Button 
                    type="link" 
                    href="https://console.cloud.google.com/apis/credentials" 
                    target="_blank"
                    icon={<LinkOutlined />}
                    size="small"
                  >
                    {__('Open Google Cloud Console', 'sa-integrations-for-google-sheets')}
                  </Button>
                </div>
              }
            />
            <Step 
              title={__('Enter Credentials Below', 'sa-integrations-for-google-sheets')}
              description={__('Paste your Client ID and Client Secret in the form below', 'sa-integrations-for-google-sheets')}
            />
            <Step 
              title={__('Connect to Google', 'sa-integrations-for-google-sheets')}
              description={__('Click "Connect to Google" button to authorize access', 'sa-integrations-for-google-sheets')}
            />
            <Step 
              title={__('Grant Permissions', 'sa-integrations-for-google-sheets')}
              description={
                <div>
                  <div style={{ marginBottom: '8px' }}>{__('Grant access to:', 'sa-integrations-for-google-sheets')}</div>
                  <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
                    <Tag icon={<GoogleDriveIcon />} color="blue">
                      Google Drive
                    </Tag>
                    <Tag icon={<GoogleSheetsIcon />} color="green">
                      Google Sheets
                    </Tag>
                  </div>
                </div>
              }
            />
          </Steps>
        </ProCard>

        <ProForm
          form={form} // 4. Connect the form instance to ProForm
          layout="vertical"
          onFinish={handleConnectToGoogle}
          submitter={{
            searchConfig: {
              submitText: __('Connect to Google', 'sa-integrations-for-google-sheets'),
            },
            submitButtonProps: {
              loading: isConnecting,
              type: 'primary',
              size: 'large',
              icon: <SafetyCertificateOutlined />,
              style: { width: '100%', height: '44px' }
            },
            render: (props, doms) => {
              if (connectionStatus === 'connected') {
                return (
                  <div style={{ display: 'flex', gap: '12px', marginTop: '24px' }}>
                    <Button 
                      type="primary" 
                      danger
                      onClick={handleDisconnect}
                      style={{ flex: 1 }}
                    >
                      {__('Disconnect', 'sa-integrations-for-google-sheets')}
                    </Button>
                    <Button 
                      type="default"
                      onClick={() => props.form?.submit()}
                      style={{ flex: 1 }}
                    >
                      {__('Reconnect', 'sa-integrations-for-google-sheets')}
                    </Button>
                  </div>
                );
              }
              return doms;
            }
          }}
          // Initial values are now handled by form.setFieldsValue in useEffect
          initialValues={{
            client_id: '',
            client_secret: ''
          }}
          // initialValues={{
          //   client_id: storedCredentials?.client_id || '',
          //   client_secret: ''
          // }}        
        >
          <ProFormText.Password
            label={__('Client ID', 'sa-integrations-for-google-sheets')}
            name="client_id"
            placeholder="1234567890-abcdefghijklmnopqrstuvwxyz.apps.googleusercontent.com"
            rules={[
              { required: true, message: __('Please enter your Client ID', 'sa-integrations-for-google-sheets') },
              { min: 10, message: __('Client ID is too short', 'sa-integrations-for-google-sheets') }
            ]}
            fieldProps={{
              visibilityToggle: false,
              style: { fontFamily: 'monospace' },
              // Disable the field when connected (security best practice)
              disabled: connectionStatus === 'connected'
            }}
          />

          <ProFormText.Password
            label={__('Client Secret', 'sa-integrations-for-google-sheets')}
            name="client_secret"
            placeholder="GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxxxx"
            rules={[
              { required: !storedCredentials, message: __('Please enter your Client Secret', 'sa-integrations-for-google-sheets') }
            ]}
            fieldProps={{
              visibilityToggle: false,
              style: { fontFamily: 'monospace' },
              // Disable the field when connected (security best practice)
              disabled: connectionStatus === 'connected'
            }}
            // extra={storedCredentials ? __('Client secret is already saved. Enter new one only if you want to change it.', 'sa-integrations-for-google-sheets') : ''}
            extra={
              connectionStatus === 'connected' 
                ? __('Credentials are saved and connected. You can only update them after disconnecting.', 'sa-integrations-for-google-sheets')
                : __('Enter your Client Secret to connect.', 'sa-integrations-for-google-sheets')
            }
          />

          {connectionStatus === 'connected' && connectedEmail && (
            <Alert
              message={__('Connected Successfully', 'sa-integrations-for-google-sheets')}
              description={
                <div>
                  <strong>{__('Connected Email Account:', 'sa-integrations-for-google-sheets')}</strong>{' '}
                  {connectedEmail}
                </div>
              }
              type="success"
              showIcon
              style={{ marginBottom: 24, marginTop: 16 }}
            />
          )}
        </ProForm>

        <ProCard 
          className="privacy-note-card"
          style={{ marginTop: 24 }}
          bordered
        >
          <div className="privacy-content">
            <div style={{ marginBottom: 12, display: 'flex', alignItems: 'center' }}>
              <SafetyCertificateOutlined style={{ color: '#52c41a', marginRight: 8, fontSize: '18px' }} />
              <strong style={{ fontSize: '16px' }}>{__('Security Note', 'sa-integrations-for-google-sheets')}</strong>
            </div>
            <p style={{ fontSize: '14px', lineHeight: '1.6', color: '#666' }}>
              {__('Your Client ID and Client Secret are stored securely on your server and never shared with third parties. The plugin only requests access to Google Drive and Sheets for integration purposes.', 'sa-integrations-for-google-sheets')}
            </p>
          </div>
        </ProCard>
      </div>
    </ProCard>
  );
};

export default ManualConfigWithClientIdSection;