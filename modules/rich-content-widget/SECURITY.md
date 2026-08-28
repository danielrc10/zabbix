# Segurança

Relate vulnerabilidades de forma privada para `danielrc10@gmail.com`. Não publique credenciais, exports de
hosts, macros secretas, tokens ou dados de produção em issues.

O módulo usa as permissões da sessão atual do frontend. Conteúdo HTML é sanitizado no backend. Data URIs SVG
com scripts, eventos, `foreignObject` ou referências externas são bloqueadas. Mesmo assim, utilize somente
imagens de origem confiável e mantenha o frontend Zabbix atualizado.
