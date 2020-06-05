function getApi() {
	if ( typeof SearchRegexi10n !== 'undefined' && SearchRegexi10n.api ) {
		return SearchRegexi10n.api;
	}

	return {
		WP_API_root: '/wp-json/',
		WP_API_nonce: 'nonce',
	};
}

/**
 * Get the API URL
 * @returns {String} API URL
 */
export const getApiUrl = () => getApi().WP_API_root;

/**
 * Set the API URL
 * @param {String} url URL value
 */
export const setApiUrl = url => typeof SearchRegexi10n !== 'undefined' ? SearchRegexi10n.api.WP_API_root = url : null;

/**
 * Get the API nonce
 * @returns {String} Nonce
 */
export const getApiNonce = () => getApi().WP_API_nonce;

/**
 * Set the API URL
 * @param {String} nonce Nonce value
 */
export const setApiNonce = nonce => typeof SearchRegexi10n !== 'undefined' ? SearchRegexi10n.api.WP_API_nonce = nonce : null;
