# Implantação pelo GitHub / Deployment through GitHub

[Português](#português) · [English](#english)

## Português

Como este repositório é público, o servidor Zabbix pode baixar atualizações por HTTPS sem armazenar senha, token ou chave SSH.

### Primeira instalação

Execute com um usuário administrativo comum. Use `sudo` somente na etapa que grava no diretório do frontend:

```bash
sudo install -d -o "$USER" -g "$(id -gn)" /opt/zabbix-community

git clone https://github.com/danielrc10/zabbix.git \
  /opt/zabbix-community/repository

cd /opt/zabbix-community/repository

sudo projects/web-service-monitoring/scripts/install_dynamic_status_cards.sh
```

Depois habilite o módulo em **Administração → Geral → Módulos** e importe o template pela GUI do Zabbix.

### Atualização

```bash
cd /opt/zabbix-community/repository
git status --short
git pull --ff-only origin main
sudo projects/web-service-monitoring/scripts/install_dynamic_status_cards.sh
```

O instalador valida os arquivos PHP e salva a versão anterior em:

```text
/var/backups/zabbix-frontend-modules/
```

Use sempre tags ou Releases para registrar exatamente qual versão está em produção. Não edite a cópia clonada no servidor; alterações locais impedem um fluxo de atualização previsível.

Se o repositório se tornar privado no futuro, use uma Deploy Key somente leitura vinculada exclusivamente a ele. Não armazene um token pessoal no servidor.

## English

Because this repository is public, the Zabbix server can download updates over HTTPS without storing a password, token, or SSH key.

### First installation

Run the commands with a regular administrative user. Use `sudo` only for the step that writes to the frontend directory:

```bash
sudo install -d -o "$USER" -g "$(id -gn)" /opt/zabbix-community

git clone https://github.com/danielrc10/zabbix.git \
  /opt/zabbix-community/repository

cd /opt/zabbix-community/repository

sudo projects/web-service-monitoring/scripts/install_dynamic_status_cards.sh
```

Then enable the module under **Administration → General → Modules** and import the template through the Zabbix GUI.

### Update

```bash
cd /opt/zabbix-community/repository
git status --short
git pull --ff-only origin main
sudo projects/web-service-monitoring/scripts/install_dynamic_status_cards.sh
```

The installer validates the PHP files and stores the previous version under:

```text
/var/backups/zabbix-frontend-modules/
```

Always use tags or Releases to record the exact version running in production. Do not edit the cloned copy on the server; local changes prevent a predictable update workflow.

If the repository becomes private in the future, use a read-only Deploy Key restricted to this repository. Do not store a personal token on the server.
