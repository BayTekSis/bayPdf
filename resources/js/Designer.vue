<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import { dimensions, moveElement } from './layout.js'

const props = defineProps({ base: String, locale: String, messages: Object })
const t = key => props.messages[key] ?? key
const templates = ref([]), types = ref([]), template = ref(null), version = ref(null), document = ref(null)
const selectedId = ref(null), busy = ref(false), error = ref(''), notice = ref(''), creating = ref(false)
const newName = ref(''), newType = ref(''), nextPage = ref(null), zoom = ref(0.8), snap = ref(true)
const previewUrl = ref(''), savedJson = ref(''), history = ref([]), historyIndex = ref(-1)
const createDialog = ref(null), previewDialog = ref(null)
const selected = computed(() => document.value?.elements.find(e => e.id === selectedId.value))
const readOnly = computed(() => Boolean(version.value?.published_at))
const fingerprint = value => JSON.stringify(value, (_key, item) => item && typeof item === 'object' && !Array.isArray(item) ? Object.fromEntries(Object.entries(item).sort(([a], [b]) => a.localeCompare(b))) : item)
const dirty = computed(() => document.value && fingerprint(document.value) !== savedJson.value)
const paper = computed(() => document.value ? dimensions(document.value.page) : [210, 297])
const scale = computed(() => 96 / 25.4 * zoom.value)
const groups = computed(() => {
  const result = Object.create(null)
  for (const v of version.value?.variables ?? []) (result[v.group ?? t('variables')] ??= []).push(v)
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
  document.value.elements.push(element); selectedId.value = element.id; remember()
}
function remove() { document.value.elements = document.value.elements.filter(e => e.id !== selectedId.value); selectedId.value = null; remember() }
function duplicate() {
  const copy = moveElement({ ...selected.value, id: crypto.randomUUID() }, 5, 5, document.value.page, snap.value)
  document.value.elements.push(copy); selectedId.value = copy.id; remember()
}
function reorder(delta) {
  const elements = document.value.elements, index = elements.findIndex(e => e.id === selectedId.value), next = index + delta
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
  await run(async () => { const result = await api('/assets', { method: 'POST', body: form }); add('image', null, result.key) })
  event.target.value = ''
}
function changeLocale(event) {
  if (!canLeave()) { event.target.value = props.locale; return }
  const url = new URL(window.location.href); url.searchParams.set('locale', event.target.value); window.location.assign(url)
}
function unload(e) { if (dirty.value) { e.preventDefault(); e.returnValue = '' } }
function showCreate() { if (!canLeave()) return; creating.value = true; createDialog.value.showModal() }
onMounted(() => { run(async () => { types.value = (await api('/catalog')).types; newType.value = types.value[0]?.key ?? ''; await loadList() }); window.addEventListener('beforeunload', unload) })
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
          <div v-for="(variables, group) in groups" :key="group" class="variable-group"><div class="section-heading">{{ group }}</div><button v-for="v in variables" :key="v.key" class="variable-button" :disabled="readOnly || busy" @click="add(v.type === 'image' ? 'image' : v.type === 'qr' ? 'qr' : 'variable', v)"><span class="brace">{ }</span>{{ v.label }}<span v-if="v.required" class="required">*</span></button></div>
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
        </div></div></div>
        <footer class="canvas-footer"><span>{{ t('hint') }}</span><label><input type="checkbox" v-model="snap">{{ t('snap') }}</label></footer>
      </main>

      <main v-else class="welcome"><div class="welcome-art" aria-hidden="true"><div class="mini-paper"><span>B.</span><i></i><i></i><i></i><b>↗</b></div></div><div class="eyebrow">BAYPDF / {{ t('studio') }}</div><h1>{{ t('empty') }}</h1><p>{{ types.length ? t('emptyHint') : t('noTypes') }}</p><button class="primary" :disabled="busy || !types.length" @click="showCreate">+ {{ t('new') }}</button></main>

      <aside v-if="document" class="inspector">
        <div class="section-heading">{{ t('properties') }} <span>↗</span></div>
        <fieldset :disabled="readOnly || busy" @change="remember">
          <template v-if="selected"><div class="selection-title">{{ t(selected.type) }}<span class="micro">{{ document.elements.findIndex(e => e.id === selected.id) + 1 }} / {{ document.elements.length }}</span></div>
            <div class="field-pair"><label>X <span>mm</span><input aria-label="X" v-model.number="selected.x" type="number" min="0" step="0.1"></label><label>Y <span>mm</span><input aria-label="Y" v-model.number="selected.y" type="number" min="0" step="0.1"></label></div>
            <div class="field-pair"><label>{{ t('width') }}<input v-model.number="selected.width" type="number" min="1" step="0.1"></label><label>{{ t('height') }}<input v-model.number="selected.height" type="number" min="1" step="0.1"></label></div>
            <label v-if="['text','qr'].includes(selected.type) && !selected.variable">{{ t('content') }}<textarea v-model="selected.content" rows="4" maxlength="5000" @input="remember"></textarea></label>
            <label v-if="selected.variable">{{ t('variable') }}<select v-model="selected.variable"><option v-for="v in version.variables.filter(v => selected.type === 'image' ? v.type === 'image' : v.type !== 'image')" :key="v.key" :value="v.key">{{ v.label }}</option></select></label>
            <template v-if="['text','variable'].includes(selected.type)"><label>{{ t('font') }} <span>pt</span><input v-model.number="selected.font_size" type="number" min="6" max="72"></label><label>{{ t('style') }}<select v-model="selected.font_style"><option value="">{{ t('regular') }}</option><option value="B">{{ t('bold') }}</option><option value="I">{{ t('italic') }}</option><option value="BI">{{ t('boldItalic') }}</option></select></label><label>{{ t('align') }}<select v-model="selected.align"><option value="L">{{ t('left') }}</option><option value="C">{{ t('center') }}</option><option value="R">{{ t('right') }}</option></select></label></template>
            <label>{{ t('color') }}<input type="color" v-model="selected.color"></label>
            <template v-if="selected.type === 'rectangle'"><label>{{ t('fill') }}<input type="color" :value="selected.fill || '#ffffff'" @input="selected.fill = $event.target.value"></label><button class="quiet" @click="selected.fill = null; remember()">{{ t('transparent') }}</button></template>
            <div class="inspector-actions"><button @click="duplicate">{{ t('duplicate') }}</button><button @click="reorder(1)">{{ t('forward') }}</button><button @click="reorder(-1)">{{ t('backward') }}</button><button @click="selected.hidden = !selected.hidden; remember()">{{ selected.hidden ? t('show') : t('hide') }}</button><button class="danger" @click="remove">{{ t('remove') }}</button></div>
          </template>
          <p v-else class="muted">{{ t('select') }}</p>
          <div class="page-settings"><div class="section-heading">{{ t('page') }}</div><label>{{ t('page') }}<select v-model="document.page.size"><option>A4</option><option>A5</option><option>Letter</option></select></label><label>{{ t('orientation') }}<select v-model="document.page.orientation"><option value="portrait">{{ t('portrait') }}</option><option value="landscape">{{ t('landscape') }}</option></select></label><p class="micro">{{ t('pageHint') }}</p></div>
        </fieldset>
        <div class="publish-panel"><p class="micro">{{ t('sample') }}</p><button v-if="!readOnly" class="publish-button" :disabled="busy || !document.elements.length" @click="publish">{{ t('publish') }} ↗</button><button class="quiet" :disabled="busy" @click="openTemplate(template.id)">{{ t('refresh') }}</button></div>
      </aside>
    </div>
    <dialog ref="createDialog" @close="creating = false"><form @submit.prevent="createTemplate"><div class="section-heading">BAYPDF</div><h2>{{ t('new') }}</h2><label>{{ t('name') }}<input v-model="newName" required maxlength="120" autofocus></label><label>{{ t('type') }}<select v-model="newType" required><option v-for="type in types" :key="type.key" :value="type.key">{{ type.label }}</option></select></label><p v-if="error" class="dialog-error" role="alert">{{ error }}</p><div class="dialog-actions"><button type="button" :disabled="busy" @click="createDialog.close()">{{ t('cancel') }}</button><button class="primary" :disabled="busy">{{ t('create') }}</button></div></form></dialog>
    <dialog ref="previewDialog" class="preview-dialog"><header><h2>{{ t('preview') }}</h2><div><a :href="previewUrl" download="baypdf-preview.pdf">{{ t('download') }}</a><button @click="previewDialog.close()">{{ t('close') }}</button></div></header><p class="micro">{{ t('sample') }}</p><iframe v-if="previewUrl" :src="previewUrl" :title="t('preview')"></iframe></dialog>
  </div>
</template>
