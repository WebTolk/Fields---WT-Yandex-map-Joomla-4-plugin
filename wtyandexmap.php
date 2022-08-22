<?php
/**
 * @package         Joomla.Plugin
 * @subpackage      Field - WT Yandex Map
 *
 * @copyright       (C) 2022 Sergey Tolkachyov, https://web-tolk.ru
 * @license         GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Fields Wt Yandex Map Plugin
 *
 * @since  3.7.0
 */
class PlgFieldsWtyandexmap extends \Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin
{
	protected $autoloadLanguage = true;
	/**
	 * @var string Entry point for free Yandex.Map API version
	 * @since 1.0.0
	 */
	protected static $yandex_map_api_entry_point_free = 'https://api-maps.yandex.ru/2.1';

	/**
	 * @var string Entry point for free Yandex.Map API version
	 * @since 1.0.0
	 */
	protected static $yandex_map_api_entry_point_paid = 'https://enterprise.api-maps.yandex.ru/2.1';

	/**
	 * Transforms the field into a DOM XML element and appends it as a child on the given parent.
	 *
	 * @param   stdClass    $field   The field.
	 * @param   DOMElement  $parent  The field node parent.
	 * @param   Form        $form    The form.
	 *
	 * @return  DOMElement
	 *
	 * @since   3.7.0
	 */
	public function onCustomFieldsPrepareDom($field, DOMElement $parent, Form $form)
	{

		if (!$fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form)) return $fieldNode;
		$parent->setAttribute('class', 'wt-items-list');
		// Получаем API ключ Яндекс.карт
		$plugin       = PluginHelper::getPlugin('fields', 'wtyandexmap');
		$pluginParams = json_decode($plugin->params);
		if (empty($pluginParams->yandex_map_api_key))
		{
			Factory::getApplication()->enqueueMessage('<strong>WT Yandex Map field:</strong> There is no Yandex.Map API key.', 'error');
			$yandex_map_api_key = '';
		}
		else
		{
			$yandex_map_api_key = 'apikey=' . $pluginParams->yandex_map_api_key . '&';
		}

		$wa                     = Factory::getApplication()->getDocument()->getWebAssetManager();
		$yandex_map_entry_point = ($pluginParams->yandex_api_type == 'free' ? self::$yandex_map_api_entry_point_free : self::$yandex_map_api_entry_point_paid);

		$yandex_map_script_uri = $yandex_map_entry_point . '/?' . $yandex_map_api_key . 'lang=' . Factory::getApplication()->getLanguage()->getTag();
		$wa->registerAndUseScript('plg.fields.wtyandexmap.yandex', $yandex_map_script_uri, [], ['defer' => true]);

		// Create field
		$value = '';
		if ((!empty($field->rawvalue)))
		{
			$value = $field->rawvalue;
		}
		elseif ((!empty($field->value)))
		{
			$value = $field->value;
		}
		$fieldNode->setAttribute('type', 'wtyandexmap');
		$fieldNode->setAttribute('value', $value);
		$fieldNode->setAttribute('data-wt-yandex-map-field-' . $field->id, '');

		$fieldParams = $field->fieldparams;

		/**
		 * Координаты центра карты из параметров плагина
		 * и координаты из поля
		 */
		$map_center_coords = explode(',', $fieldParams->get('map_center', '51.533562, 46.034266'));
		$placemark_coords  = explode(',', $field->value);

		$map_options = array(
			'zoom' => $fieldParams->get('map_zoom', 7),
			'type' => 'yandex#' . $fieldParams->get('map_type', 'map')
		);

		/**
		 * Центр карты на метке. Если координаты метки не указаны - берём центр из параметров
		 */

		if (is_array($placemark_coords) && count($placemark_coords) > 0)
		{
			if (!empty($placemark_coords[0]) && !empty($placemark_coords[1]))
			{
				$map_options['center'] = [(float) $placemark_coords[0], (float) $placemark_coords[1]];
			}
			else
			{
				if (!empty($map_center_coords[0]) && !empty($map_center_coords[1]))
				{
					$map_options['center'] = [(float) $map_center_coords[0], (float) $map_center_coords[1]];
				}
				else
				{
					$map_options['center'] = [51.533562, 46.034266];
				}
			}
		}
		else
		{
			if (!empty($map_center_coords[0]) && !empty($map_center_coords[1]))
			{
				$map_options['center'] = [(float) $map_center_coords[0], (float) $map_center_coords[1]];
			}
			else
			{
				$map_options['center'] = [51.533562, 46.034266];
			}

		}

		$map_options = json_encode($map_options);
		if ($field->value)
		{
			$placemark                                         = array(
				'id' => 1
			);
			$data                                              = array();
			$data["type"]                                      = "FeatureCollection";
			$data["features"][0]["type"]                       = "Feature";
			$data["features"][0]["id"]                         = 1;
			$data["features"][0]["geometry"]["type"]           = "Point";
			$data["features"][0]["geometry"]["coordinates"][0] = $placemark_coords[0];
			$data["features"][0]["geometry"]["coordinates"][1] = $placemark_coords[1];
			$data                                              = json_encode($data);
		}
		$js                                                = "
			document.addEventListener('DOMContentLoaded', function()
			{
				el = document.querySelector('[data-wt-yandex-map-field-" . $field->id . "]');
				el.insertAdjacentHTML('afterend', '<div id=\"data-wt-yandex-map-field-" . $field->id . "\" style=\"width:420px; height:200px; margin:0px padding:0px;\"></div>');
				ymaps.ready(init" . $field->id . ");

			});// DOMCOntentLoaded
				
			
		
        function init" . $field->id . "(){
            var myMap" . $field->id . " = new ymaps.Map('data-wt-yandex-map-field-" . $field->id . "', " . $map_options . "),
				objectManager = new ymaps.ObjectManager({
					// Чтобы метки начали кластеризоваться, выставляем опцию.
					clusterize: true,
					// ObjectManager принимает те же опции, что и кластеризатор.
					gridSize: 32,
					clusterDisableClickZoom: true
				});
				".($field->value ? "
					objectManager.add(" . $data . ");
			        myMap" . $field->id . ".geoObjects.add(objectManager);
				" : "" )."

                myMap" . $field->id . ".events.add('click', function (e) {
				    // Получение координат щелчка
				    var coords = e.get('coords');
				    el = document.querySelector('[data-wt-yandex-map-field-" . $field->id . "]');
				    el.value = coords.join(', ');
				});
        }
	";

		Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineScript($js, [], ['defer' => true]);

		return $fieldNode;
	}
}
