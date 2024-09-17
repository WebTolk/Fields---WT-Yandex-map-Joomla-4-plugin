<?php
/**
 * @package    Fields - WT Yandex Map
 * @version       2.0.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2024 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Uri\Uri;
use Joomla\CMS\Layout\FileLayout;

defined('_JEXEC') or die;

/**
 * @var $field       object field object
 * @var $fieldParams object field params object. So, you can access via $field->fieldparams->get('option_name')
 * @var $item        object Article or category or contact (etc) object
 * @var $context     string rendering context
 */

/**
 * $field->id int
 * $field->title string "Поле WT Yandex Map"
 * $field->name string "pole-wt-yandex-map"
 * $field->checked_out int 822
 * $field->checked_out_time string "2024-08-27 12:20:40"
 * $field->note string
 * $field->state int 1 Published or not
 * $field->access int 1  Access group id
 * $field->created_time string "2024-08-27 12:06:05"
 * $field->created_user_id int 822
 * $field->ordering int 0
 * $field->language string "*" or "en_GB", "ru_RU"
 * $field->fieldparams Joomla\Registry\Registry field params for site: map_center, map_zoom, map_type, map_width, map_height
 * $field->params Joomla\Registry\Registry for admin panel hint, class, showlabel, showon
 * $field->type string "wtyandexmap"
 * $field->default_value string
 * $field->context string "com_content.article"
 * $field->group_id int 0
 * $field->label string "Поле WT Yandex Map"
 * $field->description string ""
 * $field->required int 0
 * $field->only_use_in_subform int 0
 * $field->language_title string|null
 * $field->language_image string|null
 * $field->editor string "Сергей Толкачев"
 * $field->access_level "Public"
 * $field->author_name "Сергей Толкачев"
 * $field->group_title null
 * $field->group_access null
 * $field->group_state null
 * $field->group_note null
 * $field->value string "51.533562, 46.034266" Field HTML
 * $field->rawvalue string "51.533562, 46.034266" Yandex map coords
 */

// SEE https://yandex.ru/dev/jsapi30/doc/ru/upgrade/
//
//		<script>const defaultMarker = new ymaps3.YMapDefaultMarker({
//		  title: 'Hello world!',
//		  subtitle: 'Kind and bright',
//		  color: 'blue'
//		});
//
//		const content = document.createElement('div');
//		const marker = new ymaps3.YMapMarker(content);
//		content.innerHTML = '<div>There could be anything here</div>';
//
//		const map = new ymaps3.YMap(document.getElementById('map-root'), {
//		  location: INITIAL_LOCATION
//		})
//		  .addChild(new ymaps3.YMapDefaultSchemeLayer())
//		.addChild(new ymaps3.YMapDefaultFeaturesLayer({zIndex: 1800}))
//		  .addChild(defaultMarker)
//		.addChild(marker);</script>


/**
 * @var string $yandex_map_api_entry_point_free Entry point for free Yandex.Map API version
 * @since 1.0.0
 */
$yandex_map_api_entry_point_free = 'https://api-maps.yandex.ru/3.0/';

/**
 * @var string $yandex_map_api_entry_point_paid Entry point for free Yandex.Map API version
 * @since 1.0.0
 */
$yandex_map_api_entry_point_paid = 'https://enterprise.api-maps.yandex.ru/3.0/';

$app    = Factory::getApplication();
$option = $app->getInput()->get('option');

// If the field is empty
if (empty($field->value))
{
	return '';
}

$view = $app->getInput()->get('view');

// Don't show in category option
if ($view == 'category' && $fieldParams->get('dont_show_in_category', 0) == 1)
{
	return '';
}

// Yandex.Map API key

if (empty($fieldParams->get('yandex_map_api_key')))
{
	$this->getApplication()->enqueueMessage(Text::_('PLG_WTYANDEXMAP_ERROR_THERE_IS_NO_API_KEY'), 'error');

	return '';
}

