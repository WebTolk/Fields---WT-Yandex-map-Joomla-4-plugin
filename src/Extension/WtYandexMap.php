<?php
/**
 * @package       WT Yandex Map
 * @version       2.0.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2024 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\Fields\WtYandexMap\Extension;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin;

class WtYandexMap extends FieldsPlugin
{
    protected $autoloadLanguage = true;

    /**
     * @var string Entry point for free Yandex.Map API version
     * @since 1.0.0
     */
    protected static string $api_entry_point_free = 'https://api-maps.yandex.ru/3.0';

    /**
     * @var string Entry point for free Yandex.Map API version
     * @since 1.0.0
     */
    protected static string $api_entry_point_paid = 'https://enterprise.api-maps.yandex.ru/3.0';

    /**
     * Transforms the field into a DOM XML element and appends it as a child on the given parent.
     *
     * @param   \stdClass    $field   The field.
     * @param   \DOMElement  $parent  The field node parent.
     * @param   Form        $form    The form.
     *
     * @return  \DOMElement
     *
     * @since   3.7.0
     */
    public function onCustomFieldsPrepareDom($field, \DOMElement $parent, Form $form)
    {
        $fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);

        if ($fieldNode && $field->type == 'wtyandexmap') {
            $pluginParams = json_decode(PluginHelper::getPlugin('fields', 'wtyandexmap')->params);
            $api_key = $pluginParams->yandex_map_api_key;
            if (empty($api_key)) {
                $this->getApplication()->enqueueMessage('<strong>WT Yandex Map field:</strong> There is no Yandex.Map API key.', 'error');
                return $fieldNode;
            }
            $uri = $pluginParams->yandex_api_type === 'free' ? self::$api_entry_point_free : self::$api_entry_point_paid;
            $uri .= '/?apikey=' . $api_key;
            $uri .= '&lang=' . str_replace('-', '_', $this->getApplication()->getLanguage()->getTag());

            /* @var $wa WebAssetManager */
            $wa = $this->getApplication()->getDocument()->getWebAssetManager();
            $wa->registerAndUseScript('plg.fields.wtyandexmap_api', $uri);

            FormHelper::addFieldPrefix('Joomla\Plugin\Fields\WtYandexMap\Fields');
        }

        return $fieldNode;
    }
}