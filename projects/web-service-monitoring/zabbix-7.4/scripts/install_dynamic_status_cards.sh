#!/usr/bin/env bash

# PT-BR: Instalador seguro do módulo dynamic_status_cards no frontend Zabbix.
# EN: Safe installer for the dynamic_status_cards Zabbix frontend module.
#
# Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
# LinkedIn: https://www.linkedin.com/in/daniel-ti/
# Licença / License: PolyForm Noncommercial 1.0.0
# Uso comercial / Commercial use: contato / contact danielrc10@gmail.com

set -Eeuo pipefail

readonly SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_DIR="$(cd -- "${SCRIPT_DIR}/.." && pwd)"
readonly SOURCE_DIR="${PROJECT_DIR}/modules/dynamic_status_cards"
readonly MODULE_NAME="dynamic_status_cards"

modules_dir=""
backup_root="/var/backups/zabbix-frontend-modules"
dry_run=0

usage() {
	cat <<'USAGE'
Uso / Usage:
  install_dynamic_status_cards.sh [opções / options]

Opções / Options:
  --modules-dir PATH   Diretório modules do frontend / Frontend modules directory
  --backup-dir PATH    Diretório de backups / Backup directory
  --dry-run            Apenas mostra as ações / Only display planned actions
  -h, --help           Mostra esta ajuda / Show this help
USAGE
}

fail() {
	printf 'ERRO / ERROR: %s\n' "$*" >&2
	exit 1
}

while (($# > 0)); do
	case "$1" in
		--modules-dir)
			(($# >= 2)) || fail '--modules-dir requer um valor / requires a value'
			modules_dir="$2"
			shift 2
			;;
		--backup-dir)
			(($# >= 2)) || fail '--backup-dir requer um valor / requires a value'
			backup_root="$2"
			shift 2
			;;
		--dry-run)
			dry_run=1
			shift
			;;
		-h|--help)
			usage
			exit 0
			;;
		*)
			fail "opção desconhecida / unknown option: $1"
			;;
	esac
done

[[ -f "${SOURCE_DIR}/manifest.json" ]] \
	|| fail "fonte do módulo não encontrada / module source not found: ${SOURCE_DIR}"

if [[ -z "${modules_dir}" ]]; then
	for candidate in \
		/usr/share/zabbix/modules \
		/usr/share/zabbix/ui/modules \
		/usr/share/webapps/zabbix/modules; do
		if [[ -d "${candidate}" ]]; then
			modules_dir="${candidate}"
			break
		fi
	done
fi

[[ -n "${modules_dir}" ]] || fail \
	'não foi possível detectar o diretório modules; use --modules-dir /caminho / could not detect modules directory; use --modules-dir /path'
[[ "${modules_dir}" = /* ]] || fail \
	'o diretório modules deve ser absoluto / modules directory must be absolute'
[[ "${modules_dir}" == */modules ]] || fail \
	'o destino deve terminar em /modules / destination must end with /modules'
[[ -d "${modules_dir}" ]] || fail \
	"diretório inexistente / directory does not exist: ${modules_dir}"

command -v php >/dev/null 2>&1 || fail \
	'php não foi encontrado; instale o PHP CLI do frontend / php was not found; install the frontend PHP CLI'

while IFS= read -r -d '' file; do
	php -l "${file}" >/dev/null
done < <(find "${SOURCE_DIR}" -type f -name '*.php' -print0)

readonly target="${modules_dir}/${MODULE_NAME}"
readonly timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
readonly backup_dir="${backup_root}/${MODULE_NAME}-${timestamp}"

printf 'Fonte / Source: %s\n' "${SOURCE_DIR}"
printf 'Destino / Destination: %s\n' "${target}"
if [[ -e "${target}" ]]; then
	printf 'Backup: %s\n' "${backup_dir}"
fi

if ((dry_run == 1)); then
	printf 'Simulação concluída; nenhuma alteração foi feita. / Dry run complete; no changes were made.\n'
	exit 0
fi

[[ -w "${modules_dir}" ]] || fail \
	"sem permissão de escrita; execute com sudo / directory is not writable; run with sudo: ${modules_dir}"

staging="$(mktemp -d "${modules_dir}/.${MODULE_NAME}.install.XXXXXX")"
old_target=""

cleanup() {
	local status=$?
	if [[ -n "${staging:-}" && -d "${staging}" ]]; then
		rm -rf -- "${staging}"
	fi
	if ((status != 0)) && [[ -n "${old_target:-}" && -d "${old_target}" && ! -e "${target}" ]]; then
		mv -- "${old_target}" "${target}"
	fi
	exit "${status}"
}
trap cleanup EXIT

cp -a "${SOURCE_DIR}/." "${staging}/"
find "${staging}" -type d -exec chmod 0755 {} +
find "${staging}" -type f -exec chmod 0644 {} +

owner_uid="$(stat -c '%u' "${modules_dir}")"
owner_gid="$(stat -c '%g' "${modules_dir}")"
chown -R "${owner_uid}:${owner_gid}" "${staging}"

if [[ -e "${target}" ]]; then
	mkdir -p -- "${backup_root}"
	cp -a -- "${target}" "${backup_dir}"
	old_target="${modules_dir}/.${MODULE_NAME}.previous-${timestamp}"
	mv -- "${target}" "${old_target}"
fi

mv -- "${staging}" "${target}"
staging=""

if [[ -n "${old_target}" && -d "${old_target}" ]]; then
	rm -rf -- "${old_target}"
	old_target=""
fi

trap - EXIT

printf '\nMódulo instalado com sucesso. / Module installed successfully.\n'
printf '%s\n' 'Próximo passo / Next step:'
printf '%s\n' '  Administração / Administration → Geral / General → Módulos / Modules'
printf '%s\n' '  Escanear diretório / Scan directory → Habilitar / Enable'
