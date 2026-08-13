#!/usr/bin/env ruby

# PT-BR: Validação estrutural do módulo Cards de status dinâmicos.
# EN: Structural validation for the Dynamic Status Cards module.
#
# Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
# LinkedIn: https://www.linkedin.com/in/daniel-ti/
# Licença / License: PolyForm Noncommercial 1.0.0
# Uso comercial / Commercial use: contato / contact danielrc10@gmail.com

require 'json'

default_root = File.expand_path('../modules/dynamic_status_cards', __dir__)
root = ARGV.fetch(0, default_root)

def check(condition, message)
  raise "VALIDATION ERROR: #{message}" unless condition
end

required_files = %w[
  manifest.json
  Widget.php
  actions/WidgetView.php
  includes/WidgetForm.php
  views/widget.edit.php
  views/widget.view.php
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
check(manifest['version'] == '1.0.1', 'unexpected module version')
check(manifest.dig('widget', 'in', 'hostids', 'type') == '_hostids', 'dashboard host input is missing')
check(manifest.dig('actions', 'widget.dynamic_status_cards.view', 'class') == 'WidgetView', 'widget action is missing')
check(manifest.fetch('assets').fetch('css').include?('widget.css'), 'widget stylesheet is missing from the manifest')

php_files = Dir.glob(File.join(root, '**/*.php')).sort
check(php_files.length == 5, "expected five PHP files, found #{php_files.length}")

combined_php = php_files.map { |path| File.read(path) }.join("\n")
check(combined_php.include?('Modules\\DynamicStatusCards'), 'module PHP namespace is missing')
check(combined_php.include?('Manager::History()->getLastValues'), 'widget must retrieve values through the Zabbix history manager')
check(combined_php.include?("['padrao_estado']"), 'derived row state support is missing')
check(combined_php.include?(%q{array_key_exists('*', $estados)}), 'default state mapping support is missing')
check(!combined_php.match?(/password|senha|token/i), 'module must not handle credentials')

puts "OK: #{root} passed module structural checks"
puts "    #{php_files.length} PHP files, one manifest and one stylesheet"
