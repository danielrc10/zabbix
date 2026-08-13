# Implantação pelo GitHub / Deployment through GitHub

[Português](#português) · [English](#english)

## Português

Como este repositório é público, o servidor Zabbix pode baixar atualizações por HTTPS sem armazenar senha, token ou chave SSH.

> **O instalador é opcional.** Ele serve exclusivamente para adicionar o widget visual `dynamic_status_cards` ao frontend. O template de monitoramento não precisa desse script para coletar itens, avaliar triggers ou gerar gráficos.

### Primeira instalação

Execute com um usuário administrativo comum. Use `sudo` somente na etapa que grava no diretório do frontend:

```bash
sudo install -d -o "$USER" -g "$(id -gn)" /opt/zabbix-community

git clone https://github.com/danielrc10/zabbix.git \
  /opt/zabbix-community/repository

cd /opt/zabbix-community/repository
```

Importe o template pela GUI do Zabbix. Se quiser os cards personalizados, revise o código e simule a instalação sem alterações:

```bash
projects/web-service-monitoring/zabbix-7.4/scripts/install_dynamic_status_cards.sh \
  --dry-run
```

Depois instale o módulo:

```bash
sudo projects/web-service-monitoring/zabbix-7.4/scripts/install_dynamic_status_cards.sh
```

Somente nesse caso, habilite-o em **Administração → Geral → Módulos**. O script copia apenas o módulo para o frontend, valida os arquivos PHP e preserva a versão anterior em backup; não altera banco de dados, Server, Agent 2 ou hosts monitorados.

### Atualização

```bash
cd /opt/zabbix-community/repository
git status --short
git pull --ff-only origin main
```

Se você utiliza o widget opcional, execute novamente o instalador após a atualização:

```bash
sudo projects/web-service-monitoring/zabbix-7.4/scripts/install_dynamic_status_cards.sh
```

O instalador valida os arquivos PHP e salva a versão anterior em:

```text
/var/backups/zabbix-frontend-modules/
```

Use sempre tags ou Releases para registrar exatamente qual versão está em produção. Para esta implementação, as tags seguem o padrão `web-service-monitoring-zabbix-7.4-vX.Y.Z`. Não edite a cópia clonada no servidor; alterações locais impedem um fluxo de atualização previsível.

Se o repositório se tornar privado no futuro, use uma Deploy Key somente leitura vinculada exclusivamente a ele. Não armazene um token pessoal no servidor.

## English

Because this repository is public, the Zabbix server can download updates over HTTPS without storing a password, token, or SSH key.

> **The installer is optional.** It is used exclusively to add the `dynamic_status_cards` visual widget to the frontend. The monitoring template does not require this script to collect items, evaluate triggers, or generate graphs.

### First installation

Run the commands with a regular administrative user. Use `sudo` only for the step that writes to the frontend directory:

```bash
sudo install -d -o "$USER" -g "$(id -gn)" /opt/zabbix-community

git clone https://github.com/danielrc10/zabbix.git \
  /opt/zabbix-community/repository

cd /opt/zabbix-community/repository
```

Import the template through the Zabbix GUI. If you want the custom cards, review the code and preview the installation without making changes:

```bash
projects/web-service-monitoring/zabbix-7.4/scripts/install_dynamic_status_cards.sh \
  --dry-run
```

Then install the module:

```bash
sudo projects/web-service-monitoring/zabbix-7.4/scripts/install_dynamic_status_cards.sh
```

Only in that case, enable it under **Administration → General → Modules**. The script copies only the module into the frontend, validates its PHP files, and preserves the previous version as a backup; it does not modify the database, Server, Agent 2, or monitored hosts.

### Update

```bash
cd /opt/zabbix-community/repository
git status --short
git pull --ff-only origin main
```

If you use the optional widget, run the installer again after updating:

```bash
sudo projects/web-service-monitoring/zabbix-7.4/scripts/install_dynamic_status_cards.sh
```

The installer validates the PHP files and stores the previous version under:

```text
/var/backups/zabbix-frontend-modules/
```

Always use tags or Releases to record the exact version running in production. For this implementation, tags follow the `web-service-monitoring-zabbix-7.4-vX.Y.Z` pattern. Do not edit the cloned copy on the server; local changes prevent a predictable update workflow.

If the repository becomes private in the future, use a read-only Deploy Key restricted to this repository. Do not store a personal token on the server.
