#!/usr/bin/env bash

set -Eeuo pipefail

readonly SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_DIR="$(cd -- "${SCRIPT_DIR}/.." && pwd)"
readonly SOURCE_DIR="${PROJECT_DIR}/module/dynamic_rack"
readonly MODULE_NAME="dynamic_rack"

modules_dir=""
backup_root="/var/backups/zabbix-frontend-modules"
dry_run=0

fail() { printf 'ERRO / ERROR: %s\n' "$*" >&2; exit 1; }
usage() {
	printf '%s\n' \
		'Uso: install_dynamic_rack.sh [--modules-dir PATH] [--backup-dir PATH] [--dry-run]' \
		'Instala somente o módulo visual no frontend Zabbix e preserva backup da versão anterior.'
}

while (($# > 0)); do
	case "$1" in
		--modules-dir) (($# >= 2)) || fail '--modules-dir requer um valor'; modules_dir="$2"; shift 2 ;;
		--backup-dir) (($# >= 2)) || fail '--backup-dir requer um valor'; backup_root="$2"; shift 2 ;;
		--dry-run) dry_run=1; shift ;;
		-h|--help) usage; exit 0 ;;
		*) fail "opção desconhecida: $1" ;;
	esac
done

[[ -f "${SOURCE_DIR}/manifest.json" ]] || fail "fonte não encontrada: ${SOURCE_DIR}"
if [[ -z "${modules_dir}" ]]; then
	for candidate in /usr/share/zabbix/modules /usr/share/zabbix/ui/modules /usr/share/webapps/zabbix/modules; do
		if [[ -d "${candidate}" ]]; then modules_dir="${candidate}"; break; fi
	done
fi
[[ -n "${modules_dir}" ]] || fail 'use --modules-dir /caminho/para/modules'
[[ "${modules_dir}" = /* && "${modules_dir}" == */modules ]] || fail 'o destino deve ser absoluto e terminar em /modules'
[[ -d "${modules_dir}" ]] || fail "diretório inexistente: ${modules_dir}"

if command -v php >/dev/null 2>&1; then
	while IFS= read -r -d '' file; do php -l "${file}" >/dev/null; done < <(find "${SOURCE_DIR}" -type f -name '*.php' -print0)
else
	printf '%s\n' 'AVISO: PHP CLI ausente; a validação php -l foi ignorada.' >&2
fi

readonly target="${modules_dir}/${MODULE_NAME}"
readonly timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
readonly backup_dir="${backup_root}/${MODULE_NAME}-${timestamp}"
printf 'Fonte: %s\nDestino: %s\n' "${SOURCE_DIR}" "${target}"
if [[ -e "${target}" ]]; then printf 'Backup: %s\n' "${backup_dir}"; fi
if ((dry_run == 1)); then printf '%s\n' 'Simulação concluída; nenhuma alteração foi feita.'; exit 0; fi
[[ -w "${modules_dir}" ]] || fail "sem permissão de escrita em ${modules_dir}; use sudo"

staging="$(mktemp -d "${modules_dir}/.${MODULE_NAME}.install.XXXXXX")"
cleanup() { local status=$?; if [[ -d "${staging:-}" ]]; then rm -rf -- "${staging}"; fi; exit "${status}"; }
trap cleanup EXIT
cp -a "${SOURCE_DIR}/." "${staging}/"
find "${staging}" -type d -exec chmod 0755 {} +
find "${staging}" -type f -exec chmod 0644 {} +

if [[ -e "${target}" ]]; then mkdir -p -- "${backup_root}"; cp -a -- "${target}" "${backup_dir}"; rm -rf -- "${target}"; fi
mv -- "${staging}" "${target}"
staging=""
trap - EXIT
printf '%s\n' 'Módulo instalado. Escaneie e habilite Rack dinâmico em Administração → Geral → Módulos.'
