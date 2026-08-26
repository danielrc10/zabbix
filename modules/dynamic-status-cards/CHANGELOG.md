# Histórico de versões / Changelog

## 1.16.0 — 2026-08-26

### Português

- Amplia o resumo histórico textual com mínimo e máximo do período.
- Adiciona soma e média dos maiores valores de cada dia para contadores acumulados que reiniciam diariamente.
- Mantém soma e média de todas as amostras para itens incrementais e medições contínuas.
- Alinha os blocos diários ao fuso horário do frontend Zabbix e inclui o dia atual.
- Usa a agregação nativa por intervalo do Zabbix para obter máximos diários sem carregar todas as amostras brutas.
- Documenta exemplos para ligações, ataques por intervalo, tráfego, latência, CPU e temperatura.

### English

- Extends the text-only historical summary with period minimum and maximum.
- Adds sum and average of daily maxima for cumulative counters that reset every day.
- Preserves all-sample sum and average for incremental items and continuous measurements.
- Aligns daily buckets with the Zabbix frontend time zone and includes the current day.
- Uses Zabbix native interval aggregation to obtain daily maxima without loading all raw samples.
- Documents examples for calls, interval attacks, traffic, latency, CPU, and temperature.

## 1.15.0 — 2026-08-26

### Português

- Adiciona **Resumo histórico (somente texto)** aos modos de exibição de métricas.
- Permite calcular **Soma das amostras** ou **Média das amostras** no período histórico configurado.
- Reutiliza os blocos agregados do Zabbix e pondera os resultados pela quantidade real de amostras, sem uma segunda consulta ao histórico.
- Preserva formato e unidade do item no resultado textual.
- Mantém o resumo informativo fora da avaliação de cor da linha e do estado geral do card.
- Documenta que a soma representa amostras armazenadas e pode repetir contadores acumulados coletados várias vezes.

### English

- Adds **Historical summary (text only)** to the metric display modes.
- Supports **Sample sum** or **Sample average** over the configured historical period.
- Reuses Zabbix aggregated buckets and weights results by the actual sample count, without a second history query.
- Preserves item formatting and units in the text result.
- Keeps the informational summary out of row-color evaluation and the card's overall state.
- Documents that sums represent stored samples and may repeat cumulative counters collected multiple times.

## 1.14.0 — 2026-08-25

### Português

- Adiciona escolha explícita entre criar um card por host ou por item encontrado.
- Permite informar padrões como `Arquivos*` para transformar cada item descoberto em um card independente.
- Usa o próprio item gerador como contexto das métricas e mantém itens auxiliares vinculados ao mesmo host.
- Substitui “Mostrar o nome do host” por rótulos principal e secundário configuráveis.
- Resolve `{CARD.NAME}`, macros `{HOST.*}`, `{ITEM.*}`, inventário e macros de usuário nos rótulos.

### English

- Adds an explicit choice between one card per host and one card per matching item.
- Accepts patterns such as `Arquivos*` to turn every discovered item into an independent card.
- Uses the generating item as metric context while keeping auxiliary items tied to the same host.
- Replaces “Show host name” with configurable primary and secondary labels.
- Resolves `{CARD.NAME}`, `{HOST.*}`, `{ITEM.*}`, inventory, and user macros in labels.

## 1.13.0 — 2026-08-25

### Português

- Setoriza o formulário principal em origem e criação dos cards, filtros, métricas, layout e aparência.
- Mantém abertas somente as seções essenciais e recolhe filtros, layout e aparência por padrão.
- Reorganiza o editor de métricas em item e valor, formatação e exibição, estado e disponibilidade e aparência.
- Usa os componentes recolhíveis nativos do Zabbix 7.4, preservando todas as configurações existentes.

### English

- Organizes the main form into card source and creation, filters, metrics, layout, and appearance.
- Keeps only essential sections expanded while filters, layout, and appearance start collapsed.
- Reorganizes the metric editor into item and value, formatting and display, state and availability, and appearance.
- Uses native Zabbix 7.4 collapsible components while preserving every existing setting.

## 1.12.0 — 2026-08-24

### Português

- Substitui a compactação vertical por um ajuste bidimensional contínuo baseado na largura, altura e quantidade real de cards.
- Escolhe automaticamente linhas e colunas pela maior escala legível e usa a proporção do espaço como desempate, de forma semelhante ao Honeycomb.
- Faz as linhas ocuparem toda a altura disponível, eliminando a faixa vazia abaixo dos cards.
- Reduz proporcionalmente o conteúdo interno quando necessário e desativa as rolagens horizontal e vertical do widget.
- Mantém a largura máxima configurável para impedir que poucos cards fiquem excessivamente largos.

### English

