import React, { useState, useEffect } from 'react';
import { ProCard, ProForm } from '@ant-design/pro-components';
import { Tabs, Divider, message } from 'antd';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import AutoConnectSection from './AutoConnectSection';
import ManualConfigWithJsonFileSection from './ManualConfigWithJsonFileSection';
import ManualConfigWithClientIdSection from './ManualConfigWithClientIdSection';
import '../../styles/spreadsheetsetting.scss';

const AllConnectionSettings = ({ navVisibility }) => {
  // Get initial tab from URL or localStorage, default to 'auto'
  const getInitialTab = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('connection_tab');
    const tabFromStorage = localStorage.getItem('saifgs_connection_tab');
    
    return tabFromUrl || tabFromStorage || 'auto';
  };
  
  const [activeTab, setActiveTab] = useState(getInitialTab());
  
  // States for Auto Connect
  const [autoConnectStatus, setAutoConnectStatus] = useState('not_connected');
  const [connectedEmail, setConnectedEmail] = useState('');
  const [isAuthenticating, setIsAuthenticating] = useState(false);
  
  // States for Manual JSON
  const [fileList, setFileList] = useState([]);
  const [uploadedFile, setUploadedFile] = useState(null);
  const [jsonContent, setJsonContent] = useState(null);
  const [isVisible, setIsVisible] = useState(false);
  
  // States for Client Credentials
  const [clientCredentialsStatus, setClientCredentialsStatus] = useState('not_connected');
  const [clientCredentialsEmail, setClientCredentialsEmail] = useState('');

  // Handle tab change
  const handleTabChange = (key) => {
    setActiveTab(key);
    
    // Save to localStorage
    localStorage.setItem('saifgs_connection_tab', key);
    
    // Update URL without reloading page
    const url = new URL(window.location);
    url.searchParams.set('connection_tab', key);
    window.history.pushState({}, '', url);
  };

  // Check URL parameters for auth messages and connection tab
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Set active tab from URL if present
    const tabFromUrl = urlParams.get('connection_tab');
    if (tabFromUrl && ['auto', 'json', 'clientid'].includes(tabFromUrl)) {
      setActiveTab(tabFromUrl);
    }
    
    // Handle auth messages
    const authMessage = urlParams.get('auth_message');
    const authError = urlParams.get('saifgs_auth');
    const clientAuthError = urlParams.get('client_auth_error');
    
    if (authMessage === 'success') {
      message.success(__('Successfully connected to Google!', 'sa-integrations-for-google-sheets'));
      
      // Switch to the connection tab that was used for auth
      const authTab = localStorage.getItem('saifgs_last_auth_tab') || 'auto';
      setActiveTab(authTab);
      localStorage.setItem('saifgs_connection_tab', authTab);
      
      // Clean URL but keep the connection_tab parameter
      const cleanUrl = new URL(window.location);
      cleanUrl.searchParams.delete('auth_message');
      cleanUrl.searchParams.delete('saifgs_auth');
      cleanUrl.searchParams.delete('client_auth_error');
      cleanUrl.searchParams.delete('message');
      cleanUrl.searchParams.set('connection_tab', authTab);
      window.history.replaceState({}, '', cleanUrl);
      
      // Refresh the status
      fetchInitialStatus();
    }
    
    if (authError) {
      message.error(__('Authentication failed: ', 'sa-integrations-for-google-sheets') + decodeURIComponent(authError));
      
      // Keep the connection tab that was used
      const authTab = localStorage.getItem('saifgs_last_auth_tab') || 'auto';
      setActiveTab(authTab);
      
      // Clean URL
      const cleanUrl = new URL(window.location);
      cleanUrl.searchParams.delete('saifgs_auth');
      cleanUrl.searchParams.delete('message');
      cleanUrl.searchParams.set('connection_tab', authTab);
      window.history.replaceState({}, '', cleanUrl);
    }
    
    if (clientAuthError) {
      message.error(__('Client credentials authentication failed: ', 'sa-integrations-for-google-sheets') + decodeURIComponent(clientAuthError));
      
      // Switch to clientid tab to show the error
      setActiveTab('clientid');
      localStorage.setItem('saifgs_connection_tab', 'clientid');
      
      // Clean URL
      const cleanUrl = new URL(window.location);
      cleanUrl.searchParams.delete('client_auth_error');
      cleanUrl.searchParams.set('connection_tab', 'clientid');
      window.history.replaceState({}, '', cleanUrl);
    }
  }, []);

  // Check if any connection is active
  useEffect(() => {
    const isAnyConnected = autoConnectStatus === 'connected' || 
                          uploadedFile !== null || 
                          clientCredentialsStatus === 'connected';
    
    if (navVisibility) {
      navVisibility(!isAnyConnected);
    }
  }, [autoConnectStatus, uploadedFile, clientCredentialsStatus, navVisibility]);

  // Load initial status
  const fetchInitialStatus = async () => {
    try {
      // Fetch auto connect status
      const autoConnectResponse = await apiFetch({
        path: '/saifgs/v1/get-auto-connect-status/',
        method: 'GET',
      });
      
      if (autoConnectResponse.success) {
        setAutoConnectStatus(autoConnectResponse.data.status);
        setConnectedEmail(autoConnectResponse.data.email || '');
      }

      // Fetch manual JSON status
      const manualResponse = await apiFetch({
        path: '/saifgs/v1/get-integration-setting/',
        method: 'GET',
      });
      
      if (manualResponse.uploadedFile) {
        setUploadedFile(manualResponse.uploadedFile);
        setJsonContent(manualResponse.json_data);
      }

      // Fetch client credentials status
      const clientCredentialsResponse = await apiFetch({
        path: '/saifgs/v1/get-client-credentials/',
        method: 'GET',
      });
      
      if (clientCredentialsResponse.success && clientCredentialsResponse.data) {
        setClientCredentialsStatus(clientCredentialsResponse.data.status || 'not_connected');
        setClientCredentialsEmail(clientCredentialsResponse.data.email || '');
      }
    } catch (error) {
      console.error('Failed to fetch initial status:', error);
    }
  };

  useEffect(() => {
    fetchInitialStatus();
  }, []);

  // Auto Connect Handlers
  const handleAutoConnect = async () => {
    // Save which tab we're connecting from
    localStorage.setItem('saifgs_last_auth_tab', 'auto');
    
    setIsAuthenticating(true);
    try {
      const response = await apiFetch({
        path: '/saifgs/v1/initiate-auto-connect/',
        method: 'POST',
        data: {
          _wpnonce: window.saifgs_customizations_localized_objects?.nonce || ''
        }
      });
      
      if (response.success && response.data.auth_url) {
        window.location.href = response.data.auth_url;
      } else {
        message.error(__('Failed to initiate authentication', 'sa-integrations-for-google-sheets'));
      }
    } catch (error) {
      message.error(__('Authentication failed. Please try again.', 'sa-integrations-for-google-sheets'));
    } finally {
      setIsAuthenticating(false);
    }
  };

  const handleDeactivateAutoConnect = async () => {
    setIsAuthenticating(true);
    try {
      const response = await apiFetch({
        path: '/saifgs/v1/deactivate-auto-connect/',
        method: 'POST',
        data: {
          _wpnonce: window.saifgs_customizations_localized_objects?.nonce || ''
        }
      });
      
      if (response.success) {
        setAutoConnectStatus('not_connected');
        setConnectedEmail('');
        message.success(__('Successfully disconnected from Google', 'sa-integrations-for-google-sheets'));
      } else {
        message.error(response.message || __('Failed to disconnect', 'sa-integrations-for-google-sheets'));
      }
    } catch (error) {
      message.error(__('Failed to disconnect. Please try again.', 'sa-integrations-for-google-sheets'));
    } finally {
      setIsAuthenticating(false);
    }
  };

  // Manual JSON Handlers
  const handleRemoveFile = async () => {
    try {
      const response = await apiFetch({
        path: '/saifgs/v1/remove-file/',
        method: 'POST',
        data: {
          _wpnonce: window.saifgs_customizations_localized_objects?.nonce || ''
        }
      });
      setUploadedFile(null);
      setJsonContent(null);
      setFileList([]);
      message.success(__('File removed successfully!', 'sa-integrations-for-google-sheets'));
    } catch (error) {
      message.error(__('Failed to remove file. Please try again.', 'sa-integrations-for-google-sheets'));
    }
  };

  const handleToggle = () => {
    setIsVisible((prevState) => !prevState);
  };

  const handleFileUploadChange = (info) => {
    setFileList(info.fileList);
    
    if (info.file.status === 'done') {
      const response = info.file.response;
      console.log('Upload response:', response);
      
      if (response && response.success && response.data) {
        if (response.data.uploadedFile) {
          setUploadedFile(response.data.uploadedFile);
          setJsonContent(response.data.uploadedFile.jsonContent || response.data.uploadedFile);
        } else if (response.data.json_data) {
          setJsonContent(response.data.json_data);
          setUploadedFile({
            name: response.data.filename || 'uploaded_file.json',
            ...response.data
          });
        } else {
          setJsonContent(response.data);
          setUploadedFile({
            name: 'uploaded_file.json',
            ...response.data
          });
        }
        message.success(__('File uploaded successfully!', 'sa-integrations-for-google-sheets'));
      } else {
        setFileList([]);
        message.error(response?.message || __('Failed to upload file. Please try again.', 'sa-integrations-for-google-sheets'));
      }
    } else if (info.file.status === 'error') {
      setFileList([]);
      message.error(__('File upload failed. Please try again.', 'sa-integrations-for-google-sheets'));
    }
  };

  // Determine which connection methods are available
  const isAutoConnected = autoConnectStatus === 'connected';
  const isManualJsonConnected = uploadedFile !== null;
  const isClientCredentialsConnected = clientCredentialsStatus === 'connected';

  // Determine which tabs should be disabled
  const getDisabledTabs = () => {
    const disabled = {};
    
    // If auto connected, disable other methods
    if (isAutoConnected) {
      disabled.json = true;
      disabled.clientid = true;
    }
    
    // If manual JSON connected, disable other methods
    if (isManualJsonConnected) {
      disabled.auto = true;
      disabled.clientid = true;
    }
    
    // If client credentials connected, disable other methods
    if (isClientCredentialsConnected) {
      disabled.auto = true;
      disabled.json = true;
    }
    
    return disabled;
  };

  const disabledTabs = getDisabledTabs();

  // If current active tab is disabled, switch to first enabled tab
  useEffect(() => {
    if (disabledTabs[activeTab]) {
      const enabledTab = ['auto', 'json', 'clientid'].find(tab => !disabledTabs[tab]);
      if (enabledTab) {
        setActiveTab(enabledTab);
        localStorage.setItem('saifgs_connection_tab', enabledTab);
      }
    }
  }, [disabledTabs, activeTab]);

  // Form initial values for JSON section
  const formInitialValues = jsonContent || {
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
    <ProCard
      className="saifgs-app-tab-content-container spreadsheet"
      headerBordered={false}
      bordered={false}
    >
      <ProCard
        tabs={{
          type: 'card',
          activeKey: activeTab,
          onChange: handleTabChange,
          items: [
            {
              key: 'auto',
              label: __('Auto Connect', 'sa-integrations-for-google-sheets'),
              children: (
                <AutoConnectSection
                  autoConnectStatus={autoConnectStatus}
                  connectedEmail={connectedEmail}
                  isAuthenticating={isAuthenticating}
                  onConnect={handleAutoConnect}
                  onDisconnect={handleDeactivateAutoConnect}
                  isDisabled={isManualJsonConnected || isClientCredentialsConnected}
                />
              ),
              disabled: disabledTabs.auto || false,
            },
            {
              key: 'json',
              label: __('Manual (JSON File)', 'sa-integrations-for-google-sheets'),
              children: (
                <ManualConfigWithJsonFileSection
                  uploadedFile={uploadedFile}
                  jsonContent={jsonContent}
                  fileList={fileList}
                  isVisible={isVisible}
                  formInitialValues={formInitialValues}
                  onRemoveFile={handleRemoveFile}
                  onToggle={handleToggle}
                  onFileUploadChange={handleFileUploadChange}
                  isDisabled={isAutoConnected || isClientCredentialsConnected}
                />
              ),
              disabled: disabledTabs.json || false,
            },
            {
              key: 'clientid',
              label: __('Manual (Client ID/Secret)', 'sa-integrations-for-google-sheets'),
              children: (
                <ManualConfigWithClientIdSection
                  isDisabled={isAutoConnected || isManualJsonConnected}
                  onStatusChange={(status, email) => {
                    setClientCredentialsStatus(status);
                    setClientCredentialsEmail(email);
                  }}
                  onAuthInitiate={() => {
                    // Save which tab we're connecting from
                    localStorage.setItem('saifgs_last_auth_tab', 'clientid');
                  }}
                />
              ),
              disabled: disabledTabs.clientid || false,
            },
          ],
        }}
      />
      
      <Divider />
      
      <ProCard
        className="connection-status-summary"
        title={__('Current Connection Status', 'sa-integrations-for-google-sheets')}
        headerBordered
        bordered
      >
        <div style={{ padding: '16px 0' }}>
          <p style={{ marginBottom: '8px' }}>
            {isAutoConnected && (
              <span style={{ color: 'green' }}>
                ✅ {__('Connected via Auto Connect (Email: ', 'sa-integrations-for-google-sheets')}
                {connectedEmail})
              </span>
            )}
            {isManualJsonConnected && (
              <span style={{ color: 'blue' }}>
                ✅ {__('Connected via JSON Service Account (File: ', 'sa-integrations-for-google-sheets')}
                {uploadedFile.name || uploadedFile.filename || 'uploaded_file.json'})
              </span>
            )}
            {isClientCredentialsConnected && (
              <span style={{ color: 'purple' }}>
                ✅ {__('Connected via Client Credentials (Email: ', 'sa-integrations-for-google-sheets')}
                {clientCredentialsEmail})
              </span>
            )}
            {!isAutoConnected && !isManualJsonConnected && !isClientCredentialsConnected && (
              <span style={{ color: 'orange' }}>
                ⚠️ {__('Not connected. Please choose a connection method above.', 'sa-integrations-for-google-sheets')}
              </span>
            )}
          </p>
          <p className="help-text" style={{ fontSize: '12px', color: '#666' }}>
            <small>
              {__('Note: Only one connection method can be active at a time. To switch methods, disconnect from the current method first.', 'sa-integrations-for-google-sheets')}
            </small>
          </p>
        </div>
      </ProCard>
    </ProCard>
  );
};

