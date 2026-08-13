#!/usr/bin/env ruby

# PT-BR: Validação estrutural do template de monitoramento de sites.
# EN: Structural validation for the website monitoring template.
#
# Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
# LinkedIn: https://www.linkedin.com/in/daniel-ti/
# Licença / License: PolyForm Noncommercial 1.0.0
# Uso comercial / Commercial use: contato / contact danielrc10@gmail.com

require 'yaml'
require 'json'

default_path = File.expand_path('../template/template_web_service_monitoring.yaml', __dir__)
path = ARGV.fetch(0, default_path)
source = File.read(path)
data = YAML.safe_load(source, permitted_classes: [], aliases: false)

def check(condition, message)
  raise "VALIDATION ERROR: #{message}" unless condition
end

# PT-BR: O Zabbix exporta escalares como strings. Isso detecta conversões
# inesperadas do YAML antes de o arquivo chegar ao importador.
# EN: Zabbix exports scalar values as strings. This catches unexpected YAML
# conversions before the file reaches the importer.
walk_scalars = lambda do |value, current_path = []|
  case value
  when Hash
    value.each { |key, child| walk_scalars.call(child, current_path + [key]) }
  when Array
    value.each_with_index { |child, index| walk_scalars.call(child, current_path + [index]) }
  else
    check(value.is_a?(String), "non-string scalar at #{current_path.join('.')}: #{value.inspect}")
  end
end
walk_scalars.call(data)

uuid_pattern = /\A[0-9a-f]{12}4[0-9a-f]{3}[89ab][0-9a-f]{15}\z/
allowed_item_types = %w[ZABBIX_PASSIVE DEPENDENT SCRIPT].freeze
allowed_value_types = %w[FLOAT CHAR TEXT UNSIGNED].freeze
allowed_preprocessing = %w[JSONPATH JAVASCRIPT DISCARD_UNCHANGED_HEARTBEAT].freeze
template_name = 'Template Web Service Monitoring'

root = data.fetch('zabbix_export')
check(root['version'] == '7.4', 'export version must be 7.4')
check(root.fetch('template_groups').length == 1, 'exactly one template group is expected')
check(root.fetch('templates').length == 1, 'exactly one template is expected')

template = root.fetch('templates').first
check(template['template'] == template_name, 'unexpected technical template name')
check(template.fetch('groups').any? { |group| group['name'] == 'Templates/Applications' }, 'Templates/Applications group is missing')

uuids = []
collect_uuid = lambda do |object, label|
  value = object['uuid']
  check(value.is_a?(String) && value.match?(uuid_pattern), "#{label} has an invalid UUID: #{value.inspect}")
  uuids << value
end

root.fetch('template_groups').each { |group| collect_uuid.call(group, "group #{group['name']}") }
collect_uuid.call(template, 'template')

discovery_rules = template.fetch('discovery_rules')
check(discovery_rules.length == 1, 'exactly one centralized discovery rule is expected')
discovery_rules.each { |rule| collect_uuid.call(rule, "discovery rule #{rule['name']}") }

items = template.fetch('items', []) + discovery_rules.flat_map { |rule| rule.fetch('item_prototypes', []) }
keys = items.map { |item| item.fetch('key') }
check(keys.uniq.length == keys.length, 'item keys are not unique')
check(items.any? { |item| item['type'] == 'SCRIPT' && item['key'] == 'web.service.raw[{#SITE.ID}]' }, 'central Script web-check prototype is missing')
check(items.any? { |item| item['type'] == 'ZABBIX_PASSIVE' && item['key'].start_with?('web.certificate.get[') }, 'central Agent2 certificate prototype is missing')

triggers = []
items.each do |item|
  collect_uuid.call(item, "item #{item['name']}")
  type = item.fetch('type', 'ZABBIX_PASSIVE')
  check(allowed_item_types.include?(type), "unsupported item type #{type}")
  check(allowed_value_types.include?(item.fetch('value_type', 'UNSIGNED')), "unsupported value type on #{item['name']}")
  check(item.key?('params'), "SCRIPT item #{item['name']} must contain params") if type == 'SCRIPT'

  if type == 'DEPENDENT'
    master = item.fetch('master_item').fetch('key')
    check(keys.include?(master), "dependent item #{item['name']} references missing master #{master}")
  end

  item.fetch('preprocessing', []).each do |step|
    check(allowed_preprocessing.include?(step['type']), "unsupported preprocessing type #{step['type']}")
    check(step['parameters'].is_a?(Array) && !step['parameters'].empty?, "preprocessing parameters missing on #{item['name']}")
  end

  (item.fetch('triggers', []) + item.fetch('trigger_prototypes', [])).each do |trigger|
    collect_uuid.call(trigger, "trigger #{trigger['name']}")
    check(trigger.fetch('expression').include?("/#{template_name}/"), "trigger #{trigger['name']} references another template")
    check(trigger['name'].include?('{#SITE.NAME}'), "trigger #{trigger['name']} does not identify the discovered site")
    triggers << trigger
  end
end

check(triggers.length == 8, "expected eight trigger prototypes, found #{triggers.length}")
trigger_signatures = triggers.map { |trigger| [trigger['name'], trigger['expression'], trigger.fetch('recovery_expression', '')] }
triggers.each do |trigger|
  trigger.fetch('dependencies', []).each do |dependency|
    signature = [dependency['name'], dependency['expression'], dependency.fetch('recovery_expression', '')]
    check(trigger_signatures.include?(signature), "dependency of #{trigger['name']} does not resolve: #{dependency['name']}")
  end
end

