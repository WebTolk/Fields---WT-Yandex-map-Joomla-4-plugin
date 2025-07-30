<?php
/**
 * @package    Fields - WT Yandex Map
 * @version       2.1.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2024 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\Fields\WtYandexMap\Fields;

defined('_JEXEC') or die();

use Joomla\CMS\Form\FormField;

class WtyandexmapField extends FormField
{
	protected $type = 'Wtyandexmap';

	protected $layout = 'plugins.fields.wtyandexmap.wtyandexmapfield';

	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @return  array
	 *
	 * @since 3.5
	 */
	protected function getLayoutData()
	{
		$options = parent::getLayoutData();
		$fieldParams = [];
		foreach ($this->element->attributes() as $key => $value)
		{
			$fieldParams[$key] = (string)$value;
		}

		return array_merge($options,['fieldparams' => $fieldParams]);
	}
}
