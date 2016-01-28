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
 * @author      Andreas Isaak <info@andreas-isaak.de>
 * @author      Stefan Heimes <stefan_heimes@hotmail.com>
 * @author      Cliff Parnitzky <github@cliff-parnitzky.de>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\Color;

use MetaModels\Attribute\BaseSimple;
use MetaModels\IMetaModel;
use MetaModels\Render\Setting\ISimple;
use MetaModels\Render\Setting\Simple;
use MetaModels\Render\Template;

/**
 * This is the MetaModelAttribute class for handling color fields.
 *
 * @package    MetaModels
 * @subpackage AttributeColor
 * @author     Stefan Heimes <cms@men-at-work.de>
 */
class Color extends BaseSimple
{
    /**
     * {@inheritDoc}
     */
    public function getSQLDataType()
    {
        return 'TINYBLOB NULL';
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(
            parent::getAttributeSettingNames(),
            array(
                'flag',
                'searchable',
                'filterable',
                'sortable',
                'mandatory'
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldDefinition($arrOverrides = array())
    {
        $arrFieldDef = parent::getFieldDefinition($arrOverrides);

        $arrFieldDef['inputType']              = 'text';
        $arrFieldDef['eval']['maxlength']      = 6;
        $arrFieldDef['eval']['size']           = 2;
        $arrFieldDef['eval']['multiple']       = true;
        $arrFieldDef['eval']['isHexColor']     = true;
        $arrFieldDef['eval']['decodeEntities'] = true;
        $arrFieldDef['eval']['tl_class']      .= ' wizard inline';

        return $arrFieldDef;
    }


    /**
     * {@inheritdoc}
     */
    public function parseValue($arrRowData, $strOutputFormat = 'text', $objSettings = null)
    {
        // Set the Color, Opacity, RGB(A).
        $arrResult['raw']['color']   = $arrRowData[$this->getColName()][0];
        $arrResult['raw']['opacity'] = $arrRowData[$this->getColName()][1];
        if ($arrRowData[$this->getColName()][1] != null) {
            $arrResult['raw']['rgba'] = $this->hex2rgba($arrRowData[$this->getColName()][0],
                                                        $arrRowData[$this->getColName()][1]);
        } else {
            $arrResult['raw']['rgba'] = $this->hex2rgba($arrRowData[$this->getColName()][0]);
        }

        /** @var ISimple $objSettings */
        if ($objSettings && $objSettings->get('template')) {
            $strTemplate = $objSettings->get('template');

            $objTemplate = new Template($strTemplate);

            $this->prepareTemplate($objTemplate, $arrRowData, $arrResult, $objSettings);

            // Now the desired format.
            if ($strValue = $objTemplate->parse($strOutputFormat, false)) {
                $arrResult[$strOutputFormat] = $strValue;
            }

            // Text rendering is mandatory, try with the current setting,
            // upon exception, try again with the default settings, as the template name might have changed.
            // if this fails again, we are definately out of luck and bail the exception.
            try {
                $arrResult['text'] = $objTemplate->parse('text', true);
            } catch (\Exception $e) {
                $objSettingsFallback = $this->getDefaultRenderSettings()->setParent($objSettings->getParent());

                $objTemplate = new Template($objSettingsFallback->get('template'));
                $this->prepareTemplate($objTemplate, $arrRowData, $arrResult, $objSettingsFallback);

                $arrResult['text'] = $objTemplate->parse('text', true);
            }

        } else {
            // Text rendering is mandatory, therefore render using default render settings.
            $arrResult = $this->parseValue($arrResult, 'text', $this->getDefaultRenderSettings());
        }

        // HOOK: apply additional formatters to attribute.
        $arrResult = $this->hookAdditionalFormatters($arrResult, $arrRowData, $strOutputFormat, $objSettings);

        return $arrResult;
    }

    /**
     * When rendered via a template, this populates the template with values.
     *
     * @param Template $objTemplate The Template instance to populate.
     *
     * @param array    $arrRowData  The row data for the current item.
     *
     * @param ISimple  $arrRawData The current raw data.
     *
     * @param ISimple  $objSettings The render settings to use for this attribute.
     *
     * @return void
     */
    protected function prepareTemplate(Template $objTemplate, $arrRowData, $arrRawData, $objSettings)
    {
        $objTemplate->setData(array(
                                  'attribute'        => $this,
                                  'settings'         => $objSettings,
                                  'row'              => $arrRowData,
                                  'raw'              => $arrRawData['raw'],
                                  'additional_class' => $objSettings->get('additional_class')
                                      ? ' ' . $objSettings->get('additional_class')
                                      : ''
                              ));
    }

    /**
     * Hex to RGB(A) Helper function.
     *
     * @param string   $color   The color value as hexadecimal.
     *
     * @param bool|int $opacity The opacity value as an integer e.g. 75 or 15 in percent. Default false.
     *
     * @return string Returns the rgb value or if the opacity is set a rgba value.
     */
    protected function hex2rgba($color, $opacity = false)
    {

        $default = 'rgb(0,0,0)';

        //Return default if no color provided.
        if (empty($color)) {
            return $default;
        }

        //Sanitize $color if "#" is provided.
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        //Check if color has 6 or 3 characters and get values.
        if (strlen($color) == 6) {
            $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) == 3) {
            $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return $default;
        }

        //Convert hexadec to rgb.
        $rgb = array_map('hexdec', $hex);

        //Check if opacity is set(rgba or rgb).
        if ($opacity) {
            if (abs($opacity) > 1) {
                $opacity = $opacity / 100;
            }
            $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(",", $rgb) . ')';
        }

        //Return rgb(a) color string.
        return $output;
    }
}
