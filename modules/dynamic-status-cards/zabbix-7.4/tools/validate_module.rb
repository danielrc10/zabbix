#!/usr/bin/env ruby

# PT-BR: Validação estrutural do módulo Cards de status dinâmicos.
# EN: Structural validation for the Dynamic Status Cards module.
#
# Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
# LinkedIn: https://www.linkedin.com/in/daniel-ti/
# Licença / License: PolyForm Noncommercial 1.0.0
# Uso comercial / Commercial use: contato / contact danielrc10@gmail.com

require 'json'

default_root = File.expand_path('../module/dynamic_status_cards', __dir__)
root = ARGV.fetch(0, default_root)

def check(condition, message)
  raise "VALIDATION ERROR: #{message}" unless condition
end

required_files = %w[
  manifest.json
  Widget.php
  actions/WidgetView.php
  actions/MetricEdit.php
  includes/WidgetForm.php
  includes/CWidgetFieldMetricList.php
  includes/CWidgetFieldMetricListView.php
  views/widget.edit.php
  views/widget.edit.js.php
  views/widget.view.php
  views/metric.edit.php
  views/metric.edit.js.php
  assets/css/widget.css
  README.md
  LICENSE
  NOTICE.md
]

required_files.each do |relative_path|
  path = File.join(root, relative_path)
  check(File.file?(path), "missing module file: #{relative_path}")
  check(File.size(path).positive?, "empty module file: #{relative_path}")
end

manifest = JSON.parse(File.read(File.join(root, 'manifest.json')))
check(manifest['manifest_version'] == 2.0, 'manifest_version must be 2.0')
check(manifest['id'] == 'dynamic_status_cards', 'unexpected module ID')
check(manifest['type'] == 'widget', 'module type must be widget')
check(manifest['namespace'] == 'DynamicStatusCards', 'unexpected module namespace')
check(manifest['version'] == '1.3.0', 'unexpected module version')
check(manifest.dig('widget', 'in', 'groupids', 'type') == '_hostgroupids', 'dashboard host-group input is missing')
check(manifest.dig('widget', 'in', 'hostids', 'type') == '_hostids', 'dashboard host input is missing')
check(manifest.dig('actions', 'widget.dynamic_status_cards.view', 'class') == 'WidgetView', 'widget action is missing')
check(manifest.dig('actions', 'widget.dynamic_status_cards.metric.edit', 'class') == 'MetricEdit',
  'visual metric editor action is missing')
check(manifest.fetch('assets').fetch('css').include?('widget.css'), 'widget stylesheet is missing from the manifest')

php_files = Dir.glob(File.join(root, '**/*.php')).sort
check(php_files.length == 11, "expected eleven PHP files, found #{php_files.length}")

combined_php = php_files.map { |path| File.read(path) }.join("\n")
check(combined_php.include?('Modules\\DynamicStatusCards'), 'module PHP namespace is missing')
check(combined_php.include?('Manager::History()->getLastValues'), 'widget must retrieve values through the Zabbix history manager')
check(combined_php.include?('CWidgetFieldMetricList'), 'structured metric field is missing')
check(combined_php.include?("ESTADO_LIMITES"), 'numeric threshold support is missing')
check(combined_php.include?("DIRECAO_MAIOR_PIOR"), 'higher-is-worse threshold direction is missing')
check(combined_php.include?("DIRECAO_MENOR_PIOR"), 'lower-is-worse threshold direction is missing')
check(combined_php.include?("json_decode($value, true)"), 'legacy JSON migration support is missing')
check(combined_php.include?("CWidgetFieldColor"), 'configurable state colors are missing')
check(combined_php.include?("FUNDO_GRADIENTE"), 'gradient background support is missing')
check(combined_php.include?("TEXTO_PERSONALIZADO"), 'custom text-color support is missing')
check(combined_php.include?("linear-gradient"), 'gradient CSS generation is missing')
check(combined_php.include?("CWidgetFieldMultiSelectGroup"), 'host-group selector is missing')
check(combined_php.include?("getSubGroups"), 'host subgroup expansion is missing')
check(!combined_php.match?(/password|senha|token/i), 'module must not handle credentials')

stylesheet = File.read(File.join(root, 'assets/css/widget.css'))
check(stylesheet.include?('--dsc-card-fundo'), 'adaptive card background variable is missing')

puts "OK: #{root} passed module structural checks"
puts "    #{php_files.length} PHP files, one manifest and one stylesheet"
