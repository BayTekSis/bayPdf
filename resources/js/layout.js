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

export function collectionVariables(variables = []) {
  return variables.filter(variable => variable.type === 'collection')
}

export function collectionColumns(collection, width) {
  const fields = collection?.fields ?? []
  if (!fields.length) return []

  const base = Math.floor(width * 10 / fields.length) / 10
  return fields.map((field, index) => ({
    field: field.key,
    label: field.label,
    width: index === fields.length - 1 ? Number((width - base * (fields.length - 1)).toFixed(1)) : base,
    align: ['number', 'money'].includes(field.type) ? 'R' : 'L',
  }))
}

export function createFlowDocument(document, collection, id = crypto.randomUUID()) {
  const [pageWidth, pageHeight] = dimensions(document.page)
  const tableWidth = Number((pageWidth - 30).toFixed(1))

  return {
    schema_version: 2,
    page: { ...document.page },
    elements: document.elements.map(element => ({ ...element, region: element.region ?? 'page', repeat: element.repeat ?? 'first' })),
    flow: {
      first_top: 35,
      continuation_top: 25,
      bottom: Number((pageHeight - 22).toFixed(1)),
      gap: 4,
      table: {
        id,
        type: 'collection_table',
        source: collection.key,
        x: 15,
        width: tableWidth,
        repeat_header: true,
        columns: collectionColumns(collection, tableWidth),
        header: { font_size: 9, font_style: 'B', color: '#172b29', fill: '#e5edde', padding: 2, border: true },
        row: { font_size: 9, font_style: '', color: '#172b29', fill: null, padding: 2, border: true },
      },
      trailing: [],
    },
  }
}

export function changeCollectionSource(document, collection) {
  document.flow.table.source = collection.key
  document.flow.table.columns = collectionColumns(collection, document.flow.table.width)
}

export function addTrailingElement(document, type, label, variable = null, id = crypto.randomUUID()) {
  const element = {
    id,
    type,
    x: document.flow.table.x,
    width: document.flow.table.width,
    height: 12,
    content: type === 'text' ? label : '',
    variable: variable?.key ?? '',
    asset: '',
    font_size: 10,
    font_style: '',
    color: '#173b34',
    fill: null,
    align: 'L',
    hidden: false,
    gap_before: 4,
  }
  document.flow.trailing.push(element)

  return element
}

export function addPageNumber(document, label, id = crypto.randomUUID()) {
  const [pageWidth, pageHeight] = dimensions(document.page)
  const element = {
    id,
    type: 'page_number',
    region: 'footer',
    repeat: 'all',
    x: Number((pageWidth - 75).toFixed(1)),
    y: Number((pageHeight - 14).toFixed(1)),
    width: 60,
    height: 7,
    content: label,
    variable: '',
    asset: '',
    font_size: 8,
    font_style: '',
    color: '#173b34',
    fill: null,
    align: 'R',
    hidden: false,
  }
  document.elements.push(element)

  return element
}
