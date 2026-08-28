# Conteúdo rico — Zabbix 7.x

> Módulo 1.0.0 · alvo principal Zabbix 7.4 · Widget API do Zabbix 7.0+

## Instalação

O ZIP contém a pasta `rich_content`. Extraia-a diretamente no diretório de módulos do frontend:

```bash
unzip dist/rich_content.zip -d /usr/share/zabbix/modules
```

Ou revise e execute o instalador primeiro em modo de simulação:

```bash
./scripts/install_rich_content.sh --dry-run --modules-dir /usr/share/zabbix/modules
sudo ./scripts/install_rich_content.sh --modules-dir /usr/share/zabbix/modules
```

Depois acesse **Administração → Geral → Módulos → Escanear diretório**, habilite **Conteúdo rico** e adicione o
widget ao dashboard.

## Conteúdo e colunas

Selecione **Markdown seguro** ou **HTML simples e sanitizado**. Em qualquer modo, use `[[coluna]]` sozinho em
uma linha para iniciar outro bloco. O campo **Colunas internas** define o número de trilhas do grid.

Exemplo:

```markdown
# {HOST.NAME}

Último valor: **{ITEM.LASTVALUE}**

[[coluna]]

| Campo | Valor |
|---|---:|
| Item | {ITEM.NAME} |
| Chave | `{ITEM.KEY}` |
```

O Markdown aceita títulos, parágrafos, ênfase, listas, citações, links HTTP(S), código, tabelas e imagens. O
modo HTML aceita tags semânticas simples, mas remove scripts, iframes, objetos, eventos `on*`, estilos inline e
URLs perigosas.

### Classes HTML permitidas

Classes que começam com `rc-` são preservadas. O CSS inclui `rc-grid`, `rc-stack`, `rc-callout`, `rc-badge`,
`rc-muted`, `rc-text-left`, `rc-text-center`, `rc-text-right`, `rc-span-2`, `rc-span-3` e `rc-span-all`.

## Macros do Zabbix

Escolha um **Item para contexto das macros**. O backend passa o conteúdo pelo resolvedor oficial
`CMacrosResolverHelper::resolveItemBasedWidgetMacros()`, respeitando permissões da sessão e suportando:

- `{HOST.NAME}`, `{HOST.HOST}`, `{HOST.ID}` e outras macros de host/interface;
- `{ITEM.NAME}`, `{ITEM.KEY}`, `{ITEM.VALUE}`, `{ITEM.LASTVALUE}` e macros de item;
- macros de inventário;
- macros de usuário vinculadas ao host.

Sem item de contexto, as macros permanecem literais. O frontend não recebe credenciais e não consulta a API
diretamente.

## Imagens

Há duas formas de inserir mídia:

1. no campo **Mídia principal**, com controles visuais;
2. no Markdown ou HTML, para várias imagens dentro do documento.

Fontes aceitas:

- URL `http://` ou `https://`;
- caminho web local, por exemplo `modules/rich_content/assets/media/logo.svg`;
- data URI base64 para PNG, JPEG, GIF, WebP ou SVG.

O seletor local do formulário converte arquivos de até **47 KiB** em base64 para caber no campo persistido do
dashboard. Para arquivos maiores, copie-os para `assets/media` antes de instalar ou use uma URL. SVG base64 com
scripts, `foreignObject`, eventos ou referências externas é rejeitado.

Imagem Markdown com transformação:

```markdown
![Topologia](https://exemplo.local/topologia.png){width=640 height=360 fit=contain rotate=0 flip=h aspect=16/9}
```

Valores aceitos: `fit=contain|cover|fill|none`, `rotate=0|90|180|270`,
`flip=h|v|both` e `aspect=auto|1/1|4/3|16/9|3/2`.

## Fundo, borda e transparência

O modo transparente aplica a correção também aos elementos externos `.dashboard-grid-widget-header` e
`.dashboard-grid-widget-contents`, removendo fundo, borda, sombra e outline do card nativo. Os modos sólido e
gradiente também cobrem o cabeçalho do widget. PNG e SVG continuam com `background: transparent` e não recebem
uma camada de card individual.

## Ajuste automático e regra sem scroll

O conteúdo é montado numa prancheta com a **Largura-base** configurada. A cada atualização, carregamento de
imagem ou resize, o JavaScript mede largura e altura naturais — incluindo imagens rotacionadas — e aplica a
menor escala necessária nos dois eixos. A opção **Ampliar conteúdo** permite escala maior que 1.

Todos os containers principais usam `overflow: hidden`; o módulo não possui fallback com scroll. Conteúdo muito
extenso continuará cabendo, mas poderá ficar pequeno. Para preservar legibilidade, reduza o texto, aumente o
painel ou distribua o documento em mais colunas.

## Remoção

Desabilite o módulo no Zabbix e remova somente a pasta `rich_content` do diretório de módulos. Dashboards que o
referenciam manterão a configuração, mas mostrarão o tipo como indisponível até uma reinstalação.
