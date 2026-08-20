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
  includes/IconLibrary.php
  views/widget.edit.php
  views/widget.edit.js.php
  views/widget.view.php
  views/metric.edit.php
  views/metric.edit.js.php
  assets/css/widget.css
  assets/js/class.widget.js
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
check(manifest['version'] == '1.10.1', 'unexpected module version')
check(manifest.dig('widget', 'js_class') == 'CWidgetDynamicStatusCards', 'responsive widget JS class is missing')
check(manifest.dig('widget', 'in', 'groupids', 'type') == '_hostgroupids', 'dashboard host-group input is missing')
check(manifest.dig('widget', 'in', 'hostids', 'type') == '_hostids', 'dashboard host input is missing')
check(manifest.dig('actions', 'widget.dynamic_status_cards.view', 'class') == 'WidgetView', 'widget action is missing')
check(manifest.dig('actions', 'widget.dynamic_status_cards.metric.edit', 'class') == 'MetricEdit',
  'visual metric editor action is missing')
check(manifest.fetch('assets').fetch('css').include?('widget.css'), 'widget stylesheet is missing from the manifest')
check(manifest.fetch('assets').fetch('js').include?('class.widget.js'), 'widget controller is missing from the manifest')

php_files = Dir.glob(File.join(root, '**/*.php')).sort
check(php_files.length == 12, "expected twelve PHP files, found #{php_files.length}")

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
check(combined_php.include?("CWidgetFieldTags"), 'host/item tag filters are missing')
check(combined_php.include?("evaltype_host"), 'host-tag evaluation mode is missing')
check(combined_php.include?("evaltype_item"), 'item-tag evaluation mode is missing')
check(combined_php.include?("padroes_bloqueio"), 'per-metric availability item is missing')
check(combined_php.include?("itens_bloqueio"), 'availability items are not collected for card evaluation')
check(combined_php.include?("EXIBICAO_VALOR_HISTORICO"), 'historical display mode is missing')
check(combined_php.include?("getGraphAggregationByWidth"), 'bounded historical aggregation is missing')
check(combined_php.include?("historico_cor_indisponivel"), 'per-metric unavailable history color is missing')
check(combined_php.include?("ROTULOS_ESTADOS_HISTORICOS"), 'historical state labels are missing')
check(combined_php.include?("padroes_complemento"), 'complementary item support is missing')
check(combined_php.include?("estado_percentual_calculado"), 'calculated percentage state is missing')
check(combined_php.include?("mostrar_rotulo"), 'metric-name visibility control is missing')
check(combined_php.include?("meio_texto"), 'historical midpoint label is missing')
check(combined_php.include?("Períodos acima de 24 horas"), 'long historical period warning is missing')
check(combined_php.include?("FORMATO_PERCENTUAL_FRACAO"), 'fractional percentage format is missing')
check(combined_php.include?("new CButton('copy', 'Copiar')"), 'copy metric action is missing')
check(combined_php.include?("TIPO_ESPACADOR"), 'empty spacer row support is missing')
check(combined_php.include?("TIPO_SEPARADOR"), 'horizontal separator row support is missing')
check(combined_php.include?("separador_complemento"), 'custom complementary separator is missing')
check(combined_php.include?("new CSortable"), 'drag-and-drop row ordering is missing')
check(combined_php.include?("CSelect('mostrar_rotulo')"), 'explicit metric-name visibility selector is missing')
check(combined_php.include?("EXIBICAO_VALOR_GRAFICO"), 'historical graph display mode is missing')
check(combined_php.include?("montarGraficoHistorico"), 'historical graph coordinate generation is missing')
check(combined_php.include?("IconLibrary::normalize"), 'extensible metric icon selection is missing')
check(combined_php.include?("data:image/svg+xml;base64"), 'embedded SVG icon source is missing')
check(combined_php.include?("icone_cabecalho"), 'configurable card-header indicator is missing')
check(combined_php.include?("dynamic-status-card__estado-geral--icone"), 'custom card-header icon rendering is missing')
check(combined_php.include?("Ajustar colunas automaticamente"), 'automatic-column field is missing')
check(combined_php.include?("Limite manual de colunas"), 'manual maximum-column field is missing')
check(combined_php.include?("colunas_automaticas"), 'automatic-column default and evaluation are missing')
check(combined_php.include?("data-max-columns"), 'maximum-column metadata is missing from the rendered grid')
check(!combined_php.match?(/password|senha|token/i), 'module must not handle credentials')

stylesheet = File.read(File.join(root, 'assets/css/widget.css'))
check(stylesheet.include?('--dsc-card-fundo'), 'adaptive card background variable is missing')
check(stylesheet.include?('dynamic-status-card__historico-barra'), 'historical status bar styles are missing')
check(stylesheet.include?('dynamic-status-card__historico-grafico'), 'historical graph styles are missing')
check(stylesheet.include?('dynamic-status-icons__option'), 'visual icon selector styles are missing')
check(stylesheet.include?('dynamic-status-icons__panel'), 'dropdown icon catalog styles are missing')
check(stylesheet.include?('justify-self: center'), 'metric indicators are not centered in their grid column')
check(stylesheet.include?('repeat(var(--dsc-columns), minmax(0, 1fr))'), 'runtime fluid card columns are missing')
check(!stylesheet.include?('@container'), 'container-query fallback must not squeeze cards in the Zabbix dashboard DOM')

controller = File.read(File.join(root, 'assets/js/class.widget.js'))
check(controller.include?('class CWidgetDynamicStatusCards extends CWidget'), 'responsive widget controller class is missing')
check(controller.include?('onResize()'), 'widget resize lifecycle hook is missing')
check(controller.include?('cards_count'), 'responsive layout must account for the actual card count')
check(controller.include?('CARD_MIN_WIDTH'), 'minimum useful card width is missing')
check(controller.include?("style.setProperty('--dsc-columns'"), 'runtime column update is missing')

icon_files = Dir.glob(File.join(root, 'assets/icons/*.svg')).sort
check(icon_files.length >= 66, "expected at least sixty-six SVG icons, found #{icon_files.length}")
required_icons = %w[
	arrow-down.svg arrow-left.svg arrow-right.svg arrow-up.svg camera.svg linux.svg macos.svg
	memory.svg plug.svg spy.svg windows.svg www.svg cluster.svg computer.svg dvr.svg laptop.svg
	security-camera.svg server.svg
]
required_icons.each do |filename|
  check(File.file?(File.join(root, 'assets/icons', filename)), "missing bundled icon: #{filename}")
end
icon_files.each do |path|
  check(File.basename(path).match?(/\A[a-z0-9][a-z0-9_-]*\.svg\z/i), "unsafe icon filename: #{path}")
  check(File.read(path).include?('<svg'), "invalid SVG icon: #{path}")
end

puts "OK: #{root} passed module structural checks"
puts "    #{php_files.length} PHP files, #{icon_files.length} SVG icons, one manifest, one stylesheet and one JS controller"
