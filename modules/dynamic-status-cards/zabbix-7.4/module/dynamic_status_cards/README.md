# Cards de status dinâmicos / Dynamic Status Cards

[Português](#português) · [English](#english)

## Português

Widget genérico para Zabbix 7.4 que agrupa itens por uma tag e reúne várias métricas no mesmo card. Ele usa a API interna do frontend, respeita as permissões do usuário conectado e não armazena credenciais adicionais.

### Instalação

Use o instalador do projeto ou copie esta pasta para o diretório `modules` do frontend:

```text
/usr/share/zabbix/modules/dynamic_status_cards
```

Depois acesse **Administração → Geral → Módulos**, escaneie o diretório e habilite **Cards de status dinâmicos**. Em containers, instale no container do frontend, não no Zabbix Server ou Agent 2.

### Configuração

- **Grupos de hosts:** carrega dinamicamente todos os hosts monitorados do grupo e de seus subgrupos.
- **Hosts:** filtro adicional opcional; vazio, permite que novos hosts do grupo apareçam automaticamente.
- **Tags de host:** filtro E/OU ou Ou aplicado aos hosts, como no Honeycomb nativo.
- **Etiquetas de itens:** filtro E/OU ou Ou aplicado antes dos padrões de cada métrica.
- **Tag usada para agrupar:** cada valor diferente gera um card; vazia, gera um card por host.
- **Métricas exibidas:** use **Adicionar métrica** e configure tudo pela interface, sem JSON.
- **Colunas automáticas:** habilitadas por padrão; usam a largura e a quantidade real de cards sem exigir configuração da quantidade de hosts.
- **Limite manual de colunas:** opção de uma a seis colunas, usada somente quando o ajuste automático é desmarcado.
- **Largura máxima do card:** padrão de 320 px, configurável entre 160 e 1000 px; não limita a quantidade de hosts.
- **Compactação vertical:** reduz automaticamente espaços e alturas em dois níveis; mantém a rolagem somente quando o conteúdo não cabe de forma legível.
- **Copiar métrica:** duplica toda a configuração em uma nova métrica pronta para ajustes.
- **Reordenar:** arraste a alça da primeira coluna para definir a ordem exibida nos cards.
- **Tipos de linha:** métrica, espaço vazio com altura de uma linha ou separador horizontal.
- **Nome da métrica:** pode ser ocultado no card e continua disponível no editor.
- **Indicador:** LED padrão, nenhum ou ícone SVG escolhido em um catálogo suspenso extensível.
- **Indicador do cabeçalho:** escolhe separadamente o LED ou SVG que representa o pior estado no topo do card.
- **Item ou padrão:** aceita itens selecionados e `*` como curinga no nome completo.
- **Item complementar:** exibe valor principal e complementar como `usado / total`.
- **Texto entre valores:** personaliza `/`, ` / `, ` de ` ou outro separador curto.
- **Percentual calculado:** pode avaliar a cor por `principal ÷ complementar × 100`.
- **Item alternativo de estado:** permite exibir um item e usar outro item do mesmo card para definir a cor.
- **Item de disponibilidade:** força crítico e pode mostrar `Indisponível` quando outro item, como `Ping Ativo`, vale `0`.
- **Limiares numéricos:** permitem escolher se valores maiores ou menores representam pior estado.
- **Valores exatos:** associam listas de valores aos estados OK, aviso e crítico.
- **Histórico:** segue o período global do dashboard e mostra o último valor desse intervalo com barra, gráfico ou resumo textual.
- **Eixo histórico:** mostra início, ponto médio e Agora; nome e resumo percentual são opcionais.
- **Cores históricas:** OK, aviso, crítico, indisponível e sem dados podem herdar a paleta ou ser personalizadas por métrica.
- **Cores:** OK, aviso, crítico e sem dados são personalizáveis no formulário principal.
- **Fundo dos cards:** automático, transparente, sólido ou gradiente configurável independentemente do widget.
- **Fundo do widget:** automático, transparente, sólido ou gradiente aplicado ao container completo; o transparente remove a moldura externa.
- **Texto:** automático com contraste, claro, escuro ou cor personalizada.
- **Estado geral:** apresenta o pior estado encontrado nas linhas.

Formatos: `automatico`, `mapa`, `numero`, `percentual_fracao`, `data` e `texto`. O formato `percentual_fracao` converte `0.4475` em `44,75%` e aplica a mesma escala aos limiares do item principal.

Estados atuais: `ok`, `aviso`, `critico`, `sem_dados` e `neutro`. A barra também distingue `indisponivel`.

A barra e o gráfico usam itens numéricos, consultam a retenção de histórico do Zabbix e compartilham até 180 blocos agregados. O gráfico colore os trechos com as mesmas regras e pode desenhar limiares numéricos. O resumo opcional calcula disponibilidade quando há um item de disponibilidade ou o percentual OK nos demais casos. Valor e indicador usam a última amostra dentro do período global selecionado. Intervalos acima de 24 horas podem ficar mais lentos.

O modo **Resumo histórico (somente texto)** oferece soma/média das amostras, mínimo/máximo, soma/média dos máximos diários e **aumento do contador no intervalo**. Esta última opção calcula máximo menos mínimo por dia e soma os dias, servindo para contadores acumulativos e intervalos parciais.

A pasta `assets/icons` contém 66 SVGs iniciais. Novos arquivos com nome seguro aparecem automaticamente nos seletores do cabeçalho e das métricas; revise SVGs de terceiros antes de instalá-los.

A configuração é salva em campos estruturados do dashboard. Configurações JSON criadas pela versão 1.0 são convertidas ao abrir e salvar o widget.

## English

Generic Zabbix 7.4 widget that groups items by a tag and combines multiple metrics in the same card. It uses the internal frontend API, respects the logged-in user's permissions, and stores no additional credentials.

### Installation

Use the project installer or copy this directory to the frontend `modules` directory:

```text
/usr/share/zabbix/modules/dynamic_status_cards
```

Then go to **Administration → General → Modules**, scan the directory, and enable **Cards de status dinâmicos**. For containers, install it in the frontend container, not in the Zabbix Server or Agent 2 container.

### Configuration

- **Host groups:** dynamically loads every monitored host from the group and its subgroups.
- **Hosts:** optional additional filter; empty allows new group members to appear automatically.
- **Host tags:** And/Or or Or host filtering, matching the native Honeycomb behavior.
- **Item tags:** And/Or or Or filtering applied before each metric pattern.
- **Grouping tag:** each distinct value creates one card; empty creates one card per host.
- **Displayed metrics:** use **Adicionar métrica** and configure everything in the GUI without JSON.
- **Automatic columns:** enabled by default; use the actual width and card count without requiring the host count to be configured.
- **Manual column limit:** optional one-to-six column limit used only when automatic adjustment is disabled.
- **Maximum card width:** defaults to 320 px, configurable from 160 to 1000 px; it does not limit the host count.
- **Vertical compaction:** automatically reduces spacing and heights in two stages; keeps scrolling only when content cannot fit readably.
- **Copy metric:** duplicates every setting into a new metric ready to be adjusted.
- **Reorder:** drag the first-column handle to define card display order.
- **Row types:** metric, one-row empty space, or horizontal separator.
- **Metric name:** can be hidden on the card while remaining available in the editor.
- **Indicator:** default LED, none, or an SVG selected from an extensible dropdown catalog.
- **Header indicator:** independently selects the LED or SVG representing the worst state at the top of each card.
- **Item or pattern:** accepts selected items and `*` as a wildcard in the full item name.
- **Complementary item:** displays primary and complementary values as `used / total`.
- **Text between values:** customizes `/`, ` / `, ` de `, or another short separator.
- **Calculated percentage:** can evaluate color using `primary ÷ complementary × 100`.
- **Alternate state item:** displays one item while another item in the same card determines the color.
- **Availability item:** forces critical and can display `Indisponível` when another item, such as `Ping Ativo`, is `0`.
- **Numeric thresholds:** support both higher-is-worse and lower-is-worse evaluation.
- **Exact values:** associate value lists with OK, warning, and critical states.
- **History:** follows the global dashboard period and displays that range's last value with a bar, graph, or text summary.
- **Historical axis:** shows start, temporal midpoint, and Agora; metric name and percentage summary are optional.
- **Historical colors:** OK, warning, critical, unavailable, and no data can inherit the palette or be customized per metric.
- **Colors:** OK, warning, critical, and no-data colors are configurable in the main form.
- **Card background:** automatic, transparent, solid, or configurable gradient independent from the widget.
- **Widget background:** automatic, transparent, solid, or gradient applied to the complete container; transparent mode removes the external frame.
- **Text:** automatic contrast, light, dark, or a custom color.
- **Overall state:** displays the worst state found among the rows.

Formats: `automatico`, `mapa`, `numero`, `percentual_fracao`, `data`, and `texto`. The `percentual_fracao` format converts `0.4475` into `44,75%` and applies the same scale to primary-item thresholds.

Current states: `ok`, `aviso`, `critico`, `sem_dados`, and `neutro`. The bar also distinguishes `indisponivel`.

The historical bar and graph use numeric items, read Zabbix history retention, and share at most 180 aggregated buckets. Graph segments use the same state rules and may display numeric thresholds. The optional summary calculates availability when an availability item exists, or the OK percentage otherwise. Value and indicator use the last sample inside the selected global period. Ranges longer than 24 hours may load more slowly.

The **Historical summary (text only)** mode supports sample sum/average, minimum/maximum, daily-maximum sum/average, and **counter increase over the range**. The latter calculates daily maximum minus minimum and sums the days, fitting cumulative counters and partial ranges.

The `assets/icons` directory ships with 66 SVG files. New safely named files automatically appear in the header and metric selectors; review third-party SVG files before installation.

The configuration is stored as structured dashboard fields. JSON configurations created with version 1.0 are converted when the widget is opened and saved.

## Exemplos / Examples

```text
Ping: maior é pior; aviso 50; crítico 150
Ping indisponível: item de disponibilidade = Ping Ativo; crítico = 0; texto = Indisponível
Certificado: menor é pior; aviso 15; crítico 0
Disponibilidade: OK = 1; crítico = 0
```

## Autor / Author

**Daniel Carvalho**

[LinkedIn](https://www.linkedin.com/in/daniel-ti/) · [danielrc10@gmail.com](mailto:danielrc10@gmail.com)

Licença / License: [PolyForm Noncommercial 1.0.0](LICENSE) · [Aviso de uso / Usage notice](NOTICE.md)

Uso pessoal e não comercial é gratuito. Consultoria ou qualquer uso comercial exige autorização prévia de [Daniel Carvalho](mailto:danielrc10@gmail.com).

Personal and noncommercial use is free. Consulting or any commercial use requires prior authorization from [Daniel Carvalho](mailto:danielrc10@gmail.com).
