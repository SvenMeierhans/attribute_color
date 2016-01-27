<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 *
 * @package     MetaModels
 * @subpackage  AttributeColor
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\Color;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Image\GenerateHtmlEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ManipulateWidgetEvent;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\DcGeneral\Events\BaseSubscriber;

/**
 * Handle events regarding the color attributes.
 *
 * @package    MetaModels
 * @subpackage AttributeColor
 * @author     Stefan Heimes <cms@men-at-work.de>
 */
class Subscriber extends BaseSubscriber
{
    /**
     * {@inheritDoc}
     */
    public function registerEventsInDispatcher()
    {
        $this->addListener(
            ManipulateWidgetEvent::NAME,
            array($this, 'addColorPicker')
        );
    }

    /**
     * Append the date picker to the widget.
     *
     * @param ManipulateWidgetEvent $event The event.
     *
     * @return void
     */
    public static function addColorPicker(ManipulateWidgetEvent $event)
    {
        $model = $event->getModel();
        if (!$model instanceof Model) {
            return;
        }
        /** @var Model $model */
        $metaModel = $model->getItem()->getMetaModel();
        $property  = $event->getProperty()->getName();

        if (!$metaModel->getAttribute($property) instanceof Color) {
            return;
        }

        $environment = $event->getEnvironment();
        $widget      = $event->getWidget();

        $imageEvent = new GenerateHtmlEvent(
            'pickcolor.gif',
            $environment->getTranslator()->translate('MSC.colorpicker'),
            'style="vertical-align:top;cursor:pointer" id="moo_' . $property . '"'
        );

        $environment->getEventDispatcher()->dispatch(ContaoEvents::IMAGE_GET_HTML, $imageEvent);

        /** @noinspection PhpUndefinedFieldInspection */
        $widget->wizard = $imageEvent->getHtml() . '
            <script>
            new MooRainbow("moo_' . $property . '", {
            id:"ctrl_' . $property . '_0",
            startColor:((cl = $("ctrl_' . $property . '_0").value.hexToRgb(true)) ? cl : [255, 0, 0]),
            imgPath: "assets/mootools/colorpicker/' . $GLOBALS['TL_ASSETS']['COLORPICKER'] . '/images/",
            onComplete: function(color) {
                $("ctrl_' . $property . '_0").value = color.hex.replace("#", "");
            }
            });
            </script>';
    }
}