- Replaces staged vertical compaction with continuous two-dimensional fitting based on the widget width, height, and actual card count.
- Automatically selects rows and columns by the largest readable scale and uses the available-space aspect ratio as a tie-breaker, similarly to Honeycomb.
- Makes rows occupy the full available height, eliminating the empty strip below cards.
- Proportionally reduces inner content when required and disables both horizontal and vertical widget scrolling.
- Preserves the configurable maximum width so that a few cards do not become excessively wide.

## 1.11.1 — 2026-08-20

### Português

- Corrige o fundo externo do widget no DOM real do Zabbix 7.4, aplicando-o ao cabeçalho e ao container de conteúdo que recebem o fundo do tema.
- Corrige também o modo transparente e a supressão da rolagem horizontal usando as classes oficiais `dashboard-grid-widget-*`.

### English

- Fixes the outer widget background in the actual Zabbix 7.4 DOM by applying it to the header and theme-painted contents container.
- Also fixes transparent mode and horizontal-overflow suppression using the official `dashboard-grid-widget-*` classes.

## 1.11.0 — 2026-08-20

### Português

- Adiciona largura máxima configurável por card, com padrão de 320 px, sem interromper a inclusão automática de hosts do grupo.
- Mantém os cards alinhados no início do widget quando existem poucos resultados, evitando cards excessivamente largos.
- Separa o fundo dos cards do fundo do widget, preservando as configurações de cards existentes.
- Aplica o fundo automático, transparente, sólido ou gradiente ao container completo do widget Zabbix.
- No modo transparente, remove também fundo, borda, contorno e sombra externos para deixar somente os cards visíveis.

### English

- Adds a configurable per-card maximum width, defaulting to 320 px, without interrupting automatic host-group expansion.
- Keeps cards aligned to the widget start when only a few results exist, preventing excessively wide cards.
- Separates card backgrounds from the widget background while preserving existing card settings.
- Applies automatic, transparent, solid, or gradient backgrounds to the complete Zabbix widget container.
- Transparent mode also removes the external background, border, outline, and shadow so that only cards remain visible.

## 1.10.2 — 2026-08-20

### Português

- Adiciona compactação vertical automática em dois níveis quando os cards ultrapassam a altura disponível do widget.
- Reduz progressivamente cabeçalho, espaçamentos, linhas, separadores, barras e gráficos sem ocultar métricas.
- Preserva a rolagem vertical quando nem o modo mais denso cabe, evitando texto ilegível ou conteúdo cortado.

### English

- Adds two-stage automatic vertical compaction when cards exceed the widget's available height.
- Progressively reduces headers, spacing, rows, separators, bars, and graphs without hiding metrics.
- Preserves vertical scrolling when even the densest mode cannot fit, preventing unreadable text or clipped content.

## 1.10.1 — 2026-08-20

### Português

- Substitui as consultas CSS de contêiner por um controlador JavaScript integrado ao ciclo de redimensionamento do Zabbix 7.4.
- Adiciona o modo automático, habilitado por padrão, que calcula as colunas pela largura real disponível e pela quantidade real de cards.
- Mantém de uma a seis colunas somente como limite manual opcional.
- Faz um único card ocupar toda a largura de um widget estreito, sem reservar colunas vazias que espremiam seu conteúdo.
- Remove a altura mínima que podia criar uma barra de rolagem vertical em uma única linha de cards.

### English

- Replaces CSS container queries with a JavaScript controller integrated into the Zabbix 7.4 resize lifecycle.
- Adds an automatic mode, enabled by default, that calculates columns from the actual available width and card count.
- Keeps one to six columns only as an optional manual limit.
- Lets a single card use the full width of a narrow widget instead of reserving empty columns that squeezed its content.
- Removes the minimum height that could create a vertical scrollbar for a single card row.

## 1.10.0 — 2026-08-20

### Português

- Torna o grid responsivo à largura do próprio widget, independentemente do tamanho da janela do navegador.
- Trata a quantidade configurada como máximo de colunas e reduz automaticamente para quatro, três, duas ou uma coluna.
- Remove as larguras mínimas rígidas, compacta espaçamentos em widgets estreitos e elimina a rolagem horizontal dos cards.
- Adiciona câmera de segurança/DVR, gravador DVR, computador, notebook e cluster; o ícone de servidor existente continua disponível.
- Amplia a biblioteca inicial de 61 para 66 SVGs.

### English

- Makes the grid responsive to the widget's own width, independently from the browser viewport size.
- Treats the configured value as a maximum column count and automatically reduces it to four, three, two, or one column.
- Removes rigid minimum widths, compacts spacing in narrow widgets, and eliminates horizontal card scrolling.
- Adds CCTV/DVR camera, DVR recorder, desktop computer, laptop, and cluster; the existing server icon remains available.
- Expands the bundled library from 61 to 66 SVG files.

## 1.9.0 — 2026-08-20

### Português

