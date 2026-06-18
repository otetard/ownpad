import { basename, extname } from 'path'

// Stolen from Nextcloud’s Files app:
// https://github.com/nextcloud/server/blob/338c9e4a8d718472c45936776db53d65ff541cbf/apps/files/src/utils/fileUtils.ts
/**
 * Create an unique file name
 *
 * @param {string} name The initial name to use
 * @param {string[]} otherNames Other names that are already used
 * @param {(n: number) => string} suffix A function that takes an index an returns a suffix to add, defaults to '(index)'
 * @return {string} Either the initial name, if unique, or the name with the suffix so that the name is unique
 */
export const getUniqueName = (name, otherNames, suffix = (n) => `(${n})`) => {
	let newName = name
	let i = 1
	while (otherNames.includes(newName)) {
		const ext = extname(name)
		newName = `${basename(name, ext)} ${suffix(i++)}${ext}`
	}
	return newName
}
