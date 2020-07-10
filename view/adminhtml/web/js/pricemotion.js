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
    frame.src = settings.web_url + '/widget?' + buildQuery({
        token: settings.token,
        ean: settings.ean
    });
    frame.style.width = '100%';
    frame.style.border = '0';
    frame.style.height = '250px';

    root.appendChild(frame);

    window.addEventListener('message', function (e) {
        if (e.origin !== settings.web_url) {
            return;
        }
        var message = JSON.parse(e.data);
        if (message.type === 'setWidgetHeight') {
            frame.style.height = message.value + 'px';
        }
    });

    function buildQuery(query) {
        return Object.entries(query).map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&');
    }
}

new Pricemotion('#pricemotion');