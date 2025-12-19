import React from 'react';
import { ProCard } from '@ant-design/pro-components';
import { Alert, Button, Flex, Tag, Steps } from 'antd';
import { CheckCircleFilled, CloseCircleFilled, SafetyCertificateOutlined } from '@ant-design/icons';
import { GoogleDriveIcon, GoogleSheetsIcon } from '../../utilities/custom-icons';
import { __ } from '@wordpress/i18n';

const { Step } = Steps;

const AutoConnectSection = ({ autoConnectStatus, connectedEmail, isAuthenticating, onConnect, onDisconnect, isDisabled }) => {
  return (
    <ProCard
      title={__('Auto Google API Configuration', 'sa-integrations-for-google-sheets')}
      headerBordered
      className="auto-connect-section"
      extra={
        autoConnectStatus === 'connected' ? (
          <Tag color="green" icon={<CheckCircleFilled />}>
            {__('Connected', 'sa-integrations-for-google-sheets')}
          </Tag>
        ) : (
          <Tag color="red" icon={<CloseCircleFilled />}>
            {__('Not Connected', 'sa-integrations-for-google-sheets')}
          </Tag>
        )
      }
      split="vertical"
    >
      <ProCard >
        <div className="auto-connect-content">
          <h3 className="section-subtitle" style={{ marginBottom: '16px' }}>
            {__('Use Built-in Google API Configuration', 'sa-integrations-for-google-sheets')}
          </h3>
          
          <Alert
            message={__('Automatic Integration', 'sa-integrations-for-google-sheets')}
            description={ 
              __('Automatic integration allows you to connect with Google Sheets using built-in Google API configuration. By authorizing your Google account, the plugin will handle API setup and authentication automatically, enabling seamless data sync.', 'sa-integrations-for-google-sheets')
            }
            type="info"
            showIcon
            style={{ marginBottom: 24 }}
          />

          {autoConnectStatus === 'not_connected' && (
            <ProCard 
              className="auth-steps-card"
              title={__('Authentication Steps', 'sa-integrations-for-google-sheets')}
              style={{ marginBottom: 24 }}
              bordered
            >
              <Steps direction="vertical" current={0}>
                <Step 
                  title={__('Sign In with Google', 'sa-integrations-for-google-sheets')}
                  description={__('Click on the "Sign In With Google" button to start the authentication process.', 'sa-integrations-for-google-sheets')}
                />
                <Step 
                  title={__('Grant Permissions', 'sa-integrations-for-google-sheets')}
                  description={ 
                    <div>
                      <div>{__('Grant permissions for the following services:', 'sa-integrations-for-google-sheets')}</div>
                      <div style={{ marginTop: 8 }}>
                        <Tag icon={<GoogleDriveIcon />} color="blue">
                          Google Drive
                        </Tag>
                        <Tag icon={<GoogleSheetsIcon />} color="green">
                          Google Sheets
                        </Tag>
                      </div>
                      <div style={{ marginTop: 8, fontSize: '12px', color: '#ff4d4f' }}>
                        * {__('Ensure that you enable the checkbox for each of these services.', 'sa-integrations-for-google-sheets')}
                      </div>
                    </div>
                  }
                />
                <Step 
                  title={__('Automatic Configuration', 'sa-integrations-for-google-sheets')}
                  description={__('The plugin will automatically handle API setup and authentication for seamless integration.', 'sa-integrations-for-google-sheets')}
                />
              </Steps>
            </ProCard>
          )}

          {autoConnectStatus === 'connected' && connectedEmail && (
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
              style={{ marginBottom: 24 }}
            />
          )}

          <div className="auth-actions" style={{ marginTop: 24 }}>
            {autoConnectStatus === 'connected' ? (
              <Flex gap="middle">
                <Button 
                  type="primary" 
                  danger
                  icon={<CloseCircleFilled />}
                  onClick={onDisconnect}
                  loading={isAuthenticating}
                  size="large"
                  disabled={isDisabled}
                >
                  {__('Deactivate Connection', 'sa-integrations-for-google-sheets')}
                </Button>
              </Flex>
            ) : (
              <Button 
                onClick={onConnect}
                loading={isAuthenticating}
                size="large"
                className="google-auth-btn"
                target="_blank"
                disabled={isDisabled}
                style={{ 
                  padding: 0, 
                  height: 'auto', 
                  background: 'transparent',
                  border: 'none',
                  boxShadow: 'none'
                }}
              >
                <img 
                  src={window.saifgs_customizations_localized_objects?.btn_google_signin} 
                  alt={__('Sign In With Google', 'sa-integrations-for-google-sheets')}
                  style={{ 
                    display: 'block',
                    height: '46px',
                    width: 'auto'
                  }}
                />
              </Button>
            )}
          </div>
        </div>
      </ProCard>
      
      <ProCard
        className="privacy-card"
        title={__('Privacy & Security', 'sa-integrations-for-google-sheets')}
        headerBordered
        bordered
        style={{ backgroundColor: '#f0f8ff' }}
      >
        <div className="privacy-content">
          <div style={{ marginBottom: 12, display: 'flex', alignItems: 'center' }}>
            <SafetyCertificateOutlined style={{ color: '#52c41a', marginRight: 8, fontSize: '18px' }} />
            <strong style={{ fontSize: '16px' }}>{__('Your Data is Secure', 'sa-integrations-for-google-sheets')}</strong>
          </div>
          <p style={{ fontSize: '14px', lineHeight: '1.6', color: '#666' }}>
            {__('We do not store any of the data from your Google account on our servers. Everything is processed & stored on your server. We take your privacy extremely seriously and ensure it is never misused.', 'sa-integrations-for-google-sheets')}
          </p>
        </div>
      </ProCard>
    </ProCard>
  );
};

export default AutoConnectSection;