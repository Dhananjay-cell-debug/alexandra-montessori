import { Link, NavLink } from 'react-router-dom'

// Keep label nodes React-owned, so changing routes or form state remains safe.
// Only direct text is grouped. Existing images, icons and rich elements retain
// their original identity and remain separate selectable children.
export function labelProps(type, props, create) {
  if (!props || !['a', 'button'].includes(type) && type !== Link && type !== NavLink) return props
  const children = Array.isArray(props.children) ? props.children.flat(Infinity) : [props.children]
  const result = []
  let text = []
  let index = 0
  const flush = () => {
    if (!text.length) return
    const value = text.join('')
    if (text.some(item => String(item).trim())) {
      index += 1
      result.push(create('span', { 'data-am-ws-label': String(index), style: { display: 'inline-block' }, children: value }, `am-label-${index}`))
    } else result.push(...text)
    text = []
  }
  for (const child of children) {
    if (typeof child === 'string' || typeof child === 'number') text.push(child)
    else { flush(); result.push(child) }
  }
  flush()
  return { ...props, children: result.length === 1 ? result[0] : result }
}
