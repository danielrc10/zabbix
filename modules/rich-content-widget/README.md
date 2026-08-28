# Conteúdo rico para Zabbix

Widget para dashboards Zabbix 7.x focado em cabeçalhos, mídia transparente, documentos curtos e layouts que
precisam caber integralmente no painel. O módulo remove o fundo nativo quando solicitado, não cria barras de
rolagem e reduz ou amplia todo o conteúdo proporcionalmente durante o redimensionamento.

Versão inicial: **1.0.0** · alvo principal **Zabbix 7.4** · compatível com a Widget API do **Zabbix 7.0+**.

## Instalação rápida pelo Git

Primeira instalação no host do frontend Zabbix:

```bash
sudo git clone https://github.com/danielrc10/zabbix.git /opt/zabbix-community
cd /opt/zabbix-community/modules/rich-content-widget/zabbix-7.4
sudo ./scripts/install_rich_content.sh --dry-run
sudo ./scripts/install_rich_content.sh
```

Para atualizar uma instalação feita dessa forma:

```bash
sudo git -C /opt/zabbix-community pull --ff-only
cd /opt/zabbix-community/modules/rich-content-widget/zabbix-7.4
sudo ./scripts/install_rich_content.sh --dry-run
sudo ./scripts/install_rich_content.sh
```

O instalador detecta os diretórios mais comuns do frontend. Consulte o
[guia completo para Zabbix 7.4](zabbix-7.4/README.md) para informar outro caminho, instalar pelo ZIP e habilitar
o módulo no Zabbix.

## Recursos

- grid interno de uma a seis colunas;
- Markdown seguro ou subconjunto de HTML sanitizado;
- separador `[[coluna]]` para distribuir conteúdo lado a lado;
- macros `{HOST.*}`, `{ITEM.*}`, inventário e macros de usuário pelo resolvedor nativo do Zabbix;
- imagens externas, caminhos web locais e upload pequeno convertido em base64;
- PNG, JPEG, GIF, WebP e SVG, mantendo transparência;
- `contain`, `cover`, `fill` e `none`, proporção, largura, altura, rotação e espelhamento;
- fundo transparente, sólido ou gradiente de duas/três cores com ângulo configurável;
- borda sólida, tracejada, pontilhada, espessura, cor e arredondamento;
- `ResizeObserver` e `transform: scale()` integrados ao ciclo de resize do widget;
- sanitização no backend e nenhuma dependência externa.

## Estrutura

- `zabbix-7.4/module/rich_content`: código instalável;
- `zabbix-7.4/dist/rich_content.zip`: pacote para extração em `modules`;
- `zabbix-7.4/scripts/install_rich_content.sh`: instalador com simulação e backup;
- `zabbix-7.4/tools/validate_module.rb`: validação estrutural e de segurança.

Consulte a [documentação de instalação e uso](zabbix-7.4/README.md).

## Autor e licença

**Daniel Carvalho** · [LinkedIn](https://www.linkedin.com/in/daniel-ti/) ·
[danielrc10@gmail.com](mailto:danielrc10@gmail.com)

Licença [PolyForm Noncommercial 1.0.0](LICENSE). Uso comercial exige autorização prévia, conforme
[NOTICE.md](NOTICE.md).
