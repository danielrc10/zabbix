# Projetos Zabbix / Zabbix Projects

[![Validar / Validate](https://github.com/danielrc10/zabbix/actions/workflows/validate.yml/badge.svg)](https://github.com/danielrc10/zabbix/actions/workflows/validate.yml)
[![Licença / License: PolyForm NC 1.0.0](https://img.shields.io/badge/licen%C3%A7a-PolyForm%20NC%201.0.0-blue.svg)](LICENSE)

[Português](#português) · [English](#english)

## Português

Repositório público com templates, módulos e automações para Zabbix. O objetivo é compartilhar soluções práticas, documentadas e reutilizáveis que possam ajudar outros profissionais de infraestrutura e monitoramento.

### Projetos

- [Template: monitoramento centralizado de sites e certificados](templates/web-service-monitoring/README.md) — monitora vários sites HTTPS em um único host lógico no Zabbix 7.4, com descoberta automática, validade de certificados, status HTTP, tempo de resposta, triggers e dashboard.
- [Módulo: Cards de status dinâmicos](modules/dynamic-status-cards/README.md) — widget genérico para montar cards com várias métricas, padrões de itens, limiares, valores exatos e cores configuráveis diretamente pela GUI.
- [Módulo: Rack dinâmico](modules/dynamic-rack/README.md) — widget visual de rack com equipamentos monitorados, prateleiras com vários dispositivos, itens passivos, múltiplos balões e integração dinâmica entre widgets.
- [Módulo: Conteúdo rico](modules/rich-content-widget/README.md) — widget para cabeçalhos, Markdown/HTML seguro, mídia transparente, macros do Zabbix, grid interno e ajuste proporcional sem barras de rolagem.

Cada projeto mantém implementações separadas em pastas `zabbix-<versão>` e identifica explicitamente as versões testadas. Os projetos deste repositório são independentes do projeto oficial Zabbix. Antes de usar em produção, valide-os em um ambiente de homologação compatível com a sua versão.

### Autor

**Daniel Carvalho**

[LinkedIn](https://www.linkedin.com/in/daniel-ti/) · [danielrc10@gmail.com](mailto:danielrc10@gmail.com)

### Licença

O uso pessoal e não comercial é gratuito conforme a [PolyForm Noncommercial 1.0.0](LICENSE) e o [aviso de uso](NOTICE.md). Uso profissional ou comercial, revenda, sublicenciamento, distribuição paga, consultoria, serviços gerenciados ou inclusão em produto ou serviço pago exigem autorização prévia por escrito.

Consultorias, MSPs, integradores e demais interessados em uso comercial devem entrar em contato com **Daniel Carvalho** pelo e-mail [danielrc10@gmail.com](mailto:danielrc10@gmail.com).

Este projeto possui código-fonte público (*source-available*), mas não é software de código aberto segundo a definição da OSI, pois a licença restringe usos comerciais.

---

## English

Public repository with Zabbix templates, modules, and automation. Its goal is to share practical, documented, and reusable solutions that may help other infrastructure and monitoring professionals.

### Projects

- [Template: centralized website and certificate monitoring](templates/web-service-monitoring/README.md#english) — monitors multiple HTTPS websites from a single logical host in Zabbix 7.4, including automatic discovery, certificate validity, HTTP status, response time, triggers, and a dashboard.
- [Module: Dynamic Status Cards](modules/dynamic-status-cards/README.md#english) — generic widget for building cards with multiple metrics, item patterns, thresholds, exact values, and GUI-configurable colors.
- [Module: Dynamic Rack](modules/dynamic-rack/README.md) — visual rack widget with monitored equipment, multi-device shelves, passive items, multiple callouts, and dynamic widget integration.
- [Module: Rich Content](modules/rich-content-widget/README.md) — widget for headings, safe Markdown/HTML, transparent media, Zabbix macros, internal grids, and proportional no-scroll fitting.

Each project keeps separate implementations under `zabbix-<version>` directories and explicitly identifies the tested versions. The projects in this repository are independent from the official Zabbix project. Validate them in a staging environment compatible with your version before using them in production.

### Author

**Daniel Carvalho**

[LinkedIn](https://www.linkedin.com/in/daniel-ti/) · [danielrc10@gmail.com](mailto:danielrc10@gmail.com)

### License

Personal and noncommercial use is free under the [PolyForm Noncommercial 1.0.0](LICENSE) and the [usage notice](NOTICE.md). Professional or commercial use, resale, sublicensing, paid distribution, consulting, managed services, or incorporation into a paid product or service requires prior written authorization.

Consultancies, MSPs, integrators, and anyone interested in commercial use must contact **Daniel Carvalho** at [danielrc10@gmail.com](mailto:danielrc10@gmail.com).

This project is source-available, but it is not open-source software under the OSI definition because its license restricts commercial uses.
