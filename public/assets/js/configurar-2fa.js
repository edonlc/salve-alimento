document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('qrcode');
    if (!el || !el.dataset.uri) return;

    new QRCode(el, {
        text: el.dataset.uri,
        width: 200,
        height: 200,
        colorDark: '#1b4332',
        colorLight: '#ffffff',
    });
});
