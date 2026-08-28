<?php declare(strict_types = 0);

namespace Modules\DynamicRack;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public function getDefaultName(): string {
		return 'Rack dinâmico';
	}
}
