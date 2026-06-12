#!/bin/bash
# Gera par de chaves RSA-4096 para criptografia híbrida
# Execute UMA VEZ antes de subir o ambiente:
#   chmod +x keys/gerar-chaves.sh && ./keys/gerar-chaves.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "Gerando chave privada RSA-4096..."
openssl genrsa -out "$SCRIPT_DIR/private.pem" 4096

echo "Extraindo chave pública..."
openssl rsa -in "$SCRIPT_DIR/private.pem" -pubout -out "$SCRIPT_DIR/public.pem"

chmod 640 "$SCRIPT_DIR/private.pem"
chmod 644 "$SCRIPT_DIR/public.pem"

echo ""
echo "Chaves geradas com sucesso:"
echo "  Privada: keys/private.pem  (NUNCA commitar — está no .gitignore)"
echo "  Pública: keys/public.pem   (segura para versionar)"
