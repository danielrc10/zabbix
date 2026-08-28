<?php declare(strict_types = 0);

namespace Modules\DynamicRack\Includes;

use CButton, CCol, CColHeader, CDiv, CList, CRow, CSpan, CTable, CTag, CTemplateTag, CVar,
	CWidgetFieldView;

class CWidgetFieldEquipmentListView extends CWidgetFieldView {
	public function __construct(CWidgetFieldEquipmentList $field) {
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
			new CDiv('#{u_summary}'), new CDiv('#{type_summary}'), new CDiv('#{primary_label}'),
			new CDiv('#{host_summary}'),
			(new CList(array_merge($actions, [(new CSpan())->addClass('js-equipment-data')])))->addClass(ZBX_STYLE_HOR_LIST)
		]));
		$header = ['', (new CColHeader('Posição'))->addItem($template), 'Tipo', 'Rótulo', 'Host', 'Ações'];
		$table = (new CTable())->setId('list_'.$this->field->getName())->setHeader($header);
		foreach ($this->field->getValue() as $index => $row) {
			$data = [];
			foreach ($row as $name => $value) {
				$data[] = new CVar($this->field->getName().'['.$index.']['.$name.']', $value);
			}
			$position = $row['parent_key'] !== '' ? 'em '.$row['parent_key'].' · slot '.$row['slot'] : 'U'.$row['u'].' · '.$row['height_u'].'U';
			$table->addRow((new CRow([
				(new CCol((new CDiv())->addClass(ZBX_STYLE_DRAG_ICON)))->addClass('table-col-handle')->addClass(ZBX_STYLE_TD_DRAG_ICON),
				new CDiv($position), new CDiv($row['type']), new CDiv($row['primary_label']),
				new CDiv($row['hostid'] !== '' ? '#'.$row['hostid'] : 'passivo/manual'),
				(new CList(array_merge($actions, [(new CSpan($data))->addClass('js-equipment-data')])))->addClass(ZBX_STYLE_HOR_LIST)
			]))->setAttribute('data-index', $index));
		}
		$table->addItem(new CTag('tfoot', true, new CRow((new CCol(
			(new CButton('add', 'Adicionar equipamento'))->addClass(ZBX_STYLE_BTN_LINK)->setEnabled(!$this->isDisabled())
		))->setColSpan(count($header)))));
		return $table;
	}
}
