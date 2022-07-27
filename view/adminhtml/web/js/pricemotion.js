function Pricemotion(rootSelector) {
  const root = document.querySelector(rootSelector);
  if (!root) {
    throw new Error(`No element matched selector: ${rootSelector}`);
  }

  if (!root.dataset.settings) {
    throw new Error(`Root element does not have settings data attribute`);
  }

  const settings = JSON.parse(root.dataset.settings);

  const frame = document.createElement("iframe");
  frame.src = settings.widget_url;
  frame.style.width = "100%";
  frame.style.border = "0";
  frame.style.height = "250px";
  root.appendChild(frame);

  const form = createForm();
  const inputs = {};

  if (settings.form) {
    setInput("form_key", settings.form_key);
  }

  let isValid;
  let validatedResolvers = [];

  window.addEventListener("message", function (e) {
    if (e.origin !== settings.web_origin) {
      return;
    }
    const message = typeof e.data === "string" ? JSON.parse(e.data) : e.data;
    if (message.type === "setWidgetHeight") {
      frame.style.height = message.value + "px";
    } else if (message.type === "updateProductSettings") {
      isValid = message.isValid;
      setInput("product.pricemotion_settings", JSON.stringify(message.value));
      validatedResolvers.forEach(([resolve, reject]) =>
        message.isValid ? resolve() : reject()
      );
      validatedResolvers = [];
    }
  });

  document.body.addEventListener("click", function (e) {
    if (e.target.closest(".pricemotion-submit")) {
      e.preventDefault();
      const valid = new Promise((resolve, reject) =>
        validatedResolvers.push([resolve, reject])
      );
      valid.then(() => form.submit());
      valid.catch(() => console.error("invalid"));
      frame.contentWindow.postMessage(
        { type: "validate" },
        settings.web_origin
      );
    }
  });

  document.body.addEventListener(
    "click",
    function (e) {
      if (
        e.target.closest(".save") &&
        !e.target.closest(".pricemotion-submit") &&
        isValid === false
      ) {
        e.stopImmediatePropagation();

        let delay = 0;

        try {
          root
            .closest("[data-index]")
            .querySelector('[data-state-collapsible="closed"]')
            .click();
          delay = 250;
        } catch (e) {}

        setTimeout(() => {
          root.scrollIntoView({
            behavior: "smooth",
            block: "center",
          });
        }, delay);
      }
    },
    true
  );

  function createForm() {
    if (!settings.form) {
      return root;
    }
    const form = document.createElement("form");
    form.id = "pricemotion-form";
    form.action = settings.form.action;
    form.method = "post";
    root.appendChild(form);
    return form;
  }

  function setInput(name, value) {
    if (!inputs[name]) {
      const input = document.createElement("input");
      input.name = name;
      input.type = "hidden";
      input.dataset.formPart = "product_form";
      form.appendChild(input);
      inputs[name] = input;
    }
    inputs[name].value = value;
  }
}

new Pricemotion("#pricemotion");
