# Rack dinâmico — Zabbix 7.4

## Instalação

Simule primeiro; nenhuma alteração será feita:

```bash
./scripts/install_dynamic_rack.sh --dry-run --modules-dir /usr/share/zabbix/modules
```

Instale com a permissão adequada:

```bash
sudo ./scripts/install_dynamic_rack.sh --modules-dir /usr/share/zabbix/modules
```

Ou extraia `dist/dynamic_rack.zip` no diretório `modules` do frontend. Depois abra
**Administração → Geral → Módulos**, escaneie o diretório e habilite **Rack dinâmico**.

O módulo não altera banco de dados, Zabbix Server, Proxy, Agent, templates ou hosts.

## Configuração recomendada

1. Em **Origem e filtros**, escolha os grupos e hosts que poderão alimentar o rack.
2. Em **Rack**, selecione o preset ou informe uma altura de 1 a 48U, a numeração e a escala.
3. Em **Equipamentos**, crie uma chave única, posição, altura, tipo, host opcional e rótulos.
4. Em **Indicadores**, aponte cada métrica para a chave do equipamento e selecione o item/padrão.
5. Use o mesmo **número do balão** para juntar CPU, memória e rede; use outro número para criar outro balão.
6. Escolha **Balão**, **Rodapé** ou **Balão e rodapé** em cada indicador.

## Prateleira com vários equipamentos

Crie primeiro um equipamento do tipo **Prateleira**, por exemplo com chave `operadoras`, em U14 e 1U.
Depois crie cada modem com:

- **Prateleira pai:** `operadoras`;
- Claro: slot 1 de 2;
- Vivo: slot 2 de 2.

Cada filho pode ter seu próprio host, cor, marca, estado e qualquer quantidade de balões. A prateleira ocupa
o U apenas uma vez. O editor recusa posições e slots conflitantes.

## Posição e numeração

A posição é armazenada internamente a partir da base do rack. Se a exibição mudar de **U1 embaixo** para
**U1 em cima**, os equipamentos não se movem; somente os números impressos nas laterais mudam.

## Balões

Os balões são medidos depois da renderização, separados por lado e deslocados verticalmente para não se
sobreporem. Se a soma das alturas for maior que o rack, a área externa cresce. O rack nunca ganha uma barra
de rolagem interna. As linhas terminam na posição real do balão, portanto podem ser diagonais.

Quando **Herdar a cor** está ativo, o balão usa o pior estado do equipamento. O formulário principal permite
usar a cor no fundo inteiro, somente em faixa/borda, ou manter o balão neutro.

## Catálogos extensíveis

Copie imagens confiáveis para:

- `module/dynamic_rack/assets/brands` — mini logos;
- `module/dynamic_rack/assets/equipment` — desenhos de equipamentos.

São aceitos `.svg`, `.png` e `.webp`, com nomes seguros e até 128 KiB. O arquivo aparece automaticamente ao
reabrir o editor. SVGs com scripts, eventos, entidades, `foreignObject` ou referências externas são ignorados.

## Validação

```bash
ruby tools/validate_module.rb
bash -n scripts/install_dynamic_rack.sh
find module/dynamic_rack -name '*.php' -print0 | xargs -0 -n1 php -l
node --check module/dynamic_rack/assets/js/class.widget.js
```

O comando `php -l` deve ser executado em uma máquina com o PHP CLI compatível com o frontend Zabbix.
