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
    frame.classList.add('pricemotion-frame');
    frame.src = settings.web_url + '/widget#' + encodeURIComponent(JSON.stringify({
        token: settings.token,
        ean: settings.ean,
        settings: settings.settings
    }));
    frame.style.width = '100%';
    frame.style.border = '0';
    frame.style.height = '250px';
    root.appendChild(frame);

    const input = document.createElement('input');
    input.name = 'product[pricemotion_settings]';
    input.type = 'hidden';
    input.value = JSON.stringify(settings.settings);
    root.appendChild(input);

    window.addEventListener('message', function (e) {
        if (e.origin !== settings.web_url) {
            return;
        }
        var message = JSON.parse(e.data);
        if (message.type === 'setWidgetHeight') {
            frame.style.height = message.value + 'px';
        } else if (message.type === 'updateProductSettings') {
            input.value = JSON.stringify(message.value);
        }
    });
}

new Pricemotion('#pricemotion');