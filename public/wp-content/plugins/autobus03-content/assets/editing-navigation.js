(() => {
  'use strict';
  const normalize = value => String(value || '').toLocaleLowerCase('ru').replaceAll('ё', 'е').replace(/\s+/g, ' ').trim();
  const commands = window.agmEditingCommands || [];
  const dialog = document.createElement('dialog');
  dialog.id = 'agm-command-dialog';
  dialog.setAttribute('aria-labelledby', 'agm-command-title');
  dialog.innerHTML = '<header><h2 id="agm-command-title">Что нужно изменить?</h2><button type="button" class="button" data-command-close aria-label="Закрыть поиск">Закрыть</button></header><label for="agm-command-query">Название страницы, поля или задача</label><input type="search" id="agm-command-query" placeholder="Например: телефон, логотип, суббота, вакансия"><p id="agm-command-count" role="status" aria-live="polite"></p><div id="agm-command-results"></div>';
  document.body.append(dialog);
  const query = dialog.querySelector('input'), list = dialog.querySelector('#agm-command-results');
  let previousFocus;
  function render() {
    const words = normalize(query.value).split(' ').filter(Boolean);
    const matches = commands.filter(item => words.every(word => normalize(item.label + ' ' + item.group + ' ' + (item.hint || '')).includes(word)));
    list.replaceChildren();
    for (const item of matches.slice(0, 24)) {
      const row = document.createElement('a');
      row.href = item.url;
      const title = document.createElement('strong'), group = document.createElement('span');
      title.textContent = item.label; group.textContent = item.group;
      row.append(title, group); list.append(row);
    }
    dialog.querySelector('#agm-command-count').textContent = matches.length ? 'Найдено: ' + matches.length + (matches.length > 24 ? '. Показаны первые 24 — уточните запрос.' : '') : 'Ничего не найдено. Попробуйте «телефон», «фото» или название раздела.';
  }
  function open() { previousFocus = document.activeElement; render(); dialog.showModal(); query.focus(); }
  function close() { dialog.close(); previousFocus?.focus(); }
  document.querySelectorAll('[data-agm-command-open]').forEach(button => button.addEventListener('click', open));
  dialog.querySelector('[data-command-close]').addEventListener('click', close);
  query.addEventListener('input', render);
  query.addEventListener('keydown', event => {
    if (event.key === 'ArrowDown') { event.preventDefault(); list.querySelector('a')?.focus(); }
    if (event.key === 'Enter') { event.preventDefault(); if (list.children.length === 1) list.firstElementChild.click(); }
  });
  dialog.addEventListener('click', event => { if (event.target === dialog && event.clientX < dialog.getBoundingClientRect().left) close(); });
  document.addEventListener('keydown', event => { if (event.altKey && !event.ctrlKey && !event.metaKey && event.code === 'KeyK') { event.preventDefault(); dialog.open ? close() : open(); } });
  document.querySelectorAll('[data-agm-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!confirm(form.dataset.agmConfirm)) { event.preventDefault(); return; }
    // Возврат настроек — осознанная замена формы, предупреждение о несохранённом уже показано.
    document.querySelectorAll('[data-unsaved-form]').forEach(other => { other.dataset.submitted = '1'; });
  }));
  const fieldSearch = document.querySelector('#agm-field-search');
  if (fieldSearch) {
    const rows = [...document.querySelectorAll('.agm-setting-field')];
    fieldSearch.addEventListener('input', () => {
      const words = normalize(fieldSearch.value).split(' ').filter(Boolean);
      rows.forEach(row => { row.hidden = !words.every(word => normalize(row.textContent).includes(word)); });
      document.querySelector('#agm-field-count').textContent = 'Полей: ' + rows.filter(row => !row.hidden).length + ' из ' + rows.length;
    });
  }
  // Ссылка из поиска сразу подводит к нужному полю и в обычной админке.
  if (window.top === window) {
    const keys = (new URL(location.href).searchParams.get('agm_fields') || '').split(',');
    for (const key of keys) {
      if (!/^[a-z0-9_]+$/.test(key)) continue;
      const field = document.getElementById('agm-setting-' + key);
      if (field) {
        const row = field.closest('.agm-setting-field'); row?.classList.add('agm-found-field');
        setTimeout(() => { row?.scrollIntoView({block: 'center'}); if (field.type !== 'hidden') field.focus({preventScroll: true}); }, 100);
        break;
      }
    }
  }
})();
