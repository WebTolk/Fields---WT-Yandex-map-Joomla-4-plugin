<?php
/**
 * @package    Fields - WT Yandex Map
 * @version       2.1.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2024 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

use Joomla\Plugin\Fields\WtYandexMap\Fields\WtyandexmapField;
use Joomla\Registry\Registry;

extract($displayData);

/**
 *
 * @var string      $autocomplete       Автодополнение поля (HTML-атрибут autocomplete)
 * @var bool        $autofocus         Если true, поле получает фокус автоматически
 * @var string      $class             CSS-класс(ы) для поля
 * @var string|null $description       Описание поля (может быть null)
 * @var bool        $disabled          Если true, поле отключено для ввода
 * @var WtyandexmapField $field        Объект поля WT Yandex Map (Joomla)
 * @var string      $group             Группа поля (например, 'com_fields')
 * @var bool        $hidden            Если true, поле скрыто
 * @var string      $hint              Подсказка внутри поля ввода (плейсхолдер)
 * @var string      $id                HTML-идентификатор поля (атрибут id)
 * @var string      $label             Текст метки (label) поля
 * @var string      $labelclass        CSS-класс(ы) для метки
 * @var bool        $multiple          Если true, разрешён множественный ввод
 * @var string      $name              Имя поля (атрибут name, используется в форме)
 * @var string      $onchange          JavaScript-код при изменении значения
 * @var string      $onclick           JavaScript-код при клике
 * @var string      $pattern           Регулярное выражение для валидации
 * @var string      $validationtext    Текст ошибки при валидации
 * @var bool        $readonly          Если true, поле только для чтения
 * @var bool        $repeat            Если true, поле можно повторять (например, в группах)
 * @var bool        $required          Если true, поле обязательно для заполнения
 * @var int         $size              Размер поля (атрибут size)
 * @var bool        $spellcheck        Если true, проверка орфографии включена
 * @var string      $validate          Тип валидации (например, 'email', 'url')
 * @var string      $value             Текущее значение поля (например, "54.861538, 60.454256")
 * @var string      $dataAttribute     Дополнительные data-атрибуты (HTML5)
 * @var array       $dataAttributes    Массив data-атрибутов
 * @var string|null $parentclass       CSS-класс родительского элемента (или null)
 * @var array       $fieldparams       Дополнительные параметры поля (подробности в fieldparams)
 */

$fieldparams = new Registry($fieldparams);
$style = 'display: block; margin-bottom: 8px;';

if (!empty($map_width = $fieldparams->get('map_width','')))
{
	$style .= " width: {$map_width};";
}
if (!empty($map_height = $fieldparams->get('map_height','')))
{
	$style .= " height: {$map_height};";
}
else
{
	$style .= " height: 300px;";
}

if (empty($value) && !$fieldparams->get('get_geolocation_if_empty_field', false))
{
	$value = $fieldparams->get('map_center');
}

$layer = $fieldparams->get('map_type') === 'map' ? 'YMapDefaultSchemeLayer' : 'YMapDefaultSatelliteLayer';
?>

<div id="<?php echo $id;?>">
    <wtyandexmap style="<?php echo $style;?>"></wtyandexmap>
    <input type="text" name="<?php echo $name;?>" value="<?php echo $value;?>" class="form-control">
</div>
<script>
    document.addEventListener('DOMContentLoaded', initYandexMap_<?php echo $id;?>);

    async function initYandexMap_<?php echo $id;?>() {
        await ymaps3.ready;
        const {YMapZoomControl} = await ymaps3.import('@yandex/ymaps3-controls@0.0.1');
        const {YMapDefaultMarker} = await ymaps3.import('@yandex/ymaps3-markers@0.0.1');

        const container = document.getElementById("<?php echo $id;?>");
        const elem = container.querySelector("wtyandexmap");
        const inputEl = container.querySelector("input");
        let mapCenter = inputEl.value.split(",");
        mapCenter.reverse();
        const position = await ymaps3.geolocation.getPosition();
        console.log(position);
        if(position) {
            mapCenter = position.coords;
        }

        const cfg = {
            location: {
                center: mapCenter,
                zoom: <?php echo $fieldparams->get('map_zoom');?>
            }
        }

        const map = new ymaps3.YMap(elem, cfg);
        map.addChild(new ymaps3.<?php echo $layer;?>());
        map.addChild(new ymaps3.YMapDefaultFeaturesLayer());
        map.addChild(new ymaps3.YMapControls({position: 'right'})
            .addChild(new YMapZoomControl())
        );

        const draggableMarker = new YMapDefaultMarker({
            coordinates: mapCenter,
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
