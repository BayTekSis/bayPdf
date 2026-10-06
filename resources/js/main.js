import { createApp } from 'vue'
import Designer from './Designer.vue'
import './designer.css'

const root = document.getElementById('baypdf')
createApp(Designer, {
  base: root.dataset.base,
  locale: root.dataset.locale,
  messages: JSON.parse(root.dataset.messages),
}).mount(root)
