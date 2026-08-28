# Módulo Conteúdo rico

Código instalável do widget **Conteúdo rico 1.0.0** para o frontend Zabbix 7.x.

Extraia esta pasta como `rich_content` dentro do diretório `modules`, escaneie os módulos no frontend e habilite
o widget. A documentação completa está no repositório do projeto.

O módulo usa somente a API PHP da sessão Zabbix atual. HTML e imagens são sanitizados antes da renderização;
nenhuma credencial adicional é armazenada.
