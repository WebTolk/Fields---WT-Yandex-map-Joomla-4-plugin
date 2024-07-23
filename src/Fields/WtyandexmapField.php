<?php
/**
 * @package       WT Yandex Map
 * @version       2.0.0
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

    public function getInput(): string
    {
        $data = parent::getLayoutData();
        $style = 'display: block; margin-bottom: 8px;';
        $params = [];
        foreach ($this->element->attributes() as $key => $value)
        {
            $params[$key] = (string)$value;
        }

        if ($params['map_width'])
        {
            $style .= " width: {$params['map_width']};";
        }
        if ($params['map_height'])
        {
            $style .= " height: {$params['map_height']};";
        }
        else
        {
            $style .= " height: 300px;";
        }

        $value = $data['value'];
        if (empty($value))
        {
            $value = $params['map_center'];
        }
        $layer = $params['map_type'] === 'map' ? 'YMapDefaultSchemeLayer' : 'YMapDefaultSatelliteLayer';

        return "
            <div id='{$data['id']}'>
                <wtyandexmap style='$style'></wtyandexmap>
                <input type='text' name='{$data['name']}' value='{$value}' class='form-control'>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', initYandexMap_{$data['id']});
                
                async function initYandexMap_{$data['id']}()
                {
                    await ymaps3.ready;
                    const {YMapZoomControl} = await ymaps3.import('@yandex/ymaps3-controls@0.0.1');
                    const {YMapDefaultMarker} = await ymaps3.import('@yandex/ymaps3-markers@0.0.1');
                    
                    const container = document.getElementById('{$data['id']}');
                    const elem = container.querySelector('wtyandexmap');
                    const inputEl = container.querySelector('input');
                    const [y, x] = inputEl.value.split(',');
                    
                    const cfg = {
                        location: {
                            center: [x, y],
                            zoom: {$params['map_zoom']}
                        }
                    }
                    
                    const map = new ymaps3.YMap(elem, cfg);
                    map.addChild(new ymaps3.{$layer}());
                    map.addChild(new ymaps3.YMapDefaultFeaturesLayer());
                    map.addChild(new ymaps3.YMapControls({position: 'right'})
                        .addChild(new YMapZoomControl())
                    );
                    
                    const draggableMarker = new YMapDefaultMarker({
                        coordinates: [x, y],
                        draggable: true,
                        onDragEnd: (crds) =>
                        {
                            map.update({location:{center:[crds[0].toFixed(6), crds[1].toFixed(6)],duration:400}});
                            inputEl.value = crds[1].toFixed(6) + ',' + crds[0].toFixed(6);
                        }
                    });
                    map.addChild(draggableMarker);
                    map.addChild(new ymaps3.YMapListener({
                        onClick: (obj, ev) =>
                        {
                            draggableMarker.update({coordinates:[ev.coordinates[0].toFixed(6), ev.coordinates[1].toFixed(6)]});
                            map.update({location:{center:[ev.coordinates[0].toFixed(6), ev.coordinates[1].toFixed(6)],duration:400}});
                            inputEl.value = ev.coordinates[1].toFixed(6) + ',' + ev.coordinates[0].toFixed(6);
                        },
                    }));
                    inputEl.onchange = (ev) =>
                    {
                        const [new_y, new_x] = ev.target.value.split(',');
                        if (isNaN(new_x) || isNaN(new_y))
                        {
                            return;
                        }
                        draggableMarker.update({coordinates:[parseFloat(new_x).toFixed(6), parseFloat(new_y).toFixed(6)]});
                        map.update({location:{center:[parseFloat(new_x).toFixed(6), parseFloat(new_y).toFixed(6)],duration:400}});
                    };
                }
            </script>
        ";
    }
}
