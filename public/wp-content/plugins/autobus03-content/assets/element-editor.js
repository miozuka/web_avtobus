(() => {
  'use strict';
  const config = window.agmVisualEditor, api = window.agmSiteEditor;
  if (!config || !api) return;
  const fields = config.fields || {}, bindings = new WeakMap();
  const normalize = value => String(value || '').replace(/\s+/g, ' ').trim();
  const bind = (element, key) => {
    if (!element || !fields[key]) return;
    const keys = bindings.get(element) || new Set(); keys.add(key); bindings.set(element, keys);
  };
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_COMMENT);
  while (walker.nextNode()) {
    const match = walker.currentNode.nodeValue.match(/^agm-field:([a-z0-9_]+)$/);
    if (match) bind(walker.currentNode.parentElement, match[1]);
  }
  document.querySelectorAll('[data-agm-field]').forEach(el => bind(el, el.dataset.agmField));
  document.querySelectorAll('.brand-name').forEach(el => bind(el, 'site_name'));
  document.querySelectorAll('.custom-logo').forEach(el => bind(el, 'custom_logo'));
  document.querySelectorAll('.site-header .brand').forEach(el => bind(el, 'custom_logo'));
  document.querySelectorAll('.timetable-day').forEach(el => bind(el, 'tt_' + el.dataset.day + '_label'));
  const textIndex = new Map(), urlIndex = new Map();
  for (const [key, field] of Object.entries(fields)) {
    const value = normalize(field.value);
    if (!value) continue;
    if (['textarea', 'site_name', 'site_description'].includes(field.type)) {
      const keys = textIndex.get(value) || []; keys.push(key); textIndex.set(value, keys);
    }
    if (field.type === 'url') {
      try { const url = new URL(value, location.href).href; const keys = urlIndex.get(url) || []; keys.push(key); urlIndex.set(url, keys); } catch { /* Некорректная старая ссылка не становится правилом выбора. */ }
    }
  }
  const tools = document.getElementById('agm-edit-tools');
  const selectButton = document.createElement('button');
  selectButton.type = 'button'; selectButton.id = 'agm-element-select';
  selectButton.setAttribute('aria-pressed', 'false'); selectButton.hidden = true;
  selectButton.textContent = '⌖ Выбрать элемент'; document.body.append(selectButton);
  const help = document.createElement('details'); help.className = 'agm-element-help';
  const helpTitle = document.createElement('summary'); helpTitle.textContent = 'Как выбрать элемент?';
  const helpText = document.createElement('p');
  helpText.textContent = 'Нажмите «Выбрать элемент», затем нужный текст, кнопку, фото, пункт меню или время отправления. Откроется именно его поле.';
  help.append(helpTitle, helpText);
  tools.insertBefore(help, tools.querySelector('label'));
  const inspector = document.createElement('section'); inspector.id = 'agm-element-inspector'; inspector.hidden = true;
  inspector.innerHTML = '<h3>Выбранный элемент</h3><p id="agm-element-caption"></p><div id="agm-element-choices"></div><button type="button" id="agm-cookie-preview">Показать cookie-баннер</button>';
  tools.insertBefore(inspector, tools.querySelector('label'));
  const previewButton = document.createElement('button'); previewButton.type = 'button'; previewButton.className = 'agm-cookie-preview-task';
  previewButton.textContent = 'Показать cookie-баннер для редактирования';
  if (Object.keys(fields).length) tools.append(previewButton);
  const outline = document.createElement('div'); outline.id = 'agm-element-outline'; outline.hidden = true; document.body.append(outline);
  let picking = false, lastElement = null, requested = null, lastMode = null;
  let cookieWasHidden = null;
  const skip = target => target.closest('#wpadminbar,#agm-edit-tools,#agm-edit-dialog,#agm-edit-toggle,#agm-edit-tasks-toggle,#agm-element-select,#agm-edit-overlays,.media-modal');
  function setPicking(value) {
    picking = !!value && api.active();
    document.body.classList.toggle('agm-selecting-element', picking);
    selectButton.setAttribute('aria-pressed', String(picking));
    selectButton.textContent = picking ? '⌖ Нажмите элемент · Отменить' : '⌖ Выбрать элемент';
    outline.hidden = true;
  }
  function syncMode() {
    if (lastMode === api.active()) return;
    lastMode = api.active();
    selectButton.hidden = !api.active();
    if (!api.active()) {
      setPicking(false);
      document.body.classList.remove('agm-cookie-preview');
      if (cookieWasHidden !== null) { const notice = document.querySelector('.cookie-notice'); if (notice) notice.hidden = cookieWasHidden; cookieWasHidden = null; }
    }
  }
  new MutationObserver(syncMode).observe(document.body, { attributes: true, attributeFilter: ['class'] });
  selectButton.addEventListener('click', () => setPicking(!picking));
  addEventListener('keydown', event => { if (event.key === 'Escape' && picking) { event.preventDefault(); setPicking(false); } });
  function settingsChoice(keys) {
    const valid = [...new Set(keys)].filter(key => fields[key]);
    if (!valid.length) return null;
    const primary = fields[valid[0]];
    const related = valid.filter(key => fields[key].section === primary.section).slice(0, 5);
    return { label: related.length > 1 ? 'Текст и ссылка / настройки элемента' : (primary.section === 'interface' ? 'Общая подпись: ' : '') + primary.label,
      url: config.settings + encodeURIComponent(primary.section) + '&agm_fields=' + encodeURIComponent(related.join(',')), kind: 'field' };
  }
  function recordChoice(id, element, extra = {}) {
    const record = config.records[id]; if (!record) return null;
    const url = new URL(record.url);
    if (extra.direction) { url.searchParams.set('agm_direction', extra.direction); url.searchParams.set('agm_day', extra.day); }
    else if (element.closest('img,figure')) url.searchParams.set('agm_record_focus', 'image');
    else if (element.closest('h1,h2,h3,h4')) url.searchParams.set('agm_record_focus', 'title');
    else if (record.type === 'page') url.searchParams.set('agm_match', normalize(element.textContent).slice(0, 250));
    else {
      url.searchParams.set('agm_record_focus', element.closest('.icon-box') ? 'icon' : element.closest('.count-pill') ? 'count' : 'description');
      url.searchParams.set('agm_match', normalize(element.textContent).slice(0, 250));
    }
    return { label: extra.direction ? 'Отправления: выбранное направление и день' : 'Карточка: ' + record.label, url: url.href, kind: 'record' };
  }
  function choicesFor(element) {
    if (element.closest('.fleet-number strong') && config.tasks.some(task=>task.url===config.admin+'edit.php?post_type=agm_fleet')) return [{label:'Количество автобусов: изменить карточки автопарка',url:config.admin+'edit.php?post_type=agm_fleet'}];
    if (element.closest('.route-group-count') && config.tasks.some(task=>task.url===config.admin+'edit.php?post_type=agm_route')) return [{label:'Число маршрутов: открыть список маршрутов',url:config.admin+'edit.php?post_type=agm_route'}];
    const menu = element.closest('[id^="menu-item-"]');
    const menuItem = menu && config.menuItems?.[menu.id.replace('menu-item-', '')];
    if (menuItem) return [{label: 'Пункт меню: название и ссылка', url: menuItem.url, kind: 'menu'}];
    const panel = element.closest('.timetable-panel');
    if (panel && element.closest('time')) return [recordChoice(config.record, element, {direction: panel.dataset.direction, day: panel.dataset.day})].filter(Boolean);
    let keys = [];
    for (let node = element; node && node !== document.body; node = node.parentElement) {
      if (bindings.has(node)) { keys = [...bindings.get(node)]; break; }
    }
    if (!keys.length) {
      let node = element;
      for (let i = 0; node && i < 3; i++, node = node.parentElement) {
        if (normalize(node.textContent).length > 500) break;
        const found = textIndex.get(normalize(node.textContent));
        if (found) { keys = found; break; }
      }
    }
    if (!keys.length && element.closest('.hero') && fields.hero_image) keys = ['hero_image'];
    const link = element.closest('a[href]');
    const linkKeys = link ? urlIndex.get(link.href) || [] : [];
    const results = [];
    if (keys.length) {
      const section = fields[keys[0]].section;
      const related = linkKeys.filter(key => fields[key].section === section);
      const primary = settingsChoice([...keys.filter(key => fields[key].section === section), ...related]);
      if (primary) results.push(primary);
    }
    for (const key of linkKeys) {
      if (!keys.some(k => fields[k].section === fields[key].section)) results.push(settingsChoice([key]));
    }
    const recordElement = element.closest('[data-agm-record]');
    const record = recordChoice(recordElement?.dataset.agmRecord || (element.closest('main') ? config.record : 0), element);
    if (record && (!results.length || recordElement)) results.push(record);
    // Область карты выбирается снаружи iframe: содержимое стороннего сервиса не редактируется.
    const map = element.closest('.contact-map-card,.vacancy-map-card');
    if (map && fields.cp_map_embed && document.querySelector('.contact-page')) results.unshift(settingsChoice(['cp_map_embed','cp_map_url','cp_map_2gis_url']));
    if (map && fields.vp_map_embed && document.querySelector('.vacancy-page')) results.unshift(settingsChoice(['vp_map_embed','vp_map_url']));
    return results.filter(Boolean).filter((item,index,all) => all.findIndex(other => other.url === item.url) === index).slice(0, 6);
  }
  function showChoices(element) {
    const choices = choicesFor(element);
    setPicking(false);
    if (choices.length === 1) { api.open(choices[0].label, choices[0].url); return; }
    inspector.hidden = false; tools.hidden = false;
    document.getElementById('agm-edit-tasks-toggle').setAttribute('aria-expanded','true');
    document.getElementById('agm-element-caption').textContent = normalize(element.textContent || element.getAttribute('alt') || element.getAttribute('aria-label')).slice(0, 150) || 'Изображение или элемент интерфейса';
    const options = document.getElementById('agm-element-choices'); options.replaceChildren();
    if (!choices.length) {
      const text = document.createElement('p'); text.textContent = 'У этого элемента нет отдельного текстового поля. Выберите задачу ниже: карточку, страницу, меню или настройки раздела.'; options.append(text);
    }
    for (const choice of choices) {
      const button = document.createElement('button'); button.type = 'button'; button.textContent = choice.label;
      button.addEventListener('click', () => api.open(choice.label, choice.url)); options.append(button);
    }
    inspector.scrollIntoView({block:'nearest'});
    options.querySelector('button')?.focus();
  }
  document.addEventListener('click', event => {
    if (!picking || skip(event.target)) return;
    event.preventDefault(); event.stopImmediatePropagation(); showChoices(event.target);
  }, true);
  document.addEventListener('pointermove', event => {
    if (!picking || skip(event.target)) { outline.hidden = true; return; }
    const element = event.target.closest('a,button,img,h1,h2,h3,h4,p,strong,span,label,select,time,li,figure,iframe') || event.target;
    if (element === lastElement && !outline.hidden) return;
    lastElement = element; const rect = element.getBoundingClientRect();
    Object.assign(outline.style, {left:rect.left+'px', top:rect.top+'px', width:rect.width+'px', height:rect.height+'px'}); outline.hidden = false;
  }, {passive:true});
  addEventListener('scroll', () => { outline.hidden = true; lastElement = null; }, true);
  function previewCookie() {
    const notice = document.querySelector('.cookie-notice'); if (!notice) return;
    if (cookieWasHidden === null) cookieWasHidden = notice.hidden;
    document.body.classList.add('agm-cookie-preview'); notice.hidden = false; tools.hidden = true; setPicking(true);
  }
  previewButton.addEventListener('click', previewCookie);
  document.getElementById('agm-cookie-preview').addEventListener('click', previewCookie);
  document.getElementById('agm-cookie-preview').hidden = !Object.keys(fields).length;
  document.addEventListener('agm-editor-open', event => { requested = new URL(event.detail.url); setPicking(false); });
  document.getElementById('agm-edit-frame').addEventListener('load', () => {
    if (!requested) return;
    const frame = document.getElementById('agm-edit-frame');
    let doc, win; try { win = frame.contentWindow; doc = frame.contentDocument; if (!doc?.body || win.location.origin !== location.origin) return; } catch { return; }
    const css = doc.createElement('link'); css.rel = 'stylesheet'; css.href = new URL('element-editor.css', config.css).href; doc.head.append(css);
    let target = null;
    const keys = (requested.searchParams.get('agm_fields') || '').split(',').filter(key => /^[a-z0-9_]+$/.test(key));
    if (win.location.search.includes('page=agm-site-content') && keys.length) {
      const selected = keys.map(key => doc.getElementById('agm-setting-' + key)?.closest('.agm-setting-field')).filter(Boolean);
      if (selected.length) {
        doc.body.classList.add('agm-focus-fields'); selected.forEach(row => row.classList.add('agm-selected-field'));
        target = selected[0].querySelector('textarea,input:not([type=hidden]),select,button');
        const panel = doc.querySelector('.agm-admin-panel');
        if (panel) {
          const showAll = doc.createElement('button'); showAll.type = 'button'; showAll.className = 'button agm-show-all-fields'; showAll.textContent = 'Показать все поля раздела';
          showAll.addEventListener('click', () => { doc.body.classList.remove('agm-focus-fields'); showAll.remove(); }); panel.insertBefore(showAll, panel.querySelector('form'));
        }
      }
    }
    const direction = requested.searchParams.get('agm_direction'), day = requested.searchParams.get('agm_day');
    if (/^\d+$/.test(direction || '') && /^[a-z_]+$/.test(day || '')) {
      const id = [...doc.querySelectorAll('#agm-direction-list input[type=hidden]')].find(input => input.name.endsWith('[id]') && input.value === direction);
      target = id?.closest('fieldset')?.querySelector('textarea[name$="[' + day + ']"]');
    }
    const menuId = requested.searchParams.get('agm_menu_item');
    if (/^\d+$/.test(menuId || '')) {
      const item = doc.getElementById('menu-item-' + menuId);
      item?.classList.add('menu-item-edit-active'); item?.classList.remove('menu-item-edit-inactive'); target = item?.querySelector('.edit-menu-item-title');
    }
    const recordFocus = requested.searchParams.get('agm_record_focus');
    if (recordFocus) {
      if (recordFocus === 'title') target = doc.querySelector('input#title,.editor-post-title__input,[aria-label="Добавить заголовок"]');
      if (recordFocus === 'description') target = doc.querySelector('#agm_job_excerpt,#excerpt:not([hidden]),#wp-content-wrap,#content');
      if (recordFocus === 'image') target = doc.querySelector('#postimagediv .inside a,#agm-setting-hero_image,#agm_file');
      if (recordFocus === 'icon') target = doc.querySelector('#agm_icon');
      if (recordFocus === 'count') target = doc.querySelector('#agm_count');
    }
    const text = requested.searchParams.get('agm_match');
    if (text) {
      const match = [...doc.querySelectorAll('input:not([type=hidden]),textarea')].find(input => normalize(input.value) === normalize(text));
      if (match) target = match;
    }
    if (target) {
      for (let parent = target.parentElement; parent; parent = parent.parentElement) { if (parent.tagName === 'DETAILS') parent.open = true; if (parent.classList.contains('postbox')) parent.classList.remove('closed'); }
      target.classList.add('agm-chosen-input');
      setTimeout(() => { target.scrollIntoView({block:'center'}); if (target.type !== 'hidden') target.focus({preventScroll:true}); }, 180);
    }
  });
  syncMode();
})();
