(() => {
  "use strict";
  const list = document.querySelector("#agm-direction-list");
  const template = document.querySelector("#agm-direction-template");
  if (!list || !template) return;
  const number = document.querySelector("#agm_number");
  if (number) number.required = true;
  let index = list.querySelectorAll(".agm-direction").length;
  document.querySelector("#agm-add-direction").addEventListener("click", () => {
    const holder = document.createElement("div");
    holder.innerHTML = template.innerHTML.replaceAll(
      "__INDEX__",
      String(index++),
    );
    const direction = holder.firstElementChild;
    list.append(direction);
    direction.querySelectorAll("[data-route-times]").forEach(validate);
    direction.querySelector('input[type="text"]').focus();
  });
  list.addEventListener("click", (event) => {
    const remove = event.target.closest("[data-remove-direction]");
    if (remove) {
      const row = remove.closest(".agm-direction");
      if (
        [...row.querySelectorAll('textarea,input[type="text"]')].some((i) =>
          i.value.trim(),
        ) &&
        !window.confirm(
          "Удалить это направление из графика? Изменение применится после сохранения маршрута.",
        )
      )
        return;
      row.remove();
      return;
    }
    const move = event.target.closest("[data-move-direction]");
    if (!move) return;
    const row = move.closest(".agm-direction");
    if (move.dataset.moveDirection === "up" && row.previousElementSibling)
      list.insertBefore(row, row.previousElementSibling);
    if (move.dataset.moveDirection === "down" && row.nextElementSibling)
      list.insertBefore(row.nextElementSibling, row);
  });
  function validate(field) {
    const valid = field.value
      .trim()
      .split(/[\s,;]+/)
      .filter(Boolean)
      .every((t) => /^([01]?\d|2[0-3]):[0-5]\d$/.test(t));
    const status = field
      .closest("label")
      ?.querySelector("[data-route-time-status]");
    if (status) {
      const tokens = field.value
        .trim()
        .split(/[\s,;]+/)
        .filter(Boolean);
      const count = new Set(tokens.map((time) => time.padStart(5, "0"))).size;
      status.textContent = !valid
        ? "Проверьте формат времени — сохранение такого графика заблокировано."
        : count
          ? `Отправлений: ${count}. На сайте — после сохранения.`
          : "Пусто — не показывается. Серый текст — пример.";
    }
    field.setCustomValidity(
      valid
        ? ""
        : "Укажите время в формате ЧЧ:ММ, например 06:15. Разделяйте пробелами или переносами строк.",
    );
  }
  list.querySelectorAll("[data-route-times]").forEach(validate);
  list.addEventListener("input", (event) => {
    if (event.target.matches("[data-route-times]")) validate(event.target);
    else if (event.target.matches('input[type="text"]'))
      event.target.setCustomValidity("");
  });
  list.addEventListener(
    "invalid",
    (event) => {
      const details = event.target.closest("details");
      if (details) details.open = true;
    },
    true,
  );
  list.closest("form").addEventListener("submit", (event) => {
    if (event.submitter?.name === "agm_cancel_plan") return;
    let invalid = null;
    list.querySelectorAll("[data-route-times]").forEach((field) => {
      validate(field);
      if (!field.validity.valid && !invalid) invalid = field;
    });
    list.querySelectorAll(".agm-direction").forEach((row) => {
      const title = row.querySelector('input[type="text"]');
      const required = Boolean(
        row.querySelector('input[type="hidden"]').value !== "0" ||
        [...row.querySelectorAll("textarea")].some((t) => t.value.trim()),
      );
      title.setCustomValidity(
        required && !title.value.trim() ? "Введите название направления" : "",
      );
      if (!title.validity.valid && !invalid) invalid = title;
    });
    if (invalid) {
      event.preventDefault();
      const details = invalid.closest("details");
      if (details) details.open = true;
      invalid.focus();
      invalid.reportValidity();
    }
  });
})();
