export function hasCapability( cap: string ): boolean {
	return SearchRegexi10n.caps.capabilities.indexOf( cap ) !== -1;
}

export function hasPageAccess( page: string ): boolean {
	return SearchRegexi10n.caps.pages.indexOf( page ) !== -1;
}

export const CAP_SEARCHREGEX_SEARCH = 'search_regex_manage';
export const CAP_SEARCHREGEX_OPTIONS = 'search_regex_options';
export const CAP_SEARCHREGEX_SUPPORT = 'search_regex_support';