export default AllConnectionSettings;



// import React, { useState, useEffect } from 'react';
// import { ProCard, ProForm } from '@ant-design/pro-components';
// import { Tabs, Divider, message } from 'antd';
// import apiFetch from '@wordpress/api-fetch';
// import { __ } from '@wordpress/i18n';
// import AutoConnectSection from './AutoConnectSection';
// import ManualConfigWithJsonFileSection from './ManualConfigWithJsonFileSection';
// import ManualConfigWithClientIdSection from './ManualConfigWithClientIdSection';
// import '../../styles/spreadsheetsetting.scss';

// const AllConnectionSettings = ({ navVisibility }) => {
//   const [activeTab, setActiveTab] = useState('auto');
  
//   // States for Auto Connect
//   const [autoConnectStatus, setAutoConnectStatus] = useState('not_connected');
//   const [connectedEmail, setConnectedEmail] = useState('');
//   const [isAuthenticating, setIsAuthenticating] = useState(false);
  
//   // States for Manual JSON
//   const [fileList, setFileList] = useState([]);
//   const [uploadedFile, setUploadedFile] = useState(null);
//   const [jsonContent, setJsonContent] = useState(null);
//   const [isVisible, setIsVisible] = useState(false);
  
//   // States for Client Credentials
//   const [clientCredentialsStatus, setClientCredentialsStatus] = useState('not_connected');
//   const [clientCredentialsEmail, setClientCredentialsEmail] = useState('');

