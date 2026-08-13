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

- **Tag usada para agrupar:** cada valor diferente gera um card.
- **Configuração das linhas:** array JSON com rótulo, padrão do item, formato e estado.
- **Padrão:** aceita `*` como curinga no nome completo do item.
- **Padrão de estado (opcional):** `padrao_estado` permite exibir o valor de um item e usar outro item do mesmo card para definir a cor do indicador.
- **Estado padrão:** a chave `*` em `estados` é usada quando não há correspondência exata para o valor.
- **Estado geral:** apresenta o pior estado encontrado nas linhas.

Formatos: `automatico`, `mapa`, `numero`, `data` e `texto`.

Estados: `ok`, `aviso`, `critico`, `sem_dados` e `neutro`.

Campos de texto de widgets no Zabbix 7.4 aceitam até 2048 caracteres. O dashboard deste projeto inclui uma configuração pronta agrupada pela tag `site`.

## English

Generic Zabbix 7.4 widget that groups items by a tag and combines multiple metrics in the same card. It uses the internal frontend API, respects the logged-in user's permissions, and stores no additional credentials.

### Installation

Use the project installer or copy this directory to the frontend `modules` directory:

```text
/usr/share/zabbix/modules/dynamic_status_cards
```

Then go to **Administration → General → Modules**, scan the directory, and enable **Cards de status dinâmicos**. For containers, install it in the frontend container, not in the Zabbix Server or Agent 2 container.

### Configuration

- **Grouping tag:** each distinct value creates one card.
- **Row configuration:** JSON array containing label, item pattern, format, and state.
- **Pattern:** accepts `*` as a wildcard in the full item name.
- **State pattern (optional):** `padrao_estado` displays one item's value while using another item in the same card to determine the indicator color.
- **Default state:** the `*` key in `estados` is used when no exact value matches.
- **Overall state:** displays the worst state found among the rows.

Formats: `automatico`, `mapa`, `numero`, `data`, and `texto`.

States: `ok`, `aviso`, `critico`, `sem_dados`, and `neutro`.

Zabbix 7.4 widget string fields support up to 2048 characters. This project's dashboard includes a ready-to-use configuration grouped by the `site` tag.

## Exemplo / Example

```json
[
  {
    "rotulo": "Disponibilidade",
    "padrao": "* Availability",
    "formato": "mapa",
    "mapa": {"1": "UP", "0": "DOWN"},
    "estados": {"1": "ok", "0": "critico"}
  },
  {
    "rotulo": "Latência",
    "padrao": "* Response time",
    "formato": "numero",
    "decimais": 0,
    "sufixo": " ms",
    "limites": [
      {"operador": "<=", "valor": 2000, "estado": "ok"},
      {"operador": "<=", "valor": 5000, "estado": "aviso"},
      {"operador": ">", "valor": 5000, "estado": "critico"}
    ]
  },
  {
    "rotulo": "Expira em",
    "padrao": "* Expiration date",
    "formato": "data",
    "padrao_estado": "* Certificate health",
    "estados": {"2": "ok", "1": "aviso", "0": "critico"}
  }
]
```

## Autor / Author

**Daniel Carvalho**

[LinkedIn](https://www.linkedin.com/in/daniel-ti/) · [danielrc10@gmail.com](mailto:danielrc10@gmail.com)

Licença / License: [PolyForm Noncommercial 1.0.0](LICENSE) · [Aviso de uso / Usage notice](NOTICE.md)

Uso pessoal e não comercial é gratuito. Consultoria ou qualquer uso comercial exige autorização prévia de [Daniel Carvalho](mailto:danielrc10@gmail.com).

Personal and noncommercial use is free. Consulting or any commercial use requires prior authorization from [Daniel Carvalho](mailto:danielrc10@gmail.com).
