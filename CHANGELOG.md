# Histórico de alterações / Changelog

Todas as mudanças relevantes deste repositório serão documentadas aqui. O formato segue os princípios do [Keep a Changelog](https://keepachangelog.com/) e os projetos usam versionamento semântico quando aplicável.

All notable changes to this repository will be documented here. The format follows the principles of [Keep a Changelog](https://keepachangelog.com/), and projects use semantic versioning when applicable.

## [Unreleased]

### Adicionado / Added

- Estrutura pública e bilíngue do repositório.
- Public bilingual repository structure.
- Template de monitoramento centralizado de sites para Zabbix 7.4.
- Centralized website monitoring template for Zabbix 7.4.
- Widget genérico `dynamic_status_cards` para o frontend Zabbix.
- Generic `dynamic_status_cards` widget for the Zabbix frontend.

### Alterado / Changed

- Licença alterada de MIT para PolyForm Noncommercial 1.0.0; uso comercial e consultoria passam a exigir autorização prévia.
- License changed from MIT to PolyForm Noncommercial 1.0.0; commercial and consulting use now requires prior authorization.
- Implementação separada em `zabbix-7.4`, estabelecendo uma pasta própria para cada versão testada do Zabbix.
- Implementation moved under `zabbix-7.4`, establishing a dedicated directory for each tested Zabbix version.
- Indicadores dos cards corrigidos: HTTP 200 em verde e data de expiração herdando o estado do certificado.
- Card indicators fixed: HTTP 200 is green and the expiration date inherits certificate health.