//   // Check URL parameters for auth messages
//   useEffect(() => {
//     const urlParams = new URLSearchParams(window.location.search);
//     const authMessage = urlParams.get('auth_message');
//     const authError = urlParams.get('saifgs_auth');
//     const clientAuthError = urlParams.get('client_auth_error');
    
//     if (authMessage === 'success') {
//       message.success(__('Successfully connected to Google!', 'sa-integrations-for-google-sheets'));
//       // Refresh the status
//       fetchInitialStatus();
//       // Clean URL
//       window.history.replaceState({}, document.title, window.location.pathname + '?page=saifgs-dashboard&tab=integration');
//     }
    
//     if (authError) {
//       message.error(__('Authentication failed: ', 'sa-integrations-for-google-sheets') + decodeURIComponent(authError));
//       window.history.replaceState({}, document.title, window.location.pathname + '?page=saifgs-dashboard&tab=integration');
//     }
    
//     if (clientAuthError) {
//       message.error(__('Client credentials authentication failed: ', 'sa-integrations-for-google-sheets') + decodeURIComponent(clientAuthError));
//       window.history.replaceState({}, document.title, window.location.pathname + '?page=saifgs-dashboard&tab=integration');
//     }
//   }, []);

//   // Check if any connection is active
//   useEffect(() => {
//     const isAnyConnected = autoConnectStatus === 'connected' || 
//                           uploadedFile !== null || 
//                           clientCredentialsStatus === 'connected';
    
