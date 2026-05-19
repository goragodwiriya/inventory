<?php
/**
 * @filesource modules/repair/views/view.php
 */

namespace Repair\View;

use Kotchasan\Date;

class View extends \Repair\Helper\Controller
{
    /**
     * Render repair details for notification output.
     *
     * @param array $index
     * @param bool $email
     *
     * @return string
     */
    public static function render($index, $email = false)
    {
        $content = [];
        if ($email) {
            $content[] = '<header>';
            $content[] = '<h4>{LNG_Repair details}</h4>';
            $content[] = '</header>';
        }

        $content[] = '<table class="fullwidth">';
        $content[] = '<tr><td class="item"><span class="icon-number">{LNG_Job No.}</span></td><td class="item"> : </td><td class="item">'.$index['job_id'].'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-profile">{LNG_Informer}</span></td><td class="item"> : </td><td class="item">'.($index['customer_name'] !== '' ? $index['customer_name'] : $index['informer']).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-product">{LNG_Equipment}</span></td><td class="item"> : </td><td class="item">'.$index['topic'].'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-barcode">{LNG_Serial}/{LNG_Registration No}</span></td><td class="item"> : </td><td class="item">'.$index['product_no'].'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-file">{LNG_Problems and repairs details}</span></td><td class="item"> : </td><td class="item">'.nl2br($index['job_description']).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-calendar">{LNG_Appointment}</span></td><td class="item"> : </td><td class="item">'.self::formatDateValue($index['appointment_date']).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-calendar">{LNG_Created}</span></td><td class="item"> : </td><td class="item">'.self::formatDateTimeValue($index['created_at']).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-star0">{LNG_Status}</span></td><td class="item"> : </td><td class="item">'.self::getStatusText((int) $index['status']).'</td></tr>';
        if (!empty($index['operator_name'])) {
            $content[] = '<tr><td class="item"><span class="icon-user">{LNG_Operator}</span></td><td class="item"> : </td><td class="item">'.$index['operator_name'].'</td></tr>';
        }
        if ((float) $index['cost'] > 0) {
            $content[] = '<tr><td class="item"><span class="icon-money">{LNG_Actual cost}</span></td><td class="item"> : </td><td class="item">'.number_format((float) $index['cost'], 2).'</td></tr>';
        }
        if (!empty($index['comment'])) {
            $content[] = '<tr><td class="item"><span class="icon-comments">{LNG_Comment}</span></td><td class="item"> : </td><td class="item">'.nl2br($index['comment']).'</td></tr>';
        }
        if ($email) {
            $content[] = '<tr><td class="item">Url</td><td class="item"> : </td><td class="item"><a href="'.WEB_URL.'">'.WEB_URL.'</a></td></tr>';
        }
        $content[] = '</table>';

        return implode("\n", $content);
    }

    /**
     * Format a date value.
     *
     * @param string $value
     *
     * @return string
     */
    private static function formatDateValue(string $value): string
    {
        return $value === '' ? '-' : Date::format($value, 'd M Y');
    }

    /**
     * Format a datetime value.
     *
     * @param string $value
     *
     * @return string
     */
    private static function formatDateTimeValue(string $value): string
    {
        return $value === '' ? '-' : Date::format($value, 'd M Y H:i');
    }
}