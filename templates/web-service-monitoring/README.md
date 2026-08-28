# Monitoramento de sites e certificados / Website and certificate monitoring

[Português](#português) · [English](#english)

## Português

Projeto de monitoramento centralizado de sites HTTPS, certificados TLS, status HTTP e tempo de resposta no Zabbix.

O módulo independente [Cards de status dinâmicos](https://github.com/danielrc10/zabbix-dynamic-status-cards) é uma integração opcional. A coleta, os alertas e os gráficos do template não dependem da instalação desse módulo no frontend.

### Versões testadas

Cada versão do Zabbix possui uma pasta própria. Isso evita que alterações necessárias para uma versão futura quebrem uma implantação já validada.

| Versão do Zabbix | Estado | Documentação e arquivos |
|---|---|---|
| 7.4 | Testada | [Abrir versão 7.4](zabbix-7.4/README.md) |

Use somente a pasta correspondente à sua versão do Zabbix. Uma versão não listada não foi testada e pode exigir adaptações no template ou no módulo do frontend.

## English

Centralized monitoring project for HTTPS websites, TLS certificates, HTTP status, and response time in Zabbix.

The independent [Dynamic Status Cards](https://github.com/danielrc10/zabbix-dynamic-status-cards) module is an optional integration. Template collection, alerts, and graphs do not depend on installing this module in the frontend.

### Tested versions

Each Zabbix version has its own directory. This prevents changes required by a future version from breaking an already validated deployment.

| Zabbix version | Status | Documentation and files |
|---|---|---|
| 7.4 | Tested | [Open version 7.4](zabbix-7.4/README.md#english) |

Use only the directory matching your Zabbix version. An unlisted version has not been tested and may require template or frontend module changes.