//     if (navVisibility) {
//       navVisibility(!isAnyConnected);
//     }
//   }, [autoConnectStatus, uploadedFile, clientCredentialsStatus, navVisibility]);

//   // Load initial status
//   const fetchInitialStatus = async () => {
//     try {
//       // Fetch auto connect status
//       const autoConnectResponse = await apiFetch({
//         path: '/saifgs/v1/get-auto-connect-status/',
//         method: 'GET',
//       });
      
//       if (autoConnectResponse.success) {
//         setAutoConnectStatus(autoConnectResponse.data.status);
//         setConnectedEmail(autoConnectResponse.data.email || '');
//       }

//       // Fetch manual JSON status
//       const manualResponse = await apiFetch({
//         path: '/saifgs/v1/get-integration-setting/',
//         method: 'GET',
//       });
      
//       if (manualResponse.uploadedFile) {
//         setUploadedFile(manualResponse.uploadedFile);
//         setJsonContent(manualResponse.json_data);
//       }

//       // Fetch client credentials status
//       const clientCredentialsResponse = await apiFetch({
//         path: '/saifgs/v1/get-client-credentials/',
//         method: 'GET',
//       });
      
//       if (clientCredentialsResponse.success && clientCredentialsResponse.data) {
//         setClientCredentialsStatus(clientCredentialsResponse.data.status || 'not_connected');
//         setClientCredentialsEmail(clientCredentialsResponse.data.email || '');
//       }
//     } catch (error) {
//       console.error('Failed to fetch initial status:', error);
//     }
//   };

