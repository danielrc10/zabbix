# Monitoramento de sites e certificados — Zabbix 7.4 / Website and certificate monitoring — Zabbix 7.4

[Português](#português) · [English](#english)

> Zabbix 7.4 · Um host lógico · Vários sites HTTPS · LLD · Certificados · Dashboard

## Português

Este projeto monitora vários sites HTTPS a partir de **um único host lógico** no Zabbix 7.4. A macro `{$WEB.SITES}` contém a lista de destinos e uma regra de descoberta de baixo nível (LLD) cria automaticamente itens, triggers e gráficos para cada site.

Também acompanha certificados TLS usando o plugin `WebCertificate` de um Zabbix Agent 2 central. Os servidores dos sites monitorados não precisam executar um agente Zabbix.

### Arquitetura

```text
Host lógico "ZabbixServer" (nome visível "Certificados")
├── Zabbix Server ou Proxy → HTTPS, status HTTP e tempo de resposta
└── Agent 2 central        → certificados remotos com web.certificate.get
```

### Arquivos

- [Template YAML](template/template_web_service_monitoring.yaml)
- [Módulo de cards em ZIP](dist/dynamic_status_cards.zip)
- [Código-fonte do módulo](modules/dynamic_status_cards/)
- [Instalador do módulo](scripts/install_dynamic_status_cards.sh)
- [Implantação pelo GitHub](DEPLOYMENT.md)
- [Validadores](tools/)

O projeto não executa Certbot, renovação automática, `system.run` ou comandos nos servidores monitorados. Após uma renovação manual, os alarmes fecham automaticamente quando o novo certificado é coletado.

### Instalação resumida

1. Garanta que um **Zabbix Agent 2** esteja acessível pelo host lógico. O plugin `WebCertificate` é executado nesse agente central.
2. Crie um host no Zabbix, por exemplo:

   ```text
   Host name: ZabbixServer
   Visible name: Certificados
   Agent interface: 127.0.0.1:10050
   ```

   Em containers, `127.0.0.1` aponta para o próprio container. Use o endereço realmente alcançável do Agent 2.

3. Instale o módulo visual no servidor do **frontend Zabbix**:

   ```bash
   sudo ./scripts/install_dynamic_status_cards.sh
   ```

   Para informar outro diretório:

   ```bash
   sudo ./scripts/install_dynamic_status_cards.sh \
     --modules-dir /usr/share/zabbix/modules
   ```

4. Acesse **Administração → Geral → Módulos → Escanear diretório** e habilite **Cards de status dinâmicos**.
5. Importe o [template YAML](template/template_web_service_monitoring.yaml) e vincule-o ao host.
6. Sobrescreva `{$WEB.SITES}` no host e execute imediatamente a regra de descoberta.

Teste opcional do certificado:

```bash
zabbix_get -s 127.0.0.1 -p 10050 \
  -k 'web.certificate.get[www.exemplo.com.br,443,www.exemplo.com.br]'
```

### Lista de sites

Forma simples:

```json
["site-a.com.br", "site-b.com.br", "site-c.com.br"]
```

Forma completa:

```json
[
  {
    "id": "portal-cliente",
    "name": "Portal Cliente",
    "host": "portal.exemplo.com.br",
    "port": 443,
    "path": "/login",
    "status": 200
  }
]
```

| Campo | Obrigatório | Descrição |
|---|---|---|
| `host` | sim | DNS ou IPv4, sem esquema, porta ou caminho |
| `id` | não | Identificador estável e único |
| `name` | não | Nome apresentado nos itens, eventos e cards |
| `port` | não | Porta TLS; padrão `443` |
| `path` | não | Caminho iniciado por `/`; padrão `/` |
| `status` | não | Status HTTP esperado; padrão `200` |

Use um `id` explícito e estável na forma completa. Cada combinação `host:port` deve aparecer apenas uma vez. Macros de usuário do Zabbix aceitam até 2048 caracteres; listas maiores devem usar outra fonte de descoberta.

### Dados coletados

Para cada site são criados:

- disponibilidade e código HTTP;
- tempo de resposta em milissegundos;
- última mensagem de erro;
- data inicial e final do certificado;
- dias restantes e estado de saúde;
- issuer, subject, CN e SAN;
- resultado detalhado da validação TLS;
- gráfico de tempo de resposta.

A requisição segue redirecionamentos e usa um conjunto pequeno de headers configuráveis para reduzir falsos `403`:

```text
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
Accept-Language: pt-BR,pt;q=0.9,en;q=0.8
Upgrade-Insecure-Requests: 1
Sec-Fetch-Site: none
Sec-Fetch-Mode: navigate
```

### Dashboard e alertas

O dashboard **Visão geral dos sites** possui:

- página **Cards**, com um card automático para cada valor da tag `site`;
- página **Grades nativas**, com Honeycombs e gráficos nativos do Zabbix.

Os cards mostram disponibilidade, HTTP, certificado, dias restantes, vencimento e resposta. Verde representa OK, amarelo representa aviso, vermelho representa falha/expiração e cinza representa ausência de dados.

Os triggers cobrem indisponibilidade, status HTTP inesperado, resposta lenta, certificado sem dados, vencimento próximo, certificado expirado e certificado inválido.

### Principais macros

| Macro | Padrão | Uso |
|---|---:|---|
| `{$WEB.SITES}` | `[]` | Lista JSON de sites |
| `{$WEB.DISCOVERY.INTERVAL}` | `10m` | Releitura da lista |
| `{$WEB.INTERVAL}` | `1m` | Coleta HTTP |
| `{$WEB.TIMEOUT}` | `10s` | Timeout por requisição |
| `{$WEB.RESPONSE.WARN}` | `2000` | Aviso de resposta em ms |
| `{$WEB.RESPONSE.HIGH}` | `5000` | Alarme alto em ms |
| `{$CERT.INTERVAL}` | `1h` | Coleta do certificado |
| `{$CERT.NODATA}` | `3h` | Ausência de dados TLS |
| `{$CERT.EXPIRY.WARN}` | `15` | Aviso antecipado em dias |

### Validação local

Na raiz do repositório:

```bash
ruby projects/web-service-monitoring/zabbix-7.4/tools/validate_template.rb \
  projects/web-service-monitoring/zabbix-7.4/template/template_web_service_monitoring.yaml

ruby projects/web-service-monitoring/zabbix-7.4/tools/validate_module.rb \
  projects/web-service-monitoring/zabbix-7.4/modules/dynamic_status_cards

find projects/web-service-monitoring/zabbix-7.4/modules/dynamic_status_cards \
  -name '*.php' -print0 | xargs -0 -n1 php -l
```

Valide sempre a importação e o dashboard em uma instalação de homologação com o mesmo patch do Zabbix 7.4 usado em produção.

---

## English

This project monitors multiple HTTPS websites from **a single logical host** in Zabbix 7.4. The `{$WEB.SITES}` macro stores the target list, and a low-level discovery (LLD) rule automatically creates items, triggers, and graphs for each website.

It also monitors TLS certificates through the `WebCertificate` plugin of a central Zabbix Agent 2. The monitored website servers do not need to run a Zabbix agent.

### Architecture

```text
Logical host "ZabbixServer" (visible name "Certificates")
├── Zabbix Server or Proxy → HTTPS, HTTP status, and response time
└── Central Agent 2        → remote certificates with web.certificate.get
```

### Files

- [YAML template](template/template_web_service_monitoring.yaml)
- [Card module ZIP](dist/dynamic_status_cards.zip)
- [Module source code](modules/dynamic_status_cards/)
- [Module installer](scripts/install_dynamic_status_cards.sh)
- [GitHub deployment guide](DEPLOYMENT.md#english)
- [Validators](tools/)

The project does not run Certbot, automatic renewal, `system.run`, or commands on monitored servers. After a manual renewal, alarms recover automatically when the new certificate is collected.

### Quick installation

1. Make sure a **Zabbix Agent 2** is reachable through the logical host. The `WebCertificate` plugin runs on this central agent.
2. Create a Zabbix host, for example:

   ```text
   Host name: ZabbixServer
   Visible name: Certificates
   Agent interface: 127.0.0.1:10050
   ```

   In containers, `127.0.0.1` refers to the container itself. Use the actual reachable Agent 2 address.

3. Install the visual module on the **Zabbix frontend** server:

   ```bash
   sudo ./scripts/install_dynamic_status_cards.sh
   ```

   To provide a different directory:

   ```bash
   sudo ./scripts/install_dynamic_status_cards.sh \
     --modules-dir /usr/share/zabbix/modules
   ```

4. Go to **Administration → General → Modules → Scan directory** and enable **Cards de status dinâmicos**.
5. Import the [YAML template](template/template_web_service_monitoring.yaml) and link it to the host.
6. Override `{$WEB.SITES}` on the host and execute the discovery rule immediately.

Optional certificate test:

```bash
zabbix_get -s 127.0.0.1 -p 10050 \
  -k 'web.certificate.get[www.example.com,443,www.example.com]'
```

### Website list

Simple form:

```json
["site-a.example", "site-b.example", "site-c.example"]
```

Full form:

```json
[
  {
    "id": "customer-portal",
    "name": "Customer Portal",
    "host": "portal.example.com",
    "port": 443,
    "path": "/login",
    "status": 200
  }
]
```

| Field | Required | Description |
|---|---|---|
| `host` | yes | DNS name or IPv4 address without scheme, port, or path |
| `id` | no | Stable and unique identifier |
| `name` | no | Name shown in items, events, and cards |
| `port` | no | TLS port; default `443` |
| `path` | no | Path beginning with `/`; default `/` |
| `status` | no | Expected HTTP status; default `200` |

Use an explicit and stable `id` in the full form. Each `host:port` combination must appear only once. Zabbix user macros support up to 2048 characters; larger lists should use another discovery source.

### Collected data

The project creates the following data for each website:

- availability and HTTP status code;
- response time in milliseconds;
- last error message;
- certificate start and expiration dates;
- remaining days and health status;
- issuer, subject, CN, and SAN;
- detailed TLS validation result;
- response-time graph.

The request follows redirects and uses a small configurable header set to reduce false `403` responses. The default values are shown in the Portuguese section above.

### Dashboard and alerts

The **Visão geral dos sites** dashboard contains:

- a **Cards** page with one automatic card for each `site` tag value;
- a **Grades nativas** page with native Zabbix Honeycomb widgets and graphs.

Cards display availability, HTTP status, certificate state, remaining days, expiration date, and response time. Green means OK, yellow means warning, red means failure/expiration, and gray means no data.

Triggers cover unavailability, unexpected HTTP status, slow response, missing certificate data, upcoming expiration, expired certificates, and invalid certificates.

### Main macros

| Macro | Default | Purpose |
|---|---:|---|
| `{$WEB.SITES}` | `[]` | JSON website list |
| `{$WEB.DISCOVERY.INTERVAL}` | `10m` | Website-list refresh |
| `{$WEB.INTERVAL}` | `1m` | HTTP collection |
| `{$WEB.TIMEOUT}` | `10s` | Per-request timeout |
| `{$WEB.RESPONSE.WARN}` | `2000` | Response warning in ms |
| `{$WEB.RESPONSE.HIGH}` | `5000` | High response alarm in ms |
| `{$CERT.INTERVAL}` | `1h` | Certificate collection |
| `{$CERT.NODATA}` | `3h` | Missing TLS data threshold |
| `{$CERT.EXPIRY.WARN}` | `15` | Early warning in days |

### Local validation

Run the commands shown in the Portuguese validation section from the repository root. Always validate the import and dashboard on a staging installation running the same Zabbix 7.4 patch level used in production.

## Autor / Author

**Daniel Carvalho**

[LinkedIn](https://www.linkedin.com/in/daniel-ti/) · [danielrc10@gmail.com](mailto:danielrc10@gmail.com)

## Licença / License

[PolyForm Noncommercial 1.0.0](../../../LICENSE) — uso pessoal e não comercial é gratuito. Consultoria, MSP, integração comercial, revenda ou qualquer serviço pago exige autorização prévia de Daniel Carvalho pelo e-mail [danielrc10@gmail.com](mailto:danielrc10@gmail.com). Este projeto é independente e não possui afiliação oficial com a Zabbix LLC.

[PolyForm Noncommercial 1.0.0](../../../LICENSE) — personal and noncommercial use is free. Consulting, MSP, commercial integration, resale, or any paid service requires prior authorization from Daniel Carvalho at [danielrc10@gmail.com](mailto:danielrc10@gmail.com). This project is independent and is not officially affiliated with Zabbix LLC.
