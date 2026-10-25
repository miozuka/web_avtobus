(() => {
  const list = document.getElementById('agm-home-blocks');
  if (!list) return;
  const update = () => {
    [...list.children].forEach((row, index) => {
      row.querySelector('[data-agm-move="up"]').disabled = index === 0;
      row.querySelector('[data-agm-move="down"]').disabled = index === list.children.length - 1;
    });
  };
  list.addEventListener('click', (event) => {
    const button = event.target.closest('[data-agm-move]');
    if (!button) return;
    const row = button.closest('li');
    if (button.dataset.agmMove === 'up' && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
    if (button.dataset.agmMove === 'down' && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
    update();
    list.dispatchEvent(new Event('change', {bubbles:true}));
    document.getElementById('agm-home-position').textContent = `${row.querySelector('label').textContent.trim()}: позиция ${[...list.children].indexOf(row) + 1}`;
    button.focus();
  });
  update();
})();