//   useEffect(() => {
//     fetchInitialStatus();
//   }, []);

//   // Auto Connect Handlers
//   const handleAutoConnect = async () => {
//     setIsAuthenticating(true);
//     try {
//       const response = await apiFetch({
//         path: '/saifgs/v1/initiate-auto-connect/',
//         method: 'POST',
//         data: {
//           _wpnonce: window.saifgs_customizations_localized_objects?.nonce || ''
//         }
//       });
      
//       if (response.success && response.data.auth_url) {
//         window.location.href = response.data.auth_url;
//       } else {
//         message.error(__('Failed to initiate authentication', 'sa-integrations-for-google-sheets'));
//       }
//     } catch (error) {
//       message.error(__('Authentication failed. Please try again.', 'sa-integrations-for-google-sheets'));
//     } finally {
//       setIsAuthenticating(false);
//     }
//   };

//   const handleDeactivateAutoConnect = async () => {
//     setIsAuthenticating(true);
//     try {
//       const response = await apiFetch({
//         path: '/saifgs/v1/deactivate-auto-connect/',
//         method: 'POST',
//         data: {
//           _wpnonce: window.saifgs_customizations_localized_objects?.nonce || ''
//         }
//       });
      
//       if (response.success) {
//         setAutoConnectStatus('not_connected');
//         setConnectedEmail('');
//         message.success(__('Successfully disconnected from Google', 'sa-integrations-for-google-sheets'));
//       } else {
//         message.error(response.message || __('Failed to disconnect', 'sa-integrations-for-google-sheets'));
//       }
//     } catch (error) {
//       message.error(__('Failed to disconnect. Please try again.', 'sa-integrations-for-google-sheets'));
//     } finally {
//       setIsAuthenticating(false);
//     }
//   };

