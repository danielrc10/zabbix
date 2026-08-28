#!/usr/bin/env ruby

require 'json'

root = ARGV.fetch(0, File.expand_path('../module/rich_content', __dir__))

def check(condition, message)
  raise "VALIDATION ERROR: #{message}" unless condition
end

required = %w[
  manifest.json Widget.php README.md LICENSE NOTICE.md
  actions/WidgetView.php includes/WidgetForm.php includes/ContentRenderer.php
  views/widget.edit.php views/widget.edit.js.php views/widget.view.php
  assets/css/widget.css assets/js/widget.js assets/media/README.md
]

required.each do |relative|
  path = File.join(root, relative)
  check(File.file?(path) && File.size(path).positive?, "missing or empty file: #{relative}")
end

manifest = JSON.parse(File.read(File.join(root, 'manifest.json')))
check(manifest['manifest_version'] == 2.0, 'manifest_version must be 2.0')
check(manifest['id'] == 'rich_content', 'unexpected module id')
check(manifest['type'] == 'widget', 'module type must be widget')
check(manifest['namespace'] == 'RichContent', 'unexpected namespace')
check(manifest['version'] == '1.0.0', 'unexpected version')
check(manifest.dig('widget', 'js_class') == 'CWidgetRichContent', 'JavaScript class is not registered')
check(manifest.dig('actions', 'widget.rich_content.view', 'class') == 'WidgetView', 'view action is missing')
check(manifest.dig('actions', 'widget.rich_content.view', 'view') == 'widget.view', 'view mapping is missing')
check(manifest.dig('assets', 'css') == ['widget.css'], 'CSS asset is not registered')
check(manifest.dig('assets', 'js') == ['widget.js'], 'JavaScript asset is not registered')

php = Dir.glob(File.join(root, '**/*.php')).sort.map { |path| File.read(path) }.join("\n")
%w[
  CWidget CWidgetForm CWidgetField CControllerDashboardWidgetView CWidgetView
  CMacrosResolverHelper resolveItemBasedWidgetMacros CWidgetFieldMultiSelectItem
].each do |feature|
  check(php.include?(feature), "required Zabbix API feature is missing: #{feature}")
end
%w[FORMAT_MARKDOWN FORMAT_HTML MEDIA_FIT_COVER BACKGROUND_GRADIENT BORDER_DOTTED].each do |feature|
  check(php.include?(feature), "widget feature is missing: #{feature}")
end
check(php.include?('sanitizeHtml') && php.include?('sanitizeImageSource'), 'backend sanitization is missing')
check(php.include?('foreignObject') && php.include?('javascript:'), 'SVG/URL security checks are missing')
check(!php.match?(/password|senha|api[_-]?token/i), 'module must not handle credentials')

css = File.read(File.join(root, 'assets/css/widget.css'))
js = File.read(File.join(root, 'assets/js/widget.js'))
edit_js = File.read(File.join(root, 'views/widget.edit.js.php'))

check(css.include?('overflow: hidden !important'), 'strict no-scroll rule is missing')
check(!css.match?(/overflow(?:-[xy])?\s*:\s*(?:auto|scroll)/i), 'scrollable overflow is forbidden')
check(css.include?('rich-content-shell--transparent'), 'native transparent shell override is missing')
check(php.include?('linear-gradient'), 'gradient support is missing')
check(css.include?('--rc-image-rotation') && css.include?('--rc-image-flip-x'), 'image transforms are missing')
check(js.include?('ResizeObserver'), 'ResizeObserver integration is missing')
check(js.include?('onResize()'), 'Zabbix resize lifecycle hook is missing')
check(js.include?('transform = `scale('), 'proportional scale transform is missing')
check(js.include?('availableWidth / naturalWidth') && js.include?('availableHeight / naturalHeight'),
  'bidimensional fit calculation is missing')
check(edit_js.include?('FileReader') && edit_js.include?('readAsDataURL'), 'local base64 upload is missing')

media = Dir.glob(File.join(root, 'assets/media/*')).reject { |path| File.basename(path) == 'README.md' }
media.each do |path|
  check(File.file?(path), "media entry must be a file: #{path}")
  check(File.basename(path).match?(/\A[a-z0-9][a-z0-9._-]*\z/i), "unsafe media filename: #{path}")
  check(File.extname(path).downcase.match?(/\A\.(?:png|jpe?g|gif|webp|svg)\z/), "unsupported media type: #{path}")
  check(File.size(path) <= 1_048_576, "media file is too large: #{path}")
end

puts "OK: #{root} passed structural validation"
puts "    #{required.length} required files, #{media.length} optional local media files"