macro_names = template.fetch('macros').map { |macro| macro.fetch('macro') }
used_macros = source.scan(/\{\$[A-Z0-9._]+\}/).uniq
unknown_macros = used_macros - macro_names
check(unknown_macros.empty?, "undefined macros: #{unknown_macros.join(', ')}")
check(macro_names.include?('{$WEB.SITES}'), '{$WEB.SITES} macro is missing')

http_header_macros = %w[
  {$WEB.USERAGENT}
  {$WEB.ACCEPT}
  {$WEB.LANGUAGE}
  {$WEB.UPGRADE.INSECURE.REQUESTS}
  {$WEB.SECFETCHSITE}
  {$WEB.SECFETCHMODE}
]
http_header_macros.each do |macro|
  check(macro_names.include?(macro), "HTTP header macro #{macro} is missing")
end

macro_values = template.fetch('macros').to_h { |macro| [macro.fetch('macro'), macro.fetch('value', '')] }
check(macro_values.fetch('{$WEB.USERAGENT}').include?('AppleWebKit/537.36'), 'default User-Agent must include a browser engine token')
check(macro_values.fetch('{$WEB.USERAGENT}').include?('Chrome/'), 'default User-Agent must include a browser product token')

web_script = items.find { |item| item['key'] == 'web.service.raw[{#SITE.ID}]' }
header_parameters = web_script.fetch('parameters').to_h { |parameter| [parameter.fetch('name'), parameter.fetch('value')] }
expected_header_parameters = {
  'user_agent' => '{$WEB.USERAGENT}',
  'accept' => '{$WEB.ACCEPT}',
  'language' => '{$WEB.LANGUAGE}',
  'upgrade_insecure_requests' => '{$WEB.UPGRADE.INSECURE.REQUESTS}',
  'sec_fetch_site' => '{$WEB.SECFETCHSITE}',
  'sec_fetch_mode' => '{$WEB.SECFETCHMODE}'
}
expected_header_parameters.each do |parameter, macro|
  check(header_parameters[parameter] == macro, "Script parameter #{parameter} must use #{macro}")
end

required_headers = %w[User-Agent Accept Accept-Language Upgrade-Insecure-Requests Sec-Fetch-Site Sec-Fetch-Mode]
required_headers.each do |header|
  check(web_script.fetch('params').include?("'#{header}'"), "HTTP header #{header} is missing from the web Script item")
end
check(!web_script.fetch('params').include?('Zabbix 7.4 centralized website monitoring'), 'the old fixed User-Agent is still present')

discovery_rules.each do |rule|
  check(rule['type'] == 'SCRIPT', 'the centralized discovery rule must be a Script item')
  check(rule.key?('params'), "discovery rule #{rule['name']} must contain params")
  paths = rule.fetch('lld_macro_paths').map { |entry| entry.fetch('lld_macro') }
  %w[{#SITE.ID} {#SITE.NAME} {#SITE.HOST} {#SITE.PORT} {#SITE.URL}].each do |macro|
    check(paths.include?(macro), "LLD macro path #{macro} is missing")
  end
end

graph_prototypes = discovery_rules.flat_map { |rule| rule.fetch('graph_prototypes', []) }
check(!graph_prototypes.empty?, 'response-time graph prototype is missing')
graph_prototypes.each do |graph|
  collect_uuid.call(graph, "graph prototype #{graph['name']}")
  graph.fetch('graph_items').each do |graph_item|
    item = graph_item.fetch('item')
    check(item['host'] == template_name, "graph prototype #{graph['name']} references another host")
    check(keys.include?(item['key']), "graph prototype #{graph['name']} references missing item #{item['key']}")
  end
end

template.fetch('valuemaps').each { |valuemap| collect_uuid.call(valuemap, "value map #{valuemap['name']}") }

dashboards = template.fetch('dashboards')
check(dashboards.length == 1, 'exactly one template dashboard is expected')
dashboards.each do |dashboard|
  collect_uuid.call(dashboard, "dashboard #{dashboard['name']}")
  widgets = dashboard.fetch('pages').flat_map { |page| page.fetch('widgets') }
  check(widgets.count { |widget| widget['type'] == 'honeycomb' } == 4, 'dashboard must contain four Honeycomb widgets')
  cards = widgets.select { |widget| widget['type'] == 'dynamic_status_cards' }
  check(cards.length == 1, 'dashboard must contain one Dynamic Status Cards widget')
  card_fields = cards.first.fetch('fields').to_h { |field| [field.fetch('name'), field.fetch('value')] }
  check(card_fields['tag_agrupamento'] == 'site', 'Dynamic Status Cards must group website items by the site tag')
  check(card_fields.fetch('linhas').bytesize <= 2048, 'Dynamic Status Cards row configuration exceeds the Zabbix string-field limit')
  card_rows = JSON.parse(card_fields.fetch('linhas'))
  check(card_rows.length == 6, 'website card must contain six configured rows')
  check(widgets.any? { |widget| widget['type'] == 'graphprototype' }, 'dashboard graph-prototype widget is missing')
end

check(uuids.uniq.length == uuids.length, 'UUIDs are not unique')
check(!source.match?(/certbot/i), 'Certbot content must not exist in this template')
check(!source.include?('system.run['), 'system.run must not exist in the template')
check(!template.key?('httptests'), 'the multi-site design must not create a shared Web Scenario')

puts "OK: #{path} parsed as YAML and passed structural checks"
puts "    #{discovery_rules.length} LLD rule, #{items.length} item prototypes, #{triggers.length} trigger prototypes"
puts "    #{graph_prototypes.length} graph prototype, #{dashboards.length} dashboard, #{uuids.length} unique UUIDv4 values"
