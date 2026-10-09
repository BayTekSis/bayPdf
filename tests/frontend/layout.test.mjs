import { test } from 'node:test'
import assert from 'node:assert/strict'
import { addPageNumber, addTrailingElement, changeCollectionSource, collectionColumns, collectionVariables, createFlowDocument, dimensions, moveElement } from '../../resources/js/layout.js'

test('landscape and ISO page dimensions', () => {
  assert.deepEqual(dimensions({ size: 'A5', orientation: 'landscape' }), [210, 148])
})
test('dragging clamps to paper and snaps without mutating the original', () => {
  const original = { x: 10, y: 10, width: 30, height: 20 }
  assert.deepEqual(moveElement(original, 300, -50, { size: 'A4' }), { x: 180, y: 0, width: 30, height: 20 })
  assert.equal(original.x, 10)
})

const items = {
  key: 'items',
  type: 'collection',
  fields: [
    { key: 'description', label: 'Description', type: 'text' },
    { key: 'quantity', label: 'Quantity', type: 'number' },
    { key: 'total', label: 'Total', type: 'money' },
  ],
}

test('collection variables and deterministic column widths are derived from the snapshot', () => {
  assert.deepEqual(collectionVariables([{ key: 'name', type: 'text' }, items]), [items])
  assert.deepEqual(collectionColumns(items, 180), [
    { field: 'description', label: 'Description', width: 60, align: 'L' },
    { field: 'quantity', label: 'Quantity', width: 60, align: 'R' },
    { field: 'total', label: 'Total', width: 60, align: 'R' },
  ])
  assert.equal(collectionColumns({ ...items, fields: items.fields.slice(0, 3) }, 179.9).reduce((sum, column) => sum + column.width, 0), 179.9)
})

test('flow mode preserves fixed elements and creates a bounded table payload', () => {
  const legacy = { page: { size: 'A4', orientation: 'portrait' }, elements: [{ id: 'title', type: 'text' }] }
  const flow = createFlowDocument(legacy, items, 'table-id')
  assert.equal(flow.schema_version, 2)
  assert.deepEqual(flow.elements[0], { id: 'title', type: 'text', region: 'page', repeat: 'first' })
  assert.equal(flow.flow.table.source, 'items')
  assert.equal(flow.flow.table.columns.reduce((sum, column) => sum + column.width, 0), flow.flow.table.width)
  assert.equal(legacy.schema_version, undefined)
})

test('source, trailing content and page context update the save payload', () => {
  const flow = createFlowDocument({ page: { size: 'A4', orientation: 'portrait' }, elements: [] }, items, 'table-id')
  const services = { ...items, key: 'services', fields: items.fields.slice(0, 2) }
  changeCollectionSource(flow, services)
  const trailing = addTrailingElement(flow, 'variable', '', { key: 'summary' }, 'trailing-id')
  const pageNumber = addPageNumber(flow, 'Page {current} / {total}', 'page-number-id')
  assert.equal(flow.flow.table.source, 'services')
  assert.deepEqual(flow.flow.table.columns.map(column => column.field), ['description', 'quantity'])
  assert.equal(trailing.variable, 'summary')
  assert.equal(pageNumber.repeat, 'all')
  assert.match(JSON.stringify(flow), /\{current\}.*\{total\}/)
})
