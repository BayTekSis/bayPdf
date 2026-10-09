<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import { addPageNumber, addTrailingElement, changeCollectionSource, collectionVariables, createFlowDocument, dimensions, moveElement, rebalanceColumns as rebalanceTableColumns, resizeFlowDocument } from './layout.js'

const props = defineProps({ base: String, locale: String, messages: Object })
const t = key => props.messages[key] ?? key
const templates = ref([]), types = ref([]), assets = ref([]), template = ref(null), version = ref(null), document = ref(null)
const selectedId = ref(null), busy = ref(false), error = ref(''), notice = ref(''), creating = ref(false)
const newName = ref(''), newType = ref(''), nextPage = ref(null), zoom = ref(0.8), snap = ref(true)
const previewUrl = ref(''), savedJson = ref(''), history = ref([]), historyIndex = ref(-1)
const createDialog = ref(null), previewDialog = ref(null), newColumnField = ref('')
const table = computed(() => document.value?.flow?.table ?? null)
const trailing = computed(() => document.value?.flow?.trailing ?? [])
const selected = computed(() => [...(document.value?.elements ?? []), ...trailing.value].find(e => e.id === selectedId.value))
const selectedFixed = computed(() => document.value?.elements.some(element => element.id === selectedId.value))
const readOnly = computed(() => Boolean(version.value?.published_at))
const fingerprint = value => JSON.stringify(value, (_key, item) => item && typeof item === 'object' && !Array.isArray(item) ? Object.fromEntries(Object.entries(item).sort(([a], [b]) => a.localeCompare(b))) : item)
const dirty = computed(() => document.value && fingerprint(document.value) !== savedJson.value)
const paper = computed(() => document.value ? dimensions(document.value.page) : [210, 297])
const scale = computed(() => 96 / 25.4 * zoom.value)
const collections = computed(() => collectionVariables(version.value?.variables))
const activeCollection = computed(() => collections.value.find(item => item.key === table.value?.source))
const collectionFields = computed(() => activeCollection.value?.fields ?? [])
const availableColumnFields = computed(() => collectionFields.value.filter(field => !table.value?.columns.some(column => column.field === field.key)))
const columnTotal = computed(() => Number((table.value?.columns.reduce((total, column) => total + Number(column.width || 0), 0) ?? 0).toFixed(1)))
const columnWidthsValid = computed(() => !table.value || Math.abs(columnTotal.value - Number(table.value.width)) < 0.01)
const hasPublishableContent = computed(() => Boolean(document.value?.elements.length || table.value))
const groups = computed(() => {
  const result = Object.create(null)
  for (const v of version.value?.variables ?? []) if (v.type !== 'collection') (result[v.group ?? t('variables')] ??= []).push(v)
  return result
})

