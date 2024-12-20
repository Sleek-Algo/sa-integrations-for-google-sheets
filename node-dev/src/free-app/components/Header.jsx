import { __ } from '@wordpress/i18n';
import { Button } from 'antd';
import { CustomerServiceOutlined, CopyOutlined } from '@ant-design/icons';
import '../styles/header.scss';
const Header = () => {
	return (
		<div className="saifgs-app-header">
			<div className="saifgs-app-container">
				<div className="saifgs-app-logo">
					<h1>
						{ __(
							'SA Integrations For Google Sheets',
							'sa-integrations-for-google-sheets'
						) }
						<span className="saifgs-version">
							{ __(
								'(v1.1.0)',
								'sa-integrations-for-google-sheets'
							) }
						</span>
					</h1>
					<div className="saifgs-support">
						<a
							href="https://www.sleekalgo.com/contact-us/"
							target="_blank"
						>
							<Button icon={ <CustomerServiceOutlined /> }>
								{ __(
									'Support',
									'sa-integrations-for-google-sheets'
								) }
							</Button>
						</a>
						<a
							href="https://www.sleekalgo.com/sa-integrations-for-google-sheets/#installation-guide"
							target="_blank"
						>
							<Button icon={ <CopyOutlined /> }>
								{ __(
									'Documentation',
									'sa-integrations-for-google-sheets'
								) }
							</Button>
						</a>
					</div>
				</div>
			</div>
		</div>
	);
};

export default Header;
