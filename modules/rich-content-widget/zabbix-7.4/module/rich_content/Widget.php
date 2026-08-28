<?php declare(strict_types = 0);

/**
 * Classe principal do widget Conteúdo rico.
 *
 * Autor: Daniel Carvalho <danielrc10@gmail.com>
 * Licença: PolyForm Noncommercial 1.0.0
 */

namespace Modules\RichContent;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public function getDefaultName(): string {
		return 'Conteúdo rico';
	}
}