- Permite escolher no formulário principal o indicador do estado geral exibido no cabeçalho dos cards.
- O indicador do cabeçalho usa o mesmo catálogo suspenso e acompanha as cores de OK, aviso, crítico e sem dados.
- Adiciona câmera, tomada, WWW, espião/inspeção, setas em quatro direções, Windows, macOS e Linux.
- Amplia a biblioteca inicial de 50 para 61 SVGs; o ícone de memória já existente permanece disponível.

### English

- Adds a main-form selector for the overall-state indicator displayed in card headers.
- The header indicator uses the same dropdown catalog and follows OK, warning, critical, and no-data colors.
- Adds camera, power plug, WWW, spy/inspection, four arrow directions, Windows, macOS, and Linux.
- Expands the bundled library from 50 to 61 SVG files; the existing memory icon remains available.

## 1.8.2 — 2026-08-20

### Português

- Centraliza o LED padrão e todos os indicadores SVG na mesma coluna dos cards.
- Evita desalinhamento visual ao combinar ícones de dimensões diferentes, como coração e LED.

### English

- Centers the default LED and every SVG indicator in the same card column.
- Prevents visual misalignment when combining differently sized icons, such as heart and LED.

## 1.8.1 — 2026-08-20

### Português

- Corrige as miniaturas e os indicadores SVG incorporando a imagem como `data:` validada.
- Substitui o catálogo sempre aberto por um seletor suspenso compacto.
- Mantém miniatura, nome do arquivo, rolagem interna, fechamento externo e tecla Escape.

### English

- Fixes SVG previews and indicators by embedding the image as a validated `data:` source.
- Replaces the always-open catalog with a compact dropdown picker.
- Preserves previews, filenames, internal scrolling, outside-click closing, and Escape handling.

## 1.8.0 — 2026-08-20

### Português

- Adiciona modos com gráfico histórico de linha e área, usando o mesmo período, limiares e cores da barra.
- Colore os trechos do gráfico pelo estado de cada bloco, desenha limiares numéricos e preserva lacunas sem dados.
- Adiciona seleção visual de indicador por métrica com miniatura e nome do arquivo.
- Inclui uma biblioteca inicial de 50 ícones SVG e descoberta automática de novos arquivos em `assets/icons`.
- Mantém o LED como padrão e permite ocultar completamente o indicador.

### English

- Adds line-and-area historical graph modes using the same period, thresholds, and colors as the status bar.
- Colors graph segments by bucket state, draws numeric thresholds, and preserves no-data gaps.
- Adds a visual per-metric indicator selector with filename and preview.
- Includes an initial library of 50 SVG icons and automatic discovery of new files under `assets/icons`.
- Keeps the LED as the default and allows the indicator to be completely hidden.

## 1.7.0 — 2026-08-20

### Português

- Corrige a persistência da opção de mostrar ou ocultar o nome da métrica.
- Permite reordenar as linhas por arrastar e soltar.
- Adiciona linhas de espaço vazio e separadores horizontais.
- Permite personalizar o texto entre o valor principal e o complementar.
- Esclarece os controles independentes do nome e do percentual acima da barra histórica.

### English

- Fixes persistence of the show-or-hide metric name setting.
- Adds drag-and-drop row ordering.
- Adds empty spacer rows and horizontal separators.
- Allows custom text between primary and complementary values.
- Clarifies the independent name and percentage controls above historical bars.

## 1.6.1 — 2026-08-19

### Português

- Adiciona **Copiar** à lista de métricas e abre uma nova métrica com toda a configuração original preenchida.
- Adiciona o formato **Percentual (fração × 100)** para exibir `0.4475` como `44,75%`.
- Aplica a escala percentual aos limiares atuais e históricos baseados no item principal.

### English

- Adds **Copiar** to the metric list and opens a new metric prefilled with every original setting.
- Adds the **Percentual (fração × 100)** format to display `0.4475` as `44,75%`.
- Applies the percentage scale to current and historical primary-item thresholds.

## 1.6.0 — 2026-08-19

### Português

- Adiciona item complementar opcional para exibir valores como `usado / total`.
- Adiciona avaliação de cor pelo percentual `principal ÷ complementar × 100`.
- Permite ocultar o nome de cada métrica no card.
- Torna o modo somente histórico realmente compacto, sem valor atual nem LED.
- Torna o resumo percentual histórico opcional e remove sua duplicação visual.
- Exibe início, ponto médio e Agora no eixo da barra.
- Altera os padrões de novas métricas históricas para 1 dia e resumo desativado.
- Exibe aviso de desempenho para períodos acima de 24 horas.

### English

- Adds an optional complementary item for `used / total` values.
- Adds color evaluation through `primary ÷ complementary × 100`.
- Allows each metric name to be hidden on the card.
- Makes historical-only mode truly compact, without current value or LED.
- Makes the historical percentage summary optional and removes visual duplication.
- Shows start, temporal midpoint, and Agora on the bar axis.
- Changes new historical metric defaults to 1 day with summary disabled.
- Displays a performance warning for periods longer than 24 hours.
