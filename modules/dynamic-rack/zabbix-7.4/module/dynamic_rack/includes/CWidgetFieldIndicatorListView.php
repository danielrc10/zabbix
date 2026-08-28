<?php declare(strict_types = 0);

namespace Modules\DynamicRack\Includes;

use CButton, CCol, CColHeader, CDiv, CList, CRow, CSpan, CTable, CTag, CTemplateTag, CVar,
	CWidgetFieldView;

class CWidgetFieldIndicatorListView extends CWidgetFieldView {
	public function __construct(CWidgetFieldIndicatorList $field) {
		$this->field = $field;
	}

	public function getFocusableElementId(): string {
		return 'list_'.$this->field->getName();
	}

	public function getView(): CTag {
		$actions = [
			(new CButton('edit', 'Editar'))->addClass(ZBX_STYLE_BTN_LINK)->removeId(),
			(new CButton('copy', 'Copiar'))->addClass(ZBX_STYLE_BTN_LINK)->removeId(),
			(new CButton('remove', 'Remover'))->addClass(ZBX_STYLE_BTN_LINK)->removeId()
		];
		$template = new CTemplateTag($this->field->getName().'-row-tmpl', new CRow([
			(new CCol((new CDiv())->addClass(ZBX_STYLE_DRAG_ICON)))->addClass('table-col-handle')->addClass(ZBX_STYLE_TD_DRAG_ICON),
			new CDiv('#{equipment_key}'), new CDiv('#{label}'), new CDiv('#{pattern_summary}'),
			new CDiv('#{placement_summary}'),
			(new CList(array_merge($actions, [(new CSpan())->addClass('js-indicator-data')])))->addClass(ZBX_STYLE_HOR_LIST)
		]));
		$header = ['', (new CColHeader('Equipamento'))->addItem($template), 'Indicador', 'Item/padrão', 'Local', 'Ações'];
		$table = (new CTable())->setId('list_'.$this->field->getName())->setHeader($header);
		foreach ($this->field->getValue() as $index => $row) {
			$data = [];
			foreach ($row as $name => $value) {
				if (is_array($value)) {
					foreach ($value as $child => $child_value) {
						$data[] = new CVar($this->field->getName().'['.$index.']['.$name.']['.$child.']', $child_value);
					}
				}
				else {
					$data[] = new CVar($this->field->getName().'['.$index.']['.$name.']', $value);
				}
			}
			$placement = $row['location'].' · balão '.$row['balloon'].' · '.$row['side'];
			$table->addRow((new CRow([
				(new CCol((new CDiv())->addClass(ZBX_STYLE_DRAG_ICON)))->addClass('table-col-handle')->addClass(ZBX_STYLE_TD_DRAG_ICON),
				new CDiv($row['equipment_key']), new CDiv($row['label']), new CDiv(implode(', ', $row['patterns'])),
				new CDiv($placement),
				(new CList(array_merge($actions, [(new CSpan($data))->addClass('js-indicator-data')])))->addClass(ZBX_STYLE_HOR_LIST)
			]))->setAttribute('data-index', $index));
		}
		$table->addItem(new CTag('tfoot', true, new CRow((new CCol(
			(new CButton('add', 'Adicionar indicador'))->addClass(ZBX_STYLE_BTN_LINK)->setEnabled(!$this->isDisabled())
		))->setColSpan(count($header)))));
		return $table;
	}
}
