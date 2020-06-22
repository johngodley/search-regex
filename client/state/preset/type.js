export const PRESET_SELECT = 'PRESET_SELECT';
export const PRESET_SAVE = 'PRESET_SAVE';
export const PRESET_SAVED = 'PRESET_SAVED';
export const PRESET_SAVE_FAIL = 'PRESET_SAVE_FAIL';
export const PRESET_UPLOAD = 'PRESET_UPLOAD';
export const PRESET_UPLOAD_COMPLETE = 'PRESET_UPLOAD_COMPLETE';
export const PRESET_UPLOAD_FAIL = 'PRESET_UPLOAD_FAIL';
export const PRESET_CLIPBOARD_FAIL = 'PRESET_CLIPBOARD_FAIL';
export const PRESET_SET_CLIPBOARD = 'PRESET_SET_CLIPBOARD';
export const PRESET_CLEAR = 'PRESET_CLEAR';

/**
 * A preset tag
 *
 * @typedef PresetTag
 * @type
 * @property {string} name - The tag name to replace
 * @property {string} label - The label for the tag
 */

/**
 * A preset search
 *
 * @typedef PresetValue
 * @type
 * @property {SearchValues} search - Search details
 * @property {string} id - Preset ID
 * @property {string} name - Preset name
 * @property {string[]} locked - Array of locked fields
 * @property {PresetTag[]} tags - Array of tags
 */