// Connect Yandex.Map javascript
$yandexMapHost = $fieldParams->get('yandex_api_type') === 'free' ? $yandex_map_api_entry_point_free : $yandex_map_api_entry_point_paid;
$uri           = new Uri($yandexMapHost);
$uri->setQuery([
	'apikey' => $fieldParams->get('yandex_map_api_key'),
	'lang'   => str_replace('-', '_', $app->getLanguage()->getTag()),
]);
/* @var $wa WebAssetManager */
$wa = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseScript('plg.fields.wtyandexmap.yandex', $uri->toString());

$style = 'display: block; margin:0; padding:0;';

$map_width  = !empty($fieldParams->get('map_width')) ? ' width: ' . $fieldParams->get('map_width') . ';' : '';
$map_height = !empty($fieldParams->get('map_height')) ? ' height: ' . $fieldParams->get('map_height') : ' height: 300px;';

$style .= $map_width . $map_height;

/** @var string $value Coordinates for baloon */
$value = trim((!empty($field->rawvalue) ? $field->rawvalue : $fieldParams->get('map_center')));

/** @var string $layer map type */
$layer = $fieldParams->get('map_type') === 'map' ? 'YMapDefaultSchemeLayer' : 'YMapDefaultSatelliteLayer';
/** @var string $id unique map field id */
$id = 'plg_field_wtyandexmap_' . $item->id . '_' . $field->id;
/** @var string $id marker color verbal or RGB */
$marker_color = $fieldParams->get('marker_color', 'red');

// YMapMarker - with custom HTML in item
// YMapDefaultMarker - standart placemark
$marker_layout = $fieldParams->get('marker_layout', 'default');

$marker_type = ($marker_layout == 'default' ? 'YMapDefaultMarker' : 'YMapMarker');

// Include custom marker layout
if ($marker_layout !== 'default')
{
	$layout = new FileLayout($marker_layout, JPATH_SITE . '/plugins/fields/wtyandexmap/tmpl/markers', ['id' => $id, 'field' => $field, 'field_params' => $fieldParams]);
	echo $layout->render();
}


echo "
	<div id='{$id}'>
	    <wtyandexmap style='{$style}'></wtyandexmap>
	</div>
	<script>
	    document.addEventListener('DOMContentLoaded', initYandexMap_{$id});
	    
	    async function initYandexMap_{$id}()
	    {
	        await ymaps3.ready;
	        const {YMapZoomControl} = await ymaps3.import('@yandex/ymaps3-controls@0.0.1');
	        const {YMapDefaultMarker} = await ymaps3.import('@yandex/ymaps3-markers@0.0.1');
	        const {YMapMarker} = ymaps3;

			const coords = '{$value}';
			const [y,x] = coords.split(',');
	        
	        const container = document.getElementById('{$id}');
	        const elem = container.querySelector('wtyandexmap');
	        const cfg = {
	            location: {
	                center: [x, y],
	                zoom: {$fieldParams->get('map_zoom')}
	            }
	        };

	        const map = new ymaps3.YMap(elem, cfg);
	        map.addChild(new ymaps3.{$layer}());
	        map.addChild(new ymaps3.YMapDefaultFeaturesLayer());
	        map.addChild(
                new ymaps3.YMapControls({position: 'right'}).addChild(new YMapZoomControl())
	        );
			";

// We need to add a layout for marker

if ($marker_type == 'YMapDefaultMarker')
{
	echo " const markerElement = new {$marker_type}({
								coordinates: [x, y],
								//title: 'Hello World!',
								//subtitle: '{$value}',
								color: '{$marker_color}',
							});
						
			map.addChild(markerElement);
			";
}
else
{

	echo "
					const markerTemplate = document.getElementById('{$id}_marker');
					const markerElement = document.createElement('div');
					markerElement.append({$id}_marker.content.cloneNode(true));
					map.addChild(new YMapMarker({coordinates: [x, y]}, markerElement));
					";

}
echo "			
	}
	</script>
	";
