function Pricemotion(rootSelector) {
    const ATTR_SETTINGS = 'data-settings';

    const root = document.querySelector(rootSelector);
    if (!root) {
        throw new Error(`No element matched selector: ${rootSelector}`);
    }

    if (!root.hasAttribute(ATTR_SETTINGS)) {
        throw new Error(`Root element does not have ${ATTR_SETTINGS} attribute`);
    }

    const settings = JSON.parse(root.getAttribute('data-settings'));

    const frame = document.createElement('iframe');
    frame.src = settings.widget_url;
    frame.style.width = '100%';
    frame.style.border = '0';
    frame.style.height = '250px';
    root.appendChild(frame);

    const inputs = {};

    window.addEventListener('message', function (e) {
        if (e.origin !== settings.web_origin) {
            return;
        }
        var message = JSON.parse(e.data);
        if (message.type === 'setWidgetHeight') {
            frame.style.height = message.value + 'px';
        } else if (message.type === 'updateProductSettings') {
            setInput('pricemotion_settings', JSON.stringify(message.value));
            setInput('pricemotion_updated_at', '0');
        }
    });

    function setInput(name, value) {
        if (!inputs[name]) {
            const input = document.createElement('input');
            input.name = 'product.' + name;
            input.type = 'hidden';
            input.setAttribute('data-form-part', 'product_form');
            root.appendChild(input);
            inputs[name] = input;
        }
        inputs[name].value = value;
    }
}

new Pricemotion('#pricemotion');