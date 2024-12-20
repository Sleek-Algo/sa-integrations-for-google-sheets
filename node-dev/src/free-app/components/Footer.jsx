import { __ } from '@wordpress/i18n';
import '../styles/footer.scss';
const Footer = () => {
	return (
		<div className="saifgs-app-Footer">
			<div className="saifgs-app-container">
				<div>
					{ __( 'Powered By ', 'sa-integrations-for-google-sheets' ) }
					<a
						href="https://www.sleekalgo.com/"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __(
							'Sleekalgo',
							'sa-integrations-for-google-sheets'
						) }
					</a>
				</div>
			</div>
		</div>
	);
};
export default Footer;
