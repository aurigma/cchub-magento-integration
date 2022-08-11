<?php

namespace Aurigma\CustomersCanvas\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class EditorMode implements ArrayInterface
{
    public const POPUP_VALUE = 'popup';
    public const INSIDE_PAGE_VALUE = 'insidepage';
    public const FULL_SCREEN_VALUE = 'fullscreen';

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
                    [
                        'value' => EditorMode::FULL_SCREEN_VALUE, 
                        'label' => __('Full screen')
                    ],
                ];
    }

}

?>