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

- **Tag usada para agrupar:** cada valor diferente gera um card; vazia, gera um card por host.
- **Métricas exibidas:** use **Adicionar métrica** e configure tudo pela interface, sem JSON.
- **Item ou padrão:** aceita itens selecionados e `*` como curinga no nome completo.
- **Item alternativo de estado:** permite exibir um item e usar outro item do mesmo card para definir a cor.
- **Limiares numéricos:** permitem escolher se valores maiores ou menores representam pior estado.
- **Valores exatos:** associam listas de valores aos estados OK, aviso e crítico.
- **Cores:** OK, aviso, crítico e sem dados são personalizáveis no formulário principal.
- **Estado geral:** apresenta o pior estado encontrado nas linhas.

Formatos: `automatico`, `mapa`, `numero`, `data` e `texto`.

Estados: `ok`, `aviso`, `critico`, `sem_dados` e `neutro`.

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

- **Grouping tag:** each distinct value creates one card; empty creates one card per host.
- **Displayed metrics:** use **Adicionar métrica** and configure everything in the GUI without JSON.
- **Item or pattern:** accepts selected items and `*` as a wildcard in the full item name.
- **Alternate state item:** displays one item while another item in the same card determines the color.
- **Numeric thresholds:** support both higher-is-worse and lower-is-worse evaluation.
- **Exact values:** associate value lists with OK, warning, and critical states.
- **Colors:** OK, warning, critical, and no-data colors are configurable in the main form.
- **Overall state:** displays the worst state found among the rows.

Formats: `automatico`, `mapa`, `numero`, `data`, and `texto`.

States: `ok`, `aviso`, `critico`, `sem_dados`, and `neutro`.

The configuration is stored as structured dashboard fields. JSON configurations created with version 1.0 are converted when the widget is opened and saved.

## Exemplos / Examples

```text
Ping: maior é pior; aviso 50; crítico 150
Certificado: menor é pior; aviso 15; crítico 0
Disponibilidade: OK = 1; crítico = 0
```

## Autor / Author

**Daniel Carvalho**

[LinkedIn](https://www.linkedin.com/in/daniel-ti/) · [danielrc10@gmail.com](mailto:danielrc10@gmail.com)

Licença / License: [PolyForm Noncommercial 1.0.0](LICENSE) · [Aviso de uso / Usage notice](NOTICE.md)

Uso pessoal e não comercial é gratuito. Consultoria ou qualquer uso comercial exige autorização prévia de [Daniel Carvalho](mailto:danielrc10@gmail.com).

Personal and noncommercial use is free. Consulting or any commercial use requires prior authorization from [Daniel Carvalho](mailto:danielrc10@gmail.com).
