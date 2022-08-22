<?php
/**
 * @package         Joomla.Plugin
 * @subpackage      Field - WT Yandex Map
 *
 * @copyright       (C) 2022 Sergey Tolkachyov, https://web-tolk.ru
 * @license         GNU General Public License version 2 or later
 */

use Joomla\CMS\Factory;

$option = Factory::getApplication()->input->get('option');

defined('_JEXEC') or die;
$field;
$fieldParams;


// Если поле не заполнено
if (empty($field->value))
{
	return '';
}
$view = Factory::getApplication()->getInput()->get('view');
// Опция "не показывать в категориях"
// Почему не по контексту поля? Потому, что у материала в виде категории контекст материала
// и поэтому этот финт ушами не сработает. Поэтому фильтруем по $view
if($view == 'category' && $fieldParams->get('dont_show_in_category',0) == 1){
	return '';
}

$unique_id = mt_rand(11111111, 99999999);

$yandex_map_api_entry_point_free = 'https://api-maps.yandex.ru/2.1';
$yandex_map_api_entry_point_paid = 'https://enterprise.api-maps.yandex.ru/2.1';

// Получаем API ключ Яндекс.карт

if (empty($fieldParams->get('yandex_map_api_key')))
{
	$yandex_map_api_key = '';
}
else
{
	$yandex_map_api_key = 'apikey=' . $fieldParams->get('yandex_map_api_key') . '&';
}

$wa                     = Factory::getApplication()->getDocument()->getWebAssetManager();
$yandex_map_entry_point = ($fieldParams->get('yandex_api_type') == 'free' ? $yandex_map_api_entry_point_free : $yandex_map_api_entry_point_paid);
$yandex_map_script_uri  = $yandex_map_entry_point . '/?' . $yandex_map_api_key . 'lang=' . Factory::getApplication()->getLanguage()->getTag();
$wa->registerAndUseScript('plg.fields.wtyandexmap.yandex', $yandex_map_script_uri, [], [], ['core']);
/**
 * Координаты центра карты из параметров плагина
 * и координаты из поля
 */
$map_center_coords = explode(',', $fieldParams->get('map_center'));
$placemark_coords  = explode(',', $field->value);

$map_options       = array(
	'zoom'   => $fieldParams->get('map_zoom', 7),
	'type'   => 'yandex#' . $fieldParams->get('map_type', 'map')
);

/**
 * Центр карты на метке. Если координаты метки не указаны - берём центр из параметров
 */

if (is_array($placemark_coords) && count($placemark_coords) > 0)
{
	$map_options['center'] = [(float) $placemark_coords[0], (float) $placemark_coords[1]];
}
else
{
	$map_options['center'] = [(float) $map_center_coords[0], (float) $map_center_coords[1]];
}

Factory::getApplication()->getDocument()->addScriptOptions('plg_fields_wtyandexmap' . $unique_id . '_' . $field->id, $map_options);

/**
 * - заголовок
 * - картинка
 * - текст
 * - ссылка
 */

$placemark                                                 = array(
	'id' => 1
);
$data                                                      = array();
$data["type"]                                              = "FeatureCollection";
$data["features"][0]["type"]                               = "Feature";
$data["features"][0]["id"]                                 = 1;
$data["features"][0]["geometry"]["type"]                   = "Point";
$data["features"][0]["geometry"]["coordinates"][0]         = $placemark_coords[0];
$data["features"][0]["geometry"]["coordinates"][1]         = $placemark_coords[1];
//$data["features"][0]["properties"]["balloonContentHeader"] = "Название метки";
//$data["features"][0]["properties"]["balloonContentBody"]   = "ОСНОВНОЙ ТЕКСТ. ОСНОВНОЙ ТЕКСТ. ОСНОВНОЙ ТЕКСТ. ОСНОВНОЙ ТЕКСТ. ОСНОВНОЙ ТЕКСТ. ОСНОВНОЙ ТЕКСТ. ОСНОВНОЙ ТЕКСТ. ";
//$data["features"][0]["properties"]["hintContent"]          = "Название метки";
//$data["features"][0]["options"]["iconColor"] = $status_icon_color;
$data["features"][0]["options"]["preset"] = $fieldParams->get('placemark_icon_code','islands#blueDotIcon');

$data = json_encode($data);

$js_yandex_map_init = '
		ymaps.ready(init' . $unique_id . ');
        function init' . $unique_id . '(){
        	let plg_field_wtyandexmap_' . $unique_id . '_' . $field->id . '_options = Joomla.getOptions("plg_fields_wtyandexmap' . $unique_id . '_' . $field->id . '");
            var myMap' . $unique_id . '_' . $field->id . ' = new ymaps.Map("plg_field_wtyandexmap_' . $unique_id . '_' . $field->id . '", plg_field_wtyandexmap_' . $unique_id . '_' . $field->id . '_options),
				objectManager = new ymaps.ObjectManager({
					// Чтобы метки начали кластеризоваться, выставляем опцию.
					clusterize: true,
					// ObjectManager принимает те же опции, что и кластеризатор.
					gridSize: 32,
					clusterDisableClickZoom: true
				});

    // Чтобы задать опции одиночным объектам и кластерам,
    // обратимся к дочерним коллекциям ObjectManager.
    //objectManager.objects.options.set("preset", "islands#greenDotIcon");
    //objectManager.clusters.options.set("preset", "islands#greenClusterIcons");
       	objectManager.add(' . $data . ');
    	myMap' . $unique_id . '_' . $field->id . '.geoObjects.add(objectManager);


        }
        

        ';

$wa->addInlineScript($js_yandex_map_init, [], ['defer' => true]);
?>

<div id="plg_field_wtyandexmap_<?php echo $unique_id . '_' . $field->id; ?>"
	 style="width:<?php echo $fieldParams->get('map_width'); ?>;height:<?php echo $fieldParams->get('map_height'); ?>;margin:0;padding:0;"></div>
