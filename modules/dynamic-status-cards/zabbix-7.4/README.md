# Cards de status dinâmicos — Zabbix 7.4 / Dynamic Status Cards — Zabbix 7.4

[Português](#português) · [English](#english)

> Zabbix 7.4 · Módulo 1.5.1 · Filtros dinâmicos · Barras históricas · Limiares · Aparência personalizável

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
- um ou mais itens exatos ou padrões com `*`;
- formato automático, mapeamento, número, data ou texto;
- item alternativo opcional para determinar somente a cor;
- item de disponibilidade opcional que força crítico quando, por exemplo, `Ping Ativo = 0`;
- avaliação sem regra, por limiares numéricos ou por valores exatos;
- comportamento quando não houver dados.

As cores de OK, aviso, crítico e sem dados são configuradas no formulário principal do widget.

### Barras históricas

Na janela **Editar métrica**, o campo **Modo de exibição** permite mostrar somente o valor atual, o valor com uma barra histórica ou apenas a barra histórica. O período é configurável de 1 a 90 dias, com 7 dias por padrão.

A barra usa o histórico numérico nativo do Zabbix e divide o período automaticamente em até 180 blocos. Cada bloco recebe a pior condição observada conforme os limiares ou valores exatos da própria métrica. Quando há um **Item de disponibilidade**, seus valores críticos são exibidos como **Indisponível**; períodos sem amostras ficam como **Sem dados**.

As cinco cores do histórico podem herdar a paleta do widget ou ser personalizadas por métrica:

- OK;
- aviso;
- crítico;
- indisponível, preto por padrão;
- sem dados.

O resumo opcional mostra **disponibilidade** quando existe um item de disponibilidade ou o percentual de blocos **OK** nos demais casos. Blocos sem dados não entram no denominador. Passe o mouse sobre uma faixa para ver período, estado, mínimo, média e máximo.

A barra exige um item numérico para determinar o estado. Ela consulta somente a tabela de histórico, não as tendências; portanto, o período realmente visível depende da retenção configurada para o item. A cor atual do LED e do card continua sendo calculada pela amostra mais recente: uma falha antiga aparece na barra, mas não mantém o card vermelho depois da recuperação.

### Aparência

Na seção **Aparência**, o fundo pode acompanhar automaticamente o tema do Zabbix, ficar transparente, usar uma cor sólida ou um gradiente. No modo gradiente, escolha as duas cores e a direção horizontal, diagonal ou vertical.

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

Choose the grouping and use **Adicionar métrica**. An empty grouping tag creates one card per host. Each metric supports a display name, exact items or wildcard patterns, formatting, an optional alternate state item, an optional availability item, numeric thresholds or exact values, and missing-data behavior.

OK, warning, critical, and no-data colors are configured in the main widget form. Numeric thresholds support both **higher is worse** and **lower is worse** directions.

### Historical status bars

In **Edit metric**, **Display mode** can show only the current value, the value plus a historical bar, or only the historical bar. The period ranges from 1 to 90 days and defaults to 7 days.

The bar reads Zabbix's native numeric history and automatically divides the period into at most 180 buckets. Each bucket receives the worst condition detected by the metric's thresholds or exact-value rules. When an **availability item** is configured, its critical values are rendered as **Unavailable**; buckets without samples are rendered as **No data**.

The five historical colors may inherit the widget palette or be customized per metric: OK, warning, critical, unavailable (black by default), and no data.

The optional summary shows **availability** when an availability item exists, or the percentage of **OK** buckets otherwise. No-data buckets are excluded from the denominator. Hover a strip to inspect its time range, state, minimum, average, and maximum.

The bar requires a numeric item to determine state. It intentionally reads the history table rather than trends, so the visible range depends on item history retention. The current LED and card color still use the latest sample: a past failure remains visible in the bar but does not keep a recovered card red.

### Appearance

In the **Aparência** section, the background can follow the Zabbix theme automatically, become transparent, use a solid color, or use a gradient with configurable colors and direction.

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
