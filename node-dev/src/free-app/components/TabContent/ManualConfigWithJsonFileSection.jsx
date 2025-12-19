import React from 'react';
import { 
  ProCard, 
  ProForm, 
  ProFormUploadDragger, 
  ProFormText, 
  ProFormTextArea 
} from '@ant-design/pro-components';
import { 
  Alert, 
  Button, 
  Divider, 
  Flex, 
  Upload, 
  message 
} from 'antd';
import { 
  DeleteOutlined, 
  DownOutlined, 
  ExportOutlined, 
  CheckCircleFilled, 
  CloseCircleFilled 
} from '@ant-design/icons';
import { GoogleDriveIcon, GoogleSheetsIcon } from '../../utilities/custom-icons';
import { __ } from '@wordpress/i18n';

const ManualConfigWithJsonFileSection = ({ 
  uploadedFile, 
  jsonContent, 
  fileList, 
  isVisible, 
  onRemoveFile, 
  onToggle, 
  onFileUploadChange, 
  formInitialValues,
  isDisabled 
}) => {
  return (
    <ProForm
      initialValues={formInitialValues}
      layout="vertical"
      submitter={{
        resetButtonProps: { style: { display: 'none' } },
        submitButtonProps: { style: { display: 'none' } },
      }}
      readonly={!!uploadedFile}
    >
      <ProCard
        title={__('Google Account Credentials', 'sa-integrations-for-google-sheets')}
        headerBordered
        extra={
          <Flex vertical gap="small">
            <Flex gap="small" wrap>
              <Button size="small">
                <GoogleDriveIcon />
                {uploadedFile ? (
                  <CheckCircleFilled style={{ color: '#52C41A', marginLeft: '4px' }} />
                ) : (
                  <CloseCircleFilled style={{ color: '#FF4D4F', marginLeft: '4px' }} />
                )}
              </Button>
              <Button size="small">
                <GoogleSheetsIcon />
                {uploadedFile ? (
                  <CheckCircleFilled style={{ color: '#52C41A', marginLeft: '4px' }} />
                ) : (
                  <CloseCircleFilled style={{ color: '#FF4D4F', marginLeft: '4px' }} />
                )}
              </Button>
            </Flex>
          </Flex>
        }
        style={{ marginBottom: 24 }}
      >
        {uploadedFile ? (
          <div>
            <p style={{ marginBottom: '16px' }}>
              {__('You can generate your Google account credentials file according to the official documentation from', 'sa-integrations-for-google-sheets')}
              <a target="_blank" href="https://developers.google.com/workspace/guides/create-credentials" rel="noreferrer" style={{ marginLeft: '4px' }}>
                {__('here', 'sa-integrations-for-google-sheets')} <ExportOutlined />
              </a>
              .
            </p>
            <Alert
              message={
                <div>
                  <strong>
                    {__('Uploaded File:', 'sa-integrations-for-google-sheets')}
                  </strong>{' '}
                  {uploadedFile.name}
                </div>
              }
              type="success"
              showIcon
              action={
                <Flex gap="small">
                  <Button
                    icon={<DeleteOutlined />}
                    onClick={onRemoveFile}
                    size="small"
                    disabled={isDisabled}
                  >
                    {__('Remove File', 'sa-integrations-for-google-sheets')}
                  </Button>
                  <Button
                    icon={<DownOutlined />}
                    onClick={onToggle}
                    size="small"
                    className="sa-toggleBtn"
                  >
                    {__('View Detail', 'sa-integrations-for-google-sheets')}
                  </Button>
                </Flex>
              }
              style={{ marginTop: '15px' }}
            />
          </div>
        ) : (
          <ProFormUploadDragger
            label={__('Google Services API Credentials', 'sa-integrations-for-google-sheets')}
            name="json_file"
            fieldProps={{
              name: 'json_file',
              headers: {
                'X-WP-Nonce': window.saifgs_customizations_localized_objects?.nonce || ''
              },
              data: {
                _wpnonce: window.saifgs_customizations_localized_objects?.nonce || '',
              }
            }}
            title={__('Google Services API Credentials', 'sa-integrations-for-google-sheets')}
            description={__('Upload your Google Services API credentials JSON file.', 'sa-integrations-for-google-sheets')}
            fileList={fileList}
            maxCount={1}
            accept=".json"
            action="/wp-json/saifgs/v1/save-settings/"
            beforeUpload={(file) => {
              const isJson = file.type === 'application/json';
              if (!isJson) {
                message.error(__('You can only upload JSON files!', 'sa-integrations-for-google-sheets'));
              }
              return isJson ? false : Upload.LIST_IGNORE;
            }}
            onChange={onFileUploadChange}
            disabled={isDisabled}
          />
        )}
      </ProCard>

      {jsonContent && (
        <div className={`slide-container ${isVisible ? 'expanded' : 'collapsed'}`}>
          <Divider>
            {__('JSON File Contents', 'sa-integrations-for-google-sheets')}
          </Divider>
          <ProCard
            className="read-only-text"
            title={__('JSON Extracted Fields', 'sa-integrations-for-google-sheets')}
            headerBordered
            bordered
          >
            <ProFormText
              name="type"
              label={__('Type', 'sa-integrations-for-google-sheets')}
              fieldProps={{
                readOnly: true,
                value: jsonContent.type,
              }}
            />
            <ProFormText
              name="project_id"
              label={__('Project Id', 'sa-integrations-for-google-sheets')}
              fieldProps={{
                readOnly: true,
                value: jsonContent.project_id,
              }}
            />
            <ProFormText
              name="private_key_id"
              label={__('Private Key ID', 'sa-integrations-for-google-sheets')}
              fieldProps={{
                readOnly: true,
                value: jsonContent.private_key_id,
              }}
            />
            <ProFormTextArea
              name="private_key"
              label={__('Private Key', 'sa-integrations-for-google-sheets')}
              fieldProps={{
                readOnly: true,
                value: jsonContent.private_key,
                rows: 8,
              }}
            />
            <ProFormText
              name="client_email"
              label={__('Client Email', 'sa-integrations-for-google-sheets')}
              fieldProps={{
                readOnly: true,
                value: jsonContent.client_email,
              }}
            />
            <ProFormText
              name="client_id"
              label={__('Client ID', 'sa-integrations-for-google-sheets')}
              fieldProps={{
                readOnly: true,
                value: jsonContent.client_id,
              }}
            />
            <ProFormText
              name="auth_uri"
              label={__('Auth URI', 'sa-integrations-for-google-sheets')}
              fieldProps={{
                readOnly: true,
                value: jsonContent.auth_uri,
              }}
            />
            <ProFormText
              name="token_uri"
              label={__('Token URI', 'sa-integrations-for-google-sheets')}
              fieldProps={{
                readOnly: true,
                value: jsonContent.token_uri,
              }}
            />
            <ProFormText
              name="auth_provider_x509_cert_url"
              label={__('Auth Provider x509 Cert URL', 'sa-integrations-for-google-sheets')}
              fieldProps={{
                readOnly: true,
                value: jsonContent.auth_provider_x509_cert_url,
              }}
            />
            <ProFormText
              name="client_x509_cert_url"
              label={__('Client x509 Cert URL', 'sa-integrations-for-google-sheets')}
              fieldProps={{
                readOnly: true,
                value: jsonContent.client_x509_cert_url,
              }}
            />
            <ProFormText
              name="universe_domain"
              label={__('Universe Domain', 'sa-integrations-for-google-sheets')}
              fieldProps={{
                readOnly: true,
                value: jsonContent.universe_domain,
              }}
            />
          </ProCard>
        </div>
      )}
    </ProForm>
  );
};

export default ManualConfigWithJsonFileSection;