import { jsx as reactJsx, jsxs as reactJsxs } from 'react/jsx-runtime'
import { labelProps } from './editable-labels'
export { Fragment } from 'react/jsx-runtime'
export function jsx(type, props, key) { return reactJsx(type, labelProps(type, props, reactJsx), key) }
export function jsxs(type, props, key) { return reactJsxs(type, labelProps(type, props, reactJsx), key) }
