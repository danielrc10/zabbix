# Rack dinâmico para Zabbix

Widget visual de rack para o frontend Zabbix 7.4. O rack é configurado pela interface, associa equipamentos
a hosts e itens do Zabbix e mantém itens passivos — prateleiras, tampas cegas, patch panels, organizadores e
espaços vazios — no mesmo modelo físico.

Versão inicial: **0.1.0** · licença **PolyForm Noncommercial 1.0.0**.

## Instalação

```bash
git clone https://github.com/danielrc10/zabbix.git
cd zabbix/modules/dynamic-rack/zabbix-7.4

sudo ./scripts/install_dynamic_rack.sh \
  --modules-dir /usr/share/zabbix/modules
```

Depois acesse **Administração → Geral → Módulos → Escanear diretório** e habilite **Rack dinâmico**.

## O que já funciona

- rack de 1U a 48U, com presets 8U, 12U, 18U, 24U, 32U, 42U e 48U;
- U1 embaixo ou U1 em cima, sem mover a posição física salva;
- escala de 12 a 72 pixels por U e nenhuma rolagem dentro do rack;
- servidor, storage, switch, firewall, roteador, DVR/NVR, nobreak, modem e item personalizado;
- prateleira com vários equipamentos filhos em slots independentes;
- itens passivos e espaços vazios ocupando qualquer quantidade de U;
- rótulos principal e secundário com ajuste automático de fonte;
- filtros por grupo, host e tags de host, incluindo subgrupos;
- indicadores por item/padrão, formatação, limiares e valores exatos;
- vários balões por equipamento/U, à esquerda, à direita ou alternados automaticamente;
- reposicionamento sem sobreposição e conectores diagonais;
- balão inteiro, faixa/borda ou cor neutra quando houver aviso, crítico, desativado ou sem dados;
- rodapé opcional com ocupação, estado geral, métricas e ambiente;
- transmissão de host e item ao clicar, para conexão dinâmica com outros widgets;
- catálogo extensível de marcas e desenhos por pastas.

## Estrutura

- `zabbix-7.4/module/dynamic_rack`: código instalável do módulo;
- `zabbix-7.4/dist/dynamic_rack.zip`: pacote de instalação;
- `zabbix-7.4/scripts/install_dynamic_rack.sh`: instalador com simulação e backup;
- `zabbix-7.4/tools/validate_module.rb`: validação estrutural local.

Veja a [documentação do Zabbix 7.4](zabbix-7.4/README.md) para instalação e configuração.

## Integração com os Cards de status dinâmicos

O manifesto publica `_hostid`, `_hostids`, `_itemid` e `_itemids`. Ao clicar em um equipamento ou balão,
o rack transmite essas referências. No mesmo dashboard, um widget que aceite a referência correspondente
pode reagir à seleção. Os Cards de status dinâmicos já aceitam `_hostids`, portanto podem ser filtrados para
o host escolhido no rack.

Esta integração usa o mecanismo oficial de comunicação entre widgets do Zabbix. Ela não cria uma ligação
privada entre dois módulos nem força outro widget a abrir um card específico; para granularidade por
equipamento, o host ou item publicado precisa representar esse equipamento.

## Marcas

As miniaturas incluídas são identificadores tipográficos neutros para Fortinet, UniFi, Cisco, Palo Alto,
pfSense, Intelbras, Dell, Lenovo, HP, APC e Hikvision. Os nomes e marcas pertencem aos seus respectivos
titulares. Substitua as miniaturas pelos arquivos que sua organização esteja autorizada a usar.

Copyright 2026 Daniel Carvalho. Uso comercial requer autorização conforme [NOTICE.md](NOTICE.md).
