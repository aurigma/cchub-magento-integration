<?php

namespace Aurigma\CustomersCanvas\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class EditorMode implements ArrayInterface
{
    public const POPUP_VALUE = 'popup';
    public const INSIDE_PAGE_VALUE = 'insidepage';

    public function toOptionArray()
    {

        return  [
                    [
                        'value' => EditorMode::POPUP_VALUE, 
                        'label' => __('Popup window')
                    ], 
                    [
                        'value' => EditorMode::INSIDE_PAGE_VALUE, 
                        'label' => __('Block inside page')
                    ],
                ];
    }

}

?>