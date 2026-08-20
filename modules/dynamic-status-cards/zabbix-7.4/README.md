# Cards de status dinâmicos — Zabbix 7.4 / Dynamic Status Cards — Zabbix 7.4

[Português](#português) · [English](#english)

> Zabbix 7.4 · Módulo 1.10.1 · Grid responsivo · Seletor suspenso · 66 ícones extensíveis · Limiares

## Português

Widget genérico que agrupa itens por uma tag e monta automaticamente um card para cada valor dessa tag. As métricas de cada card são configuradas pela GUI, sem escrever JSON.

### Arquivos

- [Pacote ZIP](dist/dynamic_status_cards.zip)
- [Código-fonte do módulo](module/dynamic_status_cards/)
- [Instalador opcional](scripts/install_dynamic_status_cards.sh)
- [Validador](tools/validate_module.rb)

### Instalação

O instalador altera somente o diretório de módulos do **frontend Zabbix**. Ele valida os arquivos PHP, cria backup da versão anterior e não modifica banco de dados, Server, Proxy, Agent ou hosts.

Revise o script e simule primeiro:

```bash
./scripts/install_dynamic_status_cards.sh --dry-run
```

Depois instale:

```bash
sudo ./scripts/install_dynamic_status_cards.sh
```

Também é possível extrair [o ZIP](dist/dynamic_status_cards.zip) manualmente em `/usr/share/zabbix/modules`. Depois acesse **Administração → Geral → Módulos → Escanear diretório** e habilite **Cards de status dinâmicos**.

### Configuração pela GUI

Ao editar o widget, selecione um ou mais **Grupos de hosts** e use **Adicionar métrica**. O campo **Hosts** é opcional e restringe o resultado aos hosts escolhidos dentro desses grupos. Se ficar vazio, todos os hosts monitorados dos grupos são carregados automaticamente, inclusive os que forem adicionados depois. Os subgrupos também são incluídos.

Como no Honeycomb nativo, **Tags de host** e **Etiquetas de itens** aceitam avaliação **E/OU** ou **Ou**. Os filtros são cumulativos: grupo, hosts e tags de host escolhem os hosts; etiquetas de itens reduzem os itens desses hosts; por fim, os padrões definidos em cada métrica escolhem o valor exibido no card.

Escolha o agrupamento e configure as métricas. Se a tag de agrupamento ficar vazia, o widget cria um card por host. Cada métrica possui:

- nome exibido;
- opção de ocultar o nome no card sem perder a identificação no editor;
- indicador de estado configurável por métrica, com LED, biblioteca de ícones ou nenhum indicador;
- um ou mais itens exatos ou padrões com `*`;
- item complementar opcional exibido após o valor principal, como `23,17 GB / 31,94 GB`;
- avaliação opcional por percentual calculado: `principal ÷ complementar × 100`;
- formato automático, mapeamento, número, data ou texto;
- item alternativo opcional para determinar somente a cor;
- item de disponibilidade opcional que força crítico quando, por exemplo, `Ping Ativo = 0`;
- avaliação sem regra, por limiares numéricos ou por valores exatos;
- comportamento quando não houver dados.

Cada linha da lista possui **Editar**, **Copiar** e **Remover**. **Copiar** abre uma nova métrica com toda a configuração da original — itens, formato, limiares, disponibilidade, histórico e cores — sem substituir a origem. Altere somente o nome e os padrões necessários e clique em **Adicionar cópia**.

Use a alça na primeira coluna para arrastar e reordenar as linhas. A ordem visual é salva com o widget e aplicada igualmente a todos os cards.

O campo **Tipo de linha** oferece:

- **Métrica:** exibe e avalia um item normalmente;
- **Espaço vazio:** reserva a altura de uma linha sem alterar o estado do card;
- **Separador horizontal:** cria uma divisão visual entre grupos de métricas.

Em **Item complementar**, o campo **Texto entre os valores** aceita qualquer texto curto. Use `/` para `10/100`, ` / ` para `10 / 100` ou ` de ` para `10 de 100`.

Para itens que armazenam percentual como fração, selecione **Percentual (fração × 100)**. Assim, `0.4475` é exibido como `44,75%` com duas casas decimais. O número de casas é configurável; os limiares atuais e históricos baseados no item principal também usam a escala percentual. O formato explícito evita alterar automaticamente itens cujo valor correto realmente seja `0,4475%`.

**Nome da métrica no card** agora usa uma escolha explícita entre **Mostrar** e **Ocultar**. Para deixar uma linha histórica somente com a barra e o eixo, selecione **Ocultar** e desmarque **Mostrar 100,00% disponibilidade ou OK**.

As cores de OK, aviso, crítico e sem dados são configuradas no formulário principal do widget. Em **Indicador do cabeçalho**, escolha também o LED ou qualquer SVG da biblioteca para representar o estado geral no topo de todos os cards. Esse indicador é independente dos ícones das métricas e recebe a cor do pior estado encontrado no card.

**Ajustar colunas automaticamente** vem habilitado por padrão e funciona como no Honeycomb: a cada redimensionamento ou atualização do grupo, o controlador calcula quantos cards cabem na largura real do widget. Não é necessário informar a quantidade de hosts nem acertar a quantidade de colunas. Um único card ocupa toda a largura; vários cards são redistribuídos antes de ficarem espremidos. Desmarque a opção somente se quiser aplicar o **Limite manual de colunas**, entre uma e seis.

### Histórico em barra ou gráfico

Na janela **Editar métrica**, o campo **Modo de exibição** permite mostrar somente o valor atual, combinar o valor com uma barra ou gráfico histórico, ou mostrar somente a visualização histórica. Nos modos históricos sem valor, a linha atual e seu indicador não são renderizados; também é possível ocultar o nome e o resumo para deixar somente a barra ou gráfico e o eixo temporal.

O período é configurável de 1 a 90 dias. Novas métricas usam **1 dia** por padrão e iniciam com o resumo percentual desativado. Configurações já salvas preservam os valores anteriores. O eixo mostra o início, o ponto médio do período e **Agora**; até 24 horas usa hora e minuto, e períodos maiores também mostram dia e mês.

A barra usa o histórico numérico nativo do Zabbix e divide o período automaticamente em até 180 blocos. Cada bloco recebe a pior condição observada conforme os limiares ou valores exatos da própria métrica. Quando há um **Item de disponibilidade**, seus valores críticos são exibidos como **Indisponível**; períodos sem amostras ficam como **Sem dados**.

O gráfico reutiliza exatamente esses blocos, sem uma segunda consulta. A curva usa a média agregada; cada trecho e sua área recebem a cor do estado calculado para o bloco. Limiares numéricos de aviso e crítico aparecem como linhas tracejadas quando usam a mesma escala do item exibido. Lacunas no histórico interrompem a curva em vez de produzir um falso valor zero.

As cinco cores do histórico podem herdar a paleta do widget ou ser personalizadas por métrica:

- OK;
- aviso;
- crítico;
- indisponível, preto por padrão;
- sem dados.

O resumo opcional mostra **disponibilidade** quando existe um item de disponibilidade ou o percentual de blocos **OK** nos demais casos. Blocos sem dados não entram no denominador. Passe o mouse sobre uma faixa para ver período, estado, mínimo, média e máximo.

A visualização histórica exige um item numérico para determinar o estado. Ela consulta somente a tabela de histórico, não as tendências; portanto, o período realmente visível depende da retenção configurada para o item. A cor atual do indicador e do card continua sendo calculada pela amostra mais recente: uma falha antiga aparece no histórico, mas não mantém o card vermelho depois da recuperação.

Períodos acima de 24 horas podem aumentar significativamente o tempo de carregamento, pois o histórico é consultado novamente a cada atualização do dashboard. A interface exibe esse aviso ao configurar mais de 1 dia.

### Indicadores e biblioteca de ícones

Cada métrica e o cabeçalho dos cards podem usar o LED padrão, omitir o indicador ou selecionar um SVG. O campo ocupa somente uma linha; ao clicar, abre um catálogo suspenso com miniatura e nome do arquivo. A instalação inclui 66 opções para infraestrutura, disponibilidade, energia, usuários, telefonia, segurança, vídeo, armazenamento, aplicações, direções e sistemas operacionais.

Para ampliar a biblioteca, copie um SVG confiável para `module/dynamic_status_cards/assets/icons` usando letras, números, hífen ou sublinhado no nome. Ao reabrir o editor, o arquivo aparece automaticamente com miniatura e nome. Use preferencialmente `viewBox="0 0 24 24"` e formas pretas; o widget usa a imagem como máscara e aplica as cores de OK, aviso, crítico ou sem dados. Revise sempre SVGs de terceiros antes de instalá-los.

### Valor complementar e percentual calculado

Selecione um **Item complementar após o valor** para montar linhas genéricas como memória usada/total, disco usado/total ou cota consumida/total. O valor complementar conserva a unidade e a formatação automática do próprio item.

Se **Usar valor principal ÷ complementar × 100 para determinar a cor** estiver marcado, os limites numéricos passam a ser interpretados como percentuais. Exemplo: aviso `80` e crítico `90` deixam `30 GB / 32 GB` vermelho, mas `30 GB / 128 GB` verde. O item complementar deve ser numérico e maior que zero. Enquanto essa opção estiver ativa, ela tem prioridade sobre o item alternativo de estado.

No histórico, o percentual é aproximado por bloco a partir dos valores agregados dos dois itens. Para obter uma barra representativa, mantenha os dois itens com intervalos de coleta e retenção compatíveis.

### Aparência

Na seção **Aparência**, escolha o indicador do cabeçalho e configure as cores do estado. O fundo pode acompanhar automaticamente o tema do Zabbix, ficar transparente, usar uma cor sólida ou um gradiente. No modo gradiente, escolha as duas cores e a direção horizontal, diagonal ou vertical.

A cor do texto pode ser automática, clara, escura ou personalizada. O modo automático herda o tema quando o fundo também é automático ou transparente. Para fundos sólidos e gradientes, o widget calcula uma cor clara ou escura com contraste adequado. Os cards recebem uma camada discreta para preservar a leitura; LEDs, bordas de estado e a barra de título nativa do Zabbix não são recoloridos.

#### Quanto maior, pior

Exemplo de ping:

```text
Limite de aviso: 50
Limite crítico: 150
Resultado: ≤ 50 verde; > 50 e ≤ 150 amarelo; > 150 vermelho
```

#### Quanto menor, pior

Exemplo de dias restantes do certificado:

```text
Limite de aviso: 15
Limite crítico: 0
Resultado: > 15 verde; > 0 e ≤ 15 amarelo; ≤ 0 vermelho
```

#### Valores exatos

Exemplo de disponibilidade:

```text
Valores OK: 1
Valores críticos: 0
Estado para outros valores: Crítico
```

#### Evitar `0 ms` verde quando o link estiver fora

Na métrica **Tempo Resposta**, mantenha os limiares normais e configure:

```text
Item de disponibilidade: Ping Ativo
Valores que indicam indisponibilidade: 0
Texto quando indisponível: Indisponível
```

Quando o ping for `0`, a linha fica vermelha e mostra **Indisponível**. Quando o ping for `1`, o tempo de resposta volta a usar seus próprios limiares. Se o item de disponibilidade não tiver dados, a linha fica no estado **sem dados**, evitando um falso verde.

### Agrupamento

Se a tag escolhida for `site`, cada valor distinto de `site` gera um card. Se o campo ficar vazio, cada host gera um card. Dentro dele, os padrões configurados localizam as métricas pertencentes ao mesmo grupo.

Para itens criados por LLD, use padrões como `[*] Web: Response time`, pois os nomes resolvidos ainda não existem quando o dashboard do template é criado.

### Integração opcional com Web Service Monitoring

O [template Web Service Monitoring](../../../templates/web-service-monitoring/zabbix-7.4/README.md) inclui um dashboard pré-configurado para este widget. O template coleta dados e gera alertas sem o módulo; somente a página personalizada de cards depende dele.

### Atualização da versão 1.0

Dashboards existentes que armazenam as linhas em JSON continuam aceitos. Ao abrir e salvar o widget com a versão 1.1, a configuração é convertida para campos estruturados automaticamente. Faça backup do dashboard antes de atualizar em produção.

### Validação local

Na raiz do repositório:

```bash
ruby modules/dynamic-status-cards/zabbix-7.4/tools/validate_module.rb

find modules/dynamic-status-cards/zabbix-7.4/module/dynamic_status_cards \
  -name '*.php' -print0 | xargs -0 -n1 php -l

bash -n modules/dynamic-status-cards/zabbix-7.4/scripts/install_dynamic_status_cards.sh
```

---

## English

Generic widget that groups items by a tag and automatically creates one card for each tag value. Card metrics are configured through the GUI without writing JSON.

### Files

- [ZIP package](dist/dynamic_status_cards.zip)
- [Module source](module/dynamic_status_cards/)
- [Optional installer](scripts/install_dynamic_status_cards.sh)
- [Validator](tools/validate_module.rb)

### Installation

The installer changes only the **Zabbix frontend** modules directory. It validates PHP files, backs up the previous version, and does not modify the database, Server, Proxy, Agent, or monitored hosts.

Review the script and run a dry run first:

```bash
./scripts/install_dynamic_status_cards.sh --dry-run
```

Then install it:

```bash
sudo ./scripts/install_dynamic_status_cards.sh
```

You may also extract the [ZIP package](dist/dynamic_status_cards.zip) manually under `/usr/share/zabbix/modules`. Then go to **Administration → General → Modules → Scan directory** and enable **Cards de status dinâmicos**.

### GUI configuration

While editing the widget, select one or more **Grupos de hosts**. The **Hosts** field is optional and narrows the result to selected hosts inside those groups. When Hosts is empty, every monitored host in the selected groups is loaded automatically, including hosts added later. Subgroups are included as well.

Like the native Honeycomb widget, **host tags** and **item tags** support **And/Or** or **Or** evaluation. Filters are cumulative: groups, hosts, and host tags select hosts; item tags narrow their items; each metric pattern then selects the value displayed in the card.

Choose the grouping and use **Adicionar métrica**. An empty grouping tag creates one card per host. Each metric supports a display name that can be hidden on the card, exact items or wildcard patterns, an optional complementary value, formatting, an optional alternate state item, an optional availability item, numeric thresholds or exact values, and missing-data behavior.

Each list row provides **Editar**, **Copiar**, and **Remover**. **Copiar** opens a new metric containing every setting from the original — items, format, thresholds, availability, history, and colors — without replacing it. Change only the required name and patterns, then select **Adicionar cópia**.

Use the handle in the first column to drag and reorder rows. The visual order is stored with the widget and applied equally to every card.

The **Tipo de linha** field supports normal metrics, empty space that reserves one row without changing card state, and horizontal separators between metric sections.

For complementary items, **Texto entre os valores** accepts any short text. Use `/` for `10/100`, ` / ` for `10 / 100`, or ` de ` for `10 de 100`.

For items that store a percentage as a fraction, select **Percentual (fração × 100)**. For example, `0.4475` becomes `44,75%` when two decimal places are configured. Current and historical thresholds based on the primary item use the same percentage scale. Making this format explicit prevents legitimate `0.4475%` values from being changed automatically.

**Nome da métrica no card** now uses an explicit **Mostrar** or **Ocultar** selection. To render only the historical bar and time axis, select **Ocultar** and disable **Mostrar 100,00% disponibilidade ou OK**.

OK, warning, critical, and no-data colors are configured in the main widget form. **Indicador do cabeçalho** also selects the LED or any bundled SVG used for the overall state at the top of every card. This header indicator is independent from metric icons and receives the color of the worst state found in the card. Numeric thresholds support both **higher is worse** and **lower is worse** directions.

**Adjust columns automatically** is enabled by default and works like Honeycomb: on each resize or group update, the controller calculates how many cards fit the widget's actual width. There is no need to provide the host count or guess the column count. A single card uses the full width; multiple cards are redistributed before becoming squeezed. Disable the option only to apply the **Manual column limit**, from one to six.

### Historical bars and graphs

In **Edit metric**, **Display mode** can show only the current value, combine it with a historical bar or graph, or show only the historical visualization. Historical-only modes do not render the current value or its indicator; hiding the metric name and summary leaves only the bar or graph and time axis.

The period ranges from 1 to 90 days. New metrics default to **1 day** with the percentage summary disabled, while previously stored settings are preserved. The axis shows the start, temporal midpoint, and **Agora**; ranges up to 24 hours use hours and minutes, while longer ranges also include day and month.

The bar reads Zabbix's native numeric history and automatically divides the period into at most 180 buckets. Each bucket receives the worst condition detected by the metric's thresholds or exact-value rules. When an **availability item** is configured, its critical values are rendered as **Unavailable**; buckets without samples are rendered as **No data**.

The graph reuses those exact buckets without a second query. Its curve uses the aggregated average, while each segment and area receives the bucket's calculated state color. Numeric warning and critical thresholds are drawn as dashed lines when they use the displayed item's scale. History gaps break the curve instead of producing a false zero.

The five historical colors may inherit the widget palette or be customized per metric: OK, warning, critical, unavailable (black by default), and no data.

The optional summary shows **availability** when an availability item exists, or the percentage of **OK** buckets otherwise. No-data buckets are excluded from the denominator. Hover a strip to inspect its time range, state, minimum, average, and maximum.

The historical visualization requires a numeric item to determine state. It intentionally reads the history table rather than trends, so the visible range depends on item history retention. The current indicator and card color still use the latest sample: a past failure remains visible in history but does not keep a recovered card red.

Periods longer than 24 hours can significantly increase loading time because history is queried again on every dashboard refresh. The metric editor displays this warning whenever more than one day is selected.

### Indicators and icon library

Each metric and the card header may use the default LED, hide the indicator, or select an SVG. The field occupies a single line and opens a dropdown catalog with previews and filenames when clicked. The installation includes 66 icons covering infrastructure, availability, power, users, telephony, security, video, storage, applications, directions, and operating systems.

To extend the library, copy a trusted SVG file into `module/dynamic_status_cards/assets/icons` using letters, numbers, hyphens, or underscores in its name. Reopening the editor automatically displays its preview and filename. Prefer `viewBox="0 0 24 24"` and black shapes; the widget uses the image as a mask and applies OK, warning, critical, or no-data colors. Always review third-party SVG files before installation.

### Complementary value and calculated percentage

Select an **Item complementar após o valor** to build generic used/total rows for memory, disks, storage, or quotas. The complementary value keeps its own automatic Zabbix unit formatting.

When **Usar valor principal ÷ complementar × 100 para determinar a cor** is enabled, numeric thresholds are interpreted as percentages. For example, warning `80` and critical `90` make `30 GB / 32 GB` critical while `30 GB / 128 GB` remains OK. The complementary item must be numeric and greater than zero. This calculated percentage takes priority over an alternate state item while enabled.

Historical percentage state is approximated per bucket from both items' aggregated values. Use compatible collection intervals and history retention for representative results.

### Appearance

In the **Aparência** section, select the card-header indicator and configure state colors. The background can follow the Zabbix theme automatically, become transparent, use a solid color, or use a gradient with configurable colors and direction.

Text color can be automatic, light, dark, or custom. Automatic mode inherits the theme for automatic and transparent backgrounds, and calculates a contrasting light or dark color for solid and gradient backgrounds. Cards receive a subtle readability layer; status LEDs, state borders, and the native Zabbix title bar keep their original behavior.

To prevent a down host from displaying a green `0 ms`, configure **Ping Ativo** as the response-time metric's availability item, `0` as the unavailable value, and **Indisponível** as the replacement text. While the host is available, the response-time thresholds continue to apply normally. Missing availability data produces a no-data state rather than a false OK.

### Optional Web Service Monitoring integration

The [Web Service Monitoring template](../../../templates/web-service-monitoring/zabbix-7.4/README.md#english) includes a dashboard preconfigured for this widget. Template collection and alerts work without the module; only the custom cards page requires it.

### Upgrade from version 1.0

Existing dashboards that store rows as JSON remain accepted. Opening and saving the widget with version 1.1 automatically converts the configuration to structured fields. Back up the dashboard before upgrading production.

### Local validation

Run the commands from the Portuguese validation section at the repository root.

## Autor / Author

**Daniel Carvalho**

[LinkedIn](https://www.linkedin.com/in/daniel-ti/) · [danielrc10@gmail.com](mailto:danielrc10@gmail.com)

## Licença / License

[PolyForm Noncommercial 1.0.0](../../../LICENSE) — uso pessoal e não comercial é gratuito. Consultoria, MSP, integração comercial, revenda ou qualquer serviço pago exige autorização prévia de Daniel Carvalho.

[PolyForm Noncommercial 1.0.0](../../../LICENSE) — personal and noncommercial use is free. Consulting, MSP, commercial integration, resale, or any paid service requires prior authorization from Daniel Carvalho.
