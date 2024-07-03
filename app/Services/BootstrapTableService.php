<?php

namespace App\Services;

class BootstrapTableService
{
    private static string $defaultClasses = "btn btn-xs btn-rounded btn-icon";

    /**
     * @param string $iconClass
     * @param string $url
     * @param array $customClass
     * @param array $customAttributes
     * @return string
     */
    public static function button(string $iconClass, string $url, array $customClass = [], array $customAttributes = [])
    {
        $customClassStr = implode(" ", $customClass);
        $class = self::$defaultClasses . ' ' . $customClassStr;
        $attributes = '';
        if (count($customAttributes) > 0) {
            foreach ($customAttributes as $key => $value) {
                $attributes .= $key . '="' . $value . '" ';
            }
        }
        return '<a href="' . $url . '" class="' . $class . '" ' . $attributes . '><i class="' . $iconClass . '"></i></a>&nbsp;&nbsp;';
    }

    /**
     * @param $url
     * @param bool $modal
     * @return string
     */
    public static function editButton($url, bool $modal = true)
    {
        $customClass = ["edit-data", "btn-gradient-primary"];
        $customAttributes = [
            "title" => trans("Edit")
        ];
        if ($modal) {
            $customAttributes = [
                "title" => "Edit",
                "data-toggle" => "modal",
                "data-target" => "#editModal"
            ];

            $customClass[] = "set-form-url";
        }

        $iconClass = "fa fa-edit";
        return self::button($iconClass, $url, $customClass, $customAttributes);
    }

    /**
     * @param $url
     * @return string
     */
    public static function deleteButton($url)
    {
        $customClass = ["delete-form", "btn-gradient-dark"];
        $customAttributes = [
            "title" => trans("Delete"),
        ];
        $iconClass = "fa fa-trash";
        return self::button($iconClass, $url, $customClass, $customAttributes);
    }

    /**
     * @param $url
     * @param string $title
     * @return string
     */
    public static function restoreButton($url, string $title = "Restore")
    {
        $customClass = ["btn-gradient-success", "restore-data"];
        $customAttributes = [
            "title" => trans($title),
        ];
        $iconClass = "fa fa-refresh";
        return self::button($iconClass, $url, $customClass, $customAttributes);
    }

    /**
     * @param $url
     * @return string
     */
    public static function trashButton($url)
    {
        $customClass = ["btn-gradient-danger", "trash-data"];
        $customAttributes = [
            "title" => trans("Delete Permanent"),
        ];
        $iconClass = "fa fa-times";
        return self::button($iconClass, $url, $customClass, $customAttributes);
    }


    /**
     * @param $url
     * @return string
     */
    public static function viewRelatedDataButton($url) {
        $customClass = ["related-data-form", "btn-inverse-primary"];
        $customAttributes = [
            "title" => trans("View Related Data"),
        ];
        $iconClass = "fa fa-eye";
        return self::button($iconClass, $url, $customClass, $customAttributes);
    }
}
