<?php

namespace Aurigma\CustomersCanvas\Helper;

use Aurigma\CustomersCanvas\Setup\InstallData;

class EditorFamilyResolver
{
    public static function isUif($editorFamilyValue): bool
    {
        if ($editorFamilyValue === null || $editorFamilyValue === '') {
            return true;
        }

        return (int)$editorFamilyValue === InstallData::EDITOR_FAMILY_UIF_VALUE;
    }

    public static function isSe($editorFamilyValue): bool
    {
        return (int)$editorFamilyValue === InstallData::EDITOR_FAMILY_SE_VALUE;
    }

    public static function isHandy($editorFamilyValue): bool
    {
        return (int)$editorFamilyValue === InstallData::EDITOR_FAMILY_HANDY_VALUE;
    }

    public static function isKnown($editorFamilyValue): bool
    {
        return self::isUif($editorFamilyValue)
            || self::isSe($editorFamilyValue)
            || self::isHandy($editorFamilyValue);
    }
}
