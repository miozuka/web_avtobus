(() => {
  "use strict";
  const preview = document.querySelector(".agm-job-preview");
  const title = document.querySelector("#title");
  if (!preview || !title) return;
  const fields = Object.fromEntries(
    [...document.querySelectorAll("[data-job-field]")].map((field) => [
      field.dataset.jobField,
      field,
    ]),
  );
  const value = (key) => fields[key]?.value.trim() || "";
  const show = (key, text, optional = false) => {
    const node = preview.querySelector(`[data-job-preview="${key}"]`);
    node.textContent = text;
    node.hidden = optional && !text;
  };
  function update() {
    show("title", title.value.trim() || "Название должности");
    show(
      "description",
      value("description") || "Здесь будет короткое описание вакансии.",
    );
    for (const key of ["badge", "schedule", "salary_note"])
      show(key, value(key), true);
    show("salary", value("salary") || "Уточняйте в отделе кадров");
    const phone = value("phone") || preview.dataset.defaultPhone;
    show("phone", phone ? `Позвонить: ${phone}` : "", true);
    const list = preview.querySelector('[data-job-preview="features"]');
    list.replaceChildren();
    const features = [
      ...new Set(
        value("features")
          .split(/[;\r\n]+/)
          .map((s) => s.trim())
          .filter(Boolean),
      ),
    ];
    for (const text of features) {
      const item = document.createElement("li");
      item.textContent = text;
      list.append(item);
    }
    list.hidden = features.length === 0;
    preview.querySelector('[data-job-preview="url"]').hidden = !value("url");
    preview.querySelector('[data-job-preview="details"]').hidden = ![
      "duties",
      "requirements",
      "conditions",
    ].some((key) => value(key));
  }
  title.addEventListener("input", update);
  for (const field of Object.values(fields))
    field.addEventListener("input", update);
  update();
})();
