import { test, expect } from '@playwright/test'

test('a full library keeps editing tools within the desktop viewport', async ({ page }) => {
  await page.route('**/api/templates?page=1', async route => {
    const response = await route.fetch()
    const result = await response.json()
    result.data = Array.from({ length: 30 }, (_, index) => ({ id: 10000 + index, name: `Library template ${index}`, document_type: 'certificate' }))
    await route.fulfill({ response, json: result })
  })
  await page.goto('/baypdf')
  await page.getByRole('button', { name: '+ New template' }).first().click()
  await page.getByRole('dialog').getByLabel('Template name').fill('Library viewport ' + Date.now())
  await page.getByRole('dialog').getByRole('button', { name: 'Create template' }).click()
  await expect(page.getByRole('button', { name: 'T Text', exact: true })).toBeInViewport()
  await page.screenshot({ path: '.artifacts/studio-library.png', fullPage: true })
})

test('variable groups with object property names remain editable', async ({ page }) => {
  await page.route('**/api/templates', async route => {
    if (route.request().method() !== 'POST') return route.continue()
    const response = await route.fetch()
    const template = await response.json()
    template.versions[0].variables[0].group = 'constructor'
    template.versions[0].variables[1].group = '__proto__'
    await route.fulfill({ response, json: template })
  })
  await page.goto('/baypdf')
  await page.getByRole('button', { name: '+ New template' }).first().click()
  await page.getByRole('dialog').getByLabel('Template name').fill('Variable groups ' + Date.now())
  await page.getByRole('dialog').getByRole('button', { name: 'Create template' }).click()
  await expect(page.getByRole('button', { name: /Recipient name/ })).toBeVisible()
  await page.getByRole('button', { name: /Course title/ }).click()
  await expect(page.locator('.paper .element')).toContainText('Designing for the future')
})

test('create, edit, preview, publish and clone a template', async ({ page }) => {
  const errors = []
  page.on('pageerror', error => errors.push(error.message))
  page.on('dialog', dialog => dialog.accept())
  await page.goto('/baypdf')
  await page.getByRole('button', { name: '+ New template' }).first().click()
  const form = page.getByRole('dialog')
  const name = 'Studio certificate ' + Date.now()
  await form.getByLabel('Template name').fill(name)
  await form.getByLabel('Document type').selectOption('certificate')
  await form.getByRole('button', { name: 'Create template' }).click()
  await expect(page.getByRole('heading', { name })).toBeVisible()
  await page.getByRole('button', { name: 'T Text', exact: true }).click()
  await page.getByLabel('Content', { exact: true }).fill('Certificate of participation')
  await page.getByLabel('Font size').fill('22')
  await page.getByLabel('Width', { exact: true }).fill('170')
  await page.getByLabel('Height', { exact: true }).fill('30')
  await page.getByLabel('Y', { exact: true }).fill('30')
  await page.getByRole('button', { name: /Recipient name/ }).click()
  await page.getByLabel('Y', { exact: true }).fill('75')
  await page.getByLabel('Font size').fill('24')
  await page.getByLabel('Width', { exact: true }).fill('170')
  await page.getByLabel('Height', { exact: true }).fill('30')
  await page.getByRole('button', { name: 'Save draft', exact: true }).click()
  await expect(page.getByRole('status')).toContainText('Draft saved')
  await expect(page.getByRole('button', { name: 'Save draft', exact: true })).toBeDisabled()
  await page.screenshot({ path: '.artifacts/studio-desktop.png', fullPage: true })
  await page.getByRole('button', { name: 'PDF preview' }).click()
  await expect(page.getByRole('dialog').getByRole('heading', { name: 'PDF preview' })).toBeVisible()
  await expect(page.getByRole('link', { name: 'Download PDF' })).toHaveAttribute('href', /^blob:/)
  await page.getByRole('button', { name: 'Close', exact: true }).click()
  await page.getByRole('button', { name: /Publish version/ }).click()
  await expect(page.getByRole('button', { name: 'Create new draft' })).toBeVisible()
  await expect(page.getByRole('button', { name: 'T Text', exact: true })).toBeDisabled()
  await page.getByRole('button', { name: 'Create new draft' }).click()
  await expect(page.getByRole('combobox', { name: 'Version', exact: true })).toContainText('Version 2')
  await expect(page.getByRole('button', { name: 'T Text', exact: true })).toBeEnabled()
  expect(errors).toEqual([])
})

