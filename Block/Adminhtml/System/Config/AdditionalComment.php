<?php

namespace Aurigma\CustomersCanvas\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Provides field with additional information
 */
class AdditionalComment extends Field
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '<div class="customers-canvas-additional-comment__title">' . $element->getLabel() . '</div>';
        $html .= '<div class="customers-canvas-additional-comment__content"><span>' . $element->getComment() . '</span></div>';
        return $this->decorateRowHtml($element, $html);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @param string $html
     * @return string
     */
    private function decorateRowHtml(AbstractElement $element, $html)
    {
        return sprintf(
            '<tr id="row_%s" class="customers-canvas-additional-comment__row"><td colspan="3">
                <div class="customers-canvas-additional-comment">%s</div>
            </td></tr>',
            $element->getHtmlId(),
            $html
        );
    }
}
