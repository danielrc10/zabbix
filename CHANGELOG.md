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
- Editor visual de métricas com limiares, valores exatos, formatos e cores configuráveis no módulo 1.1.0.
- Visual metric editor with thresholds, exact values, formats, and configurable colors in module 1.1.0.
- Fundo automático, transparente, sólido ou gradiente e cor de texto configurável no módulo 1.2.0.
- Automatic, transparent, solid, or gradient backgrounds and configurable text color in module 1.2.0.
- Altura natural dos cards restaurada e seção Aparência sempre visível no módulo 1.2.1.
- Natural card height restored and Appearance section kept always visible in module 1.2.1.
- Seleção dinâmica por grupos de hosts e subgrupos no módulo 1.3.0.
- Dynamic host-group and subgroup selection in module 1.3.0.
- Filtros por tags de host e etiquetas de itens, além de item de disponibilidade por métrica, no módulo 1.4.0.
- Host-tag and item-tag filters plus a per-metric availability item in module 1.4.0.
- Barras históricas por métrica, período configurável, percentual, tooltips e paleta de cinco estados no módulo 1.5.0.
- Per-metric historical bars, configurable period, percentage, tooltips, and a five-state palette in module 1.5.0.
- Widget `rich_content` para cabeçalhos, Markdown/HTML seguro, mídia transparente, macros e layout proporcional sem rolagem.
- `rich_content` widget for headings, safe Markdown/HTML, transparent media, macros, and proportional no-scroll layouts.

### Alterado / Changed

- Espaçamento vertical dos cards compactado no módulo 1.5.1 para caber na menor altura útil do grid sem gerar rolagem incidental.
- Card vertical spacing compacted in module 1.5.1 to fit the smallest useful dashboard-grid height without incidental scrolling.
- Licença alterada de MIT para PolyForm Noncommercial 1.0.0; uso comercial e consultoria passam a exigir autorização prévia.
- License changed from MIT to PolyForm Noncommercial 1.0.0; commercial and consulting use now requires prior authorization.
- Implementação separada em `zabbix-7.4`, estabelecendo uma pasta própria para cada versão testada do Zabbix.
- Implementation moved under `zabbix-7.4`, establishing a dedicated directory for each tested Zabbix version.
- Indicadores dos cards corrigidos: HTTP 200 em verde e data de expiração herdando o estado do certificado.
- Card indicators fixed: HTTP 200 is green and the expiration date inherits certificate health.
- Templates e módulos separados em projetos e releases independentes.
- Templates and modules split into independent projects and releases.
- Configuração do widget migrada de um campo JSON para campos estruturados, preservando conversão da versão 1.0.
- Widget configuration migrated from one JSON field to structured fields while preserving version 1.0 conversion.
- Preset do template Web Service Monitoring atualizado para o editor visual do módulo 1.1.0.
- Web Service Monitoring template preset updated for the module 1.1.0 visual editor.
