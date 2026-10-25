(() => {
  "use strict";
  const config = window.agmStaffDirectory;
  if (!config) return;
  const node = (tag, text, className) => {
    const el = document.createElement(tag);
    if (text) el.textContent = text;
    if (className) el.className = className;
    return el;
  };

  if (config.screen === "users") {
    const summary = document.querySelector(".agm-staff-summary"),
      boundary = document.querySelector(".wrap .wp-header-end");
    if (summary && boundary) boundary.after(summary);
    const title = document.querySelector(".wrap > h1");
    if (title) title.textContent = "Сотрудники";
    const add = document.querySelector(".page-title-action");
    if (add) add.textContent = "Добавить сотрудника";
    const search = document.querySelector("#user-search-input");
    if (search) search.placeholder = "Логин, имя или email";
    const searchButton = document.querySelector("#search-submit");
    if (searchButton) searchButton.value = "Найти";
    const status = document.querySelector("#agm-staff-status"),
      filter = document.querySelector("#agm_filter"),
      change = document.querySelector("#changeit");
    if (status && filter && change) {
      const group = node("div", "", "agm-staff-filter");
      group.append(status, filter);
      change.after(group);
    }
  }
  const createButton = document.querySelector("#createusersub");
  if (createButton) createButton.value = "Добавить сотрудника";

  const role = document.querySelector("select#role");
  if (role) {
    const hint = node("p", "", "agm-staff-role-hint");
    hint.id = "agm-staff-role-hint";
    hint.setAttribute("aria-live", "polite");
    role.setAttribute(
      "aria-describedby",
      [role.getAttribute("aria-describedby"), hint.id]
        .filter(Boolean)
        .join(" "),
    );
    role.after(hint);
    const update = () => {
      hint.textContent =
        config.roles[role.value]?.summary || "Выберите роль для сотрудника.";
    };
    role.addEventListener("change", update);
    update();
  }
  const roleForm = document
    .querySelector(".agm-staff-permissions")
    ?.closest("form");
  if (roleForm) {
    const groups = document.querySelector(".agm-staff-permissions");
    const box = node("div", "", "agm-staff-preset");
    const label = node("label", "Взять права за основу");
    label.htmlFor = "agm-staff-preset";
    const select = node("select");
    select.id = "agm-staff-preset";
    select.add(new Option("Выберите существующую роль", ""));
    for (const [key, item] of Object.entries(config.roles))
      if (item.values) select.add(new Option(item.name, key));
    const button = node("button", "Подставить права", "button");
    button.type = "button";
    const status = node(
      "p",
      "Название роли не меняется. Права можно скорректировать ниже и затем сохранить.",
      "description",
    );
    status.setAttribute("aria-live", "polite");
    button.addEventListener("click", () => {
      const preset = config.roles[select.value];
      if (!preset?.values) {
        status.textContent = "Сначала выберите роль из списка.";
        select.focus();
        return;
      }
      for (const [key, value] of Object.entries(preset.values)) {
        const field = roleForm.querySelector(`[name="permissions[${key}]"]`);
        if (!field) continue;
        if (field.type === "checkbox") field.checked = Boolean(value);
        else field.value = value;
        field.dispatchEvent(new Event("change", { bubbles: true }));
      }
      status.textContent = `Подставлены права роли «${preset.name}». Проверьте их и сохраните форму.`;
    });
    box.append(label, select, button, status);
    groups.before(box);
    const summary = node("p", "", "agm-staff-live-summary");
    summary.setAttribute("aria-live", "polite");
    groups.after(summary);
    const update = () => {
      const parts = [];
      for (const field of groups.querySelectorAll("select")) {
        if (field.value !== "none")
          parts.push(
            field.closest(".agm-staff-permission").querySelector("strong")
              .textContent +
              ": " +
              field.selectedOptions[0].textContent.toLowerCase(),
          );
      }
      for (const field of groups.querySelectorAll(
        "input[type=checkbox]:checked",
      ))
        parts.push(field.closest("label").querySelector("strong").textContent);
      summary.textContent =
        "Итого: " +
        (parts.length ? parts.join("; ") : "вход и собственный профиль") +
        ".";
    };
    roleForm.addEventListener("change", update);
    update();
  }
  const form = document.querySelector("#your-profile, #createuser");
  if (form) {
    const title = document.querySelector(".wrap > h1");
    if (title && form.id === "createuser")
      title.textContent = "Добавить сотрудника";
    const help = node("div", "", "agm-staff-form-help");
    help.append(
      node(
        "strong",
        form.id === "createuser"
          ? "1. Данные → 2. Пароль → 3. Роль → Добавить"
          : "Данные, роль и пароль сотрудника",
      ),
    );
    help.append(
      node(
        "p",
        "Логин используется для входа. Email нужен для восстановления пароля. Назначайте только необходимые права; пояснение к выбранной роли находится под списком.",
      ),
    );
    form.prepend(help);
    const rows = form.querySelectorAll(
      ".user-admin-color-wrap,.user-rich-editing-wrap,.user-syntax-highlighting-wrap,.user-comment-shortcuts-wrap,.user-admin-bar-front-wrap,.user-infinite-scrolling-wrap,.user-description-wrap,.user-profile-picture",
    );
    const optional = new Set(rows);
    const url = form.querySelector("#url");
    if (url) optional.add(url.closest("tr"));
    const appPasswords = form.querySelector("#application-passwords-section");
    if (appPasswords) optional.add(appPasswords);
    for (const row of optional) if (row) row.dataset.agmOptional = "true";
    // Поля остаются в форме со своими значениями; сворачивается только их отображение.
    const personal = form.querySelector("table:has(.user-admin-color-wrap)");
    if (personal && personal.previousElementSibling?.matches("h2"))
      personal.previousElementSibling.dataset.agmOptional = "true";
    if (optional.size) {
      const toggle = node(
        "button",
        "Дополнительные настройки профиля",
        "button agm-staff-profile-toggle",
      );
      toggle.type = "button";
      toggle.setAttribute("aria-expanded", "false");
      toggle.addEventListener("click", () => {
        const expanded = toggle.getAttribute("aria-expanded") !== "true";
        toggle.setAttribute("aria-expanded", String(expanded));
        form.classList.toggle("agm-staff-compact", !expanded);
        toggle.textContent = expanded
          ? "Свернуть дополнительные настройки"
          : "Дополнительные настройки профиля";
      });
      form.addEventListener(
        "invalid",
        (event) => {
          if (
            event.target.closest("[data-agm-optional]") &&
            toggle.getAttribute("aria-expanded") === "false"
          )
            toggle.click();
        },
        true,
      );
      form.addEventListener("submit", (event) => {
        const invalid = [
          ...form.querySelectorAll("[data-agm-optional] input"),
        ].find((field) => !field.validity.valid);
        if (!invalid) return;
        event.preventDefault();
        if (toggle.getAttribute("aria-expanded") === "false") toggle.click();
        invalid.focus();
        invalid.reportValidity();
      });
      form.classList.add("agm-staff-compact");
      help.append(toggle);
    }
  }
  const reassign = document.querySelector(
    'input[name="delete_option"][value="reassign"]',
  );
  if (reassign) reassign.checked = true;
})();
