import { jsxDEV as reactJsxDEV } from 'react/jsx-dev-runtime'
import { jsx as reactJsx } from 'react/jsx-runtime'
import { labelProps } from './editable-labels'
export { Fragment } from 'react/jsx-dev-runtime'
export function jsxDEV(type, props, key, isStatic, source, self) {
  return reactJsxDEV(type, labelProps(type, props, reactJsx), key, isStatic, source, self)
}