test('German shell and mobile layout remain usable', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 })
  await page.goto('/baypdf?locale=de')
  await expect(page.getByRole('button', { name: '+ Neue Vorlage' }).first()).toBeVisible()
  await page.getByRole('button', { name: '+ Neue Vorlage' }).first().click()
  await page.getByRole('dialog').getByLabel('Vorlagenname').fill('Mobile Vorlage ' + 'W'.repeat(105))
  await page.getByRole('dialog').getByRole('button', { name: 'Vorlage erstellen' }).click()
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Mobile Vorlage')
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth)
  expect(overflow).toBe(false)
  await page.screenshot({ path: '.artifacts/studio-mobile.png', fullPage: true })
})

test('server-filtered library does not expose another scope or trust a browser scope value', async ({ page }) => {
  let requestedUrl = ''
  await page.route('**/api/templates?page=1', async route => {
    requestedUrl = route.request().url()
    const response = await route.fetch()
    await route.fulfill({ response, json: {
      current_page: 1,
      data: [{ id: 70001, name: 'Scope A template', document_type: 'certificate' }],
      next_page_url: null,
    } })
  })
  await page.goto('/baypdf?scope_key=organization%3Ab')
  await expect(page.getByRole('button', { name: /Scope A template/ })).toBeVisible()
  await expect(page.getByText('Scope B template')).toHaveCount(0)
  expect(requestedUrl).not.toContain('scope_key')
  const guessedIdStatus = await page.evaluate(async () => (await fetch('/baypdf/api/templates/999999', { headers: { Accept: 'application/json' } })).status)
  expect(guessedIdStatus).toBe(404)
})

test('collection table produces a multi-page preview with repeat and trailing settings', async ({ page }) => {
  await page.goto('/baypdf')
  await page.getByRole('button', { name: '+ New template' }).first().click()
  const form = page.getByRole('dialog')
  const name = 'Dynamic document ' + Date.now()
  await form.getByLabel('Template name').fill(name)
  await form.getByLabel('Document type').selectOption('commercial_document')
  await form.getByRole('button', { name: 'Create template' }).click()

  await page.getByRole('button', { name: 'T Text', exact: true }).click()
  await page.getByLabel('Content', { exact: true }).fill('Document header')
  await page.getByText('Advanced / data', { exact: true }).click()
  await page.getByRole('button', { name: '+ Collection table' }).click()
  await expect(page.getByRole('button', { name: 'Collection table', exact: true })).toBeVisible()
  await page.locator('.paper .element.text').click()
  await page.getByLabel('Page region').selectOption('header')
  await page.getByLabel('Show on').selectOption('all')
  await page.getByRole('button', { name: '+ Page number' }).click()
  await page.getByRole('button', { name: /Summary/ }).last().click()

  const saveRequest = page.waitForRequest(request => request.url().includes('/api/versions/') && request.method() === 'PUT')
  await page.getByRole('button', { name: 'Save draft', exact: true }).click()
  const payload = (await saveRequest).postDataJSON().document
  expect(payload.schema_version).toBe(2)
  expect(payload.flow.table.source).toBe('items')
  expect(payload.flow.table.repeat_header).toBe(true)
  expect(payload.elements.some(element => element.type === 'page_number' && element.repeat === 'all')).toBe(true)
  expect(payload.elements.some(element => element.region === 'header' && element.repeat === 'all')).toBe(true)
  expect(payload.flow.trailing.some(element => element.variable === 'document.summary')).toBe(true)

  const previewResponse = page.waitForResponse(response => response.url().includes('/preview') && response.request().method() === 'POST')
  await page.getByRole('button', { name: 'PDF preview' }).click()
  const response = await previewResponse
  expect(response.status()).toBe(200)
  expect(response.headers()['content-type']).toContain('application/pdf')
  await expect(page.getByRole('dialog').getByRole('heading', { name: 'PDF preview' })).toBeVisible()
  await expect(page.getByRole('dialog').getByRole('link', { name: 'Download PDF' })).toHaveAttribute('href', /^blob:/)
})