async function api(path, options = {}) {
  const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': documentCsrf(), ...options.headers }
  if (options.body && !(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json'
    options.body = JSON.stringify(options.body)
  }
  const response = await fetch(`${props.base}/api${path}`, { ...options, headers, credentials: 'same-origin' })
  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    throw new Error(response.status === 409 ? t('conflict') : Object.values(body.errors ?? {}).flat().join(' ') || body.message || t('error'))
  }
  return options.pdf ? response.blob() : response.json()
}
function documentCsrf() { return window.document.querySelector('meta[name="csrf-token"]')?.content ?? '' }
async function run(action) {
  busy.value = true; error.value = ''; notice.value = ''
  try { return await action() } catch (e) { error.value = e.message; return false } finally { busy.value = false }
}
async function loadList(page = 1) {
  const result = await api(`/templates?page=${page}`)
  templates.value = page === 1 ? result.data : [...templates.value, ...result.data]
  nextPage.value = result.next_page_url ? result.current_page + 1 : null
}
function canLeave() { return !dirty.value || window.confirm(t('discard')) }
function adopt(v) {
  version.value = v; document.value = JSON.parse(JSON.stringify(v.document)); selectedId.value = null
  savedJson.value = fingerprint(document.value); history.value = [JSON.stringify(document.value)]; historyIndex.value = 0
}
async function openTemplate(id) {
  if (!canLeave()) return
  await run(async () => { template.value = await api(`/templates/${id}`); adopt(template.value.versions[0]) })
}
function chooseVersion(event) {
  if (canLeave()) adopt(template.value.versions.find(v => v.id === Number(event.target.value)))
  else event.target.value = version.value.id
}
function remember() {
  const value = JSON.stringify(document.value)
  if (history.value[historyIndex.value] === value) return
  history.value = history.value.slice(0, historyIndex.value + 1)
  history.value.push(value)
  if (history.value.length > 60) history.value.shift()
  historyIndex.value = history.value.length - 1
}
function travel(delta) {
  historyIndex.value += delta; document.value = JSON.parse(history.value[historyIndex.value]); selectedId.value = null
}
async function createTemplate() {
  await run(async () => {
    template.value = await api('/templates', { method: 'POST', body: { name: newName.value, document_type: newType.value } })
    adopt(template.value.versions[0]); createDialog.value.close(); creating.value = false; newName.value = ''; await loadList()
  })
}
async function save() {
  const submitted = fingerprint(document.value)
  const current = await api(`/versions/${version.value.id}`, { method: 'PUT', body: { document: document.value, lock_version: version.value.lock_version } })
  if (fingerprint(document.value) === submitted) document.value = JSON.parse(JSON.stringify(current.document))
  version.value = current; savedJson.value = fingerprint(current.document)
  const index = template.value.versions.findIndex(v => v.id === current.id)
  template.value.versions[index] = current; notice.value = t('saved')
}
async function publish() {
  if (!window.confirm(t('publishConfirm'))) return
  await run(async () => {
    if (dirty.value) await save()
    const v = await api(`/versions/${version.value.id}/publish`, { method: 'POST', body: { lock_version: version.value.lock_version } })
    template.value.versions[template.value.versions.findIndex(item => item.id === v.id)] = v
    adopt(v); notice.value = t('publishSuccess')
  })
}
async function clone() {
  if (!canLeave()) return
  await run(async () => {
    const v = await api(`/versions/${version.value.id}/clone`, { method: 'POST' })
    template.value.versions.unshift(v); adopt(v)
  })
}
async function preview() {
  await run(async () => {
    const blob = await api(`/versions/${version.value.id}/preview`, { method: 'POST', body: { document: document.value }, pdf: true })
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = URL.createObjectURL(blob); previewDialog.value.showModal()
  })
}
function add(type, variable = null, asset = null) {
  if (readOnly.value || !document.value) return
  const image = type === 'image', square = image || type === 'qr'
  const element = { id: crypto.randomUUID(), type, x: 20, y: 20, width: square ? 40 : 110, height: square ? 40 : type === 'line' ? 1 : 20,
    font_size: 14, font_style: '', color: '#173b34', align: 'L', content: type === 'text' ? t('newText') : '', variable: variable?.key ?? '', asset: asset ?? '', fill: type === 'rectangle' ? '#e4eee5' : null, hidden: false }
  if (document.value.schema_version === 2) Object.assign(element, { region: 'page', repeat: 'first' })
  document.value.elements.push(element); selectedId.value = element.id; remember()
}
function addCollectionTable() {
  if (readOnly.value || !collections.value.length || table.value) return
  document.value = createFlowDocument(document.value, collections.value[0])
  selectedId.value = document.value.flow.table.id
  newColumnField.value = ''
  remember()
}
function selectCollection(event) {
  const collection = collections.value.find(item => item.key === event.target.value)
  if (collection) { changeCollectionSource(document.value, collection); newColumnField.value = ''; remember() }
}
function rebalanceColumns() {
  table.value.columns = rebalanceTableColumns(table.value.columns, collectionFields.value, Number(table.value.width))
}
function addColumn() {
  const field = collectionFields.value.find(item => item.key === newColumnField.value)
  if (!field) return
  table.value.columns.push({ field: field.key, label: field.label, width: 1, align: ['number', 'money'].includes(field.type) ? 'R' : 'L' })
  rebalanceColumns(); newColumnField.value = ''; remember()
}
function removeColumn(index) { if (table.value.columns.length > 1) { table.value.columns.splice(index, 1); rebalanceColumns(); remember() } }
function moveColumn(index, delta) {
  const target = index + delta
  if (target < 0 || target >= table.value.columns.length) return
  ;[table.value.columns[index], table.value.columns[target]] = [table.value.columns[target], table.value.columns[index]]; remember()
}
function addTrailing(type = 'text', variable = null) {
  if (!table.value) return
  const element = addTrailingElement(document.value, type, t('newTrailingText'), variable)
  selectedId.value = element.id; remember()
}
function addPageContext() {
  if (!table.value) return
  const element = addPageNumber(document.value, t('pageNumberPattern'))
  selectedId.value = element.id; remember()
}
function remove() {
  if (selectedFixed.value) document.value.elements = document.value.elements.filter(e => e.id !== selectedId.value)
  else document.value.flow.trailing = document.value.flow.trailing.filter(e => e.id !== selectedId.value)
  selectedId.value = null; remember()
}
function duplicate() {
  const copy = { ...selected.value, id: crypto.randomUUID() }
  if (selectedFixed.value) { Object.assign(copy, moveElement(copy, 5, 5, document.value.page, snap.value)); document.value.elements.push(copy) }
  else document.value.flow.trailing.push(copy)
  selectedId.value = copy.id; remember()
}
function reorder(delta) {
  const elements = selectedFixed.value ? document.value.elements : document.value.flow.trailing
  const index = elements.findIndex(e => e.id === selectedId.value), next = index + delta
  if (next < 0 || next >= elements.length) return
  ;[elements[index], elements[next]] = [elements[next], elements[index]]; remember()
}
function keyMove(event, element) {
  const delta = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] }[event.key]
  if (readOnly.value || busy.value || !delta) return
  event.preventDefault(); Object.assign(element, moveElement(element, delta[0] * (event.shiftKey ? 10 : 1), delta[1] * (event.shiftKey ? 10 : 1), document.value.page, snap.value)); remember()
}
let drag = null
function dragStart(event, element) {
  selectedId.value = element.id
  if (readOnly.value || event.button !== 0 || busy.value) return
  event.currentTarget.setPointerCapture(event.pointerId)
  drag = { x: event.clientX, y: event.clientY, element, original: { ...element } }
}
function dragMove(event) {
  if (!drag) return
  Object.assign(drag.element, moveElement(drag.original, (event.clientX - drag.x) / scale.value, (event.clientY - drag.y) / scale.value, document.value.page, snap.value))
}
function dragEnd() { if (drag) { drag = null; remember() } }
function elementStyle(e) {
  return { left: `${e.x * scale.value}px`, top: `${e.y * scale.value}px`, width: `${e.width * scale.value}px`, height: `${e.height * scale.value}px`,
    fontSize: `${(e.font_size ?? 12) * 96 / 72 * zoom.value}px`, fontWeight: e.font_style?.includes('B') ? 700 : 400,
    fontStyle: e.font_style?.includes('I') ? 'italic' : 'normal', color: e.color, background: e.type === 'rectangle' ? e.fill : undefined,
    textAlign: { L: 'left', C: 'center', R: 'right' }[e.align], opacity: e.hidden ? 0.25 : 1 }
}
function trailingStyle(e, index) {
  const top = document.value.flow.first_top + 34 + (activeCollection.value?.example?.slice(0, 3).length ?? 0) * 12 + document.value.flow.trailing.slice(0, index).reduce((sum, item) => sum + Number(item.height) + Number(item.gap_before ?? 0), 0) + Number(e.gap_before ?? 0)
  return { ...elementStyle({ ...e, y: top }), cursor: 'pointer' }
}
function tableStyle() {
  return { left: `${table.value.x * scale.value}px`, top: `${document.value.flow.first_top * scale.value}px`, width: `${table.value.width * scale.value}px` }
}
function tableExample(field, row) { return row?.[field] ?? `{${field}}` }
function pageChanged() {
  if (!table.value) return
  resizeFlowDocument(document.value, collectionFields.value)
}
function setElementRegion(event) {
  selected.value.region = event.target.value
  const [, pageHeight] = dimensions(document.value.page)
  if (selected.value.region === 'header') { selected.value.y = Number(Math.max(0, Math.min(document.value.flow.first_top, document.value.flow.continuation_top) - selected.value.height).toFixed(1)); selected.value.repeat = 'all' }
  if (selected.value.region === 'footer') { selected.value.y = Number((pageHeight - selected.value.height - 8).toFixed(1)); selected.value.repeat = 'all' }
  if (selected.value.region === 'page') selected.value.repeat = 'first'
}
function variableExample(e) {
  const v = version.value.variables.find(v => v.key === e.variable)
  return v?.example ?? v?.default ?? `{${e.variable}}`
}
function imageUrl(e) {
  const key = e.variable ? variableExample(e) : e.asset
  return `${props.base}/api/assets?key=${encodeURIComponent(key)}`
}
async function upload(event) {
  const file = event.target.files[0]; if (!file) return
  const form = new FormData(); form.append('file', file)
  await run(async () => { const result = await api('/assets', { method: 'POST', body: form }); assets.value.push(result.key); add('image', null, result.key) })
  event.target.value = ''
}
function changeLocale(event) {
  if (!canLeave()) { event.target.value = props.locale; return }
  const url = new URL(window.location.href); url.searchParams.set('locale', event.target.value); window.location.assign(url)
}
function unload(e) { if (dirty.value) { e.preventDefault(); e.returnValue = '' } }
function showCreate() { if (!canLeave()) return; creating.value = true; createDialog.value.showModal() }
onMounted(() => { run(async () => { const [catalog, assetCatalog] = await Promise.all([api('/catalog'), api('/assets/catalog')]); types.value = catalog.types; assets.value = assetCatalog.assets; newType.value = types.value[0]?.key ?? ''; await loadList() }); window.addEventListener('beforeunload', unload) })
onBeforeUnmount(() => { window.removeEventListener('beforeunload', unload); if (previewUrl.value) URL.revokeObjectURL(previewUrl.value) })
</script>

