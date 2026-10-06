export function dimensions(page) {
  const sizes = { A4: [210, 297], A5: [148, 210], Letter: [215.9, 279.4] }
  const size = [...sizes[page.size]]
  return page.orientation === 'landscape' ? size.reverse() : size
}

export function moveElement(element, dx, dy, page, snap = true) {
  const [width, height] = dimensions(page)
  const clamp = (n, max) => Math.max(0, Math.min(max, n))
  const round = n => snap ? Math.round(n) : Math.round(n * 10) / 10
  return {
    ...element,
    x: clamp(round(element.x + dx), width - element.width),
    y: clamp(round(element.y + dy), height - element.height),
  }
}
