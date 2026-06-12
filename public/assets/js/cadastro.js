function pemParaDer(pem) {
    var b64 = pem.replace(/-----[^-]+-----/g, '').replace(/\s/g, '');
    var bin = atob(b64);
    var der = new Uint8Array(bin.length);
    for (var i = 0; i < bin.length; i++) der[i] = bin.charCodeAt(i);
    return der.buffer;
}

function arrayParaBase64(buf) {
    var bytes = new Uint8Array(buf);
    var bin = '';
    for (var i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
    return btoa(bin);
}

async function cifrarAesGcm(texto, chaveAes) {
    var iv = crypto.getRandomValues(new Uint8Array(12));
    var encoded = new TextEncoder().encode(texto);
    var encrypted = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: iv }, chaveAes, encoded);
    var enc = new Uint8Array(encrypted);
    var ciphertext = enc.slice(0, -16);
    var tag = enc.slice(-16);
    // Formato esperado pelo PHP: iv(12) + tag(16) + ciphertext
    var blob = new Uint8Array(12 + 16 + ciphertext.length);
    blob.set(iv, 0);
    blob.set(tag, 12);
    blob.set(ciphertext, 28);
    return arrayParaBase64(blob.buffer);
}

document.getElementById('form-cadastro').addEventListener('submit', async function (e) {
    e.preventDefault();

    var form    = e.target;
    var btn     = form.querySelector('button[type=submit]');
    var status  = document.getElementById('status-cadastro');

    status.style.display = 'block';
    status.textContent = 'Processando cadastro...';
    status.className = 'alerta alerta-info';
    btn.disabled = true;

    try {
        var resp = await fetch('/api/chave-publica');
        var json = await resp.json();
        if (!json.chavePublica) throw new Error('Chave pública indisponível');

        var rsaKey = await crypto.subtle.importKey(
            'spki',
            pemParaDer(json.chavePublica),
            { name: 'RSA-OAEP', hash: 'SHA-256' },
            false,
            ['encrypt']
        );

        var aesKey = await crypto.subtle.generateKey(
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt']
        );

        var cpf      = document.getElementById('cpf').value.trim();
        var endereco = document.getElementById('endereco').value.trim();

        var cpfCifrado      = await cifrarAesGcm(cpf, aesKey);
        var enderecoCifrado = await cifrarAesGcm(endereco, aesKey);

        var aesRaw = await crypto.subtle.exportKey('raw', aesKey);
        var chaveAesCifrada = await crypto.subtle.encrypt({ name: 'RSA-OAEP' }, rsaKey, aesRaw);

        // Popula campos hidden com os blobs cifrados
        document.getElementById('cpf_enc').value      = cpfCifrado;
        document.getElementById('endereco_enc').value = enderecoCifrado;
        document.getElementById('chave_enc').value    = arrayParaBase64(chaveAesCifrada);

        // Limpa campos visíveis — texto puro não vai no POST
        document.getElementById('cpf').value      = '';
        document.getElementById('endereco').value = '';

        form.submit();
    } catch (err) {
        status.textContent = 'Erro ao cifrar: ' + err.message;
        status.className = 'alerta alerta-erro';
        btn.disabled = false;
    }
});
