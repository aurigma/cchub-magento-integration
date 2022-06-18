<?php

namespace Aurigma\CustomersCanvas\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class NoAutoComplete extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $parentHtml = parent::_getElementHtml($element);

        $htmlArray = explode(' ', $parentHtml);

        $htmlArray = array_slice($htmlArray, 0, 1, true) +
                array('readonly' => 'readonly="readonly"') +
                array_slice($htmlArray, 1, count($htmlArray)-1, true);
        $htmlArray = array_slice($htmlArray, 0, 1, true) +
                array('onfocus' => "onfocus=\"this.removeAttribute('readonly');\"") +
                array_slice($htmlArray, 1, count($htmlArray)-1, true);
        $htmlArray = array_slice($htmlArray, 0, 1, true) +
                array('onfocusout' => "onfocusout=\"this.setAttribute('readonly','readonly');\"") +
                array_slice($htmlArray, 1, count($htmlArray)-1, true);
        $htmlArray = array_slice($htmlArray, 0, 1, true) +
                array('onmouseover' => "onmouseover=\"this.removeAttribute('readonly');\"") +
                array_slice($htmlArray, 1, count($htmlArray)-1, true);
        $htmlArray = array_slice($htmlArray, 0, 1, true) +
                array('autocomplete' => 'autocomplete="off"') +
                array_slice($htmlArray, 1, count($htmlArray)-1, true);
                

        $html = implode(' ', $htmlArray);

        $html = '<div class="customers-canvas__input-password-group">'
                    .$html.
                    '<button type="button" class="customers-canvas__input-hide customers-canvas__form-action-button">
                        <span class="customers-canvas__button-text">'.__('Show').'</span>
                    </button>
                    <button type="button" class="customers-canvas__input-copy customers-canvas__form-action-button">
                        <span class="customers-canvas__button-text">'.__('Copy').'</span>
                    </button>
                </div>';

        return $html;
    }
}
?>