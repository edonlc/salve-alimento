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
    // WebCrypto retorna: ciphertext || tag (tag = últimos 16 bytes)
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

document.getElementById('form-perfil').addEventListener('submit', async function (e) {
    e.preventDefault();

    var cpf      = document.getElementById('cpf').value.trim();
    var endereco = document.getElementById('endereco').value.trim();
    var status   = document.getElementById('status-cripto');

    if (!cpf) {
        status.style.display = 'block';
        status.textContent = 'Informe o CPF.';
        status.className = 'alerta alerta-erro';
        return;
    }

    status.style.display = 'block';
    status.textContent = 'Cifrando dados no navegador...';
    status.className = 'alerta alerta-info';

    try {
        // 1. Busca chave pública RSA do servidor
        var resp = await fetch('/api/chave-publica');
        var json = await resp.json();

        if (!json.chavePublica) throw new Error('Chave pública não disponível');

        // 2. Importa chave RSA-OAEP
        var rsaKey = await crypto.subtle.importKey(
            'spki',
            pemParaDer(json.chavePublica),
            { name: 'RSA-OAEP', hash: 'SHA-256' },
            false,
            ['encrypt']
        );

        // 3. Gera chave AES-256 aleatória
        var aesKey = await crypto.subtle.generateKey(
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt']
        );

        // 4. Cifra CPF e endereço com AES-GCM
        var cpfCifrado      = await cifrarAesGcm(cpf, aesKey);
        var enderecoCifrado = endereco ? await cifrarAesGcm(endereco, aesKey) : '';

        // 5. Exporta chave AES e cifra com RSA-OAEP
        var aesRaw = await crypto.subtle.exportKey('raw', aesKey);
        var chaveAesCifrada = await crypto.subtle.encrypt(
            { name: 'RSA-OAEP' },
            rsaKey,
            aesRaw
        );

        // 6. Envia blobs cifrados ao backend (dado sensível nunca trafega em plain)
        var postResp = await fetch('/perfil/salvar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cpf:      cpfCifrado,
                endereco: enderecoCifrado,
                chave:    arrayParaBase64(chaveAesCifrada),
            }),
        });

        var resultado = await postResp.json();

        if (resultado.sucesso) {
            status.textContent = 'Dados salvos com criptografia híbrida (AES-256-GCM + RSA-OAEP).';
            status.className = 'alerta alerta-sucesso';
            setTimeout(function () { window.location.reload(); }, 1500);
        } else {
            throw new Error(resultado.erro || 'Erro no servidor');
        }
    } catch (err) {
        status.textContent = 'Erro: ' + err.message;
        status.className = 'alerta alerta-erro';
    }
});