//   // Manual JSON Handlers
//   const handleRemoveFile = async () => {
//     try {
//       const response = await apiFetch({
//         path: '/saifgs/v1/remove-file/',
//         method: 'POST',
//         data: {
//           _wpnonce: window.saifgs_customizations_localized_objects?.nonce || ''
//         }
//       });
//       setUploadedFile(null);
//       setJsonContent(null);
//       setFileList([]);
//       message.success(__('File removed successfully!', 'sa-integrations-for-google-sheets'));
//     } catch (error) {
//       message.error(__('Failed to remove file. Please try again.', 'sa-integrations-for-google-sheets'));
//     }
//   };

//   const handleToggle = () => {
//     setIsVisible((prevState) => !prevState);
//   };

//   const handleFileUploadChange = (info) => {
//     setFileList(info.fileList);
    
//     if (info.file.status === 'done') {
//       const response = info.file.response;
//       console.log('Upload response:', response);
      
//       if (response && response.success && response.data) {
//         if (response.data.uploadedFile) {
//           setUploadedFile(response.data.uploadedFile);
//           setJsonContent(response.data.uploadedFile.jsonContent || response.data.uploadedFile);
//         } else if (response.data.json_data) {
//           setJsonContent(response.data.json_data);
//           setUploadedFile({
//             name: response.data.filename || 'uploaded_file.json',
//             ...response.data
//           });
//         } else {
//           setJsonContent(response.data);
//           setUploadedFile({
//             name: 'uploaded_file.json',
//             ...response.data
//           });
//         }
//         message.success(__('File uploaded successfully!', 'sa-integrations-for-google-sheets'));
//       } else {
//         setFileList([]);
//         message.error(response?.message || __('Failed to upload file. Please try again.', 'sa-integrations-for-google-sheets'));
//       }
//     } else if (info.file.status === 'error') {
//       setFileList([]);
//       message.error(__('File upload failed. Please try again.', 'sa-integrations-for-google-sheets'));
//     }
//   };

//   // Determine which connection methods are available
//   const isAutoConnected = autoConnectStatus === 'connected';
//   const isManualJsonConnected = uploadedFile !== null;
//   const isClientCredentialsConnected = clientCredentialsStatus === 'connected';

//   // Determine which tabs should be disabled
//   const getDisabledTabs = () => {
//     const disabled = {};
    
//     // If auto connected, disable other methods
//     if (isAutoConnected) {
//       disabled.json = true;
//       disabled.clientid = true;
//     }
    
//     // If manual JSON connected, disable other methods
//     if (isManualJsonConnected) {
//       disabled.auto = true;
//       disabled.clientid = true;
//     }
    
//     // If client credentials connected, disable other methods
//     if (isClientCredentialsConnected) {
//       disabled.auto = true;
//       disabled.json = true;
//     }
    
//     return disabled;
//   };

//   const disabledTabs = getDisabledTabs();

//   // Form initial values for JSON section
//   const formInitialValues = jsonContent || {
//     type: '',
//     project_id: '',
//     private_key_id: '',
//     private_key: '',
//     client_email: '',
//     client_id: '',
//     auth_uri: '',
//     token_uri: '',
//     auth_provider_x509_cert_url: '',
//     client_x509_cert_url: '',
//     universe_domain: '',
//   };

