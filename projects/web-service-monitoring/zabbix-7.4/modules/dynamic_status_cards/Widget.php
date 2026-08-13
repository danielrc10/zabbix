<?php declare(strict_types = 0);

/**
 * PT-BR: Classe principal do widget Cards de status dinâmicos.
 * EN: Main class for the Dynamic Status Cards widget.
 *
 * Autor / Author: Daniel Carvalho <danielrc10@gmail.com>
 * LinkedIn: https://www.linkedin.com/in/daniel-ti/
 * Licença / License: PolyForm Noncommercial 1.0.0
 * Uso comercial / Commercial use: contato / contact danielrc10@gmail.com
 */

namespace Modules\DynamicStatusCards;

use Zabbix\Core\CWidget;

/**
 * PT-BR: Widget genérico de cards agrupados por uma tag de item.
 * EN: Generic card widget grouped by an item tag.
 */
class Widget extends CWidget {

	public function getDefaultName(): string {
		return 'Cards de status dinâmicos';
	}
}
