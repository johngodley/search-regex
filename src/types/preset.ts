import type { SearchValues } from './search';

/**
 * A preset tag
 */
export interface PresetTag {
	name: string;
	title: string;
}

/**
 * A preset search
 */
export interface PresetValue {
	search: SearchValues;
	id: string;
	name: string;
	description?: string;
	locked: string[];
	tags: PresetTag[];
}
