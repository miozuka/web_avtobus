(() => {
  'use strict';
  const config = window.agmVisualEditor;
  const toggle = document.getElementById('agm-edit-toggle');
  if (!config || !toggle) return;
  const tools = document.getElementById('agm-edit-tools');
  const tasksToggle = document.getElementById('agm-edit-tasks-toggle');
  const dialog = document.getElementById('agm-edit-dialog');
  const frame = document.getElementById('agm-edit-frame');
  const status = document.getElementById('agm-edit-status');
  const list = document.getElementById('agm-edit-task-list');
  const saveButton = document.querySelector('[data-agm-save]');
  let submitButton = null;
  const normal = value => String(value || '').toLocaleLowerCase('ru').replaceAll('ё','е').replace(/\s+/g,' ').trim();
  const overlays = document.createElement('div');
  overlays.id = 'agm-edit-overlays';
  document.body.append(overlays);
  let active = false, dirty = false, changed = false, pending = false, previousFocus = null;
  let frameObserver = null, frameTimer = null, drawing = false;
  const storage = { get() { try { return sessionStorage.getItem('agm-visual-mode') === 'on'; } catch { return false; } }, set(value) { try { sessionStorage.setItem('agm-visual-mode', value ? 'on' : 'off'); } catch { /* Закрытый режим браузера: редактирование работает без запоминания. */ } } };
  const safeUrl = (value) => {
    try {
      const url = new URL(value, location.href), admin = new URL(config.admin);
      return url.origin === admin.origin && url.pathname.startsWith(admin.pathname) ? url.href : null;
    } catch { return null; }
  };
  const needsConfirmation = () => {
    try { return dirty || !!frame.contentWindow.wp?.data?.select('core/editor')?.isEditedPostDirty() || !!frame.contentWindow.tinymce?.editors?.some(editor => editor.isDirty()); }
    catch { return dirty; }
  };
  function resetFrameTracking() {
    if (frameObserver) frameObserver.disconnect();
    clearInterval(frameTimer);
    frameObserver = null;
  }
  function openEditor(label, value) {
    const url = safeUrl(value);
    if (!url || (dialog.open && needsConfirmation() && !confirm('Есть несохранённые изменения. Перейти к другой форме?'))) return;
    previousFocus = document.activeElement;
    dirty = false;
    pending = false;
    resetFrameTracking();
    document.getElementById('agm-edit-dialog-title').textContent = label;
    document.getElementById('agm-edit-full').href = url;
    status.textContent = 'Загружаем форму…';
    saveButton.hidden = true; submitButton = null;
    frame.src = url;
    document.dispatchEvent(new CustomEvent('agm-editor-open', {detail: {url, label}}));
    if (!dialog.open) dialog.showModal();
    document.documentElement.classList.add('agm-dialog-open');
  }
  function closeEditor() {
    if (needsConfirmation() && !confirm('Изменения ещё не сохранены. Закрыть форму без сохранения?')) return;
    dirty = false;
    resetFrameTracking();
    dialog.close();
    frame.src = 'about:blank';
    document.documentElement.classList.remove('agm-dialog-open');
    if (changed) location.reload();
    else previousFocus?.focus();
  }
  function setActive(value) {
    active = value;
    document.body.classList.toggle('agm-editing', value);
    tools.hidden = !value || innerWidth < 781;
    tasksToggle.hidden = !value;
    tasksToggle.setAttribute('aria-expanded', String(!tools.hidden));
    toggle.setAttribute('aria-pressed', String(value));
    toggle.textContent = value ? '✓ Завершить' : '✎ Редактировать сайт';
    storage.set(value);
    draw();
  }
  // Связь блоков с уже существующими формами. Произвольный HTML на сервер не отправляется.
  const sections = [
    ['.site-header', 'identity', 'Шапка: название и логотип'],
    ['.site-footer', 'contacts', 'Подвал: контакты и ссылки'],
    ['.hero', 'hero', 'Главный баннер'], ['.schedule-section', 'schedule', 'Блок расписания'],
    ['.fleet-section', 'fleet', 'Заголовки автопарка'], ['.quote-section', 'quote', 'Слово мэра'],
    ['.services-section', 'services', 'Заголовки услуг'], ['.news-section', 'services', 'Заголовки новостей'],
    ['.faq-section', 'faq', 'Вопросы и предприятие'], ['.information-section', 'info', 'Полезные сведения'],
    ['.feedback-section', 'feedback', 'Блок Госуслуг'],
    ['.services-hero,.passenger-tariffs,.enterprise-services,.services-document', 'services_page', 'Тексты страницы услуг'],
    ['.route-hero,.route-section', 'schedule_page', 'Тексты расписания'],
    ['.timetable-header,.timetable-graph', 'timetable_page', 'Подписи графика'],
    ['.news-page-hero,.news-subscribe', 'news_page', 'Тексты страницы новостей'],
    ['.about-hero,.about-history,.about-archive,.about-documents,.about-contacts', 'about_page', 'Тексты о предприятии'],
    ['.about-leadership', 'quote', 'Фото и слово мэра'],
    ['.vacancy-hero,.vacancy-opportunities,.vacancy-benefits,.vacancy-map-section', 'vacancy_page', 'Тексты страницы вакансий'],
    ['.contact-hero,.contact-page > section', 'contact_page', 'Тексты страницы контактов'],
  ];
  const targets = [];
  const addTarget = (element, label, url, kind) => {
    if (!safeUrl(url)) return;
    element.classList.add('agm-edit-target');
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'agm-block-edit ' + (kind === 'record' ? 'agm-record-edit' : '');
    button.textContent = kind === 'record' ? '✎ Карточка' : '✎ ' + label;
    button.title = 'Изменить: ' + label;
    button.setAttribute('aria-label', 'Изменить: ' + label);
    button.addEventListener('click', () => openEditor(label, url));
    overlays.append(button);
    targets.push({ element, button, kind });
  };
  const seen = new Set();
  if (config.settings) sections.forEach(([selector, key, label]) => {
    document.querySelectorAll(selector).forEach(element => {
      if (seen.has(element)) return;
      seen.add(element);
      addTarget(element, label, config.settings + encodeURIComponent(key), 'section');
    });
  });
  document.querySelectorAll('[data-agm-record]').forEach(element => {
    const record = config.records[element.dataset.agmRecord];
    if (record) addTarget(element, record.label, record.url, 'record');
  });
  if (config.record && config.records[config.record]) {
    const element = document.querySelector('.timetable-header,.news-article,.entry-content,.passenger-page');
    const record = config.records[config.record];
    if (element && !element.hasAttribute('data-agm-record')) addTarget(element, record.label, record.url, 'record');
  }
  function draw() {
    drawing = false;
    const notice = document.querySelector('.cookie-notice:not([hidden])');
    toggle.style.bottom = notice && getComputedStyle(notice).display !== 'none' ? Math.max(18, innerHeight - notice.getBoundingClientRect().top + 12) + 'px' : '';
    targets.forEach(({element, button, kind}) => {
      const rect = element.getBoundingClientRect();
      const visible = active && !dialog.open && element.getClientRects().length && rect.bottom > 64 && rect.top < innerHeight - 60 && rect.right > 0 && rect.left < innerWidth;
      button.hidden = !visible;
      if (!visible) return;
      // Кнопка остаётся у своего блока, не перекрывает нижнюю панель и не меняет вёрстку.
      const width = Math.min(kind === 'record' ? 112 : 230, innerWidth - 24);
      button.style.maxWidth = width + 'px';
      const x = kind === 'record' ? rect.right - width - 10 : rect.left + 10;
      button.style.left = Math.max(12, Math.min(innerWidth - width - 12, x)) + 'px';
      button.style.top = Math.max(40, rect.top + (kind === 'record' && seen.has(element) ? 48 : 10)) + 'px';
    });
  }
  function scheduleDraw() {
    if (!drawing) { drawing = true; requestAnimationFrame(draw); }
  }
  addEventListener('scroll', scheduleDraw, true);
  addEventListener('resize', scheduleDraw);
  document.addEventListener('click', scheduleDraw);
  if ('ResizeObserver' in window) new ResizeObserver(scheduleDraw).observe(document.body);
  const cookieNotice = document.querySelector('.cookie-notice');
  if (cookieNotice) new MutationObserver(scheduleDraw).observe(cookieNotice, { attributes: true, attributeFilter: ['hidden', 'style', 'class'] });
  let taskScope = config.context ? 'page' : 'all';
  const search = document.getElementById('agm-edit-search');
  const isCurrentTask = task => {
    if (!config.context) return true;
    if (task.group === 'Эта страница') return true;
    const url = new URL(task.url);
    const section = url.searchParams.get('section');
    if (section) return (config.pages?.[config.context]?.[2] || []).includes(section);
    if (url.searchParams.get('page') === 'agm-home-layout') return config.context === 'home';
    const type = url.searchParams.get('post_type') || 'post';
    return /\/(edit|post-new)\.php$/.test(url.pathname) && (config.types || []).includes(type);
  };
  function renderTasks() {
    const words = normal(search.value).split(' ').filter(Boolean);
    const matches = task => words.every(word => normal(task.label + ' ' + task.group).includes(word));
    let tasks = config.tasks.filter(task => (taskScope === 'all' || isCurrentTask(task)) && matches(task));
    if (words.length) {
      for (const [id, record] of Object.entries(config.records)) {
        if (record.type === 'page' || (taskScope === 'page' && !(config.types || []).includes(record.type))) continue;
        const task = {label:record.label, url:record.url, group:'Найденные материалы'};
        if (matches(task)) tasks.push(task);
      }
      for (const [key, field] of Object.entries(config.fields || {})) {
        if (taskScope === 'page' && !(config.pages?.[config.context]?.[2] || []).includes(field.section)) continue;
        const task = {label:field.label, group:'Поля настроек', url:config.settings+encodeURIComponent(field.section)+'&agm_fields='+encodeURIComponent(key)};
        if (matches(task)) tasks.push(task);
      }
    }
    list.replaceChildren();
    let lastGroup = '';
    for (const task of tasks.slice(0, 40)) {
      if (!safeUrl(task.url)) continue;
      const row = document.createElement('div'); row.className = 'agm-task-row';
      if (task.group !== lastGroup) { const title = document.createElement('h3'); title.textContent = task.group; row.append(title); lastGroup = task.group; }
      const button = document.createElement('button'); button.type = 'button'; button.textContent = task.label;
      button.addEventListener('click', () => openEditor(task.label, task.url)); row.append(button); list.append(row);
    }
    document.getElementById('agm-edit-empty').hidden = tasks.length > 0;
    document.getElementById('agm-edit-result-count').textContent = tasks.length > 40 ? 'Найдено: '+tasks.length+'. Уточните запрос — показаны первые 40.' : 'Доступно задач: '+tasks.length;
    document.querySelectorAll('[data-agm-scope]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.agmScope === taskScope)));
  }
  document.querySelectorAll('[data-agm-scope]').forEach(button => button.addEventListener('click', () => { taskScope = button.dataset.agmScope; renderTasks(); }));
  if (!config.context) document.querySelector('[data-agm-scope="page"]').hidden = true;
  search.addEventListener('input', renderTasks);
  renderTasks();
  saveButton.addEventListener('click', () => {
    if (!submitButton || submitButton.disabled || !submitButton.isConnected) return;
    // Нажимаем штатную кнопку формы: её проверка полей, права и способ публикации сохраняются.
    submitButton.click();
  });
  toggle.addEventListener('click', () => setActive(!active));
  tasksToggle.addEventListener('click', () => { tools.hidden = !tools.hidden; tasksToggle.setAttribute('aria-expanded', String(!tools.hidden)); });
  document.querySelector('[data-agm-collapse]').addEventListener('click', () => { tools.hidden = true; tasksToggle.setAttribute('aria-expanded', 'false'); tasksToggle.focus(); });
  document.getElementById('wp-admin-bar-agm-visual-edit')?.addEventListener('click', event => { event.preventDefault(); setActive(!active); });
  document.querySelector('[data-agm-close]').addEventListener('click', closeEditor);
  dialog.addEventListener('cancel', event => { event.preventDefault(); closeEditor(); });
  dialog.addEventListener('close', scheduleDraw);
  document.querySelector('[data-agm-refresh]').addEventListener('click', () => {
    if (!needsConfirmation() || confirm('Остались несохранённые изменения. Обновить страницу без их сохранения?')) { dirty = false; location.reload(); }
  });
  frame.addEventListener('load', () => {
    resetFrameTracking();
    if (frame.src === 'about:blank' || !dialog.open) return;
    let doc, win;
    try { win = frame.contentWindow; doc = frame.contentDocument; if (!safeUrl(win.location.href)) throw new Error(); }
    catch { status.textContent = 'Для этой страницы используйте отдельную вкладку.'; return; }
    const css = doc.createElement('link'); css.rel = 'stylesheet'; css.href = config.css; doc.head.append(css);
    doc.body.classList.add('agm-embedded-editor');
    const submitted = pending; pending = false;
    dirty = submitted && !!doc.querySelector('.notice-error,#message.error');
    const success = () => {
      if (submitted && doc.querySelector('.notice-success,#message.updated')) {
        changed = true; dirty = false; status.textContent = 'Сохранено. Закройте форму, чтобы увидеть результат.';
      }
    };
    status.textContent = 'Измените поля и нажмите кнопку сохранения в форме.';
    success();
    if (dirty) status.textContent = 'Не удалось сохранить. Проверьте сообщение и поля в форме.';
    const bindSave = () => {
      submitButton = doc.querySelector('form[data-unsaved-form] .button-primary[type=submit], form[data-unsaved-form] input.button-primary, form#post #publish, #save_menu_header');
      saveButton.hidden = !submitButton;
      if (submitButton) { saveButton.textContent = submitButton.value || submitButton.textContent || 'Сохранить изменения'; saveButton.disabled = submitButton.disabled; }
    };
    bindSave();
    const trackInput = event => {
      if (!event.target.closest('form[data-unsaved-form],form#post,form#update-nav-menu')) return;
      dirty = true; status.textContent = 'Есть несохранённые изменения.';
    };
    doc.addEventListener('input', trackInput);
    doc.addEventListener('change', trackInput);
    doc.addEventListener('submit', event => {
      queueMicrotask(() => { if (!event.defaultPrevented) { pending = true; dirty = false; saveButton.disabled = true; status.textContent = 'Сохраняем…'; } });
    });
    frameObserver = new MutationObserver(bindSave);
    frameObserver.observe(doc.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled'] });
    let wasSaving = false;
    frameTimer = setInterval(() => {
      try {
        const editor = win.wp?.data?.select('core/editor');
        if (!editor) return;
        const saving = editor.isSavingPost();
        if (wasSaving && !saving && editor.didPostSaveRequestSucceed()) {
          changed = true; dirty = false; status.textContent = 'Сохранено. Закройте форму, чтобы увидеть результат.';
        }
        wasSaving = saving;
      } catch { /* Обычные формы не используют редактор блоков. */ }
    }, 700);
    const full = document.getElementById('agm-edit-full'); full.href = win.location.href;
  });
  window.agmSiteEditor = { open: openEditor, active: () => active };
  addEventListener('beforeunload', event => {
    if (dialog.open && needsConfirmation()) { event.preventDefault(); event.returnValue = ''; }
  });
  setActive(storage.get());
})();
