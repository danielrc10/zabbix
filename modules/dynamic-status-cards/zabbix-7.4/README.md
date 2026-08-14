# Cards de status dinâmicos — Zabbix 7.4 / Dynamic Status Cards — Zabbix 7.4

[Português](#português) · [English](#english)

> Zabbix 7.4 · Módulo 1.2.0 · Configuração visual · Limiares · Valores exatos · Aparência personalizável

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

Ao editar o widget, selecione os hosts, escolha o agrupamento e use **Adicionar métrica**. Se a tag de agrupamento ficar vazia, o widget cria um card por host. Cada métrica possui:

- nome exibido;
- um ou mais itens exatos ou padrões com `*`;
- formato automático, mapeamento, número, data ou texto;
- item alternativo opcional para determinar somente a cor;
- avaliação sem regra, por limiares numéricos ou por valores exatos;
- comportamento quando não houver dados.

As cores de OK, aviso, crítico e sem dados são configuradas no formulário principal do widget.

### Aparência

Na seção recolhível **Aparência**, o fundo pode acompanhar automaticamente o tema do Zabbix, ficar transparente, usar uma cor sólida ou um gradiente. No modo gradiente, escolha as duas cores e a direção horizontal, diagonal ou vertical.

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

While editing the widget, select the hosts, choose the grouping, and use **Adicionar métrica**. An empty grouping tag creates one card per host. Each metric supports a display name, exact items or wildcard patterns, formatting, an optional alternate state item, numeric thresholds or exact values, and missing-data behavior.

OK, warning, critical, and no-data colors are configured in the main widget form. Numeric thresholds support both **higher is worse** and **lower is worse** directions.

### Appearance

In the collapsible **Aparência** section, the background can follow the Zabbix theme automatically, become transparent, use a solid color, or use a gradient with configurable colors and direction.

Text color can be automatic, light, dark, or custom. Automatic mode inherits the theme for automatic and transparent backgrounds, and calculates a contrasting light or dark color for solid and gradient backgrounds. Cards receive a subtle readability layer; status LEDs, state borders, and the native Zabbix title bar keep their original behavior.

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
