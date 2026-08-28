#!/usr/bin/env ruby

require 'json'

root = ARGV.fetch(0, File.expand_path('../module/dynamic_rack', __dir__))
def check(condition, message)
  raise "VALIDATION ERROR: #{message}" unless condition
end

required = %w[
  manifest.json Widget.php README.md LICENSE NOTICE.md actions/WidgetView.php actions/EquipmentEdit.php
  actions/IndicatorEdit.php includes/AssetLibrary.php includes/WidgetForm.php
  includes/CWidgetFieldEquipmentList.php includes/CWidgetFieldEquipmentListView.php
  includes/CWidgetFieldIndicatorList.php includes/CWidgetFieldIndicatorListView.php
  views/widget.edit.php views/widget.edit.js.php views/widget.view.php
  views/equipment.edit.php views/equipment.edit.js.php views/indicator.edit.php views/indicator.edit.js.php
  assets/css/widget.css assets/js/class.widget.js
]
required.each do |relative|
  path = File.join(root, relative)
  check(File.file?(path) && File.size(path).positive?, "missing or empty file: #{relative}")
end

manifest = JSON.parse(File.read(File.join(root, 'manifest.json')))
check(manifest['manifest_version'] == 2.0, 'manifest_version must be 2.0')
check(manifest['id'] == 'dynamic_rack', 'unexpected module id')
check(manifest['namespace'] == 'DynamicRack', 'unexpected namespace')
check(manifest['version'] == '0.1.0', 'unexpected version')
check(manifest.dig('widget', 'in', 'hostids', 'type') == '_hostids', 'host input is missing')
outputs = manifest.dig('widget', 'out').map { |entry| entry['type'] }
%w[_hostid _hostids _itemid _itemids].each { |type| check(outputs.include?(type), "#{type} output is missing") }

php = Dir.glob(File.join(root, '**/*.php')).sort.map { |path| File.read(path) }.join("\n")
%w[CWidgetFieldMultiSelectGroup CWidgetFieldMultiSelectHost CWidgetFieldTags getSubGroups].each do |feature|
  check(php.include?(feature), "reused filter feature is missing: #{feature}")
end
%w[rack_units parent_key slot_count balloon footer both Manager::History].each do |feature|
  check(php.include?(feature), "rack feature is missing: #{feature}")
end
check(php.include?('U1 embaixo') && php.include?('U1 em cima'), 'numbering choices are missing')
check(php.include?("8 => '8U'"), '8U preset is missing')
check(!php.match?(/password|senha|api[_-]?token/i), 'module must not handle credentials')

css = File.read(File.join(root, 'assets/css/widget.css'))
js = File.read(File.join(root, 'assets/js/class.widget.js'))
check(!css.include?('overflow-y: auto'), 'rack must not use internal vertical scrolling')
check(js.include?('#placeSide') && js.include?('#drawLines'), 'collision layout or connectors are missing')
check(js.include?('requiredHeight'), 'callout area must grow instead of overlapping')
check(js.include?('this.broadcast'), 'widget broadcast integration is missing')
check(css.include?('data-color-mode="fill"'), 'problem balloon color inheritance is missing')

brands = Dir.glob(File.join(root, 'assets/brands/*.{svg,png,webp}')).map { |path| File.basename(path) }
%w[fortinet.svg unifi.svg cisco.svg paloalto.svg pfsense.svg intelbras.svg dell.svg lenovo.svg hp.svg apc.svg hikvision.svg].each do |brand|
  check(brands.include?(brand), "bundled brand is missing: #{brand}")
end
assets = Dir.glob(File.join(root, 'assets/{brands,equipment}/*.{svg,png,webp}'))
assets.each do |path|
  check(File.basename(path).match?(/\A[a-z0-9][a-z0-9._-]*\z/i), "unsafe asset filename: #{path}")
  check(File.size(path) <= 131_072, "asset is too large: #{path}")
end

puts "OK: #{root} passed structural validation"
puts "    #{required.length} required files, #{brands.length} bundled brands, #{assets.length} visual assets"