//   return (
//     <ProCard
//       className="saifgs-app-tab-content-container spreadsheet"
//       headerBordered={false}
//       bordered={false}
//     >
//       <ProCard
//         tabs={{
//           type: 'card',
//           activeKey: activeTab,
//           onChange: (key) => setActiveTab(key),
//           items: [
//             {
//               key: 'auto',
//               label: __('Auto Connect', 'sa-integrations-for-google-sheets'),
//               children: (
//                 <AutoConnectSection
//                   autoConnectStatus={autoConnectStatus}
//                   connectedEmail={connectedEmail}
//                   isAuthenticating={isAuthenticating}
//                   onConnect={handleAutoConnect}
//                   onDisconnect={handleDeactivateAutoConnect}
//                   isDisabled={isManualJsonConnected || isClientCredentialsConnected}
//                 />
//               ),
//               disabled: disabledTabs.auto || false,
//             },
//             {
//               key: 'json',
//               label: __('Manual (JSON File)', 'sa-integrations-for-google-sheets'),
//               children: (
//                 <ManualConfigWithJsonFileSection
//                   uploadedFile={uploadedFile}
//                   jsonContent={jsonContent}
//                   fileList={fileList}
//                   isVisible={isVisible}
//                   formInitialValues={formInitialValues}
//                   onRemoveFile={handleRemoveFile}
//                   onToggle={handleToggle}
//                   onFileUploadChange={handleFileUploadChange}
//                   isDisabled={isAutoConnected || isClientCredentialsConnected}
//                 />
//               ),
//               disabled: disabledTabs.json || false,
//             },
//             {
//               key: 'clientid',
//               label: __('Manual (Client ID/Secret)', 'sa-integrations-for-google-sheets'),
//               children: (
//                 <ManualConfigWithClientIdSection
//                   isDisabled={isAutoConnected || isManualJsonConnected}
//                   onStatusChange={(status, email) => {
//                     setClientCredentialsStatus(status);
//                     setClientCredentialsEmail(email);
//                   }}
//                 />
//               ),
//               disabled: disabledTabs.clientid || false,
//             },
//           ],
//         }}
//       />
      
//       <Divider />
      
//       <ProCard
//         className="connection-status-summary"
//         title={__('Current Connection Status', 'sa-integrations-for-google-sheets')}
//         headerBordered
//         bordered
//       >
//         <div style={{ padding: '16px 0' }}>
//           <p style={{ marginBottom: '8px' }}>
//             {isAutoConnected && (
//               <span style={{ color: 'green' }}>
//                 ✅ {__('Connected via Auto Connect (Email: ', 'sa-integrations-for-google-sheets')}
//                 {connectedEmail})
//               </span>
//             )}
//             {isManualJsonConnected && (
//               <span style={{ color: 'blue' }}>
//                 ✅ {__('Connected via JSON Service Account (File: ', 'sa-integrations-for-google-sheets')}
//                 {uploadedFile.name || uploadedFile.filename || 'uploaded_file.json'})
//               </span>
//             )}
//             {isClientCredentialsConnected && (
//               <span style={{ color: 'purple' }}>
//                 ✅ {__('Connected via Client Credentials (Email: ', 'sa-integrations-for-google-sheets')}
//                 {clientCredentialsEmail})
//               </span>
//             )}
//             {!isAutoConnected && !isManualJsonConnected && !isClientCredentialsConnected && (
//               <span style={{ color: 'orange' }}>
//                 ⚠️ {__('Not connected. Please choose a connection method above.', 'sa-integrations-for-google-sheets')}
//               </span>
//             )}
//           </p>
//           <p className="help-text" style={{ fontSize: '12px', color: '#666' }}>
//             <small>
//               {__('Note: Only one connection method can be active at a time. To switch methods, disconnect from the current method first.', 'sa-integrations-for-google-sheets')}
//             </small>
//           </p>
//         </div>
//       </ProCard>
//     </ProCard>
//   );
// };

// export default AllConnectionSettings;
