import { test } from 'node:test'
import assert from 'node:assert/strict'
import { dimensions, moveElement } from '../../resources/js/layout.js'

test('landscape and ISO page dimensions', () => {
  assert.deepEqual(dimensions({ size: 'A5', orientation: 'landscape' }), [210, 148])
})
test('dragging clamps to paper and snaps without mutating the original', () => {
  const original = { x: 10, y: 10, width: 30, height: 20 }
  assert.deepEqual(moveElement(original, 300, -50, { size: 'A4' }), { x: 180, y: 0, width: 30, height: 20 })
  assert.equal(original.x, 10)
})