<template>
  <div class="studio" :aria-busy="busy">
    <header class="masthead">
      <a class="brand" :href="base" @click="!canLeave() && $event.preventDefault()"><span class="brand-mark">B<span>↗</span></span>BayPdf<span class="brand-caption">{{ t('studio') }}</span></a>
      <label class="language"><span>{{ t('language') }}</span><select :value="locale" @change="changeLocale"><option value="en">EN</option><option value="de">DE</option><option value="tr">TR</option></select></label>
    </header>
    <div v-if="error" class="alert" role="alert">{{ error }} <button @click="error = ''" :aria-label="t('close')">×</button></div>
    <div v-if="notice" class="notice" role="status">{{ notice }}</div>
    <div class="workspace">
      <aside class="library">
        <div class="section-heading"><span>{{ t('libraryLabel') }}</span><span class="count">{{ templates.length }}</span></div>
        <button class="new-template" :disabled="busy || !types.length" @click="showCreate">+ {{ t('new') }}</button>
        <p v-if="busy && !templates.length" class="muted">{{ t('loading') }}</p>
        <nav :aria-label="t('library')"><button v-for="item in templates" :key="item.id" class="template-item" :class="{ active: template?.id === item.id }" :disabled="busy" @click="openTemplate(item.id)"><span class="sheet-icon">▤</span><span>{{ item.name }}<small>{{ types.find(type => type.key === item.document_type)?.label ?? item.document_type }}</small></span></button></nav>
        <button v-if="nextPage" class="quiet" :disabled="busy" @click="run(() => loadList(nextPage))">{{ t('loadMore') }}</button>
        <div v-if="version" class="toolbox">
          <div class="section-heading">{{ t('elements') }}</div>
          <div class="tools"><button v-for="[kind, icon] in [['text','T'],['qr','▦'],['line','╱'],['rectangle','□']]" :key="kind" :disabled="readOnly || busy" @click="add(kind)"><b>{{ icon }}</b>{{ t(kind) }}</button></div>
          <label class="upload-button" :class="{ disabled: readOnly || busy }">↑ {{ t('upload') }}<input type="file" accept="image/png,image/jpeg" :disabled="readOnly || busy" @change="upload"></label>
          <p class="micro">{{ t('imageHint') }}</p>
          <div v-if="assets.length" class="asset-list" :aria-label="t('assetLibrary')"><button v-for="asset in assets" :key="asset" :disabled="readOnly || busy" :title="asset" @click="add('image', null, asset)">{{ t('reuseImage') }}</button></div>
          <div v-for="(variables, group) in groups" :key="group" class="variable-group"><div class="section-heading">{{ group }}</div><button v-for="v in variables" :key="v.key" class="variable-button" :disabled="readOnly || busy" @click="add(v.type === 'image' ? 'image' : v.type === 'qr' ? 'qr' : 'variable', v)"><span class="brace">{ }</span>{{ v.label }}<span v-if="v.required" class="required">*</span></button></div>
          <details v-if="collections.length" class="advanced-tools">
            <summary>{{ t('advancedData') }}</summary>
            <button class="data-tool" :disabled="readOnly || busy || Boolean(table)" @click="addCollectionTable">+ {{ t('collectionTable') }}</button>
            <template v-if="table">
              <button class="data-tool" :disabled="readOnly || busy" @click="addPageContext">+ {{ t('pageNumber') }}</button>
              <button class="data-tool" :disabled="readOnly || busy" @click="addTrailing('text')">+ {{ t('trailingText') }}</button>
              <button v-for="v in version.variables.filter(item => item.type !== 'collection' && item.type !== 'image')" :key="`trailing-${v.key}`" class="variable-button" :disabled="readOnly || busy" @click="addTrailing(v.type === 'qr' ? 'qr' : 'variable', v)"><span class="brace">↓</span>{{ v.label }}</button>
            </template>
          </details>
        </div>
        <div class="library-footer"><span class="status-dot"></span>BayPdf <span>01 / STUDIO</span></div>
      </aside>

      <main v-if="document" class="editor">
        <div class="document-header"><div><div class="eyebrow">{{ t('design') }}</div><h1>{{ template.name }}</h1></div><div class="document-actions"><button :disabled="busy" @click="preview">{{ t('preview') }} ↗</button><button v-if="!readOnly" class="primary" :disabled="busy || !dirty" @click="run(save)">{{ busy ? t('saving') : t('save') }}</button><button v-else class="primary" :disabled="busy" @click="clone">{{ t('clone') }}</button></div></div>
        <div class="toolbar"><label><span class="sr-only">{{ t('version') }}</span><select :value="version.id" :disabled="busy" @change="chooseVersion"><option v-for="v in template.versions" :key="v.id" :value="v.id">{{ t('version') }} {{ v.number }} · {{ v.published_at ? t('published') : t('draft') }}</option></select></label><span class="save-state" :class="{ unsaved: dirty }">● {{ dirty ? t('dirty') : readOnly ? t('published') : t('saved') }}</span><div class="toolbar-end"><button :title="t('undo')" :aria-label="t('undo')" :disabled="readOnly || busy || historyIndex <= 0" @click="travel(-1)">↶</button><button :title="t('redo')" :aria-label="t('redo')" :disabled="readOnly || busy || historyIndex >= history.length - 1" @click="travel(1)">↷</button><label class="zoom"><span class="sr-only">{{ t('zoom') }}</span><select v-model="zoom"><option :value="0.5">50%</option><option :value="0.65">65%</option><option :value="0.8">80%</option><option :value="1">100%</option></select></label></div></div>
        <div v-if="readOnly" class="readonly-note">{{ t('readOnly') }}</div>
        <div class="canvas-scroll" @click.self="selectedId = null"><div class="paper-wrap"><div class="paper-label">{{ document.page.size }} <span>{{ paper[0] }} × {{ paper[1] }} mm</span></div><div class="paper" :class="{ grid: snap }" :style="{ width: `${paper[0] * scale}px`, height: `${paper[1] * scale}px`, '--grid': `${5 * scale}px` }" @click.self="selectedId = null">
          <div v-for="element in document.elements" :key="element.id" tabindex="0" role="button" :aria-label="`${t(element.type)}: ${element.variable || element.content || element.id}`" class="element" :class="[element.type, { selected: selectedId === element.id, readonly: readOnly }]" :style="elementStyle(element)" @focus="selectedId = element.id" @pointerdown="dragStart($event, element)" @pointermove="dragMove" @pointerup="dragEnd" @pointercancel="dragEnd" @keydown="keyMove($event, element)">
            <img v-if="element.type === 'image'" :src="imageUrl(element)" :alt="t('image')" draggable="false">
            <svg v-else-if="element.type === 'line'" width="100%" height="100%" aria-hidden="true"><line x1="0" y1="0" x2="100%" y2="100%" stroke="currentColor" stroke-width="1"/></svg>
            <div v-else-if="element.type === 'qr'" class="qr-placeholder"><svg viewBox="0 0 40 40" aria-hidden="true"><path fill="currentColor" d="M2 2h14v14H2zm3 3v8h8V5zM24 2h14v14H24zm3 3v8h8V5zM2 24h14v14H2zm3 3v8h8v-8zM22 22h6v6h-6zm9 0h7v4h-7zm-9 10h5v6h-5zm9-3h7v9h-4v-5h-3z" fill-rule="evenodd"/></svg><span>{{ t('qr') }}</span></div>
            <span v-else-if="element.type !== 'rectangle'">{{ element.type === 'variable' ? variableExample(element) : element.content }}</span>
            <span v-if="selectedId === element.id" class="element-tag">{{ t(element.type) }} · {{ element.x }} / {{ element.y }}</span>
          </div>
          <button v-if="table" type="button" class="flow-table" :class="{ selected: selectedId === table.id }" :style="tableStyle()" :aria-label="t('collectionTable')" @click="selectedId = table.id">
            <div class="flow-table-row flow-table-head"><span v-for="column in table.columns" :key="column.field" :style="{ width: `${column.width / table.width * 100}%`, textAlign: { L: 'left', C: 'center', R: 'right' }[column.align] }">{{ column.label }}</span></div>
            <div v-for="(row, rowIndex) in (activeCollection?.example ?? []).slice(0, 3)" :key="rowIndex" class="flow-table-row"><span v-for="column in table.columns" :key="column.field" :style="{ width: `${column.width / table.width * 100}%`, textAlign: { L: 'left', C: 'center', R: 'right' }[column.align] }">{{ tableExample(column.field, row) }}</span></div>
            <div v-if="!(activeCollection?.example ?? []).length" class="flow-table-empty">{{ t('emptyCollection') }}</div>
          </button>
          <div v-for="(element, index) in trailing" :key="element.id" tabindex="0" role="button" :aria-label="`${t('trailingContent')}: ${element.variable || element.content}`" class="element trailing-element" :class="{ selected: selectedId === element.id, readonly: readOnly }" :style="trailingStyle(element, index)" @focus="selectedId = element.id" @click="selectedId = element.id"><span>{{ element.variable ? variableExample(element) : element.content }}</span><span v-if="selectedId === element.id" class="element-tag">{{ t('trailingContent') }}</span></div>
        </div></div></div>
        <footer class="canvas-footer"><span>{{ t('hint') }}</span><label><input type="checkbox" v-model="snap">{{ t('snap') }}</label></footer>
      </main>

      <main v-else class="welcome"><div class="welcome-art" aria-hidden="true"><div class="mini-paper"><span>B.</span><i></i><i></i><i></i><b>↗</b></div></div><div class="eyebrow">BAYPDF / {{ t('studio') }}</div><h1>{{ t('empty') }}</h1><p>{{ types.length ? t('emptyHint') : t('noTypes') }}</p><button class="primary" :disabled="busy || !types.length" @click="showCreate">+ {{ t('new') }}</button></main>

      <aside v-if="document" class="inspector">
        <div class="section-heading">{{ t('properties') }} <span>↗</span></div>
        <fieldset :disabled="readOnly || busy" @change="remember">
          <template v-if="selected"><div class="selection-title">{{ t(selected.type) }}<span v-if="selectedFixed" class="micro">{{ document.elements.findIndex(e => e.id === selected.id) + 1 }} / {{ document.elements.length }}</span><span v-else class="micro">{{ t('trailingContent') }}</span></div>
            <div class="field-pair"><label>X <span>mm</span><input aria-label="X" v-model.number="selected.x" type="number" min="0" step="0.1"></label><label v-if="selectedFixed">Y <span>mm</span><input aria-label="Y" v-model.number="selected.y" type="number" min="0" step="0.1"></label><label v-else>{{ t('gapBefore') }} <span>mm</span><input :aria-label="t('gapBefore')" v-model.number="selected.gap_before" type="number" min="0" max="30" step="0.1"></label></div>
            <div class="field-pair"><label>{{ t('width') }}<input v-model.number="selected.width" type="number" min="1" step="0.1"></label><label>{{ t('height') }}<input v-model.number="selected.height" type="number" min="1" step="0.1"></label></div>
            <label v-if="['text','qr','page_number'].includes(selected.type) && !selected.variable">{{ t('content') }}<textarea v-model="selected.content" rows="4" maxlength="5000" @input="remember"></textarea></label>
            <label v-if="selected.variable">{{ t('variable') }}<select v-model="selected.variable"><option v-for="v in version.variables.filter(v => v.type !== 'collection' && (selected.type === 'image' ? v.type === 'image' : v.type !== 'image'))" :key="v.key" :value="v.key">{{ v.label }}</option></select></label>
            <template v-if="['text','variable','page_number'].includes(selected.type)"><label>{{ t('font') }} <span>pt</span><input v-model.number="selected.font_size" type="number" min="6" max="72"></label><label>{{ t('style') }}<select v-model="selected.font_style"><option value="">{{ t('regular') }}</option><option value="B">{{ t('bold') }}</option><option value="I">{{ t('italic') }}</option><option value="BI">{{ t('boldItalic') }}</option></select></label><label>{{ t('align') }}<select v-model="selected.align"><option value="L">{{ t('left') }}</option><option value="C">{{ t('center') }}</option><option value="R">{{ t('right') }}</option></select></label></template>
            <template v-if="document.schema_version === 2 && selectedFixed"><label>{{ t('pageRegion') }}<select :value="selected.region" @change="setElementRegion"><option value="page">{{ t('pageBody') }}</option><option value="header">{{ t('pageHeader') }}</option><option value="footer">{{ t('pageFooter') }}</option></select></label><label>{{ t('repeatOn') }}<select v-model="selected.repeat"><option value="first">{{ t('firstPage') }}</option><option value="all">{{ t('allPages') }}</option><option value="continuation">{{ t('continuationPages') }}</option><option value="last">{{ t('lastPage') }}</option></select></label></template>
            <label>{{ t('color') }}<input type="color" v-model="selected.color"></label>
            <template v-if="selected.type === 'rectangle'"><label>{{ t('fill') }}<input type="color" :value="selected.fill || '#ffffff'" @input="selected.fill = $event.target.value"></label><button class="quiet" @click="selected.fill = null; remember()">{{ t('transparent') }}</button></template>
            <div class="inspector-actions"><button @click="duplicate">{{ t('duplicate') }}</button><button @click="reorder(1)">{{ t('forward') }}</button><button @click="reorder(-1)">{{ t('backward') }}</button><button @click="selected.hidden = !selected.hidden; remember()">{{ selected.hidden ? t('show') : t('hide') }}</button><button class="danger" @click="remove">{{ t('remove') }}</button></div>
          </template>
          <p v-else class="muted">{{ t('select') }}</p>
          <div v-if="table" class="flow-settings"><div class="section-heading">{{ t('collectionTable') }}</div>
            <label>{{ t('collectionSource') }}<select :value="table.source" @change="selectCollection"><option v-for="collection in collections" :key="collection.key" :value="collection.key">{{ collection.label }}</option></select></label>
            <div class="field-pair"><label>{{ t('flowTop') }}<input v-model.number="document.flow.first_top" type="number" min="0" step="0.1"></label><label>{{ t('continuationTop') }}<input v-model.number="document.flow.continuation_top" type="number" min="0" step="0.1"></label></div>
            <div class="field-pair"><label>{{ t('flowBottom') }}<input v-model.number="document.flow.bottom" type="number" min="1" step="0.1"></label><label>{{ t('flowGap') }}<input v-model.number="document.flow.gap" type="number" min="0" max="30" step="0.1"></label></div>
            <label class="check-label"><input type="checkbox" v-model="table.repeat_header">{{ t('repeatTableHeader') }}</label>
            <div class="column-heading"><strong>{{ t('columns') }}</strong><span :class="{ invalid: !columnWidthsValid }">{{ columnTotal }} / {{ table.width }} mm</span></div>
            <p v-if="!columnWidthsValid" class="field-error" role="alert">{{ t('columnWidthError') }}</p>
            <div v-for="(column, index) in table.columns" :key="column.field" class="column-editor">
              <label>{{ t('field') }}<input :value="collectionFields.find(field => field.key === column.field)?.label ?? column.field" disabled></label>
              <label>{{ t('columnLabel') }}<input v-model="column.label" maxlength="120"></label>
              <div class="field-pair"><label>{{ t('width') }}<input v-model.number="column.width" type="number" min="1" step="0.1"></label><label>{{ t('align') }}<select v-model="column.align"><option value="L">{{ t('left') }}</option><option value="C">{{ t('center') }}</option><option value="R">{{ t('right') }}</option></select></label></div>
              <div class="column-actions"><button type="button" :aria-label="t('moveColumnUp')" :disabled="index === 0" @click="moveColumn(index, -1)">↑</button><button type="button" :aria-label="t('moveColumnDown')" :disabled="index === table.columns.length - 1" @click="moveColumn(index, 1)">↓</button><button type="button" :disabled="table.columns.length === 1" @click="removeColumn(index)">{{ t('remove') }}</button></div>
            </div>
            <div v-if="availableColumnFields.length" class="add-column"><label>{{ t('addColumn') }}<select v-model="newColumnField"><option value="">{{ t('chooseField') }}</option><option v-for="field in availableColumnFields" :key="field.key" :value="field.key">{{ field.label }}</option></select></label><button type="button" :disabled="!newColumnField" @click="addColumn">+</button></div>
            <details class="table-style"><summary>{{ t('tableStyle') }}</summary><label>{{ t('headerFill') }}<input type="color" v-model="table.header.fill"></label><label>{{ t('headerFont') }}<input v-model.number="table.header.font_size" type="number" min="6" max="36"></label><label>{{ t('rowFont') }}<input v-model.number="table.row.font_size" type="number" min="6" max="36"></label><label class="check-label"><input type="checkbox" v-model="table.header.border">{{ t('headerBorder') }}</label><label class="check-label"><input type="checkbox" v-model="table.row.border">{{ t('rowBorder') }}</label></details>
          </div>
          <div class="page-settings"><div class="section-heading">{{ t('page') }}</div><label>{{ t('page') }}<select v-model="document.page.size" @change="pageChanged"><option>A4</option><option>A5</option><option>Letter</option></select></label><label>{{ t('orientation') }}<select v-model="document.page.orientation" @change="pageChanged"><option value="portrait">{{ t('portrait') }}</option><option value="landscape">{{ t('landscape') }}</option></select></label><p class="micro">{{ table ? t('flowPageHint') : t('pageHint') }}</p></div>
        </fieldset>
        <div class="publish-panel"><p class="micro">{{ t('sample') }}</p><button v-if="!readOnly" class="publish-button" :disabled="busy || !hasPublishableContent || !columnWidthsValid" @click="publish">{{ t('publish') }} ↗</button><button class="quiet" :disabled="busy" @click="openTemplate(template.id)">{{ t('refresh') }}</button></div>
      </aside>
    </div>
    <dialog ref="createDialog" @close="creating = false"><form @submit.prevent="createTemplate"><div class="section-heading">BAYPDF</div><h2>{{ t('new') }}</h2><label>{{ t('name') }}<input v-model="newName" required maxlength="120" autofocus></label><label>{{ t('type') }}<select v-model="newType" required><option v-for="type in types" :key="type.key" :value="type.key">{{ type.label }}</option></select></label><p v-if="error" class="dialog-error" role="alert">{{ error }}</p><div class="dialog-actions"><button type="button" :disabled="busy" @click="createDialog.close()">{{ t('cancel') }}</button><button class="primary" :disabled="busy">{{ t('create') }}</button></div></form></dialog>
    <dialog ref="previewDialog" class="preview-dialog"><header><h2>{{ t('preview') }}</h2><div><a :href="previewUrl" download="baypdf-preview.pdf">{{ t('download') }}</a><button @click="previewDialog.close()">{{ t('close') }}</button></div></header><p class="micro">{{ t('sample') }}</p><iframe v-if="previewUrl" :src="previewUrl" :title="t('preview')"></iframe></dialog>
  </div>
</template>
